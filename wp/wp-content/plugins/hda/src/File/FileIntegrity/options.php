<?php
/**
 * File Integrity module options panel.
 *
 * @package HDAddons\FileIntegrity
 */

use HDAddons\File\FileIntegrity\FileIntegrity;
use HDAddons\File\FileIntegrity\FileIntegrityAdmin;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$options = Helper::getOption( FileIntegrity::OPTION_NAME, [] );

$enabled      = ! empty( $options[ FileIntegrity::KEY_ENABLED ] );
$core_scan    = ! empty( $options[ FileIntegrity::KEY_CORE_SCAN ] );
$malware_scan = ! empty( $options[ FileIntegrity::KEY_MALWARE_SCAN ] );
$vuln_scan    = ! empty( $options[ FileIntegrity::KEY_VULN_SCAN ] );
$email_alerts = ! empty( $options[ FileIntegrity::KEY_EMAIL_ALERTS ] );
$schedule     = $options[ FileIntegrity::KEY_SCHEDULE ] ?? 'weekly';

$vt_api_key = Helper::getOption( 'hda_virustotal_api_key', '' );

?>
<div class="container">
	<input type="hidden" name="file_integrity-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 1: FILE INTEGRITY SCANNER -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'File Integrity Scanner', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield-alt"></span>
				<?php esc_html_e( 'Automated file integrity scanning detects modified core files, malware, and vulnerable plugins/themes. Results are cached and viewable on the File Integrity admin page.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( FileIntegrity::KEY_ENABLED ); ?>"><?php esc_html_e( 'Enable Automated Scanning', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( FileIntegrity::KEY_ENABLED ); ?>" id="<?php echo esc_attr( FileIntegrity::KEY_ENABLED ); ?>" <?php checked( $enabled ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Schedule periodic scans via WP-Cron', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'When enabled, the selected scan types will run automatically on schedule. Enable "Email Alerts" below to receive reports when issues are found.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-select">
				<label class="heading" for="<?php echo esc_attr( FileIntegrity::KEY_SCHEDULE ); ?>"><?php esc_html_e( 'Scan Schedule', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<select class="select" name="<?php echo esc_attr( FileIntegrity::KEY_SCHEDULE ); ?>" id="<?php echo esc_attr( FileIntegrity::KEY_SCHEDULE ); ?>">
							<option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', HDA_TEXTDOMAIN ); ?></option>
							<option value="weekly" <?php selected( $schedule, 'weekly' ); ?>><?php esc_html_e( 'Weekly', HDA_TEXTDOMAIN ); ?></option>
							<option value="monthly" <?php selected( $schedule, 'monthly' ); ?>><?php esc_html_e( 'Monthly', HDA_TEXTDOMAIN ); ?></option>
						</select>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'How often automated scans should run. "Weekly" is recommended for most sites.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 2: SCAN CONFIGURATION (toggled by Enable checkbox) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset id="hda-scan-config" class="container-fieldset" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
		<legend class="section-legend"><?php esc_html_e( 'Scan Configuration', HDA_TEXTDOMAIN ); ?></legend>

		<!-- Scan Types -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Scan Types', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Select which scans run during automated and manual scanning.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
		<div class="container flex flex-x gap sm-up-1 md-up-3">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( FileIntegrity::KEY_CORE_SCAN ); ?>"><?php esc_html_e( 'Core Integrity Scan', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( FileIntegrity::KEY_CORE_SCAN ); ?>" id="<?php echo esc_attr( FileIntegrity::KEY_CORE_SCAN ); ?>" <?php checked( $core_scan ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Verify WP core files against official checksums', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Fetches checksums from WordPress.org API and compares with local files. Detects modified, unknown, and missing core files.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( FileIntegrity::KEY_MALWARE_SCAN ); ?>"><?php esc_html_e( 'Malware Scan', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( FileIntegrity::KEY_MALWARE_SCAN ); ?>" id="<?php echo esc_attr( FileIntegrity::KEY_MALWARE_SCAN ); ?>" <?php checked( $malware_scan ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Scan for known malware signatures', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Checks for backdoors, malicious scripts, obfuscated code, crypto miners, and suspicious patterns in wp-content.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( FileIntegrity::KEY_VULN_SCAN ); ?>"><?php esc_html_e( 'Vulnerability Scan', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( FileIntegrity::KEY_VULN_SCAN ); ?>" id="<?php echo esc_attr( FileIntegrity::KEY_VULN_SCAN ); ?>" <?php checked( $vuln_scan ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check plugins/themes for known CVEs', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Checks installed plugins and themes against WordPress.org for outdated, closed, or abandoned software.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>

		<!-- Alerts -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Alerts', HDA_TEXTDOMAIN ); ?></h4>
		<div class="container flex flex-x gap sm-up-1">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( FileIntegrity::KEY_EMAIL_ALERTS ); ?>"><?php esc_html_e( 'Email Alerts', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( FileIntegrity::KEY_EMAIL_ALERTS ); ?>" id="<?php echo esc_attr( FileIntegrity::KEY_EMAIL_ALERTS ); ?>" <?php checked( $email_alerts ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Email admin when issues are found', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc">
					<?php
					printf(
						/* translators: %s: admin email */
						esc_html__( 'Sends scan report to: %s', HDA_TEXTDOMAIN ),
						'<code>' . esc_html( get_option( 'admin_email' ) ) . '</code>'
					);
					?>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- API KEYS & MANUAL SCANS (always visible) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'API Keys & Manual Scans', HDA_TEXTDOMAIN ); ?></legend>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-text">
				<label class="heading" for="hda_virustotal_api_key"><?php esc_html_e( 'VirusTotal API Key', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="password" class="regular-text" name="hda_virustotal_api_key" id="hda_virustotal_api_key" value="<?php echo esc_attr( $vt_api_key ); ?>" autocomplete="off">
					</div>
				</div>
				<div class="desc">
					<?php
					printf(
						/* translators: %s: link to VirusTotal */
						esc_html__( 'Optional. Cross-checks suspicious files against 70+ antivirus engines. Used by both automated and manual scans. Get a free key at %s.', HDA_TEXTDOMAIN ),
						'<a href="https://www.virustotal.com/gui/join-us" target="_blank" rel="noopener">virustotal.com</a>'
					);
					?>
				</div>
			</div>
			<div class="cell section">
				<span class="heading"><?php esc_html_e( 'Run Manual Scan', HDA_TEXTDOMAIN ); ?></span>
				<div class="option" style="margin-top:8px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FileIntegrityAdmin::MENU_SLUG ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-search" style="vertical-align:middle;margin-right:4px;"></span>
						<?php esc_html_e( 'Go to File Integrity Page', HDA_TEXTDOMAIN ); ?>
					</a>
				</div>
				<div class="desc"><?php esc_html_e( 'Run core integrity, malware, and vulnerability scans on-demand. Manual scans always check all directories. Results are cached for 12 hours.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>
</div>

<!-- Toggle Fieldset 2 visibility based on Enable checkbox -->
<script>
(function () {
	const toggle = document.getElementById('<?php echo esc_js( FileIntegrity::KEY_ENABLED ); ?>');
	const target = document.getElementById('hda-scan-config');
	if (!toggle || !target) return;

	toggle.addEventListener('change', function () {
		target.style.display = this.checked ? '' : 'none';
	});
})();
</script>
