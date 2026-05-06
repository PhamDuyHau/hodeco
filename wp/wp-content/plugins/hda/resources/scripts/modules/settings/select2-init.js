import { isValidEmail, isValidIPRange } from './validation.js';

/**
 * Initialize all Select2 instances.
 *
 * @param {jQuery} $ jQuery reference.
 */
export function initSelect2($) {
	// Select2 multiple
	$('.select2-multiple').each(function () {
		$(this).select2({
			multiple: true,
			allowClear: true,
			width: 'resolve',
			dropdownAutoWidth: true,
			placeholder: $(this).attr('placeholder'),
		});
	});

	// Select2 tags
	$('.select2-tags').each(function () {
		$(this).select2({
			multiple: true,
			tags: true,
			allowClear: true,
			width: 'resolve',
			dropdownAutoWidth: true,
			placeholder: $(this).attr('placeholder'),
		});
	});

	// Select2 IPs
	$('.select2-ips').each(function () {
		$(this).select2({
			multiple: true,
			tags: true,
			allowClear: true,
			width: 'resolve',
			dropdownAutoWidth: true,
			placeholder: $(this).attr('placeholder'),
			createTag: function (params) {
				const term = params.term.trim();
				if (isValidIPRange(term)) {
					return { id: term, text: term };
				}
				return null;
			},
		});
	});

	// Select2 emails
	$('.select2-emails').each(function () {
		$(this).select2({
			multiple: true,
			tags: true,
			allowClear: true,
			width: 'resolve',
			dropdownAutoWidth: true,
			placeholder: $(this).attr('placeholder'),
			createTag: function (params) {
				const term = params.term.trim();
				if (isValidEmail(term)) {
					return { id: term, text: term };
				}
				return null;
			},
		});
	});
}
