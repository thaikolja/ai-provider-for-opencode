<?php
/*
 * Copyright (C) 2026-2026 by Kolja Nolte
 * https://www.kolja-nolte.com
 * kolja.nolte@gmail.com
 *
 * All Rights Reserved.
 *
 * This code is proprietary software. You may not use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of
 * the Software without express written permission from the author.
 *
 * @author Kolja Nolte <kolja.nolte@gmail.com>
 * @date 2024-2026
 */

/**
 * Hand-rolled PSR-4 autoloader for the Nominal AI Provider for OpenCode package.
 *
 * This exists because Composer's autoloader gets stripped during the
 * production build (vendor/ is excluded). We register a simple
 * namespace-to-directory mapping so the plugin works standalone.
 *
 * @since   1.0.0
 *
 * @package Nominal\AIProviderOpenCode
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register our own autoload function that maps namespaces to the `src/` directory.
spl_autoload_register( static function ( string $class_name ): void {
	// Only handle classes under our namespace. Let other autoloaders deal with the rest.
	$prefix   = 'Nominal\\AIProviderOpenCode\\';
	$base_dir = __DIR__ . '/';

	$len = strlen( $prefix );

	// Quick check: if the class doesn't start with our prefix, we're not interested.
	if ( 0 !== strncmp( $class_name, $prefix, $len ) ) {
		return;
	}

	// Chop off the prefix and turn the remaining namespace parts into a file path.
	$relative_class = substr( $class_name, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// If the file actually exists on disk, load it up.
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
