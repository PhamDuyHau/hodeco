<?php
/**
 * Scheduled Content module options panel.
 *
 * @package HDAddons\ScheduledContent
 */

use HDAddons\ScheduledContent\ScheduledContent;

\defined( 'ABSPATH' ) || exit;

$sc_options = ScheduledContent::getOptions();
$enabled    = $sc_options[ ScheduledContent::KEY_ENABLED ] ?? false;
$post_types = $sc_options[ ScheduledContent::KEY_POST_TYPES ] ?? [];

// Get public post types for selection.
$available_types = get_post_types( [ 'public' => true ], 'objects' );
unset( $available_types['attachment'] );

?>
<div class="container">
	<input type="hidden" name="scheduled_content-hidden" value="1">
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Schedule Settings', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-calendar-alt"></span>
				<?php esc_html_e( 'Control post visibility with date ranges. Add ACF Date Time Picker fields named "scheduled_start" and "scheduled_end" to your post types. Posts outside their scheduled range will be hidden from the frontend.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1">
			<div class="cell section section-checkbox">
				<label class="heading" for="sc_enabled"><?php esc_html_e( 'Enable Scheduled Content', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="sc_enabled" id="sc_enabled" <?php checked( $enabled, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses( __( 'Posts outside their <code>scheduled_start</code> / <code>scheduled_end</code> range are hidden from frontend queries automatically.', HDA_TEXTDOMAIN ), [ 'code' => [] ] ); ?></div>
			</div>
			<div class="cell section section-radio">
			<span class="heading"><?php esc_html_e( 'Post Types', HDA_TEXTDOMAIN ); ?></span>
			<div class="option inline-option">
				<div class="controls">
					<div class="inline-group">
						<?php foreach ( $available_types as $pt_slug => $pt_obj ) : ?>
						<label>
							<input type="checkbox" name="sc_post_types[]" class="checkbox" value="<?php echo esc_attr( $pt_slug ); ?>" <?php checked( in_array( $pt_slug, $post_types, true ) ); ?>>
							<span><?php echo esc_html( $pt_obj->labels->singular_name ); ?></span>
						</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div class="desc"><?php esc_html_e( 'Only checked post types will have date-range filtering. Unchecked types are not affected.', HDA_TEXTDOMAIN ); ?></div>
		</div>
		</div>
	</fieldset>
</div>
