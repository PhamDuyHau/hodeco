<?php

use HDAddons\GlobalSetting\GlobalSetting;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

// Module config
$menu_options           = GlobalSetting::getConfig();
$global_setting_options = Helper::getOption( GlobalSetting::OPTION_NAME, [] );
$current_slug           = $current_slug ?? 'global_setting';

?>
<div class="hda-bulk-actions">
	<button type="button" class="button button-primary" id="hda-enable-all">
		<span class="dashicons dashicons-yes-alt"></span>
		<?php esc_html_e( 'Enable All', HDA_TEXTDOMAIN ); ?>
	</button>
	<button type="button" class="button hda-btn-danger" id="hda-disable-all">
		<span class="dashicons dashicons-dismiss"></span>
		<?php esc_html_e( 'Disable All', HDA_TEXTDOMAIN ); ?>
	</button>
</div>

<div class="hda-notice hda-notice--info">
	<p>
		<span class="dashicons dashicons-admin-plugins"></span>
		<?php esc_html_e( 'Enable modules below to activate their features. Once enabled, each module\'s settings will appear in the left menu.', HDA_TEXTDOMAIN ); ?>
	</p>
	<p class="hda-notice__detail">
		<?php esc_html_e( 'Disable unused modules to improve performance and reduce database queries.', HDA_TEXTDOMAIN ); ?>
	</p>
</div>

<div class="container flex flex-x gap sm-up-2 md-up-3 lg-up-4">
	<?php
	foreach ( $menu_options as $slug => $value ) :
		$menu_title  = ! empty( $value['title'] ) ? $value['title'] : '';
		$description = ! empty( $value['description'] ) ? $value['description'] : '';

		if ( $slug === $current_slug ) {
			continue;
		}

		?>
		<div class="section section-checkbox cell" id="section_<?php echo esc_attr( $slug ); ?>">
			<label class="heading" for="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $menu_title ); ?></label>
			<div class="option">
				<div class="controls">
					<input type="checkbox" class="checkbox" name="<?php echo esc_attr( $slug ); ?>" id="<?php echo esc_attr( $slug ); ?>" <?php checked( $global_setting_options[ $slug ] ?? false, 1 ); ?> value="1">
				</div>
				<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="desc"><?php echo esc_html( $description ); ?></div>
		</div>
	<?php endforeach; ?>
</div>

<?php
// ─── Data Cleanup Settings ─────────────────────────
$clean_uninstall = Helper::getOption( GlobalSetting::KEY_CLEAN_UNINSTALL, false );
?>
<div class="hda-data-cleanup">
	<h3 class="hda-data-cleanup__title">
		<span class="dashicons dashicons-database-remove"></span>
		<?php esc_html_e( 'Data Cleanup', HDA_TEXTDOMAIN ); ?>
	</h3>
	<div class="section section-checkbox hda-data-cleanup__option">
		<div class="option">
			<div class="controls">
				<input type="checkbox" class="checkbox" name="<?php echo esc_attr( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>" id="<?php echo esc_attr( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>" <?php checked( $clean_uninstall, 1 ); ?> value="1">
			</div>
			<label class="explain hda-data-cleanup__label" for="<?php echo esc_attr( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>">
				<?php esc_html_e( 'Delete all plugin data when uninstalling', HDA_TEXTDOMAIN ); ?>
			</label>
		</div>
		<p class="hda-data-cleanup__hint">
			<?php esc_html_e( 'All settings, tables, and stored data will be permanently deleted on uninstall. Leave unchecked to preserve data.', HDA_TEXTDOMAIN ); ?>
		</p>
	</div>

	<div class="hda-data-cleanup__note">
		<span class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'Note: Disabling a module (unchecking above) only stops it from loading — its saved settings are preserved. Settings are only cleaned up when a module is removed from config.php.', HDA_TEXTDOMAIN ); ?>
	</div>
</div>

<script>
(function () {
	const panel = document.getElementById('global_setting_settings');
	if (!panel) return;

	const enableBtn = document.getElementById('hda-enable-all');
	const disableBtn = document.getElementById('hda-disable-all');

	function toggleAll(checked) {
		const boxes = panel.querySelectorAll('.section-checkbox .checkbox');
		boxes.forEach(function (cb) {
			// Don't toggle the uninstall cleanup checkbox
			if (cb.name === '<?php echo esc_js( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>') return;
			cb.checked = checked;
		});
	}

	enableBtn?.addEventListener('click', function () { toggleAll(true); });
	disableBtn?.addEventListener('click', function () { toggleAll(false); });
})();
</script>
