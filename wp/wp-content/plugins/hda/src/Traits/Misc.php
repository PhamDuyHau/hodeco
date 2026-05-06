<?php
/**
 * Miscellaneous utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Misc {
	// --------------------------------------------------

	/**
	 * Check if in development mode.
	 *
	 * @return bool
	 */
	public static function development(): bool {
		return wp_get_environment_type() === 'development'
				|| ( defined( 'WP_DEBUG' ) && \WP_DEBUG === true );
	}

	// --------------------------------------------------

	/**
	 * Get version string (time-based in dev mode).
	 *
	 * @return string|null
	 */
	public static function version(): ?string {
		$isDev = self::development() || ( defined( 'FORCE_VERSION' ) && \FORCE_VERSION === true );

		return $isDev ? (string) time() : null;
	}

	// --------------------------------------------------

	/**
	 * Throttled error logging.
	 *
	 * Logs errors in all environments, but throttles duplicate messages
	 * via object cache to prevent log flooding.
	 *
	 * @param string $message
	 * @param int $type
	 * @param string|null $dest
	 * @param string|null $headers
	 */
	public static function errorLog( string $message, int $type = 0, ?string $dest = null, ?string $headers = null ): void {
		$key = 'hda_err_' . md5( $message );

		if ( ! wp_cache_get( $key, 'hda_error_log_cache' ) ) {
			wp_cache_set( $key, 1, 'hda_error_log_cache', MINUTE_IN_SECONDS );
			error_log( $message, $type, $dest, $headers );
		}
	}

	// --------------------------------------------------

	/**
	 * Remove version query string from asset URLs.
	 * Single source of truth — used by both Security and Performance modules.
	 *
	 * @param string $src Asset source URL.
	 *
	 * @return string URL without version query string.
	 */
	public static function removeVersionQuery( string $src ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}

	// --------------------------------------------------

	/**
	 * Generate CSRF token field.
	 *
	 * @param string|int $action
	 * @param string $name
	 * @param bool $referer
	 * @param bool $display
	 *
	 * @return string|null
	 */
	public static function CSRFToken( string|int $action = -1, string $name = '_csrf_token', bool $referer = false, bool $display = false ): ?string {
		$name       = esc_attr( $name );
		$token      = wp_create_nonce( $action );
		$nonceField = '<input type="hidden" id="' . wp_generate_password( 10, false ) . '" name="' . $name . '" value="' . esc_attr( $token ) . '" />';

		if ( $referer ) {
			$nonceField .= wp_referer_field( false );
		}

		if ( $display ) {
			echo $nonceField;

			return null;
		}

		return $nonceField;
	}

	// --------------------------------------------------

	/**
	 * Execute shortcode directly.
	 *
	 * @param string $tag
	 * @param array $atts
	 * @param string|null $content
	 *
	 * @return mixed
	 */
	public static function doShortcode( string $tag, array $atts = [], ?string $content = null ): mixed {
		global $shortcode_tags;

		if ( ! isset( $shortcode_tags[ $tag ] ) ) {
			return false;
		}

		try {
			return call_user_func( $shortcode_tags[ $tag ], $atts, $content, $tag );
		} catch ( \Throwable $e ) {
			self::errorLog( '[Shortcode error] ' . $e->getMessage() );

			return false;
		}
	}

	// --------------------------------------------------

	/**
	 * Redirect with fallback for headers sent.
	 *
	 * @param string $uri
	 * @param int $status
	 *
	 * @return bool
	 */
	public static function redirect( string $uri = '', int $status = 301 ): bool {
		$uri = esc_url_raw( $uri );
		if ( ! $uri ) {
			return false;
		}

		if ( ! headers_sent() ) {
			wp_safe_redirect( $uri, $status );
			exit;
		}

		printf( '<script>window.location.href=%s;</script>', wp_json_encode( $uri ) );
		printf( '<noscript><meta http-equiv="refresh" content="0;url=%s" /></noscript>', esc_attr( $uri ) );

		return true;
	}

	// --------------------------------------------------

	/**
	 * Get current page URL (canonical, without pagination params).
	 *
	 * @param bool $stripPagination Whether to strip pagination query params.
	 *
	 * @return string
	 */
	public static function getCurrentUrl( bool $stripPagination = false ): string {
		if ( ! function_exists( 'home_url' ) ) {
			return '';
		}

		if ( ! $stripPagination ) {
			return home_url( add_query_arg( null, null ) );
		}

		global $wp;

		$baseUrl     = home_url( $wp->request ?? '' );
		$queryParams = array_filter(
			array_map(
				static fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : null,
				$_GET
			),
			static fn( $v ) => $v !== null
		);

		unset( $queryParams['paged'], $queryParams['page'], $queryParams['pg'] );

		if ( ! empty( $queryParams ) ) {
			return add_query_arg( $queryParams, trailingslashit( $baseUrl ) );
		}

		return trailingslashit( $baseUrl );
	}

	// --------------------------------------------------

	/**
	 * Check if value is in array and output checked attribute.
	 *
	 * @param array $checkedArr
	 * @param mixed $current
	 * @param bool $display
	 * @param string $type
	 *
	 * @return string|null
	 */
	public static function inArrayChecked( array $checkedArr, mixed $current, bool $display = true, string $type = 'checked' ): ?string {
		$type   = preg_match( '/^[a-zA-Z0-9\-]+$/', $type ) ? $type : 'checked';
		$result = in_array( $current, $checkedArr, true ) ? " {$type}='{$type}'" : '';

		if ( $display ) {
			echo $result;

			return null;
		}

		return $result;
	}

	// --------------------------------------------------

	/**
	 * Convert size string to MB.
	 *
	 * @param string $size
	 *
	 * @return int
	 */
	public static function convertToMB( string $size ): int {
		$multipliers = [
			'M' => 1,
			'G' => 1024,
			'T' => 1024 * 1024,
		];
		$size        = strtoupper( trim( $size ) );

		if ( preg_match( '/^(\d+(?:\.\d+)?)(M|MB|G|GB|T|TB)?$/', $size, $m ) ) {
			$value = (float) $m[1];
			$unit  = rtrim( $m[2] ?? 'M', 'B' );

			return (int) round( $value * ( $multipliers[ $unit ] ?? 1 ) );
		}

		return 0;
	}

	// --------------------------------------------------

	/**
	 * Validate URL.
	 *
	 * @param string|null $url
	 *
	 * @return bool
	 */
	public static function isUrl( ?string $url ): bool {
		if ( ! $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		return (bool) filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
	}

	// --------------------------------------------------

	/**
	 * Check if Lighthouse/PageSpeed is running.
	 *
	 * @return bool
	 */
	public static function lightHouse(): bool {
		$ua = strtolower( $_SERVER['HTTP_USER_AGENT'] ?? '' );

		// Use single regex instead of loop for better performance
		return (bool) preg_match( '/lighthouse|headlesschrome|chrome-lighthouse|pagespeed/', $ua );
	}

	// --------------------------------------------------

	/**
	 * Send toast success JSON response.
	 *
	 * @param string $msg
	 * @param bool $autoHide
	 */
	public static function toastSuccess( string $msg = '', bool $autoHide = true ): void {
		$text = $msg ?: esc_html__( 'Values saved', HDA_TEXTDOMAIN );

		wp_send_json_success(
			[
				'type'     => 'success',
				'message'  => $text,
				'autoHide' => $autoHide,
			]
		);
	}

	// --------------------------------------------------

	/**
	 * Send toast error JSON response.
	 *
	 * @param string $msg
	 * @param bool $autoHide
	 */
	public static function toastError( string $msg = '', bool $autoHide = false ): void {
		$text = $msg ?: esc_html__( 'An error occurred', HDA_TEXTDOMAIN );

		wp_send_json_error(
			[
				'type'     => 'error',
				'message'  => $text,
				'autoHide' => $autoHide,
			]
		);
	}
}
