<?php
/**
 * Trait for processing module settings via SettingsAware interface.
 *
 * Each module registers its own save logic via the SettingsAware contract.
 * This trait simply iterates over all registered modules and delegates
 * save handling to each one.
 *
 * @package Addons\GlobalSetting
 */

namespace HDAddons\GlobalSetting;

use HDAddons\AspectRatio\AspectRatio;
use HDAddons\ContactLink\ContactLink;
use HDAddons\Contracts\SettingsAware;
use HDAddons\CookieConsent\CookieConsent;
use HDAddons\CustomCode\CustomCode;
use HDAddons\CustomSorting\CustomSorting;
use HDAddons\Optimize\DatabaseOptimizer;
use HDAddons\Editor\Editor;
use HDAddons\File\File;
use HDAddons\Helper;
use HDAddons\LoginSecurity\LoginSecurity;
use HDAddons\Maintenance\Maintenance;
use HDAddons\Monitor404\Monitor404;
use HDAddons\Optimize\Optimize;
use HDAddons\PostTypeArchive\PostTypeArchive;
use HDAddons\Recaptcha\Recaptcha;
use HDAddons\Redirect\Redirect;
use HDAddons\ScheduledContent\ScheduledContent;
use HDAddons\Security\Security;
use HDAddons\Seo\Seo;
use HDAddons\SocialLink\SocialLink;

\defined( 'ABSPATH' ) || exit;

trait SettingsHandlerTrait {

	/**
	 * All module classes that implement SettingsAware.
	 *
	 * Order does not matter — each module checks its own form key
	 * and skips processing if the form section was not submitted.
	 *
	 * @return array<class-string<SettingsAware>>
	 */
	private function getSettingsAwareModules(): array {
		return [
			// Tier 1 — Simple modules
			Editor::class,
			File::class,
			Recaptcha::class,
			SocialLink::class,
			CookieConsent::class,
			Seo::class,
			AspectRatio::class,
			ScheduledContent::class,
			Monitor404::class,
			Maintenance::class,
			CustomCode::class,

			// Tier 2 — Medium complexity
			Optimize::class,
			DatabaseOptimizer::class,
			CustomSorting::class,
			Redirect::class,
			PostTypeArchive::class,
			ContactLink::class,

			// Tier 3 — Complex modules
			Security::class,
			LoginSecurity::class,
		];
	}

	// --------------------------------------------------

	/**
	 * Process settings using SettingsAware modules.
	 *
	 * Each module checks its own form key and handles its own save logic.
	 * GlobalSetting is handled separately (it's not a standard module).
	 *
	 * @param array $data Sanitized form data.
	 */
	private function processSettingsHandlers( array $data ): void {
		// Handle GlobalSetting first (not a standard SettingsAware module).
		$this->handleGlobalSetting( $data );

		// Delegate to each SettingsAware module.
		foreach ( $this->getSettingsAwareModules() as $moduleClass ) {
			if ( ! is_subclass_of( $moduleClass, SettingsAware::class ) ) {
				continue;
			}

			$formKey = $moduleClass::getFormKey();

			if ( ! isset( $data[ $formKey ] ) ) {
				continue;
			}

			$moduleClass::sanitizeAndSave( $data );
		}
	}

	// --------------------------------------------------

	/**
	 * Handle Global Setting module.
	 *
	 * Global settings are special — they control which modules are enabled,
	 * so they are handled inline rather than as a SettingsAware module.
	 *
	 * @param array $data Form data.
	 *
	 * @return void
	 */
	private function handleGlobalSetting( array $data ): void {
		$menu_options           = GlobalSetting::getConfig();
		$global_setting_options = [];

		foreach ( $menu_options as $slug => $value ) {
			if ( ! empty( $data[ $slug ] ) ) {
				$global_setting_options[ $slug ] = 1;
			}
		}

		if ( ! empty( $global_setting_options ) ) {
			Helper::updateOption( GlobalSetting::OPTION_NAME, $global_setting_options, 12, false );
		} else {
			Helper::removeOption( GlobalSetting::OPTION_NAME );
		}

		// Save uninstall cleanup preference (separate option for persistence)
		$cleanUninstall = ! empty( $data[ GlobalSetting::KEY_CLEAN_UNINSTALL ] ) ? 1 : 0;
		Helper::updateOption( GlobalSetting::KEY_CLEAN_UNINSTALL, $cleanUninstall, 12, false );

		// Update known modules list for orphan detection
		$currentModules = array_keys( $menu_options );
		Helper::updateOption( GlobalSetting::KEY_KNOWN_MODULES, $currentModules, 12, false );
	}
}
