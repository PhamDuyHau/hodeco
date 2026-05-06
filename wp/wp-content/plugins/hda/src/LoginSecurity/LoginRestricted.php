<?php

namespace HDAddons\LoginSecurity;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

class LoginRestricted {
	/* ---------- CONFIG -------------------------------------------------- */

	private ?array $allowlist_ips = [];

	/**
	 * Auth-related actions on wp-login.php that should be IP-restricted.
	 * Non-auth actions (logout, postpass, confirmaction) are NOT restricted.
	 */
	private const array AUTH_ACTIONS = [
		'login',
		'register',
		'lostpassword',
		'retrievepassword',
		'resetpass',
		'rp',
	];

	/* ---------- CONSTRUCT ----------------------------------------------- */

	public function __construct() {
		add_action( 'login_init', $this->restrictLoginToIps( ... ), PHP_INT_MIN );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * Restrict login page access based on IP allowlist/blocklist.
	 * Only applies to auth-related actions (login, register, lostpassword, resetpass).
	 * Non-auth actions (logout, postpass, confirmaction) are allowed through.
	 *
	 * @return bool True if access is allowed, false otherwise.
	 */
	public function restrictLoginToIps(): bool {
		// Determine current wp-login.php action
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'login';

		// Only restrict auth-related actions
		if ( ! in_array( $action, self::AUTH_ACTIONS, true ) ) {
			return true;
		}

		if ( ! $this->_restricted() ) {
			return true;
		}

		$user_ip = Helper::ipAddress();

		// Allowlist mode: only listed IPs can access, all others blocked.
		if ( ! empty( $this->allowlist_ips ) ) {
			if ( Helper::ipMatchesAny( $user_ip, $this->allowlist_ips ) ) {
				return true;
			}

			// IP not in allowlist - block access.
			$this->_blockAccess( $user_ip );
		}

		return true;
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Block access and terminate request.
	 *
	 * @param string $user_ip The IP address being blocked.
	 *
	 * @return never
	 */
	private function _blockAccess( string $user_ip ): never {
		// Atomic increment for blocked counter.
		Helper::incrementCounter( '_security_total_blocked_logins' );

		Helper::errorLog( 'Restricted login page: access currently not permitted - ' . $user_ip );
		wp_die(
			esc_html__( "You don't have access to this page. Please contact the administrator of this website for further assistance.", HDA_TEXTDOMAIN ),
			esc_html__( 'Restricted access', HDA_TEXTDOMAIN ),
			[
				'hda_error'     => true,
				'response'      => 403,
				'blocked_login' => true,
			]
		);

		// Explicit exit for static analysis (wpDie already terminates, but this satisfies the 'never' return type).
		exit;
	}

	/**
	 * Check if restrictions should be applied.
	 *
	 * @return bool True if restrictions are configured.
	 */
	private function _restricted(): bool {
		// Emergency bypass via wp-config.php or .env
		if ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY ) {
			return false;
		}

		$_options             = LoginSecurity::getOptions();
		$custom_allowlist_ips = $_options[ LoginSecurity::KEY_LOGIN_IPS_ACCESS ] ?? [];

		$_options_default           = Helper::filterSettingOptions( 'security', false );
		$allowlist_ips_login_access = $_options_default['allowlist_ips_login_access'] ?? [];

		$this->allowlist_ips = array_filter( array_merge( (array) $allowlist_ips_login_access, (array) $custom_allowlist_ips ) );

		return ! empty( $this->allowlist_ips );
	}
}
