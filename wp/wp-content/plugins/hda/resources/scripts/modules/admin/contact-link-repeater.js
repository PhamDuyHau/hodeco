/**
 * Contact Link Repeater functionality.
 *
 * @param {jQuery} $ jQuery reference.
 */
export function initContactLinkRepeater($) {
	const $container = $('#contact-link-items');
	if (!$container.length) return;

	const defaultItem = $container.data('default') || {};
	const i18n = window.hdaContactLinkI18n || {
		newContact: 'New Contact',
		remove: 'Remove',
		selectIcon: 'Select Icon',
		useThisIcon: 'Use this icon',
		atLeastOne: 'You must have at least one contact link.',
	};

	// Make items sortable
	$container.sortable({
		handle: '.drag-handle',
		placeholder: 'repeater-item ui-sortable-placeholder',
		update: function () {
			updateItemOrders();
		},
	});

	// Toggle item collapse
	$container.on('click', '.toggle-item, .item-title', function (e) {
		e.stopPropagation();
		const $item = $(this).closest('.repeater-item');
		$item.toggleClass('collapsed');
	});

	// Update item title on name change
	$container.on('input', '.item-name', function () {
		const $item = $(this).closest('.repeater-item');
		const name = $(this).val() || i18n.newContact;
		$item.find('.item-title').text(name);
	});

	// Remove item
	$container.on('click', '.remove-item', function (e) {
		e.stopPropagation();
		const $item = $(this).closest('.repeater-item');

		if ($container.find('.repeater-item').length > 1) {
			$item.slideUp(200, function () {
				$(this).remove();
				updateItemOrders();
			});
		} else {
			alert(i18n.atLeastOne);
		}
	});

	// Add new item
	$('#add-contact-item').on('click', function () {
		const index = $container.find('.repeater-item').length;
		const id = generateUUID();
		const $newItem = createItemHTML(index, id, i18n);

		$container.append($newItem);
		$newItem.hide().slideDown(200);

		// Scroll to new item
		$('html, body').animate(
			{
				scrollTop: $newItem.offset().top - 100,
			},
			300,
		);
	});

	// Media upload is handled by the generic .hda-media-upload handler.

	/**
	 * Update order values for all items.
	 */
	function updateItemOrders() {
		$container.find('.repeater-item').each(function (index) {
			$(this).attr('data-index', index);
			$(this).find('.item-order').val(index);

			// Update field names
			$(this)
				.find('[name^="contact_items["]')
				.each(function () {
					const name = $(this).attr('name');
					const newName = name.replace(/contact_items\[\d+\]/, 'contact_items[' + index + ']');
					$(this).attr('name', newName);

					// Update ID if exists
					const id = $(this).attr('id');
					if (id) {
						const newId = id.replace(/_\d+$/, '_' + index);
						$(this).attr('id', newId);
					}
				});

			// Update label for attributes
			$(this)
				.find('label[for]')
				.each(function () {
					const forAttr = $(this).attr('for');
					const newFor = forAttr.replace(/_\d+$/, '_' + index);
					$(this).attr('for', newFor);
				});
		});
	}

	/**
	 * Generate a UUID v4.
	 *
	 * @returns {string}
	 */
	function generateUUID() {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			const r = (Math.random() * 16) | 0;
			const v = c === 'x' ? r : (r & 0x3) | 0x8;
			return v.toString(16);
		});
	}

	/**
	 * Create HTML for a new repeater item.
	 *
	 * @param {number} index Item index.
	 * @param {string} id    Unique ID.
	 * @param {Object} i18n  Translations.
	 * @returns {jQuery}
	 */
	function createItemHTML(index, id, i18n) {
		return $(`
                <div class="repeater-item" data-index="${index}">
                    <div class="repeater-item-header">
                        <span class="drag-handle dashicons dashicons-move"></span>
                        <span class="item-title">${i18n.newContact}</span>
                        <button type="button" class="toggle-item" aria-expanded="true">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                        <button type="button" class="remove-item" title="${i18n.remove}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>

                    <div class="repeater-item-content">
                        <input type="hidden" name="contact_items[${index}][id]" value="${id}">
                        <input type="hidden" name="contact_items[${index}][order]" value="${index}" class="item-order">

                        <div class="field-row field-icon">
                            <label>${i18n.icon || 'Icon'}</label>
                            <div class="hda-media-upload" data-title="${i18n.selectIcon}" data-button="${i18n.useThisIcon}" data-library="image,image/svg+xml">
                                <div class="hda-media-preview empty">
                                    <span class="dashicons dashicons-format-image"></span>
                                </div>
                                <input type="hidden" name="contact_items[${index}][icon]" value="" class="hda-media-value">
                                <div style="display:flex;gap:6px;margin-top:6px;">
                                    <button type="button" class="button js-media-select">${i18n.selectIcon}</button>
                                    <button type="button" class="button js-media-remove hidden">${i18n.remove}</button>
                                </div>
                            </div>
                            <p class="field-desc">${i18n.iconDesc || 'Select an image or SVG from the media library.'}</p>
                        </div>

                        <div class="field-row">
                            <label for="contact_name_${index}">${i18n.name || 'Name'}</label>
                            <input type="text" id="contact_name_${index}" name="contact_items[${index}][name]" value="" class="regular-text item-name" placeholder="${i18n.namePlaceholder || 'e.g., Hotline, Zalo, Facebook'}">
                        </div>

                        <div class="field-row">
                            <label for="contact_value_${index}">${i18n.linkValue || 'Link/Value'}</label>
                            <input type="text" id="contact_value_${index}" name="contact_items[${index}][value]" value="" class="regular-text" placeholder="${i18n.valuePlaceholder || 'e.g., tel:+84123456789, https://zalo.me/...'}">
                        </div>

                        <div class="field-row field-row-inline">
                            <div class="field-col">
                                <label for="contact_target_${index}">${i18n.target || 'Target'}</label>
                                <select id="contact_target_${index}" name="contact_items[${index}][target]">
                                    <option value="_blank">${i18n.targetBlank || 'New Tab (_blank)'}</option>
                                    <option value="_self">${i18n.targetSelf || 'Same Tab (_self)'}</option>
                                </select>
                            </div>

                            <div class="field-col">
                                <label for="contact_class_${index}">${i18n.cssClass || 'CSS Class'}</label>
                                <input type="text" id="contact_class_${index}" name="contact_items[${index}][class]" value="" class="regular-text" placeholder="${i18n.classPlaceholder || 'e.g., hotline'}">
                            </div>

                            <div class="field-col">
                                <label for="contact_color_${index}">${i18n.color || 'Color'}</label>
                                <input type="text" id="contact_color_${index}" name="contact_items[${index}][color]" value="" class="hda-color-field" placeholder="#000000">
                            </div>
                        </div>
                    </div>
                </div>
            `);
	}
}
