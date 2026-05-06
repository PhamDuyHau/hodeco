// 3rd/index.js
// Master loader - aggregates all lazy loaders

import FX from './fx/index.js';
import Modules from './modules/index.js';
import Plugins from './plugins/index.js';
import Three from './three/index.js';
/**
 * Initialize all loaders
 * @param {Object} options - { root: Document|Element }
 */

async function initAll(options = {}) {
	await Promise.all([
		FX.init(options),
		Modules.init(options),
		Plugins.init(options),
		Three.init(options),
	]);
}
export { FX, Modules, Plugins, Three, initAll };
