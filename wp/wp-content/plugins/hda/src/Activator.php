<?php
/**
 * Plugin activation, deactivation, and uninstall handlers.
 *
 * Uses data-driven registries so adding a new module requires
 * only one entry in getTableClasses() or getCronHooks().
 *
 * @author HD
 */

namespace HDAddons;

use HDAddons\GlobalSetting\GlobalSetting;
use HDAddons\LoginSecurity\ActivityLog\ActivityLog;
use HDAddons\LoginSecurity\MagicLink\MagicLinkHandler;
use HDAddons\LoginSecurity\Totp\TotpHandler;
use HDAddons\Monitor404\Monitor404;
use HDAddons\Security\ServerConfig\ServerConfig;
use HDAddons\Security\TrafficMonitor\TrafficLogger;

\defined( 'ABSPATH' ) || exit;

final class Activator {

	// ─── Registries ─────────────────────────────────────

	/**
	 * Classes that own custom DB tables.
	 *
	 * Each class MUST expose public static createTable() and dropTable().
	 * Adding a new module with a table? → Add one line here.
	 *
	 * @return class-string[]
	 */
	private static function getTableClasses(): array {
		return [
			ActivityLog::class,
			Monitor404::class,
			TotpHandler::class,
			MagicLinkHandler::class,
			TrafficLogger::class,
		];
	}

	/**
	 * All cron hooks registered by the plugin.
	 *
	 * Used on deactivation to unschedule every hook in one pass.
	 * Adding a new cron? → Add its hook name here.
	 *
	 * @return string[]
	 */
	private static function getCronHooks(): array {
		return [
			'hda_activity_log_cleanup',
			'hda_404_log_cleanup',
			'hda_db_optimizer_cleanup',
			'hda_traffic_log_cleanup',
			'hda_file_integrity_scan',
			'hda_threat_intel_sync',
		];
	}

	// ─── Lifecycle Handlers ─────────────────────────────

	/**
	 * Run on plugin activation.
	 */
	public static function activation(): void {
		// Add HDA capability to administrator and editor roles.
		Plugin::addCapability();

		// Create all module tables.
		foreach ( self::getTableClasses() as $class ) {
			$class::createTable();
		}

		// Flush rewrite rules if needed.
		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivation(): void {
		// Unschedule all cron hooks.
		foreach ( self::getCronHooks() as $hook ) {
			wp_unschedule_hook( $hook );
		}

		// Unlock critical files first (so .htaccess is writable for block removal).
		try {
			ServerConfig::unlockFiles();
		} catch ( \Exception $e ) {
			Helper::errorLog( '[HDA] Deactivation: Failed to unlock files - ' . $e->getMessage() );
		}

		// Remove server config blocks (htaccess / nginx).
		try {
			ServerConfig::removeBlock( ServerConfig::MARKER );
			ServerConfig::removeBlock( ServerConfig::XMLRPC_MARKER );
			ServerConfig::removeBlock( ServerConfig::OPML_MARKER );
		} catch ( \Exception $e ) {
			Helper::errorLog( '[HDA] Deactivation: Failed to remove server config - ' . $e->getMessage() );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Run on plugin uninstall.
	 * Behavior controlled by the "clean_uninstall" option in GlobalSetting.
	 * If disabled, all data is preserved for future reinstall.
	 */
	public static function uninstall(): void {
		// Check if user opted to clean data on uninstall.
		$cleanUninstall = Helper::getOption( GlobalSetting::KEY_CLEAN_UNINSTALL, false );

		if ( ! $cleanUninstall ) {
			return; // Preserve all data for future reinstall.
		}

		// ── Drop custom tables ──────────────────────────
		foreach ( self::getTableClasses() as $class ) {
			$class::dropTable();
		}

		// ── Delete all module options ────────────────────
		$optionMap = GlobalSetting::getModuleOptionMap();

		foreach ( $optionMap as $slug => $optionNames ) {
			// StoredOption modules use custom post storage instead of wp_options.
			$isStoredOption = in_array( $slug, GlobalSetting::STORED_OPTION_MODULES, true );

			foreach ( $optionNames as $optionName ) {
				if ( $isStoredOption ) {
					// Delete StoredOption posts + lookup options.
					self::deleteStoredOptionDirect( $optionName );
				} else {
					Helper::removeOption( $optionName );
				}
			}
		}

		// ── Delete GlobalSetting own options ─────────────
		Helper::removeOption( GlobalSetting::OPTION_NAME );
		Helper::removeOption( GlobalSetting::KEY_CLEAN_UNINSTALL );
		Helper::removeOption( GlobalSetting::KEY_KNOWN_MODULES );
		Helper::removeOption( Plugin::KEY_CAP_VERSION );

		// ── Delete all hda_so_id_* lookup options ────────
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hda_so_id_%'" );

		// ── Delete all hda_storage posts ─────────────────
		$storagePosts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
				'hda_storage'
			)
		);

		foreach ( $storagePosts as $postId ) {
			wp_delete_post( (int) $postId, true );
		}

		// ── Delete HDA transients ────────────────────────
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_hda\\_%'
			    OR option_name LIKE '_transient_timeout_hda\\_%'"
		);

		// ── Remove capabilities from roles ───────────────
		Plugin::removeCapability();
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Delete a stored option post directly.
	 *
	 * @param string $optionKey
	 *
	 * @return void
	 */
	private static function deleteStoredOptionDirect( string $optionKey ): void {
		$postId = Helper::getOption( "hda_so_id_{$optionKey}", 0 );

		if ( $postId > 0 ) {
			wp_delete_post( (int) $postId, true );
			Helper::removeOption( "hda_so_id_{$optionKey}" );
		}
	}
}
