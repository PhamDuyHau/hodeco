import { $ as $$, o as off, b as $, e as closest, a as on, c as createWeakStore, E as Events, t as trigger } from "../vendor.We5pwaIa.js";
const DATA_TOGGLE = "data-fx-dropdown-toggle";
const DATA_DROPDOWN = "data-fx-dropdown";
const OPEN = "is-open";
const toggleHandlers = createWeakStore();
const dropdownMap = createWeakStore();
let docHandler = null;
function close(dropdown) {
  dropdown.classList.remove(OPEN);
  const btn = dropdownMap.get(dropdown);
  btn == null ? void 0 : btn.classList.remove("hover");
  btn == null ? void 0 : btn.setAttribute("aria-expanded", "false");
  Events.emit("fx:dropdown:close", { el: dropdown });
  trigger(dropdown, "fx.dropdown.closed", { el: dropdown });
}
function open(dropdown, btn) {
  var _a;
  closeAll(dropdown);
  dropdown.classList.add(OPEN);
  btn.classList.add("hover");
  btn.setAttribute("aria-expanded", "true");
  if (dropdown.dataset.autoFocus === "true") {
    (_a = dropdown.querySelector("input, textarea, select, [contenteditable]")) == null ? void 0 : _a.focus();
  }
  Events.emit("fx:dropdown:open", { btn, el: dropdown });
  trigger(dropdown, "fx.dropdown.opened", { btn, el: dropdown });
}
function closeAll(except = null) {
  $$(`[${DATA_DROPDOWN}]`).forEach((el) => {
    if (el !== except) close(el);
  });
}
const FxDropdown = {
  initAll(root = document) {
    $$("[" + DATA_TOGGLE + "]", root).forEach((btn) => {
      const sel = btn.getAttribute(DATA_TOGGLE);
      const dropdown = sel ? $(sel) : closest(btn, `[${DATA_DROPDOWN}]`);
      if (!dropdown) return;
      dropdownMap.set(dropdown, btn);
      if (dropdown.id) btn.setAttribute("aria-controls", dropdown.id);
      btn.setAttribute("aria-expanded", "false");
      const handler = (e) => {
        e.preventDefault();
        const isOpen = dropdown.classList.contains(OPEN);
        isOpen ? close(dropdown) : open(dropdown, btn);
      };
      toggleHandlers.set(btn, handler);
      on(btn, "click", handler);
    });
    if (!docHandler) {
      docHandler = (e) => {
        const inside = e.target.closest(`[${DATA_DROPDOWN}], [${DATA_TOGGLE}]`);
        if (!inside) closeAll();
      };
      on(document, "click", docHandler);
    }
  },
  destroyAll(root = document) {
    $$(`[${DATA_TOGGLE}]`, root).forEach((btn) => {
      const h = toggleHandlers.get(btn);
      if (h) {
        off(btn, "click", h);
        toggleHandlers.delete(btn);
      }
    });
    if (docHandler && root === document) {
      off(document, "click", docHandler);
      docHandler = null;
    }
  }
};
export {
  FxDropdown as default
};
//# sourceMappingURL=fx-dropdown.BCHZWHS4.js.map
