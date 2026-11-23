<?php
/**
 * Nominal AI Provider for OpenCode.
 *
 * Lets WordPress generate text with OpenCode.
 *
 * Plugin Name:       Nominal AI Provider for OpenCode
 * Plugin URI:        https://github.com/thaikolja/ai-provider-for-opencode
 * Description:       Use OpenCode to write and revise content in WordPress.
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Version:           1.1.2
 * Author:            Kolja Nolte
 * Author URI:        https://www.kolja-nolte.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nominal-ai-provider-for-opencode
 * Domain Path:       /languages
 *
 * @package Nominal\AIProviderOpenCode
 * @author  Kolja Nolte <kolja.nolte@gmail.com>
 * @license GPL-2.0-or-later
 * @link    https://docs.kolja-nolte.com/opencode-wordpress-ai-provider
 */

// Keep type checks strict.
declare( strict_types=1 );

// Plugin code lives in this namespace.
namespace Nominal\AIProviderOpenCode;

// The OpenCode provider this file registers.
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

// The WordPress AI Client, on sites that have it.
use WordPress\AiClient\AiClient;

// Do not run this file when it is opened directly.
if ( ! defined( 'ABSPATH' ) ) {
	// Stop before any plugin code runs.
	return;
}

// Load the classes. The production zip does not ship Composer.
require_once __DIR__ . '/src/autoload.php';

/**
 * Registers OpenCode with the WordPress AI Client.
 *
 * Translations load first, so the provider sentence can be translated.
 * The API key is not set here. The site supplies OPENCODE_API_KEY itself.
 *
 * @return void
 * @since 1.0.0
 */
function nominal_ai_po_register_provider(): void {
	// Load the translations shipped with the plugin.
	load_plugin_textdomain(
		'nominal-ai-provider-for-opencode',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Older WordPress, or a site without the AI Client, has nothing to register.
	if ( ! class_exists( AiClient::class ) ) {
		// Leave the site as it is.
		return;
	}

	// This is the list of AI providers WordPress already knows.
	$registry = AiClient::defaultRegistry();

	// A second copy of the plugin should not register twice.
	if ( $registry->hasProvider( Nominal_AIPO_OpenCodeProvider::class ) ) {
		// OpenCode is already on the list.
		return;
	}

	// OpenCode is now available to the AI tools.
	$registry->registerProvider( Nominal_AIPO_OpenCodeProvider::class );
}

// Run early on init, before most other plugins ask for a provider.
add_action( 'init', __NAMESPACE__ . '\\nominal_ai_po_register_provider', 5 );
