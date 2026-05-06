<?php
/**
 * Minify Trait
 *
 * Provides static methods for minifying HTML, JS, CSS content
 * and extracting/sanitizing embedded scripts and styles.
 *
 * @package HD\Utilities\Traits
 * @author  HD
 */

namespace HD\Utilities\Traits;

use HD\Utilities\Libraries\Minify_Html;
use MatthiasMullie\Minify;

defined( 'ABSPATH' ) || exit;

trait Minification {

	/**
	 * Extract and filter JavaScript content with size limits.
	 *
	 * Uses single-pass callback: each script tag decides inline whether
	 * to keep or discard itself, avoiding position-shift bugs.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function extractJs( string $content ): string {
		// Limit input size to prevent ReDoS (5MB max).
		if ( strlen( $content ) > 5242880 ) {
			self::errorLog( '[extractJs] Content too large, truncated to 5MB' );
			$content = substr( $content, 0, 5242880 );
		}

		$maliciousPatterns = [
			'/\beval\s*\(/i',
			'/\bdocument\.write\s*\(/i',
			'/;base64,/i',
		];

		// Single-pass: each script decides its own fate in the callback.
		return preg_replace_callback(
			'/<script\b[^>]*>(.*?)<\/script>/is',
			static function ( array $m ) use ( $maliciousPatterns ): string {
				$scriptContent = trim( $m[1] ?? '' );
				$hasSrc        = str_contains( $m[0], 'src=' );

				// External scripts (with src) or empty inline scripts are kept.
				if ( $hasSrc || $scriptContent === '' ) {
					return $m[0];
				}

				// Check inline content for malicious patterns.
				foreach ( $maliciousPatterns as $pattern ) {
					if ( preg_match( $pattern, $scriptContent ) ) {
						return '';
					}
				}

				return $m[0];
			},
			$content
		) ?? $content;
	}

	// --------------------------------------------------

	/**
	 * Extract and sanitize CSS content with size limits.
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public static function extractCss( string $css ): string {
		if ( ! $css ) {
			return '';
		}

		// Limit input size to prevent memory exhaustion (5MB max).
		if ( strlen( $css ) > 5242880 ) {
			self::errorLog( '[extractCss] CSS content too large, truncated to 5MB' );
			$css = substr( $css, 0, 5242880 );
		}

		// Convert encoding to UTF-8 if needed
		if ( mb_detect_encoding( $css, 'UTF-8', true ) !== 'UTF-8' ) {
			$detected = mb_detect_encoding( $css, [ 'UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII' ], true );
			if ( $detected ) {
				$css = mb_convert_encoding( $css, 'UTF-8', $detected );
			}
		}

		// Log if dangerous content is detected
		if ( str_contains( $css, '<script' ) ) {
			self::errorLog( 'Warning: `<script>` tag detected inside CSS' );
		}

		return trim(
			preg_replace(
				[
					'/<script\b[^>]*>.*?(?:<\/script>|$)/is',
					'/<style\b[^>]*>(.*?)<\/style>/is',
					'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
					'/\bexpression\s*\([^)]*\)/i',
					'/\bjavascript:/i',
					'/[^\S\r\n\t]+/',
				],
				[ '', '$1', '', '', '', ' ' ],
				$css
			) ?? ''
		);
	}

	// --------------------------------------------------

	/**
	 * @param string|null $html
	 * @param bool $respectDebug
	 *
	 * @return string|null
	 */
	public static function htmlMinify( ?string $html, bool $respectDebug = true ): ?string {
		if ( ! $html ) {
			return null;
		}
		if ( $respectDebug && self::development() ) {
			return $html;
		}

		return Minify_Html::minify(
			$html,
			[
				'cssMinifier'     => static fn( string $css ) => self::cssMinify( $css, false ),
				'jsMinifier'      => static fn( string $js ) => self::jsMinify( $js, false ),
				'jsCleanComments' => true,
			]
		);
	}

	// --------------------------------------------------

	/**
	 * @param string|null $js
	 * @param bool $respectDebug
	 *
	 * @return string|null
	 */
	public static function jsMinify( ?string $js, bool $respectDebug = true ): ?string {
		if ( ! $js ) {
			return null;
		}
		if ( $respectDebug && self::development() ) {
			return $js;
		}

		return class_exists( Minify\JS::class ) ? ( new Minify\JS() )->add( $js )->minify() : $js;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $css
	 * @param bool $respectDebug
	 *
	 * @return string|null
	 */
	public static function cssMinify( ?string $css, bool $respectDebug = true ): ?string {
		if ( ! $css ) {
			return null;
		}
		if ( $respectDebug && self::development() ) {
			return $css;
		}

		return class_exists( Minify\CSS::class ) ? ( new Minify\CSS() )->add( $css )->minify() : $css;
	}
}
