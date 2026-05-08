var __typeError = (msg) => {
  throw TypeError(msg);
};
var __accessCheck = (obj, member, msg) => member.has(obj) || __typeError("Cannot " + msg);
var __privateGet = (obj, member, getter) => (__accessCheck(obj, member, "read from private field"), getter ? getter.call(obj) : member.get(obj));
var __privateAdd = (obj, member, value) => member.has(obj) ? __typeError("Cannot add the same private member more than once") : member instanceof WeakSet ? member.add(obj) : member.set(obj, value);
var _listeners;
class EventBus {
  constructor() {
    /** @type {Map<string, Set<Function>>} */
    __privateAdd(this, _listeners, /* @__PURE__ */ new Map());
  }
  /**
   * Subscribe to an event
   * @param {string} event - Event name
   * @param {Function} cb - Callback function
   * @returns {Function} - The callback (for unsubscribing)
   */
  on(event, cb) {
    if (!__privateGet(this, _listeners).has(event)) {
      __privateGet(this, _listeners).set(event, /* @__PURE__ */ new Set());
    }
    __privateGet(this, _listeners).get(event).add(cb);
    return cb;
  }
  /**
   * Subscribe to an event (fires once then unsubscribes)
   * @param {string} event - Event name
   * @param {Function} cb - Callback function
   * @returns {void}
   */
  once(event, cb) {
    const wrapper = (payload) => {
      cb(payload);
      this.off(event, wrapper);
    };
    this.on(event, wrapper);
  }
  /**
   * Unsubscribe from an event
   * @param {string} event - Event name
   * @param {Function} [cb] - Callback to remove (omit to remove all)
   * @returns {void}
   */
  off(event, cb) {
    if (!__privateGet(this, _listeners).has(event)) return;
    if (!cb) {
      __privateGet(this, _listeners).delete(event);
      return;
    }
    const set = __privateGet(this, _listeners).get(event);
    set.delete(cb);
    if (set.size === 0) {
      __privateGet(this, _listeners).delete(event);
    }
  }
  /**
   * Emit an event to all subscribers
   * @param {string} event - Event name
   * @param {Object} [payload={}] - Event payload
   * @returns {void}
   */
  emit(event, payload = {}) {
    if (!__privateGet(this, _listeners).has(event)) return;
    __privateGet(this, _listeners).get(event).forEach((cb) => {
      try {
        cb(payload);
      } catch (e) {
        console.error(`[EventBus:${event}]`, e);
      }
    });
  }
  /**
   * Check if event has subscribers
   * @param {string} event - Event name
   * @returns {boolean}
   */
  has(event) {
    return __privateGet(this, _listeners).has(event) && __privateGet(this, _listeners).get(event).size > 0;
  }
  /**
   * Get subscriber count for an event
   * @param {string} event - Event name
   * @returns {number}
   */
  count(event) {
    var _a;
    return ((_a = __privateGet(this, _listeners).get(event)) == null ? void 0 : _a.size) || 0;
  }
  /**
   * Clear all event subscriptions
   * @returns {void}
   */
  clear() {
    __privateGet(this, _listeners).clear();
  }
  /**
   * Get all registered event names
   * @returns {string[]}
   */
  get events() {
    return [...__privateGet(this, _listeners).keys()];
  }
}
_listeners = new WeakMap();
const Events = new EventBus();
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
const splitEvents = (ev) => ev.split(" ").filter(Boolean);
const isCollection = (el) => NodeList.prototype.isPrototypeOf(el) || Array.isArray(el);
const on = (el, ev, handler, opts) => {
  if (!el) return;
  const events = splitEvents(ev);
  const bind = (target) => events.forEach((e) => target.addEventListener(e, handler, opts));
  isCollection(el) ? el.forEach(bind) : bind(el);
};
const off = (el, ev, handler, opts) => {
  if (!el) return;
  const events = splitEvents(ev);
  const unbind = (target) => events.forEach((e) => target.removeEventListener(e, handler, opts));
  isCollection(el) ? el.forEach(unbind) : unbind(el);
};
const delegate = (root, selector, ev, handler, opts) => {
  const wrapper = (e) => {
    const target = (
      /** @type {Element} */
      e.target.closest(selector)
    );
    if (target && root.contains(target)) {
      handler.call(target, e, target);
    }
  };
  on(root, ev, wrapper, opts);
  return wrapper;
};
const closest = (el, selector) => el ? el.closest(selector) : null;
const trigger = (el, name, detail = {}) => el && el.dispatchEvent(new CustomEvent(name, { detail }));
const uid = (prefix = "fx") => `${prefix}-${Math.random().toString(36).slice(2, 8)}`;
const createWeakStore = () => {
  const map = /* @__PURE__ */ new WeakMap();
  const isObject = (v) => v !== null && typeof v === "object";
  return {
    /**
     * Check if key exists
     * @param {object} key
     * @returns {boolean}
     */
    has(key) {
      return isObject(key) && map.has(key);
    },
    /**
     * Get value by key
     * @param {object} key
     * @returns {any}
     */
    get(key) {
      return isObject(key) ? map.get(key) : void 0;
    },
    /**
     * Set value for key
     * @param {object} key
     * @param {any} value
     */
    set(key, value) {
      if (isObject(key)) map.set(key, value);
    },
    /**
     * Delete key
     * @param {object} key
     */
    delete(key) {
      if (isObject(key)) map.delete(key);
    },
    /**
     * Get existing value or create new one using factory
     * @param {object} key
     * @param {() => any} factory - Function to create value if not exists
     * @returns {any}
     */
    getOrCreate(key, factory) {
      if (!isObject(key)) return void 0;
      if (map.has(key)) return map.get(key);
      const value = factory();
      map.set(key, value);
      return value;
    },
    /**
     * Run cleanup function on value and delete key
     * @param {object} key
     * @param {(value: any) => void} fn - Cleanup function
     */
    cleanup(key, fn) {
      if (!isObject(key)) return;
      const value = map.get(key);
      if (value) {
        fn(value);
        map.delete(key);
      }
    }
  };
};
const urlAlphabet = "useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";
let nanoid = (size = 21) => {
  let id = "";
  let bytes = crypto.getRandomValues(new Uint8Array(size |= 0));
  while (size--) {
    id += urlAlphabet[bytes[size] & 63];
  }
  return id;
};
export {
  $$ as $,
  Events as E,
  on as a,
  $ as b,
  createWeakStore as c,
  delegate as d,
  closest as e,
  nanoid as n,
  off as o,
  trigger as t,
  uid as u
};
//# sourceMappingURL=vendor.We5pwaIa.js.map
