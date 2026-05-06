<?php
/**
 * Custom Code module — groups Custom Script and Custom CSS into one parent module.
 *
 * @author HD
 */

namespace HDAddons\CustomCode;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\CustomCode\CustomCss;
use HDAddons\CustomCode\CustomScript;

\defined( 'ABSPATH' ) || exit;

final class CustomCode implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys ───────────────────────────────────

	/**
	 * Placeholder option — actual data stored via CustomScript and CustomCss keys.
	 */
	public const string OPTION_NAME = 'custom_code__options';

	// --------------------------------------------------

	public function __construct() {
		// Delegate to existing sub-modules (they self-register hooks).
		new CustomScript();
		new CustomCss();
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'custom_code-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		// CustomScript handles missing keys safely via ?? ''
		CustomScript::sanitizeAndSave( $data );

		if ( isset( $data[ CustomCss::KEY_FORM_CSS ] ) ) {
			CustomCss::sanitizeAndSave( $data );
		}
	}
}
