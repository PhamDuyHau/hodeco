<?php
/**
 * Post Views Service.
 *
 * Tracks and retrieves post view counts with IP-based cooldown.
 *
 * @package HD\App\Modules\Services
 * @author  HD
 */

namespace HD\App\Modules\Services;

use HD\App\Modules\AbstractModule;
use HD\Core\DB;
use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

final class PostView extends AbstractModule {

	private const int VIEW_COOLDOWN = 240; // 4 minutes
	private string $table           = 'post_views';

	// -----------------------------------------

	protected function init(): void {
		// No initialization hooks needed - this module is called on-demand
	}

	// -----------------------------------------

	/**
	 * @param int    $postId
	 * @param string $ip
	 *
	 * @return void
	 */
	public function record_view( int $postId, string $ip ): void {
		// Ensure the table exists before recording views
		$this->ensureTablesExist();

		$now      = time();
		$packedIp = inet_pton( $ip );

		if ( $packedIp === false ) {
			return;
		}

		// Fetch existing record for this post and IP (if any)
		$record = DB::getOne( $this->table, 'post_id = %d AND ip = %s', [ $postId, $packedIp ] );

		// First visit from this IP
		if ( ! $record ) {
			DB::insertOneRow(
				$this->table,
				[
					'post_id'    => $postId,
					'ip'         => $packedIp,
					'last_view'  => $now,
					'view_count' => 1,
				]
			);

			return;
		}

		if ( ! isset( $record['id'], $record['last_view'] ) ) {
			return;
		}

		// Same IP, within cooldown period
		if ( ( $now - (int) $record['last_view'] ) < self::VIEW_COOLDOWN ) {
			DB::updateOneRow( $this->table, $record['id'], [ 'last_view' => $now ] );

			return;
		}

		// Cooldown expired, increment view count
		DB::updateOneRow(
			$this->table,
			$record['id'],
			[
				'view_count' => (int) $record['view_count'] + 1,
				'last_view'  => $now,
			]
		);
	}

	// -----------------------------------------

	/**
	 * @param int $postId
	 *
	 * @return int
	 */
	public function get_total_views( int $postId ): int {
		return Helper::totalPostViews( $this->table, $postId );
	}

	// -----------------------------------------

	private function ensureTablesExist(): void {
		DB::createTable(
			$this->table,
			'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT UNSIGNED NOT NULL,
			ip VARBINARY(45) NOT NULL,
			last_view INT UNSIGNED NOT NULL,
			view_count INT UNSIGNED DEFAULT 1,
			UNIQUE KEY unique_view (post_id, ip),
			KEY post_id_idx (post_id)'
		);
	}
}
