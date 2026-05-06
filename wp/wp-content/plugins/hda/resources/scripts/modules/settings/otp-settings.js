/**
 * OTP Settings visibility toggle.
 * Controls the display of OTP mode, gateway, and config fields.
 */
export function initOtpSettings() {
	const otpModeRadios = document.querySelectorAll('input[name="otp_mode"]');
	const otpGatewaySelect = document.getElementById('otp_gateway');

	if (!otpModeRadios.length) return;

	const smsOnlyElements = document.querySelectorAll('.otp-sms-only');
	const enabledOnlyElements = document.querySelectorAll('.otp-enabled-only');
	const gatewayConfigs = document.querySelectorAll('.otp-gateway-config');

	function updateVisibility() {
		const selectedMode = document.querySelector('input[name="otp_mode"]:checked')?.value || 'disabled';
		const selectedGateway = otpGatewaySelect?.value || 'telegram';

		const isOtp = ['email', 'sms', 'totp'].includes(selectedMode);

		// SMS-only elements (gateway selector, gateway config)
		smsOnlyElements.forEach((el) => {
			el.style.display = selectedMode === 'sms' ? '' : 'none';
		});

		// OTP-only elements (visible for email, sms, totp — hidden for disabled & magic_link)
		enabledOnlyElements.forEach((el) => {
			el.style.display = isOtp || selectedMode === 'magic_link' ? '' : 'none';
		});

		// Gateway config fields (show only current gateway when SMS mode)
		gatewayConfigs.forEach((el) => {
			const isCurrentGateway = el.classList.contains('gateway-' + selectedGateway);
			el.style.display = selectedMode === 'sms' && isCurrentGateway ? '' : 'none';
		});
	}

	otpModeRadios.forEach((radio) => radio.addEventListener('change', updateVisibility));
	otpGatewaySelect?.addEventListener('change', updateVisibility);
	updateVisibility();
}
