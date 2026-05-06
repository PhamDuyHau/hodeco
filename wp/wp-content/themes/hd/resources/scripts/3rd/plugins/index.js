// plugins/index.js
// Plugin-specific assets loader

import { createLoader } from '../createLoader.js';

const config = {
	woocommerce: {
		selector: '.woocommerce, .wc-block-cart, .wc-block-checkout',
		loader: () => import('./woocommerce/woocommerce.js'),
	},
};

export default createLoader(config, 'Plugins');
