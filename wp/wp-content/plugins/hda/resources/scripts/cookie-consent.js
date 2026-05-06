/**
 * Cookie Consent banner logic.
 *
 * Reads data attributes from the banner element:
 *   data-consent-days, data-dismiss-days
 */
import CookieService from './utils/cookie.js';

const CONSENT_KEY = 'cookie_consent';
const DISMISS_KEY = 'cookie_consent_dismissed';

const banner = document.querySelector('.hda-cookie-consent');
if (banner) {
	const consentDays = parseInt(banner.dataset.consentDays) || 180;
	const dismissDays = parseInt(banner.dataset.dismissDays) || 7;

	const init = async () => {
		const [consent, dismissed] = await Promise.all([CookieService.get(CONSENT_KEY), CookieService.get(DISMISS_KEY)]);

		if (consent === 'accepted' || dismissed === '1') {
			banner.remove();
			return;
		}

		// Show banner.
		banner.style.display = 'flex';

		const hide = () => {
			banner.style.transition = 'opacity .3s ease';
			banner.style.opacity = '0';
			setTimeout(() => banner.remove(), 300);
		};

		banner.querySelector('.js-cookie-consent-accept')?.addEventListener(
			'click',
			async () => {
				await CookieService.set(CONSENT_KEY, 'accepted', { days: consentDays });
				await CookieService.delete(DISMISS_KEY);
				hide();
			},
			{ once: true },
		);

		banner.querySelector('.js-cookie-consent-close')?.addEventListener(
			'click',
			async () => {
				await CookieService.set(DISMISS_KEY, '1', { days: dismissDays });
				hide();
			},
			{ once: true },
		);
	};

	init();
}
