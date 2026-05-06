<?php
/**
 * Security module - implements various security enhancements for WordPress.
 *
 * @author HD
 */

namespace HDAddons\Security;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Security\Firewall\Firewall;
use HDAddons\Helper;
use HDAddons\Security\ServerConfig\ServerConfig;
use HDAddons\Security\TrafficMonitor\TrafficMonitor;

\defined( 'ABSPATH' ) || exit;

final class Security implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME            = 'security__options';
	public const string KEY_COMMENTS_OFF       = 'comments_off';
	public const string KEY_XMLRPC_OFF         = 'xmlrpc_off';
	public const string KEY_HIDE_WP_VERSION    = 'hide_wp_version';
	public const string KEY_WP_LINKS_OPML_OFF  = 'wp_links_opml_off';
	public const string KEY_RSS_FEED_OFF       = 'rss_feed_off';
	public const string KEY_REMOVE_README      = 'remove_readme';

	public const string KEY_APP_PASSWORDS_OFF  = 'app_passwords_off';
	public const string KEY_SERVER_CONFIG      = 'server_config';
	public const string KEY_LOCK_FILES         = 'lock_files';

	/**
	 * Security configuration options.
	 */
	private array $securityOptions;

	/**
	 * Security settings (cached).
	 */
	private array $securitySettings;

	// ------------------------------------------------------

	/**
	 * Initialize security features based on settings.
	 */
	public function __construct() {
		$this->securityOptions  = Helper::getOption( self::OPTION_NAME, [] );
		$this->securitySettings = Helper::filterSettingOptions( 'security', [] );

		$comments_off        = $this->securityOptions[ self::KEY_COMMENTS_OFF ] ?? false;
		$xmlrpc_off          = $this->securityOptions[ self::KEY_XMLRPC_OFF ] ?? false;
		$hide_wp_version     = $this->securityOptions[ self::KEY_HIDE_WP_VERSION ] ?? false;
		$wp_links_opml_off   = $this->securityOptions[ self::KEY_WP_LINKS_OPML_OFF ] ?? false;
		$rss_feed_off        = $this->securityOptions[ self::KEY_RSS_FEED_OFF ] ?? false;
		$remove_readme       = $this->securityOptions[ self::KEY_REMOVE_README ] ?? false;
		$app_passwords_off   = $this->securityOptions[ self::KEY_APP_PASSWORDS_OFF ] ?? false;

		if ( $comments_off ) {
			( new Comment() )->disable();
		}

		if ( $xmlrpc_off ) {
			( new Xmlrpc() )->disable();
		}

		if ( $hide_wp_version ) {
			$this->hideVersion();
		}

		if ( $wp_links_opml_off ) {
			$this->disableOpml();
		}

		if ( $rss_feed_off ) {
			$this->disableRssFeed();
		}

		if ( $remove_readme ) {
			new Readme();
		}

		// Note: Author archives protection is handled by Firewall → ThreatDetector
		// (detectAuthorScan — blocks ?author=N at WAF pipeline level, earlier and more comprehensive).

		if ( $app_passwords_off ) {
			$this->disableAppPasswords();
		}

		// Restrict mode — only register hooks if theme config provides user IDs.
		if ( ! empty( $this->securitySettings ) ) {
			add_filter( 'all_plugins', $this->hidePluginInstall( ... ), 10 );
			add_filter( 'user_has_cap', $this->restrictPluginInstall( ... ), 10, 3 );
			add_filter( 'user_has_cap', $this->preventDeletionAccounts( ... ), 11, 3 );
			add_action( 'delete_user', $this->preventDeletionUser( ... ), 10 );
			add_action( 'pre_user_query', $this->hideUsers( ... ), 20 );
		}

		// ── Sub-modules (previously separate config.php entries) ──
		$this->initSecuritySubModules();
	}

	/**
	 * Load WAF Firewall and Security Log sub-modules.
	 *
	 * These were previously independent config.php modules but are now
	 * loaded under the Security umbrella for a cleaner menu structure.
	 *
	 * @return void
	 */
	private function initSecuritySubModules(): void {
		// WAF Firewall
		$firewallOptions = Helper::getOption( Firewall::OPTION_NAME, [] );
		if ( ! empty( $firewallOptions[ Firewall::KEY_ENABLED ] ) ) {
			new Firewall();
		}

		// Security Log (TrafficMonitor)
		$tmOptions = Helper::getOption( TrafficMonitor::OPTION_NAME, [] );
		if ( ! empty( $tmOptions[ TrafficMonitor::KEY_ENABLED ] ) ) {
			new TrafficMonitor();
		}

		// AccessControl (IP/Country blocking) — always load (handles server config + runtime fallback)
		new AccessControl();
	}

	// ------------------------------------------------------

	/**
	 * Hide specific plugins from the plugin list for non-authorized users.
	 *
	 * @param array $plugins List of plugins.
	 *
	 * @return array Filtered plugins list.
	 */
	public function hidePluginInstall( array $plugins ): array {
		$allowed_ids = $this->securitySettings['allowed_users_ids_show_plugins'] ?? [];

		if ( ! is_array( $allowed_ids ) ) {
			$allowed_ids = [];
		}

		$user_id = get_current_user_id();

		if ( ! in_array( $user_id, $allowed_ids, true ) ) {
			$target_plugins = [ HDA_PLUGIN_BASENAME ];
			foreach ( $target_plugins as $target_plugin ) {
				unset( $plugins[ $target_plugin ] );
			}
		}

		return $plugins;
	}

	/**
	 * Hide protected accounts from the Users screen.
	 *
	 * @param \WP_User_Query $query User query object.
	 *
	 * @return void
	 */
	public function hideUsers( \WP_User_Query $query ): void {
		if ( 'users.php' !== ( $GLOBALS['pagenow'] ?? '' ) || ! is_admin() ) {
			return;
		}

		$hidden_ids = $this->securitySettings['disallowed_users_ids_delete_account'] ?? [];

		if ( empty( $hidden_ids ) || ! is_array( $hidden_ids ) ) {
			return;
		}

		$user_id    = get_current_user_id();
		$hidden_ids = array_map( 'absint', $hidden_ids );

		if ( ! in_array( $user_id, $hidden_ids, true ) ) {
			// Use WP_User_Query API instead of direct SQL manipulation
			$existing_exclude = $query->get( 'exclude' ) ?: [];
			$query->set( 'exclude', array_merge( (array) $existing_exclude, $hidden_ids ) );
		}
	}

	/**
	 * Prevent deletion of protected user accounts.
	 *
	 * @param int $user_id User ID being deleted.
	 *
	 * @return void
	 */
	public function preventDeletionUser( int $user_id ): void {
		$hidden_ids = $this->securitySettings['disallowed_users_ids_delete_account'] ?? [];

		if ( ! is_array( $hidden_ids ) ) {
			$hidden_ids = [];
		}

		if ( in_array( $user_id, $hidden_ids, true ) ) {
			wp_die(
				__( 'You cannot delete this admin account.', HDA_TEXTDOMAIN ),
				__( 'Error', HDA_TEXTDOMAIN ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Remove caps for editing/deleting protected accounts.
	 *
	 * @param array $allcaps User capabilities.
	 * @param array $cap Capability being checked.
	 * @param array $args Additional arguments.
	 *
	 * @return array Modified capabilities.
	 */
	public function preventDeletionAccounts( array $allcaps, array $cap, array $args ): array {
		$hidden_ids = $this->securitySettings['disallowed_users_ids_delete_account'] ?? [];

		if ( ! is_array( $hidden_ids ) ) {
			$hidden_ids = [];
		}

		if ( isset( $cap[0] ) && in_array( $cap[0], [ 'delete_users', 'edit_users' ], true ) ) {
			$user_id_to_delete = $args[2] ?? 0;
			if ( $user_id_to_delete && in_array( $user_id_to_delete, $hidden_ids, true ) ) {
				unset( $allcaps['delete_users'], $allcaps['edit_users'] );
			}
		}

		return $allcaps;
	}

	/**
	 * Restrict plugin installation for non-authorized users.
	 *
	 * @param array $allcaps User capabilities.
	 * @param array $caps Capabilities being checked.
	 * @param array $args Additional arguments.
	 *
	 * @return array Modified capabilities.
	 */
	public function restrictPluginInstall( array $allcaps, array $caps, array $args ): array {
		$allowed_ids = $this->securitySettings['allowed_users_ids_install_plugins'] ?? [];

		if ( ! is_array( $allowed_ids ) ) {
			$allowed_ids = [];
		}

		$user_id = get_current_user_id();

		if ( $user_id && in_array( $user_id, $allowed_ids, true ) ) {
			return $allcaps;
		}

		if ( isset( $allcaps['activate_plugins'] ) ) {
			unset( $allcaps['install_plugins'], $allcaps['delete_plugins'] );
		}

		if ( isset( $allcaps['install_themes'] ) ) {
			unset( $allcaps['install_themes'] );
		}

		return $allcaps;
	}

	/**
	 * Redirect feed requests to homepage.
	 *
	 * @return void
	 */
	public function disableFeed(): void {
		Helper::redirect( trailingslashit( esc_url( network_home_url() ) ) );
	}

	// ------------------------------------------------------
	// Private methods
	// ------------------------------------------------------

	/**
	 * Disable RSS and ATOM feeds.
	 *
	 * @return void
	 */
	private function disableRssFeed(): void {
		add_action( 'do_feed', $this->disableFeed( ... ), 1 );
		add_action( 'do_feed_rdf', $this->disableFeed( ... ), 1 );
		add_action( 'do_feed_rss', $this->disableFeed( ... ), 1 );
		add_action( 'do_feed_rss2', $this->disableFeed( ... ), 1 );
		add_action( 'do_feed_atom', $this->disableFeed( ... ), 1 );
		add_action( 'do_feed_rss2_comments', $this->disableFeed( ... ), 1 );
		add_action( 'do_feed_atom_comments', $this->disableFeed( ... ), 1 );

		remove_action( 'wp_head', 'feed_links_extra', 3 ); // Remove comments feed
		remove_action( 'wp_head', 'feed_links', 2 );
	}

	/**
	 * Hide WordPress version from meta tags and admin footer.
	 *
	 * Note: Script/style version stripping is owned by Performance module
	 * (Optimize → KEY_ENABLE_CLEANUP). This method only handles:
	 * - Admin footer version text
	 * - RSS/HTML generator meta tag
	 *
	 * @return void
	 */
	private function hideVersion(): void {
		add_filter( 'update_footer', '__return_empty_string', 11 );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	/**
	 * Block access to wp-links-opml.php.
	 *
	 * @return void
	 */
	private function disableOpml(): void {
		add_action(
			'init',
			static function () {
				$request_uri = isset( $_SERVER['REQUEST_URI'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
				: '';

				if ( str_contains( $request_uri, 'wp-links-opml.php' ) ) {
					status_header( 403 );
					exit;
				}
			}
		);
	}



	/**
	 * Disable Application Passwords feature (WP 5.6+).
	 *
	 * @return void
	 */
	private function disableAppPasswords(): void {
		// Disable the Application Passwords feature entirely
		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		// Remove the Application Passwords section from user profile
		add_action(
			'admin_init',
			static function () {
				remove_action( 'show_user_profile', 'wp_user_application_passwords_list' );
				remove_action( 'edit_user_profile', 'wp_user_application_passwords_list' );
			}
		);
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'security-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$fields = [
			self::KEY_COMMENTS_OFF,
			self::KEY_XMLRPC_OFF,
			self::KEY_HIDE_WP_VERSION,
			self::KEY_WP_LINKS_OPML_OFF,
			self::KEY_RSS_FEED_OFF,
			self::KEY_REMOVE_README,
			self::KEY_APP_PASSWORDS_OFF,
			self::KEY_SERVER_CONFIG,
			self::KEY_LOCK_FILES,
		];

		$options = self::extractFields( $data, $fields );
		self::saveOrRemove( self::OPTION_NAME, $options );

		// Set default_ping_status to closed when XML-RPC is disabled.
		if ( ! empty( $options[ self::KEY_XMLRPC_OFF ] ) && 'closed' !== Helper::getOption( 'default_ping_status' ) ) {
			Helper::updateOption( 'default_ping_status', 'closed' );
		}

		// Remove readme.html if option is set.
		if ( isset( $options[ self::KEY_REMOVE_README ] ) ) {
			try {
				( new Readme() )->deleteReadme();
			} catch ( \Exception $e ) {
				Helper::errorLog( 'HDA: Failed to delete readme.html - ' . $e->getMessage() );
			}
		}

		// Manage server config blocks — runs BEFORE file lock.
		self::handleServerConfigBlock( $data );

		// Manage file permissions lock/unlock — runs AFTER server config.
		self::handleFileLock( $data );

		// ── Access Control (WAF) ─────────────────────
		$blocked = [];
		if ( ! empty( $data['blocked_countries'] ) && is_array( $data['blocked_countries'] ) ) {
			$blocked = array_map( 'sanitize_text_field', $data['blocked_countries'] );
		}

		$blocked_ips = [];
		if ( ! empty( $data['waf_blocked_ips'] ) && is_array( $data['waf_blocked_ips'] ) ) {
			$blocked_ips = array_map( 'sanitize_text_field', $data['waf_blocked_ips'] );
			$blocked_ips = array_values( array_unique( array_filter( $blocked_ips ) ) );
		}

		// Country blocking mode.
		$country_mode = sanitize_text_field( $data['country_mode'] ?? 'block_selected' );
		if ( ! in_array( $country_mode, [ 'block_selected', 'allow_selected' ], true ) ) {
			$country_mode = 'block_selected';
		}

		$waf_options = [
			AccessControl::KEY_BLOCKED_COUNTRIES => $blocked,
			AccessControl::KEY_COUNTRY_MODE      => $country_mode,
			AccessControl::KEY_BLOCK_UNKNOWN      => ! empty( $data['block_unknown_countries'] ),
			AccessControl::KEY_BLOCKED_IPS       => $blocked_ips,
		];

		self::saveOrRemove( AccessControl::OPTION_NAME, $waf_options, true );

		AccessControl::updateIpBlockConfig( $blocked_ips );

		// ── Delegate to sub-module save handlers ─────
		// Use hidden field presence (always submitted) — NOT checkbox value,
		// otherwise unchecking the "Enable" checkbox won't trigger save.
		if ( isset( $data[ Firewall::getFormKey() ] ) ) {
			Firewall::sanitizeAndSave( $data );
		}

		if ( isset( $data[ TrafficMonitor::getFormKey() ] ) ) {
			TrafficMonitor::sanitizeAndSave( $data );
		}
	}

	// ─── Private helpers for save ───────────────────

	/**
	 * Handle server config blocks add/remove based on checkbox states.
	 *
	 * @param array $data Form data.
	 */
	private static function handleServerConfigBlock( array $data ): void {
		self::toggleServerBlock(
			! empty( $data[ self::KEY_SERVER_CONFIG ] ),
			ServerConfig::MARKER,
			'htaccess.tpl',
			'nginx.conf'
		);

		self::toggleServerBlock(
			! empty( $data[ self::KEY_XMLRPC_OFF ] ),
			ServerConfig::XMLRPC_MARKER,
			'xmlrpc-htaccess.tpl',
			'xmlrpc-nginx.conf'
		);

		self::toggleServerBlock(
			! empty( $data[ self::KEY_WP_LINKS_OPML_OFF ] ),
			ServerConfig::OPML_MARKER,
			'opml-htaccess.tpl',
			'opml-nginx.conf'
		);
	}

	/**
	 * Toggle a single server config block on or off.
	 *
	 * @param bool   $enabled     Whether to add or remove the block.
	 * @param string $marker      Marker name.
	 * @param string $htaccessTpl Htaccess template filename.
	 * @param string $nginxTpl    Nginx template filename.
	 */
	private static function toggleServerBlock( bool $enabled, string $marker, string $htaccessTpl, string $nginxTpl ): void {
		try {
			$result = $enabled
				? ServerConfig::addBlock( $marker, $htaccessTpl, $nginxTpl )
				: ServerConfig::removeBlock( $marker );

			if ( is_string( $result ) ) {
				Helper::errorLog( "[HDA] ServerConfig [{$marker}]: " . $result );
			}
		} catch ( \Exception $e ) {
			Helper::errorLog( "[HDA] ServerConfig [{$marker}] error: " . $e->getMessage() );
		}
	}

	/**
	 * Handle file permission lock/unlock based on checkbox state.
	 *
	 * @param array $data Form data.
	 */
	private static function handleFileLock( array $data ): void {
		$enabled = ! empty( $data[ self::KEY_LOCK_FILES ] );

		try {
			$results = $enabled ? ServerConfig::lockFiles() : ServerConfig::unlockFiles();

			foreach ( $results as $label => $result ) {
				if ( is_string( $result ) ) {
					Helper::errorLog( "[HDA] FileLock [{$label}]: " . $result );
				}
			}
		} catch ( \Exception $e ) {
			Helper::errorLog( '[HDA] FileLock error: ' . $e->getMessage() );
		}
	}
}

