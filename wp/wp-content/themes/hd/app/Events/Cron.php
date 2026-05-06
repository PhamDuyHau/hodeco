<?php
/**
 * Register Cron.
 *
 * @author HD
 */

namespace HD\App\Events;

defined( 'ABSPATH' ) || die;

final class Cron {
	/**
	 * Register custom cron schedules.
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public static function register( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => __( 'Once Weekly', TEXT_DOMAIN ),
			);
		}

		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Once Monthly', TEXT_DOMAIN ),
			);
		}

		return $schedules;
	}
}
