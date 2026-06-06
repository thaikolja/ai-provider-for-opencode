<?php

declare( strict_types=1 );

namespace WordPress\OpenCodeAiProvider\Metadata;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

/**
 * Class for the OpenCode model metadata directory.
 *
 * @since 1.0.0
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string}>
 * }
 */
class OpenCodeModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {
	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected function createRequest( HttpMethodEnum $method, string $path, array $headers = [], $data = null ): Request {
		return new Request( $method, OpenCodeProvider::url( $path ), $headers, $data );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected function parseResponseToModelMetadataList( Response $response ): array {
		/** @var ModelsResponseData $responseData */
		$responseData = $response->getData();
		if ( ! isset( $responseData['data'] ) || ! $responseData['data'] ) {
			throw ResponseException::fromMissingData( 'OpenCode', 'data' );
		}

		$baseTextOptions = [
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::outputMimeType(), [ 'text/plain', 'application/json' ] ),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption( OptionEnum::inputModalities(), [ [ ModalityEnum::text() ] ] ),
			new SupportedOption( OptionEnum::outputModalities(), [ [ ModalityEnum::text() ] ] ),
		];

		$modelsData = (array) $responseData['data'];

		$models = array_map( function ( array $modelData ) use ( $baseTextOptions ): ModelMetadata {
			$modelId = $modelData['id'];

			$options = $baseTextOptions;
			// Currently OpenCode primarily supports chat completions
			// You can add logic here to differentiate capabilities based on model ID if OpenCode exposes them
			// We're keeping it simple and broad for now based on deepseek-v4-flash.

			return new ModelMetadata( $modelId, self::formatDisplayName( $modelId ), [ CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory() ], $options );
		}, $modelsData );

		usort( $models, [ $this, 'modelSortCallback' ] );

		return $models;
	}

	/**
	 * Formats technical IDs into readable names.
	 *
	 * @param string $id ID.
	 *
	 * @since 1.0.0
	 */
	private static function formatDisplayName( string $id ): string {
		$map = [
			'deepseek-v4-flash' => 'DeepSeek-V4-Flash',
			'deepseek-v4-pro'   => 'DeepSeek-V4-Pro',
		];

		return $map[ $id ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $id ) );
	}

	/**
	 * Callback function for sorting models by ID, to be used with `usort()`.
	 *
	 * @param ModelMetadata $a First model.
	 * @param ModelMetadata $b Second model.
	 *
	 * @return int Comparison result.
	 * @since 1.0.0
	 *
	 */
	protected function modelSortCallback( ModelMetadata $a, ModelMetadata $b ): int {
		$aId = $a->getId();
		$bId = $b->getId();

		// Put preferred/flash/pro models near top.
		$priority = [
			'deepseek-v4-pro'   => 1,
			'deepseek-v4-flash' => 2,
		];

		$aPriority = $priority[ $aId ] ?? 99;
		$bPriority = $priority[ $bId ] ?? 99;

		if ( $aPriority !== $bPriority ) {
			return $aPriority <=> $bPriority;
		}

		return strcmp( $aId, $bId );
	}
}
