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
 * OpenCode provider for the WordPress AI Client.
 *
 * @package Nominal\AIProviderOpenCode
 */

// Keep type checks strict.
declare( strict_types=1 );

// This class lives with the other provider code.
namespace Nominal\AIProviderOpenCode\Provider;

// Turns the OpenCode model list into metadata.
use Nominal\AIProviderOpenCode\Metadata\Nominal_AIPO_OpenCodeModelMetadataDirectory;

// Generates text for one OpenCode model.
use Nominal\AIProviderOpenCode\Models\Nominal_AIPO_OpenCodeTextGenerationModel;

// Used to see which AI Client version is installed.
use WordPress\AiClient\AiClient;

// Thrown when a model can do something this plugin does not handle.
use WordPress\AiClient\Common\Exception\RuntimeException;

// Shared behavior for an API provider.
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;

// Treats a successful model list as "this provider is available".
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;

// The object that lists models.
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;

// The object that says whether the provider can be used.
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

// How OpenCode is described to WordPress.
use WordPress\AiClient\Providers\DTO\ProviderMetadata;

// Cloud provider, rather than a model running on the site.
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;

// Requests use an API key.
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;

// A model the AI Client can call.
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;

// What one discovered model can do.
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Connects OpenCode to the WordPress AI Client.
 *
 * Supplies the OpenCode address, the model list, and the text models.
 *
 * @since 1.0.0
 */
class Nominal_AIPO_OpenCodeProvider extends AbstractApiProvider {

	/**
	 * Name sent with requests so OpenCode can tell this plugin apart.
	 *
	 * Keep it in step with the plugin version. A typed constant would need
	 * PHP 8.3, and this plugin still runs on PHP 7.4.
	 *
	 * @since 1.1.0
	 */
	public const USER_AGENT = 'Nominal-AI-Provider-OpenCode/1.1.2';

	/**
	 * Address every OpenCode request starts from.
	 *
	 * @return string The OpenCode Go address.
	 * @since  1.0.0
	 */
	public static function baseUrl(): string {
		// One place to change if OpenCode moves.
		return 'https://opencode.ai/inference/go/v1';
	}

	/**
	 * Headers added to every OpenCode request.
	 *
	 * OpenCode Go rejects a request that has no session id. One random id
	 * per page load is enough, and the site does not configure it.
	 *
	 * @return array<string, string> Headers to merge into the request.
	 * @since  1.1.0
	 */
	public static function requestHeaders(): array {
		// Reuse the same id until this PHP request ends.
		static $session_id = '';

		// The first call in this request creates the id.
		if ( '' === $session_id ) {
			// A short prefix plus 32 random hex characters.
			$session_id = 'aipo-' . bin2hex( random_bytes( 16 ) );
		}

		// OpenCode reads the id from this header.
		return [
			'x-opencode-session' => $session_id,
		];
	}

	/**
	 * Adds the session id and the plugin name to a request.
	 *
	 * The session id from this provider replaces one a caller already set,
	 * so the whole page load shares a single id. A caller user agent is kept.
	 *
	 * @param array<string, string|list<string>> $headers Headers already set by the caller.
	 *
	 * @return array<string, string|list<string>> Headers to send.
	 * @since 1.1.0
	 */
	public static function applyRequestHeaders( array $headers ): array {
		// Our session id wins over any value already on the request.
		$headers = array_merge( $headers, self::requestHeaders() );

		// Leave a user agent the caller already chose.
		if ( ! isset( $headers['User-Agent'] ) && ! isset( $headers['user-agent'] ) ) {
			// Otherwise introduce this plugin and its version.
			$headers['User-Agent'] = self::USER_AGENT;
		}

		// Headers ready to send.
		return $headers;
	}

	/**
	 * Builds the model WordPress will call.
	 *
	 * Text models get a text generator. Anything else is rejected.
	 *
	 * @param ModelMetadata    $model_metadata    What OpenCode said about the model.
	 * @param ProviderMetadata $provider_metadata What WordPress knows about OpenCode.
	 *
	 * @return ModelInterface   A model ready to use.
	 * @throws RuntimeException When none of the capabilities are supported.
	 * @since  1.0.0
	 */
	public static function createModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	): ModelInterface {
		// What this model says it can do.
		$capabilities = $model_metadata->getSupportedCapabilities();

		// Take the first capability this plugin understands.
		foreach ( $capabilities as $capability ) {
			// Text is the only kind handled here.
			if ( $capability->isTextGeneration() ) {
				// Hand back a text model for this metadata.
				return new Nominal_AIPO_OpenCodeTextGenerationModel( $model_metadata, $provider_metadata );
			}
		}

		// Join the unsupported names for the error message.
		$capability_names = implode( ', ', $capabilities );

		// Escape them when WordPress is loaded. Composer-only use has no esc_html().
		if ( function_exists( 'esc_html' ) ) {
			// Make the names safe to show.
			$capability_names = esc_html( $capability_names );
		}

		// Tell the caller this model type is not supported.
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new RuntimeException( 'Unsupported model capabilities: ' . $capability_names );
	}

	/**
	 * Describes OpenCode to the AI Client.
	 *
	 * The description and logo are added only when this version of the
	 * AI Client has those fields.
	 *
	 * @return ProviderMetadata How OpenCode should appear.
	 * @since  1.0.0
	 */
	public static function createProviderMetadata(): ProviderMetadata {
		// Slug, name, type, homepage, and how requests are authenticated.
		$provider_metadata_args = [
			'opencode',
			'OpenCode',
			ProviderTypeEnum::cloud(),
			'https://opencode.ai/',
			RequestAuthenticationMethod::apiKey(),
		];

		// AI Client 1.2.0 and newer can show a short description.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			// Translate it when WordPress is loaded.
			if ( function_exists( 'esc_html__' ) ) {
				// Sentence shown next to OpenCode in the admin.
				$provider_metadata_args[] = esc_html__(
					'Text generation with OpenCode.',
					'nominal-ai-provider-for-opencode'
				);
			} else {
				// Same sentence, without translation.
				$provider_metadata_args[] = 'Text generation with OpenCode.';
			}
		}

		// AI Client 1.3.0 and newer can show a logo.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			// The icon shipped in this plugin.
			$provider_metadata_args[] = dirname( __DIR__, 2 ) . '/assets/images/opencode.svg';
		}

		// Build the description WordPress will store.
		return new ProviderMetadata( ...$provider_metadata_args );
	}

	/**
	 * Says whether OpenCode can be used right now.
	 *
	 * The AI Client decides that by trying to list models.
	 *
	 * @return ProviderAvailabilityInterface Checker used by the AI Client.
	 * @since  1.0.0
	 */
	public static function createProviderAvailability(): ProviderAvailabilityInterface {
		// Available when the model list can be fetched.
		return new ListModelsApiBasedProviderAvailability( static::modelMetadataDirectory() );
	}

	/**
	 * Returns the object that reads OpenCode's model list.
	 *
	 * @return ModelMetadataDirectoryInterface The OpenCode model directory.
	 * @since  1.0.0
	 */
	public static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		// Our own reader for the OpenCode model list.
		return new Nominal_AIPO_OpenCodeModelMetadataDirectory();
	}
}
