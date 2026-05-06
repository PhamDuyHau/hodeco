<?php

namespace HDAddons\LoginSecurity;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

class LoginAttempts {
	/**
	 * Transient key prefix for per-IP login attempts.
	 * Full key: _login_att_{md5(ip)}
	 */
	private const string TRANSIENT_PREFIX = '_login_att_';

	/**
	 * Transient expiration time (7 days).
	 */
	private const int TRANSIENT_EXPIRATION = WEEK_IN_SECONDS;

	/**
	 * The maximum allowed login attempts.
	 *
	 * @var int
	 */
	private int $limit_login_attempts = 0;

	/**
	 * Login attempts data for admin UI.
	 *
	 * @var array<int, string>
	 */
	public static array $login_attempts_data = [
		0  => 'OFF',
		3  => '3',
		5  => '5',
		10 => '10',
		15 => '15',
		20 => '20',
	];

	// --------------------------------------------------

	public function __construct() {
		// Emergency bypass via wp-config.php or .env
		if ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY ) {
			return;
		}

		$_options                   = LoginSecurity::getOptions();
		$this->limit_login_attempts = (int) ( $_options[ LoginSecurity::KEY_LIMIT_LOGIN_ATTEMPTS ] ?? 0 );

		add_action( 'login_head', $this->maybeBlockLoginAccess( ... ), PHP_INT_MIN );
		add_filter( 'login_errors', $this->logLoginAttempt( ... ) );
		add_action( 'wp_login', $this->resetLoginAttempts( ... ) );
	}

	// --------------------------------------------------

	/**
	 * Block login access if IP has exceeded attempts limit.
	 *
	 * @return void
	 */
	public function maybeBlockLoginAccess(): void {
		$user_ip = Helper::ipAddress();
		$entry   = $this->getIpAttempts( $user_ip );

		// Bail if the user doesn't have attempts.
		if ( empty( $entry['timestamp'] ) ) {
			return;
		}

		// Block if IP has reached the login attempts limit.
		if ( $entry['timestamp'] > time() ) {
			Helper::incrementCounter( '_security_total_blocked_logins' );
			Helper::errorLog( 'Too many incorrect login attempts. - ' . $user_ip );

			// Fire action for TrafficMonitor / Firewall integration.
			do_action( 'hda_login_blocked', $user_ip, $entry['attempts'] );

			wp_die(
				esc_html__( 'Access to login page is currently restricted because of too many incorrect login attempts.', HDA_TEXTDOMAIN ),
				esc_html__( 'Restricted access', HDA_TEXTDOMAIN ),
				[
					'hda_error'     => true,
					'response'      => 403,
					'blocked_login' => true,
				]
			);
		}

		// Reset the login attempts if the restriction time has ended and max ban was reached.
		if (
			$entry['attempts'] >= $this->limit_login_attempts * 3
			&& $entry['timestamp'] < time()
		) {
			$this->deleteIpAttempts( $user_ip );
		}
	}

	// --------------------------------------------------

	/**
	 * Add a login attempt for a specific IP address.
	 *
	 * @param string $error The error message.
	 *
	 * @return string The modified error message.
	 */
	public function logLoginAttempt( string $error ): string {
		global $errors;

		// Check for errors global since the custom login urls plugin is not always returning it.
		if ( empty( $errors ) ) {
			return $error;
		}

		// Skip for invalid/empty credentials (not actual login attempts).
		$skip_codes = [ 'empty_username', 'invalid_username', 'empty_password' ];
		if ( array_intersect( $skip_codes, $errors->get_error_codes() ) ) {
			return $error;
		}

		$user_ip = Helper::ipAddress();
		$entry   = $this->getIpAttempts( $user_ip );

		// Initialize IP entry if not exists.
		if ( empty( $entry ) ) {
			$entry = [
				'attempts'  => 0,
				'timestamp' => 0,
			];
		}

		// Increase the attempt count.
		++ $entry['attempts'];

		$attempts = (int) $entry['attempts'];

		$errors->add(
			'login_attempts',
			sprintf(
				'<strong>%s</strong> %s <strong>%d</strong> %s',
				esc_html__( 'Alert:', HDA_TEXTDOMAIN ),
				esc_html__( 'You have entered the wrong credentials', HDA_TEXTDOMAIN ),
				$attempts,
				esc_html__( 'times.', HDA_TEXTDOMAIN )
			)
		);

		if (
			in_array( 'incorrect_password', $errors->get_error_codes(), true ) &&
			in_array( 'login_attempts', $errors->get_error_codes(), true )
		) {
			$error_message = $errors->get_error_messages( 'login_attempts' );
			$error        .= "\t" . $error_message[0] . "<br />\n";
		}

		// Set restriction timestamp based on attempt count.
		$limit               = $this->limit_login_attempts;
		$entry['timestamp'] = match ( true ) {
			$attempts >= $limit * 3 => time() + WEEK_IN_SECONDS,
			$attempts >= $limit * 2 => time() + DAY_IN_SECONDS,
			$attempts >= $limit     => time() + HOUR_IN_SECONDS,
			default                 => 0,
		};

		$this->saveIpAttempts( $user_ip, $entry );

		return $error;
	}

	// --------------------------------------------------

	/**
	 * Reset login attempts for current IP.
	 *
	 * @return void
	 */
	public function resetLoginAttempts(): void {
		$user_ip = Helper::ipAddress();
		$this->deleteIpAttempts( $user_ip );
	}

	// --------------------------------------------------
	// PRIVATE METHODS
	// --------------------------------------------------

	/**
	 * Build a transient key for a specific IP.
	 *
	 * @param string $ip The IP address.
	 *
	 * @return string The transient key.
	 */
	private function transientKey( string $ip ): string {
		return self::TRANSIENT_PREFIX . md5( $ip );
	}

	/**
	 * Get login attempts for a specific IP.
	 *
	 * @param string $ip The IP address.
	 *
	 * @return array{attempts: int, timestamp: int}|array{}
	 */
	private function getIpAttempts( string $ip ): array {
		$data = get_transient( $this->transientKey( $ip ) );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Save login attempts for a specific IP.
	 *
	 * @param string                             $ip    The IP address.
	 * @param array{attempts: int, timestamp: int} $entry The attempts data.
	 *
	 * @return void
	 */
	private function saveIpAttempts( string $ip, array $entry ): void {
		set_transient( $this->transientKey( $ip ), $entry, self::TRANSIENT_EXPIRATION );
	}

	/**
	 * Delete login attempts for a specific IP.
	 *
	 * @param string $ip The IP address.
	 *
	 * @return void
	 */
	private function deleteIpAttempts( string $ip ): void {
		delete_transient( $this->transientKey( $ip ) );
	}
}
