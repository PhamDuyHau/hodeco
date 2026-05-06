<?php

use HDAddons\GlobalSetting\GlobalSetting;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

?>
<div id="_content" class="tabs-content">
	<h2 class="hidden-text"></h2>
	<?php
	// Module config
	$menu_options           = GlobalSetting::getConfig();
	$global_setting_options = Helper::getOption( GlobalSetting::OPTION_NAME, [] );

	foreach ( $menu_options as $current_slug => $value ) :
		$current_title       = ! empty( $value['title'] ) ? $value['title'] : '';
		$current_description = ! empty( $value['description'] ) ? $value['description'] : '';

		// Check module active
		if ( empty( $global_setting_options[ $current_slug ] ) && 'global_setting' !== $current_slug ) {
			continue;
		}

		?>
		<div id="<?php echo esc_attr( $current_slug ); ?>_settings" class="group tabs-panel">
			<?php

			echo '<div class="section-heading">';
			echo '<h2>' . esc_html( $current_title ) . '</h2>';
			echo '<div class="desc">' . esc_html( $current_description ) . '</div>';
			echo '</div>';

			$option_file = HDA_PATH . 'src/' . Helper::capitalizedSlug( $current_slug, true ) . '/options.php';
			file_exists( $option_file ) && include $option_file;

			?>
		</div>
	<?php endforeach; ?>
	<div class="save-bar">
		<button type="submit" name="_submit_settings" class="button button-primary"><?php esc_html_e( 'Save Changes', HDA_TEXTDOMAIN ); ?></button>
	</div>
</div>
