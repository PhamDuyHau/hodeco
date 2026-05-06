<?php
/**
 * Optimize module options panel.
 *
 * @package HDAddons\Optimize
 */

use HDAddons\Optimize\DatabaseOptimizer;
use HDAddons\Optimize\Optimize;

\defined( 'ABSPATH' ) || exit;

$optimize_options = Optimize::getOptions();

?>
<div class="container">
	<input type="hidden" name="optimize__options[_hidden]" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- PERFORMANCE -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Performance', HDA_TEXTDOMAIN ); ?></legend>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-select">
				<label class="heading" for="heartbeat_frequency"><?php esc_html_e( 'Heartbeat Frequency', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="optimize__options[heartbeat_frequency]" id="heartbeat_frequency">
								<?php foreach ( \HDAddons\Optimize\Performance::$heartbeatOptions as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $optimize_options[ Optimize::KEY_HEARTBEAT_FREQUENCY ] ?? 0, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'WP Heartbeat sends AJAX requests every 15s by default. Throttle or disable to reduce server load.', HDA_TEXTDOMAIN ); ?></div>
			</div>

			<div class="cell section section-select">
				<label class="heading" for="heartbeat_location"><?php esc_html_e( 'Heartbeat Location', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="optimize__options[heartbeat_location]" id="heartbeat_location">
								<option value="default" <?php selected( $optimize_options[ Optimize::KEY_HEARTBEAT_LOCATION ] ?? 'default', 'default' ); ?>><?php esc_html_e( 'Everywhere (Default)', HDA_TEXTDOMAIN ); ?></option>
								<option value="disable_frontend" <?php selected( $optimize_options[ Optimize::KEY_HEARTBEAT_LOCATION ] ?? '', 'disable_frontend' ); ?>><?php esc_html_e( 'Disable on Frontend', HDA_TEXTDOMAIN ); ?></option>
								<option value="allow_post_edit_only" <?php selected( $optimize_options[ Optimize::KEY_HEARTBEAT_LOCATION ] ?? '', 'allow_post_edit_only' ); ?>><?php esc_html_e( 'Post Edit Screen Only', HDA_TEXTDOMAIN ); ?></option>
								<option value="disable_everywhere" <?php selected( $optimize_options[ Optimize::KEY_HEARTBEAT_LOCATION ] ?? '', 'disable_everywhere' ); ?>><?php esc_html_e( 'Disable Everywhere', HDA_TEXTDOMAIN ); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Heartbeat is only needed for auto-save and post locking. Safe to disable on frontend.', HDA_TEXTDOMAIN ); ?></div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading" for="disable_embeds"><?php esc_html_e( 'Disable Embeds', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="optimize__options[disable_embeds]" id="disable_embeds" <?php checked( $optimize_options[ Optimize::KEY_DISABLE_EMBEDS ] ?? '', 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to disable', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses( __( 'Removes oEmbed discovery links, scripts, and the <code>/wp-json/oembed/</code> endpoint.', HDA_TEXTDOMAIN ), [ 'code' => [] ] ); ?></div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading" for="enable_cleanup"><?php esc_html_e( 'Enable Core Cleanup', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="optimize__options[enable_cleanup]" id="enable_cleanup" <?php checked( $optimize_options[ Optimize::KEY_ENABLE_CLEANUP ] ?? '', 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to enable', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses( __( 'Strips emoji scripts, RSD/WLW links, shortlinks, generator tag, REST API links, and feed links from <code>wp_head</code>.', HDA_TEXTDOMAIN ), [ 'code' => [] ] ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- DATABASE OPTIMIZER -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<?php
	$db_options  = DatabaseOptimizer::getOptions();
	$db_schedule = $db_options[ DatabaseOptimizer::KEY_SCHEDULE ] ?? '';
	$db_counts   = DatabaseOptimizer::getCounts();

	$db_tasks = [
		DatabaseOptimizer::KEY_REVISIONS       => [ 'label' => __( 'Post Revisions', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-backup' ],
		DatabaseOptimizer::KEY_AUTO_DRAFTS     => [ 'label' => __( 'Auto-Drafts', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-edit' ],
		DatabaseOptimizer::KEY_TRASH_POSTS     => [ 'label' => __( 'Trashed Posts', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-trash' ],
		DatabaseOptimizer::KEY_SPAM_COMMENTS   => [ 'label' => __( 'Spam Comments', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-flag' ],
		DatabaseOptimizer::KEY_TRASH_COMMENTS  => [ 'label' => __( 'Trashed Comments', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-admin-comments' ],
		DatabaseOptimizer::KEY_TRANSIENTS      => [ 'label' => __( 'Expired Transients', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-clock' ],
		DatabaseOptimizer::KEY_ORPHAN_POSTMETA => [ 'label' => __( 'Orphaned Post Meta', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-database-remove' ],
		DatabaseOptimizer::KEY_ORPHAN_TERMMETA => [ 'label' => __( 'Orphaned Term Meta', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-tag' ],
		DatabaseOptimizer::KEY_OPTIMIZE_TABLES => [ 'label' => __( 'Optimize Tables', HDA_TEXTDOMAIN ), 'icon' => 'dashicons-performance' ],
	];
	?>

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Database Optimizer', HDA_TEXTDOMAIN ); ?></legend>

		<!-- Schedule -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Schedule', HDA_TEXTDOMAIN ); ?></h4>
		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-select">
				<label class="heading" for="db_schedule"><?php esc_html_e( 'Auto Cleanup', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="db_optimizer__options[schedule]" id="db_schedule">
								<option value="" <?php selected( $db_schedule, '' ); ?>><?php esc_html_e( 'Disabled', HDA_TEXTDOMAIN ); ?></option>
								<option value="daily" <?php selected( $db_schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', HDA_TEXTDOMAIN ); ?></option>
								<option value="weekly" <?php selected( $db_schedule, 'weekly' ); ?>><?php esc_html_e( 'Weekly', HDA_TEXTDOMAIN ); ?></option>
								<option value="monthly" <?php selected( $db_schedule, 'monthly' ); ?>><?php esc_html_e( 'Monthly', HDA_TEXTDOMAIN ); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Runs via WP-Cron. All checked tasks below are included in scheduled runs.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section">
				<?php
				$next_db = wp_next_scheduled( 'hda_db_optimizer_cleanup' );
				if ( $next_db ) :
					?>
					<span class="heading"><?php esc_html_e( 'Next Run', HDA_TEXTDOMAIN ); ?></span>
					<div class="option">
						<div class="controls">
							<code><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $next_db ) ); ?></code>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Cleanup Tasks -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Cleanup Tasks', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Select the items you want to clean up manually or include in scheduled runs. Checked tasks will also be executed automatically if a schedule is set above.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
		<div class="container flex flex-x gap sm-up-1 md-up-2 lg-up-3">
			<?php foreach ( $db_tasks as $key => $task ) :
				$count   = $db_counts[ $key ] ?? 0;
				$checked = ! empty( $db_options[ $key ] );
				$isOptimize = ( $key === DatabaseOptimizer::KEY_OPTIMIZE_TABLES );
				?>
				<div class="cell section section-checkbox hda-db-task" data-task="<?php echo esc_attr( $key ); ?>">
					<label class="heading" for="db_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $task['label'] ); ?></label>
					<div class="option">
						<div class="controls">
							<input type="checkbox" class="checkbox hda-db-check" name="db_optimizer__options[<?php echo esc_attr( $key ); ?>]" id="db_<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?> value="1">
						</div>
						<div class="explain">
							<?php if ( $isOptimize ) : ?>
								<?php esc_html_e( 'Defragment and reclaim disk space', HDA_TEXTDOMAIN ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Check to include', HDA_TEXTDOMAIN ); ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="desc">
						<?php if ( $isOptimize ) : ?>
							<?php esc_html_e( 'Run OPTIMIZE TABLE on all WordPress tables.', HDA_TEXTDOMAIN ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Found:', HDA_TEXTDOMAIN ); ?>
							<strong style="color:<?php echo $count > 0 ? '#d63638' : '#46b450'; ?>;">
								<span class="hda-db-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
							</strong>
							<?php esc_html_e( 'items', HDA_TEXTDOMAIN ); ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="hda-db-actions" style="margin-top:16px;">
			<label for="hda-db-select-all" class="button hda-db-select-all-btn">
				<input type="checkbox" id="hda-db-select-all">
				<?php esc_html_e( 'Select All', HDA_TEXTDOMAIN ); ?>
			</label>
			<button type="button" id="hda-db-optimize-btn" class="button button-primary" disabled>
				<span class="dashicons dashicons-database-remove"></span>
				<?php esc_html_e( 'Run Selected Now', HDA_TEXTDOMAIN ); ?>
			</button>
			<span id="hda-db-optimize-status" class="hda-db-status"></span>
		</div>
	</fieldset>
</div>
