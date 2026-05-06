// 3rd/three/index.js
// Three.js Components Loader

import { createLoader } from '../createLoader.js';
import Events from '../events.js';

const config = {
	rotate: {
		selector: '[data-three-rotate]',
		loader: () => import('./rotate/three-rotate.js'),
	},

	float: {
		selector: '[data-three-float]',
		loader: () => import('./float/three-float.js'),
	},

	image: {
		selector: '[data-three-image]',
		loader: () => import('./image/three-image.js'),
	},
};

const THREE_APP = createLoader(config, 'THREE');

THREE_APP.on = Events.on.bind(Events);
THREE_APP.off = Events.off.bind(Events);
THREE_APP.emit = Events.emit.bind(Events);

export default THREE_APP;
