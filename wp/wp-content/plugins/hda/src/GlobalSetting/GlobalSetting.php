<?php

namespace HDAddons\GlobalSetting;

use HDAddons\Plugin;
use HDAddons\AspectRatio\AspectRatio;
use HDAddons\ContactLink\ContactLink;
use HDAddons\CookieConsent\CookieConsent;
use HDAddons\CronManager\CronManager;
use HDAddons\CustomCode\CustomCode;
use HDAddons\CustomCode\CustomCss;
use HDAddons\CustomCode\CustomScript;
use HDAddons\CustomSorting\CustomSorting;
use HDAddons\Optimize\DatabaseOptimizer;
use HDAddons\Editor\Editor;
use HDAddons\File\File;
use HDAddons\File\FileIntegrity\FileIntegrity;
use HDAddons\Security\Firewall\Firewall;
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
use HDAddons\Security\AccessControl;
use HDAddons\Seo\Seo;
use HDAddons\SocialLink\SocialLink;
use HDAddons\Security\TrafficMonitor\TrafficMonitor;

\defined( 'ABSPATH' ) || exit;

final class GlobalSetting {

	use SettingsHandlerTrait;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'global_setting__options';

	/**
	 * Whether to clean all data on plugin uninstall.
	 * When enabled, all options, tables, and stored content are deleted.
	 * When disabled, data is preserved for potential reinstall.
	 */
	public const string KEY_CLEAN_UNINSTALL = 'clean_uninstall';

	/**
	 * Stores the list of module slugs known from config.php.
	 * Used to detect when a module is removed from config.php → trigger orphan cleanup.
	 */
	public const string KEY_KNOWN_MODULES = 'hda_known_modules';

	/**
	 * Menu position constant.
	 */
	private const MENU_POSITION = 80;

	/**
	 * Modules that use StoredOption (custom post) instead of wp_options.
	 * Shared between orphan cleanup and uninstall to avoid inconsistency.
	 */
	public const array STORED_OPTION_MODULES = [ 'custom_code', 'redirect' ];

	/**
	 * Cached module configuration.
	 */
	private static ?array $configCache = null;

	// --------------------------------------------------

	/**
	 * Map of module slug → option names used by that module.
	 * Used for orphan cleanup when a module is removed from config.php.
	 *
	 * @return array<string, array<string>>
	 */
	public static function getModuleOptionMap(): array {
		return [
			'aspect_ratio'      => [ AspectRatio::OPTION_NAME ],
			'editor'            => [ Editor::OPTION_NAME ],
			'seo'               => [ Seo::OPTION_NAME ],
			'security'          => [
				Security::OPTION_NAME,
				AccessControl::OPTION_NAME,
				Firewall::OPTION_NAME,
				TrafficMonitor::OPTION_NAME,
			],
			'login_security'    => [ LoginSecurity::OPTION_NAME ],
			'optimize'          => [
				Optimize::OPTION_NAME,
				DatabaseOptimizer::OPTION_NAME
			],
			'social_link'       => [ SocialLink::OPTION_NAME ],
			'contact_link'      => [ ContactLink::OPTION_NAME ],
			'cookie_consent'    => [ CookieConsent::OPTION_NAME ],
			'file'              => [
				File::OPTION_NAME,
				FileIntegrity::OPTION_NAME,
			],
			'custom_sorting'    => [ CustomSorting::OPTION_NAME ],
			'scheduled_content' => [ ScheduledContent::OPTION_NAME ],
			'post_type_archive' => [ PostTypeArchive::OPTION_NAME ],
			'recaptcha'         => [ Recaptcha::OPTION_NAME ],
			'redirect'          => [ Redirect::OPTION_NAME ],
			'monitor_404'       => [ Monitor404::OPTION_NAME ],
			'maintenance'       => [ Maintenance::OPTION_NAME ],
			'custom_code'       => [
				CustomCode::OPTION_NAME,
				CustomScript::KEY_HEADER,
				CustomScript::KEY_FOOTER,
				CustomScript::KEY_BODY_TOP,
				CustomScript::KEY_BODY_BOTTOM,
				CustomCss::OPTION_NAME,
			],
			'cron_manager'      => [ CronManager::OPTION_NAME ],
		];
	}

	/**
	 * Clean up options for modules that have been removed from config.php.
	 * Compares previously known modules with current config.
	 * Only triggers when a module is entirely removed from config.php (not just disabled via checkbox).
	 *
	 * @return void
	 */
	public static function cleanupOrphanedModules(): void {
		$currentModules = array_keys( self::getConfig() );
		$knownModules   = Helper::getOption( self::KEY_KNOWN_MODULES, [] );

		// Modules that were merged into parent modules but still have their own options.
		// They are NOT in config.php anymore, but their data must NOT be cleaned up.
		$mergedModules = [ 'custom_script', 'custom_css', 'firewall', 'traffic_monitor', 'file_integrity' ];

		// First run — no known modules yet, just save current state
		if ( empty( $knownModules ) ) {
			Helper::updateOption( self::KEY_KNOWN_MODULES, $currentModules, 12, false );
			return;
		}

		// Find modules that were known but no longer in config.php
		$removedModules = array_diff( $knownModules, $currentModules );

		// Exclude merged modules — their data is still in use.
		$removedModules = array_diff( $removedModules, $mergedModules );

		if ( empty( $removedModules ) ) {
			// Update known modules list (in case new modules were added)
			if ( $currentModules !== $knownModules ) {
				Helper::updateOption( self::KEY_KNOWN_MODULES, $currentModules, 12, false );
			}
			return;
		}

		$optionMap = self::getModuleOptionMap();

		foreach ( $removedModules as $slug ) {
			if ( ! isset( $optionMap[ $slug ] ) ) {
				continue;
			}

			foreach ( $optionMap[ $slug ] as $optionName ) {
				// StoredOption (custom post) uses different storage
				if ( in_array( $slug, self::STORED_OPTION_MODULES, true ) ) {
					Helper::deleteStoredOption( $optionName );
				} else {
					Helper::removeOption( $optionName );
				}
			}

			Helper::errorLog( "[HDA] Orphan cleanup: removed options for '{$slug}' (deleted from config.php)" );
		}

		// Remove from global_setting__options too
		$globalOptions = Helper::getOption( self::OPTION_NAME, [] );
		foreach ( $removedModules as $slug ) {
			unset( $globalOptions[ $slug ] );
		}
		Helper::updateOption( self::OPTION_NAME, $globalOptions, 12, false );

		// Save updated known modules list
		Helper::updateOption( self::KEY_KNOWN_MODULES, $currentModules, 12, false );
	}

	// --------------------------------------------------

	/**
	 * Initialize admin menu and settings handlers.
	 */
	public function __construct() {
		add_action( 'admin_menu', $this->adminMenu( ... ) );
		add_action( 'admin_menu', $this->renameFirstSubmenu( ... ), 999 );

		add_action( 'wp_ajax_submit_settings', $this->ajaxSubmitSettings( ... ) );
	}


	// --------------------------------------------------

	/**
	 * Get cached module configuration.
	 *
	 * Loads from config.php (PHP array — OPcache-friendly, zero dependency).
	 *
	 * @return array
	 */
	public static function getConfig(): array {
		if ( null === self::$configCache ) {
			$configFile = HDA_PATH . 'config.php';
			self::$configCache = is_file( $configFile ) ? (array) include $configFile : [];
		}

		return self::$configCache;
	}

	// --------------------------------------------------

	/**
	 * Register admin menu pages.
	 *
	 * @return void
	 */
	private function adminMenu(): void {
		// HDA Settings menu
		add_menu_page(
			__( 'HDA Settings', HDA_TEXTDOMAIN ),
			__( 'HDA', HDA_TEXTDOMAIN ),
			Plugin::CAPABILITY,
			'hda-settings',
			$this->menuCallback( ... ),
			'dashicons-admin-settings',
			self::MENU_POSITION
		);

		// Advanced submenu
		add_submenu_page(
			'hda-settings',
			__( 'Advanced', HDA_TEXTDOMAIN ),
			__( 'Advanced', HDA_TEXTDOMAIN ),
			Plugin::CAPABILITY,
			'customize.php'
		);

		// Server Info submenu (development only)
		if ( Helper::development() ) {
			add_submenu_page(
				'hda-settings',
				__( 'Server Info', HDA_TEXTDOMAIN ),
				__( 'Server Info', HDA_TEXTDOMAIN ),
				Plugin::CAPABILITY,
				'hda-server-info',
				$this->serverInfoCallback( ... )
			);
		}
	}

	// --------------------------------------------------

	/**
	 * Rename the auto-generated first submenu item from "HDA" to "Settings".
	 *
	 * WordPress auto-creates the first submenu item with the same title
	 * as the parent menu. This runs late (priority 999) to override it.
	 *
	 * @return void
	 */
	private function renameFirstSubmenu(): void {
		global $submenu;

		if ( ! empty( $submenu['hda-settings'] ) ) {
			$submenu['hda-settings'][0][0] = __( 'Settings', HDA_TEXTDOMAIN );
		}
	}

	// --------------------------------------------------

	/**
	 * AJAX handler for saving settings.
	 *
	 * @return void
	 */
	private function ajaxSubmitSettings(): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		// Security checks
		check_ajax_referer( '_wpnonce_settings_form_' . get_current_user_id() );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			Helper::toastError( __( 'You do not have permission to perform this action.', HDA_TEXTDOMAIN ), true );
		}

		try {
			// Sanitize input data
			$data = isset( $_POST['_data'] ) && is_array( $_POST['_data'] )
				? $this->sanitizeData( $_POST['_data'] )
				: [];

			// Process all registered settings handlers
			$this->processSettingsHandlers( $data );

			// Clear cache and send response
			Helper::clearAllCache();
			Helper::toastSuccess( __( 'Your settings have been saved.', HDA_TEXTDOMAIN ), true );
		} catch ( \Exception $e ) {

			// Log error and show user-friendly message
			Helper::errorLog( 'HDA Settings Save Error: ' . $e->getMessage() );
			Helper::toastError( __( 'An error occurred while saving settings.', HDA_TEXTDOMAIN ), true );
		}
	}

	// --------------------------------------------------

	/**
	 * Fields that contain raw HTML/JS/CSS content.
	 * These bypass sanitize_text_field() to preserve tags and newlines.
	 * Their handlers apply content-type-specific sanitization (extractJS/extractCss).
	 */
	private const array RAW_CONTENT_FIELDS = [
		CustomScript::KEY_HEADER,
		CustomScript::KEY_FOOTER,
		CustomScript::KEY_BODY_TOP,
		CustomScript::KEY_BODY_BOTTOM,
		CustomCss::KEY_FORM_CSS,
	];

	/**
	 * Sanitize data recursively with proper type handling.
	 *
	 * @param array $data Data to sanitize.
	 * @param bool $is_nested Whether this is a nested array (preserve keys).
	 *
	 * @return array
	 */
	private function sanitizeData( array $data, bool $is_nested = false ): array {
		$sanitized = [];

		foreach ( $data as $key => $value ) {

			// For nested arrays or numeric keys, preserve the key as-is
			// Only sanitize top-level string keys to avoid breaking array structures
			$sanitizedKey = ( $is_nested || is_numeric( $key ) ) ? $key : sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $sanitizedKey ] = $this->sanitizeData( $value, true );
			} elseif ( is_string( $value ) ) {

				// Raw content fields: preserve HTML tags and newlines.
				// Downstream handlers (updateStoredOption) apply proper sanitization.
				if ( in_array( $sanitizedKey, self::RAW_CONTENT_FIELDS, true ) ) {
					$sanitized[ $sanitizedKey ] = $value;
				} else {
					$sanitized[ $sanitizedKey ] = sanitize_text_field( $value );
				}
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $sanitizedKey ] = $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $sanitizedKey ] = (bool) $value;
			} else {
				$sanitized[ $sanitizedKey ] = $value;
			}
		}

		return $sanitized;
	}

	// --------------------------------------------------

	/**
	 * Main settings page callback.
	 *
	 * @return void
	 */
	private function menuCallback(): void {
		?>
		<div class="wrap" id="_container">
			<form role="form" id="_settings_form" method="post" accept-charset="UTF-8" enctype="multipart/form-data">

				<?php wp_nonce_field( '_wpnonce_settings_form_' . get_current_user_id() ); ?>

				<div id="main" class="filter-tabs clearfix">
					<?php include __DIR__ . '/options-menu.php'; ?>
					<?php include __DIR__ . '/options-content.php'; ?>
				</div>
			</form>
		</div>
		<?php
	}

	// --------------------------------------------------

	/**
	 * Server info page callback.
	 *
	 * @return void
	 */
	private function serverInfoCallback(): void {
		global $wpdb;

		$server_software = sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' );
		$user_agent      = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' );

		include __DIR__ . '/server-info.php';
	}

	// --------------------------------------------------
}
