<?php
/**
 * File module options panel.
 */

use HDAddons\File\File;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$upload_max_filesize    = ( ini_get( 'upload_max_filesize' ) !== false ) ? ini_get( 'upload_max_filesize' ) : '2M';
$upload_max_filesize_MB = Helper::convertToMB( $upload_max_filesize );
$file_options           = Helper::getOption( File::OPTION_NAME, [] );
$upload_size_limit      = $file_options[ File::KEY_UPLOAD_SIZE_LIMIT ] ?? '';
$svgs                   = $file_options[ File::KEY_SVGS ] ?? 'disable';
$svg_options            = [
	'disable'      => esc_html__( 'Disable SVG images', HDA_TEXTDOMAIN ),
	'sanitized'    => esc_html__( 'Sanitized SVG images', HDA_TEXTDOMAIN ),
	'unrestricted' => esc_html__( 'Unrestricted SVG images', HDA_TEXTDOMAIN ),
];

?>
<div class="container">
	<input type="hidden" name="file-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FILE UPLOAD & SVG -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'File Upload & SVG', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-media-default"></span>
				<?php esc_html_e( 'Control file upload limits and enable SVG support. Server limits may override these settings.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1">
			<div class="cell section section-text">
				<label class="heading" for="upload_size_limit"><?php esc_html_e( 'Maximum upload file size', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $upload_size_limit ); ?>" class="input" type="number" min="0" step="1" id="upload_size_limit" name="upload_size_limit">
					</div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( sprintf( __( 'Override the default upload size limit (in MB). Server maximum: <strong>%s MB</strong>', HDA_TEXTDOMAIN ), esc_html( $upload_max_filesize_MB ) ) ); ?>
				</div>
			</div>
			<div class="cell section section-radio">
				<span class="heading"><?php esc_html_e( 'SVG Images', HDA_TEXTDOMAIN ); ?></span>
				<div class="option inline-option">
					<div class="controls">
						<div class="inline-group">
							<?php foreach ( $svg_options as $key => $opt ) : ?>
							<label>
								<input type="radio" name="svgs" class="radio" id="svgs-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $svgs, $key ); ?> />
								<span><?php echo esc_html( $opt ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="hda-notice hda-notice--warning">
					<p>
						<span class="dashicons dashicons-warning"></span>
						<strong><?php esc_html_e( 'Security:', HDA_TEXTDOMAIN ); ?></strong>
						<?php esc_html_e( 'SVG files can contain malicious code (XSS attacks). Use "Sanitized" for safe uploads, or "Unrestricted" only if you trust all uploaders.', HDA_TEXTDOMAIN ); ?>
					</p>
				</div>
			</div>
		</div>
	</fieldset>

	<?php
	// ── Sub-module: File Integrity Scanner ──
	include __DIR__ . '/FileIntegrity/options.php';
	?>
</div>
