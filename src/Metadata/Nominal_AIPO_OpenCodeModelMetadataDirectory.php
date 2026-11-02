<?php
/**
 * AI Provider for OpenCode.
 *
 * Connects OpenCode models to WordPress' native AI SDK.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Released under the GNU General Public License v2 or later.
 * See: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package    Nominal\AIProviderOpenCode
 * @author     Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright  2026 (C) Kolja Nolte
 * @license    GPL-2.0-or-later
 * @link       https://docs.kolja-nolte.com/opencode-wordpress-ai-provider
 */

/**
 * Model discovery for the OpenCode provider.
 *
 * @package Nominal\AIProviderOpenCode
 */

declare( strict_types=1 );

namespace Nominal\AIProviderOpenCode\Metadata;

// We need the provider to build full API URLs (base URL + path).
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

// Enums and value objects the SDK needs for describing what models can do.
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

// The abstract base that handles most of the OpenAI-compatible model listing logic.
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Handles discovery and parsing of OpenCode's model list.
 *
 * This is where we hit the /models endpoint, parse the JSON response,
 * and turn each model into a fully described ModelMetadata object that
 * the SDK and UI can work with.
 *
 * @since 1.0.0
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string}>
 * }
 */
class Nominal_AIPO_OpenCodeModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * Builds a proper HTTP request pointed at the OpenCode API.
	 *
	 * Takes the relative path (like "/models") and prepends the full base URL.
	 * The OpenCode session headers are attached so the listing call carries the
	 * same `x-opencode-session` value as the generation calls.
	 *
	 * @param  HttpMethodEnum                      $method   GET or POST, depending on the call.
	 * @param  string                              $path     Relative API path (for example "/models").
	 * @param  array<string, string|list<string>>  $headers  Extra HTTP headers if needed.
	 * @param  string|array<string, mixed>|null    $data     Request body for POST calls.
	 *
	 * @return Request                             A fully configured request ready to send.
	 * @since  1.0.0
	 */
	final public function createRequest (
		HttpMethodEnum $method,
		string         $path,
		array          $headers = [],
		               $data = null
	): Request {

		// Merge in the OpenCode session header so model listing uses the same
		// request-scoped ID as generation calls.
		$headers = array_merge( $headers, Nominal_AIPO_OpenCodeProvider::requestHeaders() );

		// Nominal_AIPO_OpenCodeProvider::url() glues the base URL and the path together for us.
		return new Request(
			$method,
			Nominal_AIPO_OpenCodeProvider::url ( $path ),
			$headers,
			$data
		);
	}

	/**
	 * Takes the raw /models API response and turns it into a list of model metadata.
	 *
	 * This is the core of the discovery process. We parse the JSON, build a
	 * standard set of options every model gets, and wrap everything up as
	 * ModelMetadata objects.
	 *
	 * @param  Response  $response  The raw API response from OpenCode.
	 *
	 * @return ModelMetadata[]    A sorted list of models the provider can use.
	 * @throws ResponseException  When the response is missing the expected data key.
	 * @since  1.0.0
	 */
	final public function parseResponseToModelMetadataList ( Response $response ): array {

		// Pull the decoded JSON body out of the response object.
		/**
		 * Decoded model list from the Go API.
		 *
		 * @var ModelsResponseData $response_data
		 */
		$response_data = $response->getData ();

		// Bail loudly if the API didn't give us a model list under the "data" key.
		if ( ! isset( $response_data[ 'data' ] ) || ! $response_data[ 'data' ] ) {
			throw ResponseException::fromMissingData ( 'OpenCode', 'data' );
		}

		// Every text-generation model shares this same set of supported options.
		// We keep it broad. Most models can handle these, and it avoids
		// conditional logic for models that don't expose fine-grained capabilities.
		$base_text_options = [
			new SupportedOption( OptionEnum::systemInstruction () ),
			new SupportedOption( OptionEnum::functionDeclarations () ),
			new SupportedOption( OptionEnum::maxTokens () ),
			new SupportedOption( OptionEnum::temperature () ),
			new SupportedOption( OptionEnum::topP () ),
			new SupportedOption( OptionEnum::stopSequences () ),
			new SupportedOption(
				OptionEnum::outputMimeType (),
				[ 'text/plain', 'application/json' ]
			),
			new SupportedOption( OptionEnum::customOptions () ),
			new SupportedOption(
				OptionEnum::inputModalities (),
				[ [ ModalityEnum::text () ] ]
			),
			new SupportedOption(
				OptionEnum::outputModalities (),
				[ [ ModalityEnum::text () ] ]
			),
		];

		// Cast to array just in case the API returned something unexpected.
		$models_data = (array) $response_data[ 'data' ];

		// Transform each raw model entry from the API into a ModelMetadata object.
		$models = array_map (
			static function ( array $model_data ) use ( $base_text_options ): ModelMetadata {

				// The model ID is the canonical slug (for example "deepseek-v4-pro").
				$model_id = $model_data[ 'id' ];

				// Every model gets text generation and chat history. We don't do
				// fine-grained capability checks here. It's simpler and covers
				// everything OpenCode currently offers.
				return new ModelMetadata(
					$model_id,
					self::formatDisplayName ( $model_id ),
					[
						CapabilityEnum::textGeneration (),
						CapabilityEnum::chatHistory (),
					],
					$base_text_options
				);
			},
			$models_data
		);

		// Alphabetical by ID. New models from the API slot in without a priority table.
		usort ( $models, [ $this, 'modelSortCallback' ] );

		return $models;
	}

	/**
	 * Converts a technical model ID into something humans can read.
	 *
	 * We have pretty names for the models we know about. Anything else
	 * gets an auto-generated name by replacing dashes and underscores
	 * with spaces and capitalising each word.
	 *
	 * @param  string  $id  The raw model ID from the API (for example "deepseek-v4-flash").
	 *
	 * @return string  A display-friendly name (for example "DeepSeek-V4-Flash").
	 * @since  1.0.0
	 */
	final public static function formatDisplayName ( string $id ): string {

		// Pretty names for the two flagship IDs. Everything else is auto-formatted.
		$map = [
			'deepseek-v4-pro'   => 'DeepSeek-V4-Pro',
			'deepseek-v4-flash' => 'DeepSeek-V4-Flash',
		];

		// If the ID is in our map, use the pretty name. Otherwise auto-format it.
		return $map[ $id ] ?? ucwords ( str_replace ( [ '-', '_' ], ' ', $id ) );
	}

	/**
	 * Sorting callback used with usort() to order models in a sensible way.
	 *
	 * Sorts models alphabetically by ID so new API models land in a stable order
	 * without a hand-maintained priority list.
	 *
	 * @param  ModelMetadata  $a  First model to compare.
	 * @param  ModelMetadata  $b  Second model to compare.
	 *
	 * @return int            Negative if $a comes first, positive if $b comes first.
	 * @since  1.0.0
	 */
	final public function modelSortCallback ( ModelMetadata $a, ModelMetadata $b ): int {
		return strcmp ( $a->getId (), $b->getId () );
	}
}
