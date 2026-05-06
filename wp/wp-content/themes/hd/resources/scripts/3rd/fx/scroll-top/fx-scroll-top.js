// offcanvas/fx-scroll-top.js

import { $$ } from '../../dom.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-scroll-top]';
const instanceStore = createWeakStore();

/**
 * Easing function for smooth scroll (easeInOutQuad)
 */
const ease = (t, b, c, d) => {
	t /= d / 2;
	return t < 1 ? (c / 2) * t * t + b : (-c / 2) * (--t * (t - 2) - 1) + b;
};

/**
 * Smooth scroll to top with custom easing
 * @param {number} duration - Animation duration in ms
 */
const scrollToTop = (duration) => {
	const start = window.scrollY;
	let startTime = null;

	const animate = (time) => {
		if (!startTime) startTime = time;
		const elapsed = time - startTime;
		window.scrollTo(0, ease(elapsed, start, -start, duration));

		if (elapsed < duration) requestAnimationFrame(animate);
	};

	requestAnimationFrame(animate);
};

/**
 * Create scroll-top instance
 * @param {HTMLElement} btn - Button element
 * @returns {Object}
 */
const createInstance = (btn) => {
	// Parse options
	const threshold = parseInt(btn.dataset.scrollStart, 10) || 300;
	const speed = parseInt(btn.dataset.scrollSpeed, 10) || 400;
	const showClass = btn.dataset.showClass || 'back-to-top__show';

	let ticking = false;

	// Scroll handler
	const onScroll = () => {
		if (ticking) return;
		ticking = true;

		requestAnimationFrame(() => {
			const show = window.scrollY > threshold;
			btn.classList.toggle(showClass, show);
			btn.dataset.show = show ? 'true' : 'false';
			ticking = false;
		});
	};

	// Click handler
	const onClick = (e) => {
		e.preventDefault();
		scrollToTop(speed);
	};

	// Bind
	window.addEventListener('scroll', onScroll, { passive: true });
	btn.addEventListener('click', onClick);
	onScroll(); // Check initial state

	return {
		destroy() {
			window.removeEventListener('scroll', onScroll);
			btn.removeEventListener('click', onClick);
			btn.classList.remove(showClass);
			btn.dataset.show = 'false';
		},
	};
};

const FxScrollTop = {
	/**
	 * Initialize all scroll-top buttons in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		$$(SELECTOR, root).forEach(
			/** @param {HTMLElement} btn */
			(btn) => {
				if (instanceStore.has(btn)) return;
				instanceStore.set(btn, createInstance(btn));
			},
		);
	},

	/**
	 * Destroy all scroll-top instances in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		$$(SELECTOR, root).forEach(
			/** @param {HTMLElement} btn */
			(btn) => {
				instanceStore.cleanup(btn, (inst) => inst.destroy());
			},
		);
	},

	/**
	 * Scroll to top programmatically
	 * @param {number} duration - Animation duration in ms
	 */
	scrollToTop,
};

export default FxScrollTop;
