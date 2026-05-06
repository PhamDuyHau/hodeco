// 3rd/createLoader.js
// Factory function to create a lazy loader

/**
 * @param {Object} config - Module configuration { key: { selector, loader } }
 * @param {string} name - Loader name for logging
 * @param {Object} options - Loader options
 * @param {boolean} [options.debug=false] - Enable debug logging
 */
export function createLoader(config, name = 'Loader', { debug = false } = {}) {
	const loaded = new Map();

	const log = (...args) => {
		if (debug) console.log(`[${name}]`, ...args);
	};

	const isNeeded = (key, root = document) => {
		const cfg = config[key];
		return cfg ? root.querySelector(cfg.selector) !== null : false;
	};

	const load = async (key) => {
		if (loaded.has(key)) {
			log(`Cache hit: ${key}`);
			return loaded.get(key);
		}

		const cfg = config[key];
		if (!cfg) {
			log(`Not found: ${key}`);
			return null;
		}

		try {
			log(`Loading: ${key}`);
			const module = await cfg.loader();
			const m = module.default || module;
			loaded.set(key, m);
			log(`Loaded: ${key}`);
			return m;
		} catch (e) {
			console.error(`[${name}] Failed to load: ${key}`, e);
			return null;
		}
	};

	return {
		async init({ root = document } = {}) {
			const needed = Object.keys(config).filter((key) => isNeeded(key, root));
			log(`Init - needed modules:`, needed);

			const promises = needed.map(async (key) => {
				const m = await load(key);
				m?.initAll?.(root);
			});

			await Promise.all(promises);
			log(`Init complete`);
		},

		async destroy(key, root = document) {
			const m = loaded.get(key);
			if (m) {
				m.destroyAll?.(root);
				log(`Destroyed: ${key}`);
			}
		},

		async reinit(key, root = document) {
			let m = loaded.get(key);
			if (!m) m = await load(key);
			if (m) {
				m.destroyAll?.(root);
				m.initAll?.(root);
				log(`Reinit: ${key}`);
			}
		},

		load,

		get loaded() {
			return [...loaded.keys()];
		},

		get available() {
			return Object.keys(config);
		},

		/** Enable/disable debug mode at runtime */
		setDebug(enabled) {
			debug = enabled;
			log(`Debug mode: ${enabled ? 'ON' : 'OFF'}`);
		},

		/**
		 * Refresh/re-init modules in a specific container (for dynamic AJAX content)
		 * Zero overhead when not called - most performant option for dynamic content
		 * @param {Element|Document} root - Container to scan for new elements
		 * @param {string|string[]} [keys] - Specific module(s) to refresh, or all if omitted
		 */
		async refresh(root = document, keys = null) {
			const targetKeys = keys ? (Array.isArray(keys) ? keys : [keys]) : Object.keys(config);

			const needed = targetKeys.filter((key) => isNeeded(key, root));
			log(`Refresh - needed modules:`, needed);

			const promises = needed.map(async (key) => {
				const m = await load(key);
				// Only init, don't destroy - for new elements added via AJAX
				m?.initAll?.(root);
			});

			await Promise.all(promises);
			log(`Refresh complete`);
		},
	};
}
