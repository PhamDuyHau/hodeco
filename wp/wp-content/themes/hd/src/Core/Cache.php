<?php
/**
 * Cache Manager
 *
 * Unified caching system that combines:
 * 1. Cache wrapper (remember/forget) - Environment-aware object caching
 * 2. Cache clearing (admin UX) - URL-based cache flush
 *
 * Auto-detects hosting environment:
 * - Production (LiteSpeed/Redis): Uses persistent cache
 * - Development: Falls back to transients
 *
 * @package HD\Core
 * @author  HD
 */

namespace HD\Core;

use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class Cache {
	use Singleton;

	// --------------------------------------------------
	// ADMIN UX - Cache Clearing
	// --------------------------------------------------

	private function init(): void {
		add_action( 'init', $this->handleClearCache( ... ) );

		// Auto-flush menu cache when menu is updated
		add_action( 'wp_update_nav_menu', self::flushMenuCaches( ... ) );

		// Auto-flush post/term cache when content changes
		$postHooks = [
			'save_post',
			'deleted_post',
			'trashed_post',
			'created_term',
			'edited_term',
			'delete_term',
		];
		array_map( fn( $hook ) => add_action( $hook, self::flushPostCaches( ... ), 10, 0 ), $postHooks );
	}

	// --------------------------------------------------

	/**
	 * Flush menu cache group.
	 */
	public static function flushMenuCaches(): void {
		self::flush( 'theme_menus' );
	}

	/**
	 * Handle clear cache request from admin.
	 *
	 * @return void
	 */
	public function handleClearCache(): void {
		$nonce = $_GET['_wpnonce'] ?? '';

		if (
			( $_GET['clear_cache'] ?? '' ) !== '1'
			|| ! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce( $nonce, 'hd_clear_cache' )
		) {
			return;
		}

		// Clear WordPress caches
		Helper::clearAllCache();

		// Clear theme object cache groups
		self::flushAll();

		set_transient( '_clear_cache_message', __( 'Cache has been successfully cleared.', TEXT_DOMAIN ), 30 );

		// Output script in footer to clean URL after cache clear.
		$this->addCleanupScript();
	}

	/**
	 * Add cleanup script to remove cache parameters from URL.
	 *
	 * @return void
	 */
	private function addCleanupScript(): void {
		$outputScript = static function (): void {
			?>
			<script>(function () {
					const currentUrl = window.location.href;
					if (currentUrl.includes('clear_cache=1')) {
						let newUrl = currentUrl.replace(/([?&])clear_cache=1(&_wpnonce=[^&]*)?/, '$1').replace(/[&?]$/, '');
						currentUrl.includes('wp-admin') ? window.location.replace(newUrl) : window.history.replaceState({}, document.title, newUrl);
					}
				})();</script>
			<?php
		};

		add_action( is_admin() ? 'admin_footer' : 'wp_footer', $outputScript, 99 );
	}

	// --------------------------------------------------
	// CACHE WRAPPER - Environment-Aware Caching
	// --------------------------------------------------

	/**
	 * Detect if persistent object cache is available.
	 *
	 * @return bool
	 */
	public static function isPersistent(): bool {
		return (bool) wp_using_ext_object_cache();
	}

	/**
	 * Get backend type (for debugging).
	 *
	 * @return string redis|memcached|litespeed|transient
	 */
	public static function getBackend(): string {
		if ( ! self::isPersistent() ) {
			return 'transient';
		}

		// Detect Redis
		if ( class_exists( 'Redis' ) || defined( 'WP_REDIS_CLIENT' ) ) {
			return 'redis';
		}

		// Detect Memcached
		if ( class_exists( 'Memcached' ) || class_exists( 'Memcache' ) ) {
			return 'memcached';
		}

		// Detect LiteSpeed Cache
		if ( defined( 'LSCWP_V' ) ) {
			return 'litespeed';
		}

		return 'unknown';
	}

	/**
	 * Remember: Get cached value or execute callback and cache result.
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Callback to execute if cache miss.
	 * @param string   $group    Cache group.
	 * @param int      $ttl      Time to live in seconds (0 = forever).
	 *
	 * @return mixed
	 */
	public static function remember( string $key, callable $callback, string $group = 'hd_theme', int $ttl = HOUR_IN_SECONDS ): mixed {
		// Try persistent cache first
		if ( self::isPersistent() ) {
			$cached = wp_cache_get( $key, $group );

			if ( false !== $cached ) {
				return $cached;
			}

			$value = $callback();
			wp_cache_set( $key, $value, $group, $ttl );

			return $value;
		}

		// Fallback to transients (development/test environments)
		return self::rememberTransient( $key, $callback, $group, $ttl );
	}

	/**
	 * Remember using transients (fallback for non-cached environments).
	 *
	 * @param string   $key
	 * @param callable $callback
	 * @param string   $group
	 * @param int      $ttl
	 *
	 * @return mixed
	 */
	private static function rememberTransient( string $key, callable $callback, string $group, int $ttl ): mixed {
		$transientKey = 'hd_' . md5( $group . ':' . $key );
		$cached       = get_transient( $transientKey );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $callback();
		set_transient( $transientKey, $value, $ttl );

		return $value;
	}

	/**
	 * Remember forever (until manually cleared).
	 *
	 * @param string   $key
	 * @param callable $callback
	 * @param string   $group
	 *
	 * @return mixed
	 */
	public static function rememberForever( string $key, callable $callback, string $group = 'hd_theme' ): mixed {
		return self::remember( $key, $callback, $group, 0 );
	}

	/**
	 * Forget: Delete cached value.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function forget( string $key, string $group = 'hd_theme' ): bool {
		if ( self::isPersistent() ) {
			return wp_cache_delete( $key, $group );
		}

		// Fallback: delete transient (key must match rememberTransient derivation)
		$transientKey = 'hd_' . md5( $group . ':' . $key );

		return delete_transient( $transientKey );
	}

	/**
	 * Flush entire cache group.
	 *
	 * With persistent cache (production): flushes the specific group.
	 * Without persistent cache (dev): deletes ALL theme transients ('hd_' prefixed)
	 * since transients cannot be selectively flushed by group.
	 *
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function flush( string $group = 'hd_theme' ): bool {
		if ( ! self::isPersistent() ) {
			// Dev fallback: delete all theme transients from DB.
			// Cannot target specific groups — transient keys are md5 hashed.
			// Acceptable for dev: content freshness > cache efficiency.
			global $wpdb;
			$wpdb->query(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hd\_%' OR option_name LIKE '_transient_timeout_hd\_%'"
			);

			return true;
		}

		// Support for Redis/Memcached group flush
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			return wp_cache_flush_group( $group );
		}

		// Fallback: try wp_cache_delete_group (some cache plugins support this)
		if ( function_exists( 'wp_cache_delete_group' ) ) {
			wp_cache_delete_group( $group );

			return true;
		}

		return false;
	}

	/**
	 * Flush all theme cache groups.
	 *
	 * @return void
	 */
	public static function flushAll(): void {
		$groups = [
			'hd_theme',
			'theme_posts',
			'theme_menus',
			'theme_taxonomies',
			'theme_queries',
			'acf_cache',
		];

		foreach ( $groups as $group ) {
			self::flush( $group );
		}
	}

	/**
	 * Flush post-related cache groups.
	 *
	 * Called when posts are created, updated, or deleted.
	 *
	 * @return void
	 */
	public static function flushPostCaches(): void {
		self::flush( 'theme_posts' );
		self::flush( 'theme_queries' );
		self::flush( 'theme_taxonomies' );
	}

	/**
	 * Get cache statistics (for debugging).
	 *
	 * @return array
	 */
	public static function stats(): array {
		return [
			'persistent' => self::isPersistent(),
			'backend'    => self::getBackend(),
			'enabled'    => true,
		];
	}

	/**
	 * Warm cache with multiple items.
	 *
	 * @param array  $items Array of [ key => callback ] pairs.
	 * @param string $group
	 * @param int    $ttl
	 *
	 * @return void
	 */
	public static function warm( array $items, string $group = 'hd_theme', int $ttl = HOUR_IN_SECONDS ): void {
		foreach ( $items as $key => $callback ) {
			self::remember( $key, $callback, $group, $ttl );
		}
	}
}
