<?php
/**
 * Maintenance module - Restrict frontend access during maintenance.
 *
 * When enabled, non-privileged visitors see a maintenance page.
 * Admins, allowlisted IPs, and allowlisted user roles can bypass.
 *
 * @package HDAddons\Maintenance
 * @author  HD
 */

namespace HDAddons\Maintenance;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class Maintenance implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME         = 'maintenance__options';
	public const string KEY_ENABLED         = 'enabled';
	public const string KEY_TITLE           = 'title';
	public const string KEY_MESSAGE         = 'message';
	public const string KEY_ALLOWLIST_IPS   = 'allowlist_ips';
	public const string KEY_ALLOWLIST_ROLES = 'allowlist_roles';

	/**
	 * Cached module options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ------------------------------------------------------

	/**
	 * Initialize maintenance mode.
	 */
	public function __construct() {
		$options = self::getOptions();

		if ( empty( $options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// Admin notice.
		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action( 'admin_notices', self::adminNotice( ... ) );

			return;
		}

		// Skip cron, AJAX, REST API, and CLI.
		if (
			wp_doing_cron()
			|| wp_doing_ajax()
			|| ( defined( 'REST_REQUEST' ) && \REST_REQUEST )
			|| ( defined( 'WP_CLI' ) && \WP_CLI )
		) {
			return;
		}

		add_action( 'template_redirect', $this->maybeShowMaintenancePage( ... ), 0 );
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
	 * Show admin bar notice when maintenance mode is active.
	 *
	 * @return void
	 */
	public static function adminNotice(): void {
		echo '<div class="notice notice-warning" style="border-left-color:#f0b849;">';
		echo '<p><strong>🚧 ' . esc_html__( 'Maintenance mode is active.', HDA_TEXTDOMAIN ) . '</strong> ';
		echo esc_html__( 'The frontend is only accessible to administrators and allowlisted IPs/roles.', HDA_TEXTDOMAIN );
		echo '</p></div>';
	}

	// ------------------------------------------------------

	/**
	 * Show maintenance page if visitor is not allowed.
	 *
	 * @return void
	 */
	public function maybeShowMaintenancePage(): void {
		if ( $this->isVisitorAllowed() ) {
			return;
		}

		$options = self::getOptions();
		$message = $options[ self::KEY_MESSAGE ] ?? '';
		$title   = $options[ self::KEY_TITLE ] ?? __( 'Under Maintenance', HDA_TEXTDOMAIN );

		if ( empty( $message ) ) {
			$message = __( 'We are currently performing scheduled maintenance. We\'ll be back soon. Thank you for your patience.', HDA_TEXTDOMAIN );
		}

		// Set 503 header.
		status_header( 503 );
		header( 'Retry-After: 3600' );
		nocache_headers();

		$this->renderMaintenancePage( $title, $message );
		exit;
	}

	// ------------------------------------------------------

	/**
	 * Check if the current visitor is allowed to bypass maintenance mode.
	 *
	 * @return bool
	 */
	private function isVisitorAllowed(): bool {
		// Allow wp-login.php and wp-admin URLs.
		$requestUri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		if ( str_contains( $requestUri, 'wp-login.php' ) || str_contains( $requestUri, 'wp-admin' ) ) {
			return true;
		}

		// Logged-in administrators always bypass.
		if ( is_user_logged_in() && current_user_can( Plugin::CAPABILITY ) ) {
			return true;
		}

		$options = self::getOptions();

		// Allowlisted IPs (supports exact, CIDR, and dash ranges).
		$allowlistIps = $options[ self::KEY_ALLOWLIST_IPS ] ?? [];
		if ( ! empty( $allowlistIps ) ) {
			$ip = Helper::ipAddress();
			if ( $ip && Helper::ipMatchesAny( $ip, $allowlistIps ) ) {
				return true;
			}
		}

		// Allowlisted user roles.
		$allowlistRoles = $options[ self::KEY_ALLOWLIST_ROLES ] ?? [];
		if ( ! empty( $allowlistRoles ) && is_user_logged_in() ) {
			$roles = wp_get_current_user()->roles;

			if ( ! empty( array_intersect( $roles, $allowlistRoles ) ) ) {
				return true;
			}
		}

		return false;
	}

	// ------------------------------------------------------

	/**
	 * Render the maintenance page HTML.
	 *
	 * @param string $title   Page title.
	 * @param string $message Maintenance message.
	 *
	 * @return void
	 */
	private function renderMaintenancePage( string $title, string $message ): void {
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $title ); ?> — <?php bloginfo( 'name' ); ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen,Ubuntu,sans-serif;background:#1a1a2e;color:#e0e0e0}
.maintenance-wrap{max-width:580px;padding:48px 32px;text-align:center}
.maintenance-icon{font-size:64px;margin-bottom:24px;display:block}
h1{font-size:28px;font-weight:700;margin-bottom:16px;color:#fff}
p{font-size:16px;line-height:1.7;color:#b0b0c0}
</style>
</head>
<body>
<div class="maintenance-wrap">
	<span class="maintenance-icon">🚧</span>
	<h1><?php echo esc_html( $title ); ?></h1>
	<p><?php echo wp_kses_post( $message ); ?></p>
</div>
</body>
</html>
		<?php
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'maintenance-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$allowlistIps = [];
		if ( ! empty( $data['mt_allowlist_ips'] ) ) {
			$allowlistIps = array_filter( array_map( 'sanitize_text_field', (array) $data['mt_allowlist_ips'] ) );
		}

		$allowlistRoles = [];
		if ( ! empty( $data['mt_allowlist_roles'] ) ) {
			$allowlistRoles = array_map( 'sanitize_key', (array) $data['mt_allowlist_roles'] );
		}

		$options = [
			self::KEY_ENABLED         => ! empty( $data['mt_enabled'] ),
			self::KEY_TITLE           => isset( $data['mt_title'] ) ? sanitize_text_field( $data['mt_title'] ) : '',
			self::KEY_MESSAGE         => isset( $data['mt_message'] ) ? wp_kses_post( $data['mt_message'] ) : '',
			self::KEY_ALLOWLIST_IPS   => $allowlistIps,
			self::KEY_ALLOWLIST_ROLES => $allowlistRoles,
		];

		self::saveOrRemove( self::OPTION_NAME, $options, true );
	}
}
