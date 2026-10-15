<?php

/**
 * Minimal stubs for PHPStan analysis.
 *
 * Declares the WordPress functions used by the plugin, plus the handful of PHP
 * extension functions (GD) and WordPress HTTP helpers that are not guaranteed
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
 * Hooks a function on to a specific filter.
 *
 * @param string   $hook_name     The name of the filter to add the callback to.
 * @param callable $callback      The callback to be run when the filter is applied.
 * @param int      $priority      Used to specify the order in which the functions
 *                                associated with a particular filter are executed.
 * @param int      $accepted_args The number of arguments the callback accepts.
 *
 * @return void
 */
function add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
}

/**
 * Loads the WordPress environment and template.
 *
 * @param string $template_file The path to the template file.
 * @param bool   $require_once  Whether to require_once or require.
 *
 * @return void
 */
function load_template( string $template_file, bool $require_once = true ): void {
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
 * Retrieves the translation of $text and escapes it for safe use in HTML output.
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

/*
 * ----------------------------------------------------------------------------
 * WordPress HTTP API.
 * ----------------------------------------------------------------------------
 */

/**
 * Performs an HTTP request using the WordPress HTTP API.
 *
 * @param string $url  The request URL.
 * @param array  $args Request arguments.
 *
 * @return mixed The response, or a WP_Error on failure.
 */
function wp_remote_get( string $url, array $args = array() ) {
}

/**
 * Checks whether the given value is a WordPress error.
 *
 * @param mixed $thing The value to check.
 *
 * @return bool True if the value is a WP_Error, false otherwise.
 */
function is_wp_error( $thing ): bool {
	return false;
}

/**
 * Retrieves the body of an HTTP response.
 *
 * @param mixed $response The HTTP response.
 *
 * @return mixed The response body.
 */
function wp_remote_retrieve_body( $response ) {
}

/*
 * ----------------------------------------------------------------------------
 * GD image functions.
 *
 * Used by Nominal_AIPO_OpenCodeImageConverter to re-encode images the upstream
 * API cannot accept. Declared loosely (variadic, mixed) purely so PHPStan can
 * resolve the calls when the GD extension is not loaded in the analysis
 * environment.
 * ----------------------------------------------------------------------------
 */

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagecreatefromstring( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagecreatetruecolor( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagecolorallocate( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagefilledrectangle( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagealphablending( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagecopy( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagesx( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagesy( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagesavealpha( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagepalettetotruecolor( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imageistruecolor( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagedestroy( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagepng( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagejpeg( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagegif( ...$args ) {
}

/**
 * GD stub.
 *
 * @param mixed ...$args Arguments.
 * @return mixed
 */
function imagewebp( ...$args ) {
}
