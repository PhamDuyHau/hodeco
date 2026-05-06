// utils/script-loader.js

const scriptLoader = (timeout = 4000, selector = 'script[data-type="lazy"]') => {
	return new Promise((resolve) => {
		const events = ['mouseover', 'keydown', 'touchstart', 'touchmove', 'wheel'];
		let done = false;

		const load = () => {
			if (done) return;
			done = true;

			document.querySelectorAll(selector).forEach((s) => {
				s.src = s.dataset.src;
				s.removeAttribute('data-src');
				s.removeAttribute('data-type');
			});

			clearTimeout(timer);
			resolve();
		};

		const timer = setTimeout(load, timeout);

		// Events use { once: true } so they auto-remove after first trigger
		events.forEach((e) => window.addEventListener(e, load, { once: true, passive: true, capture: true }));
	});
};

export default scriptLoader;
