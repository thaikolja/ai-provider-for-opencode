<?php

/**
 * Plugin Name: Nominal AI Provider for OpenCode
 * Plugin URI: https://github.com/thaikolja/nominal-ai-provider-for-opencode
 * Description: Nominal AI Provider for OpenCode for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: Kolja Nolte
 * Author URI: https://www.kolja-nolte.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nominal-ai-provider-for-opencode
 *
 * @package Nominal\AIProviderOpenCode
 *
 * This is where the magic starts — every WordPress plugin needs one of these
 * header blocks so WP knows what to call it and where to find it.
 */

declare( strict_types=1 );

namespace Nominal\AIProviderOpenCode;

use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;
use WordPress\AiClient\AiClient;

// Typical WP guard — if someone hits this file directly, bail out.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Pull in our hand-rolled autoloader since Composer's isn't available in prod builds.
require_once __DIR__ . '/src/autoload.php';

/**
 * Kicks off registration of the OpenCode provider with the AI Client.
 *
 * This runs early on `init` so other plugins can latch onto the provider
 * as soon as WordPress is ready.
 *
 * @return void
 * @since 1.0.0
 * @noinspection PhpUnused
 */
function nominal_ai_po_register_provider(): void {

	// If the AI Client SDK isn't even loaded, there's nothing to register with.
	if ( ! class_exists( AiClient::class ) ) {
		return;
	}

	// Grab the default registry — this is where all providers live.
	$registry = AiClient::defaultRegistry();

	// No point in registering twice, so skip if OpenCode is already on the list.
	if ( $registry->hasProvider( Nominal_AIPO_OpenCodeProvider::class ) ) {
		return;
	}

	// Finally, hook our provider into the system so it shows up as an option.
	$registry->registerProvider( Nominal_AIPO_OpenCodeProvider::class );
}

// Priority 5 means we fire before most other init callbacks, so the provider is ready early.
add_action( 'init', __NAMESPACE__ . '\\nominal_ai_po_register_provider', 5 );
