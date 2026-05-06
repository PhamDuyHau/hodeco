<?php
/**
 * Activity Log - Tracks login/logout events.
 *
 * @package HDAddons\LoginSecurity\ActivityLog
 * @author  HD
 */

namespace HDAddons\LoginSecurity\ActivityLog;

use HDAddons\DB;
use HDAddons\Helper;
use HDAddons\LoginSecurity\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

final class ActivityLog {

	/**
	 * Database table name (without prefix).
	 */
	public const string TABLE_NAME = 'hda_activity_log';

	/**
	 * Log retention in days.
	 */
	private const int RETENTION_DAYS = 30;

	/**
	 * Maximum logs to keep.
	 */
	private const int MAX_LOGS = 10000;

	/**
	 * Whether activity logging is enabled.
	 */
	private bool $enabled = false;

	// ------------------------------------------------------

	public function __construct() {
		$options       = LoginSecurity::getOptions();
		$this->enabled = ! empty( $options[ LoginSecurity::KEY_ACTIVITY_LOG_ENABLED ] );

		if ( ! $this->enabled ) {
			return;
		}

		// Login events
		add_action( 'wp_login', $this->logLogin( ... ), 10, 2 );
		add_action( 'wp_login_failed', $this->logFailedLogin( ... ), 10, 2 );
		add_action( 'wp_logout', $this->logLogout( ... ), 5, 1 );

		// Scheduled cleanup
		add_action( 'hda_activity_log_cleanup', self::cleanup( ... ) );

		if ( ! wp_next_scheduled( 'hda_activity_log_cleanup' ) ) {
			wp_schedule_event( time(), 'monthly', 'hda_activity_log_cleanup' );
		}
	}

	// ------------------------------------------------------
	// DATABASE METHODS
	// ------------------------------------------------------

	/**
	 * Create the activity log table.
	 *
	 * @return void
	 */
	public static function createTable(): void {
		DB::createTable(
			self::TABLE_NAME,
			"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED DEFAULT 0,
			username VARCHAR(60) NOT NULL DEFAULT '',
			action VARCHAR(20) NOT NULL DEFAULT 'login',
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at),
			KEY ip_address (ip_address)"
		);
	}

	/**
	 * Drop the activity log table.
	 *
	 * @return void
	 */
	public static function dropTable(): void {
		DB::dropTable( self::TABLE_NAME );
	}

	/**
	 * Check if the table exists.
	 *
	 * @return bool
	 */
	public static function tableExists(): bool {
		static $exists = null;

		if ( $exists !== null ) {
			return $exists;
		}

		$exists = DB::tableExists( self::TABLE_NAME );

		return $exists;
	}

	// ------------------------------------------------------
	// LOGGING METHODS
	// ------------------------------------------------------

	/**
	 * Log successful login.
	 *
	 * @param string   $username Username.
	 * @param \WP_User $user     User object.
	 *
	 * @return void
	 */
	public function logLogin( string $username, \WP_User $user ): void {
		$this->insertLog( $user->ID, $username, 'login' );
	}

	/**
	 * Log failed login attempt.
	 *
	 * @param string         $username Username.
	 * @param \WP_Error|null $error    Error object.
	 *
	 * @return void
	 */
	public function logFailedLogin( string $username, $error = null ): void {
		// Try to get user ID if username exists
		$user    = get_user_by( 'login', $username );
		$user_id = $user ? $user->ID : 0;

		$this->insertLog( $user_id, $username, 'failed' );
	}

	/**
	 * Log user logout.
	 *
	 * WordPress calls wp_set_current_user(0) BEFORE firing wp_logout,
	 * so wp_get_current_user() returns ID 0 at this point.
	 * We must use the $user_id parameter passed by the hook.
	 *
	 * @param int $user_id User ID passed by wp_logout action.
	 *
	 * @return void
	 */
	public function logLogout( int $user_id ): void {
		if ( $user_id > 0 ) {
			$user = get_user_by( 'id', $user_id );
			$this->insertLog( $user_id, $user ? $user->user_login : '', 'logout' );
		}
	}

	/**
	 * Log a custom security event from any HDA module.
	 *
	 * Use for events that don't have native WP hooks
	 * (e.g. TOTP setup/reset, OTP failures).
	 *
	 * @param int    $userId   WordPress user ID.
	 * @param string $username User login name.
	 * @param string $action   Action key (e.g. 'totp_setup', 'totp_reset', 'otp_failed').
	 *
	 * @return void
	 */
	public static function logEvent( int $userId, string $username, string $action ): void {
		$options = LoginSecurity::getOptions();
		if ( empty( $options[ LoginSecurity::KEY_ACTIVITY_LOG_ENABLED ] ) ) {
			return;
		}

		if ( ! self::tableExists() ) {
			return;
		}

		DB::insertOneRow(
			self::TABLE_NAME,
			[
				'user_id'    => $userId,
				'username'   => sanitize_user( $username ),
				'action'     => sanitize_key( $action ),
				'ip_address' => Helper::ipAddress(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
					? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
					: '',
				'created_at' => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Insert a log entry.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $username Username.
	 * @param string $action   Action type (login, logout, failed).
	 *
	 * @return void
	 */
	private function insertLog( int $user_id, string $username, string $action ): void {
		self::logEvent( $user_id, $username, $action );
	}

	// ------------------------------------------------------
	// QUERY METHODS
	// ------------------------------------------------------

	/**
	 * Get grouped logs with pagination.
	 *
	 * Groups entries by (username, action, ip_address) and counts hits.
	 * Individual records are preserved in the database; grouping is display-only.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array{items: array, total: int}
	 */
	public static function getGroupedLogs( array $args = [] ): array {
		$db = DB::db();

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'last_event',
			'order'    => 'DESC',
			'action'   => '',
			'search'   => '',
		];

		$args  = wp_parse_args( $args, $defaults );
		$table = DB::tableNameFull( self::TABLE_NAME );

		// Build WHERE clause.
		$where  = [];
		$values = [];

		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$values[] = $args['action'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]     = '(username LIKE %s OR ip_address LIKE %s)';
			$search_term = '%' . $db->esc_like( $args['search'] ) . '%';
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Validate orderby.
		$allowed_orderby = [ 'username', 'action', 'ip_address', 'hit_count', 'last_event' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_event';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Count total groups.
		$count_sql = "SELECT COUNT(*) FROM (SELECT 1 FROM {$table} {$where_sql} GROUP BY username, action, ip_address) AS sub";
		if ( $values ) {
			$total = (int) $db->get_var( $db->prepare( $count_sql, ...$values ) );
		} else {
			$total = (int) $db->get_var( $count_sql );
		}

		// Prevent GROUP_CONCAT truncation on large groups (default 1024 bytes).
		$db->query( 'SET SESSION group_concat_max_len = 1000000' );

		// Get grouped items.
		$offset    = ( (int) $args['page'] - 1 ) * (int) $args['per_page'];
		$limit     = (int) $args['per_page'];
		$items_sql = "SELECT
			GROUP_CONCAT(id ORDER BY created_at DESC) as group_ids,
			MAX(user_id) as user_id,
			username,
			action,
			ip_address,
			SUBSTRING_INDEX(GROUP_CONCAT(user_agent ORDER BY created_at DESC SEPARATOR '|||'), '|||', 1) as user_agent,
			COUNT(*) as hit_count,
			MAX(created_at) as last_event,
			MIN(created_at) as first_event
		FROM {$table}
		{$where_sql}
		GROUP BY username, action, ip_address
		ORDER BY {$orderby} {$order}
		LIMIT %d OFFSET %d";

		$prepared_values = array_merge( $values, [ $limit, $offset ] );
		$items           = $db->get_results( $db->prepare( $items_sql, ...$prepared_values ), ARRAY_A );

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	/**
	 * Get grouped log counts by action type.
	 *
	 * Counts distinct (username, action, ip_address) groups per action.
	 *
	 * @return array<string, int>
	 */
	public static function getGroupedCounts(): array {
		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$results = $db->get_results(
			"SELECT action, COUNT(*) as count FROM (
				SELECT username, action, ip_address
				FROM {$table}
				GROUP BY username, action, ip_address
			) AS sub
			GROUP BY action",
			ARRAY_A
		);

		$counts = [
			'login'            => 0,
			'logout'           => 0,
			'failed'           => 0,
			'otp_failed'       => 0,
			'totp_setup'       => 0,
			'totp_reset'       => 0,
			'magic_link_sent'  => 0,
			'magic_link_login' => 0,
			'blocked_username' => 0,
			'total'            => 0,
		];

		foreach ( $results as $row ) {
			$counts[ $row['action'] ] = (int) $row['count'];
			$counts['total']         += (int) $row['count'];
		}

		return $counts;
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

		$db     = DB::db();
		$table  = DB::tableNameFull( self::TABLE_NAME );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . self::RETENTION_DAYS . ' days' ) );

		// Delete old entries.
		$deleted = (int) $db->query(
			$db->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				$cutoff
			)
		);

		// Limit total entries.
		$total = (int) $db->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $total > self::MAX_LOGS ) {
			$excess = $total - self::MAX_LOGS;

			$deleted += (int) $db->query(
				$db->prepare(
					"DELETE FROM {$table} ORDER BY created_at ASC LIMIT %d",
					$excess
				)
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
}
