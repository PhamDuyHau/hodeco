<?php
/**
 * Custom Script module options panel.
 *
 * @package HDAddons\CustomScript
 */

use HDAddons\CustomCode\CustomScript;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$html_header      = Helper::getStoredOptionContent( CustomScript::KEY_HEADER );
$html_footer      = Helper::getStoredOptionContent( CustomScript::KEY_FOOTER );
$html_body_top    = Helper::getStoredOptionContent( CustomScript::KEY_BODY_TOP );
$html_body_bottom = Helper::getStoredOptionContent( CustomScript::KEY_BODY_BOTTOM );

?>
<fieldset class="container-fieldset">
	<legend class="section-legend"><?php esc_html_e( 'Custom Scripts', HDA_TEXTDOMAIN ); ?></legend>

	<div class="hda-notice hda-notice--info">
		<p>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Add custom HTML, JavaScript, or tracking codes (Google Analytics, Facebook Pixel, etc.) to specific locations on your site.', HDA_TEXTDOMAIN ); ?>
		</p>
		<p class="hda-notice__detail">
			<?php esc_html_e( 'Scripts are output on all pages. Wrap JavaScript code in <script> tags.', HDA_TEXTDOMAIN ); ?>
		</p>
	</div>

	<div class="container flex flex-x gap sm-up-1 lg-up-2">
		<div class="cell section section-textarea">
			<label class="heading" for="html_header"><?php esc_html_e( 'Header scripts', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="html_header" id="html_header" rows="4"><?php echo esc_textarea( $html_header ); ?></textarea>
				</div>
			</div>
			<div class="desc">Injected inside <code>&lt;head&gt;</code> via <code>wp_head</code>. Ideal for meta tags, analytics, and early-loading scripts.</div>
		</div>
		<div class="cell section section-textarea">
			<label class="heading" for="html_footer"><?php esc_html_e( 'Footer scripts', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="html_footer" id="html_footer" rows="4"><?php echo esc_textarea( $html_footer ); ?></textarea>
				</div>
			</div>
			<div class="desc">Injected before <code>&lt;/body&gt;</code> via <code>wp_footer</code>. Best for non-critical scripts.</div>
		</div>
		<div class="cell section section-textarea">
			<label class="heading" for="html_body_top"><?php esc_html_e( 'Body scripts - TOP', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="html_body_top" id="html_body_top" rows="4"><?php echo esc_textarea( $html_body_top ); ?></textarea>
				</div>
			</div>
			<div class="desc">Injected right after <code>&lt;body&gt;</code> opens via <code>wp_body_open</code>. Used by GTM and similar.</div>
		</div>
		<div class="cell section section-textarea">
			<label class="heading" for="html_body_bottom"><?php esc_html_e( 'Body scripts - BOTTOM', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="html_body_bottom" id="html_body_bottom" rows="4"><?php echo esc_textarea( $html_body_bottom ); ?></textarea>
				</div>
			</div>
			<div class="desc">Injected just before <code>&lt;/body&gt;</code>, after <code>wp_footer</code>. Last position in the document.</div>
		</div>
	</div>
</fieldset>
