<?php
// @formatter:off

/**
 * Minimal WordPress function stubs for PHPStan analysis.
 *
 * Declares the WordPress functions used by the plugin that are not guaranteed
 * to be present in the analysis environment.
 *
 * @package Nominal\AIProviderOpenCode
 */

/**
 * Hooks a function on to a specific action.
 *
 * @param string   $hook_name     The name of the action to add the callback to.
 * @param callable $callback      The callback to be run when the action is called.
 * @param int      $priority      Used to specify the order in which the functions
 *                                associated with a particular action are executed.
 * @param int      $accepted_args The number of arguments the callback accepts.
 *
 * @return void
 */
function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
}

/**
 * Loads the plugin's translated strings.
 *
 * @param string $domain          Unique identifier for retrieving translated strings.
 * @param string|false $deprecated Optional. Unused. Default false.
 * @param string|false $plugin_rel_path Optional. Relative path to the translations directory.
 *
 * @return bool True when the textdomain was loaded.
 */
function load_plugin_textdomain( string $domain, $deprecated = false, $plugin_rel_path = false ): bool {
	// The real WordPress function loads translation files. The static write keeps
	// this stub from looking side-effect free to PHPStan.
	static $loaded = array();

	$loaded[ $domain ] = true;

	return isset( $loaded[ $domain ] );
}

/**
 * Gets the basename of a plugin.
 *
 * @param string $file The filename of the plugin.
 *
 * @return string The plugin basename.
 */
function plugin_basename( string $file ): string {
	return $file;
}

/**
 * Retrieves the translation of $text.
 *
 * @param string $text   Text to translate.
 * @param string $domain Text domain.
 *
 * @return string Translated text.
 */
function __( string $text, string $domain = 'default' ): string {
	return $text;
}

/**
 * Escapes HTML and retrieves the translation of $text.
 *
 * @param string $text   Text to translate.
 * @param string $domain Text domain.
 *
 * @return string Translated and HTML-escaped text.
 */
function esc_html__( string $text, string $domain = 'default' ): string {
	return $text;
}

/**
 * Escapes a string for safe use in HTML output.
 *
 * @param string $text Text to escape.
 *
 * @return string Escaped text.
 */
function esc_html( string $text ): string {
	return $text;
}

/**
 * Calls the callback functions that have been added to a filter hook.
 *
 * @param string $hook_name The name of the filter hook.
 * @param mixed  $value     The value to filter.
 * @param mixed  ...$args   Additional arguments to pass to the callback functions.
 *
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function apply_filters( string $hook_name, $value, ...$args ) {
	return $value;
}
