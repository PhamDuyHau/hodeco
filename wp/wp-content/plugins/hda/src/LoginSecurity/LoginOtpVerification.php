<?php
/**
 * Email/SMS OTP Login Verification
 *
 * @author HD
 */

namespace HDAddons\LoginSecurity;

use HDAddons\Asset;
use HDAddons\Helper;
use HDAddons\LoginSecurity\ActivityLog\ActivityLog;
use HDAddons\LoginSecurity\Gateway\GatewayFactory;
use HDAddons\LoginSecurity\Gateway\GatewayInterface;
use HDAddons\LoginSecurity\Totp\TotpHandler;
use Random\RandomException;

\defined( 'ABSPATH' ) || exit;

class LoginOtpVerification {
	/* ---------- TRANSIENT & META KEYS ----------------------------------- */

	private const string KEY_OTP = 'loginotp_%d';     // hash (OTP)
	private const string KEY_ATTEMPT = 'loginotp_try_%d'; // int
	private const string META_LASTSEND = '_otp_last_send';  // timestamp
	private const string META_TOKEN = '_otp_dnc_token';  // random

	/* ---------- CONFIG -------------------------------------------------- */

	public const int OTP_DIGITS = 6;
	public const int|float OTP_LIFETIME = 5 * MINUTE_IN_SECONDS; // 5 minutes (transient and form)
	public const int|float RESEND_INTERVAL = 5 * MINUTE_IN_SECONDS; // 5 minutes (cool-down email)
	public const int|float COOKIE_LIFETIME = DAY_IN_SECONDS; // 1 day
	public const int MAX_ATTEMPTS = 5;
	public const int TOKEN_LENGTH = 32; // 32 bytes = 64 hex chars
	public const string ACTION_VALIDATE = '_otp_validate';

	/**
	 * Re-entry guard: prevents initOtp() from re-triggering
	 * after _loginUser() fires wp_login.
	 */
	private static bool $otpVerified = false;

	/**
	 * Cached gateway instance for the current request.
	 * Avoids creating multiple gateway objects per login attempt.
	 *
	 * @var GatewayInterface|null|false null=not resolved, false=unavailable
	 */
	private GatewayInterface|null|false $cachedGateway = null;

	/* ---------- UID SIGNING --------------------------------------------- */

	/**
	 * Sign a user ID with HMAC to prevent form tampering.
	 *
	 * @param int $userId User ID to sign.
	 *
	 * @return string Signed user ID in format "uid:signature".
	 */
	public static function signUid( int $userId ): string {
		$sig = hash_hmac( 'sha256', (string) $userId, AUTH_SALT . NONCE_SALT );

		return $userId . ':' . $sig;
	}

	/**
	 * Verify and extract user ID from a signed uid string.
	 *
	 * @param string $signedUid Signed uid in format "uid:signature".
	 *
	 * @return int|false User ID on success, false on failure.
	 */
	public static function verifySignedUid( string $signedUid ): int|false {
		if ( ! str_contains( $signedUid, ':' ) ) {
			return false;
		}

		[ $uidStr, $sig ] = explode( ':', $signedUid, 2 );

		$uid         = absint( $uidStr );
		$expectedSig = hash_hmac( 'sha256', (string) $uid, AUTH_SALT . NONCE_SALT );

		if ( ! hash_equals( $expectedSig, $sig ) ) {
			return false;
		}

		return $uid;
	}

	/* ---------- LIFECYCLE ----------------------------------------------- */

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		add_action( 'login_enqueue_scripts', $this->enqueueAssets( ... ), 32 );

		// login / logout
		add_action( 'wp_login', $this->initOtp( ... ), 10, 2 ); // Fires after successful login
		add_action( 'wp_logout', $this->cleanupOtpOnLogout( ... ), 10, 1 );
		add_action( 'clear_auth_cookie', $this->cleanupOtpOnLogout( ... ), 10, 0 );

		// form + message
		add_filter( 'login_message', $this->otpFailMessage( ... ) );
		add_action( 'login_form_' . self::ACTION_VALIDATE, $this->validateOtpLogin( ... ) );
	}

	/* ---------- PUBLIC HOOKS -------------------------------------------- */

	/**
	 * Enqueue JS on OTP login page
	 *
	 * @return void
	 */
	public function enqueueAssets(): void {
		if ( $this->_isEnabled() ) {
			Asset::enqueueJS(
				'otp-login.js',
				[ Asset::handle( 'login.js' ) ],
				null,
				true,
				[ 'module', 'defer' ]
			);
		}
	}

	/**
	 * Sends OTP, if not yet verified, fires after the user has successfully logged in.
	 *
	 * @param string $user_login Username.
	 * @param \WP_User $user WP_User object of the logged-in user.
	 *
	 * @return void
	 * @throws RandomException
	 */
	public function initOtp( string $user_login, \WP_User $user ): void {
		// Guard: skip if OTP was just verified (prevents re-entry from _loginUser)
		if ( self::$otpVerified ) {
			return;
		}

		// Only roles configured to use OTP
		if ( empty( array_intersect( $this->_otpUserRoles(), $user->roles ) ) ) {
			return;
		}

		// Already has valid OTP cookie → skip
		if ( $this->_checkOtpCookie( $user ) ) {
			return;
		}

		// Remove auth-cookie to pause the session
		wp_clear_auth_cookie();

		$mode = $this->_getOtpMode();

		// ── TOTP mode ──────────────────────────────────────
		if ( $mode === 'totp' ) {
			// User has completed TOTP setup → show TOTP login form
			if ( TotpHandler::isUserSetup( $user->ID ) ) {
				$this->_loadForm(
					[
						'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
						'template' => 'Totp/totp-login.php',
						'uid'      => self::signUid( $user->ID ),
						'error'    => '',
					]
				);

				return; // _loadForm exits, but return for clarity.
			}

			// TOTP not setup → fallback to email OTP silently
			Helper::errorLog( 'HDA OTP: TOTP not setup for user #' . $user->ID . ', falling back to email.' );
		}

		// ── Email / SMS mode (or TOTP fallback) ──────────
		$result = $this->_maybeSendOtp( $user );

		if ( $result === false ) {
			$this->_clearOtpData( $user->ID );
			$error_key = ( $mode === 'sms' ) ? 'sms' : 'email';
			wp_safe_redirect( add_query_arg( '_error', $error_key, wp_login_url() ) );
			exit;
		}

		// Show an OTP form — sign the uid to prevent form tampering
		$this->_loadForm(
			[
				'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
				'template'       => 'recovery-login.php',
				'uid'            => self::signUid( $user->ID ),
				'send_at'        => (int) get_user_meta( $user->ID, self::META_LASTSEND, true ),
				'error'          => '',
				'channel'        => $this->_getChannelLabel( $user ),
				'recipient_hint' => $this->_maskRecipient( $user ),
			]
		);
	}

	/**
	 * Remove all OTP artifacts when a user logs out (or cookie cleared)
	 *
	 * @param int $userId
	 *
	 * @return void
	 */
	public function cleanupOtpOnLogout( int $userId = 0 ): void {
		$userId = $userId ?: get_current_user_id();
		if ( $userId ) {
			$this->_clearOtpData( $userId );
		}
	}

	/**
	 * Handle OTP submit (`wp-login.php?action=_otp_validate`)
	 *
	 * @throws RandomException
	 */
	public function validateOtpLogin(): void {
		// Sanitize inputs
		$authcode   = isset( $_POST['authcode'] ) ? sanitize_text_field( wp_unslash( $_POST['authcode'] ) ) : '';
		$signedUid  = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( $_POST['uid'] ) ) : '';
		$csrf_token = isset( $_POST['_csrf_token'] ) ? sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ) ) : '';

		// Verify CSRF token first
		if ( empty( $csrf_token ) || ! wp_verify_nonce( $csrf_token, 'otp_csrf_token' ) ) {
			wp_safe_redirect( add_query_arg( '_error', 'invalid_request', wp_login_url() ) );
			exit;
		}

		// Verify signed uid — prevents tampering with the hidden uid field
		$uid = self::verifySignedUid( $signedUid );
		if ( false === $uid || $uid === 0 ) {
			wp_safe_redirect( add_query_arg( '_error', 'invalid_request', wp_login_url() ) );
			exit;
		}

		// Empty authcode - show form again with error (re-sign uid)
		if ( empty( $authcode ) ) {
			$user = get_user_by( 'id', $uid );
			$this->_loadForm(
				[
					'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
					'template'       => 'recovery-login.php',
					'uid'            => self::signUid( $uid ),
					'send_at'        => (int) get_user_meta( $uid, self::META_LASTSEND, true ),
					'error'          => __( 'Please enter the verification code.', HDA_TEXTDOMAIN ),
					'channel'        => $user ? $this->_getChannelLabel( $user ) : '',
					'recipient_hint' => $user ? $this->_maskRecipient( $user ) : '',
				]
			);

			return;
		}

		$userId  = $uid;
		$entered = preg_replace( '/\D/', '', $authcode );
		$user    = get_user_by( 'id', $userId );

		// ── TOTP validation ────────────────────────────────
		if ( $this->_getOtpMode() === 'totp' && TotpHandler::isUserSetup( $userId ) ) {
			// Brute-force protection (shared with email/sms)
			$attempts = (int) get_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

			$lastUsed  = TotpHandler::getLastUsed( $userId );
			$secret    = TotpHandler::getUserSecret( $userId );
			$timeSlice = TotpHandler::verify( $secret, $entered, TotpHandler::WINDOW, $lastUsed );

			if ( $timeSlice === false ) {
				++ $attempts;
				set_transient( sprintf( self::KEY_ATTEMPT, $userId ), $attempts, self::OTP_LIFETIME );

				// Log failed TOTP attempt
				ActivityLog::logEvent( $userId, $user ? $user->user_login : '', 'otp_failed' );

				if ( $attempts >= self::MAX_ATTEMPTS ) {
					delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );
					wp_safe_redirect( add_query_arg( '_error', 'max_attempts', wp_login_url() ) );
					exit;
				}

				$this->_loadForm(
					[
						'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
						'template' => 'Totp/totp-login.php',
						'uid'      => self::signUid( $userId ),
						'error'    => sprintf( __( 'Invalid code. You have %1$d of %2$d attempts left.', HDA_TEXTDOMAIN ), self::MAX_ATTEMPTS - $attempts, self::MAX_ATTEMPTS ),
					]
				);

				return;
			}

			// TOTP success — update replay protection
			TotpHandler::setLastUsed( $userId, $timeSlice );
			delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

			$rememberme = ! empty( $_POST['rememberme'] );
			$this->_loginUser( $userId, $rememberme );
			$this->_interimCheck();

			$redirect = ! empty( $_POST['redirect_to'] ) ? sanitize_url( wp_unslash( $_POST['redirect_to'] ) ) : get_admin_url();
			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit;
		}

		// ── Email / SMS validation ────────────────────────
		// Transient data
		$hash     = get_transient( sprintf( self::KEY_OTP, $userId ) );
		$attempts = (int) get_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

		if ( false === $hash ) {
			$this->_loadForm(
				[
					'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
					'template'       => 'recovery-login.php',
					'uid'            => self::signUid( $userId ),
					'send_at'        => (int) get_user_meta( $userId, self::META_LASTSEND, true ),
					'error'          => __( 'Verification code expired - please request a new code.', HDA_TEXTDOMAIN ),
					'channel'        => $user ? $this->_getChannelLabel( $user ) : '',
					'recipient_hint' => $user ? $this->_maskRecipient( $user ) : '',
				]
			);

			return;
		}

		// Compare using secure hash
		if ( ! hash_equals( $hash, $this->_hashOtp( $entered ) ) ) {

			// +1 failed attempt
			++ $attempts;
			set_transient( sprintf( self::KEY_ATTEMPT, $userId ), $attempts, self::OTP_LIFETIME );

			// Too many attempts?
			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->_clearOtpData( $userId );
				wp_safe_redirect( add_query_arg( '_error', 'max_attempts', wp_login_url() ) );
				exit;
			}

			$this->_loadForm(
				[
					'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
					'template'       => 'recovery-login.php',
					'uid'            => self::signUid( $userId ),
					'send_at'        => (int) get_user_meta( $userId, self::META_LASTSEND, true ),
					'error'          => sprintf( __( 'Invalid code. You have %1$d of %2$d attempts left.', HDA_TEXTDOMAIN ), self::MAX_ATTEMPTS - $attempts, self::MAX_ATTEMPTS ),
					'channel'        => $user ? $this->_getChannelLabel( $user ) : '',
					'recipient_hint' => $user ? $this->_maskRecipient( $user ) : '',
				]
			);

			return;
		}

		// Success + log the user in again and redirect
		$rememberme = ! empty( $_POST['rememberme'] );
		$this->_loginUser( $userId, $rememberme );
		$this->_interimCheck();

		$redirect = ! empty( $_POST['redirect_to'] ) ? sanitize_url( wp_unslash( $_POST['redirect_to'] ) ) : get_admin_url();
		wp_safe_redirect( esc_url_raw( $redirect ) );
		exit;
	}

	/**
	 * Replace default login messages with OTP-specific errors
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	public function otpFailMessage( string $message ): string {
		$error = isset( $_GET['_error'] ) ? sanitize_key( $_GET['_error'] ) : '';
		if ( empty( $error ) ) {
			return $message;
		}

		return match ( $error ) {
			'email'           => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', HDA_TEXTDOMAIN ) . '</strong>: ' . esc_html__( 'Unable to send OTP e-mail.', HDA_TEXTDOMAIN ) . '</p></div>',
			'sms'             => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', HDA_TEXTDOMAIN ) . '</strong>: ' . esc_html__( 'Unable to send OTP via SMS/Messaging.', HDA_TEXTDOMAIN ) . '</p></div>',
			'max_attempts'    => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', HDA_TEXTDOMAIN ) . '</strong>: ' . esc_html__( 'Too many attempts.', HDA_TEXTDOMAIN ) . '</p></div>',
			'invalid_request' => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', HDA_TEXTDOMAIN ) . '</strong>: ' . esc_html__( 'Invalid request. Please try again.', HDA_TEXTDOMAIN ) . '</p></div>',
			default           => $message,
		};
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Log user in again & set OTP cookie
	 *
	 * @param int $userId
	 *
	 * @param bool $rememberme Whether to remember the user.
	 *
	 * @return void
	 * @throws RandomException
	 */
	private function _loginUser( int $userId = 0, bool $rememberme = false ): void {
		self::$otpVerified = true;

		wp_set_auth_cookie( $userId, $rememberme );
		wp_set_current_user( $userId );

		$this->_clearOtpData( $userId );
		$this->_setOtpCookie( $userId );

		// Fire wp_login so ActivityLog and other hooks can capture the event.
		// The $otpVerified flag prevents initOtp() from re-triggering.
		$user = get_user_by( 'id', $userId );
		if ( $user ) {
			/** This action is documented in wp-includes/user.php */
			do_action( 'wp_login', $user->user_login, $user );
		}
	}

	/**
	 * Show success page for interim-login iframe.
	 * WordPress uses interim-login for AJAX session refresh when session expires mid-work.
	 *
	 * @return void
	 */
	private function _interimCheck(): void {
		$interim_login = isset( $_REQUEST['interim-login'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['interim-login'] ) ) : '';

		// Only proceed if this is actually an interim login request
		if ( ! in_array( $interim_login, [ '1', 'success' ], true ) ) {
			return;
		}

		$GLOBALS['interim_login'] = 'success';

		login_header( '', '<p class="message">' . __( 'You have logged in successfully.', HDA_TEXTDOMAIN ) . '</p>' );
		echo '</div>';
		do_action( 'login_footer' );
		echo '</body></html>';
		exit;
	}

	/**
	 * Send an OTP using the configured mode (email or sms).
	 *
	 * @param \WP_User $user
	 *
	 * @return bool|null true=sent, false=error, null=cooldown active
	 * @throws RandomException
	 */
	private function _maybeSendOtp( \WP_User $user ): ?bool {
		// Check cooldown
		$last_sent = (int) get_user_meta( $user->ID, self::META_LASTSEND, true );
		if ( $last_sent && ( time() - $last_sent ) < self::RESEND_INTERVAL ) {
			return null;
		}

		// Generate OTP
		$otp = str_pad( (string) random_int( 0, ( 10 ** self::OTP_DIGITS ) - 1 ), self::OTP_DIGITS, '0', STR_PAD_LEFT );

		// Send via appropriate channel
		$mode = $this->_getOtpMode();
		$sent = ( $mode === 'sms' )
			? $this->_sendViaSms( $user, $otp )
			: $this->_sendViaEmail( $user, $otp );

		if ( ! $sent ) {
			return false;
		}

		// Success - store cooldown and transients
		update_user_meta( $user->ID, self::META_LASTSEND, time() );
		set_transient( sprintf( self::KEY_OTP, $user->ID ), $this->_hashOtp( $otp ), self::OTP_LIFETIME );
		set_transient( sprintf( self::KEY_ATTEMPT, $user->ID ), 0, self::OTP_LIFETIME );

		return true;
	}

	/**
	 * Send OTP via email.
	 *
	 * @param \WP_User $user
	 * @param string   $otp
	 *
	 * @return bool
	 */
	private function _sendViaEmail( \WP_User $user, string $otp ): bool {
		return wp_mail(
			$user->user_email,
			__( 'Your One-Time OTP', HDA_TEXTDOMAIN ),
			sprintf(
				__( "Hello %1\$s,\n\nYour OTP is: %2\$s\nThis code will expire in 5 minutes.\n\nIf you didn't request this login, please ignore this email.", HDA_TEXTDOMAIN ),
				$user->user_login,
				$otp
			)
		);
	}

	/**
	 * Send OTP via SMS gateway.
	 *
	 * @param \WP_User $user
	 * @param string   $otp
	 *
	 * @return bool
	 */
	private function _sendViaSms( \WP_User $user, string $otp ): bool {
		$gateway = $this->_getGateway();

		// Fallback to email if gateway not available or not configured
		if ( ! $gateway ) {
			Helper::errorLog( 'HDA OTP: Gateway not configured, falling back to email.' );

			return $this->_sendViaEmail( $user, $otp );
		}

		// Get recipient (phone, chat_id, etc.)
		$meta_key  = $gateway->getUserMetaKey();
		$recipient = get_user_meta( $user->ID, $meta_key, true );

		// Fallback to email if no recipient configured
		if ( empty( $recipient ) ) {
			return $this->_sendViaEmail( $user, $otp );
		}

		$sent = $gateway->send( $recipient, $otp );

		// Fallback to email if SMS sending failed
		if ( ! $sent ) {
			Helper::errorLog( 'HDA OTP Gateway Error: ' . $gateway->getLastError() . ' - falling back to email.' );

			return $this->_sendViaEmail( $user, $otp );
		}

		return true;
	}

	/**
	 * Display the OTP authentication forms.
	 *
	 * @param $args
	 *
	 * @return void
	 */
	private function _loadForm( $args ): void {
		if ( empty( $args['template'] ) ) {
			return;
		}

		// Path to the form template.
		$path = __DIR__ . '/' . $args['template'];
		if ( ! is_file( $path ) ) {
			return;
		}

		$args = array_merge(
			$args,
			[
				'otp_digits'      => self::OTP_DIGITS,
				'resend_interval' => self::RESEND_INTERVAL,
				'interim_login'   => ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false,
				'redirect_to'     => isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url(),
			]
		);

		// Include the login header if the function doesn't exist.
		if ( ! function_exists( 'login_header' ) ) {
			include_once ABSPATH . 'wp-login.php';
		}

		// Include the template.php if the function doesn't exist.
		if ( ! function_exists( 'submit_button' ) ) {
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		login_header();
		include_once $path;
		login_footer();
		exit;
	}

	/**
	 * Create a secure cookie for device-not-challenge
	 *
	 * @param int $userId
	 *
	 * @return void
	 * @throws RandomException
	 */
	private function _setOtpCookie( int $userId = 0 ): void {
		$token     = bin2hex( random_bytes( self::TOKEN_LENGTH ) );
		$ip        = Helper::ipAddress();
		$expiresAt = time() + self::COOKIE_LIFETIME;

		// Store token data with expiry for validation
		update_user_meta( $userId, self::META_TOKEN, [
			'token'      => $token,
			'ip'         => $ip,
			'expires_at' => $expiresAt,
		] );

		setcookie(
			'_otp_dnc_cookie',
			$userId . '|' . $token,
			[
				'expires'  => $expiresAt,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Validate OTP cookie for device-not-challenge.
	 *
	 * @param \WP_User $user The user to check cookie for.
	 *
	 * @return bool True if cookie is valid, false otherwise.
	 */
	private function _checkOtpCookie( \WP_User $user ): bool {
		if ( empty( $_COOKIE['_otp_dnc_cookie'] ) ) {
			return false;
		}

		$cookie = sanitize_text_field( wp_unslash( $_COOKIE['_otp_dnc_cookie'] ) );

		// Validate cookie format contains separator.
		if ( ! str_contains( $cookie, '|' ) ) {
			return false;
		}

		$parts = explode( '|', $cookie, 2 );

		// Validate we have exactly 2 parts.
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		[ $uid, $token ] = $parts;

		// Validate uid is numeric and token is not empty.
		if ( ! is_numeric( $uid ) || empty( $token ) ) {
			return false;
		}

		$storedData = get_user_meta( $user->ID, self::META_TOKEN, true );

		// Handle both old (string) and new (array) token formats
		if ( is_array( $storedData ) ) {
			$storedToken = $storedData['token'] ?? '';
			$storedIp    = $storedData['ip'] ?? '';
			$expiresAt   = $storedData['expires_at'] ?? 0;

			// Check token expiry
			if ( $expiresAt > 0 && time() > $expiresAt ) {
				$this->_clearOtpData( $user->ID );
				return false;
			}
		} else {
			// Backward compatibility: old format stored token as string
			$storedToken = $storedData;
			$storedIp    = get_user_meta( $user->ID, self::META_TOKEN . '_ip', true );
		}

		// Use hash_equals for timing-safe comparison.
		if (
			(int) $uid !== $user->ID ||
			empty( $storedToken ) ||
			! hash_equals( $storedToken, $token )
		) {
			return false;
		}

		// Check IP binding if enabled
		if ( ! empty( $storedIp ) && $this->_isIpBindingEnabled() && $storedIp !== Helper::ipAddress() ) {
			return false;
		}

		return true;
	}

	/**
	 * @param int $userId
	 *
	 * @return void
	 */
	private function _clearOtpData( int $userId = 0 ): void {
		delete_transient( sprintf( self::KEY_OTP, $userId ) );
		delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );
		delete_user_meta( $userId, self::META_LASTSEND );
		delete_user_meta( $userId, self::META_TOKEN );
		delete_user_meta( $userId, self::META_TOKEN . '_ip' ); // Legacy cleanup
	}

	/**
	 * True if OTP is enabled via option (email or sms mode)
	 *
	 * @return bool
	 */
	private function _isEnabled(): bool {
		// Emergency bypass via wp-config.php or .env
		// HDA_DISABLE_OTP=true or HDA_DISABLE_LOGIN_SECURITY=true
		if ( defined( 'HDA_DISABLE_OTP' ) && \HDA_DISABLE_OTP ) {
			return false;
		}

		if ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY ) {
			return false;
		}

		$opt = LoginSecurity::getOptions();

		return isset( $opt[ LoginSecurity::KEY_OTP_MODE ] ) && $opt[ LoginSecurity::KEY_OTP_MODE ] !== 'disabled';
	}

	/**
	 * Get the current OTP mode (email, sms, or totp)
	 *
	 * @return string 'email', 'sms', or 'totp'
	 */
	private function _getOtpMode(): string {
		$opt = LoginSecurity::getOptions();

		if ( isset( $opt[ LoginSecurity::KEY_OTP_MODE ] ) && $opt[ LoginSecurity::KEY_OTP_MODE ] !== 'disabled' ) {
			return $opt[ LoginSecurity::KEY_OTP_MODE ];
		}

		// Backward compatibility
		return 'email';
	}

	/**
	 * Get the cached gateway instance.
	 * Creates it once and caches for the lifetime of this request.
	 *
	 * @return GatewayInterface|null null if gateway not available
	 */
	private function _getGateway(): ?GatewayInterface {
		if ( $this->cachedGateway === null ) {
			$instance = GatewayFactory::create();
			$this->cachedGateway = ( $instance && $instance->validateConfig() ) ? $instance : false;
		}

		return $this->cachedGateway ?: null;
	}

	/**
	 * Create a secure hash of the OTP.
	 *
	 * @param string $otp The OTP to hash.
	 *
	 * @return string Hashed OTP.
	 */
	private function _hashOtp( string $otp ): string {
		return hash_hmac( 'sha256', $otp, AUTH_SALT . SECURE_AUTH_SALT );
	}

	/**
	 * Check if IP binding is enabled for OTP cookie.
	 *
	 * @return bool
	 */
	private function _isIpBindingEnabled(): bool {
		$opt = LoginSecurity::getOptions();

		return ! empty( $opt[ LoginSecurity::KEY_OTP_IP_BINDING ] );
	}

	/**
	 * Roles that should be forced to use Email-OTP.
	 *
	 * @return array
	 */
	private function _otpUserRoles(): array {
		$opt   = LoginSecurity::getOptions();
		$roles = ! empty( $opt[ LoginSecurity::KEY_OTP_USER_ROLES ] ) ? (array) $opt[ LoginSecurity::KEY_OTP_USER_ROLES ] : [ 'editor', 'administrator' ];

		return apply_filters( 'loginotp_user_roles', $roles );
	}

	// ------------------------------------------------------

	/**
	 * Get the channel label for display (e.g., "Telegram", "Email")
	 *
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	private function _getChannelLabel( \WP_User $user ): string {
		$mode = $this->_getOtpMode();

		if ( $mode !== 'sms' ) {
			return __( 'Email', HDA_TEXTDOMAIN );
		}

		$gateway = $this->_getGateway();
		if ( ! $gateway ) {
			return __( 'Email', HDA_TEXTDOMAIN );
		}

		// Check if user has configured recipient for this gateway
		$metaKey   = $gateway->getUserMetaKey();
		$recipient = get_user_meta( $user->ID, $metaKey, true );

		if ( empty( $recipient ) ) {
			return __( 'Email', HDA_TEXTDOMAIN ); // Fallback to email
		}

		return $gateway->getLabel();
	}

	// ------------------------------------------------------

	/**
	 * Mask the recipient for display (e.g., "***5678", "d***@gmail.com")
	 *
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	private function _maskRecipient( \WP_User $user ): string {
		$mode = $this->_getOtpMode();

		if ( $mode !== 'sms' ) {
			return $this->_maskEmail( $user->user_email );
		}

		$gateway = $this->_getGateway();
		if ( ! $gateway ) {
			return $this->_maskEmail( $user->user_email );
		}

		$metaKey   = $gateway->getUserMetaKey();
		$recipient = get_user_meta( $user->ID, $metaKey, true );

		if ( empty( $recipient ) ) {
			return $this->_maskEmail( $user->user_email ); // Fallback
		}

		// Mask phone/chat_id: show last 4 chars
		$length = strlen( $recipient );
		if ( $length <= 4 ) {
			return str_repeat( '*', $length );
		}

		return str_repeat( '*', $length - 4 ) . substr( $recipient, -4 );
	}

	// ------------------------------------------------------

	/**
	 * Mask email address for display
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	private function _maskEmail( string $email ): string {
		if ( ! str_contains( $email, '@' ) ) {
			return $email;
		}

		[ $local, $domain ] = explode( '@', $email, 2 );

		$localLength = strlen( $local );
		if ( $localLength <= 2 ) {
			$maskedLocal = str_repeat( '*', $localLength );
		} else {
			$maskedLocal = $local[0] . str_repeat( '*', $localLength - 2 ) . $local[ $localLength - 1 ];
		}

		return $maskedLocal . '@' . $domain;
	}
}
