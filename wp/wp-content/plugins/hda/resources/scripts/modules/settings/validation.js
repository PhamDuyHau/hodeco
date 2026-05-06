/**
 * Validation helpers.
 */

/**
 * Validate email address.
 *
 * @param {string} email
 * @returns {boolean}
 */
export function isValidEmail(email) {
	const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailPattern.test(email);
}

/**
 * Validate IP range (IPv4).
 *
 * @param {string} range
 * @returns {boolean}
 */
export function isValidIPRange(range) {
	const ipPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})$/;
	const rangePattern =
		/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})-(\\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])$/;
	const cidrPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\/(\\d|[1-2]\d|3[0-2])$/;

	if (ipPattern.test(range)) {
		return true;
	}

	if (rangePattern.test(range)) {
		const [startIP, endRange] = range.split('-');
		const endIP = startIP.split('.').slice(0, 3).join('.') + '.' + endRange;
		return compareIPs(startIP, endIP) < 0;
	}

	return cidrPattern.test(range);
}

/**
 * Compare two IP addresses.
 *
 * @param {string} ip1
 * @param {string} ip2
 * @returns {number}
 */
function compareIPs(ip1, ip2) {
	const ip1Parts = ip1.split('.').map(Number);
	const ip2Parts = ip2.split('.').map(Number);

	for (let i = 0; i < 4; i++) {
		if (ip1Parts[i] < ip2Parts[i]) return -1;
		if (ip1Parts[i] > ip2Parts[i]) return 1;
	}

	return 0;
}
