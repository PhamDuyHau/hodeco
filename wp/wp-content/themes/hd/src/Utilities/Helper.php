<?php
/**
 * Theme Helper Utilities
 *
 * This file defines the Helper class, a static utility class that provides
 * commonly used helper methods for various theme functionalities.
 * It centralizes reusable logic such as data formatting, template helpers,
 * and other generic utility operations.
 *
 * @author HD
 */

namespace HD\Utilities;

use HD\Core\DB;
use HD\Utilities\Traits\Arr;
use HD\Utilities\Traits\Base;
use HD\Utilities\Traits\DateTime;
use HD\Utilities\Traits\Embed;
use HD\Utilities\Traits\Encryption;
use HD\Utilities\Traits\File;
use HD\Utilities\Traits\Generator;
use HD\Utilities\Traits\Minification;
use HD\Utilities\Traits\Str;
use HD\Utilities\Traits\Url;
use HD\Utilities\Traits\Validation;
use HD\Utilities\Traits\WpAcf;
use HD\Utilities\Traits\WpMedia;
use HD\Utilities\Traits\WpMisc;
use HD\Utilities\Traits\WpNavigation;
use HD\Utilities\Traits\WpOptions;
use HD\Utilities\Traits\WpPost;
use HD\Utilities\Traits\WpQuery;
use HD\Utilities\Traits\WpTemplate;

defined( 'ABSPATH' ) || exit;

final class Helper {
	// Base utility traits
	use Base;
	use Arr;
	use DateTime;
	use Embed;
	use Encryption;
	use File;
	use Generator;
	use Minification;
	use Str;
	use Url;
	use Validation;

	// WordPress-specific traits
	use WpAcf;
	use WpMedia;
	use WpMisc;
	use WpNavigation;
	use WpOptions;
	use WpPost;
	use WpQuery;
	use WpTemplate;

	// -------------------------------------------------------------

	/**
	 * @param string $table
	 * @param int $postId
	 *
	 * @return int
	 */
	public static function totalPostViews( string $table, int $postId ): int {
		$tableName = DB::backtickedTable( $table );

		return (int) DB::db()->get_var(
			DB::db()->prepare( "SELECT SUM(view_count) FROM {$tableName} WHERE `post_id` = %d", $postId )
		);
	}

	// -------------------------------------------------------------

	/**
	 * @param string $name
	 * @param array $defaultValue
	 *
	 * @return mixed
	 */
	public static function filterSettingOptions( string $name, array $defaultValue = [] ): mixed {
		static $filters = null;
		$filters      ??= apply_filters( 'hd_settings_filter', [] );

		if ( ! isset( $filters[ $name ] ) ) {
			return $defaultValue;
		}

		return $filters[ $name ] ?: $defaultValue;
	}

	// -------------------------------------------------------------

	/**
	 * @return string
	 */
	public static function currentLanguage(): string {
		// Polylang
		if ( function_exists( 'pll_current_language' ) ) {
			return \pll_current_language( 'slug' );
		}

		// Weglot
		if ( function_exists( 'weglot_get_current_language' ) ) {
			return \weglot_get_current_language();
		}

		// WPML
		$currentLanguage = apply_filters( 'wpml_current_language', null );

		return $currentLanguage ?: strtolower( substr( get_bloginfo( 'language' ), 0, 2 ) );
	}

	// --------------------------------------------------

	/**
	 * Clear all caches.
	 *
	 * Theme handles basic WordPress cache (transients + object cache).
	 * Plugin can hook into 'hd_clear_all_cache' for comprehensive clearing (cache plugins).
	 *
	 * @return void
	 */
	public static function clearAllCache(): void {
		self::clearTransients();
		self::flushObjectCache();

		// Allow plugin to handle cache plugin clearing
		do_action( 'hd_clear_all_cache' );
	}

	// -------------------------------------------------------------

	/**
	 * Clear WordPress transients from database.
	 *
	 * When persistent object cache (Redis/Memcached) is active,
	 * transients are stored in the object cache — not wp_options.
	 * flushObjectCache() handles that case, so we skip the SQL.
	 */
	private static function clearTransients(): void {
		if ( wp_using_ext_object_cache() ) {
			return;
		}

		$patterns = [
			'_transient_%',
			'_transient_timeout_%',
			'_site_transient_%',
			'_site_transient_timeout_%',
		];

		$db    = DB::db();
		$table = $db->options;

		foreach ( $patterns as $pattern ) {
			$db->query(
				$db->prepare( "DELETE FROM {$table} WHERE option_name LIKE %s LIMIT 1000", $pattern )
			);
		}
	}

	// -------------------------------------------------------------

	/**
	 * Flush object cache.
	 */
	private static function flushObjectCache(): void {
		wp_cache_flush();
	}
}
