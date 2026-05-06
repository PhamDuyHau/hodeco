<?php
/**
 * Cookie Consent module - GDPR-compliant cookie consent banner.
 *
 * Renders a customizable cookie consent banner on the frontend.
 * CSS and JS are loaded from Vite-built assets.
 *
 * @package HDAddons\CookieConsent
 * @author  HD
 */

namespace HDAddons\CookieConsent;

use HDAddons\Asset;
use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CookieConsent implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'cookie_consent__options';

	public const string KEY_ENABLED      = 'enabled';
	public const string KEY_POSITION     = 'position';
	public const string KEY_MESSAGE      = 'message';
	public const string KEY_ACCEPT_TEXT  = 'accept_text';
	public const string KEY_DISMISS_TEXT = 'dismiss_text';
	public const string KEY_PRIVACY_URL  = 'privacy_url';
	public const string KEY_PRIVACY_TEXT = 'privacy_text';
	public const string KEY_CONSENT_DAYS = 'consent_days';
	public const string KEY_DISMISS_DAYS = 'dismiss_days';

	/**
	 * Cached module options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ------------------------------------------------------

	/**
	 * Initialize cookie consent banner.
	 */
	public function __construct() {
		$options = self::getOptions();

		if ( empty( $options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// Do not show in admin.
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_footer', $this->renderBanner( ... ), 99 );
		add_action( 'wp_enqueue_scripts', $this->enqueueAssets( ... ), 99 );
	}

	// ------------------------------------------------------

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

	// ------------------------------------------------------

	/**
	 * Enqueue cookie consent CSS and JS.
	 *
	 * @return void
	 */
	public function enqueueAssets(): void {
		Asset::enqueueCSS( 'cookie-consent.scss' );
		Asset::enqueueJS( 'cookie-consent.js', [], null, true, [ 'module', 'defer' ] );
	}

	// ------------------------------------------------------

	/**
	 * Render cookie consent banner HTML.
	 *
	 * @return void
	 */
	public function renderBanner(): void {
		$options = self::getOptions();

		$message     = $options[ self::KEY_MESSAGE ] ?? __( 'We use cookies to improve your experience. By continuing to use this site, you agree to our use of cookies.', HDA_TEXTDOMAIN );
		$acceptText  = $options[ self::KEY_ACCEPT_TEXT ] ?? __( 'Accept', HDA_TEXTDOMAIN );
		$dismissText = $options[ self::KEY_DISMISS_TEXT ] ?? __( 'Close', HDA_TEXTDOMAIN );
		$privacyUrl  = $options[ self::KEY_PRIVACY_URL ] ?? '';
		$privacyText = $options[ self::KEY_PRIVACY_TEXT ] ?? __( 'Privacy Policy', HDA_TEXTDOMAIN );
		$position    = $options[ self::KEY_POSITION ] ?? 'bottom';
		$consentDays = (int) ( $options[ self::KEY_CONSENT_DAYS ] ?? 180 );
		$dismissDays = (int) ( $options[ self::KEY_DISMISS_DAYS ] ?? 7 );

		$privacyLink = '';
		if ( $privacyUrl ) {
			$privacyLink = sprintf(
				' <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $privacyUrl ),
				esc_html( $privacyText )
			);
		}

		printf(
			'<section class="hda-cookie-consent" data-position="%s" data-consent-days="%d" data-dismiss-days="%d" aria-label="%s">',
			esc_attr( $position ),
			$consentDays,
			$dismissDays,
			esc_attr__( 'Cookie consent', HDA_TEXTDOMAIN )
		);

		echo '<div class="hda-cc-text">';
		echo wp_kses_post( $message );
		echo $privacyLink; // Already escaped above.
		echo '</div>';

		echo '<div class="hda-cc-actions">';
		printf( '<button type="button" class="hda-cc-btn hda-cc-accept js-cookie-consent-accept">%s</button>', esc_html( $acceptText ) );
		printf( '<button type="button" class="hda-cc-btn hda-cc-dismiss js-cookie-consent-close">%s</button>', esc_html( $dismissText ) );
		echo '</div>';

		echo '</section>';
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'cookie_consent-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$options = [
			self::KEY_ENABLED      => ! empty( $data['cc_enabled'] ),
			self::KEY_POSITION     => isset( $data['cc_position'] ) ? sanitize_key( $data['cc_position'] ) : 'bottom',
			self::KEY_MESSAGE      => isset( $data['cc_message'] ) ? wp_kses_post( $data['cc_message'] ) : '',
			self::KEY_ACCEPT_TEXT  => isset( $data['cc_accept_text'] ) ? sanitize_text_field( $data['cc_accept_text'] ) : '',
			self::KEY_DISMISS_TEXT => isset( $data['cc_dismiss_text'] ) ? sanitize_text_field( $data['cc_dismiss_text'] ) : '',
			self::KEY_PRIVACY_URL  => isset( $data['cc_privacy_url'] ) ? esc_url_raw( $data['cc_privacy_url'] ) : '',
			self::KEY_PRIVACY_TEXT => isset( $data['cc_privacy_text'] ) ? sanitize_text_field( $data['cc_privacy_text'] ) : '',
			self::KEY_CONSENT_DAYS => isset( $data['cc_consent_days'] ) ? absint( $data['cc_consent_days'] ) : 180,
			self::KEY_DISMISS_DAYS => isset( $data['cc_dismiss_days'] ) ? absint( $data['cc_dismiss_days'] ) : 7,
		];

		self::saveOrRemove( self::OPTION_NAME, $options, true );
	}
}
