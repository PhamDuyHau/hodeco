/**
 * WAF — add/remove country tag interactions + mode switching.
 */
export function initWaf() {
	const select = document.getElementById('hda-country-select');
	const addBtn = document.getElementById('hda-add-country-btn');
	const list = document.getElementById('hda-blocked-list');

	if (!select || !addBtn || !list) return;

	// ── Mode-dependent text map ──────────────────────
	const TEXT = {
		block_selected: {
			heading: 'Blocked Countries',
			placeholder: 'Select a country to block...',
			btn: 'Add to Blocklist',
			empty: 'No countries blocked.',
		},
		allow_selected: {
			heading: 'Allowed Countries',
			placeholder: 'Select a country to allow...',
			btn: 'Add to Allowlist',
			empty: 'No countries in allowlist. All traffic will be blocked!',
		},
	};

	/** Get current country_mode from radio buttons. */
	function getMode() {
		const checked = document.querySelector('input[name="country_mode"]:checked');
		return checked ? checked.value : 'block_selected';
	}

	/** Update all mode-dependent text elements. */
	function updateModeText() {
		const mode = getMode();
		const t = TEXT[mode] || TEXT.block_selected;

		const heading = document.getElementById('hda-country-heading');
		const placeholder = document.getElementById('hda-country-placeholder');
		const btnText = document.getElementById('hda-country-btn-text');
		const emptyMsg = list.querySelector('.empty-msg');

		if (heading) heading.textContent = t.heading;
		if (placeholder) placeholder.textContent = t.placeholder;
		if (btnText) btnText.textContent = t.btn;
		if (emptyMsg) emptyMsg.textContent = t.empty;
	}

	// ── Listen for mode radio changes ────────────────
	document.querySelectorAll('input[name="country_mode"]').forEach((radio) => {
		radio.addEventListener('change', updateModeText);
	});

	/**
	 * Create a blocked-country list item.
	 *
	 * @param {string} code  ISO country code (e.g. "CN").
	 * @param {string} name  Country display name.
	 * @returns {HTMLLIElement}
	 */
	function createItem(code, name) {
		const li = document.createElement('li');
		li.className = 'blocked-item';

		li.innerHTML = `
			<img src="https://flagcdn.com/16x12/${code.toLowerCase()}.png" width="16" height="12" alt="" />
			<span>${name}</span>
			<input type="hidden" name="blocked_countries[]" value="${code}">
			<button type="button" class="remove-country" aria-label="Remove">&times;</button>
		`;

		return li;
	}

	// Add country
	addBtn.addEventListener('click', () => {
		const code = select.value;
		if (!code) return;

		const selectedOption = select.options[select.selectedIndex];
		const name = selectedOption.textContent.replace(/\s*\([A-Z]{2}\)\s*$/, '');

		// Remove empty message if present
		const emptyMsg = list.querySelector('.empty-msg');
		if (emptyMsg) emptyMsg.remove();

		// Prevent duplicates
		if (list.querySelector(`input[value="${code}"]`)) return;

		// Add item to list
		list.appendChild(createItem(code, name));

		// Disable the option in select
		selectedOption.disabled = true;
		select.value = '';
	});

	// Remove country (event delegation)
	list.addEventListener('click', (e) => {
		const btn = e.target.closest('.remove-country');
		if (!btn) return;

		const li = btn.closest('.blocked-item');
		const code = li.querySelector('input').value;

		li.remove();

		// Re-enable the option in select
		const option = select.querySelector(`option[value="${code}"]`);
		if (option) option.disabled = false;

		// Show empty message if list is now empty
		if (!list.querySelector('.blocked-item')) {
			const mode = getMode();
			const t = TEXT[mode] || TEXT.block_selected;

			const emptyLi = document.createElement('li');
			emptyLi.className = 'empty-msg';
			emptyLi.textContent = t.empty;
			list.appendChild(emptyLi);
		}
	});
}
