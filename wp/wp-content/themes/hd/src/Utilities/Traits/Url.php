<?php
/**
 * URL utility trait.
 *
 * Provides URL manipulation, IP address, and redirect utilities.
 *
 * @package HD\Utilities\Traits
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Url {
	// --------------------------------------------------

	/**
	 * @param string $uri
	 * @param bool $permanent
	 *
	 * @return void
	 */
	public static function redirect( string $uri = '', bool $permanent = true ): void {
		$uri = esc_url_raw( $uri );
		if ( ! $uri ) {
			return;
		}

		$status = $permanent ? 301 : 302;

		if ( ! headers_sent() && ! wp_doing_ajax() && ! wp_is_json_request() ) {
			wp_safe_redirect( $uri, $status );
			exit;
		}

		// Fallback for already sent headers
		if ( ! wp_doing_ajax() && ! wp_is_json_request() ) {
			echo '<script>window.location.href="' . esc_js( $uri ) . '";</script>';
			echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $uri ) . '" /></noscript>';
		}
	}

	// --------------------------------------------------

	/**
	 * Get the real client IP address.
	 *
	 * Checks common proxy headers in priority order.
	 * For advanced proxy trust validation, use the 'hd_client_ip_filter' filter.
	 *
	 * @return string Client IP address
	 */
	public static function ipAddress(): string {
		$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

		if ( ! filter_var( $remoteAddr, FILTER_VALIDATE_IP ) ) {
			return '0.0.0.0';
		}

		// Headers to check in priority order
		$headers = [
			'HTTP_CF_CONNECTING_IP', // CloudFlare
			'HTTP_X_FORWARDED_FOR',  // Standard proxy header
			'HTTP_X_REAL_IP',        // Nginx
			'HTTP_CLIENT_IP',        // Some proxies
		];

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			// X-Forwarded-For can contain multiple IPs: client, proxy1, proxy2
			// The first (leftmost) is the original client.
			$ip = trim( explode( ',', $_SERVER[ $header ] )[0] );

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return apply_filters( 'hd_client_ip_filter', $ip, $remoteAddr );
			}
		}

		return apply_filters( 'hd_client_ip_filter', $remoteAddr, $remoteAddr );
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param string|null $scheme
	 *
	 * @return string
	 */
	public static function home( string $path = '', ?string $scheme = null ): string {
		return apply_filters( 'hd_home_url_filter', esc_url( home_url( $path, $scheme ) ), $path );
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param string|null $scheme
	 *
	 * @return string
	 */
	public static function siteURL( string $path = '', ?string $scheme = null ): string {
		return apply_filters( 'hd_site_url_filter', esc_url( site_url( $path, $scheme ) ), $path );
	}

	// --------------------------------------------------

	/**
	 * @param bool $nopaging
	 * @param bool $getVars
	 *
	 * @return string
	 */
	public static function current( bool $nopaging = true, bool $getVars = true ): string {
		global $wp;

		$currentUrl = self::home( $wp->request );

		// Remove pagination segment (e.g., /page/2/) from URL.
		if ( $nopaging ) {
			$currentUrl = preg_replace( '#/page/\d+/?$#', '/', $currentUrl );
		}

		if ( $getVars ) {
			$queryString = http_build_query( array_map( 'sanitize_text_field', wp_unslash( $_GET ) ) );

			if ( $queryString ) {
				$currentUrl .= ( str_contains( $currentUrl, '?' ) ? '&' : '?' ) . $queryString;
			}
		}

		return $currentUrl;
	}
}
