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
 * OpenCode provider for the PHP AI Client.
 *
 * @package Nominal\AIProviderOpenCode
 */

declare( strict_types=1 );

namespace Nominal\AIProviderOpenCode\Provider;

// Our own classes that fill in the OpenCode-specific details.
use Nominal\AIProviderOpenCode\Metadata\Nominal_AIPO_OpenCodeModelMetadataDirectory;
use Nominal\AIProviderOpenCode\Models\Nominal_AIPO_OpenCodeTextGenerationModel;

// The core AI Client. We need this for version checks and the registry.
use WordPress\AiClient\AiClient;

// Thrown when a model has capabilities we can't handle (shouldn't happen in practice).
use WordPress\AiClient\Common\Exception\RuntimeException;

// The abstract base that does all the heavy lifting for API-based providers.
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;

// Tells the SDK this provider is "available" by hitting the models endpoint.
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;

// Interfaces we implement through the abstract base, listed explicitly for clarity.
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

// Value objects the SDK uses to pass around provider and model info.
use WordPress\AiClient\Providers\DTO\ProviderMetadata;

// Enums so we don't pass around loose strings for things like provider type.
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * The main entry point for the OpenCode AI provider.
 *
 * Extends the SDK's AbstractApiProvider, which means we get OpenAI-compatible
 * behavior for free. We plug in the right URL and wire up our own model
 * directory and text generation model.
 *
 * @since 1.0.0
 */
class Nominal_AIPO_OpenCodeProvider extends AbstractApiProvider {

	/**
	 * The root URL for all OpenCode API calls.
	 *
	 * Every request this provider makes will start from this base.
	 * The /inference/go/v1 path points to the OpenAI-compatible endpoint.
	 *
	 * @return string The base API URL.
	 * @since  1.0.0
	 */
	public static function baseUrl(): string {
		// If this URL ever changes, here's where you fix it, once, for everything.
		return 'https://opencode.ai/inference/go/v1';
	}

	/**
	 * Returns the extra HTTP headers sent with every OpenCode API request.
	 *
	 * OpenCode Go requires the `x-opencode-session` header. This provider serves
	 * one-off requests rather than conversations, so a random ID generated once per
	 * PHP request is enough and no external configuration is needed.
	 *
	 * @return array<string, string> Headers to merge into the outgoing request.
	 * @since  1.1.0
	 */
	public static function requestHeaders(): array {
		static $session_id = '';

		if ( '' === $session_id ) {
			$session_id = 'aipo-' . bin2hex( random_bytes( 16 ) );
		}

		return [ 'x-opencode-session' => $session_id ];
	}

	/**
	 * Creates a model instance based on what the API says it can do.
	 *
	 * The SDK calls this for every model the metadata directory discovers.
	 * We peek at the model's capabilities. If it can generate text, we spin up
	 * the appropriate model class. Anything else gets a loud complaint.
	 *
	 * @param ModelMetadata    $model_metadata    The model's metadata from the API.
	 * @param ProviderMetadata $provider_metadata Info about this provider (OpenCode).
	 *
	 * @return ModelInterface    A ready-to-use model instance.
	 * @throws RuntimeException  When the model's capabilities aren't supported.
	 * @since  1.0.0
	 */
	public static function createModel(
		ModelMetadata $model_metadata, ProviderMetadata $provider_metadata
	): ModelInterface {

		// Ask the metadata what this model claims to be able to do.
		$capabilities = $model_metadata->getSupportedCapabilities();

		// Walk through all the claimed capabilities and pick the first one we support.
		foreach ( $capabilities as $capability ) {
			// Text generation is our bread and butter. Handle that one.
			if ( $capability->isTextGeneration() ) {
				return new Nominal_AIPO_OpenCodeTextGenerationModel( $model_metadata, $provider_metadata );
			}
		}

		// If we got here, the model wants something we don't offer. That's a problem.
		$capability_names = implode( ', ', $capabilities );
		if ( function_exists( 'esc_html' ) ) {
			$capability_names = esc_html( $capability_names );
		}

		// Exception text is not HTML. Escape above when WordPress provides esc_html().
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new RuntimeException( 'Unsupported model capabilities: ' . $capability_names );
	}

	/**
	 * Builds the metadata blob that describes this provider to the SDK.
	 *
	 * This is where we decide how OpenCode looks and reads in the UI.
	 * Some fields (description, logo) only get added if the installed
	 * SDK version supports them. We don't want to crash old setups.
	 *
	 * @return ProviderMetadata The fully assembled metadata object.
	 * @since  1.0.0
	 */
	public static function createProviderMetadata(): ProviderMetadata {

		// The base set of arguments: slug, display name, type, homepage, auth method.
		$provider_metadata_args = [
			'opencode',                             // Internal slug. Used in ->usingProvider().
			'OpenCode',                             // Human-readable name for the UI.
			ProviderTypeEnum::cloud(),              // This is a cloud/remote provider.
			'https://opencode.ai/',                 // Link to the service's main site.
			RequestAuthenticationMethod::apiKey(),  // All requests are authed with an API key.
		];

		// SDK 1.2.0+ lets us attach a short description to the provider.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			// If we're inside WordPress, make the description translatable.
			$provider_metadata_args[] = __( 'Text generation with OpenCode.', 'nominal-ai-provider-for-opencode' );
		}

		// SDK 1.3.0+ lets us show a logo next to the provider name.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			// Point to our bundled icon so the UI has something pretty to display.
			$provider_metadata_args[] = dirname( __DIR__, 2 ) . '/assets/images/opencode.svg';
		}

		// Splat the args into the ProviderMetadata constructor.
		return new ProviderMetadata( ...$provider_metadata_args );
	}

	/**
	 * Decides whether this provider is "available" (the API is reachable).
	 *
	 * We use the SDK's built-in strategy: try listing models. If the call
	 * succeeds, the provider lights up as available in the UI.
	 *
	 * @return ProviderAvailabilityInterface An object that knows how to check availability.
	 * @since  1.0.0
	 */
	public static function createProviderAvailability(): ProviderAvailabilityInterface {
		// The simple approach: if we can fetch the model list, the API is alive.
		return new ListModelsApiBasedProviderAvailability( static::modelMetadataDirectory() );
	}

	/**
	 * Spins up the directory that knows how to fetch and parse the /models response.
	 *
	 * @return ModelMetadataDirectoryInterface Our custom metadata directory.
	 * @since  1.0.0
	 */
	public static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		// Straightforward. Just hand back our OpenCode-specific implementation.
		return new Nominal_AIPO_OpenCodeModelMetadataDirectory();
	}
}
