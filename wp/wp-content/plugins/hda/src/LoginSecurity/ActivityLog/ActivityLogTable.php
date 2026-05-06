<?php
/**
 * Activity Log List Table - WP_List_Table implementation.
 *
 * Displays grouped view: identical (username, action, ip) entries
 * are merged into a single row with a hit count.
 *
 * @package HDAddons\LoginSecurity\ActivityLog
 * @author  HD
 */

namespace HDAddons\LoginSecurity\ActivityLog;

use HDAddons\DB;

\defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class ActivityLogTable extends \WP_List_Table {

	/**
	 * Total items from the last query (avoids re-counting in extra_tablenav).
	 *
	 * @var int
	 */
	private int $totalItems = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Activity Log', HDA_TEXTDOMAIN ),
				'plural'   => __( 'Activity Logs', HDA_TEXTDOMAIN ),
				'ajax'     => false,
			]
		);
	}

	/**
	 * Get table columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username', HDA_TEXTDOMAIN ),
			'action'     => __( 'Action', HDA_TEXTDOMAIN ),
			'ip_address' => __( 'IP Address', HDA_TEXTDOMAIN ),
			'hit_count'  => __( 'Hits', HDA_TEXTDOMAIN ),
			'user_agent' => __( 'Browser', HDA_TEXTDOMAIN ),
			'last_event' => __( 'Last Event', HDA_TEXTDOMAIN ),
		];
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return [
			'username'   => [ 'username', false ],
			'action'     => [ 'action', false ],
			'ip_address' => [ 'ip_address', false ],
			'hit_count'  => [ 'hit_count', true ],
			'last_event' => [ 'last_event', true ],
		];
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return [
			'delete' => __( 'Delete', HDA_TEXTDOMAIN ),
		];
	}

	/**
	 * Process bulk actions.
	 *
	 * Handles comma-separated group IDs from grouped rows.
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		// Verify nonce.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		// Each value may be comma-separated group IDs (e.g. "5,3,2,1").
		$raw_ids = isset( $_REQUEST['log_ids'] ) ? (array) $_REQUEST['log_ids'] : [];
		$ids     = [];

		foreach ( $raw_ids as $raw ) {
			foreach ( explode( ',', sanitize_text_field( $raw ) ) as $part ) {
				$id = absint( $part );
				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}
		}

		if ( empty( $ids ) ) {
			return;
		}

		$db           = DB::db();
		$table        = DB::tableNameFull( ActivityLog::TABLE_NAME );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$db->query( $db->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) );
	}

	/**
	 * Prepare items for display.
	 *
	 * Uses grouped query to merge identical (username, action, ip) entries.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$this->process_bulk_action();

		$per_page = 20;
		$page     = $this->get_pagenum();

		// Get filter values.
		$action_filter = isset( $_REQUEST['action_filter'] ) ? sanitize_key( $_REQUEST['action_filter'] ) : '';
		$search        = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby       = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'last_event';
		$order         = isset( $_REQUEST['order'] ) ? sanitize_key( $_REQUEST['order'] ) : 'DESC';

		$result = ActivityLog::getGroupedLogs(
			[
				'per_page' => $per_page,
				'page'     => $page,
				'action'   => $action_filter,
				'search'   => $search,
				'orderby'  => $orderby,
				'order'    => $order,
			]
		);

		$this->items      = $result['items'];
		$this->totalItems = $result['total'];

		$this->set_pagination_args(
			[
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			]
		);

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];
	}

	/**
	 * Get views (action filters).
	 *
	 * @return array
	 */
	protected function get_views(): array {
		$counts  = ActivityLog::getGroupedCounts();
		$current = isset( $_REQUEST['action_filter'] ) ? sanitize_key( $_REQUEST['action_filter'] ) : '';

		$base_url = admin_url( 'admin.php?page=hda-activity-log' );

		$views = [
			'all' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( $base_url ),
				'' === $current ? 'current' : '',
				__( 'All', HDA_TEXTDOMAIN ),
				$counts['total']
			),
		];

		$action_labels = [
			'login'            => __( 'Login', HDA_TEXTDOMAIN ),
			'logout'           => __( 'Logout', HDA_TEXTDOMAIN ),
			'failed'           => __( 'Failed', HDA_TEXTDOMAIN ),
			'otp_failed'       => __( 'OTP Failed', HDA_TEXTDOMAIN ),
			'totp_setup'       => __( 'TOTP Setup', HDA_TEXTDOMAIN ),
			'totp_reset'       => __( 'TOTP Reset', HDA_TEXTDOMAIN ),
			'magic_link_sent'  => __( 'Magic Link Sent', HDA_TEXTDOMAIN ),
			'magic_link_login' => __( 'Magic Link Login', HDA_TEXTDOMAIN ),
			'blocked_username' => __( 'Blocked Username', HDA_TEXTDOMAIN ),
		];

		foreach ( $action_labels as $action => $label ) {
			if ( $counts[ $action ] > 0 ) {
				$views[ $action ] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'action_filter', $action, $base_url ) ),
					$current === $action ? 'current' : '',
					$label,
					$counts[ $action ]
				);
			}
		}

		return $views;
	}

	/**
	 * Extra table navigation (filters).
	 *
	 * @param string $which Top or bottom.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		if ( $this->totalItems > 0 ) {
			echo '<div class="alignleft actions">';
			submit_button( __( 'Clear All Logs', HDA_TEXTDOMAIN ), 'delete', 'clear_all_logs', false );
			echo '</div>';
		}
	}

	/**
	 * Checkbox column.
	 *
	 * Uses comma-separated group IDs so bulk delete removes entire groups.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		$value = ! empty( $item['group_ids'] ) ? $item['group_ids'] : (string) absint( $item['id'] );

		return sprintf(
			'<input type="checkbox" name="log_ids[]" value="%s" />',
			esc_attr( $value )
		);
	}

	/**
	 * Username column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_username( $item ): string {
		$username = esc_html( $item['username'] );

		if ( $item['user_id'] > 0 ) {
			$user = get_user_by( 'ID', $item['user_id'] );
			if ( $user ) {
				$avatar = get_avatar( $item['user_id'], 24 );
				return sprintf(
					'%s <a href="%s">%s</a>',
					$avatar,
					esc_url( get_edit_user_link( $item['user_id'] ) ),
					$username
				);
			}
		}

		return sprintf( '<span class="unknown-user">%s</span>', $username );
	}

	/**
	 * Action column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_action( $item ): string {
		$action = $item['action'];

		$labels = [
			'login'            => '<span class="action-badge action-login">' . esc_html__( 'Login', HDA_TEXTDOMAIN ) . '</span>',
			'logout'           => '<span class="action-badge action-logout">' . esc_html__( 'Logout', HDA_TEXTDOMAIN ) . '</span>',
			'failed'           => '<span class="action-badge action-failed">' . esc_html__( 'Failed', HDA_TEXTDOMAIN ) . '</span>',
			'otp_failed'       => '<span class="action-badge action-failed">' . esc_html__( 'OTP Failed', HDA_TEXTDOMAIN ) . '</span>',
			'totp_setup'       => '<span class="action-badge action-login">' . esc_html__( 'TOTP Setup', HDA_TEXTDOMAIN ) . '</span>',
			'totp_reset'       => '<span class="action-badge action-logout">' . esc_html__( 'TOTP Reset', HDA_TEXTDOMAIN ) . '</span>',
			'magic_link_sent'  => '<span class="action-badge action-login">' . esc_html__( 'Magic Link Sent', HDA_TEXTDOMAIN ) . '</span>',
			'magic_link_login' => '<span class="action-badge action-login">' . esc_html__( 'Magic Link Login', HDA_TEXTDOMAIN ) . '</span>',
			'blocked_username' => '<span class="action-badge action-failed">' . esc_html__( 'Blocked Username', HDA_TEXTDOMAIN ) . '</span>',
		];

		return $labels[ $action ] ?? esc_html( $action );
	}

	/**
	 * IP Address column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_ip_address( array $item ): string {
		return sprintf( '<code>%s</code>', esc_html( $item['ip_address'] ) );
	}

	/**
	 * Hit count column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_hit_count( array $item ): string {
		$count = (int) $item['hit_count'];

		$class = 'hit-count';
		if ( $count >= 100 ) {
			$class .= ' hit-count--high';
		} elseif ( $count >= 10 ) {
			$class .= ' hit-count--medium';
		}

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), number_format_i18n( $count ) );
	}

	/**
	 * User Agent column.
	 *
	 * Shows the browser from the most recent event in the group.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_user_agent( array $item ): string {
		$ua = $item['user_agent'];

		if ( empty( $ua ) ) {
			return '<em>' . esc_html__( 'Unknown', HDA_TEXTDOMAIN ) . '</em>';
		}

		// Simple browser detection.
		$browser = 'Unknown';
		if ( str_contains( $ua, 'Firefox' ) ) {
			$browser = 'Firefox';
		} elseif ( str_contains( $ua, 'Edg/' ) ) {
			$browser = 'Edge';
		} elseif ( str_contains( $ua, 'Chrome' ) ) {
			$browser = 'Chrome';
		} elseif ( str_contains( $ua, 'Safari' ) ) {
			$browser = 'Safari';
		} elseif ( str_contains( $ua, 'MSIE' ) || str_contains( $ua, 'Trident' ) ) {
			$browser = 'IE';
		}

		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( $ua ),
			esc_html( $browser )
		);
	}

	/**
	 * Last event column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_last_event( array $item ): string {
		// created_at is stored in local time via current_time('mysql').
		// Convert to UTC Unix timestamp for correct display.
		$gmt_offset = (int) ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
		$timestamp  = strtotime( $item['last_event'] ) - $gmt_offset;

		return sprintf(
			'<time datetime="%s" title="%s">%s</time>',
			esc_attr( gmdate( 'c', $timestamp ) ),
			esc_attr( wp_date( 'Y-m-d H:i:s', $timestamp ) ),
			esc_html( human_time_diff( $timestamp ) ) . ' ' . esc_html__( 'ago', HDA_TEXTDOMAIN )
		);
	}

	/**
	 * Default column handler.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( $item[ $column_name ] ?? '' );
	}

	/**
	 * Display when no items found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No activity logs found.', HDA_TEXTDOMAIN );
	}
}
