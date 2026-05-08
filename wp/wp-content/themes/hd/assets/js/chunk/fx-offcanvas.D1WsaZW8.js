import { o as off, a as on, c as createWeakStore, E as Events, t as trigger, $ as $$, b as $, e as closest } from "../vendor.We5pwaIa.js";
const CLASS = "js-off-canvas-overlay";
const LOCK = "is-off-canvas-open";
let overlay = null;
const getOverlay = () => {
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = `${CLASS} is-overlay-fixed`;
    document.body.appendChild(overlay);
  }
  return overlay;
};
const lockScroll = () => {
  document.body.classList.add(LOCK);
};
const unlockScroll = () => {
  document.body.classList.remove(LOCK);
};
const THRESHOLD = 80;
const swipes = createWeakStore();
const bindSwipe = (panel, overlay2, onClose) => {
  if (swipes.has(panel)) return;
  let startX = 0;
  let startY = 0;
  let currX = 0;
  let currY = 0;
  let dragging = false;
  const right = panel.classList.contains("position-right");
  const tStart = (e) => {
    if (!panel.classList.contains("is-open")) return;
    dragging = true;
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    currX = startX;
    currY = startY;
    panel.style.transition = "none";
  };
  const tMove = (e) => {
    if (!dragging) return;
    currX = e.touches[0].clientX;
    currY = e.touches[0].clientY;
    const dx = currX - startX;
    const dy = currY - startY;
    if (Math.abs(dx) > Math.abs(dy)) {
      if (right && dx > 0 || !right && dx < 0) {
        const w = panel.offsetWidth || 320;
        const d = Math.min(Math.abs(dx), w);
        panel.style.transform = `translate(${right ? d : -d}px)`;
        overlay2.style.opacity = 1 - d / w;
      }
    }
  };
  const tEnd = () => {
    if (!dragging) return;
    dragging = false;
    panel.style.transition = "";
    panel.style.transform = "";
    overlay2.style.opacity = "";
    const dx = Math.abs(currX - startX);
    const dy = Math.abs(currY - startY);
    if (dx > THRESHOLD && dx > dy) {
      const isSwipeOut = right && currX > startX || !right && currX < startX;
      if (isSwipeOut) onClose();
    }
  };
  swipes.set(panel, { tStart, tMove, tEnd });
  on(panel, "touchstart", tStart, { passive: true });
  on(document, "touchmove", tMove, { passive: true });
  on(document, "touchend", tEnd);
};
const unbindSwipe = (panel) => {
  const h = swipes.get(panel);
  if (!h) return;
  off(panel, "touchstart", h.tStart);
  off(document, "touchmove", h.tMove);
  off(document, "touchend", h.tEnd);
  swipes.delete(panel);
};
const isOpen = (panel) => panel.classList.contains("is-open");
const openOffCanvas = (panel, overlay2) => {
  if (isOpen(panel)) return;
  panel.classList.add("is-open");
  panel.classList.remove("is-closed");
  overlay2.classList.add("is-visible", "is-closable");
  if (panel.dataset.contentScroll === "false") lockScroll();
  bindSwipe(panel, overlay2, () => closeOffCanvas(panel, overlay2));
  Events.emit("fx:offcanvas:open", { el: panel });
  trigger(panel, "fx.offcanvas.opened", { el: panel });
};
const closeOffCanvas = (panel, overlay2) => {
  if (!isOpen(panel)) return;
  panel.classList.remove("is-open");
  panel.classList.add("is-closed");
  overlay2.classList.remove("is-visible");
  unlockScroll();
  unbindSwipe(panel);
  Events.emit("fx:offcanvas:close", { el: panel });
  trigger(panel, "fx.offcanvas.closed", { el: panel });
};
const OC = "data-fx-off-canvas";
const OPEN = "data-open";
const CLOSE = "data-close";
const openHandlers = createWeakStore();
const closeHandlers = createWeakStore();
let overlayHandler = null;
const FxOffCanvas = {
  initAll(root = document) {
    const overlay2 = getOverlay();
    $$(`[${OPEN}]`, root).forEach((btn) => {
      const id = btn.getAttribute(OPEN);
      const panel = document.getElementById(id);
      if (!panel || !panel.hasAttribute(OC)) return;
      const h = (e) => {
        e.preventDefault();
        openOffCanvas(panel, overlay2);
        btn.setAttribute("aria-expanded", "true");
      };
      openHandlers.set(btn, h);
      on(btn, "click", h);
    });
    $$(`[${CLOSE}]`, root).forEach((btn) => {
      const sel = btn.getAttribute(CLOSE);
      const panel = sel ? $(sel) : closest(btn, `[${OC}]`);
      if (!panel) return;
      const h = (e) => {
        e.preventDefault();
        closeOffCanvas(panel, overlay2);
      };
      closeHandlers.set(btn, h);
      on(btn, "click", h);
    });
    if (!overlayHandler) {
      overlayHandler = () => {
        $$(`[${OC}]`).forEach((p) => closeOffCanvas(p, overlay2));
      };
      on(overlay2, "click", overlayHandler);
    }
  },
  destroyAll(root = document) {
    $$(`[${OPEN}]`, root).forEach((btn) => {
      const h = openHandlers.get(btn);
      if (h) {
        off(btn, "click", h);
        openHandlers.delete(btn);
      }
    });
    $$(`[${CLOSE}]`, root).forEach((btn) => {
      const h = closeHandlers.get(btn);
      if (h) {
        off(btn, "click", h);
        closeHandlers.delete(btn);
      }
    });
    if (root === document && overlayHandler) {
      off(getOverlay(), "click", overlayHandler);
      overlayHandler = null;
    }
  }
};
export {
  FxOffCanvas as default
};
//# sourceMappingURL=fx-offcanvas.D1WsaZW8.js.map
