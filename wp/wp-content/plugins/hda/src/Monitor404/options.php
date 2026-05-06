<?php
/**
 * 404 Monitor module options panel.
 *
 * @package HDAddons\Monitor404
 */

use HDAddons\Monitor404\Monitor404;

\defined( 'ABSPATH' ) || exit;

$options = Monitor404::getOptions();

?>
<div class="container">
	<input type="hidden" name="monitor_404-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Monitor Settings', HDA_TEXTDOMAIN ); ?></legend>
		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="m404_enabled"><?php esc_html_e( 'Enable 404 Monitoring', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="m404_enabled" id="m404_enabled" <?php checked( ! empty( $options[ Monitor404::KEY_ENABLED ] ) ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Logs every 404 hit (URL, referrer, user-agent, IP) to help find broken links and missing pages.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="m404_retention_days"><?php esc_html_e( 'Log Retention', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input small-text" name="m404_retention_days" id="m404_retention_days" value="<?php echo absint( $options[ Monitor404::KEY_RETENTION_DAYS ] ?? 90 ); ?>" min="7" max="365" step="1">
						<?php esc_html_e( 'days', HDA_TEXTDOMAIN ); ?>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Auto-purged monthly via WP-Cron. Hard cap: 50,000 entries.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-textarea" style="grid-column:1/-1;">
				<label class="heading" for="m404_ignored_patterns"><?php esc_html_e( 'Ignored URL Patterns', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<textarea class="textarea" name="m404_ignored_patterns" id="m404_ignored_patterns" rows="4" placeholder="/wp-admin/&#10;/wp-json/&#10;/feed/"><?php echo esc_textarea( $options[ Monitor404::KEY_IGNORED_PATTERNS ] ?? '' ); ?></textarea>
					</div>
				</div>
				<div class="desc">
					<?php echo wp_kses(
						__( 'One URL prefix per line. Matching 404s won\'t be logged.<br>Static assets (<code>.css</code>, <code>.js</code>, images, fonts) are always ignored.', HDA_TEXTDOMAIN ),
						[ 'br' => [], 'code' => [] ]
					); ?>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- AUTO-BLOCK (404 Flood Protection) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( '404 Flood Protection', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--warning">
			<p>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Detects IPs generating excessive 404 errors (scanners, vulnerability probes). Events are logged to Security Log when enabled.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-3">
			<div class="cell section section-checkbox">
				<label class="heading" for="m404_auto_block"><?php esc_html_e( 'Enable Flood Detection', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="m404_auto_block" id="m404_auto_block" <?php checked( ! empty( $options[ Monitor404::KEY_AUTO_BLOCK ] ) ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Track 404 frequency per IP', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="m404_block_threshold"><?php esc_html_e( 'Threshold', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input small-text" name="m404_block_threshold" id="m404_block_threshold" value="<?php echo absint( $options[ Monitor404::KEY_BLOCK_THRESHOLD ] ?? 20 ); ?>" min="5" max="200" step="1">
						<?php esc_html_e( '404 hits', HDA_TEXTDOMAIN ); ?>
					</div>
				</div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="m404_block_window"><?php esc_html_e( 'Time Window', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input small-text" name="m404_block_window" id="m404_block_window" value="<?php echo absint( $options[ Monitor404::KEY_BLOCK_WINDOW ] ?? 5 ); ?>" min="1" max="60" step="1">
						<?php esc_html_e( 'minutes', HDA_TEXTDOMAIN ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php if ( ! empty( $options[ Monitor404::KEY_ENABLED ] ) ) : ?>
		<div style="margin-top:16px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hda-404-monitor' ) ); ?>" class="button">
				<span class="dashicons dashicons-visibility" style="vertical-align:middle;margin-right:4px;"></span>
				<?php esc_html_e( 'View 404 Log', HDA_TEXTDOMAIN ); ?>
			</a>
		</div>
		<?php endif; ?>
	</fieldset>
</div>
