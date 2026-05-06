// index.js

//import device from 'current-device';
import { initAll } from './3rd/index.js';

import './utils/global.js';
import scriptLoader from './utils/script-loader.js';

// TailwindCSS -> tw.xxx.css
import '../styles/tailwind/index.css';

const run = async () => {
	// Lazy-load all libs (FX, Modules, Plugins)
	await initAll();

	await scriptLoader();
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
