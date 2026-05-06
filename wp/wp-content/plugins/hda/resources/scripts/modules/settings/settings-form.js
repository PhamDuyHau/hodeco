/**
 * Settings form AJAX submit + toast notifications.
 *
 * @param {jQuery} $ jQuery reference.
 */
export function initSettingsForm($) {
	$(document).on('submit', '#_settings_form', function (e) {
		e.preventDefault();
		const $this = $(this);
		const $data = $this.serializeObject();

		const btnSubmit = $this.find('button[name="_submit_settings"]');
		const buttonText = btnSubmit.html();
		const buttonTextLoading = '<span class="ajax-loader">&nbsp;</span>';

		btnSubmit.prop('disabled', true).html(buttonTextLoading);

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			dataType: 'json',
			data: {
				action: 'submit_settings',
				_data: $data,
				_ajax_nonce: $this.find('input[name="_wpnonce"]').val(),
				_wp_http_referer: $this.find('input[name="_wp_http_referer"]').val(),
			},
		})
			.done(function (response) {
				// Show toast notification
				if (response?.data?.message) {
					showToast($, response.data.message, response.data.type || 'success', response.data.autoHide);
				}

				// Auto reload tabs
				const hash = window.location.hash;
				const shouldReload =
					!hash ||
					hash === '#global_setting_settings' ||
					hash === '#custom_css_settings' ||
					hash === '#custom_script_settings' ||
					(hash === '#custom_sorting_settings' && $data['order_reset'] !== undefined) ||
					(hash === '#base_slug_settings' && $data['base_slug_reset'] !== undefined);

				if (shouldReload) {
					setTimeout(() => {
						window.location.reload();
					}, 1500);
				}
			})
			.fail(function (xhr) {
				// Try to parse error response
				let errorMsg = 'An error occurred';
				try {
					const response = JSON.parse(xhr.responseText);
					if (response?.data?.message) {
						errorMsg = response.data.message;
					}
				} catch (e) {
					// Use default error message
				}
				showToast($, errorMsg, 'error', false);
			})
			.always(function () {
				btnSubmit.prop('disabled', false).html(buttonText);
			});
	});
}

/**
 * Show toast notification.
 *
 * @param {jQuery} $ jQuery reference.
 * @param {string} message - The message to display.
 * @param {string} type - Type of toast: 'success' or 'error'.
 * @param {boolean} autoHide - Whether to auto-hide the toast.
 */
function showToast($, message, type = 'success', autoHide = true) {
	// Ensure toast container exists
	let $container = $('#hda-toast-container');
	if (!$container.length) {
		$container = $('<div id="hda-toast-container"></div>').appendTo('body');
	}

	// Create toast element
	const iconSuccess =
		'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
	const iconError =
		'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
	const icon = type === 'success' ? iconSuccess : iconError;

	const safeType = type === 'error' ? 'error' : 'success';
	const $toast = $('<div>', { class: `hda-toast hda-toast--${safeType}` })
		.append($('<span>', { class: 'hda-toast__icon' }).html(icon))
		.append($('<span>', { class: 'hda-toast__message' }).text(message))
		.append(
			$('<button>', { type: 'button', class: 'hda-toast__close', 'aria-label': 'Close' }).html(
				'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
			),
		);

	// Append to container
	$container.append($toast);

	// Trigger animation
	requestAnimationFrame(() => {
		$toast.addClass('hda-toast--show');
	});

	// Close button handler
	$toast.find('.hda-toast__close').on('click', function () {
		removeToast($toast);
	});

	// Auto-hide
	if (autoHide) {
		setTimeout(() => {
			removeToast($toast);
		}, 4000);
	}
}

/**
 * Remove toast with animation.
 *
 * @param {jQuery} $toast - The toast element to remove.
 */
function removeToast($toast) {
	$toast.removeClass('hda-toast--show');
	setTimeout(() => {
		$toast.remove();
	}, 300);
}
