<?php
/**
 * AI Provider for OpenCode.
 *
 * Connects OpenCode to WordPress.
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
 * Reads the OpenCode model list.
 *
 * @package Nominal\AIProviderOpenCode
 */

// Keep type checks strict.
declare( strict_types=1 );

// This class lives with the other metadata code.
namespace Nominal\AIProviderOpenCode\Metadata;

// Builds the OpenCode address and shared headers.
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

// Text is the only input and output this plugin advertises.
use WordPress\AiClient\Messages\Enums\ModalityEnum;

// The request sent to OpenCode.
use WordPress\AiClient\Providers\Http\DTO\Request;

// The reply that contains the model list.
use WordPress\AiClient\Providers\Http\DTO\Response;

// GET for the model list, POST for generation.
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

// Thrown when the model list is not in the expected shape.
use WordPress\AiClient\Providers\Http\Exception\ResponseException;

// One model, as WordPress wants it described.
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

// One setting a model accepts, such as temperature.
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;

// Text generation and conversation history.
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

// Names of the settings a model accepts.
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

// Shared model-list behavior for an OpenAI-compatible API.
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Fetches OpenCode's models and describes each one to WordPress.
 *
 * @since 1.0.0
 */
class Nominal_AIPO_OpenCodeModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * Builds the request that asks OpenCode for data.
	 *
	 * The path is appended to the OpenCode address, and the shared headers
	 * are added so this call uses the same session id as text generation.
	 *
	 * @param HttpMethodEnum                     $method  GET or POST.
	 * @param string                             $path    Path after the OpenCode address.
	 * @param array<string, string|list<string>> $headers Extra headers, if the caller set any.
	 * @param string|array<string, mixed>|null   $data    Body for a POST request.
	 *
	 * @return Request The request ready to send.
	 * @since  1.0.0
	 */
	final protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = array(),
		$data = null
	): Request {
		// Point the call at OpenCode and attach the shared headers.
		return new Request(
			$method,
			Nominal_AIPO_OpenCodeProvider::url( $path ),
			Nominal_AIPO_OpenCodeProvider::applyRequestHeaders( $headers ),
			$data
		);
	}

	/**
	 * Turns the model-list reply into models WordPress can show.
	 *
	 * An empty list is fine. A reply without a list of models is not.
	 * Rows that have no id are skipped. The rest are sorted by id.
	 *
	 * @param Response $response The reply from OpenCode.
	 *
	 * @return ModelMetadata[]   Models, sorted by id.
	 * @throws ResponseException When the reply has no model list.
	 * @since  1.0.0
	 */
	final public function parseResponseToModelMetadataList( Response $response ): array {
		// The decoded reply.
		$response_data = $response->getData();

		// A missing or broken list cannot be shown.
		if ( ! is_array( $response_data ) || ! isset( $response_data['data'] ) || ! is_array(
			$response_data['data']
		) ) {
			// Tell the caller the model list was not there.
			throw ResponseException::fromMissingData( 'OpenCode', 'data' );
		}

		// Settings every text model is assumed to accept.
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

		// Models worth showing.
		$models = array();

		// One bad row should not hide the rest of the list.
		foreach ( $response_data['data'] as $model_data ) {
			// Skip anything that is not a model record.
			if ( ! is_array( $model_data ) ) {
				// This row cannot be described.
				continue;
			}

			// Turn this row into a model, or skip it when it has no id.
			$model = self::metadataFromRow( $model_data, $base_text_options );

			// No id, so there is nothing to list.
			if ( null === $model ) {
				// Move on to the next row.
				continue;
			}

			// Keep this model.
			$models[] = $model;
		}

		// Alphabetical by id, so the order stays stable as models come and go.
		usort( $models, [ $this, 'modelSortCallback' ] );

		// The list WordPress will show.
		return $models;
	}

	/**
	 * Builds one model from one row of the list.
	 *
	 * The name OpenCode sends is used when it is there. Otherwise the id
	 * is turned into a readable label. A row with no id is skipped.
	 *
	 * @param array<string, mixed> $model_data One entry from the model list.
	 * @param SupportedOption[]    $options    Settings shared by every text model.
	 *
	 * @return ModelMetadata|null The model, or null when the row has no id.
	 * @since  1.1.1
	 */
	private static function metadataFromRow( array $model_data, array $options ): ?ModelMetadata {
		// The id WordPress will store. Missing means this row is unusable.
		$model_id = $model_data['id'] ?? null;

		// Skip a row that has no real id.
		if ( ! is_string( $model_id ) || '' === $model_id ) {
			// The caller drops this row.
			return null;
		}

		// Prefer the name OpenCode sent.
		$model_name = $model_data['name'] ?? null;

		// Fall back to a label made from the id.
		if ( ! is_string( $model_name ) || '' === $model_name ) {
			// Split the id into words OpenCode did not name.
			$model_name = self::formatDisplayName( $model_id );
		}

		// A text model that can also keep a conversation going.
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
	 * Makes a readable name from a model id.
	 *
	 * Used only when OpenCode did not send a name. A few known ids have a
	 * fixed label. Every other id is split on dashes and underscores.
	 *
	 * @param string $id The model id from OpenCode, for example "deepseek-v4.1-flash".
	 *
	 * @return string A name people can read, for example "DeepSeek V4.1 Flash".
	 * @since  1.0.0
	 */
	final public static function formatDisplayName( string $id ): string {
		// Labels for ids that read badly if they are only split into words.
		$map = [
			'deepseek-v4-pro'     => 'DeepSeek V4 Pro',
			'deepseek-v4-flash'   => 'DeepSeek V4 Flash',
			'deepseek-v4.1-flash' => 'DeepSeek V4.1 Flash',
		];

		// Use the known label, or turn the id into words.
		return $map[ $id ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $id ) );
	}

	/**
	 * Compares two models by id for sorting.
	 *
	 * @param ModelMetadata $a The first model.
	 * @param ModelMetadata $b The second model.
	 *
	 * @return int Negative when $a comes first, positive when $b comes first.
	 * @since  1.0.0
	 */
	final public function modelSortCallback( ModelMetadata $a, ModelMetadata $b ): int {
		// Plain alphabetical order.
		return strcmp( $a->getId(), $b->getId() );
	}
}
