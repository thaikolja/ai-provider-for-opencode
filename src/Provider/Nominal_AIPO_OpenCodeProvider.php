<?php

declare( strict_types = 1 );

namespace Nominal\AIProviderOpenCode\Provider;

// Our own classes that fill in the OpenCode-specific details.
use Nominal\AIProviderOpenCode\Metadata\Nominal_AIPO_OpenCodeModelMetadataDirectory;
use Nominal\AIProviderOpenCode\Models\Nominal_AIPO_OpenCodeTextGenerationModel;
// The core AI Client — we need this for version checks and the registry.
use WordPress\AiClient\AiClient;
// Thrown when a model has capabilities we can't handle (shouldn't happen in practice).
use WordPress\AiClient\Common\Exception\RuntimeException;
// The abstract base that does all the heavy lifting for API-based providers.
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
// Tells the SDK this provider is "available" by hitting the models endpoint.
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
// Interfaces we implement through the abstract base — listed explicitly for clarity.
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
 * behavior for free — we just plug in the right URL and wire up our own
 * model directory and text generation model.
 *
 * @since 1.0.0
 */
class Nominal_AIPO_OpenCodeProvider extends AbstractApiProvider {

	/**
	 * The HTTP header OpenCode uses to correlate related requests into a session.
	 *
	 * OpenCode Go uses this value to optimise routing and prompt caching, so it
	 * should stay stable for the lifetime of a conversation.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	private const SESSION_HEADER = 'x-opencode-session';

	/**
	 * Cached session ID, kept stable for the duration of the current PHP request.
	 *
	 * @since 1.1.0
	 * @var string|null
	 */
	private static ?string $sessionId = null;

	/**
	 * The root URL for all OpenCode API calls.
	 *
	 * Every request this provider makes will start from this base.
	 * The /inference/go/v1 path points to the OpenAI-compatible endpoint.
	 *
	 * @since 1.0.0
	 * @return string The base API URL.
	 */
	protected static function baseUrl(): string {
		// If this URL ever changes, here's where you fix it — once, for everything.
		return 'https://opencode.ai/inference/go/v1';
	}

	/**
	 * Returns the extra HTTP headers sent with every OpenCode API request.
	 *
	 * This currently carries the OpenCode session ID, which OpenCode Go uses to
	 * attribute requests to a conversation.
	 *
	 * @since 1.1.0
	 * @return array<string, string> Headers to merge into the outgoing request.
	 */
	public static function requestHeaders(): array {
		return [ self::SESSION_HEADER => self::sessionId() ];
	}

	/**
	 * Resolves the session ID sent as the `x-opencode-session` header.
	 *
	 * Resolution order:
	 * 1. The `OPENCODE_SESSION_ID` constant, if defined and non-empty.
	 * 2. The `nominal_ai_po_opencode_session_id` filter (WordPress only).
	 * 3. A random ID that stays stable for the current PHP request.
	 *
	 * The resolved value is cached, so every request made during a single PHP
	 * request shares the same session ID.
	 *
	 * @since 1.1.0
	 * @return string The session ID used for the current request.
	 */
	public static function sessionId(): string {
		if ( null !== self::$sessionId ) {
			return self::$sessionId;
		}

		$sessionId = '';

		// A constant (e.g. define( 'OPENCODE_SESSION_ID', ... ) in wp-config.php) wins.
		if ( defined( 'OPENCODE_SESSION_ID' ) ) {
			$constant = constant( 'OPENCODE_SESSION_ID' );
			if ( is_string( $constant ) && '' !== $constant ) {
				$sessionId = $constant;
			}
		}

		// Otherwise, let integrations supply a stable, application-specific session ID.
		if ( '' === $sessionId && function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'nominal_ai_po_opencode_session_id', '' );
			if ( is_string( $filtered ) && '' !== $filtered ) {
				$sessionId = $filtered;
			}
		}

		// Fall back to a random ID that remains stable for the current PHP request.
		if ( '' === $sessionId ) {
			$sessionId = 'aipo-' . bin2hex( random_bytes( 16 ) );
		}

		self::$sessionId = $sessionId;

		return $sessionId;
	}

	/**
	 * Creates a model instance based on what the API says it can do.
	 *
	 * The SDK calls this for every model the metadata directory discovers.
	 * We peek at the model's capabilities; if it can generate text, we spin up
	 * the appropriate model class. Anything else gets a loud complaint.
	 *
	 * @since 1.0.0
	 * @param ModelMetadata $modelMetadata The model's metadata from the API.
	 * @param ProviderMetadata $providerMetadata Info about this provider (OpenCode).
	 * @return ModelInterface A ready-to-use model instance.
	 * @throws RuntimeException If the model's capabilities aren't supported.
	 */
	protected static function createModel(
		ModelMetadata $modelMetadata, ProviderMetadata $providerMetadata
	): ModelInterface {

		// Ask the metadata what this model claims to be able to do.
		$capabilities = $modelMetadata->getSupportedCapabilities();

		// Walk through all the claimed capabilities and pick the first one we support.
		foreach ( $capabilities as $capability ) {
			// Text generation is our bread and butter — handle that one.
			if ( $capability->isTextGeneration() ) {
				return new Nominal_AIPO_OpenCodeTextGenerationModel( $modelMetadata, $providerMetadata );
			}
		}

		// If we got here, the model wants something we don't offer. That's a problem.
		$capabilityNames = implode( ', ', $capabilities );
		if ( function_exists( 'esc_html' ) ) {
			$capabilityNames = esc_html( $capabilityNames );
		}
		throw new RuntimeException( 'Unsupported model capabilities: ' . $capabilityNames );
	}

	/**
	 * Builds the metadata blob that describes this provider to the SDK.
	 *
	 * This is where we decide how OpenCode looks and reads in the UI.
	 * Some fields (description, logo) only get added if the installed
	 * SDK version supports them — we don't want to crash old setups.
	 *
	 * @since 1.0.0
	 * @return ProviderMetadata The fully assembled metadata object.
	 */
	protected static function createProviderMetadata(): ProviderMetadata {

		// The base set of arguments: slug, display name, type, homepage, auth method.
		$providerMetadataArgs = [
			'opencode', // Internal slug — used in ->usingProvider()
			'OpenCode', // Human-readable name for the UI
			ProviderTypeEnum::cloud(), // This is a cloud/remote provider
			'https://opencode.ai/', // Link to the service's main site
			RequestAuthenticationMethod::apiKey() // All requests are authed with an API key
		];

		// SDK 1.2.0+ lets us attach a short description to the provider.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			// If we're inside WordPress, make the description translatable.
			if ( function_exists( 'esc_html__' ) ) {
				$providerMetadataArgs[] = esc_html__(
					'Text generation with OpenCode.',
					'nominal-ai-provider-for-opencode'
				);
			} else {
				// Standalone mode — no translation functions available.
				$providerMetadataArgs[] = 'Text generation with OpenCode.';
			}
		}

		// SDK 1.3.0+ lets us show a logo next to the provider name.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			// Point to our bundled icon so the UI has something pretty to display.
			$providerMetadataArgs[] = dirname( __DIR__, 2 ) . '/assets/images/opencode.svg';
		}

		// Splat the args into the ProviderMetadata constructor.
		return new ProviderMetadata( ...$providerMetadataArgs );
	}

	/**
	 * Decides whether this provider is "available" (i.e. the API is reachable).
	 *
	 * We use the SDK's built-in strategy: try listing models. If the call
	 * succeeds, the provider lights up as available in the UI.
	 *
	 * @since 1.0.0
	 * @return ProviderAvailabilityInterface An object that knows how to check availability.
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {

		// The simple approach: if we can fetch the model list, the API is alive.
		return new ListModelsApiBasedProviderAvailability( static::modelMetadataDirectory() );
	}

	/**
	 * Spins up the directory that knows how to fetch and parse the /models response.
	 *
	 * @since 1.0.0
	 * @return ModelMetadataDirectoryInterface Our custom metadata directory.
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {

		// Straightforward — just hand back our OpenCode-specific implementation.
		return new Nominal_AIPO_OpenCodeModelMetadataDirectory();
	}
}
