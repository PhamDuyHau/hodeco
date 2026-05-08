import { $ as $$, E as Events, c as createWeakStore } from "../vendor.We5pwaIa.js";
const $locale = (l10n2) => new Proxy(l10n2, {
  /**
   * Trap for getter
   *
   * Returns translation string if set or translation marker.
   *
   * @param {object} self
   * @param {string} marker
   * @returns {string}
   */
  get(self, marker) {
    return self.lang && self[self.lang][marker] || marker;
  }
});
const l10n = $locale({});
const REJECTED_TAGS = "html|head|body|meta|link|style|script";
const DENIED_PROPS = "attributes|classList|innerHTML|outerHTML|nodeName|nodeType";
class part {
  /**
   * Element render
   */
  render() {
    delete this.element;
    delete this.render;
  }
  /**
   * Binds the compo to an element
   *
   * @see Node.appendChild
   *
   * @param {Element} node A valid Element node
   * @param {function} [cb] Callback function
   * @returns {boolean}
   */
  bind(node, cb) {
    const el = this[this.ns];
    typeof cb == "function" && cb.call(this, el);
    return !!node.appendChild(el);
  }
  /**
   * Unbinds the compo from an element
   *
   * @see Node.removeChild
   *
   * @param {Element} node A valid Element node
   * @param {function} [cb] Callback function
   * @returns {boolean}
   */
  unbind(node, cb) {
    const el = this[this.ns];
    typeof cb == "function" && cb.call(this, el);
    return !!node.removeChild(el);
  }
  /**
   * Replaces a placeholder element with compo and binds the compo
   *
   * @see ChildNode.replaceWith
   *
   * @param {Element} node A valid Element node used as placeholder
   * @param {function} [cb] Callback function
   * @returns {boolean}
   */
  place(node, cb) {
    const el = this[this.ns];
    typeof cb == "function" && cb.call(this, el);
    return !!node.replaceWith(el);
  }
  /**
   * Appends a compo to this compo
   *
   * @see Node.appendChild
   *
   * @param {part} compo A compo
   * @returns {boolean}
   */
  append(compo) {
    const ns = this.ns, el = this[ns];
    return !!el.appendChild(compo[ns]);
  }
  /**
   * Prepends a compo to this compo
   *
   * @see Node.insertBefore
   *
   * @param {part} compo A compo
   * @returns {boolean}
   */
  prepend(compo) {
    const ns = this.ns, el = this[ns];
    return !!el.insertBefore(compo[ns], el.firstElementChild || null);
  }
  /**
   * Removes a compo from this composition
   *
   * @see Node.removeChild
   *
   * @param {part} compo A compo
   * @returns {boolean}
   */
  remove(compo) {
    const ns = this.ns, el = this[ns];
    return !!el.removeChild(compo[ns]);
  }
  /**
   * Fills the compo inner with an element node
   *
   * Note: Any inner element contained will be removed.
   *
   * @see Node.appendChild
   *
   * @constant {RegExp} REJECTED_TAGS Regular expression for rejected tag
   * @param {Element} node A valid Element node
   * @returns {boolean}
   */
  fill(node) {
    if (!node instanceof Element || RegExp(REJECTED_TAGS, "i").test(node.tagName) || RegExp(`(<(${REJECTED_TAGS})*>)`, "i").test(node.innerHTML)) {
      throw new Error(l10n.EMTAG);
    }
    this.empty();
    return !!this[this.ns].appendChild(node);
  }
  /**
   * Emptys this compo
   *
   * Note: Any inner element will be removed.
   */
  empty() {
    while (this.first) {
      this.remove(this.first);
    }
  }
  /**
   * Getter for children property, the children compo of this compo
   *
   * @type {array}
   */
  get children() {
    return Array.prototype.map.call(this[this.ns].children, (node) => {
      return node._1;
    });
  }
  /**
   * Getter for first property, the first compo in this compo
   *
   * @type {part}
   */
  get first() {
    const el = this[this.ns];
    return el.firstElementChild ? el.firstElementChild._1 : null;
  }
  /**
   * Getter for last property, the last compo in this compo
   *
   * @type {part}
   */
  get last() {
    const el = this[this.ns];
    return el.lastElementChild ? el.lastElementChild._1 : null;
  }
}
class Compo extends part {
  /**
   * Constructor method
   *
   * @constructs
   * @constant {RegExp} REJECTED_TAGS Regular expression for rejected tag
   * @constant {RegExp} DENIED_PROPS Regular expression for denied properties
   * @param {string} ns Compo namespace
   * @param {string} [tag='div'] The Element node name or compo name
   * @param {string[]} [name] The compo name, used for CSS className
   * @param {object} [props] Properties for compo
   * @param {?object} [options] An optional ElementCreationOptions object
   * @param {?object} [elementNS] Options for namespace Element node
   * @param {string} [elementNS.namespaceURI] A valid namespace URI
   * @param {string} [elementNS.qualifiedName] A valid qualified name
   */
  constructor(ns, tag, name, props, options, elementNS) {
    super();
    const ns0 = this.ns = `_${ns}`;
    const tagName = tag ? tag.toString() : "div";
    if (RegExp(REJECTED_TAGS, "i").test(tagName)) {
      throw new Error(l10n.ETAGN);
    }
    const el = this[ns0] = this.element(ns, tagName, name, props, options, elementNS);
    this.__Compo = 1;
    this[ns0]._1 = this;
    if (props && typeof props == "object") {
      for (const prop in props) {
        const p = prop.toString();
        if (RegExp(DENIED_PROPS).test(p)) {
          throw new Error(l10n.EPROP);
        }
        if (p.indexOf("on") === 0 && props[p] && typeof props[p] == "function") {
          el[p] = props[p].bind(this);
        } else if (typeof props[p] != "object") {
          el[p] = props[p];
        } else if (p == "children") {
          if (typeof props[p] == "object" && props[p].length) {
            for (const child of props.children) {
              const { tag: tag2, name: name2, props: props2 } = child;
              this.append(new Compo(ns, tag2, name2, props2));
            }
          }
        }
      }
    }
    if (name) {
      const nodeClass = el.className;
      el.className = "";
      if (typeof name == "string") {
        el.className = `${ns}-${name}`;
      } else if (typeof name == "object") {
        el.className = Object.values(name).map((a) => `${ns}-${a}`).join(" ");
      }
      if (nodeClass) {
        el.className += ` ${nodeClass}`;
      }
    }
    this.render();
  }
  /**
   * Object wrapper
   *
   * @see Document.createElement
   * @see Document.createElementNS
   *
   * @param {string} ns Compo namespace
   * @param {string} tag The Element node name or compo name
   * @param {string} [name] Name for compo, used for CSS className
   * @param {object} [props] Properties for compo
   * @param {?object} [options] An optional ElementCreationOptions object
   * @param {?object} [elementNS] Options for namespaced Element node
   * @param {string} [elementNS.namespaceURI] A valid namespace URI
   * @param {string} [elementNS.qualifiedName] A valid qualified name
   */
  element(ns, tag, name, props, options, elementNS) {
    if (elementNS) return document.createElementNS(elementNS, tag, options);
    else return document.createElement(tag, options);
  }
  /**
   * Checks for an attribute of this compo
   *
   * @see Element.hasAttribute
   *
   * @param {string} attr An attribute
   * @returns {boolean}
   */
  hasAttr(attr) {
    return this[this.ns].hasAttribute(attr);
  }
  /**
   * Gets an attribute from this compo
   *
   * @see Element.getAttribute
   *
   * @param {string} attr An attribute
   * @returns {string}
   */
  getAttr(attr) {
    return this[this.ns].getAttribute(attr);
  }
  /**
   * Sets an attribute in this compo
   *
   * @see Element.setAttribute
   *
   * @param {string} attr An attribute
   * @param {string} value The value
   */
  setAttr(attr, value) {
    this[this.ns].setAttribute(attr, value);
  }
  /**
   * Removes an attribute from this compo
   *
   * @see Element.removeAttribute
   *
   * @param {string} attr An attribute
   */
  delAttr(attr) {
    this[this.ns].removeAttribute(attr);
  }
  /**
   * Gets a current style property
   *
   * @see Window.getComputedStyle
   *
   * @param {string} prop A style property
   * @returns {mixed}
   */
  getStyle(prop) {
    return getComputedStyle(this[this.ns])[prop];
  }
  /**
   * Shows this compo
   */
  show() {
    this[this.ns].hidden = false;
  }
  /**
   * Hides this compo
   */
  hide() {
    this[this.ns].hidden = true;
  }
  /**
   * Util to set attribute disabled to true
   */
  enable() {
    this[this.ns].disabled = false;
  }
  /**
   * Util to set attribute disabled to false
   */
  disable() {
    this[this.ns].disabled = true;
  }
  /**
   * Getter for node property, the Element node in this compo
   *
   * Note: Direct access to the node is discouraged.
   *
   * @type {Element}
   */
  get node() {
    console.warn(l10n.DOM);
    return this[this.ns];
  }
  /**
   * Getter for parent property, the parent compo of this compo
   *
   * @type {Compo}
   */
  get parent() {
    const el = this[this.ns];
    return el.parentElement && el.parentElement._1 ? el.parentElement._1 : null;
  }
  /**
   * Getter for previous property, the previous sibling of this compo
   *
   * @type {Compo}
   */
  get previous() {
    const el = this[this.ns];
    return el.previousElementSibling ? el.previousElementSibling._1 : null;
  }
  /**
   * Getter for next property, the next sibling of this compo
   *
   * @type {Compo}
   */
  get next() {
    const el = this[this.ns];
    return el.nextElementSibling ? el.nextElementSibling._1 : null;
  }
  /**
   * Getter for classList property, the classList of the Element node in this compo
   *
   * @see DOMTokenList
   *
   * @type {DOMTokenList}
   */
  get classList() {
    return this[this.ns].classList;
  }
  /**
   * Checks passed object is an ensemble Compo instance
   *
   * @static
   * @returns {boolean}
   */
  static isCompo(obj) {
    return obj instanceof Compo;
  }
}
class Data {
  /**
   * Constructor method
   *
   * @constructs
   * @param {string} ns Data namespace
   * @param {object} [obj] A starter Object
   */
  constructor(ns, obj) {
    if (obj && typeof obj == "object") {
      Object.assign(this, {}, obj);
    }
    const ns0 = this.ns = `_${ns}`;
    this.__Data = 0;
    this[ns0] = { ns };
  }
  /**
   * The compo method is a utility to render elements
   * 
   * When you create a compo with this method, it will create a Compo or an Object placeholder.
   * With defer render you can render it in place.
   *
   * @param {string} tag Element node tag or compo name
   * @param {string} [name] The compo name, used for CSS className
   * @param {object} [props] Properties for Element node or compo
   * @param {boolean} [defer] Defer componet render
   * @param {mixed} [load] Callback function, on load compo
   * @param {mixed} [unload] Callback function, on unload compo
   * @returns {mixed} compo A compo element or an Object placeholder 
   */
  compo(tag, name, props, defer = false, load = false, unload = false) {
    const ns = this[this.ns].ns;
    let compo;
    if (defer) {
      compo = { ns, tag, name, props, load, unload };
    } else {
      compo = new Compo(ns, tag, name, props);
    }
    if (load && typeof load == "function") {
      compo.load = props.onload = load;
    }
    if (unload && typeof unload == "function") {
      compo.unload = props.onunload = unload;
    }
    return compo;
  }
  /**
   * Renders a compo, passed by reference
   *
   * @async
   * @param {mixed} slot Reference of the element to render
   */
  async render(slot) {
    const el = this[this.ns];
    const self = this;
    if (el[slot] && el[slot]._) {
      el[slot].load();
    } else {
      el[slot] = { _: self[slot], load: self[slot].load, unload: self[slot].unload };
      self[slot] = new Compo(self[slot].ns, self[slot].tag, self[slot].name, self[slot].props);
      el[slot].load();
    }
  }
  /**
   * Unloads a compo, passed by reference
   *
   * @async
   * @param {mixed} slot Reference of the element to render
   */
  async unload(slot) {
    const el = this[this.ns];
    if (el[slot] && el[slot]._) {
      el[slot].unload();
    }
  }
  /**
   * Reflows a compo, passed by reference
   *
   * @async
   * @param {mixed} slot Reference of the element to render
   * @param {boolean} [force] Force reflow
   */
  async reflow(slot, force) {
    const el = this[this.ns];
    if (force) {
      el[slot] = this.compo(el[slot]._.ns, el[slot]._.name, el[slot]._.props);
    } else if (el[slot] && el[slot]._) {
      el[slot].load();
    }
  }
  /**
   * Checks passed object is an ensemble Data instance
   *
   * @static
   * @returns {boolean}
   */
  static isData(obj) {
    return obj instanceof Data;
  }
}
class Event {
  /**
   * Constructor method
   *
   * @see Element.addEventListener
   * @see Element.removeEventListener
   *
   * @constructs
   * @param {string} ns Event namespace
   * @param {string} name Event type name
   * @param {Element} [node] A valid Element node or compo
   */
  constructor(ns, name, node) {
    const ns0 = this.ns = `_${ns}`;
    node = (Compo.isCompo(node) ? node[ns] : node) || document;
    this.__Event = 0;
    this[ns0] = { name, node };
  }
  /**
   * Adds an event for this compo
   *
   * @see Element.addEventListener
   *
   * @param {function} func The function handler
   * @param {mixed} [options] An options Object or useCapture boolean
   */
  add(func, options = false) {
    const { node, name } = this[this.ns];
    node.addEventListener(name, func, options);
  }
  /**
   * Removes an event from this compo
   *
   * @see Element.removeEventListener
   *
   * @param {function} func The function handler
   */
  remove(func) {
    const { node, name } = this[this.ns];
    node.removeEventListener(name, func);
  }
  /**
   * Prevents the default event action for Event
   *
   * @see Event.preventDefault
   *
   * @static
   * @param {Event} event An Event
   */
  static prevent(event) {
    event.preventDefault();
  }
  /**
   * Performs focus on event target
   *
   * @see HTMLElement.focus
   *
   * @static
   * @param {Event} event An Event
   * @param {object} [options] Options for focus
   */
  static focus(event, options) {
    const { currentTarget } = event;
    currentTarget.focus && currentTarget.focus(options);
  }
  /**
   * Performs blur on event target
   *
   * @see HTMLElement.blur
   *
   * @static
   * @param {Event} event An Event
   * @param {number} [delay=100] Delay time in milliseconds
   */
  static blur(event, delay = 100) {
    const { currentTarget } = event;
    setTimeout(() => currentTarget.blur && currentTarget.blur(), delay);
  }
  /**
   * Checks passed object is an ensemble Event instance
   *
   * @static
   * @returns {boolean}
   */
  static isEvent(obj) {
    return obj instanceof Event;
  }
}
class base {
  /**
   * Constructor method
   *
   * @constructs
   * @param {Element} [element] An optional valid Element node
   * @param {object} [options] Options object
   */
  constructor() {
    const args = arguments;
    let element, options;
    if (args.length > 1) {
      element = args[0];
      options = args[1];
    } else if (args[0] && typeof args[0] == "object" && args[0].nodeType) {
      element = args[0];
    } else {
      options = args[0];
    }
    if (options && typeof options != "object") {
      throw new TypeError(l10n.EOPTS);
    }
    if (element && typeof element != "object") {
      throw new TypeError(l10n.EELEM);
    }
    this.binds();
    this.options = this.opts(this.defaults(), options);
    Object.freeze(this.options);
    this.element = element;
  }
  /**
   * Creates an options Object from a defaults object
   * 
   * Note: Supports only first level depth.
   *
   * @param {object} defaults The default options Object
   * @param {object} [options] An options Object to extend defaults
   * @returns {object}
   */
  opts(defaults, options = {}) {
    const opts = {};
    for (const key in defaults) {
      if (defaults[key] != null && typeof defaults[key] == "object") {
        opts[key] = Object.assign(defaults[key], options[key]);
      } else {
        opts[key] = typeof options[key] != "undefined" ? options[key] : defaults[key];
      }
    }
    return opts;
  }
  /**
   * Shorthand method for ensemble Compo class
   *
   * When passed the first argument, makes a new Compo instance 
   * otherwise returns a reference to the Compo class.
   *
   * @param {string} [tag='div'] The Element node name or compo name, empty for Compo class reference
   * @param {string[]} [name] The compo name, used for CSS className
   * @param {object} [props] Properties for compo
   * @returns {mixed} Instance of Compo or Compo class reference
   */
  compo(tag, name, props) {
    const ns = this.options.ns;
    return tag != void 0 ? new Compo(ns, tag, name, props) : Compo;
  }
  /**
   * Shorthand method for ensemble Data class
   *
   * When passed the first argument, makes a new Data instance 
   * otherwise returns a reference to the Data class.
   *
   * @param {object} [obj] A starter Object, empty for Data class reference
   * @returns {mixed} Instance of Data or Data class reference
   */
  data(obj) {
    const ns = this.options.ns;
    return obj != void 0 ? new Data(ns, obj) : Data;
  }
  /**
   * Shorthand method for ensemble Event class
   *
   * When the passed first argument is a string makes a new Event instance 
   * otherwise it returns a reference to the Event class.
   *
   * @param {string} [name] A valid Event name
   * @param {Element} [node] An Element node
   * @returns {mixed} Instance of Event or Event class reference
   */
  event(name, node) {
    const ns = this.options.ns;
    return name != void 0 ? new Event(ns, name, node) : Event;
  }
  /**
   * Shorthand for querySelectorAll and querySelector [DOM]
   *
   * @see Document.querySelectorAll
   * @see Document.querySelector
   *
   * @param {string} query Text query
   * @param {Element} [node] An Element node where to find
   * @param {boolean} [all] Find multiple elements
   * @return {mixed} Element or ElementCollection
   */
  selector(query, node, all = false) {
    node = node || document;
    return all ? node.querySelectorAll(query) : node.querySelector(query);
  }
  /**
   * Shorthand for Element.cloneNode [DOM]
   *
   * @see Node.cloneNode
   *
   * @param {Element} node An Element node to clone
   * @param {boolean} [deep] Clone inner nodes
   * @returns {boolean}
   */
  cloneNode(node, deep = false) {
    return node.cloneNode(deep);
  }
  /**
   * Shorthand for Element.hasAttribute [DOM]
   *
   * @see Element.hasAttribute
   *
   * @param {Element} node An Element node
   * @param {string} attr An attribute
   * @returns {boolean}
   */
  hasAttr(node, attr) {
    return node.hasAttribute(attr);
  }
  /**
   * Shorthand for Element.getAttribute [DOM]
   *
   * @see Element.getAttribute
   *
   * @param {Element} node An Element node
   * @param {string} attr An attribute
   * @returns {string}
   */
  getAttr(node, attr) {
    return node.getAttribute(attr);
  }
  /**
   * Util to create an icon
   *
   * @param {string} type Icons type: font, svg, symbol, shape
   * @param {string} name Icon name, CSS class name
   * @param {string} prefix Icon prefix, CSS class name
   * @param {string} [path] Icon SVG path or SVG image src
   * @param {string} [hash] Icon SVG symbol href or SVG image src hash
   * @param {string} [viewBox] Icon SVG viewBox size
   */
  icon(type, name, prefix, path, hash, viewBox) {
    const ns = this.options.ns;
    const className = prefix ? `${prefix}-${name}` : name;
    const icon = this.compo("span", "icon", { className });
    if (type != "font") {
      if (type == "symbol" || type == "shape") {
        const svgNsUri = "http://www.w3.org/2000/svg";
        const svg = new Compo(ns, "svg", false, false, null, svgNsUri);
        const tag = type == "symbol" ? "use" : "path";
        const node = new Compo(ns, tag, false, false, null, svgNsUri);
        if (viewBox) {
          const m = viewBox.match(/\d+ \d+ (\d+) (\d+)/);
          if (m) {
            Object.entries({
              width: m[1],
              height: m[2],
              viewBox: m[0]
            }).forEach((a) => svg.setAttr(a[0], a[1]));
          }
        }
        if (tag == "use") {
          node.setAttr("href", `#${hash}`);
        } else {
          node.setAttr("d", path);
        }
        svg.append(node);
        icon.append(svg);
      } else if (type == "svg" && this.origin()) {
        const img = this.compo(ns, "img", false, {
          "src": `${path}#${hash}`
        });
        icon.append(img);
      }
    }
    return icon;
  }
  /**
   * URL origin comparator
   *
   * @see URL
   * @see Window.origin
   * @see Window.location
   *
   * @param {URL} b URL
   * @param {URL} a URL
   * @returns {boolean} Check is same origin
   */
  origin(b, a) {
    a = URL.canParse(a) ? a : window.origin != "null" ? window.origin : window.location.origin;
    b = URL.canParse(b) ? new URL(b).origin : a;
    return a && b && a === b;
  }
  /**
   * Gets the time from a style property of an element
   *
   * @see Window.getComputedStyle
   *
   * @param {mixed} node An Element node or a compo
   * @param {string} prop A style property
   * @returns {number} time Delay time in milliseconds
   */
  cst(node, prop) {
    let time = Compo.isCompo(node) ? node.getStyle(prop) : getComputedStyle(node)[prop];
    if (time) {
      time = time.indexOf("s") ? parseFloat(time) * 1e3 : parseInt(time);
    }
    return time || 0;
  }
  /**
   * Provides a delay with callback function
   *
   * @see Window.setTimeout
   *
   * @param {function} func A callback function
   * @param {mixed} [node] An Element node or a compo
   * @param {number} [time] Default delay time in milliseconds
   */
  delay(func, node, time) {
    const delay = node ? this.cst(node, "transitionDuration") : 0;
    setTimeout(func, delay || time);
  }
  /**
   * Creates a proxy function to the instance
   *
   * @param {function} method A method from the current instance
   * @returns {function}
   */
  wrap(method) {
    const self = this;
    if (this[method] && typeof method != "function") {
      throw new TypeError(l10n.EMETH);
    }
    return function() {
      method.call(self, ...arguments, this);
    };
  }
}
const SocialShareActionEnum = Object.freeze({
  share: 0,
  send: 1,
  email: 2,
  copy: 3,
  webapi: 4
});
class SocialShare extends base {
  /**
   * Shorthand for sharing action enum
   *
   * @static
   * @returns {SocialShareActionEnum}
   */
  static actionEnum() {
    return SocialShareActionEnum;
  }
  /**
   * Default scaffold sharing intents
   *
   * @constant {object} SocialShareActionEnum Sharing action enum
   * @returns {object}
   */
  aks() {
    const i = SocialShareActionEnum;
    return {
      "facebook": i.share,
      "x": i.share,
      "linkedin": i.share,
      "threads": i.share,
      "bluesky": i.share,
      "reddit": i.share,
      "mastodon": i.share,
      "quora": i.share,
      "whatsapp": i.send,
      "messenger": i.send,
      "telegram": i.send,
      "skype": i.send,
      "viber": i.send,
      "line": i.send,
      "snapchat": i.send,
      "send-email": i.email,
      "copy-link": i.copy,
      "web-share": i.webapi
    };
  }
  /**
   * Default properties
   *
   * @returns {object}
   */
  defaults() {
    this.ska = this.aks();
    return {
      ns: "share",
      root: "body",
      className: "social-share",
      layout: "h",
      icons: {
        type: "font",
        prefix: "icon"
      },
      effects: true,
      link: "",
      title: "",
      description: "",
      intents: null,
      scaffold: this.ska,
      uriform: {
        "facebook": "https://www.facebook.com/dialog/share?display=popup&href=%url%&app_id=%app_id%",
        "x": "https://twitter.com/intent/tweet?text=%title%&url=%url%",
        "linkedin": "https://www.linkedin.com/sharing/share-offsite?mini=true&url=%url%&title=%title%&ro=false&summary=%summary%",
        "threads": "https://threads.net/intent/post?text=%url%",
        "bluesky": "https://bsky.app/intent/compose?text=%url%",
        "reddit": "https://www.reddit.com/submit?url=%url%&title=%title%",
        "mastodon": "https://mastodon.social/share?text=%text%",
        "quora": "https://www.quora.com/share?url=%url%&title=%title%",
        "whatsapp": "https://api.whatsapp.com/send?text=%text%",
        "messenger": "fb-messenger://share/?link=%url%&app_id=%app_id%",
        "telegram": "https://telegram.me/share/url?url=%url%&text=%text%",
        "skype": "https://web.skype.com/share?url=%url%&text=%title%",
        "viber": "viber://forward?text=%text%",
        "line": "https://line.me/R/msg/text/?%text%",
        "snapchat": "https://www.snapchat.com/share?link=%url%",
        "send-email": "mailto:?subject=%subject%&body=%text%"
      },
      selectorLink: {
        element: 'link[rel="canonical"]',
        attribute: "href"
      },
      selectorTitle: null,
      selectorDescription: {
        element: 'meta[name="description"]',
        attribute: "content"
      },
      label: {
        className: "sr-only"
      },
      ariaLabel: true,
      copiedEffectDelay: 1e3,
      locale: {
        label: "Share",
        share: "Share on %s",
        send: "Send to %s",
        subject: "An interesting thing",
        text: "Hi! Here something may interesting you: %s",
        email: "Send via email",
        copy: "Copy link",
        copied: "Copied link!",
        "whatsapp": "WhatsApp",
        "linkedin": "LinkedIn",
        "web-share": "Share"
      },
      onIntent: () => {
      },
      onInit: () => {
      }
    };
  }
  /**
   * Methods binding
   */
  binds() {
    this.intent = this.wrap(this.intent);
  }
  /**
   * Constructor
   *
   * @constructs
   */
  constructor() {
    super(...arguments);
    this.encoder = encodeURIComponent;
    this.init();
  }
  /**
   * Initializes the component
   *
   * @emits #options.onInit
   */
  init() {
    if (this.built)
      return;
    const opts = this.options;
    this.root = this.selector(opts.root);
    let intents = [];
    if (!opts.intents && this.ska === opts.scaffold) {
      const a = Object.keys(this.ska);
      for (const i of [0, 1, 8, 9, 10, 2, 15, 16, 17]) {
        intents.push(a[i]);
      }
    } else if (opts.intents instanceof Array) {
      intents = opts.intents;
    } else if (opts.intents) {
      intents = Object.keys(opts.scaffold);
    }
    this.intents = intents;
    this.layout();
    if (this.element) {
      this.$.place(this.element, (function(node) {
        this.element = node;
      }).bind(this));
    }
    this.drawer();
    opts.onInit.call(this, this);
  }
  /**
   * Lead layout
   */
  layout() {
    const opts = this.options;
    const locale = opts.locale;
    const compo = this.$ = this.compo(false, false, {
      className: typeof opts.className == "object" ? Object.values(opts.className).join(" ") : opts.className
    });
    if (opts.ariaLabel) {
      const ariaLabel = opts.ariaLabel;
      compo.setAttr("aria-label", typeof ariaLabel == "string" ? ariaLabel : locale.label);
    }
    if (opts.label) {
      const labelParams = opts.label;
      const label = this.compo("span", "label", {
        className: labelParams.className,
        innerText: labelParams.text ?? locale.label
      });
      compo.append(label);
    }
    if (opts.layout == "v") {
      compo.classList.add(`${opts.ns}-vertical`);
    }
    const actions = this.actions = this.compo("ul", "actions");
    compo.append(actions);
    this.built = true;
  }
  /**
   * Places all the actions in a drawer
   *
   * @see Navigator.share
   */
  drawer() {
    const act = SocialShareActionEnum;
    const opts = this.options;
    const locale = opts.locale;
    for (const intent of this.intents) {
      if (!intent in opts.scaffold)
        continue;
      const name = intent in locale ? locale[intent].toString() : intent.replace(/\w/, (cap) => cap.toUpperCase());
      let title;
      switch (opts.scaffold[intent]) {
        case act.share:
          title = locale.share.toString().replace("%s", name);
          break;
        case act.send:
          title = locale.send.toString().replace("%s", name);
          break;
        case act.email:
          title = locale.email.toString();
          break;
        case act.copy:
          title = locale.copy.toString();
          break;
        case act.webapi:
          if (!navigator.share)
            continue;
          title = locale["web-share"].toString();
          break;
      }
      this.action(intent, title);
    }
  }
  /**
   * Creates the action with a share button
   *
   * @param {string} intent Intent name
   * @param {string} title Title for action
   */
  action(intent, title) {
    const opts = this.options;
    const action = this.compo("li", "action", {
      className: `${opts.ns}-action-${intent}`
    });
    const button = this.compo("button", ["button", "intent"], {
      className: `${opts.ns}-intent-${intent}`,
      title,
      onclick: this.intent
    });
    action.setAttr("data-share-intent", intent);
    action.append(button);
    {
      const { type, prefix, src, viewBox } = opts.icons;
      const icon = this.icon(type, intent, prefix, src, intent, viewBox);
      button.append(icon);
    }
    this.actions.append(action);
  }
  /**
   * Selects a sharing intent
   *
   * This method is called from each action.
   *
   * @see Window.location
   *
   * @emits #options.onIntent
   *
   * @param {Event} evt An Event
   * @param {Element} target The element is invoking
   */
  intent(evt, target) {
    this.event().prevent(evt);
    if (!evt.isTrusted) return;
    const act = SocialShareActionEnum;
    const opts = this.options;
    const locale = opts.locale;
    if (!this.compo().isCompo(target))
      return;
    const action = target.parent;
    if (!(action && action.hasAttr("data-share-intent")))
      return;
    const intent = action.getAttr("data-share-intent");
    if (this.intents.indexOf(intent) == -1)
      return;
    let url, title, summary, text;
    const { selectorLink, selectorTitle, selectorDescription } = opts;
    if (opts.link) {
      url = new URL(opts.link).toString();
    } else if (selectorLink && selectorLink.element && this.selector(selectorLink.element)) {
      url = this.getAttr(this.selector(selectorLink.element), selectorLink.attribute);
    } else {
      url = window.location.href;
    }
    if (opts.title) {
      title = opts.title;
    } else if (selectorTitle && selectorTitle.element && this.selector(selectorTitle.element)) {
      title = this.getAttr(this.selector(selectorTitle.element), selectorTitle.attribute);
    } else {
      title = document.title;
    }
    if (opts.description) {
      summary = opts.description;
    } else if (selectorDescription && selectorDescription.element && this.selector(selectorDescription.element)) {
      summary = this.getAttr(this.selector(selectorDescription.element), selectorDescription.attribute);
    } else {
      summary = title;
    }
    text = "\n\n%title%\n%url%\n\n";
    const data = { url, title, text, summary };
    opts.onIntent.call(this, this, evt, intent, data);
    data.text = locale.text.toString().replace("%s", data.text);
    if (intent in opts.scaffold) {
      switch (opts.scaffold[intent]) {
        case act.email:
          this.sendEmail(evt, data);
          break;
        case act.copy:
          this.copyLink(evt, data);
          break;
        case act.webapi:
          this.webShare(evt, data);
          break;
        default:
          this.share(evt, data, intent);
      }
    }
    this.event().blur(evt);
  }
  /**
   * Text substitutions and URL encodes
   *
   * @param {object} data Sharing data object
   * @param {string} data.url Share URL
   * @param {string} data.title Share title text
   * @param {string} data.text Share description text
   * @param {string} data.summary Share summary text
   * @return {string} URL encoded text string
   */
  text(data) {
    return this.encoder(
      data.text.replace("%url%", data.url).replace("%title%", data.title).replace("%summary%", data.summary)
    );
  }
  /**
   * Share intent for social sharing action
   *
   * @see Window.open
   *
   * @param {Event} evt An Event
   * @param {object} data Sharing data object
   * @param {string} data.url Share URL
   * @param {string} data.title Share title text
   * @param {string} data.text Share description text
   * @param {string} data.summary Share summary text
   * @param {string} intent Intent name
   */
  share(evt, data, intent) {
    const { options: opts, encoder } = this;
    if (!intent in opts.uriform) return;
    let url = opts.uriform[intent].replace("%url%", encoder(data.url)).replace("%title%", encoder(data.title)).replace("%summary%", encoder(data.summary));
    const features = "toolbar=0,status=0,width=640,height=480";
    if (/%text%/.test(opts.uriform[intent])) {
      url = url.replace("%text%", this.text(data));
    }
    if (intent == "facebook" || intent == "messenger") {
      const app_id = opts[`${intent}_app_id`] ?? "";
      url = url.replace("%app_id%", encoder(app_id));
    }
    window.open(url, null, features);
  }
  /**
   * Send email intent
   *
   * Tries to open the default e-mail client.
   *
   * @see Window.open
   *
   * @param {Event} evt An Event
   * @param {object} data Sharing data object
   * @param {string} data.url Share URL
   * @param {string} data.title Share title text
   * @param {string} data.text Share description text
   * @param {string} data.summary Share summary text
   */
  sendEmail(evt, data) {
    const opts = this.options;
    const locale = opts.locale;
    const url = opts.uriform["send-email"].replace("%subject%", this.encoder(locale.subject)).replace("%text%", this.text(data));
    window.open(url, "_self");
  }
  /**
   * Copy link intent
   *
   * Tries to copy the link to the clipboard.
   *
   * @see Navigator.clipboard
   * @see Document.execCommand
   *
   * @param {Event} evt An Event
   * @param {object} data Sharing data object
   * @param {string} data.url Share URL
   * @param {string} data.title Share title text
   * @param {string} data.text Share description text
   * @param {string} data.summary Share summary text
   */
  copyLink(evt, data) {
    if (!this.element) return;
    const opts = this.options;
    const locale = opts.locale;
    try {
      navigator.clipboard.writeText(data.url);
    } catch (err) {
      if (err instanceof TypeError) {
        const doc = document;
        const node = doc.createElement("textarea");
        node.style = "position:absolute;width:0;height:0;opacity:0;z-index:-1;overflow:hidden";
        node.value = data.url;
        doc.append(node);
        node.focus();
        node.select();
        doc.execCommand("copy");
        node.remove();
      } else {
        console.error("copyLink", err.message);
      }
    }
    if (opts.effects) {
      const { root } = this;
      const bg = this.compo(false, "effects-copied-link--bg", { hidden: true });
      const msg = this.compo("span", "copied-link-msg", { innerText: locale.copied });
      root.classList.add("share-effects-copied-link");
      bg.bind(root);
      msg.bind(root);
      bg.show();
      this.delay(() => {
        msg.unbind(root);
        bg.unbind(root);
        root.classList.remove("share-effects-copied-link");
      }, bg, parseInt(opts.copiedEffectDelay) || 0);
    }
  }
  /**
   * Share from device caller
   *
   * Calls the native WebShare API
   *
   * @see Navigator.share
   *
   * @async
   * @param {Event} evt An Event
   * @param {object} data Sharing data object
   */
  async webShare(evt, data) {
    try {
      await navigator.share({ title: data.title, url: data.url });
    } catch (err) {
      console.error("webShare", err.message);
    }
  }
}
const SELECTOR = "[data-fx-share]";
const instanceStore = createWeakStore();
const DEFAULT_OPTIONS = {
  layout: "h",
  intents: [
    "facebook",
    "x",
    "linkedin",
    "threads",
    "bluesky",
    "reddit",
    "mastodon",
    "quora",
    "whatsapp",
    "messenger",
    "telegram",
    "skype",
    "viber",
    "line",
    "snapchat",
    "send-email",
    "copy-link",
    "web-share",
    "print"
  ],
  onIntent: (self, event, intent, data) => {
    if (intent === "facebook") {
      event.preventDefault();
      const url = encodeURIComponent(data.url || window.location.href);
      const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
      window.open(shareUrl, "_blank", "width=600,height=400,scrollbars=yes");
      return;
    }
    if (intent === "print") {
      setTimeout(window.print, 200);
    }
    Events.emit("fx:share:intent", { intent, data });
  }
};
const fixPrintButtonTitle = (container) => {
  const tryFix = () => {
    const button = container.querySelector(".share-intent-print");
    if (button && (!button.title || button.title === "undefined")) {
      button.setAttribute("title", "Print");
      return true;
    }
    return false;
  };
  if (tryFix()) return;
  const observer = new MutationObserver(() => {
    if (tryFix()) {
      observer.disconnect();
    }
  });
  observer.observe(container, { childList: true, subtree: true });
  setTimeout(() => observer.disconnect(), 3e3);
};
const FxShare = {
  /**
   * Initialize all share components in root
   * @param {Document|Element} root - Root element to search
   */
  initAll(root = document) {
    $$(SELECTOR, root).forEach(
      /** @param {HTMLElement} container */
      (container) => {
        if (instanceStore.has(container)) return;
        const layout = container.dataset.layout || DEFAULT_OPTIONS.layout;
        const intentsAttr = container.dataset.intents;
        const intents = intentsAttr ? intentsAttr.split(",").map((s) => s.trim()) : DEFAULT_OPTIONS.intents;
        const options = {
          ...DEFAULT_OPTIONS,
          layout,
          intents
        };
        const instance = new SocialShare(container, options);
        instanceStore.set(container, instance);
        if (intents.includes("print")) {
          fixPrintButtonTitle(container);
        }
        Events.emit("fx:share:init", { container, instance });
      }
    );
  },
  /**
   * Destroy all share components in root
   * @param {Document|Element} root - Root element to search
   */
  destroyAll(root = document) {
    $$(SELECTOR, root).forEach(
      /** @param {HTMLElement} container */
      (container) => {
        instanceStore.cleanup(container, (instance) => {
          container.innerHTML = "";
        });
      }
    );
  }
};
export {
  FxShare as default
};
//# sourceMappingURL=fx-share.D8dRNZ51.js.map
