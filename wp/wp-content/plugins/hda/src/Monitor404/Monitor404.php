<?php
/**
 * 404 Monitor - Logs and tracks 404 (Not Found) errors.
 *
 * @package HDAddons\Monitor404
 * @author  HD
 */

namespace HDAddons\Monitor404;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\DB;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Monitor404 implements SettingsAware {

	use SettingsHelpers;

	/**
	 * Database table name (without prefix).
	 */
	public const string TABLE_NAME = 'hda_404_log';

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME           = 'monitor_404__options';
	public const string KEY_ENABLED           = 'm404_enabled';
	public const string KEY_IGNORED_PATTERNS  = 'm404_ignored_patterns';
	public const string KEY_RETENTION_DAYS    = 'm404_retention_days';
	public const string KEY_AUTO_BLOCK        = 'm404_auto_block';       // Enable 404 flood protection
	public const string KEY_BLOCK_THRESHOLD   = 'm404_block_threshold';  // Max 404s before action
	public const string KEY_BLOCK_WINDOW      = 'm404_block_window';     // Time window in minutes

	/**
	 * Default log retention in days.
	 */
	private const int DEFAULT_RETENTION_DAYS = 90;

	/**
	 * Maximum logs to keep.
	 */
	private const int MAX_LOGS = 50000;

	/**
	 * Static assets extensions to skip.
	 */
	private const array SKIP_EXTENSIONS = [
		'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif',
		'woff', 'woff2', 'ttf', 'eot', 'otf',
		'ico', 'map', 'xml', 'txt', 'json',
	];

	/**
	 * Table schema SQL (without CREATE TABLE wrapper).
	 */
	private const string TABLE_SCHEMA = <<<'SQL'
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		url VARCHAR(2048) NOT NULL DEFAULT '',
		referer VARCHAR(2048) NOT NULL DEFAULT '',
		ip_address VARCHAR(45) NOT NULL DEFAULT '',
		user_agent VARCHAR(255) NOT NULL DEFAULT '',
		hit_count INT UNSIGNED NOT NULL DEFAULT 1,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY url (url(191)),
		KEY hit_count (hit_count),
		KEY updated_at (updated_at)
	SQL;

	/**
	 * Cached options (avoids re-reading from DB in same request).
	 *
	 * @var array|null
	 */
	private ?array $cachedOptions = null;

	/**
	 * Cached table existence flag per request.
	 *
	 * @var bool|null
	 */
	private static ?bool $tableExistsCache = null;

	// ------------------------------------------------------

	public function __construct() {
		$options = $this->getCachedOptions();

		if ( empty( $options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// Cron cleanup — register on ALL requests (admin + frontend)
		// so WP-Cron can fire it regardless of context.
		add_action( 'hda_404_log_cleanup', self::cleanup( ... ) );

		if ( ! wp_next_scheduled( 'hda_404_log_cleanup' ) ) {
			wp_schedule_event( time(), 'monthly', 'hda_404_log_cleanup' );
		}

		// Admin: register submenu page
		if ( is_admin() ) {
			new Monitor404Admin();

			return;
		}

		// Frontend: log 404 errors
		add_action( 'template_redirect', $this->log404( ... ), 999 );
	}

	// ------------------------------------------------------
	// OPTIONS
	// ------------------------------------------------------

	/**
	 * Get module options (static, always from DB).
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		return Helper::getOption( self::OPTION_NAME, [] );
	}

	/**
	 * Get cached options for the current request.
	 *
	 * @return array
	 */
	private function getCachedOptions(): array {
		return $this->cachedOptions ??= self::getOptions();
	}

	// ------------------------------------------------------
	// DATABASE METHODS
	// ------------------------------------------------------

	/**
	 * Create the 404 log table.
	 *
	 * @return void
	 */
	public static function createTable(): void {
		DB::createTable( self::TABLE_NAME, self::TABLE_SCHEMA );
		self::$tableExistsCache = true;
	}

	/**
	 * Drop the 404 log table.
	 *
	 * @return void
	 */
	public static function dropTable(): void {
		DB::dropTable( self::TABLE_NAME );
		self::$tableExistsCache = false;
	}

	/**
	 * Check if the table exists (cached per request).
	 *
	 * @return bool
	 */
	public static function tableExists(): bool {
		return self::$tableExistsCache ??= DB::tableExists( self::TABLE_NAME );
	}

	// ------------------------------------------------------
	// LOGGING
	// ------------------------------------------------------

	/**
	 * Log a 404 error on the frontend.
	 *
	 * @return void
	 */
	public function log404(): void {
		if ( ! is_404() ) {
			return;
		}

		$requestUri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		if ( empty( $requestUri ) ) {
			return;
		}

		// Skip static assets
		$path      = wp_parse_url( $requestUri, PHP_URL_PATH ) ?: '';
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( in_array( $extension, self::SKIP_EXTENSIONS, true ) ) {
			return;
		}

		// Skip ignored patterns (re-use cached options)
		$options         = $this->getCachedOptions();
		$ignoredPatterns = ! empty( $options[ self::KEY_IGNORED_PATTERNS ] )
			? array_filter( array_map( 'trim', explode( "\n", $options[ self::KEY_IGNORED_PATTERNS ] ) ) )
			: [];

		foreach ( $ignoredPatterns as $pattern ) {
			if ( str_starts_with( $requestUri, $pattern ) ) {
				return;
			}
		}

		$this->insertOrUpdate( $requestUri );

		// ── Auto-block: track 404 flood per IP ──────────
		$this->maybeAutoBlock( $requestUri );
	}

	/**
	 * Track 404 frequency per IP and fire action if threshold exceeded.
	 *
	 * Uses transients for lightweight frequency tracking.
	 * Fires `hda_404_flood_detected` action for external modules (e.g., TrafficMonitor)
	 * to handle the actual blocking.
	 *
	 * @param string $uri The 404 URI.
	 *
	 * @return void
	 */
	private function maybeAutoBlock( string $uri ): void {
		$options = $this->getCachedOptions();

		if ( empty( $options[ self::KEY_AUTO_BLOCK ] ) ) {
			return;
		}

		$threshold = max( 5, (int) ( $options[ self::KEY_BLOCK_THRESHOLD ] ?? 20 ) );
		$window    = max( 1, (int) ( $options[ self::KEY_BLOCK_WINDOW ] ?? 5 ) );
		$ip        = Helper::ipAddress();

		if ( '' === $ip || '127.0.0.1' === $ip || '::1' === $ip ) {
			return;
		}

		// Transient-based counter: hda_404_flood_{hash}
		$key   = 'hda_404_flood_' . md5( $ip );
		$count = (int) get_transient( $key );
		$count++;

		set_transient( $key, $count, $window * MINUTE_IN_SECONDS );

		if ( $count >= $threshold ) {
			// Fire action for external handling (TrafficMonitor / Firewall).
			do_action( 'hda_404_flood_detected', $ip, $count, $uri );

			// Reset counter after firing to prevent spamming the action.
			delete_transient( $key );

			Helper::errorLog( sprintf(
				'[HDA Monitor404] 404 flood detected: %s (%d hits in %d min) — %s',
				$ip,
				$count,
				$window,
				$uri
			) );
		}
	}

	/**
	 * Insert a new 404 record or increment hit_count if URL already exists.
	 *
	 * @param string $url The 404 URL.
	 *
	 * @return void
	 */
	private function insertOrUpdate( string $url ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		$referer = isset( $_SERVER['HTTP_REFERER'] )
			? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
			: '';

		$ip = Helper::ipAddress();

		$userAgent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: '';

		$now = gmdate( 'Y-m-d H:i:s' );

		// Check if URL already exists — use raw query for performance
		// (avoids SHOW COLUMNS overhead from DB::getOne on the first call).
		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$existing = $db->get_row(
			$db->prepare(
				"SELECT id, hit_count FROM {$table} WHERE url = %s LIMIT 1",
				$url
			),
			ARRAY_A
		);

		if ( $existing ) {
			$db->update(
				$table,
				[
					'hit_count'  => ( (int) $existing['hit_count'] ) + 1,
					'referer'    => $referer,
					'ip_address' => $ip,
					'user_agent' => $userAgent,
					'updated_at' => $now,
				],
				[ 'id' => (int) $existing['id'] ],
				[ '%d', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			$db->insert(
				$table,
				[
					'url'        => $url,
					'referer'    => $referer,
					'ip_address' => $ip,
					'user_agent' => $userAgent,
					'hit_count'  => 1,
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
			);
		}
	}

	// ------------------------------------------------------
	// QUERY METHODS
	// ------------------------------------------------------

	/**
	 * Get logs with pagination and search.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array{items: array, total: int}
	 */
	public static function getLogs( array $args = [] ): array {
		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'updated_at',
			'order'    => 'DESC',
			'search'   => '',
		];

		$args  = wp_parse_args( $args, $defaults );
		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		// Build WHERE clause
		$where  = [];
		$values = [];

		if ( ! empty( $args['search'] ) ) {
			$where[]     = '(url LIKE %s OR referer LIKE %s)';
			$search_term = '%' . $db->esc_like( $args['search'] ) . '%';
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Validate orderby
		$allowed_orderby = [ 'id', 'url', 'hit_count', 'ip_address', 'created_at', 'updated_at' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'updated_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Get total count
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( $values ) {
			$total = (int) $db->get_var( $db->prepare( $count_sql, ...$values ) );
		} else {
			$total = (int) $db->get_var( $count_sql );
		}

		// Get items
		$offset    = ( (int) $args['page'] - 1 ) * (int) $args['per_page'];
		$limit     = (int) $args['per_page'];
		$items_sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$prepared_values = array_merge( $values, [ $limit, $offset ] );
		$items = $db->get_results( $db->prepare( $items_sql, ...$prepared_values ), ARRAY_A );

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	// ------------------------------------------------------
	// CLEANUP METHODS
	// ------------------------------------------------------

	/**
	 * Cleanup old log entries.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function cleanup(): int {
		if ( ! self::tableExists() ) {
			return 0;
		}

		$options       = self::getOptions();
		$retentionDays = ! empty( $options[ self::KEY_RETENTION_DAYS ] ) ? (int) $options[ self::KEY_RETENTION_DAYS ] : self::DEFAULT_RETENTION_DAYS;

		$db     = DB::db();
		$table  = DB::tableNameFull( self::TABLE_NAME );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $retentionDays . ' days' ) );

		// Delete old entries
		$deleted = (int) $db->query(
			$db->prepare( "DELETE FROM {$table} WHERE updated_at < %s", $cutoff )
		);

		// Limit total entries
		$total = (int) $db->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $total > self::MAX_LOGS ) {
			$excess = $total - self::MAX_LOGS;

			$db->query(
				$db->prepare( "DELETE FROM {$table} ORDER BY updated_at ASC LIMIT %d", $excess )
			);
		}

		return $deleted;
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool
	 */
	public static function clearAll(): bool {
		if ( ! self::tableExists() ) {
			return false;
		}

		return DB::truncateTable( self::TABLE_NAME );
	}

	/**
	 * Delete logs by IDs.
	 *
	 * @param array<int> $ids Log IDs to delete.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function deleteByIds( array $ids ): int {
		if ( empty( $ids ) || ! self::tableExists() ) {
			return 0;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ids   = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		return (int) $db->query( $db->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) );
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'monitor_404-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$options = [
			self::KEY_ENABLED          => ! empty( $data['m404_enabled'] ),
			self::KEY_IGNORED_PATTERNS => isset( $data['m404_ignored_patterns'] )
				? sanitize_textarea_field( $data['m404_ignored_patterns'] )
				: '',
			self::KEY_RETENTION_DAYS   => isset( $data['m404_retention_days'] )
				? max( 7, min( 365, absint( $data['m404_retention_days'] ) ) )
				: 90,
			self::KEY_AUTO_BLOCK       => ! empty( $data['m404_auto_block'] ),
			self::KEY_BLOCK_THRESHOLD  => isset( $data['m404_block_threshold'] )
				? max( 5, min( 200, absint( $data['m404_block_threshold'] ) ) )
				: 20,
			self::KEY_BLOCK_WINDOW     => isset( $data['m404_block_window'] )
				? max( 1, min( 60, absint( $data['m404_block_window'] ) ) )
				: 5,
		];

		self::saveOrRemove( self::OPTION_NAME, $options, true );
	}
}
