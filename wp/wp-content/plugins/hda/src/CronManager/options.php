<?php
/**
 * Cron Manager module options panel.
 *
 * @package HDAddons\CronManager
 */

use HDAddons\CronManager\CronManager;

\defined( 'ABSPATH' ) || exit;

$events     = CronManager::getEvents();
$stats      = CronManager::getStats( $events );
$cronStatus = CronManager::getCronStatus();
$schedules  = CronManager::getSchedules();

?>
<div class="container">
	<input type="hidden" name="cron_manager-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- CRON STATUS -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'WP-Cron Status', HDA_TEXTDOMAIN ); ?></legend>

		<?php if ( $cronStatus['disabled_constant'] ) : ?>
			<div class="hda-notice hda-notice--warning">
				<p>
					<span class="dashicons dashicons-warning"></span>
					<?php echo wp_kses_post( __( '<code>DISABLE_WP_CRON</code> is <b>true</b> — WP-Cron is disabled. Make sure a system cron (<code>crontab</code>) is configured to call <code>wp-cron.php</code>.', HDA_TEXTDOMAIN ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $cronStatus['alternate_cron'] ) : ?>
			<div class="hda-notice hda-notice--info">
				<p>
					<span class="dashicons dashicons-info"></span>
					<?php echo wp_kses_post( __( '<code>ALTERNATE_WP_CRON</code> is active — using redirect-based cron execution.', HDA_TEXTDOMAIN ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="container flex flex-x gap sm-up-2 md-up-4 hda-cron-stats">
			<div class="cell hda-cron-stat">
				<div class="hda-cron-stat__value hda-cron-stat__value--primary"><?php echo esc_html( $stats['total'] ); ?></div>
				<div class="hda-cron-stat__label"><?php esc_html_e( 'Total Events', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell hda-cron-stat">
				<div class="hda-cron-stat__value <?php echo $stats['overdue'] > 0 ? 'hda-cron-stat__value--danger' : 'hda-cron-stat__value--success'; ?>">
					<?php echo esc_html( $stats['overdue'] ); ?>
				</div>
				<div class="hda-cron-stat__label"><?php esc_html_e( 'Overdue', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell hda-cron-stat">
				<div class="hda-cron-stat__value hda-cron-stat__value--recurring"><?php echo esc_html( $stats['recurring'] ); ?></div>
				<div class="hda-cron-stat__label"><?php esc_html_e( 'Recurring', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell hda-cron-stat">
				<div class="hda-cron-stat__value hda-cron-stat__value--one-time"><?php echo esc_html( $stats['one_time'] ); ?></div>
				<div class="hda-cron-stat__label"><?php esc_html_e( 'One-time', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- CRON EVENTS TABLE -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Scheduled Events', HDA_TEXTDOMAIN ); ?></legend>

		<div class="desc">
			<?php esc_html_e( 'Read-only view — use the action buttons to run or remove individual events. This panel does not save settings.', HDA_TEXTDOMAIN ); ?>
			<div class="hda-cron-legend">
				<span><span class="hda-cron-badge hda-cron-badge--recurring">🔁 Recurring</span> — runs repeatedly on a fixed schedule (daily, hourly, etc.)</span>
				<span><span class="hda-cron-badge hda-cron-badge--one-time">⏱ One-time</span> — runs once at the scheduled time, then removed</span>
				<span><span class="hda-cron-badge hda-cron-badge--overdue">⚠ Overdue</span> — past due by &gt;60s, WP-Cron may not be firing</span>
			</div>
		</div>

		<?php if ( empty( $events ) ) : ?>
			<div class="hda-notice hda-notice--info">
				<p><?php esc_html_e( 'No scheduled cron events found.', HDA_TEXTDOMAIN ); ?></p>
			</div>
		<?php else : ?>
			<div class="hda-table-responsive">
				<table class="widefat striped hda-cron-table" id="hda-cron-table">
					<thead>
						<tr>
							<th class="hda-cron-table__row-num">#</th>
							<th><?php esc_html_e( 'Hook', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Next Run', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Schedule', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Interval', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Type', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Actions', HDA_TEXTDOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $row_num = 0; ?>
						<?php foreach ( $events as $event ) : $row_num++; ?>
							<tr class="hda-cron-row" data-hook="<?php echo esc_attr( $event['hook'] ); ?>" data-timestamp="<?php echo esc_attr( $event['timestamp'] ); ?>" data-sig="<?php echo esc_attr( $event['args_key'] ); ?>">
								<td class="hda-cron-table__row-num"><?php echo esc_html( $row_num ); ?></td>
								<td>
									<code class="hda-cron-table__hook">
										<?php echo esc_html( $event['hook'] ); ?>
									</code>
								</td>
								<td>
									<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $event['timestamp'] ) ); ?>
								</td>
								<td>
									<?php if ( ! empty( $event['schedule'] ) ) : ?>
										<span class="hda-cron-table__schedule">
											<?php echo esc_html( $schedules[ $event['schedule'] ]['display'] ?? $event['schedule'] ); ?>
										</span>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $event['interval'] ) : ?>
										<?php echo esc_html( human_time_diff( 0, $event['interval'] ) ); ?>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $event['overdue'] ) : ?>
										<span class="hda-cron-badge hda-cron-badge--overdue" title="<?php esc_attr_e( 'This event is overdue — cron may not be running', HDA_TEXTDOMAIN ); ?>">⚠ Overdue</span>
									<?php elseif ( ! empty( $event['schedule'] ) ) : ?>
										<span class="hda-cron-badge hda-cron-badge--recurring">🔁 Recurring</span>
									<?php else : ?>
										<span class="hda-cron-badge hda-cron-badge--one-time">⏱ One-time</span>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="button button-small hda-cron-run" title="<?php esc_attr_e( 'Run now', HDA_TEXTDOMAIN ); ?>">
										<span class="dashicons dashicons-controls-play"></span>
									</button>
									<button type="button" class="button button-small hda-cron-delete" title="<?php esc_attr_e( 'Delete', HDA_TEXTDOMAIN ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<div id="hda-cron-status" class="hda-cron-status"></div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- REGISTERED SCHEDULES -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Registered Schedules', HDA_TEXTDOMAIN ); ?></legend>
		<div class="desc">
			<?php esc_html_e( 'All recurrence intervals available for WP-Cron events. Plugins may register custom schedules.', HDA_TEXTDOMAIN ); ?>
		</div>
		<div class="hda-table-responsive">
			<table class="widefat striped hda-cron-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Slug', HDA_TEXTDOMAIN ); ?></th>
						<th><?php esc_html_e( 'Display Name', HDA_TEXTDOMAIN ); ?></th>
						<th><?php esc_html_e( 'Interval', HDA_TEXTDOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $schedules as $slug => $info ) : ?>
						<tr>
							<td><code class="hda-cron-table__hook"><?php echo esc_html( $slug ); ?></code></td>
							<td><?php echo esc_html( $info['display'] ); ?></td>
							<td>
								<?php echo esc_html( human_time_diff( 0, $info['interval'] ) ); ?>
								<span class="hda-cron-table__interval-seconds">(<?php echo esc_html( number_format_i18n( $info['interval'] ) ); ?>s)</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>
