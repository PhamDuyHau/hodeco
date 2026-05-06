const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./chunk/fx-smoothscroll.DPA-Oc8x.js","./vendor.BOa7rJol.js","./chunk/fx-tabs.CK5HLI-f.js","../css/chunk/fx-tabs.rKrExMXy.css","./chunk/fx-offcanvas.CGyMibiN.js","../css/chunk/fx-offcanvas.bY8YaZM4.css","./chunk/fx-dropdown.B7iZd-E6.js","../css/chunk/fx-dropdown.CVbmmqPT.css","./chunk/fx-dropdown-menu.CKGlAUSJ.js","../css/chunk/fx-dropdown-menu.Dw_AsFJS.css","./chunk/fx-accordion.CHVvY6JV.js","../css/chunk/fx-accordion.BLAg01zG.css","./chunk/fx-accordion-menu.CmKtm7uJ.js","./chunk/fx-counter.Cvvy0cDm.js","../css/chunk/fx-counter.B_4lZ8WS.css","./chunk/fx-share.B2fOsDMf.js","../css/chunk/fx-share.BnbeewL1.css","./chunk/fx-lightbox.C3coyKKb.js","../css/chunk/fx-lightbox.B5NvWX_Y.css","./chunk/fx-slider.CrBpnqVe.js","../css/chunk/fx-slider.CuOSdcWZ.css","./chunk/fx-sticky.BEeWG13R.js","./chunk/fx-scroll-top.LNeXWZRQ.js","./chunk/fx-masonry.Dtes-JaB.js","../css/chunk/fx-masonry.BNLGY-vX.css","./chunk/fx-freeform.CPTEvNOb.js","../css/chunk/fx-freeform.DUjGNYeQ.css","./chunk/fx-hybrid.BaarsiHm.js","../css/chunk/fx-hybrid.C4fI1xWO.css","./chunk/woocommerce.Cum0fP4R.js","../css/chunk/woocommerce.B8ANIoJf.css","./three-rotate.VI9CCBaR.js","./three-float.LGqPXVFC.js","./three-image.BAF47hR8.js"])))=>i.map(i=>d[i]);
import { E as Events } from "./vendor.BOa7rJol.js";
/* empty css            */
const scriptRel = "modulepreload";
const assetsURL = function(dep, importerUrl) {
  return new URL(dep, importerUrl).href;
};
const seen = {};
const __vitePreload = function preload(baseModule, deps, importerUrl) {
  let promise = Promise.resolve();
  if (deps && deps.length > 0) {
    let allSettled = function(promises$2) {
      return Promise.all(promises$2.map((p) => Promise.resolve(p).then((value$1) => ({
        status: "fulfilled",
        value: value$1
      }), (reason) => ({
        status: "rejected",
        reason
      }))));
    };
    const links = document.getElementsByTagName("link");
    const cspNonceMeta = document.querySelector("meta[property=csp-nonce]");
    const cspNonce = (cspNonceMeta == null ? void 0 : cspNonceMeta.nonce) || (cspNonceMeta == null ? void 0 : cspNonceMeta.getAttribute("nonce"));
    promise = allSettled(deps.map((dep) => {
      dep = assetsURL(dep, importerUrl);
      if (dep in seen) return;
      seen[dep] = true;
      const isCss = dep.endsWith(".css");
      const cssSelector = isCss ? '[rel="stylesheet"]' : "";
      if (!!importerUrl) for (let i$1 = links.length - 1; i$1 >= 0; i$1--) {
        const link$1 = links[i$1];
        if (link$1.href === dep && (!isCss || link$1.rel === "stylesheet")) return;
      }
      else if (document.querySelector(`link[href="${dep}"]${cssSelector}`)) return;
      const link = document.createElement("link");
      link.rel = isCss ? "stylesheet" : scriptRel;
      if (!isCss) link.as = "script";
      link.crossOrigin = "";
      link.href = dep;
      if (cspNonce) link.setAttribute("nonce", cspNonce);
      document.head.appendChild(link);
      if (isCss) return new Promise((res, rej) => {
        link.addEventListener("load", res);
        link.addEventListener("error", () => rej(/* @__PURE__ */ new Error(`Unable to preload CSS for ${dep}`)));
      });
    }));
  }
  function handlePreloadError(err$2) {
    const e$1 = new Event("vite:preloadError", { cancelable: true });
    e$1.payload = err$2;
    window.dispatchEvent(e$1);
    if (!e$1.defaultPrevented) throw err$2;
  }
  return promise.then((res) => {
    for (const item of res || []) {
      if (item.status !== "rejected") continue;
      handlePreloadError(item.reason);
    }
    return baseModule().catch(handlePreloadError);
  });
};
function createLoader(config2, name = "Loader", { debug = false } = {}) {
  const loaded = /* @__PURE__ */ new Map();
  const log = (...args) => {
    if (debug) console.log(`[${name}]`, ...args);
  };
  const isNeeded = (key, root = document) => {
    const cfg = config2[key];
    return cfg ? root.querySelector(cfg.selector) !== null : false;
  };
  const load = async (key) => {
    if (loaded.has(key)) {
      log(`Cache hit: ${key}`);
      return loaded.get(key);
    }
    const cfg = config2[key];
    if (!cfg) {
      log(`Not found: ${key}`);
      return null;
    }
    try {
      log(`Loading: ${key}`);
      const module = await cfg.loader();
      const m = module.default || module;
      loaded.set(key, m);
      log(`Loaded: ${key}`);
      return m;
    } catch (e) {
      console.error(`[${name}] Failed to load: ${key}`, e);
      return null;
    }
  };
  return {
    async init({ root = document } = {}) {
      const needed = Object.keys(config2).filter((key) => isNeeded(key, root));
      log(`Init - needed modules:`, needed);
      const promises = needed.map(async (key) => {
        var _a;
        const m = await load(key);
        (_a = m == null ? void 0 : m.initAll) == null ? void 0 : _a.call(m, root);
      });
      await Promise.all(promises);
      log(`Init complete`);
    },
    async destroy(key, root = document) {
      var _a;
      const m = loaded.get(key);
      if (m) {
        (_a = m.destroyAll) == null ? void 0 : _a.call(m, root);
        log(`Destroyed: ${key}`);
      }
    },
    async reinit(key, root = document) {
      var _a, _b;
      let m = loaded.get(key);
      if (!m) m = await load(key);
      if (m) {
        (_a = m.destroyAll) == null ? void 0 : _a.call(m, root);
        (_b = m.initAll) == null ? void 0 : _b.call(m, root);
        log(`Reinit: ${key}`);
      }
    },
    load,
    get loaded() {
      return [...loaded.keys()];
    },
    get available() {
      return Object.keys(config2);
    },
    /** Enable/disable debug mode at runtime */
    setDebug(enabled) {
      debug = enabled;
      log(`Debug mode: ${enabled ? "ON" : "OFF"}`);
    },
    /**
     * Refresh/re-init modules in a specific container (for dynamic AJAX content)
     * Zero overhead when not called - most performant option for dynamic content
     * @param {Element|Document} root - Container to scan for new elements
     * @param {string|string[]} [keys] - Specific module(s) to refresh, or all if omitted
     */
    async refresh(root = document, keys = null) {
      const targetKeys = keys ? Array.isArray(keys) ? keys : [keys] : Object.keys(config2);
      const needed = targetKeys.filter((key) => isNeeded(key, root));
      log(`Refresh - needed modules:`, needed);
      const promises = needed.map(async (key) => {
        var _a;
        const m = await load(key);
        (_a = m == null ? void 0 : m.initAll) == null ? void 0 : _a.call(m, root);
      });
      await Promise.all(promises);
      log(`Refresh complete`);
    }
  };
}
const config$3 = {
  smoothScroll: {
    selector: "[data-fx-scroll]",
    loader: () => __vitePreload(() => import("./chunk/fx-smoothscroll.DPA-Oc8x.js"), true ? __vite__mapDeps([0,1]) : void 0, import.meta.url)
  },
  tabs: {
    selector: "[data-fx-tabs]",
    loader: () => __vitePreload(() => import("./chunk/fx-tabs.CK5HLI-f.js"), true ? __vite__mapDeps([2,1,3]) : void 0, import.meta.url)
  },
  offCanvas: {
    selector: "[data-fx-off-canvas], [data-open], [data-close]",
    loader: () => __vitePreload(() => import("./chunk/fx-offcanvas.CGyMibiN.js"), true ? __vite__mapDeps([4,1,5]) : void 0, import.meta.url)
  },
  dropdown: {
    selector: "[data-fx-dropdown], [data-fx-dropdown-toggle]",
    loader: () => __vitePreload(() => import("./chunk/fx-dropdown.B7iZd-E6.js"), true ? __vite__mapDeps([6,1,7]) : void 0, import.meta.url)
  },
  dropdownMenu: {
    selector: "[data-fx-dropdown-menu]",
    loader: () => __vitePreload(() => import("./chunk/fx-dropdown-menu.CKGlAUSJ.js"), true ? __vite__mapDeps([8,1,9]) : void 0, import.meta.url)
  },
  accordion: {
    selector: "[data-fx-accordion]",
    loader: () => __vitePreload(() => import("./chunk/fx-accordion.CHVvY6JV.js"), true ? __vite__mapDeps([10,1,11]) : void 0, import.meta.url)
  },
  accordionMenu: {
    selector: "[data-fx-accordion-menu]",
    loader: () => __vitePreload(() => import("./chunk/fx-accordion-menu.CmKtm7uJ.js"), true ? __vite__mapDeps([12,1]) : void 0, import.meta.url)
  },
  counter: {
    selector: "[data-fx-counter]",
    loader: () => __vitePreload(() => import("./chunk/fx-counter.Cvvy0cDm.js"), true ? __vite__mapDeps([13,1,14]) : void 0, import.meta.url)
  },
  share: {
    selector: "[data-fx-share]",
    loader: () => __vitePreload(() => import("./chunk/fx-share.B2fOsDMf.js"), true ? __vite__mapDeps([15,1,16]) : void 0, import.meta.url)
  },
  lightbox: {
    selector: '[data-fancybox], .fcy-popup, .fcy-video, .banner-video a, [id^="gallery-"] a, [data-rel="lightbox"], [data-fx-lightbox]',
    loader: () => __vitePreload(() => import("./chunk/fx-lightbox.C3coyKKb.js"), true ? __vite__mapDeps([17,1,18]) : void 0, import.meta.url)
  },
  slider: {
    selector: "[data-fx-slider]",
    loader: () => __vitePreload(() => import("./chunk/fx-slider.CrBpnqVe.js"), true ? __vite__mapDeps([19,1,20]) : void 0, import.meta.url)
  },
  sticky: {
    selector: "[data-fx-sticky]",
    loader: () => __vitePreload(() => import("./chunk/fx-sticky.BEeWG13R.js"), true ? __vite__mapDeps([21,1]) : void 0, import.meta.url)
  },
  scrollTop: {
    selector: "[data-fx-scroll-top]",
    loader: () => __vitePreload(() => import("./chunk/fx-scroll-top.LNeXWZRQ.js"), true ? __vite__mapDeps([22,1]) : void 0, import.meta.url)
  },
  masonry: {
    selector: "[data-fx-masonry]",
    loader: () => __vitePreload(() => import("./chunk/fx-masonry.Dtes-JaB.js"), true ? __vite__mapDeps([23,1,24]) : void 0, import.meta.url)
  },
  freeform: {
    selector: "[data-fx-freeform]",
    loader: () => __vitePreload(() => import("./chunk/fx-freeform.CPTEvNOb.js"), true ? __vite__mapDeps([25,1,26]) : void 0, import.meta.url)
  },
  hybrid: {
    selector: "[data-fx-hybrid]",
    loader: () => __vitePreload(() => import("./chunk/fx-hybrid.BaarsiHm.js"), true ? __vite__mapDeps([27,1,28]) : void 0, import.meta.url)
  }
};
const FX = createLoader(config$3, "FX");
FX.on = Events.on.bind(Events);
FX.off = Events.off.bind(Events);
FX.emit = Events.emit.bind(Events);
const config$2 = {
  // Add module-specific assets here when needed
  // Example:
  // optimizer: {
  //     selector: '[data-lazy-styles]',
  //     loader: () => import('./optimizer/index.js'),
  // },
};
const Modules = createLoader(config$2, "Modules");
const config$1 = {
  woocommerce: {
    selector: ".woocommerce, .wc-block-cart, .wc-block-checkout",
    loader: () => __vitePreload(() => import("./chunk/woocommerce.Cum0fP4R.js"), true ? __vite__mapDeps([29,30]) : void 0, import.meta.url)
  }
};
const Plugins = createLoader(config$1, "Plugins");
const config = {
  rotate: {
    selector: "[data-three-rotate]",
    loader: () => __vitePreload(() => import("./three-rotate.VI9CCBaR.js"), true ? __vite__mapDeps([31,1]) : void 0, import.meta.url)
  },
  float: {
    selector: "[data-three-float]",
    loader: () => __vitePreload(() => import("./three-float.LGqPXVFC.js"), true ? __vite__mapDeps([32,1]) : void 0, import.meta.url)
  },
  image: {
    selector: "[data-three-image]",
    loader: () => __vitePreload(() => import("./three-image.BAF47hR8.js"), true ? __vite__mapDeps([33,1]) : void 0, import.meta.url)
  }
};
const THREE_APP = createLoader(config, "THREE");
THREE_APP.on = Events.on.bind(Events);
THREE_APP.off = Events.off.bind(Events);
THREE_APP.emit = Events.emit.bind(Events);
async function initAll(options = {}) {
  await Promise.all([
    FX.init(options),
    Modules.init(options),
    Plugins.init(options),
    THREE_APP.init(options)
  ]);
}
(() => {
  if (window.__globalInit) return;
  window.__globalInit = true;
  const currentDomain = window.location.hostname;
  const invalidHref = /^(#|mailto:|tel:|javascript:|data:|blob:)/i;
  const selector = 'a._blank, a.blank, a[target="_blank"]';
  const linkSet = /* @__PURE__ */ new Set();
  function checkExternal(el) {
    var _a;
    const href = (_a = el.getAttribute("href")) == null ? void 0 : _a.trim();
    if (!href || invalidHref.test(href)) return;
    try {
      const url = new URL(href, window.location.href);
      if (url.hostname && url.hostname !== currentDomain) {
        linkSet.add(el);
      }
    } catch {
    }
  }
  function applyTargetRel(el) {
    if (el.target !== "_blank") el.target = "_blank";
    const relParts = (el.rel || "").split(/\s+/).filter(Boolean);
    ["noopener", "noreferrer", "nofollow"].forEach((r) => {
      if (!relParts.includes(r)) relParts.push(r);
    });
    el.rel = relParts.join(" ");
  }
  function processLinks() {
    for (const el of linkSet) applyTargetRel(el);
  }
  let observerTimeout;
  function handleMutations() {
    clearTimeout(observerTimeout);
    observerTimeout = setTimeout(() => {
      document.querySelectorAll('ul.submenu[role="menubar"]').forEach((menu) => {
        menu.setAttribute("role", "menu");
      });
      document.querySelectorAll('[aria-hidden="true"] a, [aria-hidden="true"] button').forEach((el) => {
        el.setAttribute("tabindex", "-1");
      });
    }, 200);
  }
  const run2 = async () => {
    document.querySelectorAll(selector).forEach((el) => linkSet.add(el));
    document.querySelectorAll("a[href]").forEach(checkExternal);
    processLinks();
    const observer = new MutationObserver(handleMutations);
    observer.observe(document.body, { childList: true, subtree: true });
    document.querySelectorAll("#footer-columns .toggle-title").forEach((link) => {
      link.addEventListener("click", function(event) {
        event.preventDefault();
        this.classList.toggle("active");
      });
    });
    document.querySelectorAll(".entry-content table").forEach(function(tbl) {
      if (tbl.parentElement && tbl.parentElement.classList.contains("table-scroll")) return;
      const wrap = document.createElement("div");
      wrap.className = "table-scroll";
      tbl.parentNode.insertBefore(wrap, tbl);
      wrap.appendChild(tbl);
    });
  };
  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", run2, { once: true }) : run2();
})();
const scriptLoader = (timeout = 4e3, selector = 'script[data-type="lazy"]') => {
  return new Promise((resolve) => {
    const events = ["mouseover", "keydown", "touchstart", "touchmove", "wheel"];
    let done = false;
    const load = () => {
      if (done) return;
      done = true;
      document.querySelectorAll(selector).forEach((s) => {
        s.src = s.dataset.src;
        s.removeAttribute("data-src");
        s.removeAttribute("data-type");
      });
      clearTimeout(timer);
      resolve();
    };
    const timer = setTimeout(load, timeout);
    events.forEach((e) => window.addEventListener(e, load, { once: true, passive: true, capture: true }));
  });
};
const run = async () => {
  await initAll();
  await scriptLoader();
};
document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", run, { once: true }) : run();
//# sourceMappingURL=index.CbkBVvGW.js.map
