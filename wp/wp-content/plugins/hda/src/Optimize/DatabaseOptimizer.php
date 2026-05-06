<?php
/**
 * Database Optimizer - Clean up database bloat and optimize tables.
 *
 * Removes revisions, auto-drafts, trashed posts/comments, expired transients,
 * orphaned metadata, and optimizes database tables.
 *
 * @package HDAddons\Optimize
 * @author  HD
 */

namespace HDAddons\Optimize;

use HDAddons\Asset;
use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class DatabaseOptimizer implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys ─────────────────────────────────
	public const string OPTION_NAME     = 'db_optimizer__options';
	public const string KEY_SCHEDULE    = 'schedule';    // '' | 'daily' | 'weekly' | 'monthly'
	public const string KEY_REVISIONS   = 'revisions';
	public const string KEY_AUTO_DRAFTS = 'auto_drafts';
	public const string KEY_TRASH_POSTS = 'trash_posts';
	public const string KEY_SPAM_COMMENTS    = 'spam_comments';
	public const string KEY_TRASH_COMMENTS   = 'trash_comments';
	public const string KEY_TRANSIENTS       = 'transients';
	public const string KEY_ORPHAN_POSTMETA  = 'orphan_postmeta';
	public const string KEY_ORPHAN_TERMMETA  = 'orphan_termmeta';
	public const string KEY_OPTIMIZE_TABLES  = 'optimize_tables';

	private const string CRON_HOOK = 'hda_db_optimizer_cleanup';

	/**
	 * Cached options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ─────────────────────────────────────────────────

	/**
	 * Initialize the module.
	 */
	public function __construct() {
		// Cron handler.
		add_action( self::CRON_HOOK, self::runScheduledCleanup( ... ) );

		// AJAX handler for manual cleanup.
		add_action( 'wp_ajax_hda_db_optimize', self::ajaxOptimize( ... ) );

		// Localize nonce for external JS module.
		add_action( 'admin_enqueue_scripts', static function (): void {
			$handle = Asset::handle( 'hda.js' );
			if ( $handle ) {
				Asset::localize( $handle, 'hdaDbOptimizer', [
					'nonce' => wp_create_nonce( 'hda_db_optimize' ),
					'i18n'  => [
						'optimizing' => __( 'Optimizing...', HDA_TEXTDOMAIN ),
					],
				] );
			}
		}, 50 );
	}

	// ─────────────────────────────────────────────────

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

	/**
	 * Reset cached options (call after save).
	 *
	 * @return void
	 */
	public static function resetCache(): void {
		self::$options = null;
	}

	// ─────────────────────────────────────────────────

	/**
	 * Sync the cron schedule based on the given recurrence.
	 * Called from SettingsHandlerTrait on settings save — NOT on every admin_init.
	 *
	 * @param string $schedule '' | 'daily' | 'weekly' | 'monthly'
	 *
	 * @return void
	 */
	public static function syncSchedule( string $schedule ): void {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( empty( $schedule ) ) {
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
			}

			return;
		}

		// Re-schedule if recurrence changed.
		if ( $scheduled ) {
			$existing = wp_get_schedule( self::CRON_HOOK );
			if ( $existing !== $schedule ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
				$scheduled = false;
			}
		}

		if ( ! $scheduled ) {
			wp_schedule_event( time(), $schedule, self::CRON_HOOK );
		}

		self::resetCache();
	}

	// ─────────────────────────────────────────────────

	/**
	 * Run scheduled cleanup using saved options.
	 *
	 * @return void
	 */
	public static function runScheduledCleanup(): void {
		$options = self::getOptions();
		self::runCleanup( $options );
	}

	// ─────────────────────────────────────────────────

	/**
	 * AJAX handler for manual optimization.
	 *
	 * @return void
	 */
	public static function ajaxOptimize(): void {
		check_ajax_referer( 'hda_db_optimize', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}

		$tasks = isset( $_POST['tasks'] ) && is_array( $_POST['tasks'] )
			? array_map( 'sanitize_key', $_POST['tasks'] )
			: [];

		if ( empty( $tasks ) ) {
			wp_send_json_error( [ 'message' => 'No tasks selected.' ] );
		}

		// Build a pseudo-options array from selected tasks.
		$taskOptions = [];
		foreach ( $tasks as $task ) {
			$taskOptions[ $task ] = true;
		}

		$results = self::runCleanup( $taskOptions );

		wp_send_json_success( [
			'message' => __( 'Optimization complete.', HDA_TEXTDOMAIN ),
			'results' => $results,
		] );
	}

	// ─────────────────────────────────────────────────

	/**
	 * Run cleanup tasks based on provided options.
	 *
	 * @param array $options Task flags.
	 *
	 * @return array<string, int> Results with counts.
	 */
	public static function runCleanup( array $options ): array {
		global $wpdb;

		$results = [];

		// ── Post Revisions ───────────────────────────
		if ( ! empty( $options[ self::KEY_REVISIONS ] ) ) {
			$results['revisions'] = (int) $wpdb->query(
				"DELETE p, pm FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				 WHERE p.post_type = 'revision'"
			);
		}

		// ── Auto-Drafts ──────────────────────────────
		if ( ! empty( $options[ self::KEY_AUTO_DRAFTS ] ) ) {
			$results['auto_drafts'] = (int) $wpdb->query(
				"DELETE p, pm FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				 WHERE p.post_status = 'auto-draft'"
			);
		}

		// ── Trashed Posts ────────────────────────────
		if ( ! empty( $options[ self::KEY_TRASH_POSTS ] ) ) {
			$results['trash_posts'] = (int) $wpdb->query(
				"DELETE p, pm FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				 WHERE p.post_status = 'trash'"
			);
		}

		// ── Spam Comments ────────────────────────────
		if ( ! empty( $options[ self::KEY_SPAM_COMMENTS ] ) ) {
			$results['spam_comments'] = (int) $wpdb->query(
				"DELETE c, cm FROM {$wpdb->comments} c
				 LEFT JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID
				 WHERE c.comment_approved = 'spam'"
			);
		}

		// ── Trashed Comments ─────────────────────────
		if ( ! empty( $options[ self::KEY_TRASH_COMMENTS ] ) ) {
			$results['trash_comments'] = (int) $wpdb->query(
				"DELETE c, cm FROM {$wpdb->comments} c
				 LEFT JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID
				 WHERE c.comment_approved = 'trash'"
			);
		}

		// ── Expired Transients ───────────────────────
		if ( ! empty( $options[ self::KEY_TRANSIENTS ] ) ) {
			$time = time();
			$results['transients'] = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a
					 INNER JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout_', '_')
					 WHERE a.option_name LIKE '\_transient\_timeout\_%'
					 AND a.option_value < %d",
					$time
				)
			);
		}

		// ── Orphaned Postmeta ────────────────────────
		if ( ! empty( $options[ self::KEY_ORPHAN_POSTMETA ] ) ) {
			$results['orphan_postmeta'] = (int) $wpdb->query(
				"DELETE pm FROM {$wpdb->postmeta} pm
				 LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE p.ID IS NULL"
			);
		}

		// ── Orphaned Termmeta ────────────────────────
		if ( ! empty( $options[ self::KEY_ORPHAN_TERMMETA ] ) ) {
			$results['orphan_termmeta'] = (int) $wpdb->query(
				"DELETE tm FROM {$wpdb->termmeta} tm
				 LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
				 WHERE t.term_id IS NULL"
			);
		}

		// ── Optimize Tables ──────────────────────────
		if ( ! empty( $options[ self::KEY_OPTIMIZE_TABLES ] ) ) {
			$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
			$count  = 0;

			foreach ( $tables as $table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "OPTIMIZE TABLE `{$table}`" );
				++$count;
			}

			$results['optimize_tables'] = $count;
		}

		return $results;
	}

	// ─────────────────────────────────────────────────

	/**
	 * Get counts of items that can be cleaned.
	 *
	 * @return array<string, int>
	 */
	public static function getCounts(): array {
		global $wpdb;

		return [
			'revisions'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" ),
			'auto_drafts'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" ),
			'trash_posts'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" ),
			'spam_comments'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ),
			'trash_comments'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" ),
			'transients'      => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_%' AND option_value < %d",
					time()
				)
			),
			'orphan_postmeta' => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL"
			),
			'orphan_termmeta' => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id WHERE t.term_id IS NULL"
			),
		];
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return self::OPTION_NAME;
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$input   = $data[ self::OPTION_NAME ];
		$options = [];

		// Schedule ('' | 'daily' | 'weekly' | 'monthly').
		$schedule = $input[ self::KEY_SCHEDULE ] ?? '';
		if ( in_array( $schedule, [ 'daily', 'weekly', 'monthly' ], true ) ) {
			$options[ self::KEY_SCHEDULE ] = $schedule;
		}

		// Task checkboxes.
		$taskKeys = [
			self::KEY_REVISIONS,
			self::KEY_AUTO_DRAFTS,
			self::KEY_TRASH_POSTS,
			self::KEY_SPAM_COMMENTS,
			self::KEY_TRASH_COMMENTS,
			self::KEY_TRANSIENTS,
			self::KEY_ORPHAN_POSTMETA,
			self::KEY_ORPHAN_TERMMETA,
			self::KEY_OPTIMIZE_TABLES,
		];

		foreach ( $taskKeys as $key ) {
			if ( ! empty( $input[ $key ] ) ) {
				$options[ $key ] = true;
			}
		}

		self::saveOrRemove( self::OPTION_NAME, $options );

		// Manage cron schedule immediately after save.
		self::syncSchedule( $options[ self::KEY_SCHEDULE ] ?? '' );
	}
}
