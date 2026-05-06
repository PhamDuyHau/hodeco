var __typeError = (msg) => {
  throw TypeError(msg);
};
var __accessCheck = (obj, member, msg) => member.has(obj) || __typeError("Cannot " + msg);
var __privateGet = (obj, member, getter) => (__accessCheck(obj, member, "read from private field"), getter ? getter.call(obj) : member.get(obj));
var __privateAdd = (obj, member, value) => member.has(obj) ? __typeError("Cannot add the same private member more than once") : member instanceof WeakSet ? member.add(obj) : member.set(obj, value);
var __privateMethod = (obj, member, method) => (__accessCheck(obj, member, "access private method"), method);
var _sameSiteMap, _CookieService_static, normalizeSameSite_fn;
const _CookieService = class _CookieService {
  static async get(name) {
    var _a;
    if (window.cookieStore) {
      return ((_a = await window.cookieStore.get(name)) == null ? void 0 : _a.value) || "";
    }
    const m = document.cookie.match(new RegExp(`(^|;)\\s*${encodeURIComponent(name)}\\s*=\\s*([^;]+)`));
    return m ? decodeURIComponent(m[2]) : "";
  }
  static async set(name, value, { days = 365, path = "/", secure = true, sameSite = "Lax" } = {}) {
    var _a;
    const ss = __privateMethod(_a = _CookieService, _CookieService_static, normalizeSameSite_fn).call(_a, sameSite);
    const expires = days ? new Date(Date.now() + days * 864e5) : void 0;
    if (window.cookieStore) {
      const opts = { name, value, path, secure: !!secure, sameSite: ss.toLowerCase() };
      if (expires) opts.expires = expires;
      return window.cookieStore.set(opts);
    }
    let str = `${encodeURIComponent(name)}=${encodeURIComponent(value)};path=${path};SameSite=${ss}`;
    if (expires) str += `;expires=${expires.toUTCString()}`;
    if (secure) str += ";Secure";
    document.cookie = str;
  }
  static async delete(name, { path = "/" } = {}) {
    if (window.cookieStore) {
      return window.cookieStore.delete(name, { path });
    }
    document.cookie = `${encodeURIComponent(name)}=;path=${path};expires=Thu, 01 Jan 1970 00:00:00 GMT`;
  }
};
_sameSiteMap = new WeakMap();
_CookieService_static = new WeakSet();
normalizeSameSite_fn = function(val = "Lax") {
  return __privateGet(_CookieService, _sameSiteMap)[String(val).toLowerCase()] || "Lax";
};
__privateAdd(_CookieService, _CookieService_static);
__privateAdd(_CookieService, _sameSiteMap, { lax: "Lax", strict: "Strict", none: "None" });
let CookieService = _CookieService;
const CONSENT_KEY = "cookie_consent";
const DISMISS_KEY = "cookie_consent_dismissed";
const banner = document.querySelector(".hda-cookie-consent");
if (banner) {
  const consentDays = parseInt(banner.dataset.consentDays) || 180;
  const dismissDays = parseInt(banner.dataset.dismissDays) || 7;
  const init = async () => {
    var _a, _b;
    const [consent, dismissed] = await Promise.all([CookieService.get(CONSENT_KEY), CookieService.get(DISMISS_KEY)]);
    if (consent === "accepted" || dismissed === "1") {
      banner.remove();
      return;
    }
    banner.style.display = "flex";
    const hide = () => {
      banner.style.transition = "opacity .3s ease";
      banner.style.opacity = "0";
      setTimeout(() => banner.remove(), 300);
    };
    (_a = banner.querySelector(".js-cookie-consent-accept")) == null ? void 0 : _a.addEventListener(
      "click",
      async () => {
        await CookieService.set(CONSENT_KEY, "accepted", { days: consentDays });
        await CookieService.delete(DISMISS_KEY);
        hide();
      },
      { once: true }
    );
    (_b = banner.querySelector(".js-cookie-consent-close")) == null ? void 0 : _b.addEventListener(
      "click",
      async () => {
        await CookieService.set(DISMISS_KEY, "1", { days: dismissDays });
        hide();
      },
      { once: true }
    );
  };
  init();
}
//# sourceMappingURL=cookie-consent.Bz71TwNC.js.map
