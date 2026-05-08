import { $ as $$, E as Events, c as createWeakStore } from "../vendor.We5pwaIa.js";
const SELECTOR = "[data-fx-masonry]";
const instanceStore = createWeakStore();
const observerStore = createWeakStore();
const debounce = (fn, ms) => {
  let t;
  return (...a) => (clearTimeout(t), t = setTimeout(() => fn(...a), ms));
};
const parseJSON = (str, fallback) => {
  try {
    return JSON.parse(str);
  } catch {
    return fallback;
  }
};
const parseOptions = (el) => {
  const d = el.dataset;
  let gutter = { default: 12, md: 24 };
  if (d.fxMasonryGutter) {
    const parsed = parseJSON(d.fxMasonryGutter, null);
    if (typeof parsed === "number") gutter = { default: parsed, md: parsed };
    else if (parsed) gutter = { ...gutter, ...parsed };
    else {
      const val = +d.fxMasonryGutter;
      if (!isNaN(val)) gutter = { default: val, md: val };
    }
  }
  const breakpoints = d.fxMasonryBreakpoints ? parseJSON(d.fxMasonryBreakpoints, { 0: 2, 768: 3, 1024: 4 }) : { 0: 2, 768: 3, 1024: 4 };
  return {
    gutter,
    breakpoints,
    itemSelector: d.fxMasonryItem || ".masonry-item",
    animate: d.fxMasonryAnimate !== "false",
    resizeDebounce: +d.fxMasonryDebounce || 100
  };
};
class FxMasonry {
  constructor(el, opts = {}) {
    this.el = el;
    this.opts = {
      gutter: { default: 12, md: 24 },
      breakpoints: { 0: 2, 768: 3, 1024: 4 },
      itemSelector: ".masonry-item",
      animate: true,
      resizeDebounce: 100,
      ...opts
    };
    this.heights = [];
    this.items = [];
    this._resizeHandler = null;
    this._init();
  }
  _init() {
    this.el.classList.add("fx-masonry");
    this.el.style.position = "relative";
    this.items = [...this.el.querySelectorAll(this.opts.itemSelector)];
    this.layout();
    this._resizeHandler = debounce(() => {
      this.layout();
      Events.emit("fx:masonry:resize", { el: this.el, instance: this });
    }, this.opts.resizeDebounce);
    addEventListener("resize", this._resizeHandler, { passive: true });
    Events.emit("fx:masonry:init", { el: this.el, instance: this });
  }
  getCols() {
    const w = innerWidth;
    for (const [bp, c] of Object.entries(this.opts.breakpoints).sort((a, b) => +b[0] - +a[0])) {
      if (w >= +bp) return c;
    }
    return 2;
  }
  getGutter() {
    const w = innerWidth, g = this.opts.gutter;
    return g.lg && w >= 1024 && g.lg || g.md && w >= 768 && g.md || g.default || 12;
  }
  layout() {
    const cols = this.getCols(), gap = this.getGutter(), w = (this.el.offsetWidth - gap * (cols - 1)) / cols;
    this.heights = new Float32Array(cols);
    const items = this.items;
    const len = items.length;
    for (let i = 0; i < len; i++) {
      this._pos(items[i], w, gap, cols);
    }
    let maxHeight = this.heights[0];
    for (let i = 1; i < cols; i++) {
      if (this.heights[i] > maxHeight) maxHeight = this.heights[i];
    }
    this.el.style.height = `${maxHeight}px`;
    Events.emit("fx:masonry:layout", { el: this.el, instance: this, cols, gap, itemWidth: w });
  }
  _pos(item, w, gap, cols) {
    const heights = this.heights;
    let minIdx = 0;
    let minH = heights[0];
    for (let i = 1; i < cols; i++) {
      if (heights[i] < minH) {
        minH = heights[i];
        minIdx = i;
      }
    }
    const style = item.style;
    style.position = "absolute";
    style.width = `${w}px`;
    style.left = `${minIdx * (w + gap)}px`;
    style.top = `${minH}px`;
    heights[minIdx] = minH + item.offsetHeight + gap;
  }
  appendItems(newItems, animate = true) {
    const cols = this.getCols(), gap = this.getGutter(), w = (this.el.offsetWidth - gap * (cols - 1)) / cols, added = [], shouldAnimate = animate && this.opts.animate;
    newItems.forEach((item) => {
      if (shouldAnimate) Object.assign(item.style, { opacity: "0", transform: "translateY(20px)" });
      Object.assign(item.style, { position: "absolute", width: `${w}px`, visibility: "hidden" });
      this.el.appendChild(item);
      this._pos(item, w, gap);
      item.style.visibility = "";
      this.items.push(item);
      added.push(item);
    });
    const h = Math.max(...this.heights);
    if (shouldAnimate) {
      this.el.style.transition = "height 0.3s ease-out";
      this.el.style.height = `${h}px`;
      added.forEach(
        (item, i) => setTimeout(() => {
          Object.assign(item.style, { transition: "opacity 0.4s ease-out, transform 0.4s ease-out", opacity: "1", transform: "translateY(0)" });
          setTimeout(() => Object.assign(item.style, { transition: "", opacity: "", transform: "" }), 400);
        }, i * 60)
      );
      setTimeout(() => this.el.style.transition = "", 300);
    } else {
      this.el.style.height = `${h}px`;
    }
    Events.emit("fx:masonry:append", { el: this.el, instance: this, items: added, totalItems: this.items.length });
    return added;
  }
  removeItems(items, relayout = true) {
    items.forEach((item) => {
      const idx = this.items.indexOf(item);
      if (idx > -1) this.items.splice(idx, 1), item.remove();
    });
    relayout && this.layout();
    Events.emit("fx:masonry:remove", { el: this.el, instance: this, removedCount: items.length });
  }
  refresh() {
    this.items = [...this.el.querySelectorAll(this.opts.itemSelector)];
    this.layout();
    Events.emit("fx:masonry:refresh", { el: this.el, instance: this });
  }
  update(opts) {
    Object.assign(this.opts, opts);
    this.layout();
    Events.emit("fx:masonry:update", { el: this.el, instance: this });
  }
  destroy() {
    this._resizeHandler && removeEventListener("resize", this._resizeHandler);
    this.el.classList.remove("fx-masonry");
    Object.assign(this.el.style, { position: "", height: "" });
    this.items.forEach((item) => Object.assign(item.style, { position: "", width: "", left: "", top: "" }));
    Events.emit("fx:masonry:destroy", { el: this.el });
  }
  getState() {
    return { cols: this.getCols(), gutter: this.getGutter(), itemCount: this.items.length, height: this.el.offsetHeight };
  }
}
const initMasonry = (el) => {
  if (!el || instanceStore.has(el)) return instanceStore.get(el) || null;
  const instance = new FxMasonry(el, parseOptions(el));
  instanceStore.set(el, instance);
  return instance;
};
const FxMasonryAPI = {
  initAll(root = document) {
    $$(SELECTOR, root).forEach((el) => {
      if (instanceStore.has(el)) return;
      if ("IntersectionObserver" in window) {
        const obs = new IntersectionObserver(
          ([e], o) => {
            if (e.isIntersecting) {
              initMasonry(e.target);
              o.unobserve(e.target);
              observerStore.delete(e.target);
            }
          },
          { rootMargin: "100px" }
        );
        obs.observe(el);
        observerStore.set(el, obs);
      } else {
        initMasonry(el);
      }
    });
  },
  destroyAll(root = document) {
    $$(SELECTOR, root).forEach((el) => {
      observerStore.cleanup(el, (o) => o.disconnect());
      instanceStore.cleanup(el, (i) => i.destroy());
    });
    Events.emit("fx:masonry:destroyAll");
  },
  init: initMasonry,
  getInstance: (el) => instanceStore.get(el) || null,
  FxMasonry
};
export {
  FxMasonryAPI as default
};
//# sourceMappingURL=fx-masonry.C2ee7g4q.js.map
