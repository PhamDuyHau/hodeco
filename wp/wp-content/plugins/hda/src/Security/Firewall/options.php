<?php
/**
 * Firewall module options panel.
 *
 * @package HDAddons\Firewall
 */

use HDAddons\Security\Firewall\Firewall;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$options = Helper::getOption( Firewall::OPTION_NAME, [] );

// Current values.
$enabled       = ! empty( $options[ Firewall::KEY_ENABLED ] );
$mode          = $options[ Firewall::KEY_MODE ] ?? 'learning';
$sqli          = ! empty( $options[ Firewall::KEY_SQLI ] );
$xss           = ! empty( $options[ Firewall::KEY_XSS ] );
$rce           = ! empty( $options[ Firewall::KEY_RCE ] );
$lfi           = ! empty( $options[ Firewall::KEY_LFI ] );
$bad_bot       = ! empty( $options[ Firewall::KEY_BAD_BOT ] );

$rate_limit    = ! empty( $options[ Firewall::KEY_RATE_LIMIT ] );
$rate_global   = $options[ Firewall::KEY_RATE_GLOBAL ] ?? 300;
$crawler_wl    = ! empty( $options[ Firewall::KEY_CRAWLER_WL ] );
$ip_reputation = ! empty( $options[ Firewall::KEY_IP_REPUTATION ] );
$allowlist_ips = $options[ Firewall::KEY_ALLOWLIST_IPS ] ?? [];

?>
<div class="container" style="margin-top: 30px">
	<input type="hidden" name="firewall-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 1: WAF ENGINE -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'WAF Engine', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield-alt"></span>
				<?php esc_html_e( 'Web Application Firewall protects against SQL injection, cross-site scripting, remote code execution, and other attacks in real-time.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_ENABLED ); ?>"><?php esc_html_e( 'Enable Firewall', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_ENABLED ); ?>" id="<?php echo esc_attr( Firewall::KEY_ENABLED ); ?>" <?php checked( $enabled ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Activate the WAF pipeline', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Analyzes every request for threats. When disabled, all WAF features are inactive.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-radio">
				<label class="heading"><?php esc_html_e( 'Firewall Mode', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<label style="margin-right:16px;">
							<input type="radio" name="<?php echo esc_attr( Firewall::KEY_MODE ); ?>" value="learning" <?php checked( $mode, 'learning' ); ?>>
							<?php esc_html_e( 'Learning (log only)', HDA_TEXTDOMAIN ); ?>
						</label>
						<label>
							<input type="radio" name="<?php echo esc_attr( Firewall::KEY_MODE ); ?>" value="protecting" <?php checked( $mode, 'protecting' ); ?>>
							<?php esc_html_e( 'Protecting (block threats)', HDA_TEXTDOMAIN ); ?>
						</label>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Start with Learning mode to monitor threats without blocking. Switch to Protecting when confident.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>

		<!-- IP Allowlist -->
		<div class="container flex flex-x gap sm-up-1" style="margin-top:12px;">
			<div class="cell section section-select">
				<label class="heading" for="firewall_allowlist_ips"><?php esc_html_e( 'IP Allowlist (bypass all checks)', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple
								placeholder="<?php esc_attr_e( 'Type an IP and press Enter', HDA_TEXTDOMAIN ); ?>"
								class="select select2-ips !w[100%]"
								name="<?php echo esc_attr( Firewall::KEY_ALLOWLIST_IPS ); ?>[]"
								id="firewall_allowlist_ips"
							>
								<?php foreach ( $allowlist_ips as $ip ) : ?>
									<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc">
					<?php esc_html_e( 'These IPs bypass all firewall checks entirely. Accepted formats:', HDA_TEXTDOMAIN ); ?>
					<ul style="margin:6px 0 0 18px;list-style:disc;">
						<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', HDA_TEXTDOMAIN ) ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 2: THREAT DETECTION & PROTECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset id="hda-firewall-detection" class="container-fieldset" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
		<legend class="section-legend"><?php esc_html_e( 'Threat Detection & Protection', HDA_TEXTDOMAIN ); ?></legend>

		<!-- Attack Detection -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Attack Detection', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Enable specific attack type detectors below. Each scans incoming requests in real-time and triggers the configured Firewall Mode action (log or block).', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
		<div class="container flex flex-x gap sm-up-2 md-up-3">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_SQLI ); ?>"><?php esc_html_e( 'SQL Injection', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_SQLI ); ?>" id="<?php echo esc_attr( Firewall::KEY_SQLI ); ?>" <?php checked( $sqli ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Detect SQLi payloads', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Scans GET, POST, cookies, and headers for SQL injection patterns (UNION SELECT, OR 1=1, etc.). Prevents attackers from reading or modifying your database.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_XSS ); ?>"><?php esc_html_e( 'Cross-Site Scripting', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_XSS ); ?>" id="<?php echo esc_attr( Firewall::KEY_XSS ); ?>" <?php checked( $xss ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Detect XSS payloads', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Detects injected JavaScript, event handlers, and HTML tags in request data. Prevents attackers from stealing sessions or defacing pages.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_RCE ); ?>"><?php esc_html_e( 'Remote Code Execution', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_RCE ); ?>" id="<?php echo esc_attr( Firewall::KEY_RCE ); ?>" <?php checked( $rce ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Detect RCE payloads', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Blocks shell commands (eval, exec, system, passthru) injected via request parameters. Prevents full server takeover.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_LFI ); ?>"><?php esc_html_e( 'Local File Inclusion', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_LFI ); ?>" id="<?php echo esc_attr( Firewall::KEY_LFI ); ?>" <?php checked( $lfi ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Detect path traversal', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Detects directory traversal patterns (../../etc/passwd, php://filter) in URLs and parameters. Prevents reading sensitive server files.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_BAD_BOT ); ?>"><?php esc_html_e( 'Bad Bots', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_BAD_BOT ); ?>" id="<?php echo esc_attr( Firewall::KEY_BAD_BOT ); ?>" <?php checked( $bad_bot ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Block known malicious bots', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Identifies hacking tools (sqlmap, nikto, wpscan, metasploit, DDoS tools) by User-Agent. Different from Server Config bot blocking which targets SEO/AI spam crawlers.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>

		<!-- Rate Limiting -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Rate Limiting', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Login brute-force protection uses LoginSecurity → Limit Login Attempts (escalating ban: 1h → 1d → 1w after N failed passwords).', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
		<div class="container flex flex-x gap sm-up-1 md-up-3">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_RATE_LIMIT ); ?>"><?php esc_html_e( 'Enable Rate Limiting', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_RATE_LIMIT ); ?>" id="<?php echo esc_attr( Firewall::KEY_RATE_LIMIT ); ?>" <?php checked( $rate_limit ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Throttle excessive requests per IP', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_RATE_GLOBAL ); ?>"><?php esc_html_e( 'Global Limit', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input small-text" name="<?php echo esc_attr( Firewall::KEY_RATE_GLOBAL ); ?>" id="<?php echo esc_attr( Firewall::KEY_RATE_GLOBAL ); ?>" value="<?php echo absint( $rate_global ); ?>" min="10" max="1000" step="10">
						<?php esc_html_e( 'requests/min', HDA_TEXTDOMAIN ); ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Threat Intelligence -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Threat Intelligence', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Synced daily via cron from official sources. No API keys required.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_CRAWLER_WL ); ?>"><?php esc_html_e( 'Auto-Whitelist Crawlers', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_CRAWLER_WL ); ?>" id="<?php echo esc_attr( Firewall::KEY_CRAWLER_WL ); ?>" <?php checked( $crawler_wl ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Bypass WAF for verified Googlebot, Bingbot, Cloudflare', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Verifies bots against official published IP ranges. Prevents false positive blocking of search engine crawlers.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_IP_REPUTATION ); ?>"><?php esc_html_e( 'IP Reputation Check', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Firewall::KEY_IP_REPUTATION ); ?>" id="<?php echo esc_attr( Firewall::KEY_IP_REPUTATION ); ?>" <?php checked( $ip_reputation ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Block IPs from known abuse lists', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Checks against Spamhaus DROP/EDROP and Emerging Threats compromised IP lists.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>
</div>

<!-- Toggle Fieldset 2 visibility based on Enable Firewall checkbox -->
<script>
(function () {
	const toggle = document.getElementById('<?php echo esc_js( Firewall::KEY_ENABLED ); ?>');
	const target = document.getElementById('hda-firewall-detection');
	if (!toggle || !target) return;

	toggle.addEventListener('change', function () {
		target.style.display = this.checked ? '' : 'none';
	});
})();
</script>
