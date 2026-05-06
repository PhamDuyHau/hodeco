<?php
/**
 * Plugin detection utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Plugin {

	/**
	 * Cache flag for plugin functions loaded.
	 */
	private static bool $pluginFunctionsLoaded = false;

	// --------------------------------------------------

	/**
	 * Ensure plugin functions are loaded (cached).
	 */
	private static function ensurePluginFunctions(): void {
		if ( self::$pluginFunctionsLoaded ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		self::$pluginFunctionsLoaded = true;
	}

	// --------------------------------------------------

	/**
	 * Check if plugin is active.
	 *
	 * @param string $pluginFile
	 *
	 * @return bool
	 */
	public static function checkPluginActive( string $pluginFile ): bool {
		self::ensurePluginFunctions();

		if ( is_multisite() && is_plugin_active_for_network( $pluginFile ) ) {
			return true;
		}

		return is_plugin_active( $pluginFile );
	}

	// --------------------------------------------------

	/**
	 * Check if Classic Editor is active.
	 *
	 * @return bool
	 */
	public static function isClassicEditorActive(): bool {
		return class_exists( 'Classic_Editor' )
				|| self::checkPluginActive( 'classic-editor/classic-editor.php' );
	}

	// --------------------------------------------------

	/**
	 * Check if ACF Pro is active.
	 *
	 * @return bool
	 */
	public static function isAcfProActive(): bool {
		if ( defined( 'ACF_PRO' ) || class_exists( 'acf_pro' ) ) {
			return true;
		}

		return self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' );
	}
}
