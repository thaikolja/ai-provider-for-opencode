<?php

/**
 * Minimal WordPress function stubs for PHPStan analysis.
 * Declares functions used by the plugin that are normally provided by WordPress.
 *
 * @package WordPress\OpenCodeAiProvider
 */

/**
 * Hooks a function on to a specific action.
 *
 * @param string $hook_name The name of the action to add the callback to.
 * @param callable $callback The callback to be run when the action is called.
 * @param int $priority Used to specify the order in which the functions
 *                                associated with a particular action are executed.
 * @param int $accepted_args The number of arguments the callback accepts.
 *
 * @return void
 */
function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
}

/**
 * Hooks a function on to a specific filter.
 *
 * @param string $hook_name The name of the filter to add the callback to.
 * @param callable $callback The callback to be run when the filter is applied.
 * @param int $priority Used to specify the order in which the functions
 *                                associated with a particular filter are executed.
 * @param int $accepted_args The number of arguments the callback accepts.
 *
 * @return void
 */
function add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
}

/**
 * Loads the WordPress environment and template.
 *
 * @param string $template_file The path to the template file.
 * @param bool $require_once Whether to require_once or require.
 *
 * @return void
 */
function load_template( string $template_file, bool $require_once = true ): void {
}

/**
 * Retrieves the translation of $text.
 *
 * @param string $text Text to translate.
 * @param string $domain Text domain.
 *
 * @return string Translated text.
 */
function __( string $text, string $domain = 'default' ): string {
	return $text;
}
