import { $ as $$, o as off, d as delegate, c as createWeakStore, E as Events } from "../vendor.BOa7rJol.js";
const DATA_TABS = "[data-fx-tabs]";
const DATA_TABS_CONTENT = "data-fx-tabs-content";
const ACTIVE = "is-active";
const TAB_BTN = ".tabs-title > a";
const delegatedHandlers = createWeakStore();
const tabMeta = createWeakStore();
const FxTabs = {
  initAll(root = document) {
    $$(DATA_TABS, root).forEach((tabList) => {
      const collapse = tabList.dataset.activeCollapse === "true";
      const id = tabList.id;
      if (!id) return;
      const content = root.querySelector(`[${DATA_TABS_CONTENT}="${id}"]`);
      if (!content) return;
      tabList.setAttribute("role", "tablist");
      const buttons = tabList.querySelectorAll(TAB_BTN);
      const panels = content.querySelectorAll(".tabs-panel");
      const titles = tabList.querySelectorAll(".tabs-title");
      buttons.forEach((btn) => {
        const li = btn.parentElement;
        const panelId = btn.getAttribute("href");
        if (!(panelId == null ? void 0 : panelId.startsWith("#"))) return;
        const panel = content.querySelector(`[id="${panelId.slice(1)}"]`);
        if (!panel) return;
        const active = li.classList.contains(ACTIVE);
        li.setAttribute("role", "presentation");
        btn.setAttribute("role", "tab");
        btn.setAttribute("aria-controls", panel.id);
        btn.setAttribute("aria-selected", String(active));
        panel.setAttribute("role", "tabpanel");
        panel.setAttribute("aria-hidden", String(!active));
        panel.classList.toggle(ACTIVE, active);
      });
      tabMeta.set(tabList, { collapse, content, titles, panels, buttons });
      const handler = (e, btn) => {
        e.preventDefault();
        const meta = tabMeta.get(tabList);
        if (!meta) return;
        const panelId = btn.getAttribute("href");
        if (!(panelId == null ? void 0 : panelId.startsWith("#"))) return;
        const panel = meta.content.querySelector(`[id="${panelId.slice(1)}"]`);
        if (!panel) return;
        const li = btn.parentElement;
        const isActive = li.classList.contains(ACTIVE);
        if (meta.collapse && isActive) {
          li.classList.remove(ACTIVE);
          btn.setAttribute("aria-selected", "false");
          panel.classList.remove(ACTIVE);
          panel.setAttribute("aria-hidden", "true");
          Events.emit("fx:tabs:change", { tab: btn, panel, wrapper: tabList });
          return;
        }
        if (isActive) return;
        meta.titles.forEach((t) => t.classList.remove(ACTIVE));
        meta.panels.forEach((p) => {
          p.classList.remove(ACTIVE);
          p.setAttribute("aria-hidden", "true");
        });
        meta.buttons.forEach((b) => b.setAttribute("aria-selected", "false"));
        li.classList.add(ACTIVE);
        btn.setAttribute("aria-selected", "true");
        panel.classList.add(ACTIVE);
        panel.setAttribute("aria-hidden", "false");
        Events.emit("fx:tabs:change", { tab: btn, panel, wrapper: tabList });
      };
      const wrapperFn = delegate(tabList, TAB_BTN, "click", handler);
      delegatedHandlers.set(tabList, wrapperFn);
    });
  },
  destroyAll(root = document) {
    $$(DATA_TABS, root).forEach((tabList) => {
      const wrapperFn = delegatedHandlers.get(tabList);
      if (wrapperFn) {
        off(tabList, "click", wrapperFn);
        delegatedHandlers.delete(tabList);
      }
      tabMeta.delete(tabList);
    });
  }
};
export {
  FxTabs as default
};
//# sourceMappingURL=fx-tabs.CK5HLI-f.js.map
