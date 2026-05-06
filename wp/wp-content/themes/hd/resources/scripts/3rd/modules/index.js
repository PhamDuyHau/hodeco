// modules/index.js
// Core Modules assets loader

import { createLoader } from '../createLoader.js';

const config = {
	// Add module-specific assets here when needed
	// Example:
	// optimizer: {
	//     selector: '[data-lazy-styles]',
	//     loader: () => import('./optimizer/index.js'),
	// },
};

export default createLoader(config, 'Modules');
