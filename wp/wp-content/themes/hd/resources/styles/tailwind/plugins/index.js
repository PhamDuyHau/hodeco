/* tailwind/plugins/index.js - Plugin entry point */

import { composeHandlers } from './_compose.js';
import presets from './clamp-presets.js';
import fluidTypeFactory from './clamp.js';

// Fluid typography: p-fs-clamp-[min,max] or p-fs-clamp-h1
const fluidType = fluidTypeFactory({
	root: 16,
	defaults: { minw: 640, maxw: 1536, base: 0 },
	presets,
});

export default composeHandlers(fluidType);
