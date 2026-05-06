/**
 * Cookie utility using CookieStore API with document.cookie fallback.
 */
export default class CookieService {
	static #sameSiteMap = { lax: 'Lax', strict: 'Strict', none: 'None' };

	static #normalizeSameSite(val = 'Lax') {
		return CookieService.#sameSiteMap[String(val).toLowerCase()] || 'Lax';
	}

	static async get(name) {
		if (window.cookieStore) {
			return (await window.cookieStore.get(name))?.value || '';
		}

		const m = document.cookie.match(new RegExp(`(^|;)\\s*${encodeURIComponent(name)}\\s*=\\s*([^;]+)`));
		return m ? decodeURIComponent(m[2]) : '';
	}

	static async set(name, value, { days = 365, path = '/', secure = true, sameSite = 'Lax' } = {}) {
		const ss = CookieService.#normalizeSameSite(sameSite);
		const expires = days ? new Date(Date.now() + days * 864e5) : undefined;

		if (window.cookieStore) {
			const opts = { name, value, path, secure: !!secure, sameSite: ss.toLowerCase() };
			if (expires) opts.expires = expires;
			return window.cookieStore.set(opts);
		}

		let str = `${encodeURIComponent(name)}=${encodeURIComponent(value)};path=${path};SameSite=${ss}`;
		if (expires) str += `;expires=${expires.toUTCString()}`;
		if (secure) str += ';Secure';
		document.cookie = str;
	}

	static async delete(name, { path = '/' } = {}) {
		if (window.cookieStore) {
			return window.cookieStore.delete(name, { path });
		}

		document.cookie = `${encodeURIComponent(name)}=;path=${path};expires=Thu, 01 Jan 1970 00:00:00 GMT`;
	}
}
