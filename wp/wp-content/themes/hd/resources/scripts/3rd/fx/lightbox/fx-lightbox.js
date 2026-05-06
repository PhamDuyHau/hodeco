// lightbox/fx-lightbox.js
import './fx-lightbox.scss';

import { $$ } from '../../dom.js';
import Events from '../../events.js';

import { Fancybox } from '@fancyapps/ui';

const SELECTOR = '[data-fx-lightbox]';

// Selectors for auto-binding (popup, video, gallery)
const AUTO_SELECTORS = {
	popup: '.fcy-popup, .fcy-video, .banner-video a',
	gallery: '[id^="gallery-"] a, [data-rel="lightbox"]',
};

/**
 * Default Fancybox options
 * @type {*}
 */
const DEFAULT_OPTIONS = {};

/**
 * Gallery options with grouping and carousel
 * @type {*}
 */
const GALLERY_OPTIONS = {
	groupAll: true,
	Carousel: {
		transition: 'slide',
		friction: 0.92,
	},
};

const FxLightbox = {
	/**
	 * Initialize all lightbox bindings in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		// Bind popup/video elements
		if (root.querySelector(AUTO_SELECTORS.popup)) {
			Fancybox.bind(AUTO_SELECTORS.popup, DEFAULT_OPTIONS);
		}

		// Bind gallery elements with grouping
		AUTO_SELECTORS.gallery.split(', ').forEach((selector) => {
			if (root.querySelector(selector)) {
				Fancybox.bind(selector, GALLERY_OPTIONS);
			}
		});

		// Handle custom [data-fx-lightbox] containers
		$$(SELECTOR, root).forEach(
			/** @param {HTMLElement} container */
			(container) => {
				const group = container.dataset.group === 'true';
				const options = group ? GALLERY_OPTIONS : DEFAULT_OPTIONS;

				// Bind all links inside container (using container + selector overload)
				Fancybox.bind(container, 'a', options);
			},
		);

		Events.emit('fx:lightbox:init', { root });
	},

	/**
	 * Destroy Fancybox bindings within root
	 * @param {Document|Element} root - Root element to unbind
	 */
	destroyAll(root = document) {
		// Unbind popup/video elements within root
		if (root.querySelector(AUTO_SELECTORS.popup)) {
			Fancybox.unbind(AUTO_SELECTORS.popup);
		}

		// Unbind gallery elements
		AUTO_SELECTORS.gallery.split(', ').forEach((selector) => {
			if (root.querySelector(selector)) {
				Fancybox.unbind(selector);
			}
		});

		// Unbind custom containers
		if (root.querySelector(SELECTOR)) {
			Fancybox.unbind(`${SELECTOR} a`);
		}

		Events.emit('fx:lightbox:destroy', { root });
	},

	/**
	 * Expose Fancybox for direct API access
	 */
	Fancybox,
};

export default FxLightbox;
