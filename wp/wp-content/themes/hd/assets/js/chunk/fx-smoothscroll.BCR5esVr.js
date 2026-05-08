import { $ as $$, o as off, E as Events, a as on, c as createWeakStore } from "../vendor.We5pwaIa.js";
const SELECTOR = "[data-fx-scroll]";
const handlers = createWeakStore();
const FxSmoothScroll = {
  activeAnimation: null,
  smoothScrollTo(targetY, { offset = 0, onStart, onUpdate, onEnd } = {}) {
    if (this.activeAnimation) {
      cancelAnimationFrame(this.activeAnimation);
      this.activeAnimation = null;
    }
    const startY = window.scrollY;
    let currentY = startY;
    let velocity = 0;
    const maxSpeed = 50;
    const minSpeed = 0.4;
    const decelFactor = 0.12;
    const nearFactor = 0.18;
    const NEAR_DISTANCE = 30;
    const finalTarget = targetY - offset;
    onStart == null ? void 0 : onStart({ startY, targetY: finalTarget });
    const animate = () => {
      const dist = finalTarget - currentY;
      const abs = Math.abs(dist);
      if (abs < 0.8) {
        window.scrollTo(0, finalTarget);
        this.activeAnimation = null;
        onEnd == null ? void 0 : onEnd({ finalY: finalTarget });
        return;
      }
      velocity = dist * decelFactor;
      if (abs < NEAR_DISTANCE) velocity *= nearFactor;
      velocity = Math.max(-maxSpeed, Math.min(maxSpeed, velocity));
      if (velocity > 0 && velocity < minSpeed) velocity = minSpeed;
      if (velocity < 0 && velocity > -minSpeed) velocity = -minSpeed;
      currentY += velocity;
      window.scrollTo(0, currentY);
      onUpdate == null ? void 0 : onUpdate({ y: currentY, velocity, dist });
      this.activeAnimation = requestAnimationFrame(animate);
    };
    this.activeAnimation = requestAnimationFrame(animate);
  },
  /**
   * Extract target ID from href
   * Supports: #abc, ?section=abc, &section=abc
   * @param {string} href
   * @returns {string|null}
   */
  getTargetId(href) {
    if (!href) return null;
    if (href.startsWith("#")) {
      return href.slice(1) || null;
    }
    try {
      const url = new URL(href, window.location.origin);
      const section = url.searchParams.get("section");
      if (section) return section;
    } catch {
      const match = href.match(/[?&]section=([^&]+)/);
      if (match) return match[1];
    }
    return null;
  },
  initAll(root = document) {
    $$(SELECTOR, root).forEach((a) => {
      const handler = (e) => {
        var _a;
        const href = a.getAttribute("href");
        const targetId = this.getTargetId(href);
        if (!targetId) return;
        e.preventDefault();
        const target = document.getElementById(targetId);
        if (!target) return;
        const offset = parseInt(a.dataset.fxOffset ?? ((_a = a.closest("[data-fx-offset]")) == null ? void 0 : _a.dataset.fxOffset) ?? document.body.dataset.fxOffset ?? 0, 10);
        const targetY = target.getBoundingClientRect().top + window.scrollY;
        FxSmoothScroll.smoothScrollTo(targetY, {
          offset,
          onStart: () => Events.emit("fx:smoothscroll:start", { link: a, target }),
          onEnd: () => {
            target.setAttribute("tabindex", "-1");
            target.focus({ preventScroll: true });
            Events.emit("fx:smoothscroll:goto", { link: a, target });
          }
        });
      };
      handlers.set(a, handler);
      on(a, "click", handler);
    });
    this.scrollToUrlTarget();
  },
  /**
   * Scroll to target section based on current URL
   * Checks for: #abc, ?section=abc, &section=abc
   * Called automatically on page load/init
   */
  scrollToUrlTarget() {
    const url = window.location;
    const section = new URLSearchParams(url.search).get("section");
    if (!section) return;
    const target = document.getElementById(section);
    if (!target) return;
    const offset = parseInt(document.body.dataset.fxOffset ?? 0, 10);
    const targetY = target.getBoundingClientRect().top + window.scrollY;
    requestAnimationFrame(() => {
      FxSmoothScroll.smoothScrollTo(targetY, {
        offset,
        onStart: () => Events.emit("fx:smoothscroll:start", { link: null, target }),
        onEnd: () => Events.emit("fx:smoothscroll:goto", { link: null, target })
      });
    });
  },
  destroyAll(root = document) {
    if (this.activeAnimation) {
      cancelAnimationFrame(this.activeAnimation);
      this.activeAnimation = null;
    }
    $$(SELECTOR, root).forEach((a) => {
      const handler = handlers.get(a);
      if (handler) {
        off(a, "click", handler);
        handlers.delete(a);
      }
    });
  }
};
export {
  FxSmoothScroll as default
};
//# sourceMappingURL=fx-smoothscroll.BCR5esVr.js.map
