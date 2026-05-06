<?php
/**
 * Base utility trait.
 *
 * Provides common utility methods for WordPress development.
 *
 * @package HD\Utilities\Traits
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Base {

	// -------------------------------------------------------------

	/**
	 * Check if running in development mode.
	 *
	 * @return bool
	 */
	public static function development(): bool {
		return wp_get_environment_type() === 'development' || ( defined( 'WP_DEBUG' ) && \WP_DEBUG === true );
	}

	// -------------------------------------------------------------

	/**
	 * Get version string for cache-busting.
	 * Returns timestamp in development mode, null in production.
	 *
	 * @return ?string
	 */
	public static function version(): ?string {
		$isDev = self::development() || ( defined( 'FORCE_VERSION' ) && \FORCE_VERSION === true );

		return $isDev ? (string) time() : null;
	}

	// -------------------------------------------------------------

	/**
	 * Render admin notice.
	 *
	 * @param string $msg Notice message.
	 * @param string $cssClass CSS class for notice.
	 *
	 * @return void
	 */
	private static function renderNotice( string $msg, string $cssClass ): void {
		printf(
			'<div class="%1$s"><p><strong>%2$s</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%3$s</span></button></div>',
			esc_attr( $cssClass ),
			esc_html( $msg ),
			esc_html__( 'Dismiss this notice.', TEXT_DOMAIN )
		);
	}

	// -------------------------------------------------------------

	/**
	 * Display success notice.
	 *
	 * @param string $msg Message text.
	 * @param bool $autoHide Whether to auto-hide.
	 *
	 * @return void
	 */
	public static function messageSuccess( string $msg = 'Values saved', bool $autoHide = false ): void {
		$class = 'notice notice-success is-dismissible' . ( $autoHide ? ' dismissible-auto' : '' );
		self::renderNotice( $msg, $class );
	}

	// -------------------------------------------------------------

	/**
	 * Display error notice.
	 *
	 * @param string $msg Message text.
	 * @param bool $autoHide Whether to auto-hide.
	 *
	 * @return void
	 */
	public static function messageError( string $msg = 'Values error', bool $autoHide = false ): void {
		$class = 'notice notice-error is-dismissible' . ( $autoHide ? ' dismissible-auto' : '' );
		self::renderNotice( $msg, $class );
	}

	// -------------------------------------------------------------

	/**
	 * Throttled error logging with a 1-minute throttle per unique message.
	 *
	 * @param string $message Error message.
	 * @param int $type Error type.
	 * @param string|null $dest Destination.
	 * @param string|null $headers Headers.
	 *
	 * @return void
	 */
	public static function errorLog( string $message, int $type = 0, ?string $dest = null, ?string $headers = null ): void {
		$key = 'hdf_err_' . md5( $message );

		// Throttle: skip if same message was logged within the last minute.
		if ( wp_cache_get( $key, 'hdf_error_log_cache' ) ) {
			return;
		}

		wp_cache_set( $key, 1, 'hdf_error_log_cache', MINUTE_IN_SECONDS );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging utility.
		error_log( $message, $type, $dest, $headers );
	}

	// -------------------------------------------------------------

	/**
	 * Check if value is empty (handles strings properly).
	 *
	 * @param mixed $value Value to check.
	 *
	 * @return bool
	 */
	public static function isEmpty( mixed $value ): bool {
		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		return ! is_numeric( $value ) && ! is_bool( $value ) && empty( $value );
	}

	// -------------------------------------------------------------

	/**
	 * Check if the current page is using a specific page template.
	 *
	 * @param string|null $template Template file path or regex pattern.
	 *                              If null, returns true if any custom template is used.
	 *                              If starts with '/', treated as regex pattern.
	 *
	 * @return bool
	 */
	public static function isPageTemplate( ?string $template = null ): bool {
		$templateSlug = get_page_template_slug();
		if ( ! $templateSlug ) {
			return false;
		}

		// No template specified - just check if any custom template is used
		if ( $template === null ) {
			return true;
		}

		// If template starts with '/', treat as regex pattern
		if ( str_starts_with( $template, '/' ) ) {
			return (bool) preg_match( $template, $templateSlug );
		}

		// Exact match
		return $templateSlug === $template;
	}

	// -------------------------------------------------------------

	/**
	 * Check if on login page.
	 *
	 * @return bool
	 */
	public static function isLogin(): bool {
		$pagenow = $GLOBALS['pagenow'] ?? null;

		return $pagenow && in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ], true );
	}

	// -------------------------------------------------------------

	/**
	 * Ensure plugin functions are loaded.
	 *
	 * @return void
	 */
	private static function ensurePluginFunctions(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	// -------------------------------------------------------------

	/**
	 * Check if a plugin is active.
	 *
	 * @param string $pluginFile Plugin file path.
	 *
	 * @return bool
	 */
	public static function checkPluginActive( string $pluginFile ): bool {
		// Ensure plugin functions are loaded first
		self::ensurePluginFunctions();

		if ( is_multisite() && is_plugin_active_for_network( $pluginFile ) ) {
			return true;
		}

		return is_plugin_active( $pluginFile );
	}

	// -------------------------------------------------------------

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function isWoocommerceActive(): bool {
		static $active = null;

		return $active ??= function_exists( 'WC' )
				|| class_exists( 'WooCommerce' )
				|| self::checkPluginActive( 'woocommerce/woocommerce.php' );
	}

	// -------------------------------------------------------------

	/**
	 * Check if ACF is active.
	 *
	 * @return bool
	 */
	public static function isAcfActive(): bool {
		static $active = null;

		return $active ??= function_exists( 'acf' )
				|| class_exists( 'ACF' )
				|| self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' )
				|| self::checkPluginActive( 'advanced-custom-fields/acf.php' );
	}

	// -------------------------------------------------------------

	/**
	 * Check if ACF Pro is active.
	 *
	 * @return bool
	 */
	public static function isAcfProActive(): bool {
		static $active = null;

		return $active ??= defined( 'ACF_PRO' )
				|| class_exists( 'acf_pro' )
				|| self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isRankMathActive(): bool {
		static $active = null;

		return $active ??= class_exists( 'RankMath' )
				|| self::checkPluginActive( 'seo-by-rank-math/rank-math.php' );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isCf7Active(): bool {
		static $active = null;

		return $active ??= defined( 'WPCF7_PLUGIN_BASENAME' )
				|| class_exists( 'WPCF7' )
				|| self::checkPluginActive( 'contact-form-7/wp-contact-form-7.php' );
	}

	// -------------------------------------------------------------
}
