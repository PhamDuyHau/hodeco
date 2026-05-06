import Swiper from 'swiper';

/**
 * Parse options from data attribute
 */
export const parseOptions = (el) => {
	const json = el?.querySelector('.swiper-wrapper')?.dataset?.swiperOptions;
	if (!json) return {};
	try {
		return JSON.parse(json);
	} catch (e) {
		console.warn('[FxSlider] Invalid JSON on element', el, e);
		return {};
	}
};

/**
 * Convert shorthand breakpoints to Swiper format
 */
export const getBreakpoints = (options = {}) => {
	if (options.breakpoints) return options.breakpoints;

	const bp = {};
	const map = { xs: 0, sm: 640, md: 768, lg: 1024, xl: 1280, xxl: 1536 };
	Object.entries(map).forEach(([key, val]) => {
		if (options[key]) bp[val] = options[key];
	});
	return bp;
};

/**
 *   Thumbs D
 */
const createThumbsSwiper = (el) => {
    const section = el.closest('.home-progress-slider');
    if (!section) return null;

    const thumbsEl = section.querySelector('.thumbs-slider');
    if (!thumbsEl) return null;

    return new Swiper(thumbsEl, {
        slidesPerView: 'auto',
        watchSlidesProgress: true,
    });
};

/**
 *   Bar 
 */
const moveProgress = (swiper, progress = 0) => {
	const section = swiper.el.closest('.home-progress-slider');
	const bar = section.querySelector('.progress-fill');
	const thumbs = section.querySelectorAll('.thumbs-slider .swiper-slide');

	if (!bar || !thumbs.length) return;

	const current = swiper.realIndex;
	let next = current + 1;
	let isLast = false;

	if (next >= thumbs.length) {
		isLast = true;
		next = current;
	}

	const currentEl = thumbs[current];
	const nextEl = thumbs[next];

	const parentRect = thumbs[0].parentElement.getBoundingClientRect();
	const currentRect = currentEl.getBoundingClientRect();
	const nextRect = nextEl.getBoundingClientRect();

	const currentX = currentRect.left - parentRect.left;
	const nextX = nextRect.left - parentRect.left;

	let x;

	if (isLast) {
		const wrapper = thumbs[0].parentElement;
		const maxWidth = wrapper.scrollWidth;

		x = currentX + (maxWidth - currentX) * progress;
	} else {
		x = currentX + (nextX - currentX) * progress;
	}

	bar.style.width = `${x}px`;
};

/**
 * Build Swiper options
 */
export const buildSwiperOptions = (options, classes, el) => {

	const swiperOptions = {
		loopedSlides: parseInt(options.loopedSlides) || undefined,
		loopAdditionalSlides: parseInt(options.loopedSlides) || 0,
		spaceBetween: parseInt(options.spaceBetween) || 0,
		slidesPerView: options.slidesPerView === 'auto' ? 'auto' : parseInt(options.slidesPerView) || 1,
		speed: parseInt(options.speed) || 600,
		direction: options.direction || 'horizontal',
		grabCursor: !!options.grabCursor,
		loop: !!options.loop,
		parallax: !!options.parallax,
		autoHeight: !!options.autoHeight,
		rewind: !!options.rewind,
		observer: !!options.observer,
		observeParents: !!options.observeParents,
		watchSlidesProgress: !!options.watchSlidesProgress,
		breakpoints: getBreakpoints(options),

		on: {
			autoplayTimeLeft(swiper, time, progress) {
				const p = 1 - progress;
				moveProgress(swiper, p);
			},

			slideChange(swiper) {
				moveProgress(swiper, 0);
			},

			init(swiper) {
				moveProgress(swiper, 0);
			}
		},
	};

	const thumbsSwiper = createThumbsSwiper(el);

	// Thumbs
	if (thumbsSwiper) {
		swiperOptions.thumbs = { swiper: thumbsSwiper };
	}

	// FreeMode
	if (options.freeMode) {
		swiperOptions.freeMode = { enabled: true, sticky: true };
	}

	// CSS Mode
	if (options.cssMode) {
		swiperOptions.cssMode = true;
		swiperOptions.observer = true;
		swiperOptions.observeParents = true;
	}

	// Effect
	if (options.effect) {
		swiperOptions.effect = options.effect;
		if (options.effect === 'fade') {
			swiperOptions.fadeEffect = { crossFade: true };
		}
	}

	// Centered
	if (options.centered) {
		swiperOptions.centeredSlides = true;
		swiperOptions.centeredSlidesBounds = true;
	}

	// Autoplay
	if (options.autoplay) {
		swiperOptions.autoplay = {
			delay: parseInt(options.delay) || 6000,
			pauseOnMouseEnter: false,
			disableOnInteraction: options.disableOnInteraction ?? true,
			reverseDirection: !!options.reverseDirection,
		};
	}

	// Marquee
	if (options.marquee) {
		swiperOptions.loop = true;
		swiperOptions.speed = parseInt(options.speed) || 6000;
		swiperOptions.autoplay = {
			delay: 0,
			pauseOnMouseEnter: true,
			disableOnInteraction: options.disableOnInteraction ?? true,
			reverseDirection: !!options.reverseDirection,
		};
	}

	// RTL
	if (options.rtl) {
		el.setAttribute('dir', 'rtl');
	}

	// Grid
	if (options.rows) {
		swiperOptions.grid = {
			rows: parseInt(options.rows) || 1,
			fill: 'row',
		};
	}

	return swiperOptions;
};
