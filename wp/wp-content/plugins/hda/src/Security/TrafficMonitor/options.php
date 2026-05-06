<?php
/**
 * Traffic Monitor module options panel.
 *
 * @package HDAddons\TrafficMonitor
 */

use HDAddons\Helper;
use HDAddons\Security\TrafficMonitor\TrafficMonitor;

\defined( 'ABSPATH' ) || exit;

$options = Helper::getOption( TrafficMonitor::OPTION_NAME, [] );

$enabled        = ! empty( $options[ TrafficMonitor::KEY_ENABLED ] );
$retention_days = $options[ TrafficMonitor::KEY_RETENTION_DAYS ] ?? 30;

?>
<div class="container" style="margin-top: 30px">
	<input type="hidden" name="traffic_monitor-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Traffic Monitor Settings', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-chart-area"></span>
				<?php esc_html_e( 'Logs security events from Firewall, Login Security, and 404 Monitor into a single dashboard. Requires the corresponding modules to be enabled for data to flow in.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( TrafficMonitor::KEY_ENABLED ); ?>"><?php esc_html_e( 'Enable Traffic Monitor', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( TrafficMonitor::KEY_ENABLED ); ?>" id="<?php echo esc_attr( TrafficMonitor::KEY_ENABLED ); ?>" <?php checked( $enabled ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Activate security event logging', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Captures blocked requests, firewall alerts, brute-force attempts, and 404 floods into a searchable log.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="<?php echo esc_attr( TrafficMonitor::KEY_RETENTION_DAYS ); ?>"><?php esc_html_e( 'Log Retention', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input small-text" name="<?php echo esc_attr( TrafficMonitor::KEY_RETENTION_DAYS ); ?>" id="<?php echo esc_attr( TrafficMonitor::KEY_RETENTION_DAYS ); ?>" value="<?php echo absint( $retention_days ); ?>" min="7" max="365" step="1">
						<?php esc_html_e( 'days', HDA_TEXTDOMAIN ); ?>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Logs older than this are auto-purged weekly via WP-Cron. Hard cap: 100,000 entries.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
		<?php if ( $enabled ) : ?>
		<div style="margin-top:16px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hda-traffic-monitor' ) ); ?>" class="button button-primary">
				<span class="dashicons dashicons-chart-area" style="vertical-align:middle;margin-right:4px;"></span>
				<?php esc_html_e( 'View Traffic Log', HDA_TEXTDOMAIN ); ?>
			</a>
		</div>
		<?php endif; ?>
	</fieldset>
</div>
