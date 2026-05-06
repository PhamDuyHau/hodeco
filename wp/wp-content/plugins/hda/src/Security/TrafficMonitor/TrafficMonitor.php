<?php
/**
 * Traffic Monitor Module — entry point.
 *
 * Manages settings, schedules cleanup cron, and wires Firewall events to TrafficLogger.
 * When enabled alongside the Firewall module, automatically logs all threat events.
 *
 * @package HDAddons\TrafficMonitor
 * @author  HD
 */

namespace HDAddons\Security\TrafficMonitor;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Security\Firewall\ThreatResult;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class TrafficMonitor implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME         = 'traffic_monitor__options';
	public const string KEY_ENABLED         = 'tm_enabled';
	public const string KEY_RETENTION_DAYS  = 'tm_retention_days';

	/**
	 * Default log retention in days.
	 */
	private const int DEFAULT_RETENTION_DAYS = 30;

	/**
	 * Cached options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Shared logger instance (lazy-loaded).
	 *
	 * @var TrafficLogger|null
	 */
	private ?TrafficLogger $logger = null;

	// --------------------------------------------------

	public function __construct() {
		$this->options = Helper::getOption( self::OPTION_NAME, [] );

		if ( empty( $this->options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// ── Cron cleanup (all contexts) ─────────────
		add_action( 'hda_traffic_log_cleanup', self::runCleanup( ... ) );
		if ( ! wp_next_scheduled( 'hda_traffic_log_cleanup' ) ) {
			wp_schedule_event( time(), 'weekly', 'hda_traffic_log_cleanup' );
		}

		// ── Wire Firewall events → TrafficLogger ────
		add_action( 'hda_firewall_threat_detected', $this->onThreatDetected( ... ), 10, 4 );

		// ── Wire Monitor404 flood events ─────────────
		add_action( 'hda_404_flood_detected', $this->on404Flood( ... ), 10, 3 );

		// ── Wire LoginSecurity blocked events ────────
		add_action( 'hda_login_blocked', $this->onLoginBlocked( ... ), 10, 2 );

		// ── Admin page ──────────────────────────────
		if ( is_admin() ) {
			new TrafficMonitorAdmin();
		}
	}

	// ══════════════════════════════════════════════════
	// Firewall integration
	// ══════════════════════════════════════════════════

	/**
	 * Handle a threat detected by the Firewall module.
	 *
	 * Fires on the `hda_firewall_threat_detected` action hook.
	 *
	 * @param ThreatResult $threat      Detected threat.
	 * @param string       $ip          Client IP.
	 * @param array        $requestData Analyzed request data.
	 * @param string       $mode        Firewall mode (learning/protecting).
	 *
	 * @return void
	 */
	public function onThreatDetected( ThreatResult $threat, string $ip, array $requestData, string $mode ): void {
		$this->getLogger()->log( [
			'ip'          => $ip,
			'uri'         => $requestData['uri'] ?? '',
			'method'      => $requestData['method'] ?? 'GET',
			'user_agent'  => $requestData['user_agent'] ?? '',
			'action'      => 'protecting' === $mode ? 'blocked' : 'logged',
			'attack_type' => $threat->attackType,
			'rule_id'     => $threat->ruleId,
			'severity'    => $threat->severity,
			'matched'     => $threat->matchedValue,
		] );
	}

	/**
	 * Handle 404 flood detection from Monitor404 module.
	 *
	 * @param string $ip    Client IP.
	 * @param int    $count Number of 404s in the window.
	 * @param string $uri   Last 404 URI.
	 *
	 * @return void
	 */
	public function on404Flood( string $ip, int $count, string $uri ): void {
		$this->getLogger()->log( [
			'ip'          => $ip,
			'uri'         => $uri,
			'method'      => 'GET',
			'action'      => 'logged',
			'attack_type' => '404_flood',
			'severity'    => 'medium',
			'matched'     => sprintf( '%d 404s triggered', $count ),
		] );
	}

	/**
	 * Handle login blocked event from LoginAttempts module.
	 *
	 * @param string $ip       Client IP.
	 * @param int    $attempts Number of failed attempts.
	 *
	 * @return void
	 */
	public function onLoginBlocked( string $ip, int $attempts ): void {
		$this->getLogger()->log( [
			'ip'          => $ip,
			'uri'         => '/wp-login.php',
			'method'      => 'POST',
			'action'      => 'blocked',
			'attack_type' => 'brute_force',
			'severity'    => 'high',
			'matched'     => sprintf( '%d failed login attempts', $attempts ),
		] );
	}

	/**
	 * Get the shared logger instance (lazy-loaded).
	 *
	 * @return TrafficLogger
	 */
	private function getLogger(): TrafficLogger {
		return $this->logger ??= new TrafficLogger();
	}

	// ══════════════════════════════════════════════════
	// Cleanup
	// ══════════════════════════════════════════════════

	/**
	 * Run cleanup via cron.
	 *
	 * @return void
	 */
	public static function runCleanup(): void {
		$options       = Helper::getOption( self::OPTION_NAME, [] );
		$retentionDays = ! empty( $options[ self::KEY_RETENTION_DAYS ] )
			? max( 7, (int) $options[ self::KEY_RETENTION_DAYS ] )
			: self::DEFAULT_RETENTION_DAYS;

		TrafficLogger::cleanup( $retentionDays );
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'traffic_monitor-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$options = [
			self::KEY_ENABLED        => ! empty( $data[ self::KEY_ENABLED ] ),
			self::KEY_RETENTION_DAYS => isset( $data[ self::KEY_RETENTION_DAYS ] )
				? max( 7, min( 365, absint( $data[ self::KEY_RETENTION_DAYS ] ) ) )
				: self::DEFAULT_RETENTION_DAYS,
		];

		self::saveOrRemove( self::OPTION_NAME, $options, true );
	}
}
