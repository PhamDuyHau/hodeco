<?php
/**
 * SEO module - Outputs meta tags, Open Graph, Twitter Card, and robots directives.
 *
 * Replaces the theme's Seo class by providing global defaults from plugin settings.
 * Per-post overrides via ACF fields (seo_title, seo_description, seo_image, seo_noindex)
 * are still supported when ACF is available.
 *
 * Auto-disables when a third-party SEO plugin is active.
 *
 * @package HDAddons\Seo
 * @author  HD
 */

namespace HDAddons\Seo;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Seo implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME          = 'seo__options';
	public const string KEY_DEFAULT_OG_IMAGE = 'default_og_image';
	public const string KEY_FB_APP_ID        = 'fb_app_id';
	public const string KEY_TWITTER_SITE     = 'twitter_site';
	public const string KEY_TITLE_SEPARATOR  = 'title_separator';
	public const string KEY_VERIFICATION_TAGS = 'verification_tags';

	/**
	 * Cached SEO data for the current request.
	 *
	 * @var array|null
	 */
	private static ?array $seoData = null;

	/**
	 * Cached module options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ------------------------------------------------------

	/**
	 * Initialize SEO hooks.
	 */
	public function __construct() {
		// Bail if a third-party SEO plugin is active.
		if ( self::isSeoPluginActive() ) {
			return;
		}

		add_filter( 'pre_get_document_title', self::filterTitle( ... ), 999 );
		add_filter( 'document_title_separator', self::filterTitleSeparator( ... ), 999 );
		add_filter( 'wp_robots', self::filterRobots( ... ), 999 );
		add_action( 'wp_head', self::outputMetaTags( ... ), 2 );

		// Remove WP core canonical — we output our own to avoid duplicates.
		remove_action( 'wp_head', 'rel_canonical' );
	}

	// ------------------------------------------------------

	/**
	 * Check if a known SEO plugin is active.
	 *
	 * @return bool
	 */
	private static function isSeoPluginActive(): bool {
		return \defined( 'WPSEO_VERSION' )           // Yoast SEO
			|| \defined( 'RANK_MATH_VERSION' )        // Rank Math
			|| \defined( 'AIOSEO_VERSION' )            // All in One SEO
			|| \defined( 'SEOPRESS_VERSION' )          // SEOPress
			|| \defined( 'THE_SEO_FRAMEWORK_VERSION' ); // The SEO Framework
	}

	// ------------------------------------------------------

	/**
	 * Get cached module options.
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		if ( null === self::$options ) {
			self::$options = Helper::getOption( self::OPTION_NAME, [] );
		}

		return self::$options;
	}

	// ------------------------------------------------------
	// SEO DATA
	// ------------------------------------------------------

	/**
	 * Build and cache SEO data for the current request.
	 *
	 * @return array
	 */
	public static function getSeoData(): array {
		if ( null !== self::$seoData ) {
			return self::$seoData;
		}

		$title       = '';
		$customTitle = null;
		$description = '';
		$image       = '';
		$imageWidth  = 0;
		$imageHeight = 0;
		$imageAlt    = '';
		$imageType   = '';
		$type        = 'website';
		$published   = '';
		$modified    = '';

		$postId = self::getCurrentPostId();

		if ( $postId ) {
			$customTitle = self::getAcfField( 'seo_title', $postId );
			$title       = $customTitle ?: get_the_title( $postId );
			$description = self::getAcfField( 'seo_description', $postId ) ?: self::getExcerpt( $postId );

			// Image: ACF field → featured image → default.
			$seoImage = self::getAcfField( 'seo_image', $postId );
			$imageId  = $seoImage
				? ( is_array( $seoImage ) ? ( $seoImage['id'] ?? $seoImage['ID'] ?? 0 ) : (int) $seoImage )
				: (int) get_post_thumbnail_id( $postId );

			if ( $imageId ) {
				$imageData   = self::getOptimizedImage( $imageId );
				$image       = $imageData['url'];
				$imageWidth  = $imageData['width'];
				$imageHeight = $imageData['height'];
				$imageAlt    = $imageData['alt'];
				$imageType   = $imageData['type'];
			}

			// Dates.
			$post = get_post( $postId );
			if ( $post ) {
				$published = get_the_date( 'c', $post );
				$modified  = get_the_modified_date( 'c', $post );
				$type      = is_singular( 'post' ) ? 'article' : 'website';
			}
		}

		// Fallbacks.
		$title       = $title ?: get_bloginfo( 'name' );
		$description = $description ?: get_bloginfo( 'description' );

		if ( ! $image ) {
			$options  = self::getOptions();
			$imageUrl = $options[ self::KEY_DEFAULT_OG_IMAGE ] ?? '';

			// Support attachment ID or URL.
			if ( is_numeric( $imageUrl ) && (int) $imageUrl > 0 ) {
				$src = wp_get_attachment_image_url( (int) $imageUrl, 'large' );
				if ( $src ) {
					$image = $src;
				}
			} elseif ( ! empty( $imageUrl ) ) {
				$image = $imageUrl;
			}
		}

		self::$seoData = [
			'title'            => wp_strip_all_tags( $title ),
			'description'      => wp_strip_all_tags( $description ),
			'has_custom_title' => ! empty( $customTitle ),
			'image'            => $image,
			'image_width'      => $imageWidth,
			'image_height'     => $imageHeight,
			'image_alt'        => $imageAlt ?: wp_strip_all_tags( $title ),
			'image_type'       => $imageType,
			'type'             => $type,
			'published'        => $published,
			'modified'         => $modified,
			'url'              => Helper::getCurrentUrl( true ),
		];

		/**
		 * Filter SEO data before output.
		 *
		 * @param array $seoData SEO data array.
		 * @param int   $postId  Current post ID.
		 */
		self::$seoData = apply_filters( 'hda_seo_data', self::$seoData, $postId );

		return self::$seoData;
	}

	// ------------------------------------------------------
	// FILTERS
	// ------------------------------------------------------

	/**
	 * Filter document title.
	 *
	 * Only overrides when an explicit seo_title ACF field is set.
	 * Otherwise, returns empty so WP generates the default "Page Title [sep] Site Name".
	 *
	 * @param string $title Default title (empty from WP core).
	 *
	 * @return string
	 */
	public static function filterTitle( string $title ): string {
		$data = self::getSeoData();

		if ( ! empty( $data['has_custom_title'] ) ) {
			return $data['title'];
		}

		return $title;
	}

	// ------------------------------------------------------

	/**
	 * Filter title separator character.
	 *
	 * @param string $separator Default separator.
	 *
	 * @return string
	 */
	public static function filterTitleSeparator( string $separator ): string {
		$options  = self::getOptions();
		$custom   = $options[ self::KEY_TITLE_SEPARATOR ] ?? '';

		return $custom ?: $separator;
	}

	// ------------------------------------------------------

	/**
	 * Enhanced robots meta directives.
	 *
	 * @param array $robots Default robots.
	 *
	 * @return array
	 */
	public static function filterRobots( array $robots ): array {
		$postId = self::getCurrentPostId();

		if ( $postId && self::getAcfField( 'seo_noindex', $postId ) ) {
			// Remove conflicting directives before adding noindex.
			unset( $robots['index'], $robots['follow'] );

			$robots['noindex']  = true;
			$robots['nofollow'] = true;

			return $robots;
		}

		// Remove conflicting directives before adding index.
		unset( $robots['noindex'], $robots['nofollow'] );

		// Values only — WP core renders as "{directive}:{value}".
		$robots['index']             = true;
		$robots['follow']            = true;
		$robots['max-snippet']       = '-1';
		$robots['max-video-preview'] = '-1';
		$robots['max-image-preview'] = 'large';

		return $robots;
	}

	// ------------------------------------------------------
	// OUTPUT
	// ------------------------------------------------------

	/**
	 * Output all SEO meta tags.
	 *
	 * @return void
	 */
	public static function outputMetaTags(): void {
		$data = self::getSeoData();

		// Meta description.
		if ( $data['description'] ) {
			printf( '<meta name="description" content="%s"/>%s', esc_attr( $data['description'] ), "\n" );
		}

		// Canonical URL.
		echo '<link rel="canonical" href="' . esc_url( $data['url'] ) . '" />' . "\n";

		// Open Graph.
		self::outputOpenGraph( $data );

		// Twitter Card.
		self::outputTwitterCard( $data );

		// Verification meta tags.
		self::outputVerificationTags();
	}

	// ------------------------------------------------------

	/**
	 * Output Open Graph tags.
	 *
	 * @param array $data SEO data.
	 *
	 * @return void
	 */
	private static function outputOpenGraph( array $data ): void {
		$options  = self::getOptions();
		$fbAppId  = $options[ self::KEY_FB_APP_ID ] ?? '';
		$siteName = get_bloginfo( 'name' );
		$locale   = get_locale();

		$tags = [
			'og:locale'       => $locale,
			'og:type'         => $data['type'],
			'og:title'        => $data['title'],
			'og:description'  => $data['description'],
			'og:url'          => $data['url'],
			'og:site_name'    => $siteName,
		];

		if ( $fbAppId ) {
			$tags['fb:app_id'] = $fbAppId;
		}

		if ( $data['modified'] ) {
			$tags['og:updated_time'] = $data['modified'];
		}

		foreach ( $tags as $property => $content ) {
			if ( $content ) {
				printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $property ), esc_attr( $content ) );
			}
		}

		// OG Image.
		if ( $data['image'] ) {
			$imageTags = [
				'og:image'            => $data['image'],
				'og:image:secure_url' => str_replace( 'http://', 'https://', $data['image'] ),
			];

			if ( $data['image_width'] ) {
				$imageTags['og:image:width'] = $data['image_width'];
			}
			if ( $data['image_height'] ) {
				$imageTags['og:image:height'] = $data['image_height'];
			}

			$imageTags['og:image:alt'] = $data['image_alt'];

			if ( $data['image_type'] ) {
				$imageTags['og:image:type'] = $data['image_type'];
			}

			foreach ( $imageTags as $property => $content ) {
				if ( $content ) {
					printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $property ), esc_attr( $content ) );
				}
			}
		}

		// Article-specific tags.
		if ( 'article' === $data['type'] ) {
			if ( $data['published'] ) {
				printf( '<meta property="article:published_time" content="%s" />' . "\n", esc_attr( $data['published'] ) );
			}
			if ( $data['modified'] ) {
				printf( '<meta property="article:modified_time" content="%s" />' . "\n", esc_attr( $data['modified'] ) );
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * Output Twitter Card tags.
	 *
	 * @param array $data SEO data.
	 *
	 * @return void
	 */
	private static function outputTwitterCard( array $data ): void {
		$options     = self::getOptions();
		$twitterSite = $options[ self::KEY_TWITTER_SITE ] ?? '';

		$tags = [
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => $data['title'],
			'twitter:description' => $data['description'],
		];

		if ( $twitterSite ) {
			$tags['twitter:site'] = '@' . ltrim( $twitterSite, '@' );
		}

		if ( $data['image'] ) {
			$tags['twitter:image'] = $data['image'];
		}

		foreach ( $tags as $name => $content ) {
			if ( $content ) {
				printf( '<meta name="%s" content="%s" />' . "\n", esc_attr( $name ), esc_attr( $content ) );
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * Output site verification meta tags.
	 *
	 * Only allows safe <meta> tags with name/content attributes.
	 *
	 * @return void
	 */
	private static function outputVerificationTags(): void {
		$options = self::getOptions();
		$raw     = $options[ self::KEY_VERIFICATION_TAGS ] ?? '';

		if ( empty( $raw ) ) {
			return;
		}

		$allowedHtml = [
			'meta' => [
				'name'    => true,
				'content' => true,
			],
		];

		$lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );

		foreach ( $lines as $line ) {
			$sanitized = wp_kses( $line, $allowedHtml );
			if ( $sanitized ) {
				echo $sanitized . "\n";
			}
		}
	}

	// ------------------------------------------------------
	// HELPERS
	// ------------------------------------------------------

	/**
	 * Safely get ACF field value.
	 *
	 * @param string $name    Field name.
	 * @param int    $postId  Post ID.
	 *
	 * @return mixed
	 */
	private static function getAcfField( string $name, int $postId ): mixed {
		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}

		try {
			return get_field( $name, $postId );
		} catch ( \Throwable ) {
			return null;
		}
	}

	// ------------------------------------------------------

	/**
	 * Get current post ID regardless of page type.
	 *
	 * @return int
	 */
	private static function getCurrentPostId(): int {
		if ( is_singular() ) {
			return get_queried_object_id();
		}

		if ( is_home() && ! is_front_page() ) {
			return (int) get_option( 'page_for_posts' );
		}

		if ( is_front_page() ) {
			return (int) get_option( 'page_on_front' );
		}

		return 0;
	}

	// ------------------------------------------------------

	/**
	 * Get post excerpt or trimmed content.
	 *
	 * @param int $postId Post ID.
	 *
	 * @return string
	 */
	private static function getExcerpt( int $postId ): string {
		$post = get_post( $postId );
		if ( ! $post ) {
			return '';
		}

		if ( $post->post_excerpt ) {
			return wp_trim_words( $post->post_excerpt, 30, '' );
		}

		return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '' );
	}

	// ------------------------------------------------------

	/**
	 * Get optimized image for social sharing (1200×630).
	 *
	 * @param int $attachmentId Attachment ID.
	 *
	 * @return array{url: string, width: int, height: int, alt: string, type: string}
	 */
	private static function getOptimizedImage( int $attachmentId ): array {
		$default = [ 'url' => '', 'width' => 0, 'height' => 0, 'alt' => '', 'type' => '' ];

		if ( ! $attachmentId ) {
			return $default;
		}

		foreach ( [ 'og-image', 'large', 'full' ] as $size ) {
			$src = wp_get_attachment_image_src( $attachmentId, $size );
			if ( $src && ! empty( $src[0] ) ) {
				return [
					'url'    => $src[0],
					'width'  => (int) $src[1],
					'height' => (int) $src[2],
					'alt'    => get_post_meta( $attachmentId, '_wp_attachment_image_alt', true ) ?: '',
					'type'   => get_post_mime_type( $attachmentId ) ?: '',
				];
			}
		}

		return $default;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'seo-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$options = [
			self::KEY_DEFAULT_OG_IMAGE => isset( $data['seo_default_og_image'] )
				? sanitize_text_field( $data['seo_default_og_image'] )
				: '',
			self::KEY_FB_APP_ID        => isset( $data['seo_fb_app_id'] )
				? sanitize_text_field( $data['seo_fb_app_id'] )
				: '',
			self::KEY_TWITTER_SITE     => isset( $data['seo_twitter_site'] )
				? sanitize_text_field( ltrim( $data['seo_twitter_site'], '@' ) )
				: '',
			self::KEY_TITLE_SEPARATOR  => isset( $data['seo_title_separator'] )
				? sanitize_text_field( $data['seo_title_separator'] )
				: '-',
			self::KEY_VERIFICATION_TAGS => isset( $data['seo_verification_tags'] )
				? wp_kses( $data['seo_verification_tags'], [ 'meta' => [ 'name' => true, 'content' => true ] ] )
				: '',
		];

		self::saveOrRemove( self::OPTION_NAME, $options, true );
	}
}
