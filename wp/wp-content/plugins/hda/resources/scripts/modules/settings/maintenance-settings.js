/**
 * Maintenance Settings — visibility toggle.
 *
 * When "Enable" is unchecked → hide all dependent fields.
 */
export function initMaintenanceSettings() {
	const enableCheckbox = document.getElementById('mt_enabled');

	if (!enableCheckbox) return;

	const dependentFields = document.querySelectorAll('.mt-depends-enabled');

	function updateVisibility() {
		const isEnabled = enableCheckbox.checked;

		dependentFields.forEach((el) => {
			el.style.display = isEnabled ? '' : 'none';
		});
	}

	enableCheckbox.addEventListener('change', updateVisibility);
	updateVisibility();
}
