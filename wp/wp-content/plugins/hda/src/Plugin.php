<?php
/**
 * Plugin Class - Main orchestrator for loading modules and third-party integrations.
 *
 * @author HD
 */

namespace HDAddons;

use HDAddons\ThirdParty\Faker;
use HDAddons\Updater\GitHubUpdater;

\defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Custom capability for managing HDA settings. */
	public const string CAPABILITY = 'hda_manage_options';

	/** Option key to track capability version. */
	public const string KEY_CAP_VERSION = 'hda_capability_version';

	// -------------------------------------------------------------

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		// Register hidden post type for option storage
		add_action( 'init', Helper::registerStoragePostType( ... ), 0 );

		// Register custom cron schedules (only if not already provided by theme)
		add_filter( 'cron_schedules', static function ( array $schedules ): array {
			if ( ! isset( $schedules['weekly'] ) ) {
				$schedules['weekly'] = [
					'interval' => 7 * DAY_IN_SECONDS,
					'display'  => __( 'Once Weekly', HDA_TEXTDOMAIN ),
				];
			}

			if ( ! isset( $schedules['monthly'] ) ) {
				$schedules['monthly'] = [
					'interval' => 30 * DAY_IN_SECONDS,
					'display'  => __( 'Once Monthly', HDA_TEXTDOMAIN ),
				];
			}

			return $schedules;
		} );

		// Load modules immediately (we're already in plugins_loaded context)
		$this->loadModules();

		// Classic Editor: prevent duplicate settings registration
		if ( class_exists( 'Classic_Editor' ) && Helper::isClassicEditorActive() ) {
			remove_action( 'admin_init', [ 'Classic_Editor', 'register_settings' ] );
		}

		if ( is_admin() && ! wp_doing_cron() ) {
			// GitHub auto-update (admin context, including AJAX for update process)
			new GitHubUpdater();

			// Sync capability once per plugin version.
			add_action( 'admin_init', self::maybeAddCapability(...) );

			// Emergency bypass warning
			$this->maybeShowEmergencyBypassNotice();
		}

		// Admin assets
		add_action( 'admin_enqueue_scripts', $this->adminEnqueueAssets( ... ), 39 );

		// Script tag attribute injection
		add_filter( 'script_loader_tag', $this->scriptLoaderTag( ... ), 11, 3 );

		// Hook into theme's cache clearing to handle cache plugins
		add_action( 'hd_clear_all_cache', Helper::clearCachePlugins( ... ) );
	}

	// -------------------------------------------------------------
	// Capability Management
	// -------------------------------------------------------------

	/**
	 * Add capability to roles.
	 *
	 * Assigns `hda_manage_options` to administrator and editor roles.
	 * Called on activation and when plugin version changes.
	 *
	 * @return void
	 */
	public static function addCapability(): void {
		$roles = apply_filters( 'hda_manage_roles', [ 'administrator', 'editor' ] );

		foreach ( $roles as $roleName ) {
			$role = get_role( $roleName );
			$role?->add_cap( self::CAPABILITY );
		}
	}

	/**
	 * Remove capability from all roles.
	 *
	 * Called on plugin uninstall.
	 *
	 * @return void
	 */
	public static function removeCapability(): void {
		$roles = [ 'administrator', 'editor' ];

		foreach ( $roles as $roleName ) {
			$role = get_role( $roleName );
			$role?->remove_cap( self::CAPABILITY );
		}
	}

	/**
	 * Add capability once per plugin version (not every admin_init).
	 *
	 * @return void
	 */
	private static function maybeAddCapability(): void {
		$storedVersion = Helper::getOption( self::KEY_CAP_VERSION, '' );

		if ( $storedVersion === HDA_VERSION ) {
			return;
		}

		self::addCapability();
		Helper::updateOption( self::KEY_CAP_VERSION, HDA_VERSION );
	}

	// -------------------------------------------------------------

	/**
	 * Show admin notice when emergency login security bypass is active.
	 *
	 * @return void
	 */
	private function maybeShowEmergencyBypassNotice(): void {
		$bypassOtp      = defined( 'HDA_DISABLE_OTP' ) && \HDA_DISABLE_OTP;
		$bypassSecurity = defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY;
		$bypassCaptcha  = defined( 'HDA_DISABLE_LOGIN_CAPTCHA' ) && \HDA_DISABLE_LOGIN_CAPTCHA;
		$bypassFirewall = defined( 'HDA_DISABLE_FIREWALL' ) && \HDA_DISABLE_FIREWALL;

		if ( ! $bypassOtp && ! $bypassSecurity && ! $bypassCaptcha && ! $bypassFirewall ) {
			return;
		}

		add_action( 'admin_notices', static function () use ( $bypassOtp, $bypassSecurity, $bypassCaptcha, $bypassFirewall ) {
			$messages = [];

			if ( $bypassSecurity ) {
				$messages[] = __( '<code>HDA_DISABLE_LOGIN_SECURITY</code> is active - ALL login security features are bypassed!', HDA_TEXTDOMAIN );
			} elseif ( $bypassOtp ) {
				$messages[] = __( '<code>HDA_DISABLE_OTP</code> is active - OTP verification is bypassed!', HDA_TEXTDOMAIN );
			}

			if ( $bypassCaptcha ) {
				$messages[] = __( '<code>HDA_DISABLE_LOGIN_CAPTCHA</code> is active - Login CAPTCHA is bypassed!', HDA_TEXTDOMAIN );
			}

			if ( $bypassFirewall ) {
				$messages[] = __( '<code>HDA_DISABLE_FIREWALL</code> is active - Firewall is bypassed!', HDA_TEXTDOMAIN );
			}

			echo '<div class="notice notice-error" style="border-left-color:#dc3232;">';
			echo '<p><strong>⚠️ ' . esc_html__( 'HDA Security Warning:', HDA_TEXTDOMAIN ) . '</strong></p>';
			foreach ( $messages as $msg ) {
				echo '<p>' . wp_kses( $msg, [ 'code' => [] ] ) . '</p>';
			}
			echo '<p>' . esc_html__( 'Remember to remove these settings from your .env file after recovery!', HDA_TEXTDOMAIN ) . '</p>';
			echo '</div>';
		} );
	}

	// -------------------------------------------------------------

	/**
	 * Load modules and initialize third-party integrations.
	 * Only modules enabled via GlobalSetting checkboxes are initialized.
	 * Disabled modules are NOT loaded — their options are preserved in DB for re-activation.
	 */
	private function loadModules(): void {
		// GlobalSetting always loads first — it controls enablement of other modules.
		$this->initModule( 'global_setting', GlobalSetting\GlobalSetting::class );

		// Get enabled module states from DB (checkbox values saved by GlobalSetting)
		$enabledModules = Helper::getOption(
			GlobalSetting\GlobalSetting::OPTION_NAME, []
		);

		// Load each registered module if enabled
		foreach ( self::getModuleClassMap() as $slug => $classFQN ) {

			// Skip modules not enabled in GlobalSetting checkboxes
			if ( empty( $enabledModules[ $slug ] ) ) {
				continue;
			}

			$this->initModule( $slug, $classFQN );
		}

		// Third-party integrations
		$this->loadThirdPartyIntegrations();

		// Clean up options for modules removed from config.php (admin only)
		if ( is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
			GlobalSetting\GlobalSetting::cleanupOrphanedModules();
		}
	}

	// -------------------------------------------------------------

	/**
	 * Explicit map of config slug → FQCN.
	 *
	 * Single source of truth for module class resolution.
	 * Adding a new module: (1) add entry to config.php (2) add entry here.
	 *
	 * @return array<string, class-string>
	 */
	private static function getModuleClassMap(): array {
		return [
			'aspect_ratio'      => AspectRatio\AspectRatio::class,
			'editor'            => Editor\Editor::class,
			'seo'               => Seo\Seo::class,
			'security'          => Security\Security::class,
			'login_security'    => LoginSecurity\LoginSecurity::class,
			'optimize'          => Optimize\Optimize::class,
			'social_link'       => SocialLink\SocialLink::class,
			'contact_link'      => ContactLink\ContactLink::class,
			'cookie_consent'    => CookieConsent\CookieConsent::class,
			'file'              => File\File::class,
			'custom_sorting'    => CustomSorting\CustomSorting::class,
			'scheduled_content' => ScheduledContent\ScheduledContent::class,
			'post_type_archive' => PostTypeArchive\PostTypeArchive::class,
			'recaptcha'         => Recaptcha\Recaptcha::class,
			'redirect'          => Redirect\Redirect::class,
			'monitor_404'       => Monitor404\Monitor404::class,
			'cron_manager'      => CronManager\CronManager::class,
			'maintenance'       => Maintenance\Maintenance::class,
			'custom_code'       => CustomCode\CustomCode::class,
		];
	}

	// -------------------------------------------------------------

	/**
	 * Initialize a single module by slug.
	 *
	 * @param string       $slug     Module slug from config.
	 * @param class-string $classFQN Fully qualified class name.
	 */
	private function initModule( string $slug, string $classFQN ): void {
		if ( ! class_exists( $classFQN ) ) {
			return;
		}

		try {
			new $classFQN();
		} catch ( \Throwable $e ) {
			Helper::errorLog( "[HDA] Failed to load module '{$slug}': " . $e->getMessage() );
		}
	}

	// -------------------------------------------------------------

	/**
	 * Load third-party plugin integrations.
	 */
	private function loadThirdPartyIntegrations(): void {
		if ( class_exists( Faker::class ) ) {
			new Faker();
		}
	}

	// -------------------------------------------------------------

	/**
	 * Inject extra attributes (defer, module, etc.) to script tags.
	 *
	 * @param string $tag The script tag HTML.
	 * @param string $handle The script handle.
	 * @param string $src The script source URL.
	 *
	 * @return string Modified script tag.
	 */
	public function scriptLoaderTag( string $tag, string $handle, string $src ): string {
		$scripts = wp_scripts();
		$reg     = $scripts->registered[ $handle ] ?? null;

		if ( ! $reg || empty( $reg->extra['hda'] ) ) {
			return $tag;
		}

		$extras = is_array( $reg->extra['hda'] )
			? $reg->extra['hda']
			: explode( ' ', (string) $reg->extra['hda'] );

		foreach ( $extras as $attr ) {
			$attr = trim( $attr );
			if ( empty( $attr ) ) {
				continue;
			}

			if ( 'module' === $attr ) {
				// Add type="module" if not present
				if ( ! str_contains( $tag, 'type=' ) ) {
					$tag = str_replace( ' src=', ' type="module" src=', $tag );
				}
			} elseif ( ! preg_match( "#\\s{$attr}(=|>|\\s|$)#", $tag ) ) {
				// Add other attributes (defer, async, etc.)
				$tag = str_replace( ' src=', " {$attr} src=", $tag );
			}
		}

		return $tag;
	}

	// -------------------------------------------------------------

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function adminEnqueueAssets( string $hook ): void {
		// Enqueue vendor CSS first.
		$vendorCssHandle = Asset::enqueueVendorCSS();

		// Global admin assets
		Asset::enqueueCSS( 'admin.scss', $vendorCssHandle ? [ $vendorCssHandle ] : [] );
		Asset::enqueueJS( 'admin.js', [ 'jquery-core', 'jquery-ui-sortable' ], null, true, [ 'module', 'defer' ] );

		// Addon settings pages only
		$allowed_pages = [
			'toplevel_page_hda-settings',
			'hda_page_hda-server-info',
			'hda_page_hda-file-integrity',
		];

		if ( ! in_array( $hook, $allowed_pages, true ) ) {
			return;
		}

		// Color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		Asset::enqueueCSS( 'hda.scss', $vendorCssHandle ? [ $vendorCssHandle ] : [] );
		Asset::enqueueJS( 'hda.js', [ 'wp-color-picker' ], null, true, [ 'module', 'defer' ] );

		// CodeMirror
		wp_enqueue_style( 'wp-codemirror' );

		$hdaHandle = Asset::handle( 'hda.js' );
		if ( $hdaHandle ) {
			$l10n = [
				'codemirror_css'  => wp_enqueue_code_editor( [ 'type' => 'text/css' ] ),
				'codemirror_html' => wp_enqueue_code_editor( [ 'type' => 'text/html' ] ),
			];
			Asset::localize( $hdaHandle, 'codemirror_settings', $l10n );
		}
	}
}
