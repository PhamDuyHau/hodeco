// dropdown/fx-dropdown.js
import './fx-dropdown.scss';

import { $, $$, on, off, closest, trigger } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const DATA_TOGGLE = 'data-fx-dropdown-toggle';
const DATA_DROPDOWN = 'data-fx-dropdown';
const OPEN = 'is-open';

const toggleHandlers = createWeakStore();
const dropdownMap = createWeakStore();
let docHandler = null;

// Helpers
function close(dropdown) {
	dropdown.classList.remove(OPEN);

	const btn = dropdownMap.get(dropdown);
	btn?.classList.remove('hover');
	btn?.setAttribute('aria-expanded', 'false');

	Events.emit('fx:dropdown:close', { el: dropdown });
	trigger(dropdown, 'fx.dropdown.closed', { el: dropdown });
}

function open(dropdown, btn) {
	closeAll(dropdown);

	dropdown.classList.add(OPEN);
	btn.classList.add('hover');
	btn.setAttribute('aria-expanded', 'true');

	if (dropdown.dataset.autoFocus === 'true') {
		dropdown.querySelector('input, textarea, select, [contenteditable]')?.focus();
	}

	Events.emit('fx:dropdown:open', { btn, el: dropdown });
	trigger(dropdown, 'fx.dropdown.opened', { btn, el: dropdown });
}

function closeAll(except = null) {
	$$(`[${DATA_DROPDOWN}]`).forEach((el) => {
		if (el !== except) close(el);
	});
}

const FxDropdown = {
	initAll(root = document) {
		// attach toggles
		$$('[' + DATA_TOGGLE + ']', root).forEach((btn) => {
			const sel = btn.getAttribute(DATA_TOGGLE);
			const dropdown = sel ? $(sel) : closest(btn, `[${DATA_DROPDOWN}]`);
			if (!dropdown) return;

			dropdownMap.set(dropdown, btn);

			// ARIA
			if (dropdown.id) btn.setAttribute('aria-controls', dropdown.id);
			btn.setAttribute('aria-expanded', 'false');

			const handler = (e) => {
				e.preventDefault();

				const isOpen = dropdown.classList.contains(OPEN);
				isOpen ? close(dropdown) : open(dropdown, btn);
			};

			toggleHandlers.set(btn, handler);
			on(btn, 'click', handler);
		});

		// document click (singleton)
		if (!docHandler) {
			docHandler = (e) => {
				const inside = e.target.closest(`[${DATA_DROPDOWN}], [${DATA_TOGGLE}]`);
				if (!inside) closeAll();
			};
			on(document, 'click', docHandler);
		}
	},

	destroyAll(root = document) {
		$$(`[${DATA_TOGGLE}]`, root).forEach((btn) => {
			const h = toggleHandlers.get(btn);
			if (h) {
				off(btn, 'click', h);
				toggleHandlers.delete(btn);
			}
		});

		// Only remove docHandler when destroying entire document
		if (docHandler && root === document) {
			off(document, 'click', docHandler);
			docHandler = null;
		}
	},
};

export default FxDropdown;
