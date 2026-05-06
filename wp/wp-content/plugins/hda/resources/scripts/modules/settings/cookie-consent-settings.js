/**
 * Cookie Consent Settings — visibility toggles.
 *
 * - When "Enable" is unchecked → hide all other fields.
 * - When "Privacy Policy URL" is empty → hide "Privacy Link Text".
 */
export function initCookieConsentSettings() {
	const enableCheckbox = document.getElementById('cc_enabled');
	const privacyUrlInput = document.getElementById('cc_privacy_url');

	if (!enableCheckbox) return;

	const dependentFields = document.querySelectorAll('.cc-depends-enabled');
	const privacyTextWrap = document.querySelector('.cc-depends-privacy-url');

	function updateVisibility() {
		const isEnabled = enableCheckbox.checked;

		// Toggle all fields that depend on "enabled".
		dependentFields.forEach((el) => {
			el.style.display = isEnabled ? '' : 'none';
		});

		// Toggle "Privacy Link Text" based on URL value (only when enabled).
		if (privacyTextWrap) {
			const hasUrl = isEnabled && privacyUrlInput && privacyUrlInput.value.trim() !== '';
			privacyTextWrap.style.display = hasUrl ? '' : 'none';
		}
	}

	enableCheckbox.addEventListener('change', updateVisibility);
	privacyUrlInput?.addEventListener('input', updateVisibility);
	updateVisibility();
}
