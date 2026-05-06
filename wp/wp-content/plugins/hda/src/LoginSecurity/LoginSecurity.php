<?php

namespace HDAddons\LoginSecurity;

use HDAddons\Asset;
use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\CSS;
use HDAddons\Helper;
use HDAddons\LoginSecurity\ActivityLog\ActivityLog;
use HDAddons\LoginSecurity\ActivityLog\ActivityLogAdmin;
use HDAddons\LoginSecurity\MagicLink\LoginMagicLink;

\defined( 'ABSPATH' ) || exit;

final class LoginSecurity implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────
	// All modules, handlers, forms, and theme code MUST reference
	// these constants instead of raw strings to prevent mismatch bugs.

	public const string OPTION_NAME = 'login_security__options';

	// Login URL
	public const string KEY_CUSTOM_LOGIN_URI     = 'custom_login_uri';
	public const string KEY_LOGIN_TOKEN_IP_CHECK = 'login_token_ip_check';

	// OTP
	public const string KEY_OTP_MODE           = 'otp_mode';
	public const string KEY_OTP_GATEWAY        = 'otp_gateway';
	public const string KEY_OTP_GATEWAY_CONFIG = 'otp_gateway_config';
	public const string KEY_OTP_USER_ROLES     = 'otp_user_roles';
	public const string KEY_OTP_IP_BINDING     = 'otp_ip_binding';

	// Protection
	public const string KEY_LOGIN_IPS_ACCESS     = 'login_ips_access';

	public const string KEY_ILLEGAL_USERS        = 'illegal_users';
	public const string KEY_LIMIT_LOGIN_ATTEMPTS = 'limit_login_attempts';
	public const string KEY_ACTIVITY_LOG_ENABLED = 'activity_log_enabled';

	/**
	 * Cached options for performance.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ------------------------------------------------------

	public function __construct() {
		add_action( 'login_enqueue_scripts', $this->loginEnqueueAssets( ... ), 31 );
		add_filter( 'login_headertext', $this->loginHeadertext( ... ) );
		add_filter( 'login_headerurl', $this->loginHeaderurl( ... ) );

		// CSRF login-form
		add_action( 'login_form', $this->addCsrfLoginForm( ... ) );
		add_filter( 'authenticate', $this->verifyCsrfLogin( ... ), 30, 3 );

		// CSRF lost-password
		add_action( 'lostpassword_form', $this->addCsrfLostpasswordForm( ... ) );
		add_action( 'lostpassword_post', $this->verifyCsrfLostpasswordPost( ... ), 30, 2 );

		// Initialize submodules with lazy loading
		// Only instantiate modules that are actually enabled to reduce overhead
		$this->initSubModules();
	}

	/**
	 * Get cached login security options.
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		if ( null === self::$options ) {
			self::$options = Helper::getOption( self::OPTION_NAME, [] );
		}

		return self::$options;
	}

	/**
	 * Initialize submodules with lazy loading.
	 * Only instantiate modules that are actually enabled to reduce overhead.
	 *
	 * @return void
	 */
	private function initSubModules(): void {
		$options = self::getOptions();

		// Modules that are always needed (admin UI)
		( new ActivityLogAdmin() );

		// LoginRestricted - check if any restriction is configured (plugin UI OR theme defaults)
		$hasRestrictions       = ! empty( $options[ self::KEY_LOGIN_IPS_ACCESS ] );
		$themeSecurityDefaults = Helper::filterSettingOptions( 'security', false );
		$hasThemeRestrictions  = ! empty( $themeSecurityDefaults['allowlist_ips_login_access'] );

		if ( $hasRestrictions || $hasThemeRestrictions ) {
			( new LoginRestricted() );
		}

		// LoginIllegalUsers - check if illegal usernames are defined
		if ( ! empty( $options[ self::KEY_ILLEGAL_USERS ] ) ) {
			( new LoginIllegalUsers() );
		}

		// LoginAttempts - check if limit is set
		if (
			! empty( $options[ self::KEY_LIMIT_LOGIN_ATTEMPTS ] )
			&& (int) $options[ self::KEY_LIMIT_LOGIN_ATTEMPTS ] > 0
		) {
			( new LoginAttempts() );
		}

		// LoginOtpVerification - check if OTP mode is enabled (email/sms/totp)
		$otpMode = $options[ self::KEY_OTP_MODE ] ?? 'disabled';

		if ( in_array( $otpMode, [ 'email', 'sms', 'totp' ], true ) ) {
			( new LoginOtpVerification() );
			( new UserOtpProfile() );
		}

		// LoginMagicLink - passwordless login via email link
		if ( $otpMode === 'magic_link' ) {
			( new LoginMagicLink() );
		}

		// LoginUrl - check if custom login URL is set
		if ( ! empty( $options[ self::KEY_CUSTOM_LOGIN_URI ] ) ) {
			( new LoginUrl() );
		}

		// ActivityLog - check if logging is enabled
		if ( ! empty( $options[ self::KEY_ACTIVITY_LOG_ENABLED ] ) ) {
			( new ActivityLog() );
		}
	}

	/**
	 * Check if current request method is POST.
	 *
	 * @return bool
	 */
	public static function isPostRequest(): bool {
		return isset( $_SERVER['REQUEST_METHOD'] )
			&& strtoupper( sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) ) === 'POST';
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public function loginEnqueueAssets(): void {
		Asset::enqueueCSS( 'login.scss' );
		Asset::enqueueJS( 'login.js', [ 'jquery' ], null, true, [ 'module', 'defer' ] );

		$default_logo = HDA_URL . 'assets/img/logo.png';
		$default_bg   = HDA_URL . 'assets/img/login-bg.jpg';

		// scripts / styles
		$logo     = esc_url_raw( Helper::getThemeMod( 'login_page_logo_setting' ) ?: $default_logo );
		$bg_img   = esc_url_raw( Helper::getThemeMod( 'login_page_bgimage_setting' ) ?: $default_bg );
		$bg_color = sanitize_hex_color( Helper::getThemeMod( 'login_page_bgcolor_setting' ) );

		$css = new CSS();

		if ( $bg_img ) {
			$css->setSelector( 'body.login' )
				->addProperty( 'background-image', "url({$bg_img})" );
		}

		if ( $bg_color ) {
			$css->setSelector( 'body.login' )
				->addProperty( 'background-color', $bg_color )
				->setSelector( 'body.login:before' )
				->addProperty( 'background', 'none' )
				->addProperty( 'opacity', 1 );
		}

		if ( $logo ) {
			$css->setSelector( 'body.login #login h1 a' )
				->addProperty( 'background-image', "url({$logo})" );
		}

		$inline = $css->cssOutput();
		if ( $inline ) {
			Asset::inlineStyle( Asset::handle( 'login.scss' ), $inline );
		}
	}

	// -------------------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public function loginHeadertext(): mixed {
		return Helper::getThemeMod( 'login_page_headertext_setting' ) ?: get_bloginfo( 'name' );
	}

	// -------------------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public function loginHeaderurl(): mixed {
		return Helper::getThemeMod( 'login_page_headerurl_setting' ) ?: site_url( '/' );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function addCsrfLoginForm(): void {
		echo Helper::CSRFToken( 'login_csrf_token' );
	}

	// ------------------------------------------------------

	/**
	 * Verify CSRF token on login form submit.
	 *
	 * @param mixed  $user     The user object or error.
	 * @param string $username The username.
	 * @param string $password The password.
	 *
	 * @return mixed|\WP_Error
	 */
	public function verifyCsrfLogin( $user, $username, $password ): mixed {
		if ( empty( $username ) || ! $this->isPostRequest() ) {
			return $user;
		}

		$csrf_token = isset( $_POST['_csrf_token'] ) ? sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ) ) : '';
		if ( empty( $csrf_token ) || ! wp_verify_nonce( $csrf_token, 'login_csrf_token' ) ) {
			return new \WP_Error( 'csrf_error', __( 'Invalid CSRF token. Please try again.', HDA_TEXTDOMAIN ) );
		}

		return $user;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function addCsrfLostpasswordForm(): void {
		echo Helper::CSRFToken( 'lostpassword_csrf_token' );
	}

	// ------------------------------------------------------

	/**
	 * Verify CSRF token on lost password form submit.
	 *
	 * @param \WP_Error $errors    The error object.
	 * @param mixed     $user_data The user data.
	 *
	 * @return void
	 */
	public function verifyCsrfLostpasswordPost( \WP_Error $errors, $user_data ): void {
		if ( ! $this->isPostRequest() ) {
			return;
		}

		$csrf_token = isset( $_POST['_csrf_token'] ) ? sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ) ) : '';
		if ( empty( $csrf_token ) || ! wp_verify_nonce( $csrf_token, 'lostpassword_csrf_token' ) ) {
			$errors->add( 'csrf_error', __( 'Invalid CSRF token, please try again.', HDA_TEXTDOMAIN ) );
		}
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'login-security-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$fields = [
			self::KEY_CUSTOM_LOGIN_URI,
			self::KEY_LOGIN_TOKEN_IP_CHECK,
			self::KEY_OTP_MODE,
			self::KEY_OTP_GATEWAY,
			self::KEY_OTP_GATEWAY_CONFIG,
			self::KEY_OTP_USER_ROLES,
			self::KEY_OTP_IP_BINDING,
			self::KEY_LOGIN_IPS_ACCESS,
			self::KEY_ILLEGAL_USERS,
			self::KEY_LIMIT_LOGIN_ATTEMPTS,
			self::KEY_ACTIVITY_LOG_ENABLED,
		];

		$options  = self::extractFields( $data, $fields );
		$existing = Helper::getOption( self::OPTION_NAME, [] );

		// ── Preserve settings for hidden sections ──────────────
		// When otp_mode changes, hidden form sections are not submitted,
		// so their keys are absent from $options. Merge from existing.
		$preserveKeys = [
			self::KEY_OTP_GATEWAY,
			self::KEY_OTP_GATEWAY_CONFIG,
			self::KEY_OTP_USER_ROLES,
			self::KEY_OTP_IP_BINDING,
		];

		foreach ( $preserveKeys as $key ) {
			if ( ! isset( $options[ $key ] ) && isset( $existing[ $key ] ) ) {
				$options[ $key ] = $existing[ $key ];
			}
		}

		// Validate gateway config when SMS mode is enabled (warning only).
		if ( ( $options[ self::KEY_OTP_MODE ] ?? 'disabled' ) === 'sms' ) {
			$gatewayName   = $options[ self::KEY_OTP_GATEWAY ] ?? 'telegram';
			$gatewayConfig = $options[ self::KEY_OTP_GATEWAY_CONFIG ][ $gatewayName ] ?? [];

			$validationResult = self::validateGatewayConfig( $gatewayName, $gatewayConfig );

			if ( ! $validationResult['valid'] ) {
				add_action( 'admin_notices', static function () use ( $validationResult ) {
					echo '<div class="notice notice-warning is-dismissible">';
					echo '<p><strong>' . esc_html__( 'OTP Gateway Warning:', HDA_TEXTDOMAIN ) . '</strong> ';
					echo esc_html( $validationResult['message'] );
					echo ' ' . esc_html__( 'SMS mode is enabled but may fall back to email if gateway is not properly configured.', HDA_TEXTDOMAIN );
					echo '</p></div>';
				} );
			}
		}

		// Check privileged user permissions.
		$defaults            = Helper::filterSettingOptions( 'security', false );
		$privileged_user_ids = $defaults['privileged_user_ids'] ?? [];
		$user_id             = get_current_user_id();

		// Non-privileged users cannot modify certain security settings.
		if ( ! in_array( $user_id, $privileged_user_ids, true ) ) {
			$options[ self::KEY_CUSTOM_LOGIN_URI ]     = $existing[ self::KEY_CUSTOM_LOGIN_URI ] ?? '';
			$options[ self::KEY_LOGIN_TOKEN_IP_CHECK ] = $existing[ self::KEY_LOGIN_TOKEN_IP_CHECK ] ?? '';
			$options[ self::KEY_OTP_MODE ]             = $existing[ self::KEY_OTP_MODE ] ?? 'disabled';
			$options[ self::KEY_OTP_GATEWAY ]          = $existing[ self::KEY_OTP_GATEWAY ] ?? 'telegram';
			$options[ self::KEY_OTP_GATEWAY_CONFIG ]   = $existing[ self::KEY_OTP_GATEWAY_CONFIG ] ?? [];
			$options[ self::KEY_OTP_USER_ROLES ]       = $existing[ self::KEY_OTP_USER_ROLES ] ?? [ 'editor', 'administrator' ];
			$options[ self::KEY_OTP_IP_BINDING ]       = $existing[ self::KEY_OTP_IP_BINDING ] ?? '';
			$options[ self::KEY_LOGIN_IPS_ACCESS ]     = $existing[ self::KEY_LOGIN_IPS_ACCESS ] ?? [];
		}

		// ── Self-lockout prevention ─────────────────────────
		// If allowlist IPs are set, ensure the current user's IP is included
		// to prevent accidentally locking themselves out of the login page.
		$allowlist_ips = (array) ( $options[ self::KEY_LOGIN_IPS_ACCESS ] ?? [] );
		if ( ! empty( $allowlist_ips ) ) {
			$current_ip = Helper::ipAddress();

			if ( $current_ip && ! Helper::ipMatchesAny( $current_ip, $allowlist_ips ) ) {
				$allowlist_ips[]                        = $current_ip;
				$options[ self::KEY_LOGIN_IPS_ACCESS ] = $allowlist_ips;

				add_action( 'admin_notices', static function () use ( $current_ip ) {
					echo '<div class="notice notice-info is-dismissible">';
					echo '<p><strong>' . esc_html__( 'Login Security:', HDA_TEXTDOMAIN ) . '</strong> ';
					echo sprintf(
						/* translators: %s: IP address */
						esc_html__( 'Your current IP (%s) was automatically added to the allowlist to prevent self-lockout.', HDA_TEXTDOMAIN ),
						esc_html( $current_ip )
					);
					echo '</p></div>';
				} );
			}
		}

		Helper::updateOption( self::OPTION_NAME, $options );
	}

	/**
	 * Validate gateway configuration.
	 *
	 * @param string $gatewayName Gateway name.
	 * @param array  $config      Gateway config.
	 *
	 * @return array{valid: bool, message: string}
	 */
	private static function validateGatewayConfig( string $gatewayName, array $config ): array {
		$requiredFields = match ( $gatewayName ) {
			'telegram' => [ 'bot_token' => __( 'Bot Token', HDA_TEXTDOMAIN ) ],
			'zalo'     => [
				'app_id'        => __( 'App ID', HDA_TEXTDOMAIN ),
				'secret_key'    => __( 'Secret Key', HDA_TEXTDOMAIN ),
				'refresh_token' => __( 'Refresh Token', HDA_TEXTDOMAIN ),
				'template_id'   => __( 'Template ID', HDA_TEXTDOMAIN ),
			],
			'whatsapp' => [
				'phone_number_id' => __( 'Phone Number ID', HDA_TEXTDOMAIN ),
				'access_token'    => __( 'Access Token', HDA_TEXTDOMAIN ),
			],
			'smsgate' => [
				'username' => __( 'Username', HDA_TEXTDOMAIN ),
				'password' => __( 'Password', HDA_TEXTDOMAIN ),
			],
			'viber'   => [ 'auth_token' => __( 'Auth Token', HDA_TEXTDOMAIN ) ],
			'line'    => [ 'channel_access_token' => __( 'Channel Access Token', HDA_TEXTDOMAIN ) ],
			'discord' => [ 'bot_token' => __( 'Bot Token', HDA_TEXTDOMAIN ) ],
			default   => [],
		};

		$missingFields = [];
		foreach ( $requiredFields as $field => $label ) {
			if ( empty( $config[ $field ] ) ) {
				$missingFields[] = $label;
			}
		}

		if ( ! empty( $missingFields ) ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					__( 'Missing required fields: %s.', HDA_TEXTDOMAIN ),
					implode( ', ', $missingFields )
				),
			];
		}

		return [ 'valid' => true, 'message' => '' ];
	}
}
