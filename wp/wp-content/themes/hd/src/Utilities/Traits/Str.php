<?php
/**
 * String utility trait.
 *
 * Provides string manipulation and formatting utilities.
 *
 * @package HD\Utilities\Traits
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Str {
	// --------------------------------------------------

	/**
	 * Remove empty <p> tags from content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function removeEmptyP( string $content ): string {
		return preg_replace( '/<p(?:\s+[^>]*)?>\s*(?:&nbsp;|\xC2\xA0|\s)*<\/p>/i', '', $content ) ?? $content;
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function camelCase( string $value ): string {
		$value = trim( $value );
		if ( ! $value ) {
			return '';
		}

		$value = str_replace( [ '-', '_' ], ' ', $value );
		$value = mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );

		return lcfirst( str_replace( ' ', '', $value ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function snakeCase( string $value ): string {
		$value = trim( $value );
		if ( ! $value ) {
			return '';
		}

		$value = str_replace( '-', '_', $value );
		$value = preg_replace( '/(?<!^)([A-Z])/u', '_$1', $value );

		return mb_strtolower( $value, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function dashCase( string $value ): string {
		return str_replace( '_', '-', self::snakeCase( $value ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $haystack
	 * @param string|array $needles
	 *
	 * @return bool
	 */
	public static function startsWith( string $haystack, string|array $needles ): bool {
		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && str_starts_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $haystack
	 * @param string|array $needles
	 *
	 * @return bool
	 */
	public static function endsWith( string $haystack, string|array $needles ): bool {
		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && str_ends_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function removePrefix( string $value, string $prefix ): string {
		if ( ! $prefix ) {
			return $value;
		}

		return self::startsWith( $value, $prefix )
			? mb_substr( $value, mb_strlen( $prefix, 'UTF-8' ), null, 'UTF-8' )
			: $value;
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param string $prefix
	 * @param string|null $trim
	 *
	 * @return string
	 */
	public static function prefix( string $value, string $prefix, ?string $trim = null ): string {
		$value = trim( $value );
		if ( ! $value ) {
			return '';
		}

		return $prefix . self::removePrefix( $value, $trim ?? $prefix );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param string $suffix
	 *
	 * @return string
	 */
	public static function suffix( string $value, string $suffix ): string {
		$value = trim( $value );

		if ( ! $suffix || ! $value ) {
			return $value;
		}

		return self::endsWith( $value, $suffix ) ? $value : $value . $suffix;
	}

	// --------------------------------------------------

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function replaceFirst( string $search, string $replace, string $subject ): string {
		if ( ! $search || ! $subject ) {
			return $subject;
		}

		$pos = mb_strpos( $subject, $search, 0, 'UTF-8' );
		if ( $pos === false ) {
			return $subject;
		}

		return mb_substr( $subject, 0, $pos, 'UTF-8' )
				. $replace
				. mb_substr( $subject, $pos + mb_strlen( $search, 'UTF-8' ), null, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function replaceLast( string $search, string $replace, string $subject ): string {
		if ( ! $search || ! $subject ) {
			return $subject;
		}

		$pos = mb_strrpos( $subject, $search, 0, 'UTF-8' );
		if ( $pos === false ) {
			return $subject;
		}

		return mb_substr( $subject, 0, $pos, 'UTF-8' )
				. $replace
				. mb_substr( $subject, $pos + mb_strlen( $search, 'UTF-8' ), null, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * @param string $str
	 *
	 * @return string
	 */
	public static function sanitizeKeywords( string $str ): string {
		$str = wp_strip_all_tags( $str );
		$str = preg_replace(
			[ '/[\s\v]+/u', '/\s*,\s*/u', '/\s+/u' ],
			[ ' ', ',', ',' ],
			trim( $str )
		);

		$keywords = array_unique(
			array_filter(
				array_map(
					static fn( $word ) => preg_replace(
						'/[^a-z0-9áàảãạăắằẳẵặâấầẩẫậđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵ\s\-]/u',
						'',
						mb_strtolower( trim( $word ), 'UTF-8' )
					),
					explode( ',', $str )
				)
			)
		);

		return implode( ', ', $keywords );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param int $length
	 * @param string $end
	 *
	 * @return string
	 */
	public static function truncate( string $value, int $length, string $end = '' ): string {
		if ( $length <= 0 ) {
			return '';
		}

		$value = trim( $value );
		if ( mb_strlen( $value, 'UTF-8' ) <= $length ) {
			return $value;
		}

		$adjusted  = max( 0, $length - mb_strlen( $end, 'UTF-8' ) );
		$truncated = mb_substr( $value, 0, $adjusted, 'UTF-8' );

		return rtrim( $truncated ) . $end;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $text
	 * @param string|array|null $allowedTags
	 * @param bool $removeJs
	 * @param bool $flatten
	 *
	 * @return string
	 */
	public static function stripAllTags( ?string $text, string|array|null $allowedTags = null, bool $removeJs = true, bool $flatten = true ): string {
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
	 * @param string|null $value
	 * @param bool $stripTags
	 * @param string $replace
	 *
	 * @return string
	 */
	public static function stripSpace( ?string $value, bool $stripTags = true, string $replace = '' ): string {
		if ( $value === null || trim( $value ) === '' ) {
			return '';
		}

		if ( $stripTags ) {
			$value = wp_strip_all_tags( $value );
		}

		return trim( preg_replace( '/[\p{Z}\s]+/u', $replace, $value ) ?? '' );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $value
	 *
	 * @return string
	 */
	public static function escAttr( ?string $value ): string {
		return $value === null ? '' : esc_attr( wp_strip_all_tags( $value ) );
	}
}
