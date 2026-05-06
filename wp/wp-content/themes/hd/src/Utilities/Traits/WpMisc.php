<?php
/**
 * Miscellaneous utility methods.
 *
 * @author HD
 */

namespace HD\Utilities\Traits;

use HD\Utilities\Libraries\CSS;

defined( 'ABSPATH' ) || exit;

trait WpMisc {

	// -------------------------------------------------------------

	/**
	 * @param string|int $action
	 * @param string $name
	 * @param bool $referer
	 * @param bool $display
	 *
	 * @return string|null
	 */
	public static function csrfToken( string|int $action = - 1, string $name = '_csrf_token', bool $referer = false, bool $display = false ): ?string {
		$name       = esc_attr( $name );
		$token      = wp_create_nonce( $action );
		$nonceField = '<input type="hidden" id="' . wp_generate_password( 10, false ) . '" name="' . $name . '" value="' . esc_attr( $token ) . '" />';

		if ( $referer ) {
			$nonceField .= wp_referer_field( false );
		}

		if ( $display ) {
			echo $nonceField;

			return null;
		}

		return $nonceField;
	}

	// -------------------------------------------------------------

	/**
	 * @param string $tag
	 * @param array $atts
	 * @param string|null $content
	 *
	 * @return mixed
	 */
	public static function doShortcode( string $tag, array $atts = [], ?string $content = null ): mixed {
		global $shortcode_tags;

		$callback = $shortcode_tags[ $tag ] ?? null;

		if ( ! $callback ) {
			return false;
		}

		try {
			return $callback( $atts, $content, $tag );
		} catch ( \Throwable $e ) {
			self::errorLog( '[Shortcode error] ' . $e->getMessage() );

			return false;
		}
	}

	// -------------------------------------------------------------

	/**
	 * @param array|null $arrParsed
	 * @param string $tag
	 * @param string $handle
	 *
	 * @return string
	 */
	public static function lazyScriptTag( ?array $arrParsed, string $tag, string $handle ): string {
		if ( $arrParsed === null ) {
			return $tag;
		}

		foreach ( $arrParsed as $str => $value ) {
			if ( ! str_contains( $handle, $str ) ) {
				continue;
			}

			if ( $value === 'defer' ) {
				return preg_replace( [ '/\s+defer\s+/', '/\s+src=/' ], [ ' ', ' defer src=' ], $tag );
			}

			if ( $value === 'delay' && ! is_admin() ) {
				return preg_replace( [ '/\s+defer\s+/', '/\s+src=/' ], [ ' ', ' defer data-type="lazy" data-src=' ], $tag );
			}
		}

		return $tag;
	}

	// -------------------------------------------------------------

	/**
	 * @param array|null $arrStyles
	 * @param string $html
	 * @param string $handle
	 *
	 * @return string
	 */
	public static function lazyStyleTag( ?array $arrStyles, string $html, string $handle ): string {
		if ( $arrStyles === null ) {
			return $html;
		}

		foreach ( $arrStyles as $style ) {
			if ( ! str_contains( $handle, $style ) ) {
				continue;
			}

			$attrs = [
				'id'    => '',
				'href'  => '',
				'type'  => 'text/css',
				'media' => 'all',
			];

			foreach ( array_keys( $attrs ) as $key ) {
				if ( preg_match( '/' . $key . '=[\'"]([^\'"]+)[\'"]/', $html, $m ) ) {
					$attrs[ $key ] = esc_attr( $m[1] );
				}
			}

			return sprintf(
				"<link rel='preload' id='%s' href='%s' as='style' type='%s' onload=\"this.rel='stylesheet'\">",
				$attrs['id'],
				$attrs['href'],
				$attrs['type']
			);
		}

		return $html;
	}

	// -------------------------------------------------------------

	/**
	 * @param string $postType
	 * @param string|null $option
	 *
	 * @return array|string
	 */
	public static function getAspectRatioOption( string $postType = '', ?string $option = '' ): array|string {
		$postType = $postType ?: 'post';
		$option   = $option ?: 'aspect_ratio__options';

		$options = self::getOption( $option );
		$width   = $options[ 'as-' . $postType . '-width' ] ?? '';
		$height  = $options[ 'as-' . $postType . '-height' ] ?? '';

		return ( $width && $height ) ? [ $width, $height ] : '';
	}

	// -------------------------------------------------------------

	/**
	 * @param string $postType
	 * @param string $defaultValue
	 *
	 * @return string
	 */
	public static function aspectRatioClass( string $postType = 'post', string $defaultValue = 'as-3-2' ): string {
		$ratio  = self::getAspectRatioOption( $postType );
		$ratioX = $ratio[0] ?? '';
		$ratioY = $ratio[1] ?? '';

		return ( $ratioX && $ratioY ) ? "as-{$ratioX}-{$ratioY}" : $defaultValue;
	}

	// -------------------------------------------------------------

	/**
	 * @param string $postType
	 * @param string|null $option
	 * @param string $defaultValue
	 *
	 * @return object
	 */
	public static function getAspectRatio( string $postType = 'post', ?string $option = '', string $defaultValue = 'as-3-2' ): object {
		$ratio  = self::getAspectRatioOption( $postType, $option );
		$ratioX = $ratio[0] ?? '';
		$ratioY = $ratio[1] ?? '';

		$ratioStyle = '';
		if ( ! $ratioX || ! $ratioY ) {
			$ratioClass = $defaultValue;
		} else {
			$ratioClass           = "as-{$ratioX}-{$ratioY}";
			$arSettings           = self::filterSettingOptions( 'aspect_ratio' );
			$arAspectRatioDefault = $arSettings['aspect_ratio_default'] ?? [];

			if ( is_array( $arAspectRatioDefault ) && ! in_array( "{$ratioX}-{$ratioY}", $arAspectRatioDefault, true ) ) {
				$css = new CSS();
				$css->setSelector( '.' . $ratioClass );
				$css->addProperty( 'aspect-ratio', "{$ratioX}/{$ratioY}" );

				$ratioStyle = $css->cssOutput();
			}
		}

		return (object) [
			'class' => $ratioClass,
			'style' => $ratioStyle,
		];
	}

	// -------------------------------------------------------------

	/**
	 * Get any necessary microdata.
	 *
	 * @param string|null $context The element to target.
	 *
	 * @return string Our final attribute to add to the element.
	 */
	public static function microdata( ?string $context ): string {
		$data = match ( $context ) {
			'body'                          => self::getBodyMicrodata(),
			'header'                        => 'itemtype="https://schema.org/WPHeader" itemscope',
			'navigation'                    => 'itemtype="https://schema.org/SiteNavigationElement" itemscope',
			'article'                       => 'itemtype="https://schema.org/CreativeWork" itemscope',
			'product'                       => 'itemtype="https://schema.org/Product" itemscope',
			'post-author', 'comment-author' => 'itemprop="author" itemtype="https://schema.org/Person" itemscope',
			'comment-body'                  => 'itemtype="https://schema.org/Comment" itemscope',
			'sidebar'                       => 'itemtype="https://schema.org/WPSideBar" itemscope',
			'footer'                        => 'itemtype="https://schema.org/WPFooter" itemscope',
			'headline'                      => 'itemprop="headline"',
			'url'                           => 'itemprop="url"',
			'name'                          => 'itemprop="name"',
			'review'                        => 'itemtype="https://schema.org/Review" itemscope',
			'publisher'                     => 'itemtype="https://schema.org/Organization" itemscope',
			'date-published'                => 'itemprop="datePublished"',
			'date-modified'                 => 'itemprop="dateModified"',
			'rating'                        => 'itemtype="https://schema.org/Rating" itemscope',
			'faq'                           => 'itemtype="https://schema.org/FAQPage" itemscope',
			'question'                      => 'itemtype="https://schema.org/Question" itemscope',
			'answer'                        => 'itemtype="https://schema.org/Answer" itemscope',
			default                         => '',
		};

		return apply_filters( "hd_{$context}_microdata_filter", $data );
	}

	// -------------------------------------------------------------

	/**
	 * @return string
	 */
	private static function getBodyMicrodata(): string {
		// Priority-based type detection (most specific first)
		if ( function_exists( 'is_product_category' ) && \is_product_category() ) {
			$type = 'Collection';
		} elseif ( function_exists( 'is_shop' ) && \is_shop() ) {
			$type = 'Collection';
		} elseif ( is_search() ) {
			$type = 'SearchResultsPage';
		} elseif ( is_home() || is_archive() || is_tax() || is_single() ) {
			$type = 'Blog';
		} else {
			$type = 'WebPage';
		}

		return sprintf( 'itemtype="https://schema.org/%s" itemscope', esc_html( $type ) );
	}
}
