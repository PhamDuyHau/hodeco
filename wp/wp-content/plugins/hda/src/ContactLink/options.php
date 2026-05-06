<?php

use HDAddons\Helper;
use HDAddons\ContactLink\ContactLink;

\defined( 'ABSPATH' ) || exit;

$contactItems = ContactLink::getItems();
$defaultItem  = ContactLink::getDefaultItem();

?>
<div class="container">
	<input type="hidden" name="contact-link-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Contact Links', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-phone"></span>
				<?php esc_html_e( 'Manage contact buttons (Hotline, Zalo, Messenger) displayed floating on the website.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="contact-link-repeater">
			<div id="contact-link-items" class="repeater-items" data-default='<?php echo esc_attr( wp_json_encode( $defaultItem ) ); ?>'>
				<?php
				foreach ( $contactItems as $index => $item ) :

					$item = wp_parse_args( $item, $defaultItem );
					$id   = $item['id'] ?: wp_generate_uuid4();

					// Determine icon display.
					$iconPreview = '';
					$iconValue   = $item['icon'];

					if ( is_numeric( $iconValue ) ) {
						$attachmentUrl = wp_get_attachment_image_url( (int) $iconValue, 'thumbnail' );
						if ( $attachmentUrl ) {
							$iconPreview = '<img src="' . esc_url( $attachmentUrl ) . '" alt="icon">';
						}
					} elseif ( ! empty( $iconValue ) ) {
						$iconPreview = Helper::renderIcon( $iconValue, $item['name'] );
					}
				?>
				<div class="repeater-item collapsed" data-index="<?php echo esc_attr( $index ); ?>">
					<div class="repeater-item-header">
						<span class="drag-handle dashicons dashicons-move"></span>
						<span class="item-title"><?php echo esc_html( $item['name'] ?: __( 'New Contact', HDA_TEXTDOMAIN ) ); ?></span>
						<button type="button" class="toggle-item" aria-expanded="false">
							<span class="dashicons dashicons-arrow-up-alt2"></span>
						</button>
						<button type="button" class="remove-item" title="<?php esc_attr_e( 'Remove', HDA_TEXTDOMAIN ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</button>
					</div>

					<div class="repeater-item-content">
						<input type="hidden" name="contact_items[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $id ); ?>">
						<input type="hidden" name="contact_items[<?php echo esc_attr( $index ); ?>][order]" value="<?php echo esc_attr( $item['order'] ?? $index ); ?>" class="item-order">

						<div class="field-row field-icon">
							<label><?php esc_html_e( 'Icon', HDA_TEXTDOMAIN ); ?></label>
							<div class="hda-media-upload" data-title="<?php esc_attr_e( 'Select Icon', HDA_TEXTDOMAIN ); ?>" data-button="<?php esc_attr_e( 'Use this icon', HDA_TEXTDOMAIN ); ?>" data-library="image,image/svg+xml" data-preview="thumbnail">
								<div class="hda-media-preview <?php echo empty( $iconPreview ) ? 'empty' : ''; ?>">
									<?php
									if ( $iconPreview ) {
										echo $iconPreview;
									} else {
										echo '<span class="dashicons dashicons-format-image"></span>';
									}
									?>
								</div>
								<?php
								// For SVG strings, use base64 encoding to preserve content
								$iconFieldValue = $iconValue;
								if ( ! empty( $iconValue ) && ! is_numeric( $iconValue ) && str_starts_with( $iconValue, '<svg' ) ) {
									$iconFieldValue = 'base64:' . base64_encode( $iconValue );
								}
								?>
								<input type="hidden" name="contact_items[<?php echo esc_attr( $index ); ?>][icon]" value="<?php echo esc_attr( $iconFieldValue ); ?>" class="hda-media-value">
								<div style="display:flex;gap:6px;margin-top:6px;">
									<button type="button" class="button js-media-select"><?php esc_html_e( 'Select Icon', HDA_TEXTDOMAIN ); ?></button>
									<button type="button" class="button js-media-remove <?php echo empty( $iconValue ) ? 'hidden' : ''; ?>"><?php esc_html_e( 'Remove', HDA_TEXTDOMAIN ); ?></button>
								</div>
								<p class="field-desc"><?php esc_html_e( 'Select an image or SVG from the media library.', HDA_TEXTDOMAIN ); ?></p>
							</div>

							<div class="field-row">
								<label for="contact_name_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Name', HDA_TEXTDOMAIN ); ?></label>
								<input type="text" id="contact_name_<?php echo esc_attr( $index ); ?>" name="contact_items[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $item['name'] ); ?>" class="regular-text item-name" placeholder="<?php esc_attr_e( 'e.g., Hotline, Zalo, Facebook', HDA_TEXTDOMAIN ); ?>">
							</div>

							<div class="field-row">
								<label for="contact_value_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Link/Value', HDA_TEXTDOMAIN ); ?></label>
								<input type="text" id="contact_value_<?php echo esc_attr( $index ); ?>" name="contact_items[<?php echo esc_attr( $index ); ?>][value]" value="<?php echo esc_attr( $item['value'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., tel:+84123456789, https://zalo.me/...', HDA_TEXTDOMAIN ); ?>">
							</div>

							<div class="field-row field-row-inline">
								<div class="field-col">
									<label for="contact_target_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Target', HDA_TEXTDOMAIN ); ?></label>
									<select id="contact_target_<?php echo esc_attr( $index ); ?>" name="contact_items[<?php echo esc_attr( $index ); ?>][target]">
										<option value="_blank" <?php selected( $item['target'], '_blank' ); ?>><?php esc_html_e( 'New Tab (_blank)', HDA_TEXTDOMAIN ); ?></option>
										<option value="_self" <?php selected( $item['target'], '_self' ); ?>><?php esc_html_e( 'Same Tab (_self)', HDA_TEXTDOMAIN ); ?></option>
									</select>
								</div>

								<div class="field-col">
									<label for="contact_class_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'CSS Class', HDA_TEXTDOMAIN ); ?></label>
									<input type="text" id="contact_class_<?php echo esc_attr( $index ); ?>" name="contact_items[<?php echo esc_attr( $index ); ?>][class]" value="<?php echo esc_attr( $item['class'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., hotline', HDA_TEXTDOMAIN ); ?>">
								</div>

								<div class="field-col">
									<label for="contact_color_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Color', HDA_TEXTDOMAIN ); ?></label>
									<input type="text" id="contact_color_<?php echo esc_attr( $index ); ?>" name="contact_items[<?php echo esc_attr( $index ); ?>][color]" value="<?php echo esc_attr( $item['color'] ); ?>" class="hda-color-field" placeholder="#000000">
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="repeater-footer">
				<button type="button" id="add-contact-item" class="button button-primary">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Contact Link', HDA_TEXTDOMAIN ); ?>
				</button>
			</div>
		</div>
	</fieldset>
</div>
