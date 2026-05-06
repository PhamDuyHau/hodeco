import { $ as $$, E as Events, c as createWeakStore } from "../vendor.BOa7rJol.js";
const SELECTOR = "[data-fx-counter]";
const COUNTER_CLASS = ".counter";
const observerStore = createWeakStore();
const ease = (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
const formatValue = (value, template) => {
  const needsPad = template.startsWith("0") && template.length > 1;
  return needsPad ? String(value).padStart(template.length, "0") : value.toLocaleString();
};
const animate = (el, end, duration) => {
  const start = performance.now();
  const template = el.dataset.counter;
  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const value = Math.floor(end * ease(progress));
    el.textContent = formatValue(value, template);
    if (progress < 1) {
      requestAnimationFrame(tick);
    } else {
      el.textContent = formatValue(end, template);
      el.classList.add("counter-completed");
      Events.emit("fx:counter:complete", { el, value: end });
    }
  };
  requestAnimationFrame(tick);
};
const reset = (el) => {
  el.textContent = formatValue(0, el.dataset.counter);
  el.classList.remove("counter-completed");
};
const FxCounter = {
  /**
   * Initialize all counters in root
   * @param {Document|Element} root - Root element to search
   */
  initAll(root = document) {
    $$(SELECTOR, root).forEach(
      /** @param {HTMLElement} container */
      (container) => {
        const once = container.dataset.once !== "false";
        const duration = parseInt(container.dataset.duration, 10) || 2e3;
        const threshold = parseFloat(container.dataset.threshold) || 0.3;
        const counters = container.querySelectorAll(COUNTER_CLASS);
        if (!counters.length) return;
        let counted = false;
        const observer = new IntersectionObserver(
          ([entry]) => {
            if (entry.isIntersecting) {
              if (once && counted) return;
              counted = true;
              container.classList.add("is-counting");
              counters.forEach((el) => animate(el, parseInt(el.dataset.counter, 10) || 0, duration));
              Events.emit("fx:counter:start", { container, counters });
              if (once) observer.disconnect();
            } else if (!once) {
              container.classList.remove("is-counting");
              counters.forEach(reset);
              Events.emit("fx:counter:reset", { container, counters });
            }
          },
          { threshold }
        );
        observer.observe(container);
        observerStore.set(container, observer);
      }
    );
  },
  /**
   * Destroy all counters in root
   * @param {Document|Element} root - Root element to search
   */
  destroyAll(root = document) {
    $$(SELECTOR, root).forEach(
      /** @param {HTMLElement} container */
      (container) => {
        observerStore.cleanup(container, (o) => o.disconnect());
        container.classList.remove("is-counting");
      }
    );
  }
};
export {
  FxCounter as default
};
//# sourceMappingURL=fx-counter.Cvvy0cDm.js.map
