// extra.js

/**
 * Extra scripts - lazy loaded for additional functionality
 * Add your custom scripts here
 */

const run = async () => {
	//...
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
