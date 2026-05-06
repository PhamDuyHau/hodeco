<?php
/**
 * Trait for rendering icons from various formats.
 *
 * @package HDAddons\Traits
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait IconRenderer {

	/**
	 * Render icon from various formats.
	 *
	 * Supports:
	 * - Attachment ID (numeric) - loads SVG content for SVG files
	 * - URL (image URL)
	 * - SVG string (inline SVG)
	 * - Icon class (FontAwesome, etc.)
	 * - Data URI
	 *
	 * @param string|int $icon Icon value.
	 * @param string     $name Name for alt text.
	 * @param string     $cssClass Optional CSS class for SVG/icon.
	 * @param int        $size Optional size in pixels for images.
	 *
	 * @return string Rendered HTML.
	 */
	public static function renderIcon( string|int $icon, string $name = '', string $cssClass = '', int $size = 32 ): string {
		if ( empty( $icon ) ) {
			return '';
		}

		// Attachment ID - get image from media library.
		if ( is_numeric( $icon ) ) {
			$attachmentId = absint( $icon );
			$imageUrl     = wp_get_attachment_image_url( $attachmentId, 'thumbnail' );

			if ( $imageUrl ) {

				// For SVG attachments, load file content instead of using URL.
				$mimeType = get_post_mime_type( $attachmentId );
				if ( 'image/svg+xml' === $mimeType ) {
					$svgPath = get_attached_file( $attachmentId );

					if ( $svgPath && is_file( $svgPath ) && filesize( $svgPath ) < 50000 ) {
						$svgContent = self::readFile( $svgPath );
						if ( $svgContent ) {
							return self::addSvgClass( self::ksesSvg( $svgContent ), $cssClass );
						}
					}
				}

				// Regular image or large SVG - use URL.
				return sprintf(
					'<img width="%d" height="%d" src="%s" alt="%s"%s>',
					$size,
					$size,
					esc_url( $imageUrl ),
					esc_attr( $name ? "{$name}-icon" : 'icon' ),
					! empty( $cssClass ) ? ' class="' . esc_attr( $cssClass ) . '"' : ''
				);
			}

			return '';
		}

		// URL or data URI - render as image.
		if ( self::isUrl( $icon ) || str_starts_with( $icon, 'data:' ) ) {
			return sprintf(
				'<img width="%d" height="%d" src="%s" alt="%s"%s>',
				$size,
				$size,
				esc_url( $icon ),
				esc_attr( $name ? "{$name}-icon" : 'icon' ),
				! empty( $cssClass ) ? ' class="' . esc_attr( $cssClass ) . '"' : ''
			);
		}

		// Inline SVG - sanitize and add optional class.
		if ( str_starts_with( $icon, '<svg' ) ) {
			return self::addSvgClass( self::ksesSvg( $icon ), $cssClass );
		}

		// <i> tag (already rendered icon class) - sanitize.
		if ( str_starts_with( $icon, '<i' ) ) {
			return self::ksesSvg( $icon );
		}

		// Icon class (e.g., FontAwesome) - render as <i> element.
		$classes = trim( $icon . ' ' . $cssClass );

		return sprintf( '<i class="%s"></i>', esc_attr( $classes ) );
	}

	// --------------------------------------------------

	/**
	 * Add CSS class to sanitized SVG markup.
	 *
	 * @param string $svg      Sanitized SVG string.
	 * @param string $cssClass CSS class to add.
	 *
	 * @return string SVG with class attribute.
	 */
	private static function addSvgClass( string $svg, string $cssClass ): string {
		if ( ! empty( $cssClass ) && ! str_contains( $svg, 'class=' ) ) {
			return str_replace( '<svg', '<svg class="' . esc_attr( $cssClass ) . '"', $svg );
		}

		return $svg;
	}
}
