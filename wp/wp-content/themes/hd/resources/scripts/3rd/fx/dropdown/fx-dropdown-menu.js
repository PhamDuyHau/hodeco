// dropdown/fx-dropdown-menu.js
import './fx-dropdown-menu.scss';

import { $$, on, off } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const ROOT = '[data-fx-dropdown-menu]';
const OPEN = 'is-active';
const DELAY = { open: 80, close: 120 };

const FxDropdownMenu = {
	_handlers: createWeakStore(),
	_observers: createWeakStore(),

	initAll(root = document) {
		$$(ROOT, root).forEach((menu) => {
			this.initMenu(menu);
			if (menu.dataset.autohide === 'true') this.initAutoHide(menu);
		});
	},

	initMenu(menu, useHover = menu.dataset?.hover === 'true') {
		menu.setAttribute('role', 'menubar');

		menu.querySelectorAll('li').forEach((li) => {
			const sub = li.querySelector(':scope > ul');
			const btn = li.querySelector(':scope > a, :scope > button');

			if (!sub || !btn) {
				li.setAttribute('role', 'none');
				btn?.setAttribute('role', 'menuitem');
				return;
			}

			li.classList.add('is-dropdown-submenu-parent', 'is-dropdown-submenu-item');
			sub.classList.add('is-dropdown-submenu');
			if (!li.closest('.is-dropdown-submenu')) sub.classList.add('first-sub');

			li.setAttribute('role', 'none');
			btn.setAttribute('role', 'menuitem');
			btn.setAttribute('aria-haspopup', 'true');
			btn.setAttribute('aria-expanded', 'false');
			sub.setAttribute('role', 'menu');

			if (useHover) {
				let openT, closeT;
				const enter = () => {
					clearTimeout(closeT);
					openT = setTimeout(() => this.open(li, btn, sub), DELAY.open);
				};
				const leave = () => {
					clearTimeout(openT);
					closeT = setTimeout(() => this.close(li, btn, sub), DELAY.close);
				};

				this._handlers.set(li, { type: 'hover', enter, leave });
				on(li, 'mouseenter', enter);
				on(li, 'mouseleave', leave);
			} else {
				const handler = (e) => {
					e.preventDefault();
					const isOpen = li.classList.toggle(OPEN);
					btn.setAttribute('aria-expanded', isOpen);
					sub.setAttribute('aria-hidden', !isOpen);
					isOpen ? requestAnimationFrame(() => this.applyAutoPosition(li, sub)) : li.classList.remove('opens-left', 'opens-right');
					Events.emit('fx:dropdownmenu:toggle', { li, sub, isOpen });
				};
				this._handlers.set(btn, { type: 'click', handler });
				on(btn, 'click', handler);
			}
		});
	},

	open(li, btn, sub) {
		li.classList.add(OPEN);
		btn.setAttribute('aria-expanded', 'true');
		sub.setAttribute('aria-hidden', 'false');
		requestAnimationFrame(() => {
			this.applyAutoPosition(li, sub);
			Events.emit('fx:dropdownmenu:open', { li, sub });
		});
	},

	close(li, btn, sub) {
		li.classList.remove(OPEN, 'opens-left', 'opens-right');
		btn.setAttribute('aria-expanded', 'false');
		sub.setAttribute('aria-hidden', 'true');
		Events.emit('fx:dropdownmenu:close', { li, sub });
	},

	applyAutoPosition(li, sub) {
		const r = sub.getBoundingClientRect().right;
		li.classList.toggle('opens-left', r > window.innerWidth);
		li.classList.toggle('opens-right', r <= window.innerWidth);
	},

	// AUTOHIDE - collapses overflow items into "More" dropdown
	// Optimized: Cache measurements, debounced resize, minimal DOM access
	initAutoHide(menu) {
		const container = menu.dataset.autohideContainer ? menu.closest(menu.dataset.autohideContainer) : menu.parentElement;
		if (!container) return;

		let more = null;
		let cachedWidths = null;
		let cachedMoreW = 0;
		let cachedGap = 0;
		let lastContainerWidth = container.clientWidth;

		// Invalidate cache when needed (font load, significant resize)
		const invalidateCache = () => {
			cachedWidths = null;
			cachedMoreW = 0;
		};

		// Measure all widths once - cached for performance
		const measureWidths = () => {
			const items = [...menu.children].filter((li) => li !== more);
			const style = getComputedStyle(menu);
			cachedGap = parseFloat(style.gap) || parseFloat(style.columnGap) || 0;

			// Measure More button only once
			if (more && cachedMoreW === 0) {
				more.style.cssText = 'visibility:hidden;position:absolute;pointer-events:none;';
				cachedMoreW = more.offsetWidth;
				more.style.cssText = '';
				more.classList.add('hidden!');
			}

			// Cache item widths - only measure once
			if (!cachedWidths) {
				cachedWidths = items.map((li) => li.offsetWidth);
			}

			return { items, widths: cachedWidths, moreW: cachedMoreW, gap: cachedGap };
		};

		// Recalculate visible items
		const recalculate = () => {
			if (!more) return;

			const dropdown = more.querySelector('.submenu');
			if (!dropdown) return;

			const { items, widths, moreW, gap } = measureWidths();
			const containerW = container.clientWidth;
			const total = widths.reduce((s, w) => s + w, 0) + gap * (items.length - 1);

			// All items fit
			if (total <= containerW) {
				items.forEach((li) => li.classList.remove('hidden!'));
				dropdown.innerHTML = '';
				more.classList.add('hidden!');
				menu.classList.add('is-adjusted');
				this.reinitMenu(menu);
				return;
			}

			// Find cut point
			const available = containerW - moreW - gap;
			let used = 0,
				cut = items.length;

			for (let i = 0; i < items.length; i++) {
				const w = widths[i] + (i > 0 ? gap : 0);
				if (used + w > available) {
					cut = i;
					break;
				}
				used += w;
			}

			// Batch writes in rAF
			requestAnimationFrame(() => {
				items.forEach((li) => li.classList.remove('hidden!'));
				dropdown.innerHTML = '';

				if (cut < items.length) {
					const frag = document.createDocumentFragment();
					for (let i = cut; i < items.length; i++) {
						items[i].classList.add('hidden!');
						const clone = items[i].cloneNode(true);
						clone.classList.remove('hidden!');
						frag.appendChild(clone);
					}
					dropdown.appendChild(frag);
					more.classList.remove('hidden!');
				} else {
					more.classList.add('hidden!');
				}

				menu.classList.add('is-adjusted');
				this.reinitMenu(menu);
			});
		};

		// Create More button and initialize
		const createMoreAndInit = () => {
			more = document.createElement('li');
			more.className = 'fx-more has-dropdown hidden!';
			more.innerHTML =
				'<a href="#" role="menuitem" aria-haspopup="true" aria-expanded="false">More</a><ul class="submenu vertical menu is-dropdown-submenu"></ul>';
			menu.appendChild(more);
			recalculate();
		};

		// Initial check
		const init = () => {
			if (menu.scrollWidth <= container.clientWidth) {
				menu.classList.add('is-adjusted');
				return;
			}
			createMoreAndInit();
		};

		// Run after fonts load - invalidate cache as font metrics changed
		(document.fonts?.ready || Promise.resolve()).then(() => {
			invalidateCache();
			requestAnimationFrame(init);
		});

		// ResizeObserver with debounce
		if (window.ResizeObserver) {
			let firstCall = true;
			const ro = new ResizeObserver(() => {
				if (firstCall) {
					firstCall = false;
					return;
				}
				clearTimeout(ro._t);
				ro._t = setTimeout(() => {
					// Invalidate cache on significant width change
					const currentWidth = container.clientWidth;
					if (Math.abs(currentWidth - lastContainerWidth) > 50) {
						invalidateCache();
						lastContainerWidth = currentWidth;
					}

					// FIX: If no More button yet but now overflows, create it
					if (!more && menu.scrollWidth > container.clientWidth) {
						createMoreAndInit();
					} else if (more) {
						recalculate();
					}
				}, 150);
			});
			ro.observe(container);
			this._observers.set(menu, ro);
		}
	},

	reinitMenu(menu) {
		this.destroyAll(menu);
		this.initMenu(menu);
	},

	destroyAll(root = document) {
		$$(ROOT, root).forEach((menu) => {
			this._observers.get(menu)?.disconnect();
			this._observers.delete(menu);

			menu.querySelectorAll('li').forEach((li) => {
				const h = this._handlers.get(li);
				if (h?.type === 'hover') {
					off(li, 'mouseenter', h.enter);
					off(li, 'mouseleave', h.leave);
				}
				this._handlers.delete(li);

				const btn = li.querySelector(':scope > a, :scope > button');
				const c = this._handlers.get(btn);
				if (c?.type === 'click') off(btn, 'click', c.handler);
				this._handlers.delete(btn);
			});
		});
	},
};

export default FxDropdownMenu;
