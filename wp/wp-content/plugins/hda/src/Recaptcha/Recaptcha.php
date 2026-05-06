<?php
/**
 * CAPTCHA Module — Unified CAPTCHA integration.
 *
 * Supports Google reCAPTCHA v2 (Checkbox) and Cloudflare Turnstile.
 * Stores API keys, resolves the active provider, and delegates
 * form protection to FormProtection.
 *
 * @package HDAddons\Recaptcha
 * @author  HD
 */

namespace HDAddons\Recaptcha;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;
use HDAddons\Recaptcha\Provider\CaptchaProviderInterface;
use HDAddons\Recaptcha\Provider\RecaptchaV2Provider;
use HDAddons\Recaptcha\Provider\TurnstileProvider;

\defined( 'ABSPATH' ) || exit;

final class Recaptcha implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME              = 'recaptcha__options';

	// API keys.
	public const string KEY_V2_SITE_KEY          = 'recaptcha_v2_site_key';
	public const string KEY_V2_SECRET_KEY        = 'recaptcha_v2_secret_key';
	public const string KEY_GLOBAL               = 'recaptcha_global';
	public const string KEY_TURNSTILE_SITE_KEY   = 'turnstile_site_key';
	public const string KEY_TURNSTILE_SECRET_KEY = 'turnstile_secret_key';
	public const string KEY_ALLOWLIST_IPS        = 'recaptcha_allowlist_ips';

	// Provider & form protection.
	public const string KEY_CAPTCHA_PROVIDER   = 'captcha_provider';
	public const string KEY_FORM_LOGIN         = 'form_login';
	public const string KEY_FORM_REGISTER      = 'form_register';
	public const string KEY_FORM_LOST_PASSWORD = 'form_lost_password';
	public const string KEY_FORM_COMMENT       = 'form_comment';

	/**
	 * Cached options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ------------------------------------------------------

	/**
	 * Initialize CAPTCHA module.
	 */
	public function __construct() {
		self::migrateFromLoginSecurity();

		// Resolve the active provider and initialize form protection.
		$this->initFormProtection( self::getOptions() );
	}

	// ─── OPTIONS ────────────────────────────────────────

	/**
	 * Get cached module options.
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
	 * Reset cached options (call after save).
	 *
	 * @return void
	 */
	public static function resetCache(): void {
		self::$options = null;
	}

	// ─── PROVIDER RESOLUTION ────────────────────────────

	/**
	 * Resolve the active CAPTCHA provider from settings.
	 *
	 * @param array $options Module options.
	 *
	 * @return CaptchaProviderInterface|null Null if disabled or keys missing.
	 */
	public static function resolveProvider( array $options ): ?CaptchaProviderInterface {
		$providerKey = $options[ self::KEY_CAPTCHA_PROVIDER ] ?? '';

		if ( empty( $providerKey ) ) {
			return null;
		}

		return match ( $providerKey ) {
			'recaptcha_v2' => self::resolveRecaptchaV2( $options ),
			'turnstile'    => self::resolveTurnstile( $options ),
			default        => null,
		};
	}

	/**
	 * Resolve reCAPTCHA v2 provider.
	 *
	 * @param array $options Module options.
	 *
	 * @return RecaptchaV2Provider|null
	 */
	private static function resolveRecaptchaV2( array $options ): ?RecaptchaV2Provider {
		$siteKey   = $options[ self::KEY_V2_SITE_KEY ] ?? '';
		$secretKey = $options[ self::KEY_V2_SECRET_KEY ] ?? '';

		if ( empty( $siteKey ) || empty( $secretKey ) ) {
			return null;
		}

		return new RecaptchaV2Provider(
			$siteKey,
			$secretKey,
			! empty( $options[ self::KEY_GLOBAL ] )
		);
	}

	/**
	 * Resolve Turnstile provider.
	 *
	 * @param array $options Module options.
	 *
	 * @return TurnstileProvider|null
	 */
	private static function resolveTurnstile( array $options ): ?TurnstileProvider {
		$siteKey   = $options[ self::KEY_TURNSTILE_SITE_KEY ] ?? '';
		$secretKey = $options[ self::KEY_TURNSTILE_SECRET_KEY ] ?? '';

		if ( empty( $siteKey ) || empty( $secretKey ) ) {
			return null;
		}

		return new TurnstileProvider( $siteKey, $secretKey );
	}

	// ─── FORM PROTECTION ────────────────────────────────

	/**
	 * Initialize form protection if provider is active and any form is enabled.
	 *
	 * @param array $options Module options.
	 *
	 * @return void
	 */
	private function initFormProtection( array $options ): void {
		// Emergency bypass via constants.
		if (
			( defined( 'HDA_DISABLE_LOGIN_CAPTCHA' ) && \HDA_DISABLE_LOGIN_CAPTCHA )
			|| ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY )
		) {
			return;
		}

		$provider = self::resolveProvider( $options );
		if ( ! $provider ) {
			return;
		}

		// Check IP allowlist — skip CAPTCHA for allowlisted IPs.
		$allowlistIps = $options[ self::KEY_ALLOWLIST_IPS ] ?? [];
		$ip           = Helper::ipAddress();
		if ( ! empty( $allowlistIps ) && $ip && Helper::ipMatchesAny( $ip, $allowlistIps ) ) {
			return;
		}

		// Build form flags map.
		$forms = [
			self::KEY_FORM_LOGIN         => ! empty( $options[ self::KEY_FORM_LOGIN ] ),
			self::KEY_FORM_REGISTER      => ! empty( $options[ self::KEY_FORM_REGISTER ] ),
			self::KEY_FORM_LOST_PASSWORD => ! empty( $options[ self::KEY_FORM_LOST_PASSWORD ] ),
			self::KEY_FORM_COMMENT       => ! empty( $options[ self::KEY_FORM_COMMENT ] ),
		];

		// Only instantiate if at least one form is enabled.
		if ( ! in_array( true, $forms, true ) ) {
			return;
		}

		new FormProtection( $provider, $forms );
	}

	// ─── MIGRATION ──────────────────────────────────────

	/**
	 * One-time migration: move Login CAPTCHA setting from LoginSecurity to this module.
	 *
	 * @return void
	 */
	private static function migrateFromLoginSecurity(): void {
		$options = self::getOptions();

		// Already migrated or already has a provider set.
		if ( ! empty( $options[ self::KEY_CAPTCHA_PROVIDER ] ) ) {
			return;
		}

		$loginSecurityOptions = Helper::getOption( 'login_security__options', [] );
		$oldProvider          = $loginSecurityOptions['login_captcha_provider'] ?? '';

		if ( empty( $oldProvider ) ) {
			return;
		}

		// Migrate: copy provider + enable login form.
		$options[ self::KEY_CAPTCHA_PROVIDER ] = $oldProvider;
		$options[ self::KEY_FORM_LOGIN ]       = true;

		Helper::updateOption( self::OPTION_NAME, $options );

		// Clean up old setting.
		unset( $loginSecurityOptions['login_captcha_provider'] );
		Helper::updateOption( 'login_security__options', $loginSecurityOptions );

		// Reset cache so the current request uses the migrated values.
		self::resetCache();
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'recaptcha-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$fields = [
			self::KEY_V2_SITE_KEY,
			self::KEY_V2_SECRET_KEY,
			self::KEY_GLOBAL,
			self::KEY_TURNSTILE_SITE_KEY,
			self::KEY_TURNSTILE_SECRET_KEY,
			self::KEY_ALLOWLIST_IPS,
			self::KEY_CAPTCHA_PROVIDER,
			self::KEY_FORM_LOGIN,
			self::KEY_FORM_REGISTER,
			self::KEY_FORM_LOST_PASSWORD,
			self::KEY_FORM_COMMENT,
		];

		$options = self::extractFields( $data, $fields, true );
		self::saveOrRemove( self::OPTION_NAME, $options );
		self::resetCache();
	}
}
