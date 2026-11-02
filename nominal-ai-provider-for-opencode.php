<?php
/**
 * Nominal AI Provider for OpenCode.
 *
 * Connects OpenCode models to WordPress' native AI SDK.
 *
 * Plugin Name:       Nominal AI Provider for OpenCode
 * Plugin URI:        https://github.com/thaikolja/nominal-ai-provider-for-opencode
 * Description:       Nominal AI Provider for OpenCode for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Version:           1.1.0
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

declare( strict_types=1 );

namespace Nominal\AIProviderOpenCode;

use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;
use WordPress\AiClient\AiClient;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Registers the OpenCode provider with the AI Client.
 *
 * Bundled translations load first so the provider description can be translated.
 * The API key is not set here. The PHP AI Client reads OPENCODE_API_KEY from
 * the environment or from a constant the site defines itself.
 *
 * @since 1.0.0
 *
 * @return void
 */
function nominal_ai_po_register_provider(): void {
	load_plugin_textdomain(
		'nominal-ai-provider-for-opencode',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	if ( ! class_exists( AiClient::class ) ) {
		return;
	}

	$registry = AiClient::defaultRegistry();

	if ( $registry->hasProvider( Nominal_AIPO_OpenCodeProvider::class ) ) {
		return;
	}

	$registry->registerProvider( Nominal_AIPO_OpenCodeProvider::class );
}

add_action( 'init', __NAMESPACE__ . '\\nominal_ai_po_register_provider', 5 );
