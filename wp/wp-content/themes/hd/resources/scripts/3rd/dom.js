// 3rd/dom.js - Global DOM helpers

/* --------------------------------------------------
 * Query helpers
 * -------------------------------------------------- */

/**
 * Query single element
 * @param {string} selector - CSS selector
 * @param {Document|Element} [root=document] - Root element to search within
 * @returns {Element|null}
 */
export const $ = (selector, root = document) => root.querySelector(selector);

/**
 * Query all elements as array
 * @param {string} selector - CSS selector
 * @param {Document|Element} [root=document] - Root element to search within
 * @returns {Element[]}
 */
export const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

/* --------------------------------------------------
 * Event helpers
 * -------------------------------------------------- */

/**
 * @param {string} ev - Space-separated event names
 * @returns {string[]}
 */
const splitEvents = (ev) => ev.split(' ').filter(Boolean);

/**
 * @param {any} el
 * @returns {boolean}
 */
const isCollection = (el) => NodeList.prototype.isPrototypeOf(el) || Array.isArray(el);

/**
 * Add event listener(s) to element(s)
 * @param {Element|Element[]|NodeList|null} el - Target element(s)
 * @param {string} ev - Space-separated event names (e.g. 'click focus')
 * @param {EventListener} handler - Event handler function
 * @param {AddEventListenerOptions|boolean} [opts] - Event listener options
 * @returns {void}
 */
export const on = (el, ev, handler, opts) => {
	if (!el) return;
	const events = splitEvents(ev);
	const bind = (target) => events.forEach((e) => target.addEventListener(e, handler, opts));
	isCollection(el) ? el.forEach(bind) : bind(el);
};

/**
 * Remove event listener(s) from element(s)
 * @param {Element|Element[]|NodeList|null} el - Target element(s)
 * @param {string} ev - Space-separated event names
 * @param {EventListener} handler - Event handler function
 * @param {EventListenerOptions|boolean} [opts] - Event listener options
 * @returns {void}
 */
export const off = (el, ev, handler, opts) => {
	if (!el) return;
	const events = splitEvents(ev);
	const unbind = (target) => events.forEach((e) => target.removeEventListener(e, handler, opts));
	isCollection(el) ? el.forEach(unbind) : unbind(el);
};

/**
 * Event delegation helper
 * @param {Element} root - Root element to attach listener
 * @param {string} selector - CSS selector for target elements
 * @param {string} ev - Space-separated event names
 * @param {(e: Event, target: Element) => void} handler - Event handler with target
 * @param {AddEventListenerOptions|boolean} [opts] - Event listener options
 * @returns {EventListener} - The wrapper function (for removal)
 */
export const delegate = (root, selector, ev, handler, opts) => {
	/** @type {EventListener} */
	const wrapper = (e) => {
		const target = /** @type {Element} */ (e.target).closest(selector);
		if (target && root.contains(target)) {
			handler.call(target, e, target);
		}
	};
	on(root, ev, wrapper, opts);
	return wrapper;
};

/* --------------------------------------------------
 * DOM helpers
 * -------------------------------------------------- */

/**
 * Find closest ancestor matching selector
 * @param {Element|null} el - Starting element
 * @param {string} selector - CSS selector
 * @returns {Element|null}
 */
export const closest = (el, selector) => (el ? el.closest(selector) : null);

/**
 * Append child to parent
 * @param {Element|null} parent - Parent element
 * @param {Element|null} child - Child element to append
 * @returns {void}
 */
export const append = (parent, child) => parent && child && parent.appendChild(child);

/**
 * Check if element is visible in DOM
 * @param {Element|null} el - Element to check
 * @returns {boolean}
 */
export const isVisible = (el) => !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));

/**
 * Execute function when DOM is ready
 * @param {() => void} fn - Function to execute
 * @returns {void}
 */
export const ready = (fn) => (document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn));

/* --------------------------------------------------
 * Class helpers
 * -------------------------------------------------- */

/**
 * @param {string} cls - Space-separated class names
 * @returns {string[]}
 */
const splitClasses = (cls) => cls.split(' ').filter(Boolean);

/**
 * Check if element has class
 * @param {Element|null} el - Element to check
 * @param {string} cls - Class name
 * @returns {boolean}
 */
export const hasClass = (el, cls) => !!(el && el.classList.contains(cls));

/**
 * Add class(es) to element
 * @param {Element|null} el - Target element
 * @param {string} cls - Space-separated class names
 * @returns {void}
 */
export const addClass = (el, cls) => el && el.classList.add(...splitClasses(cls));

/**
 * Remove class(es) from element
 * @param {Element|null} el - Target element
 * @param {string} cls - Space-separated class names
 * @returns {void}
 */
export const removeClass = (el, cls) => el && el.classList.remove(...splitClasses(cls));

/**
 * Toggle class(es) on element
 * @param {Element|null} el - Target element
 * @param {string} cls - Space-separated class names
 * @param {boolean} [force] - Force add (true) or remove (false)
 * @returns {void}
 */
export const toggleClass = (el, cls, force) => el && splitClasses(cls).forEach((c) => el.classList.toggle(c, force));

/* --------------------------------------------------
 * Style & data
 * -------------------------------------------------- */

/**
 * Apply inline styles to element
 * @param {HTMLElement|null} el - Target element
 * @param {Partial<CSSStyleDeclaration>} [styles={}] - Style object
 * @returns {void}
 */
export const css = (el, styles = {}) => el && Object.assign(el.style, styles);

/**
 * Get or set data attribute
 * @param {HTMLElement|null} el - Target element
 * @param {string} key - Data key (without 'data-' prefix)
 * @param {string} [val] - Value to set (omit to get)
 * @returns {string|null|undefined}
 */
export const data = (el, key, val) => {
	if (!el) return null;
	if (val === undefined) return el.dataset[key];
	el.dataset[key] = val;
};

/* --------------------------------------------------
 * Element creation & events
 * -------------------------------------------------- */

/**
 * Dispatch custom event on element
 * @param {Element|null} el - Target element
 * @param {string} name - Event name
 * @param {Object} [detail={}] - Event detail data
 * @returns {void}
 */
export const trigger = (el, name, detail = {}) => el && el.dispatchEvent(new CustomEvent(name, { detail }));

/**
 * Create element with attributes
 * Special keys: 'class' -> className, 'html' -> innerHTML, 'text' -> textContent
 * @param {string} tag - HTML tag name
 * @param {Record<string, string>} [attrs={}] - Attributes object
 * @returns {HTMLElement}
 */
export const create = (tag, attrs = {}) => {
	const el = document.createElement(tag);
	Object.entries(attrs).forEach(([k, v]) => {
		if (k === 'class') el.className = v;
		else if (k === 'html') el.innerHTML = v;
		else if (k === 'text') el.textContent = v;
		else el.setAttribute(k, v);
	});

	return el;
};

/* --------------------------------------------------
 * Utils
 * -------------------------------------------------- */

/**
 * Generate unique ID with prefix
 * @param {string} [prefix='fx'] - ID prefix
 * @returns {string}
 */
export const uid = (prefix = 'fx') => `${prefix}-${Math.random().toString(36).slice(2, 8)}`;
