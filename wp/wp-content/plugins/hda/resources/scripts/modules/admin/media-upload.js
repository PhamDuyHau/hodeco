/**
 * Generic Media Upload Handler (.hda-media-upload).
 *
 * Supports data attributes:
 *   data-title    - Media frame title (default: "Select Image")
 *   data-button   - Frame button text (default: "Use this image")
 *   data-library  - Comma-separated mime types (default: "image")
 *   data-preview  - Preview size: "thumbnail" | "medium" | "large" (default: "medium")
 *
 * @param {jQuery} $ jQuery reference.
 */
export function initMediaUpload($) {
	$(document).on('click', '.js-media-select', function (e) {
		e.preventDefault();
		const $wrapper = $(this).closest('.hda-media-upload');
		const $input = $wrapper.find('.hda-media-value');
		const $preview = $wrapper.find('.hda-media-preview');
		const $removeBtn = $wrapper.find('.js-media-remove');

		const title = $wrapper.data('title') || 'Select Image';
		const buttonText = $wrapper.data('button') || 'Use this image';
		const previewSize = $wrapper.data('preview') || 'medium';

		// Parse library types from data attribute.
		let libraryType = 'image';
		const rawLibrary = $wrapper.data('library');
		if (rawLibrary) {
			const types = String(rawLibrary)
				.split(',')
				.map((s) => s.trim())
				.filter(Boolean);
			libraryType = types.length > 1 ? types : types[0] || 'image';
		}

		const frame = wp.media({
			title: title,
			button: { text: buttonText },
			multiple: false,
			library: { type: libraryType },
		});

		frame.on('select', function () {
			const attachment = frame.state().get('selection').first().toJSON();

			$input.val(attachment.id);

			// Pick best preview URL.
			const url = attachment.sizes?.[previewSize]?.url || attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;

			$preview.html('<img src="' + url + '" alt="preview">').removeClass('empty');
			$removeBtn.removeClass('hidden');
		});

		frame.open();
	});

	$(document).on('click', '.js-media-remove', function (e) {
		e.preventDefault();
		const $wrapper = $(this).closest('.hda-media-upload');

		$wrapper.find('.hda-media-value').val('');
		$wrapper.find('.hda-media-preview').html('<span class="dashicons dashicons-format-image"></span>').addClass('empty');
		$(this).addClass('hidden');
	});
}
