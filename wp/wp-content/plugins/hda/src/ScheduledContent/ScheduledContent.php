<?php
/**
 * Scheduled Content module - Auto publish/expire posts based on ACF date fields.
 *
 * Uses ACF fields `scheduled_start` and `scheduled_end` (datetime picker)
 * to control post visibility on the frontend.
 *
 * @package HDAddons\ScheduledContent
 * @author  HD
 */

namespace HDAddons\ScheduledContent;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class ScheduledContent implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME    = 'scheduled_content__options';
	public const string KEY_ENABLED    = 'enabled';
	public const string KEY_POST_TYPES = 'post_types';

	// ─── Cache ──────────────────────────────────────────

	private const string CACHE_KEY   = 'hda_scheduled_excluded';
	private const string CACHE_GROUP = 'hda';
	private const int    CACHE_TTL   = 60; // seconds

	/**
	 * Cached module options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	/**
	 * Cached excluded post IDs (request-scoped).
	 *
	 * @var int[]|null
	 */
	private static ?array $excludedIds = null;

	// ------------------------------------------------------

	/**
	 * Initialize scheduled content filtering.
	 */
	public function __construct() {
		$options = self::getOptions();

		if ( empty( $options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// Only filter on frontend.
		if (
			( defined( 'REST_REQUEST' ) && \REST_REQUEST )
			|| is_admin()
			|| wp_doing_cron()
			|| wp_doing_ajax()
		) {
			return;
		}

		add_action( 'pre_get_posts', $this->filterScheduledPosts( ... ), 99 );
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

	/**
	 * Filter posts by excluding those outside their scheduled date range.
	 *
	 * Instead of adding meta_query to every WP_Query (which causes expensive
	 * LEFT JOINs on wp_postmeta), this method runs a single optimized query
	 * to find excluded post IDs, caches them, and uses `post__not_in`.
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return void
	 */
	public function filterScheduledPosts( \WP_Query $query ): void {
		if ( $query->is_admin ) {
			return;
		}

		$options   = self::getOptions();
		$postTypes = $options[ self::KEY_POST_TYPES ] ?? [];

		if ( empty( $postTypes ) ) {
			return;
		}

		// Check if this query's post type matches configured types.
		$queryPostType = $query->get( 'post_type' );
		if ( empty( $queryPostType ) ) {
			$queryPostType = 'post';
		}

		$queryTypes = is_array( $queryPostType ) ? $queryPostType : [ $queryPostType ];
		if ( empty( array_intersect( $queryTypes, $postTypes ) ) ) {
			return;
		}

		$excludedIds = self::getExcludedIds( $postTypes );

		if ( empty( $excludedIds ) ) {
			return;
		}

		// Merge with any existing post__not_in.
		$existing = (array) ( $query->get( 'post__not_in' ) ?: [] );
		$query->set( 'post__not_in', array_unique( array_merge( $existing, $excludedIds ) ) );
	}

	// ─── Excluded IDs Query ────────────────────────────

	/**
	 * Get post IDs that should be hidden (outside their schedule window).
	 *
	 * Runs a single optimized INNER JOIN query (not per-query LEFT JOINs)
	 * and caches the result for the request (static) + 60s (object cache).
	 *
	 * @param string[] $postTypes Post types to check.
	 *
	 * @return int[] Post IDs to exclude.
	 */
	private static function getExcludedIds( array $postTypes ): array {
		// Request-level cache.
		if ( self::$excludedIds !== null ) {
			return self::$excludedIds;
		}

		// Object cache (cross-request with persistent cache plugin).
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			self::$excludedIds = $cached;

			return $cached;
		}

		global $wpdb;

		$now          = current_time( 'Y-m-d H:i:s' );
		$placeholders = implode( ',', array_fill( 0, count( $postTypes ), '%s' ) );

		// Find posts that should be HIDDEN:
		// - scheduled_start > NOW → hasn't started yet
		// - scheduled_end   < NOW → already expired
		//
		// Posts WITHOUT these meta fields are NOT affected (INNER JOIN).

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type IN ({$placeholders})
			  AND p.post_status = 'publish'
			  AND (
			    (pm.meta_key = 'scheduled_start' AND pm.meta_value != '' AND pm.meta_value > %s)
			    OR
			    (pm.meta_key = 'scheduled_end' AND pm.meta_value != '' AND pm.meta_value < %s)
			  )",
			...array_merge( $postTypes, [ $now, $now ] )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$ids               = $wpdb->get_col( $sql );
		self::$excludedIds = array_map( 'intval', $ids );

		wp_cache_set( self::CACHE_KEY, self::$excludedIds, self::CACHE_GROUP, self::CACHE_TTL );

		return self::$excludedIds;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'scheduled_content-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$postTypes = [];
		if ( ! empty( $data['sc_post_types'] ) ) {
			$postTypes = array_map( 'sanitize_key', (array) $data['sc_post_types'] );
		}

		$options = [
			self::KEY_ENABLED    => ! empty( $data['sc_enabled'] ),
			self::KEY_POST_TYPES => $postTypes,
		];

		self::saveOrRemove( self::OPTION_NAME, $options, true );

		// Invalidate cache so next request picks up changes.
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}
}
