// slider/fx-slider.js
import './fx-slider.scss';

import { $$ } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

import { nanoid } from 'nanoid';
import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination, Thumbs, FreeMode, Grid } from 'swiper/modules';

import { parseOptions, buildSwiperOptions } from './fx-slider.options.js';
import { getControls, buildNavigation, buildPagination, buildScrollbar, buildAutoplayProgress } from './fx-slider.controls.js';

const SELECTOR = '[data-fx-slider]';

const instanceStore = createWeakStore();
const observerStore = createWeakStore();

const defaultModules = [Autoplay, Navigation, Pagination, Thumbs, FreeMode, Grid];

/**
 * Default Swiper options
 */
const defaultOptions = {
	allowTouchMove: true,
	threshold: 5,
	wrapperClass: 'swiper-wrapper',
	slideClass: 'swiper-slide',
	slideActiveClass: 'swiper-slide-active',
};

/**
 * Generate unique class names for each instance
 * @returns {Object}
 */
const generateClasses = () => {
	const id = nanoid(8);
	return {
		id,
		swiper: `swiper-${id}`,
		next: `swiper-next-${id}`,
		prev: `swiper-prev-${id}`,
		pagination: `swiper-pagination-${id}`,
		scrollbar: `swiper-scrollbar-${id}`,
		progress: `swiper-autoplay-progress-${id}`,
	};
};

/**
 * Initialize a single Swiper instance
 * @param {HTMLElement} el - Swiper container
 * @returns {Swiper|null}
 */
const initSwiper = (el) => {
	if (!el || instanceStore.has(el)) return null;

	const classes = generateClasses();
	el.classList.add(classes.swiper);

	const options = parseOptions(el);
	if (!options || Object.keys(options).length === 0) {
		console.warn('[FxSlider] Skipped:', el, '(no valid options)');
		return null;
	}

	// Thumbs swiper
	let thumbsSwiper = null;
	if (options.thumbs) {
		const thumbsEl = document.querySelector(options.thumbs);
		if (thumbsEl) {
			thumbsSwiper = instanceStore.get(thumbsEl) || initSwiper(thumbsEl);
		}
	}

	// Build base options
	const swiperOptions = {
		modules: defaultModules,
		...defaultOptions,
		...buildSwiperOptions(options, classes, el, thumbsSwiper),
	};

	// Build controls
	const controls = getControls(el);
	const fragment = document.createDocumentFragment();

	const navigation = buildNavigation(options, classes, controls, fragment);
	if (navigation) swiperOptions.navigation = navigation;

	const pagination = buildPagination(options, classes, controls, fragment);
	if (pagination) swiperOptions.pagination = pagination;

	const scrollbar = buildScrollbar(options, classes, controls, fragment);
	if (scrollbar) swiperOptions.scrollbar = scrollbar;

	const progressHandler = buildAutoplayProgress(options, classes, controls, fragment);
	if (progressHandler) swiperOptions.on = progressHandler;

	// Append controls to DOM
	if (fragment.childNodes.length) {
		controls.append(fragment);
	}

	// Create instance
	const instance = new Swiper(`.${classes.swiper}`, swiperOptions);
	el.swiper = instance;
	instanceStore.set(el, instance);

	Events.emit('fx:slider:init', { el, instance });

	return instance;
};

const FxSlider = {
	/**
	 * Initialize all sliders in root (with lazy loading via IntersectionObserver)
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		$$(SELECTOR, root).forEach(
			/** @param {HTMLElement} el */
			(el) => {
				if (instanceStore.has(el)) return;

				// Lazy init with IntersectionObserver
				if ('IntersectionObserver' in window) {
					const observer = new IntersectionObserver(
						([entry], obs) => {
							if (entry.isIntersecting) {
								initSwiper(entry.target);
								obs.unobserve(entry.target);
								observerStore.delete(entry.target);
							}
						},
						{ rootMargin: '100px' },
					);

					observer.observe(el);
					observerStore.set(el, observer);
				} else {
					// Fallback: init immediately
					initSwiper(el);
				}
			},
		);
	},

	/**
	 * Destroy all sliders in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		$$(SELECTOR, root).forEach(
			/** @param {HTMLElement} el */
			(el) => {
				// Disconnect observer if exists
				observerStore.cleanup(el, (obs) => obs.disconnect());

				// Destroy swiper instance
				instanceStore.cleanup(el, (instance) => {
					instance.destroy(true, true);
					delete el['swiper'];
				});
			},
		);

		Events.emit('fx:slider:destroy');
	},

	/**
	 * Expose Swiper class for direct access
	 */
	Swiper,

	/**
	 * Manually init a specific element
	 * @param {HTMLElement} el - Element to init
	 * @returns {Swiper|null}
	 */
	init: initSwiper,
};

export default FxSlider;
