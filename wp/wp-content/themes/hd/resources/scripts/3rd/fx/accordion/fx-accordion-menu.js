// accordion/fx-accordion-menu.js

import { $$, off, on, uid } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-accordion-menu]';
const PARENT_CLASS = 'is-accordion-submenu-parent';
const SUBMENU_CLASS = 'is-accordion-submenu';

const toggleHandlers = createWeakStore();
const linkHandlers = createWeakStore();

const FxAccordionMenu = {
	initAll(root = document) {
		$$(SELECTOR, root).forEach((menu) => {
			const allowMulti = menu.dataset.multiSelectable === 'true';
			const autoToggle = menu.dataset.submenuToggle === 'true';

			menu.setAttribute('role', 'menubar');

			menu.querySelectorAll('li:has(> ul)').forEach((li) => {
				const link = li.querySelector(':scope > a, :scope > button');
				const sub = li.querySelector(':scope > ul');

				if (!link || !sub) return;

				li.setAttribute('role', 'none');
				link.setAttribute('role', 'menuitem');

				// ---- Toggle button ----
				let toggleBtn = li.querySelector(':scope > .submenu-toggle');
				if (!toggleBtn) {
					toggleBtn = document.createElement('button');
					toggleBtn.className = 'submenu-toggle';
					toggleBtn.innerHTML = '<span class="submenu-toggle-text">Toggle menu</span>';

					toggleBtn.id = uid('acc-menu-link');
					link.after(toggleBtn);
				}

				// ---- Accessibility ----
				const submenuId = uid('acc-menu');

				sub.id = submenuId;
				sub.classList.add(SUBMENU_CLASS);
				sub.setAttribute('role', 'group');
				sub.setAttribute('aria-labelledby', toggleBtn.id);

				const isOpen = li.classList.contains('active') || li.classList.contains('is-active');

				toggleBtn.setAttribute('aria-controls', submenuId);
				toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				sub.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

				if (!isOpen) sub.classList.add('hidden');
				li.classList.add(PARENT_CLASS);

				// ---- Handler ----
				const toggleHandler = (e) => {
					e.preventDefault();
					e.stopPropagation();

					const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';

					if (!allowMulti) {
						menu.querySelectorAll(`.${PARENT_CLASS}`).forEach((other) => {
							if (other !== li) FxAccordionMenu._close(other);
						});
					}

					expanded ? FxAccordionMenu._close(li) : FxAccordionMenu._open(li);
					Events.emit('fx:accordionmenu:toggle', { li, submenu: sub });
				};

				toggleHandlers.set(toggleBtn, toggleHandler);
				on(toggleBtn, 'click', toggleHandler);

				// auto toggle via link
				if (autoToggle && link.tagName.toLowerCase() === 'a') {
					const linkHandler = (e) => {
						if (link.getAttribute('href') === '#') {
							e.preventDefault();
							toggleHandler(e);
						}
					};

					linkHandlers.set(link, linkHandler);
					on(link, 'click', linkHandler);
				}
			});
		});
	},

	destroyAll(root = document) {
		$$(SELECTOR, root).forEach((menu) => {
			menu.querySelectorAll('.submenu-toggle').forEach((btn) => {
				const h = toggleHandlers.get(btn);
				if (h) {
					off(btn, 'click', h);
					toggleHandlers.delete(btn);
				}
			});

			menu.querySelectorAll('a, button').forEach((el) => {
				const h = linkHandlers.get(el);
				if (h) {
					off(el, 'click', h);
					linkHandlers.delete(el);
				}
			});
		});
	},

	// ---------- Helper ----------
	_open(li) {
		const btn = li.querySelector('.submenu-toggle');
		const sub = li.querySelector(':scope > ul');
		if (!btn || !sub) return;

		sub.classList.remove('hidden');
		btn.setAttribute('aria-expanded', 'true');
		sub.setAttribute('aria-hidden', 'false');
		li.classList.add('active', 'is-active');
	},

	_close(li) {
		const btn = li.querySelector('.submenu-toggle');
		const sub = li.querySelector(':scope > ul');
		if (!btn || !sub) return;

		sub.classList.add('hidden');
		btn.setAttribute('aria-expanded', 'false');
		sub.setAttribute('aria-hidden', 'true');
		li.classList.remove('active', 'is-active');
	},
};

export default FxAccordionMenu;
