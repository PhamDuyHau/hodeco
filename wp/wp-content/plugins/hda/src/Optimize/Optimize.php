<?php
/**
 * Optimize Module
 *
 * Heartbeat, embeds, wp_head cleanup, and database optimization.
 *
 * @package HDAddons\Optimize
 * @author  HD
 */

namespace HDAddons\Optimize;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

defined( 'ABSPATH' ) || exit;

class Optimize implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME              = 'optimize__options';
	public const string KEY_HEARTBEAT_FREQUENCY  = 'heartbeat_frequency';
	public const string KEY_HEARTBEAT_LOCATION   = 'heartbeat_location';
	public const string KEY_DISABLE_EMBEDS       = 'disable_embeds';
	public const string KEY_ENABLE_CLEANUP       = 'enable_cleanup';

	// --------------------------------------------------

	public function __construct() {

		// Initialize Performance (Heartbeat/Embeds/Cleanup) module
		new Performance();

		// Initialize Database Optimizer (cleanup/cron)
		new DatabaseOptimizer();
	}

	// --------------------------------------------------

	/**
	 * Get module options (cached per request).
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		static $options = null;

		if ( $options === null ) {
			$options = Helper::getOption( self::OPTION_NAME, [] );
		}

		return $options;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'optimize__options';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$input   = $data['optimize__options'];
		$options = [];

		$options[ self::KEY_HEARTBEAT_FREQUENCY ] = isset( $input[ self::KEY_HEARTBEAT_FREQUENCY ] )
			? (int) $input[ self::KEY_HEARTBEAT_FREQUENCY ]
			: 0;

		$options[ self::KEY_HEARTBEAT_LOCATION ] = isset( $input[ self::KEY_HEARTBEAT_LOCATION ] )
			? sanitize_key( $input[ self::KEY_HEARTBEAT_LOCATION ] )
			: 'default';

		$options[ self::KEY_DISABLE_EMBEDS ] = ! empty( $input[ self::KEY_DISABLE_EMBEDS ] );
		$options[ self::KEY_ENABLE_CLEANUP ] = ! empty( $input[ self::KEY_ENABLE_CLEANUP ] );

		self::saveOrRemove( self::OPTION_NAME, $options );
	}
}

