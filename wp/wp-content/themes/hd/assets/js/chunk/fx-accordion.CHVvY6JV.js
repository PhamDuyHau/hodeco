import { $ as $$, o as off, d as delegate, c as createWeakStore, e as closest, E as Events } from "../vendor.BOa7rJol.js";
const SELECTOR = "[data-fx-accordion]";
const ITEM = "[data-fx-accordion-item]";
const CONTENT = "[data-fx-accordion-content]";
const TITLE = "[data-fx-accordion-title], .accordion-title";
const ACTIVE_CLASS = "is-active";
const delegatedHandlers = createWeakStore();
const FxAccordion = {
  initAll(root = document) {
    $$(SELECTOR, root).forEach((accordionEl) => {
      const allowAllClosed = accordionEl.dataset.allowAllClosed === "true";
      const multiExpand = accordionEl.dataset.multiExpand === "true";
      accordionEl.querySelectorAll(ITEM).forEach((item) => {
        const btn = item.querySelector(TITLE);
        const panel = item.querySelector(CONTENT);
        if (!btn || !panel) return;
        const active = item.classList.contains(ACTIVE_CLASS);
        btn.setAttribute("aria-expanded", active ? "true" : "false");
        panel.setAttribute("aria-hidden", active ? "false" : "true");
      });
      const handler = (e, btn) => {
        e.preventDefault();
        const item = closest(btn, ITEM);
        const panel = item == null ? void 0 : item.querySelector(CONTENT);
        if (!item || !panel) return;
        const isOpen = item.classList.contains(ACTIVE_CLASS);
        if (isOpen) {
          if (!allowAllClosed) {
            const openedCount = accordionEl.querySelectorAll(`.${ACTIVE_CLASS}`).length;
            if (openedCount <= 1) return;
          }
          item.classList.remove(ACTIVE_CLASS);
          btn.setAttribute("aria-expanded", "false");
          panel.setAttribute("aria-hidden", "true");
          Events.emit("fx:accordion:close", { item, panel });
          return;
        }
        if (!multiExpand) {
          accordionEl.querySelectorAll(ITEM).forEach((other) => {
            if (other !== item) {
              other.classList.remove(ACTIVE_CLASS);
              const b = other.querySelector(TITLE);
              const p = other.querySelector(CONTENT);
              b == null ? void 0 : b.setAttribute("aria-expanded", "false");
              p == null ? void 0 : p.setAttribute("aria-hidden", "true");
            }
          });
        }
        item.classList.add(ACTIVE_CLASS);
        btn.setAttribute("aria-expanded", "true");
        panel.setAttribute("aria-hidden", "false");
        Events.emit("fx:accordion:open", { item, panel });
      };
      const wrapperFn = delegate(accordionEl, TITLE, "click", handler);
      delegatedHandlers.set(accordionEl, wrapperFn);
    });
  },
  destroyAll(root = document) {
    $$(SELECTOR, root).forEach((accordionEl) => {
      const wrapperFn = delegatedHandlers.get(accordionEl);
      if (wrapperFn) {
        off(accordionEl, "click", wrapperFn);
        delegatedHandlers.delete(accordionEl);
      }
    });
  }
};
export {
  FxAccordion as default
};
//# sourceMappingURL=fx-accordion.CHVvY6JV.js.map
