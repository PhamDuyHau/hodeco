<?php
/**
 * Order Items (Posts, Pages, and Custom Post Types) using Drag-and-Drop Sortable JavaScript.
 *
 * @author Colorlib
 * @link   https://github.com/developer developer
 *
 * Modified by HD
 */

namespace HDAddons\CustomSorting;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;
use HDAddons\Asset;

\defined( 'ABSPATH' ) || exit;

final class CustomSorting implements SettingsAware {

	use SettingsHelpers;

	use PostOrderTrait;
	use TaxonomyOrderTrait;
	use AjaxHandlerTrait;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME         = 'custom_sorting__options';
	public const string KEY_ORDER_POST_TYPE = 'order_post_type';
	public const string KEY_ORDER_TAXONOMY  = 'order_taxonomy';

	/**
	 * Post types enabled for custom sorting.
	 */
	private array $orderPostType;

	/**
	 * Taxonomies enabled for custom sorting.
	 */
	private array $orderTaxonomy;

	// ------------------------------------------------------

	/**
	 * Initialize custom sorting functionality.
	 */
	public function __construct() {
		$custom_sorting_options = Helper::getOption( self::OPTION_NAME, [] );

		$this->orderPostType = $custom_sorting_options[ self::KEY_ORDER_POST_TYPE ] ?? [];
		$this->orderTaxonomy = $custom_sorting_options[ self::KEY_ORDER_TAXONOMY ] ?? [];

		if ( ! empty( $this->orderPostType ) || ! empty( $this->orderTaxonomy ) ) {
			self::ensureTermOrderColumn();
			$this->initHooks();
		}
	}

	// ------------------------------------------------------

	/**
	 * Ensure term_order column exists (runs once, cached via theme_mod).
	 */
	public static function ensureTermOrderColumn(): void {
		if ( Helper::getThemeMod( '_custom_sorting_' ) ) {
			return;
		}

		global $wpdb;

		if ( ! $wpdb->query( "DESCRIBE {$wpdb->terms} `term_order`" ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->terms} ADD `term_order` INT( 4 ) NULL DEFAULT '0'" );
		}

		Helper::setThemeMod( '_custom_sorting_', 1 );
	}

	// ------------------------------------------------------

	/**
	 * Initialize WordPress hooks.
	 */
	private function initHooks(): void {
		add_action( 'admin_enqueue_scripts', $this->enqueueAdminScripts( ... ), 33 );
		add_action( 'admin_init', $this->refresh( ... ) );

		// posts
		add_action( 'pre_get_posts', $this->customOrderPreGetPosts( ... ) );

		// dynamic hook get_(adjacent)_post_sort
		add_filter( 'get_previous_post_sort', $this->customOrderPreviousPostSort( ... ) );
		add_filter( 'get_next_post_sort', $this->customOrderNextPostSort( ... ) );

		// dynamic hook get_(adjacent)_post_where
		add_filter( 'get_previous_post_where', $this->customOrderPreviousPostWhere( ... ) );
		add_filter( 'get_next_post_where', $this->customOrderNextPostWhere( ... ) );

		// terms
		add_filter( 'get_terms_orderby', $this->customOrderGetTermsOrderby( ... ), 10, 2 );
		add_filter( 'wp_get_object_terms', $this->customOrderGetObjectTerms( ... ) );
		add_filter( 'get_terms', $this->customOrderGetObjectTerms( ... ) );

		// ajax
		add_action( 'wp_ajax_update-menu-order', $this->updateMenuOrderAjax( ... ) );
		add_action( 'wp_ajax_update-menu-order-tags', $this->updateMenuOrderTagsAjax( ... ) );
	}

	// ------------------------------------------------------

	/**
	 * Enqueue admin scripts for sorting.
	 *
	 * @param string $hook_suffix Current admin page (unused but required by hook).
	 *
	 * @return void
	 */
	public function enqueueAdminScripts( string $hook_suffix ): void {
		if ( ! $this->shouldLoadSortingScript() ) {
			return;
		}

		Asset::localize( 'jquery-core', 'customSortingVars', [
			'nonce'   => wp_create_nonce( self::NONCE_ACTION . get_current_user_id() ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		] );
		Asset::enqueueJS(
			'sorting.js',
			[ 'jquery-core', 'jquery-ui-sortable' ],
			null,
			true,
			[ 'module', 'defer' ]
		);
	}

	// ------------------------------------------------------

	/**
	 * Check if sorting script should be loaded.
	 *
	 * @return bool True if script should load.
	 */
	private function shouldLoadSortingScript(): bool {
		if ( empty( $this->orderPostType ) && empty( $this->orderTaxonomy ) ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		if ( isset( $_GET['orderby'] ) || str_contains( $request_uri, 'action=edit' ) || str_contains( $request_uri, 'wp-admin/post-new.php' ) ) {
			return false;
		}

		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$taxonomy  = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

		if ( ! empty( $this->orderPostType ) ) {
			if ( $post_type && empty( $taxonomy ) && in_array( $post_type, $this->orderPostType, true ) ) {
				return true;
			}
			if ( empty( $post_type ) && str_contains( $request_uri, 'wp-admin/edit.php' ) && in_array( 'post', $this->orderPostType, true ) ) {
				return true;
			}
		}

		if ( ! empty( $this->orderTaxonomy ) && $taxonomy && in_array( $taxonomy, $this->orderTaxonomy, true ) ) {
			return true;
		}

		return false;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'custom-sorting-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$order_reset = ! empty( $data['order_reset'] ) ? sanitize_text_field( $data['order_reset'] ) : '';
		$options     = [];

		if ( empty( $order_reset ) ) {
			foreach ( [ self::KEY_ORDER_POST_TYPE, self::KEY_ORDER_TAXONOMY ] as $field ) {
				if ( ! empty( $data[ $field ] ) ) {
					$options[ $field ] = array_map( 'sanitize_text_field', (array) $data[ $field ] );
				}
			}
		}

		try {
			if ( ! empty( $options ) ) {
				self::ensureTermOrderColumn();
				Helper::updateOption( self::OPTION_NAME, $options );
				( new self() )->updateOptions();
			} else {
				// Reset FIRST while option data is still available,
				// resetAll() handles option removal internally.
				( new self() )->resetAll();
			}
		} catch ( \Exception $e ) {
			Helper::errorLog( 'HDA: Custom sorting update failed - ' . $e->getMessage() );
		}
	}
}
