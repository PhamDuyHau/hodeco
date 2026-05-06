<?php
/**
 * Firewall Module — Web Application Firewall orchestrator.
 *
 * Pipeline: Analyze → Detect → Rate Limit → Respond.
 * Supports 'learning' (log only) and 'protecting' (block) modes.
 *
 * @package HDAddons\Firewall
 * @author  HD
 */

namespace HDAddons\Security\Firewall;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class Firewall implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'firewall__options';

	public const string KEY_ENABLED       = 'firewall_enabled';
	public const string KEY_MODE          = 'firewall_mode';          // 'learning' | 'protecting'
	public const string KEY_SQLI          = 'firewall_sqli';
	public const string KEY_XSS           = 'firewall_xss';
	public const string KEY_RCE           = 'firewall_rce';
	public const string KEY_LFI           = 'firewall_lfi';
	public const string KEY_BAD_BOT       = 'firewall_bad_bot';
	public const string KEY_RATE_LIMIT    = 'firewall_rate_limit';
	public const string KEY_RATE_GLOBAL   = 'firewall_rate_global';   // requests/min
	public const string KEY_CRAWLER_WL    = 'firewall_crawler_whitelist'; // Auto-whitelist crawlers
	public const string KEY_IP_REPUTATION = 'firewall_ip_reputation';    // Check abuse lists
	public const string KEY_ALLOWLIST_IPS = 'firewall_allowlist_ips'; // IP whitelist array

	/**
	 * Firewall options (cached).
	 *
	 * @var array
	 */
	private array $options;

	// --------------------------------------------------

	/**
	 * Initialize the firewall.
	 *
	 * Hooks into `plugins_loaded` with high priority (early execution).
	 * Only runs on frontend + unauthenticated admin requests.
	 */
	public function __construct() {
		$this->options = Helper::getOption( self::OPTION_NAME, [] );

		// Emergency bypass via .env constant.
		if ( defined( 'HDA_DISABLE_FIREWALL' ) && \HDA_DISABLE_FIREWALL ) {
			return;
		}

		// Must be explicitly enabled.
		if ( empty( $this->options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// ── Threat Intel cron sync (all contexts) ─────
		add_action( 'hda_threat_intel_sync', self::runThreatIntelSync( ... ) );
		if ( ! wp_next_scheduled( 'hda_threat_intel_sync' ) ) {
			wp_schedule_event( time(), 'daily', 'hda_threat_intel_sync' );
		}

		// Skip CLI.
		if ( 'cli' === PHP_SAPI ) {
			return;
		}

		// Skip WP Cron.
		if ( wp_doing_cron() ) {
			return;
		}

		// Run the WAF pipeline as early as possible.
		// We're already inside `plugins_loaded` (from Plugin::loadModules),
		// so we run immediately in the constructor.
		$this->run();
	}

	// ══════════════════════════════════════════════════
	// WAF Pipeline
	// ══════════════════════════════════════════════════

	/**
	 * Execute the WAF pipeline.
	 *
	 * @return void
	 */
	private function run(): void {
		$ip = Helper::ipAddress();

		// ── 1. Skip localhost ──────────────────────────
		if ( in_array( $ip, [ '127.0.0.1', '::1', '' ], true ) ) {
			return;
		}

		// ── 2. IP Allowlist (always pass) ─────────────
		$allowlist = $this->options[ self::KEY_ALLOWLIST_IPS ] ?? [];
		if ( ! empty( $allowlist ) && Helper::ipMatchesAny( $ip, (array) $allowlist ) ) {
			return;
		}

		// ── 3. Analyze request ────────────────────────
		$analyzer    = new RequestAnalyzer();
		$requestData = $analyzer->analyze();

		// ── 4. Skip static files (zero overhead) ──────
		if ( $requestData['is_static'] ) {
			return;
		}

		// ── 5. Skip admin for logged-in admins ────────
		$uri = $requestData['uri'];
		if ( str_contains( $uri, '/wp-admin/' ) && is_user_logged_in() && current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		// ── 6. Crawler whitelist (bypass scanning) ────
		if ( ! empty( $this->options[ self::KEY_CRAWLER_WL ] ) ) {
			$crawler = ( new CrawlerWhitelist() )->isVerifiedCrawler( $ip, $requestData['user_agent'] ?? '' );
			if ( $crawler ) {
				return; // Verified crawler — skip all checks.
			}
		}

		// ── 7. IP Reputation check ────────────────────
		if ( ! empty( $this->options[ self::KEY_IP_REPUTATION ] ) ) {
			$reputation = ( new IpReputation() )->check( $ip );
			if ( $reputation ) {
				$threat = new ThreatResult(
					ruleId: 'ip_rep_' . $reputation['source'],
					attackType: 'ip_reputation',
					severity: 'high',
					matchedValue: $ip . ' (' . $reputation['source'] . ')',
					context: 'global',
					description: 'IP on known abuse list: ' . $reputation['source'],
				);
				$this->handleThreat( $threat, $ip, $requestData );

				return;
			}
		}

		// ── 8. Threat detection ───────────────────────
		$threat = $this->detectThreats( $requestData );

		if ( $threat ) {
			$this->handleThreat( $threat, $ip, $requestData );

			return;
		}

		// ── 9. Rate limiting ──────────────────────────
		if ( ! empty( $this->options[ self::KEY_RATE_LIMIT ] ) ) {
			$rateThreat = $this->checkRateLimit( $ip, $requestData );
			if ( $rateThreat ) {
				$this->handleThreat( $rateThreat, $ip, $requestData );
			}
		}
	}

	// ══════════════════════════════════════════════════
	// Detection
	// ══════════════════════════════════════════════════

	/**
	 * Run threat detection based on enabled attack types.
	 *
	 * @param array $requestData Analyzed request data.
	 *
	 * @return ThreatResult|null
	 */
	private function detectThreats( array $requestData ): ?ThreatResult {
		$enabledTypes = $this->getEnabledAttackTypes();

		if ( empty( $enabledTypes ) ) {
			return null;
		}

		$detector = new ThreatDetector();

		return $detector->detect( $requestData, $enabledTypes );
	}

	/**
	 * Get the list of currently enabled attack types.
	 *
	 * @return string[]
	 */
	private function getEnabledAttackTypes(): array {
		$map = [
			'sqli'    => self::KEY_SQLI,
			'xss'     => self::KEY_XSS,
			'rce'     => self::KEY_RCE,
			'lfi'     => self::KEY_LFI,
			'bad_bot' => self::KEY_BAD_BOT,
		];

		$enabled = [];
		foreach ( $map as $type => $key ) {
			if ( ! empty( $this->options[ $key ] ) ) {
				$enabled[] = $type;
			}
		}

		return $enabled;
	}



	// ══════════════════════════════════════════════════
	// Rate Limiting
	// ══════════════════════════════════════════════════

	/**
	 * Check rate limits for the current request.
	 *
	 * @param string $ip          Client IP.
	 * @param array  $requestData Analyzed request data.
	 *
	 * @return ThreatResult|null
	 */
	private function checkRateLimit( string $ip, array $requestData ): ?ThreatResult {
		$customLimits = [];

		if ( ! empty( $this->options[ self::KEY_RATE_GLOBAL ] ) ) {
			$customLimits['global'] = (int) $this->options[ self::KEY_RATE_GLOBAL ];
		}

		// Note: Login page rate limiting is handled by LoginSecurity\LoginAttempts
		// (counts actual failed logins with escalating ban: 1h → 1d → 1w).

		$limiter = new RateLimiter( $customLimits );

		return $limiter->check( $ip, $requestData );
	}

	// ══════════════════════════════════════════════════
	// Response
	// ══════════════════════════════════════════════════

	/**
	 * Handle a detected threat based on current mode.
	 *
	 * @param ThreatResult $threat      The detected threat.
	 * @param string       $ip          Client IP.
	 * @param array        $requestData Request context.
	 *
	 * @return void
	 */
	private function handleThreat( ThreatResult $threat, string $ip, array $requestData ): void {
		$mode = $this->options[ self::KEY_MODE ] ?? 'learning';

		// ── Log the threat (always, regardless of mode) ───
		$this->logThreat( $threat, $ip, $requestData, $mode );

		// ── Fire action for external integrations ─────────
		do_action( 'hda_firewall_threat_detected', $threat, $ip, $requestData, $mode );

		// ── In protecting mode, block the request ─────────
		if ( 'protecting' === $mode ) {
			ResponseHandler::block( $threat, $ip );
		}

		// Learning mode: log only, don't block.
	}

	/**
	 * Log a threat to the error log (and TrafficMonitor if available).
	 *
	 * @param ThreatResult $threat      Detected threat.
	 * @param string       $ip          Client IP.
	 * @param array        $requestData Request data.
	 * @param string       $mode        Current mode (learning/protecting).
	 *
	 * @return void
	 */
	private function logThreat( ThreatResult $threat, string $ip, array $requestData, string $mode ): void {
		$action = 'protecting' === $mode ? 'blocked' : 'logged';

		Helper::errorLog( sprintf(
			'[HDA Firewall] %s | %s | %s | %s | %s | %s | %s',
			strtoupper( $action ),
			$threat->attackType,
			$threat->severity,
			$threat->ruleId,
			$ip,
			$requestData['uri'] ?? '',
			$threat->matchedValue,
		) );

	}

	// ══════════════════════════════════════════════════
	// Threat Intel Sync (Cron)
	// ══════════════════════════════════════════════════

	/**
	 * Sync threat intelligence data via daily cron.
	 *
	 * @return void
	 */
	public static function runThreatIntelSync(): void {
		CrawlerWhitelist::syncAll();
		IpReputation::syncAll();
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'firewall-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$checkboxFields = [
			self::KEY_ENABLED,
			self::KEY_SQLI,
			self::KEY_XSS,
			self::KEY_RCE,
			self::KEY_LFI,
			self::KEY_BAD_BOT,
			self::KEY_RATE_LIMIT,
			self::KEY_CRAWLER_WL,
			self::KEY_IP_REPUTATION,
		];

		$textFields = [
			self::KEY_MODE,
			self::KEY_RATE_GLOBAL,
		];

		$options = self::extractFields( $data, array_merge( $checkboxFields, $textFields ) );

		// Handle allowlist IPs (textarea → array).
		if ( ! empty( $data[ self::KEY_ALLOWLIST_IPS ] ) && is_array( $data[ self::KEY_ALLOWLIST_IPS ] ) ) {
			$options[ self::KEY_ALLOWLIST_IPS ] = array_map( 'sanitize_text_field', $data[ self::KEY_ALLOWLIST_IPS ] );
		}

		// Validate mode.
		if ( isset( $options[ self::KEY_MODE ] ) && ! in_array( $options[ self::KEY_MODE ], [ 'learning', 'protecting' ], true ) ) {
			$options[ self::KEY_MODE ] = 'learning';
		}

		self::saveOrRemove( self::OPTION_NAME, $options );
	}
}
