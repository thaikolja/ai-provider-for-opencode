<?php

declare( strict_types=1 );

namespace WordPress\OpenCodeAiProvider\Provider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\OpenCodeAiProvider\Metadata\OpenCodeModelMetadataDirectory;
use WordPress\OpenCodeAiProvider\Models\OpenCodeTextGenerationModel;

/**
 * Class for the AI Provider for OpenCode.
 *
 * @since 1.0.0
 */
class OpenCodeProvider extends AbstractApiProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function baseUrl(): string {
		return 'https://opencode.ai/zen/go/v1';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createModel(
		ModelMetadata $modelMetadata, ProviderMetadata $providerMetadata
	): ModelInterface {
		$capabilities = $modelMetadata->getSupportedCapabilities();
		foreach ( $capabilities as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new OpenCodeTextGenerationModel( $modelMetadata, $providerMetadata );
			}
		}

		throw new RuntimeException( 'Unsupported model capabilities: ' . implode( ', ', $capabilities ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$providerMetadataArgs = [
			'opencode',
			'OpenCode',
			ProviderTypeEnum::cloud(),
			'https://opencode.ai/',
			RequestAuthenticationMethod::apiKey()
		];
		// Provider description support was added in 1.2.0.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			// For WordPress, we should translate the description.
			if ( function_exists( '__' ) ) {
				// phpcs:ignore Generic.Files.LineLength.TooLong
				$providerMetadataArgs[] = __( 'Text generation with OpenCode.', 'ai-provider-for-opencode' );
			} else {
				$providerMetadataArgs[] = 'Text generation with OpenCode.';
			}
		}
		// Provider logoPath support was added in 1.3.0.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			// We use a generic AI icon for OpenCode. You can replace it later if you have a specific svg
			$providerMetadataArgs[] = dirname( __DIR__, 2 ) . '/assets/images/opencode.svg';
		}

		return new ProviderMetadata( ...$providerMetadataArgs );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		// Check valid API access by attempting to list models.
		return new ListModelsApiBasedProviderAvailability( static::modelMetadataDirectory() );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new OpenCodeModelMetadataDirectory();
	}
}
