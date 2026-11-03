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
	final protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = array(),
		$data = null
	): Request {
		return new Request(
			$method,
			Nominal_AIPO_OpenCodeProvider::url( $path ),
			Nominal_AIPO_OpenCodeProvider::applyRequestHeaders( $headers ),
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
	final public function parseResponseToModelMetadataList( Response $response ): array {

		$response_data = $response->getData();

		// An empty list is a valid catalog. A missing or non-array "data" key is not.
		if ( ! is_array( $response_data ) || ! isset( $response_data['data'] ) || ! is_array(
			$response_data['data']
		) ) {
			throw ResponseException::fromMissingData( 'OpenCode', 'data' );
		}

		// Every text-generation model shares this same set of supported options.
		// We keep it broad. Most models can handle these, and it avoids
		// conditional logic for models that don't expose fine-grained capabilities.
		$base_text_options = [
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::functionDeclarations() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption(
				OptionEnum::outputMimeType(),
				[ 'text/plain', 'application/json' ]
			),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption(
				OptionEnum::inputModalities(),
				[ [ ModalityEnum::text() ] ]
			),
			new SupportedOption(
				OptionEnum::outputModalities(),
				[ [ ModalityEnum::text() ] ]
			),
		];

		// Skip rows the catalog cannot identify. One bad entry must not hide the rest.
		$models = array();
		foreach ( $response_data['data'] as $model_data ) {
			if ( ! is_array( $model_data ) ) {
				continue;
			}

			$model = self::metadataFromRow( $model_data, $base_text_options );
			if ( null === $model ) {
				continue;
			}

			$models[] = $model;
		}

		// Alphabetical by ID. New models from the API slot in without a priority table.
		usort( $models, [ $this, 'modelSortCallback' ] );

		return $models;
	}

	/**
	 * Builds metadata for one catalog row.
	 *
	 * The API name wins. A missing or empty name falls back to formatDisplayName().
	 * Rows without a usable ID are skipped by the caller.
	 *
	 * @param array<string, mixed> $model_data One entry from the models payload.
	 * @param SupportedOption[]    $options    Options shared by every text model.
	 *
	 * @return ModelMetadata|null Metadata for the row, or null when it has no ID.
	 * @since  1.1.1
	 */
	private static function metadataFromRow( array $model_data, array $options ): ?ModelMetadata {
		$model_id = $model_data['id'] ?? null;
		if ( ! is_string( $model_id ) || '' === $model_id ) {
			return null;
		}

		$model_name = $model_data['name'] ?? null;
		if ( ! is_string( $model_name ) || '' === $model_name ) {
			$model_name = self::formatDisplayName( $model_id );
		}

		return new ModelMetadata(
			$model_id,
			$model_name,
			array(
				CapabilityEnum::textGeneration(),
				CapabilityEnum::chatHistory(),
			),
			$options
		);
	}

	/**
	 * Converts a technical model ID into something humans can read.
	 *
	 * Used when the model list omits `name`. Known IDs get a fixed label.
	 * Anything else is generated by replacing dashes and underscores with
	 * spaces and capitalising each word.
	 *
	 * @param  string  $id  The raw model ID from the API (for example "deepseek-v4.1-flash").
	 *
	 * @return string  A display-friendly name (for example "DeepSeek V4.1 Flash").
	 * @since  1.0.0
	 */
	final public static function formatDisplayName( string $id ): string {

		// Fallback labels. The live catalog usually sends its own name.
		$map = [
			'deepseek-v4-pro'     => 'DeepSeek V4 Pro',
			'deepseek-v4-flash'   => 'DeepSeek V4 Flash',
			'deepseek-v4.1-flash' => 'DeepSeek V4.1 Flash',
		];

		// If the ID is in our map, use the pretty name. Otherwise auto-format it.
		return $map[ $id ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $id ) );
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
	final public function modelSortCallback( ModelMetadata $a, ModelMetadata $b ): int {
		return strcmp( $a->getId(), $b->getId() );
	}
}
