// slider/fx-slider.controls.js

/**
 * Get or create swiper controls container
 * @param {HTMLElement} el - Swiper container
 * @returns {HTMLElement}
 */
export const getControls = (el) => {
	const wrapper = el.closest('.closest-swiper') || el.parentElement;
	const existing = wrapper?.querySelector('.swiper-controls');
	if (existing) return existing;

	const div = document.createElement('div');
	div.className = 'swiper-controls';
	el.after(div);
	return div;
};

/**
 * Build navigation buttons
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Navigation config for Swiper
 */
export const buildNavigation = (options, classes, controls, fragment) => {
	if (!options.navigation) return null;

	let btnPrev = controls?.querySelector('.swiper-button-prev');
	let btnNext = controls?.querySelector('.swiper-button-next');

	if (btnPrev && btnNext) {
		btnPrev.classList.add(classes.prev);
		btnNext.classList.add(classes.next);
	} else {
		btnPrev = document.createElement('div');
		btnNext = document.createElement('div');
		btnPrev.className = `swiper-button swiper-button-prev ${classes.prev}`;
		btnNext.className = `swiper-button swiper-button-next ${classes.next}`;
		// btnPrev.innerHTML = `<svg class="size-6!"><use href="#icon-arrow-left-outline"></use></svg>`;
		btnPrev.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32"><path d="M14 26l1.41-1.41L7.83 17H28v-2H7.83l7.58-7.59L14 6L4 16l10 10z" fill="currentColor"></path></svg>`;
		btnNext.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32"><path d="M18 26l-1.41-1.41L24.17 17H4v-2h20.17l-7.58-7.59L18 6l10 10z" fill="currentColor"></path></svg>`;
		fragment.append(btnPrev, btnNext);
	}

	return {
		nextEl: '.' + classes.next,
		prevEl: '.' + classes.prev,
	};
};

/**
 * Build pagination
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Pagination config for Swiper
 */
export const buildPagination = (options, classes, controls, fragment) => {
	if (!options.pagination) return null;

	let pagination = controls?.querySelector('.swiper-pagination');
	if (pagination) {
		pagination.classList.add(classes.pagination);
	} else {
		pagination = document.createElement('div');
		pagination.className = `swiper-pagination ${classes.pagination}`;
		fragment.append(pagination);
	}

	const type = options.pagination;
	return {
		el: '.' + classes.pagination,
		clickable: true,
		...(type === 'bullets' && { dynamicBullets: true, type: 'bullets' }),
		...(type === 'fraction' && { type: 'fraction' }),
		...(type === 'progressbar' && { type: 'progressbar' }),
		...(type === 'custom' && { renderBullet: (i, cls) => `<span class="${cls}"><span class="num">${i + 1}</span></span>` }),
	};
};

/**
 * Build scrollbar
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Scrollbar config for Swiper
 */
export const buildScrollbar = (options, classes, controls, fragment) => {
	if (!options.scrollbar) return null;

	let scrollbar = controls?.querySelector('.swiper-scrollbar');
	if (scrollbar) {
		scrollbar.classList.add(classes.scrollbar);
	} else {
		scrollbar = document.createElement('div');
		scrollbar.className = `swiper-scrollbar ${classes.scrollbar}`;
		fragment.append(scrollbar);
	}

	return {
		el: '.' + classes.scrollbar,
		hide: true,
		draggable: true,
	};
};

/**
 * Build autoplay progress indicator
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Event handlers for Swiper
 */
export const buildAutoplayProgress = (options, classes, controls, fragment) => {
	if (!options.autoplayProgress) return null;

	const progress = document.createElement('div');
	progress.className = `swiper-autoplay-progress ${classes.progress}`;
	progress.innerHTML = `<svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="20"></circle></svg><span></span>`;
	fragment.append(progress);

	let lastSecond = -1;
	return {
		autoplayTimeLeft(s, time, progressValue) {
			const currentSecond = Math.ceil(time / 1000);
			if (currentSecond !== lastSecond) {
				lastSecond = currentSecond;
				const progressEl = controls?.querySelector('.swiper-autoplay-progress');
				if (progressEl) {
					progressEl.style.setProperty('--progress', 1 - progressValue);
					progressEl.querySelector('span').textContent = `${currentSecond}s`;
				}
			}
		},
	};
};
