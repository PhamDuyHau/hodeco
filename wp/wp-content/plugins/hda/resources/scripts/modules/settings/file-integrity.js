/**
 * File Integrity — AJAX scan triggers with progress bar.
 *
 * Buttons with `data-scan` attribute trigger AJAX calls.
 * During scan: button disabled, progress bar animates.
 * On complete: page reloads to show server-rendered results.
 */
export function initFileIntegrity() {
	const buttons = document.querySelectorAll('.hda-fi-scan-btn');

	if (!buttons.length) return;

	const config = window.hdaFileIntegrity || {};
	const nonce = config.nonce || '';
	const i18n = config.i18n || {};

	buttons.forEach((btn) => {
		btn.addEventListener('click', () => {
			if (btn.disabled) return;

			const action = btn.dataset.scan;
			if (!action) return;

			// Disable button.
			btn.disabled = true;

			// Show progress bar.
			const progressEl = document.querySelector(`.hda-fi-progress[data-progress-for="${action}"]`);
			if (progressEl) progressEl.classList.add('is-active');

			// AJAX request.
			const fd = new FormData();
			fd.append('action', action);
			fd.append('_nonce', nonce);

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then((r) => r.json())
				.then((data) => {
					if (data.success) {
						window.location.reload();
					} else {
						btn.disabled = false;
						if (progressEl) progressEl.classList.remove('is-active');
						showToast(data.data?.message || i18n.error || 'Error', 'error');
					}
				})
				.catch(() => {
					btn.disabled = false;
					if (progressEl) progressEl.classList.remove('is-active');
					showToast(i18n.error || 'Scan failed. Please try again.', 'error');
				});
		});
	});

	/**
	 * Show a simple toast notification.
	 */
	function showToast(message, type = 'success') {
		const container = document.getElementById('hda-fi-toast');
		if (!container) return;

		const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
		const toast = document.createElement('div');
		toast.className = `notice ${noticeClass} is-dismissible`;
		const p = document.createElement('p');
		p.textContent = message;
		toast.appendChild(p);
		container.appendChild(toast);

		setTimeout(() => {
			toast.style.transition = 'opacity 0.3s ease';
			toast.style.opacity = '0';
			setTimeout(() => toast.remove(), 300);
		}, 6000);
	}
}
