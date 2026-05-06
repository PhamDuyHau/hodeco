<?php
/**
 * Interface for modules that handle their own settings save/load.
 *
 * Implemented by modules that have a settings section in the admin UI.
 * Each module is responsible for its own sanitization and persistence.
 *
 * @package HDAddons\Contracts
 */

namespace HDAddons\Contracts;

defined( 'ABSPATH' ) || exit;

interface SettingsAware {

	/**
	 * The hidden field key that indicates this module's form section was submitted.
	 *
	 * Used by GlobalSetting to detect which modules need to process form data.
	 * Example return values: 'editor-hidden', 'optimize__options', 'security-hidden'
	 *
	 * @return string
	 */
	public static function getFormKey(): string;

	/**
	 * Sanitize and save settings from form data.
	 *
	 * Each module handles its own sanitization and persistence.
	 * The form data has already been pre-sanitized by GlobalSetting::sanitizeData().
	 *
	 * @param array $data Full sanitized form data from AJAX submit.
	 *
	 * @return void
	 */
	public static function sanitizeAndSave( array $data ): void;
}
