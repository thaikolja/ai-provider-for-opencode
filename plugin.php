<?php

/**
 * Plugin Name: AI Provider for OpenCode
 * Plugin URI: https://github.com/WordPress/ai-provider-for-opencode
 * Description: AI Provider for OpenCode for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress AI Team
 * Author URI: https://make.wordpress.org/ai/
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-opencode
 *
 * @package WordPress\OpenCodeAiProvider
 */

declare(strict_types=1);

namespace WordPress\OpenCodeAiProvider;

use WordPress\AiClient\AiClient;
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

if (!defined('ABSPATH')) {
    return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Registers the AI Provider for OpenCode with the AI Client.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();

    if ($registry->hasProvider(OpenCodeProvider::class)) {
        return;
    }

    $registry->registerProvider(OpenCodeProvider::class);
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);
