<?php
/**
 * Embed Trait
 *
 * Provides static methods for YouTube embeds, safe mailto links,
 * SVG sanitization, and FAQ schema generation.
 *
 * @package HD\Utilities\Traits
 * @author  HD
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Embed {

	/**
	 * @return array
	 */
	public static function ksesSVG(): array {
		return [
			'svg'            => [
				'xmlns'               => true,
				'viewbox'             => true,
				'width'               => true,
				'height'              => true,
				'fill'                => true,
				'stroke'              => true,
				'stroke-width'        => true,
				'stroke-linecap'      => true,
				'stroke-linejoin'     => true,
				'stroke-miterlimit'   => true,
				'stroke-dasharray'    => true,
				'stroke-dashoffset'   => true,
				'fill-rule'           => true,
				'clip-rule'           => true,
				'preserveaspectratio' => true,
				'aria-hidden'         => true,
				'role'                => true,
				'focusable'           => true,
				'id'                  => true,
				'class'               => true,
				'style'               => true,
			],
			'g'              => [
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'clip-path'    => true,
				'transform'    => true,
				'id'           => true,
				'class'        => true,
				'style'        => true,
			],
			'path'           => [
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
				'vector-effect'   => true,
				'transform'       => true,
				'opacity'         => true,
				'id'              => true,
				'class'           => true,
				'style'           => true,
			],
			'circle'         => [
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'ellipse'        => [
				'cx'           => true,
				'cy'           => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'rect'           => [
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'line'           => [
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'polyline'       => [
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'polygon'        => [
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'defs'           => [],
			'symbol'         => [
				'id'                  => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
			],
			'use'            => [
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
				'id'         => true,
				'class'      => true,
			],
			'clippath'       => [ 'id' => true ],
			'mask'           => [
				'id'               => true,
				'x'                => true,
				'y'                => true,
				'width'            => true,
				'height'           => true,
				'maskunits'        => true,
				'maskcontentunits' => true,
			],
			'lineargradient' => [
				'id'                => true,
				'x1'                => true,
				'y1'                => true,
				'x2'                => true,
				'y2'                => true,
				'gradientunits'     => true,
				'gradienttransform' => true,
			],
			'radialgradient' => [
				'id'                => true,
				'cx'                => true,
				'cy'                => true,
				'r'                 => true,
				'fx'                => true,
				'fy'                => true,
				'gradientunits'     => true,
				'gradienttransform' => true,
			],
			'stop'           => [
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
			],
			'title'          => [],
			'desc'           => [],
		];
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 * @param int $resolutionKey
	 *
	 * @return string
	 */
	public static function youtubeImage( string $url, int $resolutionKey = 0 ): string {
		if ( ! $url ) {
			return '';
		}

		$resolutions = [ 'sddefault', 'hqdefault', 'mqdefault', 'default', 'maxresdefault' ];
		$urlImg      = self::pixelImg();

		$queryString = wp_parse_url( $url, PHP_URL_QUERY );
		if ( $queryString ) {
			parse_str( $queryString, $vars );
			if ( isset( $vars['v'] ) ) {
				$resKey = $resolutions[ $resolutionKey ] ?? $resolutions[0];
				$urlImg = 'https://img.youtube.com/vi/' . $vars['v'] . '/' . $resKey . '.jpg';
			}
		}

		return $urlImg;
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 * @param int $autoplay
	 * @param bool $lazyload
	 * @param bool $control
	 *
	 * @return string|null
	 */
	public static function youtubeIframe( string $url, int $autoplay = 0, bool $lazyload = true, bool $control = true ): ?string {
		if ( ! $url ) {
			return null;
		}

		$videoId = null;

		// Parse URL and extract query string
		$queryString = wp_parse_url( $url, PHP_URL_QUERY );
		if ( $queryString ) {
			parse_str( $queryString, $vars );
			$videoId = isset( $vars['v'] ) ? esc_attr( $vars['v'] ) : null;
		}

		// Fallback: extract from youtu.be/VIDEO_ID or embed/VIDEO_ID
		if ( ! $videoId ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( $path ) {
				$segments = explode( '/', trim( $path, '/' ) );
				$videoId  = $segments ? esc_attr( end( $segments ) ) : null;
			}
		}

		if ( ! $videoId ) {
			return null;
		}

		$home            = esc_url( trailingslashit( network_home_url() ) );
		$allowAttributes = 'accelerometer; encrypted-media; gyroscope; picture-in-picture';
		$src             = "https://www.youtube.com/embed/{$videoId}?wmode=transparent&origin={$home}";

		if ( $autoplay ) {
			$allowAttributes .= '; autoplay';
			$src             .= '&autoplay=1';
		}

		if ( ! $control ) {
			$src .= '&modestbranding=1&controls=0&rel=0&version=3&loop=1&enablejsapi=1&iv_load_policy=3&playlist=' . $videoId;
		}

		$src .= '&html5=1';

		return sprintf(
			'<iframe id="ytb_iframe_%1$s" title="YouTube Video Player" width="800" height="450" allow="%2$s"%3$s src="%4$s" style="border:0"></iframe>',
			$videoId,
			$allowAttributes,
			$lazyload ? ' loading="lazy"' : '',
			esc_url( $src )
		);
	}

	// --------------------------------------------------

	/**
	 * @param string $email
	 * @param string $title
	 * @param array|string $attributes
	 *
	 * @return string|null
	 */
	public static function safeMailTo( string $email, string $title = '', array|string $attributes = '' ): ?string {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return null;
		}

		$title        = $title ?: $email;
		$encodedEmail = self::encodeChars( $email );
		$encodedTitle = self::encodeChars( $title );

		// Handle attributes
		$attrString = '';
		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $key => $val ) {
				$attrString .= ' ' . htmlspecialchars( $key, ENT_QUOTES | ENT_HTML5 ) . '="' . htmlspecialchars( $val, ENT_QUOTES | ENT_HTML5 ) . '"';
			}
		} elseif ( is_string( $attributes ) ) {
			$attrString = ' ' . $attributes;
		}

		return '<a href="mailto:' . $encodedEmail . '"' . $attrString . '>' . $encodedTitle . '</a>';
	}

	// --------------------------------------------------

	private static function encodeChars( string $str ): string {
		$encoded = '';
		$len     = mb_strlen( $str, 'UTF-8' );

		for ( $i = 0; $i < $len; $i++ ) {
			$char     = mb_substr( $str, $i, 1, 'UTF-8' );
			$encoded .= '&#' . mb_ord( $char, 'UTF-8' ) . ';';
		}

		return $encoded;
	}

	// --------------------------------------------------

	/**
	 * @param array|null $faqs
	 * @param string $q
	 * @param string $a
	 *
	 * @return string|null
	 */
	public static function faqSchema( ?array $faqs = [], string $q = 'question', string $a = 'answer' ): ?string {
		if ( ! $faqs ) {
			return null;
		}

		$schemaFaq = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				static fn( $faq ) => [
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $faq[ $q ] ),
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $faq[ $a ] ),
					],
				],
				$faqs
			),
		];

		return '<script type="application/ld+json">' . wp_json_encode( $schemaFaq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}
}
