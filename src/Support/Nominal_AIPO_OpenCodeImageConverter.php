<?php

declare( strict_types = 1 );

namespace Nominal\AIProviderOpenCode\Support;

// The SDK value object that describes an attached file.
use WordPress\AiClient\Files\DTO\File;

/**
 * Normalises images into a format OpenCode's upstream providers accept.
 *
 * The upstream APIs behind OpenCode only accept webp, png, jpeg and gif images.
 * The PHP AI Client, however, forwards whatever bytes or URL it is handed, so a
 * BMP, TIFF, HEIC or SVG attachment (or a file labelled with the wrong MIME
 * type) is rejected with a 400 error. This helper sniffs the real format of an
 * image and, when it is not one of the accepted types, re-encodes it before the
 * request is sent.
 *
 * Only GD is used, because it is present on virtually every WordPress host. GD
 * cannot decode vector (SVG) or some container formats (TIFF, HEIC), so those
 * are left untouched — and will still be rejected upstream.
 *
 * @since 1.1.0
 */
class Nominal_AIPO_OpenCodeImageConverter {

	/**
	 * Image MIME types accepted by OpenCode's upstream providers.
	 *
	 * @since 1.1.0
	 * @var string[]
	 */
	private const SUPPORTED_MIME_TYPES = [
		'image/webp',
		'image/png',
		'image/jpeg',
		'image/gif',
	];

	/**
	 * The format unsupported images are converted to by default.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	private const DEFAULT_TARGET_MIME_TYPE = 'image/webp';

	/**
	 * Maximum number of bytes to download when fetching a remote image.
	 *
	 * @since 1.1.0
	 * @var int
	 */
	private const MAX_REMOTE_BYTES = 20971520; // 20 MB.

	/**
	 * Builds a replacement data URI for an image that has to be rewritten.
	 *
	 * Returns null when the image can be sent exactly as the client described it,
	 * so callers can treat a null result as "leave the original alone".
	 *
	 * @since 1.1.0
	 * @param File $file The image file to inspect.
	 * @return string|null The data URI to send instead, or null when no change is needed.
	 */
	public static function toSupportedDataUri( File $file ): ?string {
		if ( ! $file->isImage() ) {
			return null;
		}

		$declaredMimeType = $file->getMimeType();

		// Remote images that already use an accepted format can be passed straight through.
		// Anything else has to be downloaded so it can be re-encoded.
		if ( $file->isRemote() ) {
			if ( in_array( self::normaliseMimeType( $declaredMimeType ), self::SUPPORTED_MIME_TYPES, true ) ) {
				return null;
			}

			$url = $file->getUrl();
			if ( ! is_string( $url ) || '' === $url ) {
				return null;
			}

			$bytes = self::download( $url );
			if ( null === $bytes ) {
				return null;
			}

			return self::rewrite( $bytes, $declaredMimeType, true );
		}

		// Inline images: decode the base64 payload the client supplied.
		if ( ! $file->isInline() ) {
			return null;
		}

		$encodedData = $file->getBase64Data();
		if ( ! is_string( $encodedData ) || '' === $encodedData ) {
			return null;
		}

		$bytes = base64_decode( $encodedData, true );
		if ( ! is_string( $bytes ) || '' === $bytes ) {
			return null;
		}

		return self::rewrite( $bytes, $declaredMimeType, false );
	}

	/**
	 * Rewrites raw image bytes into a supported data URI when necessary.
	 *
	 * @since 1.1.0
	 * @param string $bytes The raw image bytes.
	 * @param string $declaredMimeType The MIME type the client declared (raw, un-normalised).
	 * @param bool $force Whether a data URI must be returned (true for downloaded remote images).
	 * @return string|null The data URI, or null when nothing needs to change.
	 */
	private static function rewrite( string $bytes, string $declaredMimeType, bool $force ): ?string {
		$detectedMimeType = self::detectMimeType( $bytes );

		if ( null !== $detectedMimeType && in_array( $detectedMimeType, self::SUPPORTED_MIME_TYPES, true ) ) {
			// The image is already in an accepted format. Only rewrite it when the declared
			// label differs from the real one (e.g. "image/jpg"), or when a remote URL had to
			// be downloaded. The parent class sends the raw label, so compare exactly.
			if ( ! $force && $detectedMimeType === $declaredMimeType ) {
				return null;
			}

			return self::buildDataUri( $detectedMimeType, $bytes );
		}

		// An unsupported format (BMP, TIFF, …) or one we could not sniff: try to convert it.
		$converted = self::convert( $bytes );
		if ( null === $converted ) {
			return null;
		}

		return self::buildDataUri( $converted['mimeType'], $converted['bytes'] );
	}

	/**
	 * Re-encodes raw image bytes into a supported format using GD.
	 *
	 * @since 1.1.0
	 * @param string $bytes The raw image bytes.
	 * @return array{mimeType: string, bytes: string}|null The converted image, or null on failure.
	 */
	private static function convert( string $bytes ): ?array {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return null;
		}

		$targetMimeType = self::resolveTargetMimeType();
		if ( null === $targetMimeType ) {
			return null;
		}

		$image = @imagecreatefromstring( $bytes );
		if ( false === $image ) {
			return null;
		}

		$output = self::encode( $image, $targetMimeType );
		imagedestroy( $image );

		if ( null === $output ) {
			return null;
		}

		return [
			'mimeType' => $targetMimeType,
			'bytes' => $output,
		];
	}

	/**
	 * Encodes a GD image resource into the given format, returning the raw bytes.
	 *
	 * @since 1.1.0
	 * @param mixed $image The GD image resource.
	 * @param string $targetMimeType The MIME type to encode to.
	 * @return string|null The encoded bytes, or null on failure.
	 */
	private static function encode( $image, string $targetMimeType ): ?string {
		$canvas = $image;

		if ( 'image/jpeg' === $targetMimeType ) {
			// JPEG has no alpha channel, so flatten the image onto a white canvas.
			$width = imagesx( $image );
			$height = imagesy( $image );
			$flattened = imagecreatetruecolor( $width, $height );
			if ( false !== $flattened ) {
				$white = imagecolorallocate( $flattened, 255, 255, 255 );
				imagefilledrectangle( $flattened, 0, 0, $width, $height, $white );
				imagealphablending( $flattened, true );
				imagecopy( $flattened, $image, 0, 0, 0, 0, $width, $height );
				$canvas = $flattened;
			}
		} elseif ( 'image/png' === $targetMimeType || 'image/webp' === $targetMimeType ) {
			// PNG and WebP keep transparency, so make sure alpha survives the round-trip.
			if ( function_exists( 'imagepalettetotruecolor' )
				&& function_exists( 'imageistruecolor' )
				&& ! imageistruecolor( $image )
			) {
				@imagepalettetotruecolor( $image );
			}
			imagealphablending( $image, false );
			imagesavealpha( $image, true );
		}

		ob_start();
		switch ( $targetMimeType ) {
			case 'image/webp':
				$success = function_exists( 'imagewebp' ) && imagewebp( $canvas, null, 90 );
				break;
			case 'image/jpeg':
				$success = imagejpeg( $canvas, null, 90 );
				break;
			case 'image/gif':
				$success = imagegif( $canvas );
				break;
			default:
				$success = imagepng( $canvas );
				break;
		}
		$output = ob_get_clean();

		if ( $canvas !== $image ) {
			imagedestroy( $canvas );
		}

		if ( ! $success || ! is_string( $output ) || '' === $output ) {
			return null;
		}

		return $output;
	}

	/**
	 * Resolves the MIME type unsupported images should be converted to.
	 *
	 * Falls back to the first format the local GD build can actually encode.
	 *
	 * @since 1.1.0
	 * @return string|null The target MIME type, or null when nothing can be encoded.
	 */
	private static function resolveTargetMimeType(): ?string {
		$targetMimeType = self::DEFAULT_TARGET_MIME_TYPE;

		// Allow integrations to pick a different (smaller or lossless) target format.
		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'nominal_ai_po_opencode_image_conversion_mime_type', $targetMimeType );
			if ( is_string( $filtered ) ) {
				$filtered = self::normaliseMimeType( $filtered );
				if ( self::canEncode( $filtered ) ) {
					$targetMimeType = $filtered;
				}
			}
		}

		if ( self::canEncode( $targetMimeType ) ) {
			return $targetMimeType;
		}

		foreach ( [ 'image/png', 'image/jpeg', 'image/gif' ] as $fallbackMimeType ) {
			if ( self::canEncode( $fallbackMimeType ) ) {
				return $fallbackMimeType;
			}
		}

		return null;
	}

	/**
	 * Checks whether the given format has an available GD encoder.
	 *
	 * @since 1.1.0
	 * @param string $mimeType The MIME type to check.
	 * @return bool True when the format can be encoded, false otherwise.
	 */
	private static function canEncode( string $mimeType ): bool {
		switch ( $mimeType ) {
			case 'image/webp':
				return function_exists( 'imagewebp' );
			case 'image/jpeg':
				return function_exists( 'imagejpeg' );
			case 'image/gif':
				return function_exists( 'imagegif' );
			case 'image/png':
				return function_exists( 'imagepng' );
		}

		return false;
	}

	/**
	 * Determines the real image format from its leading bytes.
	 *
	 * @since 1.1.0
	 * @param string $bytes The raw image bytes.
	 * @return string|null The detected MIME type, or null when it could not be determined.
	 */
	private static function detectMimeType( string $bytes ): ?string {
		if ( strlen( $bytes ) < 12 ) {
			return null;
		}

		if ( self::startsWith( $bytes, "\x89PNG\r\n\x1a\n" ) ) {
			return 'image/png';
		}
		if ( self::startsWith( $bytes, "\xFF\xD8\xFF" ) ) {
			return 'image/jpeg';
		}
		if ( self::startsWith( $bytes, 'GIF87a' ) || self::startsWith( $bytes, 'GIF89a' ) ) {
			return 'image/gif';
		}
		if ( 'RIFF' === substr( $bytes, 0, 4 ) && 'WEBP' === substr( $bytes, 8, 4 ) ) {
			return 'image/webp';
		}
		if ( self::startsWith( $bytes, 'BM' ) ) {
			return 'image/bmp';
		}
		if ( 'ftyp' === substr( $bytes, 4, 4 ) ) {
			$brand = substr( $bytes, 8, 4 );
			if ( 'avif' === $brand || 'avis' === $brand ) {
				return 'image/avif';
			}
			if ( in_array( $brand, [ 'heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'mif1' ], true ) ) {
				return 'image/heic';
			}
		}
		if ( self::startsWith( $bytes, "II*\x00" ) || self::startsWith( $bytes, "MM\x00*" ) ) {
			return 'image/tiff';
		}

		// SVG is text-based, so look for the root element in the first chunk.
		$head = ltrim( substr( $bytes, 0, 1024 ) );
		if ( '' !== $head && false !== stripos( $head, '<svg' ) ) {
			return 'image/svg+xml';
		}

		return null;
	}

	/**
	 * Downloads a remote image, preferring the WordPress HTTP API when available.
	 *
	 * @since 1.1.0
	 * @param string $url The image URL.
	 * @return string|null The downloaded bytes, or null on failure.
	 */
	private static function download( string $url ): ?string {
		if ( function_exists( 'wp_remote_get' )
			&& function_exists( 'is_wp_error' )
			&& function_exists( 'wp_remote_retrieve_body' )
		) {
			$response = wp_remote_get( $url, [ 'timeout' => 20 ] );
			if ( is_wp_error( $response ) ) {
				return null;
			}

			$body = wp_remote_retrieve_body( $response );

			return ( is_string( $body ) && '' !== $body && strlen( $body ) <= self::MAX_REMOTE_BYTES )
				? $body
				: null;
		}

		// Standalone fallback for when the plugin runs outside of WordPress.
		$context = stream_context_create( [ 'http' => [ 'timeout' => 20 ] ] );
		$body = @file_get_contents( $url, false, $context );

		return ( is_string( $body ) && '' !== $body && strlen( $body ) <= self::MAX_REMOTE_BYTES )
			? $body
			: null;
	}

	/**
	 * Builds a base64 data URI from raw bytes.
	 *
	 * @since 1.1.0
	 * @param string $mimeType The MIME type to declare.
	 * @param string $bytes The raw bytes.
	 * @return string The data URI.
	 */
	private static function buildDataUri( string $mimeType, string $bytes ): string {
		return sprintf( 'data:%s;base64,%s', $mimeType, base64_encode( $bytes ) );
	}

	/**
	 * Normalises a MIME type, collapsing common aliases.
	 *
	 * @since 1.1.0
	 * @param string $mimeType The MIME type to normalise.
	 * @return string The normalised MIME type.
	 */
	private static function normaliseMimeType( string $mimeType ): string {
		$mimeType = strtolower( trim( $mimeType ) );

		// Drop any parameters such as ";charset=utf-8".
		$separatorPosition = strpos( $mimeType, ';' );
		if ( false !== $separatorPosition ) {
			$mimeType = trim( substr( $mimeType, 0, $separatorPosition ) );
		}

		$aliases = [
			'image/jpg' => 'image/jpeg',
			'image/pjpeg' => 'image/jpeg',
			'image/x-png' => 'image/png',
			'image/x-bmp' => 'image/bmp',
			'image/x-ms-bmp' => 'image/bmp',
		];

		return $aliases[ $mimeType ] ?? $mimeType;
	}

	/**
	 * Checks whether a string starts with a given prefix (PHP 7.4 compatible).
	 *
	 * @since 1.1.0
	 * @param string $haystack The string to search.
	 * @param string $needle The prefix to look for.
	 * @return bool True when the prefix matches, false otherwise.
	 */
	private static function startsWith( string $haystack, string $needle ): bool {
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}
