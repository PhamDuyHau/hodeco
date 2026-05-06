<?php
/**
 * Custom Login Url
 *
 * @author HD
 */

namespace HDAddons\LoginSecurity;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

class LoginUrl {
	/* ---------- CONFIG -------------------------------------------------- */

	private const string CLU_TOKEN = '_nonce';
	private const string TOKEN_PREFIX = 'hda_clu_';
	private const int TOKEN_TTL = 600; // 10 minutes
	private const int TOKEN_LENGTH = 48; // 48 bytes = 96 hex chars

	private array $options = [];

	/* ---------- CONSTRUCT ----------------------------------------------- */

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		add_action( 'plugins_loaded', $this->handleRequest( ... ), 1000 );
		add_action( 'wp_authenticate_user', $this->maybeBlockCustomLogin( ... ), 10, 1 );
		add_filter( 'wp_logout', $this->logout( ... ) );
		add_filter( 'logout_redirect', $this->logoutRedirect( ... ), 10, 3 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * Handle user logout.
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function logout( int $user_id ): void {
		if ( $this->_isValidCookie( 'login' ) ) {
			$this->_removeCookie( 'login' );

			return;
		}

		// Redirect to the homepage.
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/**
	 * @param $redirect_to
	 * @param $requested_redirect_to
	 * @param $user
	 *
	 * @return string
	 */
	public function logoutRedirect( $redirect_to, $requested_redirect_to, $user ): string {
		return add_query_arg( self::CLU_TOKEN, rawurlencode( $this->_generateSecureToken() ), $redirect_to );
	}

	/**
	 * Handle request paths.
	 *
	 * @return void
	 */
	public function handleRequest(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = $this->_relativePath( $request_uri, false );

		if ( $path === $this->options['new_slug'] ) {
			$this->_redirectToken( 'login', 'wp-login.php' );
		}

		if ( str_contains( $path, 'wp-login' ) || str_contains( $path, 'wp-login.php' ) ) {
			$this->_handleLogin();
		}

		if ( $path === $this->options['register'] ) {
			$this->_handleRegistration();
		}
	}

	/**
	 * Block administrators from logging-in through third-party login forms when `Custom Login URL` is enabled.
	 *
	 * @param \WP_User $user
	 *
	 * @return \WP_Error|\WP_User
	 */
	public function maybeBlockCustomLogin( \WP_User $user ): \WP_Error|\WP_User {
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return $user;
		}

		$error = new \WP_Error(
			'authentication_failed',
			__( '<strong>Error</strong>: Invalid login credentials.', HDA_TEXTDOMAIN )
		);

		// Extract query string directly from referer URL.
		$http_referer  = ! empty( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$referer_parts = wp_parse_url( $http_referer );

		if ( empty( $referer_parts['query'] ) ) {
			return $error;
		}

		parse_str( $referer_parts['query'], $referer_query );
		$token = ! empty( $referer_query[ self::CLU_TOKEN ] ) ? sanitize_text_field( $referer_query[ self::CLU_TOKEN ] ) : '';

		if ( ! empty( $token ) && $this->_validateToken( rawurldecode( $token ) ) ) {
			return $user;
		}

		return $error;
	}

	/**
	 * Block a request to the page.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function block( string $type = 'login' ): void {
		if ( is_user_logged_in() || $this->_isValidCookie( $type ) ) {
			return;
		}

		// Allow other modules (e.g., Magic Link) to bypass the block
		// when they have their own valid authentication token.
		if ( apply_filters( 'hda_login_url_allow_access', false, $type ) ) {
			return;
		}

		// Die if there is `redirect` page.
		if ( empty( $this->options['redirect'] ) ) {
			wp_die(
				esc_html__( 'This feature has been disabled.', HDA_TEXTDOMAIN ),
				esc_html__( 'Restricted access', HDA_TEXTDOMAIN ),
				[
					'hda_error' => true,
					'response'  => 403,
				]
			);
		}

		// Redirect to configured page (may be external, use wp_redirect).
		wp_safe_redirect( esc_url_raw( $this->options['redirect'] ), 302 );
		exit;
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Handle login.
	 *
	 * @return void
	 */
	private function _handleLogin(): void {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

		if ( in_array( $action, [ 'rp', 'resetpass', 'postpass', 'lostpassword' ] ) ) {
			return;
		}

		if ( 'register' === $action ) {
			if ( 'wp-signup.php' !== $this->options['register'] ) {
				$this->block( 'register' );
			}

			return;
		}

		// Jetpack
		if (
			'jetpack_json_api_authorization' === $action &&
			has_filter( 'login_form_jetpack_json_api_authorization' )
		) {
			return;
		}

		// Jetpack SSO
		if (
			'jetpack-sso' === $action &&
			has_filter( 'login_form_jetpack-sso' )
		) {
			add_action( 'login_form_jetpack-sso', $this->block( ... ) );

			return;
		}

		$this->block( 'login' );
	}

	/**
	 * Handle registration request.
	 *
	 * @return void
	 */
	private function _handleRegistration(): void {
		if (
			1 !== (int) Helper::getOption( 'users_can_register', 0 ) ||
			empty( Helper::getOption( 'users_can_register' ) )
		) {
			return;
		}

		$this->_setPermissionsCookie( 'login' );

		if ( is_multisite() ) {
			$this->_redirectToken( 'register', 'wp-signup.php' );
		}

		$this->_redirectToken( 'register', 'wp-login.php?action=register' );
	}

	/**
	 * Adds a token and redirect to the url.
	 *
	 * @param $type
	 * @param $path
	 *
	 * @return void
	 */
	private function _redirectToken( $type, $path ): void {
		$this->_setPermissionsCookie( $type );

		// Preserve existing query vars and add access token query arg.
		$query_vars                    = array_map( 'sanitize_text_field', array_filter( $_GET, 'is_string' ) );
		$query_vars[ self::CLU_TOKEN ] = rawurlencode( $this->_generateSecureToken() );

		$url = add_query_arg( $query_vars, rtrim( $this->_siteUrl( $path ), '/' ) );

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Generate a cryptographically secure random token.
	 * Token is stored in transient with short TTL for one-time use.
	 *
	 * @return string 64-character hex token
	 */
	private function _generateSecureToken(): string {
		try {
			$token = bin2hex( random_bytes( self::TOKEN_LENGTH ) );
		} catch ( \Exception $e ) {
			// Fallback for older systems (should never happen in PHP 7+)
			$token = wp_generate_password( self::TOKEN_LENGTH * 2, false );
		}

		// Create a hash of the token to use as transient key (don't store raw token)
		$tokenHash = $this->_hashToken( $token );

		// Store token hash in transient with short TTL
		set_transient(
			self::TOKEN_PREFIX . $tokenHash,
			[
				'slug'    => $this->options['new_slug'],
				'created' => time(),
				'ip'      => Helper::ipAddress(),
			],
			self::TOKEN_TTL
		);

		return $token;
	}

	/**
	 * Validate and consume a token (one-time use).
	 *
	 * @param string $token The token to validate.
	 *
	 * @return bool True if token is valid, false otherwise.
	 */
	private function _validateToken( string $token ): bool {
		if ( empty( $token ) || strlen( $token ) !== self::TOKEN_LENGTH * 2 ) {
			return false;
		}

		$tokenHash     = $this->_hashToken( $token );
		$transientKey  = self::TOKEN_PREFIX . $tokenHash;
		$transientData = get_transient( $transientKey );

		if ( false === $transientData ) {
			return false;
		}

		// Validate token data
		if (
			! isset( $transientData['slug'] ) ||
			$transientData['slug'] !== $this->options['new_slug']
		) {
			return false;
		}

		// Validate IP if strict mode is enabled
		if (
			! empty( $this->options['ip_check'] ) &&
			isset( $transientData['ip'] ) &&
			$transientData['ip'] !== Helper::ipAddress()
		) {
			return false;
		}

		// Delete transient to ensure one-time use
		delete_transient( $transientKey );

		return true;
	}

	/**
	 * Create a secure hash of the token for storage.
	 *
	 * @param string $token Raw token.
	 *
	 * @return string Hashed token.
	 */
	private function _hashToken( string $token ): string {
		return hash_hmac( 'sha256', $token, AUTH_SALT . SECURE_AUTH_SALT );
	}

	/**
	 * Set a cookie which will be used to check if the user has permissions to view a page.
	 *
	 * @param string $type
	 */
	private function _setPermissionsCookie( string $type = '' ): void {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = trailingslashit( $url_parts['path'] );

		if ( ! empty( $type ) ) {
			setcookie(
				self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH,
				$this->options['new_slug'],
				[
					'expires'  => time() + 3600,
					'path'     => $home_path,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
		}
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	private function _removeCookie( string $type = 'login' ): void {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = trailingslashit( $url_parts['path'] );

		setcookie(
			self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH,
			'',
			[
				'expires'  => time() - 3600,
				'path'     => $home_path,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Checks if the user has permissions to view a page.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	private function _isValidCookie( string $type ): bool {
		$cookie = self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH;

		// Check if the validation cookie is set.
		if ( isset( $_COOKIE[ $cookie ] ) && $_COOKIE[ $cookie ] === $this->options['new_slug'] ) {
			return true;
		}

		// Check if the token value is set and valid.
		if (
			isset( $_REQUEST[ self::CLU_TOKEN ] ) &&
			$this->_validateToken( rawurldecode( sanitize_text_field( wp_unslash( $_REQUEST[ self::CLU_TOKEN ] ) ) ) )
		) {
			// Add the permissions' cookie.
			$this->_setPermissionsCookie( $type );

			return true;
		}

		return false;
	}

	/**
	 * Get the path without a home URL path.
	 *
	 * @param string $url
	 * @param bool $queryString
	 *
	 * @return string The URL path.
	 */
	private function _relativePath( string $url, bool $queryString = false ): string {
		$url_parts = wp_parse_url( $this->_homeUrl() );
		$home_path = ! empty( $url_parts['path'] ) ? trailingslashit( $url_parts['path'] ) : '/';

		$_temp_url = explode( '?', wp_make_link_relative( $url ) );
		$path      = wp_parse_url( $_temp_url[0], PHP_URL_PATH );

		if ( $queryString && ! empty( $_temp_url[1] ) ) {
			$path .= '?' . $_temp_url[1];
		}

		return $path ? trim( str_replace( $home_path, '', $path ), '/' ) : '';
	}

	/**
	 * Get site URL with proper scheme.
	 *
	 * @param string $path Optional path to append.
	 *
	 * @return string The site URL.
	 */
	private function _siteUrl( string $path = '' ): string {
		$url       = Helper::getOption( 'siteurl' );
		$urlParsed = wp_parse_url( $url );
		$scheme    = is_ssl() ? 'https' : ( $urlParsed['scheme'] ?? 'http' );
		$url       = set_url_scheme( $url, $scheme );

		if ( $path ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return trailingslashit( $url );
	}

	/**
	 * Get home URL with proper scheme.
	 *
	 * @param string $path Optional path to append.
	 *
	 * @return string The home URL.
	 */
	private function _homeUrl( string $path = '' ): string {
		$url       = Helper::getOption( 'home' );
		$urlParsed = wp_parse_url( $url );
		$scheme    = is_ssl() ? 'https' : ( $urlParsed['scheme'] ?? 'http' );
		$url       = set_url_scheme( $url, $scheme );

		if ( $path ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return trailingslashit( $url );
	}

	/**
	 * True if plugin enabled via option.
	 *
	 * @return bool
	 */
	private function _isEnabled(): bool {
		// Emergency bypass via wp-config.php or .env
		if ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY ) {
			return false;
		}

		$opt              = LoginSecurity::getOptions();
		$custom_login_uri = ! empty( $opt[ LoginSecurity::KEY_CUSTOM_LOGIN_URI ] ) ? $opt[ LoginSecurity::KEY_CUSTOM_LOGIN_URI ] : 'wp-login.php';

		// Set the required options.
		$this->options = [
			'new_slug' => $custom_login_uri,
			'redirect' => apply_filters( 'clu_login_redirect', $this->_homeUrl() ),
			'register' => apply_filters( 'clu_login_register', 'register' ),
			'ip_check' => ! empty( $opt[ LoginSecurity::KEY_LOGIN_TOKEN_IP_CHECK ] ),
		];

		if ( empty( $custom_login_uri ) || in_array( $custom_login_uri, [ 'wp-login.php', 'wp-admin' ] ) ) {
			return false;
		}

		// Warn about conflicting plugins (but don't disable — that would be a security hole).
		if ( is_admin() ) {
			$this->_warnConflictingPlugins();
		}

		return true;
	}

	/**
	 * Show admin notice if a conflicting plugin is active.
	 * Does NOT disable the feature — only warns the admin.
	 *
	 * @return void
	 */
	private function _warnConflictingPlugins(): void {
		$conflicting_plugins = [
			'wps-hide-login/wps-hide-login.php'        => 'WPS Hide Login',
			'perfmatters/perfmatters.php'               => 'Perfmatters',
			'loginizer/loginizer.php'                   => 'Loginizer',
			'better-wp-security/better-wp-security.php' => 'Solid Security',
			'hide-my-wp/index.php'                      => 'Hide My WP Ghost',
			'wp-simple-firewall/icwp-wpsf.php'          => 'Shield Security',
		];

		$active = [];
		foreach ( $conflicting_plugins as $plugin => $name ) {
			if ( Helper::checkPluginActive( $plugin ) ) {
				$active[] = $name;
			}
		}

		if ( empty( $active ) ) {
			return;
		}

		add_action( 'admin_notices', static function () use ( $active ) {
			printf(
				'<div class="notice notice-warning"><p><strong>HDA:</strong> %s</p></div>',
				sprintf(
					/* translators: %s: list of conflicting plugin names */
					esc_html__( 'The following plugin(s) may conflict with the Custom Login URL feature: %s. Please deactivate them to avoid unexpected behavior.', HDA_TEXTDOMAIN ),
					'<strong>' . esc_html( implode( ', ', $active ) ) . '</strong>'
				)
			);
		} );
	}
}
