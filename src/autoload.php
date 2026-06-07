<?php

/**
 * Hand-rolled PSR-4 autoloader for the AI Provider for OpenCode package.
 *
 * This exists because Composer's autoloader gets stripped during the
 * production build (vendor/ is excluded). We register a simple
 * namespace-to-directory mapping so the plugin works standalone.
 *
 * @since 1.0.0
 *
 * @package WordPress\OpenCodeAiProvider
 */

declare( strict_types=1 );

// Register our own autoload function that maps namespaces to the `src/` directory.
spl_autoload_register( static function ( string $class ): void {

	// Only handle classes under our namespace — let other autoloaders deal with the rest.
	$prefix = 'WordPress\\OpenCodeAiProvider\\';

	// The base dir is wherever this file lives (the `src/` folder).
	$baseDir = __DIR__ . '/';

	$len = strlen( $prefix );

	// Quick check: if the class doesn't start with our prefix, we're not interested.
	if ( strncmp( $class, $prefix, $len ) !== 0 ) {
		return;
	}

	// Chop off the prefix and turn the remaining namespace parts into a file path.
	$relativeClass = substr( $class, $len );
	$file = $baseDir . str_replace( '\\', '/', $relativeClass ) . '.php';

	// If the file actually exists on disk, load it up.
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
