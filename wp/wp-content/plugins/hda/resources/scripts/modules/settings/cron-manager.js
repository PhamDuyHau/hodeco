/**
 * Cron Manager — Run / Delete events via AJAX.
 */
export function initCronManager() {
	const statusEl = document.getElementById('hda-cron-status');
	const table = document.getElementById('hda-cron-table');

	if (!table) return;

	const nonce = window.hdaCronManager?.nonce || '';

	function showStatus(msg, color) {
		if (statusEl) {
			statusEl.textContent = msg;
			statusEl.style.color = color || '#666';
		}
	}

	function fadeOutRow(row) {
		row.style.transition = 'opacity 0.3s ease';
		row.style.opacity = '0';

		setTimeout(() => {
			// Collapse all cells to zero height.
			const cells = row.querySelectorAll('td');
			cells.forEach((td) => {
				td.style.transition = 'padding 0.25s ease, height 0.25s ease';
				td.style.padding = '0';
				td.style.height = '0';
				td.style.overflow = 'hidden';
				td.style.lineHeight = '0';
				td.style.fontSize = '0';
				td.style.border = 'none';
			});

			// Remove from DOM after collapse.
			setTimeout(() => row.remove(), 300);
		}, 300);
	}

	function setButtonLoading(btn, loading) {
		btn.disabled = loading;
		const icon = btn.querySelector('.dashicons');
		if (!icon) return;

		if (loading) {
			btn._origIcon = icon.className;
			icon.className = 'dashicons dashicons-update hda-spin';
			btn.style.opacity = '0.7';
		} else {
			if (btn._origIcon) icon.className = btn._origIcon;
			btn.style.opacity = '';
		}
	}

	/**
	 * Show brief inline feedback on a row (flash green/red).
	 */
	function flashRow(row, message, isSuccess) {
		const color = isSuccess ? '#46b450' : '#d63638';
		const bg = isSuccess ? '#ecf7ed' : '#fcf0f1';

		row.style.transition = 'background-color 0.3s ease';
		row.style.backgroundColor = bg;

		// Also update the status bar.
		showStatus(`${isSuccess ? '✅' : '❌'} ${message}`, color);

		// Clear row highlight after a moment.
		if (isSuccess) {
			setTimeout(() => {
				row.style.backgroundColor = '';
			}, 2000);
		}
	}

	// Run event.
	table.addEventListener('click', (e) => {
		const btn = e.target.closest('.hda-cron-run');
		if (!btn || btn.disabled) return;

		const row = btn.closest('.hda-cron-row');
		const hook = row.dataset.hook;
		const ts = row.dataset.timestamp;
		const sig = row.dataset.sig || '';

		if (!confirm(`Run "${hook}" now?`)) return;

		setButtonLoading(btn, true);

		const fd = new FormData();
		fd.append('action', 'hda_cron_run');
		fd.append('_nonce', nonce);
		fd.append('hook', hook);
		fd.append('timestamp', ts);
		fd.append('sig', sig);

		showStatus('Running...', '#0073aa');

		fetch(ajaxurl, { method: 'POST', body: fd })
			.then((r) => r.json())
			.then((data) => {
				if (data.success) {
					flashRow(row, data.data.message, true);

					// One-time events are removed after run.
					if (data.data.removed) {
						setTimeout(() => fadeOutRow(row), 1500);
					} else {
						setButtonLoading(btn, false);
					}
				} else {
					flashRow(row, data.data?.message || 'Error', false);
					setButtonLoading(btn, false);
				}
			})
			.catch(() => {
				flashRow(row, 'Request failed', false);
				setButtonLoading(btn, false);
			});
	});

	// Delete event.
	table.addEventListener('click', (e) => {
		const btn = e.target.closest('.hda-cron-delete');
		if (!btn || btn.disabled) return;

		const row = btn.closest('.hda-cron-row');
		const hook = row.dataset.hook;
		const ts = row.dataset.timestamp;
		const sig = row.dataset.sig || '';

		if (!confirm(`Delete "${hook}" event?`)) return;

		setButtonLoading(btn, true);

		const fd = new FormData();
		fd.append('action', 'hda_cron_delete');
		fd.append('_nonce', nonce);
		fd.append('hook', hook);
		fd.append('timestamp', ts);
		fd.append('sig', sig);

		showStatus('Deleting...', '#d63638');

		fetch(ajaxurl, { method: 'POST', body: fd })
			.then((r) => r.json())
			.then((data) => {
				if (data.success) {
					flashRow(row, data.data.message, true);
					setTimeout(() => fadeOutRow(row), 1000);
				} else {
					flashRow(row, data.data?.message || 'Error', false);
					setButtonLoading(btn, false);
				}
			})
			.catch(() => {
				flashRow(row, 'Request failed', false);
				setButtonLoading(btn, false);
			});
	});
}
