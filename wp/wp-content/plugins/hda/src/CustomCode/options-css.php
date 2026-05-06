<?php
/**
 * Custom CSS module options panel.
 *
 * @package HDAddons\CustomCss
 */

use HDAddons\CustomCode\CustomCss;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$css = Helper::getStoredOptionContent( CustomCss::OPTION_NAME );

?>
<fieldset class="container-fieldset">
	<legend class="section-legend"><?php esc_html_e( 'Custom CSS', HDA_TEXTDOMAIN ); ?></legend>

	<div class="hda-notice hda-notice--info">
		<p>
			<span class="dashicons dashicons-admin-appearance"></span>
			<?php esc_html_e( 'Add custom CSS to override theme styles. This CSS persists through theme updates and is loaded after all other stylesheets.', HDA_TEXTDOMAIN ); ?>
		</p>
		<p class="hda-notice__detail">
			<?php esc_html_e( 'No need to wrap in <style> tags. CSS is automatically minified in production.', HDA_TEXTDOMAIN ); ?>
		</p>
	</div>

	<div class="container flex flex-x gap sm-up-1">
		<div class="cell section section-textarea">
			<label class="heading" for="html_custom_css"><?php esc_html_e( 'Custom CSS', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_css" name="html_custom_css" id="html_custom_css" rows="8"><?php echo esc_textarea( $css ); ?></textarea>
				</div>
			</div>
			<div class="desc">
				Output as inline <code>&lt;style&gt;</code> via <code>wp_add_inline_style</code> — loaded after all enqueued stylesheets for reliable overrides.
			</div>
		</div>
	</div>
</fieldset>
