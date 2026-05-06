/**
 * Redirect Manager — Inline edit, bulk actions, search, import/export.
 */
export function initRedirectRepeater() {
	const tbody = document.getElementById('hda-redirect-rules');
	const tableWrap = document.getElementById('hda-redirect-table-wrap');
	const addBtn = document.getElementById('hda-redirect-add');
	const emptyMsg = document.getElementById('hda-redirect-empty');
	const selectAllCb = document.getElementById('hda-redirect-select-all');
	const deleteSelectedBtn = document.getElementById('hda-redirect-delete-selected');
	const deleteAllBtn = document.getElementById('hda-redirect-delete-all');

	const searchInput = document.getElementById('hda-redirect-search');

	const importBtn = document.getElementById('hda-redirect-import-btn');
	const importFile = document.getElementById('hda-redirect-import-file');
	const importMode = document.getElementById('hda-redirect-import-mode');
	const importStatus = document.getElementById('hda-redirect-import-status');

	const config = window.hdaRedirect || {};
	const nonce = config.nonce || '';
	const i18n = config.i18n || {};

	if (!tbody) return;

	// ══════════════════════════════════════════════════
	// DUPLICATE CHECK (on blur of "from" input)
	// ══════════════════════════════════════════════════

	tbody.addEventListener(
		'blur',
		(e) => {
			const input = e.target;
			if (input.name !== 'redirect_from[]') return;

			const val = (input.value || '').trim();
			clearDupeWarning(input);

			if (!val) return;

			// Also check against other rows on the same page (client side).
			const row = input.closest('.hda-redirect-row');
			const allFromInputs = tbody.querySelectorAll('[name="redirect_from[]"]');
			let clientDupe = false;
			const normalized = val.toLowerCase().replace(/\/+$/, '');

			allFromInputs.forEach((other) => {
				if (other === input) return;
				const otherVal = (other.value || '').trim().toLowerCase().replace(/\/+$/, '');
				if (otherVal === normalized) clientDupe = true;
			});

			if (clientDupe) {
				showDupeWarning(input, 'Duplicate — this path already exists on this page.');
				return;
			}

			// Server-side check against DB (other pages).
			const fd = new FormData();
			fd.append('action', 'hda_redirect_check_dupe');
			fd.append('_nonce', nonce);
			fd.append('from', val);

			// Skip check for existing rows being edited (they'd match themselves).
			if (row && row.dataset.origFrom) {
				const origNorm = row.dataset.origFrom.toLowerCase().replace(/\/+$/, '');
				if (origNorm === normalized) return; // Same as original — no change.
			}

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then((r) => r.json())
				.then((data) => {
					if (data.success && data.data.exists) {
						showDupeWarning(input, `Duplicate — already redirects to: ${data.data.existing_to}`);
					}
				})
				.catch(() => {});
		},
		true,
	); // useCapture for blur delegation.

	function showDupeWarning(input, message) {
		clearDupeWarning(input);
		const warn = document.createElement('small');
		warn.className = 'hda-redirect-dupe-warn';
		warn.textContent = message;
		input.parentNode.appendChild(warn);
		input.classList.add('hda-redirect-input--dupe');
	}

	function clearDupeWarning(input) {
		const existing = input.parentNode?.querySelector('.hda-redirect-dupe-warn');
		if (existing) existing.remove();
		input.classList.remove('hda-redirect-input--dupe');
	}

	// ══════════════════════════════════════════════════
	// INLINE EDIT
	// ══════════════════════════════════════════════════

	tbody.addEventListener('click', (e) => {
		const editBtn = e.target.closest('.hda-redirect-edit');
		if (!editBtn) return;

		const row = editBtn.closest('.hda-redirect-row');
		if (!row) return;

		const isEditing = row.classList.contains('hda-redirect-row--editing');

		if (isEditing) {
			// Cancel editing — revert to original values.
			cancelEdit(row);
		} else {
			// Enter edit mode.
			enterEdit(row);
		}
	});

	function enterEdit(row) {
		row.classList.add('hda-redirect-row--editing');

		// Make inputs editable.
		row.querySelectorAll('.hda-redirect-input').forEach((input) => {
			input.removeAttribute('readonly');
		});

		// Enable select and sync hidden input on change.
		const sel = row.querySelector('.hda-redirect-select');
		if (sel) {
			sel.disabled = false;
			sel.onchange = () => {
				const hidden = row.querySelector('.hda-redirect-type-hidden');
				if (hidden) hidden.value = sel.value;
			};
		}

		// Store original values for cancel.
		row.dataset.origFrom = row.querySelector('[name="redirect_from[]"]')?.value || '';
		row.dataset.origTo = row.querySelector('[name="redirect_to[]"]')?.value || '';
		row.dataset.origType = row.querySelector('.hda-redirect-type-hidden')?.value || '301';

		// Change edit icon to cancel.
		const editBtn = row.querySelector('.hda-redirect-edit');
		if (editBtn) {
			editBtn.title = 'Cancel';
			const icon = editBtn.querySelector('.dashicons');
			if (icon) {
				icon.classList.remove('dashicons-edit');
				icon.classList.add('dashicons-no-alt');
			}
		}
	}

	function cancelEdit(row) {
		row.classList.remove('hda-redirect-row--editing');

		// Revert values.
		const fromInput = row.querySelector('[name="redirect_from[]"]');
		const toInput = row.querySelector('[name="redirect_to[]"]');
		const typeSelect = row.querySelector('[name="redirect_type[]"]');

		if (fromInput) {
			fromInput.value = row.dataset.origFrom || '';
			fromInput.setAttribute('readonly', '');
		}
		if (toInput) {
			toInput.value = row.dataset.origTo || '';
			toInput.setAttribute('readonly', '');
		}
		if (typeSelect) {
			typeSelect.value = row.dataset.origType || '301';
			typeSelect.disabled = true;
			typeSelect.onchange = null;
		}

		// Revert hidden type input.
		const hiddenType = row.querySelector('.hda-redirect-type-hidden');
		if (hiddenType) hiddenType.value = row.dataset.origType || '301';

		// Update display spans.
		updateDisplaySpans(row);

		// Restore edit icon.
		const editBtn = row.querySelector('.hda-redirect-edit');
		if (editBtn) {
			editBtn.title = 'Edit';
			const icon = editBtn.querySelector('.dashicons');
			if (icon) {
				icon.classList.remove('dashicons-no-alt');
				icon.classList.add('dashicons-edit');
			}
		}
	}

	function updateDisplaySpans(row) {
		const spans = row.querySelectorAll('.hda-redirect-display');
		const fromInput = row.querySelector('[name="redirect_from[]"]');
		const toInput = row.querySelector('[name="redirect_to[]"]');
		const hiddenType = row.querySelector('.hda-redirect-type-hidden');

		if (spans[0] && fromInput) spans[0].textContent = fromInput.value;
		if (spans[1] && toInput) spans[1].textContent = toInput.value;
		if (spans[2] && hiddenType) spans[2].textContent = hiddenType.value;
	}

	// ══════════════════════════════════════════════════
	// ADD ROW (always in edit mode)
	// ══════════════════════════════════════════════════

	if (addBtn) {
		addBtn.addEventListener('click', () => {
			if (emptyMsg) emptyMsg.remove();
			if (tableWrap) tableWrap.style.display = '';

			const rowNum = tbody.querySelectorAll('.hda-redirect-row').length + 1;

			const tr = document.createElement('tr');
			tr.className = 'hda-redirect-row hda-redirect-row--new hda-redirect-row--editing';
			tr.innerHTML = `
				<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-redirect-cb"></td>
				<td class="hda-redirect-table__num">${rowNum}</td>
				<td>
					<span class="hda-redirect-display"></span>
					<input type="text" class="input hda-redirect-input" name="redirect_from[]" placeholder="/old-page">
				</td>
				<td>
					<span class="hda-redirect-display"></span>
					<input type="url" class="input hda-redirect-input" name="redirect_to[]" placeholder="https://example.com/new-page">
				</td>
				<td>
					<span class="hda-redirect-display">301</span>
					<input type="hidden" name="redirect_type[]" value="301" class="hda-redirect-type-hidden">
					<select class="select hda-redirect-select" data-name="redirect_type">
						<option value="301">301</option>
						<option value="302">302</option>
					</select>
				</td>
				<td class="hda-redirect-table__actions-cell">
					<button type="button" class="button button-small hda-redirect-edit" title="Cancel">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
					<button type="button" class="button button-small hda-redirect-remove" title="Delete">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</td>
			`;

			// Sync hidden input on select change for new row.
			const newSelect = tr.querySelector('.hda-redirect-select');
			const newHidden = tr.querySelector('.hda-redirect-type-hidden');
			if (newSelect && newHidden) {
				newSelect.onchange = () => {
					newHidden.value = newSelect.value;
				};
			}

			tbody.appendChild(tr);
			tr.querySelector('input[name="redirect_from[]"]')?.focus();
		});
	}

	// ══════════════════════════════════════════════════
	// DELETE ROW (AJAX — immediate)
	// ══════════════════════════════════════════════════

	tbody.addEventListener('click', (e) => {
		const removeBtn = e.target.closest('.hda-redirect-remove');
		if (!removeBtn) return;

		const row = removeBtn.closest('.hda-redirect-row');
		if (!row) return;

		const idx = row.dataset.index;

		// New rows (not yet saved) — just remove from DOM.
		if (idx === undefined || idx === '') {
			fadeOutRow(row);
			return;
		}

		if (!confirm('Delete this redirect rule?')) return;

		removeBtn.disabled = true;
		ajaxDeleteByIndices([parseInt(idx, 10)], [row]);
	});

	function fadeOutRow(row) {
		row.style.transition = 'opacity 0.25s ease';
		row.style.opacity = '0';

		setTimeout(() => {
			row.remove();
			renumberRows();
			updateBulkUI();
		}, 250);
	}

	/**
	 * AJAX delete by indices, then reload or remove rows.
	 */
	function ajaxDeleteByIndices(indices, rows) {
		const fd = new FormData();
		fd.append('action', 'hda_redirect_delete');
		fd.append('_nonce', nonce);
		indices.forEach((idx) => fd.append('indices[]', idx));

		fetch(ajaxurl, { method: 'POST', body: fd })
			.then((r) => r.json())
			.then((data) => {
				if (data.success) {
					rows.forEach((row) => fadeOutRow(row));
					// Reload to refresh pagination & indices.
					setTimeout(() => window.location.reload(), 800);
				} else {
					alert(data.data?.message || 'Error');
				}
			})
			.catch(() => alert('Request failed.'));
	}

	// ══════════════════════════════════════════════════
	// SELECT ALL / BULK ACTIONS
	// ══════════════════════════════════════════════════

	if (selectAllCb) {
		selectAllCb.addEventListener('change', () => {
			const checked = selectAllCb.checked;
			tbody.querySelectorAll('.hda-redirect-cb').forEach((cb) => {
				const row = cb.closest('.hda-redirect-row');
				if (row && row.style.display !== 'none') {
					cb.checked = checked;
				}
			});
			updateBulkUI();
		});
	}

	tbody.addEventListener('change', (e) => {
		if (e.target.classList.contains('hda-redirect-cb')) {
			updateBulkUI();
		}
	});

	function getCheckedRows() {
		return [...tbody.querySelectorAll('.hda-redirect-cb:checked')].map((cb) => cb.closest('.hda-redirect-row'));
	}

	function updateBulkUI() {
		const checked = getCheckedRows();
		if (deleteSelectedBtn) {
			deleteSelectedBtn.style.display = checked.length > 0 ? '' : 'none';
		}
	}

	// Delete Selected (AJAX)
	if (deleteSelectedBtn) {
		deleteSelectedBtn.addEventListener('click', () => {
			const rows = getCheckedRows();
			if (!rows.length) return;

			if (!confirm(`Delete ${rows.length} selected rule(s)?`)) return;

			const indices = rows
				.map((row) => row.dataset.index)
				.filter((idx) => idx !== undefined && idx !== '')
				.map((idx) => parseInt(idx, 10));

			if (indices.length > 0) {
				ajaxDeleteByIndices(indices, rows);
			} else {
				// All are new (unsaved) rows — just remove from DOM.
				rows.forEach((row) => row.remove());
				renumberRows();
				updateBulkUI();
			}

			if (selectAllCb) selectAllCb.checked = false;
		});
	}

	// Delete All (AJAX)
	if (deleteAllBtn) {
		deleteAllBtn.addEventListener('click', () => {
			const rows = tbody.querySelectorAll('.hda-redirect-row');
			if (!rows.length) return;

			if (!confirm('Delete ALL redirect rules? This cannot be undone.')) return;

			const fd = new FormData();
			fd.append('action', 'hda_redirect_delete_all');
			fd.append('_nonce', nonce);

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then((r) => r.json())
				.then((data) => {
					if (data.success) {
						rows.forEach((row) => fadeOutRow(row));
						setTimeout(() => window.location.reload(), 800);
					} else {
						alert(data.data?.message || 'Error');
					}
				})
				.catch(() => alert('Request failed.'));
		});
	}

	// ══════════════════════════════════════════════════
	// SEARCH
	// ══════════════════════════════════════════════════

	if (searchInput) {
		searchInput.addEventListener('input', () => {
			const query = searchInput.value.toLowerCase().trim();
			const rows = tbody.querySelectorAll('.hda-redirect-row');

			rows.forEach((row) => {
				if (!query) {
					row.style.display = '';
					return;
				}

				const from = (row.querySelector('[name="redirect_from[]"]')?.value || '').toLowerCase();
				const to = (row.querySelector('[name="redirect_to[]"]')?.value || '').toLowerCase();
				const type = (row.querySelector('[name="redirect_type[]"]')?.value || '').toLowerCase();

				const match = from.includes(query) || to.includes(query) || type.includes(query);
				row.style.display = match ? '' : 'none';
			});
		});
	}

	// ══════════════════════════════════════════════════
	// HELPERS
	// ══════════════════════════════════════════════════

	function renumberRows() {
		const rows = tbody.querySelectorAll('.hda-redirect-row');
		rows.forEach((row, i) => {
			const numCell = row.querySelector('.hda-redirect-table__num');
			if (numCell) numCell.textContent = i + 1;
		});
	}

	// ══════════════════════════════════════════════════
	// IMPORT
	// ══════════════════════════════════════════════════

	if (importBtn && importFile) {
		importBtn.addEventListener('click', () => {
			importFile.click();
		});

		importFile.addEventListener('change', () => {
			const file = importFile.files?.[0];
			if (!file) return;

			const mode = importMode?.value || 'append';

			if (mode === 'replace' && !confirm(i18n.confirm_replace || 'Replace all existing rules?')) {
				importFile.value = '';
				return;
			}

			const fd = new FormData();
			fd.append('action', 'hda_redirect_import');
			fd.append('_nonce', nonce);
			fd.append('import_file', file);
			fd.append('import_mode', mode);

			showImportStatus(i18n.importing || 'Importing...', '#0073aa');
			importBtn.disabled = true;

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then((r) => r.json())
				.then((data) => {
					if (data.success) {
						let msg = `✅ ${data.data.message}`;
						if (data.data.errors?.length) {
							msg += `<br><small style="color:#d63638;">${data.data.errors.join('<br>')}</small>`;
						}
						showImportStatus(msg, '#46b450');
						setTimeout(() => window.location.reload(), 1500);
					} else {
						showImportStatus(`❌ ${data.data?.message || 'Error'}`, '#d63638');
						importBtn.disabled = false;
					}
				})
				.catch(() => {
					showImportStatus('❌ Request failed', '#d63638');
					importBtn.disabled = false;
				});

			importFile.value = '';
		});
	}

	function showImportStatus(html, color) {
		if (!importStatus) return;
		importStatus.innerHTML = html;
		importStatus.style.color = color || '#666';
		importStatus.style.display = '';
	}
}
