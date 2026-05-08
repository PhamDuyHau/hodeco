import { $ as $$, E as Events, c as createWeakStore } from "../vendor.We5pwaIa.js";
const SELECTOR = "[data-fx-sticky]";
const instanceStore = createWeakStore();
const parseOptions = (el) => ({
  stickyClass: el.dataset.stickyClass || "is-sticky",
  offset: parseInt(el.dataset.stickyOffset, 10) || 0,
  // 'up' = show on scroll up, 'down' = show on scroll down, 'both' = always when past threshold
  direction: el.dataset.stickyDirection || "both"
});
const createStickyInstance = (el) => {
  const options = parseOptions(el);
  const { stickyClass, offset, direction } = options;
  let lastScrollY = window.scrollY;
  let isSticky = false;
  let sentinel = null;
  let observer = null;
  let scrollHandler = null;
  sentinel = document.createElement("div");
  Object.assign(sentinel.style, {
    display: "block",
    width: "1px",
    height: "0",
    marginTop: offset ? `-${offset}px` : "0",
    opacity: "0",
    visibility: "hidden",
    pointerEvents: "none"
    //position: 'absolute',
    //top: '0',
    //left: '0',
  });
  el.parentNode.insertBefore(sentinel, el);
  const updateSticky = (sticky, scrollDir = null) => {
    if (direction === "both") {
      if (sticky !== isSticky) {
        isSticky = sticky;
        el.classList.toggle(stickyClass, sticky);
        Events.emit("fx:sticky:change", { el, isSticky, direction: scrollDir });
      }
    } else if (direction === "up") {
      if (sticky && scrollDir === "up") {
        if (!isSticky) {
          isSticky = true;
          el.classList.add(stickyClass);
          Events.emit("fx:sticky:change", { el, isSticky, direction: scrollDir });
        }
      } else if (!sticky || scrollDir === "down") {
        if (isSticky) {
          isSticky = false;
          el.classList.remove(stickyClass);
          Events.emit("fx:sticky:change", { el, isSticky, direction: scrollDir });
        }
      }
    } else if (direction === "down") {
      if (sticky && scrollDir === "down") {
        if (!isSticky) {
          isSticky = true;
          el.classList.add(stickyClass);
          Events.emit("fx:sticky:change", { el, isSticky, direction: scrollDir });
        }
      } else if (!sticky || scrollDir === "up") {
        if (isSticky) {
          isSticky = false;
          el.classList.remove(stickyClass);
          Events.emit("fx:sticky:change", { el, isSticky, direction: scrollDir });
        }
      }
    }
  };
  observer = new IntersectionObserver(
    ([entry]) => {
      const scrollDir = window.scrollY > lastScrollY ? "down" : "up";
      updateSticky(!entry.isIntersecting, scrollDir);
      lastScrollY = window.scrollY;
    },
    {
      rootMargin: "0px",
      threshold: 0
    }
  );
  observer.observe(sentinel);
  if (direction !== "both") {
    let ticking = false;
    scrollHandler = () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          const currentScrollY = window.scrollY;
          const scrollDir = currentScrollY > lastScrollY ? "down" : "up";
          const rect = sentinel.getBoundingClientRect();
          const isPastThreshold = rect.top < 0;
          updateSticky(isPastThreshold, scrollDir);
          lastScrollY = currentScrollY;
          ticking = false;
        });
        ticking = true;
      }
    };
    window.addEventListener("scroll", scrollHandler, { passive: true });
  }
  const initialRect = el.getBoundingClientRect();
  if (initialRect.top <= offset) {
    el.classList.add(stickyClass);
    isSticky = true;
  }
  return {
    el,
    options,
    destroy() {
      if (observer) {
        observer.disconnect();
        observer = null;
      }
      if (sentinel) {
        sentinel.remove();
        sentinel = null;
      }
      if (scrollHandler) {
        window.removeEventListener("scroll", scrollHandler);
        scrollHandler = null;
      }
      el.classList.remove(stickyClass);
    }
  };
};
const FxSticky = {
  /**
   * Initialize all sticky elements in root
   * @param {Document|Element} root - Root element to search
   */
  initAll(root = document) {
    $$(SELECTOR, root).forEach(
      /** @param {HTMLElement} el */
      (el) => {
        if (instanceStore.has(el)) return;
        const instance = createStickyInstance(el);
        instanceStore.set(el, instance);
        Events.emit("fx:sticky:init", { el, instance });
      }
    );
  },
  /**
   * Destroy all sticky instances in root
   * @param {Document|Element} root - Root element to search
   */
  destroyAll(root = document) {
    $$(SELECTOR, root).forEach(
      /** @param {HTMLElement} el */
      (el) => {
        instanceStore.cleanup(el, (instance) => {
          instance.destroy();
        });
      }
    );
    Events.emit("fx:sticky:destroy");
  },
  /**
   * Manually init a specific element
   * @param {HTMLElement} el - Element to init
   * @returns {Object} - Instance
   */
  init(el) {
    if (instanceStore.has(el)) return instanceStore.get(el);
    const instance = createStickyInstance(el);
    instanceStore.set(el, instance);
    return instance;
  }
};
export {
  FxSticky as default
};
//# sourceMappingURL=fx-sticky.BIZ-Ix_E.js.map
