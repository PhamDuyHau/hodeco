// utils/global.js (IIFE)

(() => {
	if (window.__globalInit) return;
	window.__globalInit = true;

	const currentDomain = window.location.hostname;
	const invalidHref = /^(#|mailto:|tel:|javascript:|data:|blob:)/i;
	const selector = 'a._blank, a.blank, a[target="_blank"]';
	const linkSet = new Set();

	/**
	 * Check external link
	 *
	 * @param el
	 */
	function checkExternal(el) {
		const href = el.getAttribute('href')?.trim();
		if (!href || invalidHref.test(href)) return;

		try {
			const url = new URL(href, window.location.href);
			if (url.hostname && url.hostname !== currentDomain) {
				linkSet.add(el);
			}
		} catch {}
	}

	/**
	 * Apply target and rel to link
	 *
	 * @param el
	 */
	function applyTargetRel(el) {
		if (el.target !== '_blank') el.target = '_blank';
		const relParts = (el.rel || '').split(/\s+/).filter(Boolean);
		['noopener', 'noreferrer', 'nofollow'].forEach((r) => {
			if (!relParts.includes(r)) relParts.push(r);
		});
		el.rel = relParts.join(' ');
	}

	/**
	 * Process links
	 */
	function processLinks() {
		for (const el of linkSet) applyTargetRel(el);
	}

	/**
	 * Optimize observer callback with debounce
	 */
	let observerTimeout;

	function handleMutations() {
		clearTimeout(observerTimeout);
		observerTimeout = setTimeout(() => {
			// Re-check submenu roles
			document.querySelectorAll('ul.submenu[role="menubar"]').forEach((menu) => {
				menu.setAttribute('role', 'menu');
			});

			// Disable focus for hidden elements
			document.querySelectorAll('[aria-hidden="true"] a, [aria-hidden="true"] button').forEach((el) => {
				el.setAttribute('tabindex', '-1');
			});
		}, 200);
	}

	/**
	 * Run
	 *
	 * @returns {Promise<void>}
	 */
	const run = async () => {
		//
		// target _blank links
		//
		document.querySelectorAll(selector).forEach((el) => linkSet.add(el));
		document.querySelectorAll('a[href]').forEach(checkExternal);

		processLinks();

		//
		// MutationObserver (debounced)
		//
		const observer = new MutationObserver(handleMutations);
		observer.observe(document.body, { childList: true, subtree: true });

		//
		// Toggle menu footer
		//
		document.querySelectorAll('#footer-columns .toggle-title').forEach((link) => {
			link.addEventListener('click', function (event) {
				event.preventDefault();
				this.classList.toggle('active');
			});
		});

		//
		// Table scroll
		//
		document.querySelectorAll('.entry-content table').forEach(function (tbl) {
			if (tbl.parentElement && tbl.parentElement.classList.contains('table-scroll')) return;

			const wrap = document.createElement('div');
			wrap.className = 'table-scroll';
			tbl.parentNode.insertBefore(wrap, tbl);
			wrap.appendChild(tbl);
		});
	};

	document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
})();
