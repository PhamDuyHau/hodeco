<?php
/**
 * String utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Str {
	// --------------------------------------------------

	/**
	 * Strip all HTML tags with optional JS removal.
	 *
	 * @param string|null $text
	 * @param bool $removeJs
	 * @param bool $flatten
	 * @param string|array|null $allowedTags
	 *
	 * @return string
	 */
	public static function stripAllTags( ?string $text, bool $removeJs = true, bool $flatten = true, string|array|null $allowedTags = null ): string {
		if ( ! $text ) {
			return '';
		}

		if ( is_array( $allowedTags ) ) {
			$allowedTags = implode( '', array_map( static fn( $tag ) => "<{$tag}>", $allowedTags ) );
		}

		if ( $removeJs ) {
			$text = preg_replace( '/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $text ) ?? '';
		}

		$text = strip_tags( $text, $allowedTags );

		if ( $flatten ) {
			$text = preg_replace( '/\s+/u', ' ', $text ) ?? '';
		}

		return trim( $text );
	}

	// --------------------------------------------------

	/**
	 * Escape attribute safely.
	 *
	 * @param mixed $text
	 *
	 * @return string
	 */
	public static function escAttr( mixed $text ): string {
		return esc_attr( self::stripAllTags( (string) $text ) );
	}

	// --------------------------------------------------

	/**
	 * Convert slug to capitalized format (PascalCase or Original_Case).
	 *
	 * @param string $slug
	 * @param bool $removeSymbols If true, returns PascalCase; if false, preserves delimiter.
	 *
	 * @return string
	 */
	public static function capitalizedSlug( string $slug, bool $removeSymbols = true ): string {
		$words = preg_split( '/[_\-]/', $slug );
		$words = array_map( 'ucfirst', $words );

		if ( $removeSymbols ) {
			return implode( '', $words );
		}

		return str_contains( $slug, '_' ) ? implode( '_', $words ) : implode( '-', $words );
	}
}
