// plugins/woocommerce/woocommerce.js

import './woocommerce.scss';

/**
 * WooCommerce specific scripts
 * Lazy loaded only when WooCommerce elements are present
 */

export default {
	initAll(root = document) {
		// WooCommerce enhancements can be added here
		// This module is lazy-loaded when .woocommerce, .wc-block-cart or .wc-block-checkout is present
	},

	destroyAll(root = document) {
		// Cleanup if needed
	},
};
