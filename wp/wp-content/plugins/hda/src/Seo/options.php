<?php
/**
 * SEO module options panel.
 *
 * @package HDAddons\Seo
 */

use HDAddons\Helper;
use HDAddons\Seo\Seo;

\defined( 'ABSPATH' ) || exit;

$seo_options       = Helper::getOption( Seo::OPTION_NAME, [] );
$default_image     = $seo_options[ Seo::KEY_DEFAULT_OG_IMAGE ] ?? '';
$fb_app_id         = $seo_options[ Seo::KEY_FB_APP_ID ] ?? '';
$twitter_site      = $seo_options[ Seo::KEY_TWITTER_SITE ] ?? '';
$title_separator   = $seo_options[ Seo::KEY_TITLE_SEPARATOR ] ?? '-';
$verification_tags = $seo_options[ Seo::KEY_VERIFICATION_TAGS ] ?? '';

// Resolve image URL for preview.
$preview_url = '';
if ( is_numeric( $default_image ) && (int) $default_image > 0 ) {
	$preview_url = wp_get_attachment_image_url( (int) $default_image, 'medium' );
} elseif ( ! empty( $default_image ) ) {
	$preview_url = $default_image;
}

?>
<div class="container">
	<input type="hidden" name="seo-hidden" value="1">

	<!-- Open Graph / Social -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Social Sharing Defaults', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Lightweight SEO meta tags. Auto-disables when Yoast, Rank Math, or another SEO plugin is active. Per-post overrides available via ACF fields (seo_title, seo_description, seo_image, seo_noindex).', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-media-upload">
				<label class="heading"><?php esc_html_e( 'Default OG Image', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="hda-media-upload hda-media-upload--lg" data-preview="medium">
							<div class="hda-media-preview<?php echo $preview_url ? '' : ' empty'; ?>">
								<?php if ( $preview_url ) : ?>
									<img src="<?php echo esc_url( $preview_url ); ?>" alt="OG Image">
								<?php else : ?>
									<span class="dashicons dashicons-format-image"></span>
								<?php endif; ?>
							</div>
							<input type="hidden" name="seo_default_og_image" id="seo_default_og_image" value="<?php echo esc_attr( $default_image ); ?>" class="hda-media-value">
							<div style="display:flex;gap:6px;margin-top:8px;">
								<button type="button" class="button js-media-select"><?php esc_html_e( 'Select Image', HDA_TEXTDOMAIN ); ?></button>
								<button type="button" class="button js-media-remove<?php echo $preview_url ? '' : ' hidden'; ?>"><?php esc_html_e( 'Remove', HDA_TEXTDOMAIN ); ?></button>
							</div>
						</div>
					</div>
				</div>
				<div class="desc">
					<?php esc_html_e( 'Fallback image for Open Graph when no featured image or ACF seo_image is set. Recommended: 1200×630.', HDA_TEXTDOMAIN ); ?>
				</div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="seo_fb_app_id"><?php esc_html_e( 'Facebook App ID', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="text" class="input" name="seo_fb_app_id" id="seo_fb_app_id" value="<?php echo esc_attr( $fb_app_id ); ?>" placeholder="e.g. 123456789012345">
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Optional. Outputs fb:app_id meta tag for Facebook Insights integration.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="seo_twitter_site"><?php esc_html_e( 'Twitter / X Username', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="text" class="input" name="seo_twitter_site" id="seo_twitter_site" value="<?php echo esc_attr( $twitter_site ); ?>" placeholder="@username">
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Optional. Outputs twitter:site meta tag for Twitter Card attribution.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- Title -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Title Settings', HDA_TEXTDOMAIN ); ?></legend>
		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-select">
				<label class="heading" for="seo_title_separator"><?php esc_html_e( 'Title Separator', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="seo_title_separator" id="seo_title_separator">
								<?php
								$separators = [
									'–'  => '– (en dash)',
									'—'  => '— (em dash)',
									'|'  => '| (pipe)',
									'·'  => '· (middle dot)',
									'»'  => '» (guillemet)',
									'•'  => '• (bullet)',
									'-'  => '- (hyphen)',
								];
								foreach ( $separators as $sep_value => $sep_label ) {
									printf(
										'<option value="%s"%s>%s</option>',
										esc_attr( $sep_value ),
										selected( $title_separator, $sep_value, false ),
										esc_html( $sep_label )
									);
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc">
					<?php
					printf(
						/* translators: %1$s: example title format */
						esc_html__( 'Character between page title and site name. Preview: %1$s', HDA_TEXTDOMAIN ),
						'<code>' . esc_html__( 'Page Title', HDA_TEXTDOMAIN ) . ' ' . esc_html( $title_separator ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . '</code>'
					);
					?>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- Verification -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Site Verification', HDA_TEXTDOMAIN ); ?></legend>
		<div class="container flex flex-x gap sm-up-1">
			<div class="cell section section-textarea">
				<label class="heading" for="seo_verification_tags"><?php esc_html_e( 'Verification Meta Tags', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<textarea class="textarea" name="seo_verification_tags" id="seo_verification_tags" rows="4" placeholder='<?php echo esc_attr( "<meta name=\"google-site-verification\" content=\"abc123\">\n<meta name=\"msvalidate.01\" content=\"xyz456\">" ); ?>'><?php echo esc_textarea( $verification_tags ); ?></textarea>
					</div>
				</div>
				<div class="desc">
					<?php esc_html_e( 'Paste verification meta tags from Google Search Console, Bing Webmaster, Yandex, Pinterest, etc. One tag per line.', HDA_TEXTDOMAIN ); ?>
				</div>
			</div>
		</div>
	</fieldset>
</div>
