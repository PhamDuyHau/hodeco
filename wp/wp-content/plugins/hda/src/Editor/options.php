<?php
/**
 * Editor module options panel.
 *
 * @package HDAddons\Editor
 */

use HDAddons\Editor\Editor;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$editor_options                     = Helper::getOption( Editor::OPTION_NAME, [] );
$use_widgets_block_editor_off       = $editor_options[ Editor::KEY_WIDGETS_BLOCK_EDITOR_OFF ] ?? '';
$use_block_editor_for_post_type_off = $editor_options[ Editor::KEY_BLOCK_EDITOR_OFF ] ?? '';
$block_style_off                    = $editor_options[ Editor::KEY_BLOCK_STYLE_OFF ] ?? '';
$font_library_off                   = $editor_options[ Editor::KEY_FONT_LIBRARY_OFF ] ?? '';
$remote_patterns_off                = $editor_options[ Editor::KEY_REMOTE_PATTERNS_OFF ] ?? '';
$openverse_off                      = $editor_options[ Editor::KEY_OPENVERSE_OFF ] ?? '';
$site_editor_off                    = $editor_options[ Editor::KEY_SITE_EDITOR_OFF ] ?? '';

// Hidden class for JS toggle
$block_editor_dependent_class = $use_block_editor_for_post_type_off ? ' hidden' : '';

?>
<div class="container">
	<input type="hidden" name="editor-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Block Editor Settings', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Control Gutenberg Block Editor features. For classic themes, disabling unused features can improve page load speed.', HDA_TEXTDOMAIN ); ?>
			</p>
			<p class="hda-notice__detail">
				<?php esc_html_e( 'Some options are hidden when Block Editor is completely disabled.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="use_widgets_block_editor_off"><?php esc_html_e( 'Disable Widgets Block Editor', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="use_widgets_block_editor_off" id="use_widgets_block_editor_off" <?php checked( $use_widgets_block_editor_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Use classic widgets instead of block-based widgets.', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="use_block_editor_for_post_type_off"><?php esc_html_e( 'Disable Block Editor', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="use_block_editor_for_post_type_off" id="use_block_editor_for_post_type_off" <?php checked( $use_block_editor_for_post_type_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Use Classic Editor for all post types.', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="block_style_off"><?php esc_html_e( 'Remove Block CSS', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="block_style_off" id="block_style_off" <?php checked( $block_style_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Remove block library CSS, global styles, and emoji styles.', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-checkbox block-editor-dependent<?php echo esc_attr( $block_editor_dependent_class ); ?>">
				<label class="heading" for="font_library_off"><?php esc_html_e( 'Disable Font Library', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="font_library_off" id="font_library_off" <?php checked( $font_library_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Hide Font Library UI in Site Editor (WP 6.5+).', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-checkbox block-editor-dependent<?php echo esc_attr( $block_editor_dependent_class ); ?>">
				<label class="heading" for="remote_patterns_off"><?php esc_html_e( 'Disable Remote Patterns', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="remote_patterns_off" id="remote_patterns_off" <?php checked( $remote_patterns_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Disable remote block patterns from WordPress.org.', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-checkbox block-editor-dependent<?php echo esc_attr( $block_editor_dependent_class ); ?>">
				<label class="heading" for="openverse_off"><?php esc_html_e( 'Disable Openverse', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="openverse_off" id="openverse_off" <?php checked( $openverse_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Hide Openverse image integration in media inserter.', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="site_editor_off"><?php esc_html_e( 'Hide Site Editor Menu', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="site_editor_off" id="site_editor_off" <?php checked( $site_editor_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Hide Site Editor menu (for classic themes).', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
		</div>
	</fieldset>
</div>
