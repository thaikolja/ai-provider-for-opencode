<?php
/**
 * Loads plugin classes when Composer is not installed.
 *
 * The production zip leaves out vendor/. This file maps the plugin
 * namespace to the src/ folder so WordPress can still find the classes.
 *
 * @package Nominal\AIProviderOpenCode
 * @license GPL-2.0-or-later
 * @since   1.0.0
 */

// Keep type checks strict.
declare( strict_types=1 );

// Composer loads these classes itself. This file is only for the plugin zip.
if ( ! defined( 'ABSPATH' ) ) {
	// Opened directly, so do not register anything.
	exit;
}

// Hand class names under our namespace to the loader below.
spl_autoload_register(
	static function ( string $class_name ): void {
		// Only classes from this plugin.
		$prefix = 'Nominal\\AIProviderOpenCode\\';

		// They live next to this file.
		$base_dir = __DIR__ . '/';

		// How many characters to skip before the class path.
		$len = strlen( $prefix );

		// Leave classes from other plugins alone.
		if ( 0 !== strncmp( $class_name, $prefix, $len ) ) {
			// Some other loader can take this class.
			return;
		}

		// The part after the namespace, still with backslashes.
		$relative_class = substr( $class_name, $len );

		// Turn that into a path under src/.
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// Load it when the file is actually there.
		if ( file_exists( $file ) ) {
			// Bring the class in.
			require $file;
		}
	}
);
