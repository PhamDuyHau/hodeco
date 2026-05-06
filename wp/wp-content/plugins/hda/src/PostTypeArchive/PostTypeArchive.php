<?php
/**
 * Post Type Archive - Assign static pages as archive pages for custom post types.
 *
 * Similar to WordPress's "Posts page" setting, this module allows administrators
 * to choose a page for each eligible custom post type to serve as its archive page.
 *
 * @package HDAddons\PostTypeArchive
 * @author  HD
 */

namespace HDAddons\PostTypeArchive;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class PostTypeArchive implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME   = 'post_type_archive__options';
	public const string KEY_PTA_PAGES = 'pta_pages';

	/**
	 * Cached settings: post_type => page_id mapping.
	 *
	 * @var array<string, int>
	 */
	private array $archivePages;

	// ------------------------------------------------------

	public function __construct() {
		$this->archivePages = self::getArchivePages();

		if ( empty( $this->archivePages ) ) {
			return;
		}

		add_action( 'init', $this->addRewriteRules( ... ) );
		add_action( 'pre_get_posts', $this->handlePageAsArchive( ... ) );

		if ( is_admin() ) {
			add_filter( 'display_post_states', $this->addArchivePageState( ... ), 10, 2 );
		}
	}

	// ------------------------------------------------------
	// OPTIONS
	// ------------------------------------------------------

	/**
	 * Get archive page assignments.
	 *
	 * @return array<string, int> Post type slug => page ID mapping.
	 */
	public static function getArchivePages(): array {
		$options = Helper::getOption( self::OPTION_NAME, [] );

		if ( ! is_array( $options ) || empty( $options[ self::KEY_PTA_PAGES ] ) ) {
			return [];
		}

		// Filter out zero/empty values and ensure integer page IDs
		$pages = [];
		foreach ( $options[ self::KEY_PTA_PAGES ] as $postType => $pageId ) {
			$pageId = absint( $pageId );
			if ( $pageId > 0 ) {
				$pages[ sanitize_key( $postType ) ] = $pageId;
			}
		}

		return $pages;
	}

	// ------------------------------------------------------
	// ELIGIBLE POST TYPES
	// ------------------------------------------------------

	/**
	 * Get post types that can have an archive page assigned.
	 *
	 * Criteria:
	 * - Public post type
	 * - Not built-in (excludes post, page, attachment)
	 * - has_archive is false or empty (post types with their own archive don't need this)
	 * - Not a WooCommerce post type (WooCommerce manages its own archive/shop page)
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	public static function getEligiblePostTypes(): array {
		$postTypes = get_post_types(
			[
				'public'   => true,
				'_builtin' => false,
			],
			'objects'
		);

		// WooCommerce post types are managed by WooCommerce itself
		$wooExclude = [ 'product', 'shop_order', 'shop_coupon', 'shop_order_refund' ];

		$eligible = [];
		foreach ( $postTypes as $slug => $postType ) {
			// Skip WooCommerce types
			if ( in_array( $slug, $wooExclude, true ) ) {
				continue;
			}

			// Only include post types that do NOT already have their own archive
			if ( empty( $postType->has_archive ) ) {
				$eligible[ $slug ] = $postType;
			}
		}

		return $eligible;
	}

	// ------------------------------------------------------
	// REWRITE RULES
	// ------------------------------------------------------

	/**
	 * Add rewrite rules for archive pages to support pagination.
	 *
	 * @return void
	 */
	public function addRewriteRules(): void {
		foreach ( $this->archivePages as $postType => $pageId ) {
			$page = get_post( $pageId );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}

			$pageSlug = $page->post_name;

			// Check for parent pages (hierarchical slug)
			$ancestors = get_post_ancestors( $pageId );
			if ( ! empty( $ancestors ) ) {
				$slugParts = [];
				foreach ( array_reverse( $ancestors ) as $ancestorId ) {
					$ancestor = get_post( $ancestorId );
					if ( $ancestor ) {
						$slugParts[] = $ancestor->post_name;
					}
				}
				$slugParts[] = $pageSlug;
				$pageSlug    = implode( '/', $slugParts );
			}

			add_rewrite_rule(
				'^' . preg_quote( $pageSlug, '/' ) . '/page/([0-9]+)/?',
				'index.php?pagename=' . $pageSlug . '&paged=$matches[1]',
				'top'
			);
		}
	}

	// ------------------------------------------------------
	// QUERY HANDLING
	// ------------------------------------------------------

	/**
	 * Transform the main query on archive pages.
	 *
	 * When a user visits the assigned page, instead of showing the page content,
	 * we display the post type's archive listing.
	 *
	 * @param \WP_Query $query The main query.
	 *
	 * @return void
	 */
	public function handlePageAsArchive( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		foreach ( $this->archivePages as $postType => $pageId ) {
			$obj = get_post_type_object( $postType );
			if ( ! $obj ) {
				continue;
			}

			// Skip if the post type now has its own archive
			if ( ! empty( $obj->has_archive ) ) {
				continue;
			}

			if ( ! is_page( $pageId ) ) {
				continue;
			}

			$query->set( 'post_type', $postType );
			$query->set( 'posts_per_page', Helper::getOption( 'posts_per_page' ) );
			$query->set( 'paged', max( 1, get_query_var( 'paged' ) ) );
			$query->set( 'post_status', 'publish' );
			$query->set( 'pagename', '' );

			$query->is_page              = false;
			$query->is_archive           = true;
			$query->is_post_type_archive = true;
			$query->is_home              = false;
			$query->is_singular          = false;

			break;
		}
	}

	// ------------------------------------------------------
	// ADMIN UI
	// ------------------------------------------------------

	/**
	 * Add "Archive Page" state label next to assigned pages in admin list.
	 *
	 * @param array    $postStates Current post states.
	 * @param \WP_Post $post       Current post object.
	 *
	 * @return array
	 */
	public function addArchivePageState( array $postStates, \WP_Post $post ): array {
		if ( 'page' !== get_post_type( $post ) ) {
			return $postStates;
		}

		foreach ( $this->archivePages as $postType => $pageId ) {
			if ( $pageId !== $post->ID ) {
				continue;
			}

			$obj = get_post_type_object( $postType );
			if ( ! $obj ) {
				continue;
			}

			$label = sprintf(
				/* translators: %s: post type singular name */
				__( 'Archive Page (%s)', HDA_TEXTDOMAIN ),
				$obj->labels->singular_name ?? ucfirst( $postType )
			);

			$postStates[ 'page_archive_' . $postType ] = esc_html( $label );
		}

		return $postStates;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'post_type_archive-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$ptaPages = [];

		if ( ! empty( $data['pta_pages'] ) ) {
			foreach ( (array) $data['pta_pages'] as $postType => $pageId ) {
				$postType = sanitize_key( $postType );
				$pageId   = absint( $pageId );

				// Validate: must be a published page.
				if ( $pageId > 0 && get_post_type( $pageId ) === 'page' && get_post_status( $pageId ) === 'publish' ) {
					$ptaPages[ $postType ] = $pageId;
				}
			}
		}

		$options = [ self::KEY_PTA_PAGES => $ptaPages ];
		self::saveOrRemove( self::OPTION_NAME, $options, true );

		// Flush rewrite rules to update pagination routes.
		flush_rewrite_rules();
	}
}
