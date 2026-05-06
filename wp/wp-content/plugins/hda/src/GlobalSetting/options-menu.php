<?php

use HDAddons\GlobalSetting\GlobalSetting;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

?>
<div id="_nav" class="tabs-nav">
	<div class="logo-title">
		<h3>
			<?php esc_html_e( 'HDA Settings', HDA_TEXTDOMAIN ); ?>
			<span>Version: <?php echo esc_html( HDA_VERSION ); ?></span>
		</h3>
	</div>
	<div class="save-bar">
		<button type="submit" name="_submit_settings" class="button button-primary"><?php esc_html_e( 'Save Changes', HDA_TEXTDOMAIN ); ?></button>
	</div>
	<ul class="ul-menu-list">
		<?php
		// Module config
		$menu_options           = GlobalSetting::getConfig();
		$global_setting_options = Helper::getOption( GlobalSetting::OPTION_NAME, [] );

		foreach ( $menu_options as $slug => $value ) :
			$menu_title = ! empty( $value['title'] ) ? $value['title'] : '';

			// Check module active
			if ( empty( $global_setting_options[ $slug ] ) && 'global_setting' !== $slug ) {
				continue;
			}

			?>
			<li class="<?php echo esc_attr( $slug ); ?>-settings">
				<a title="<?php echo esc_attr( $menu_title ); ?>" href="#<?php echo esc_attr( $slug ); ?>_settings"><?php echo esc_html( $menu_title ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
