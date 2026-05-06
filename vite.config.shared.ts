import { UserConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

import { wpVite, type WPViteOptions } from './vite.plugin-wp';

/**
 * Create shared config for WordPress themes/plugins
 * Uses wpVite plugin internally for simpler configuration
 */
export const getSharedConfig = (wpOptions: WPViteOptions): UserConfig => ({
	plugins: [
		// WordPress Vite Plugin - handles everything: input/output/chunks/css/build/imagemin/static-copy/jquery-alias
		...wpVite({
			// Default jQuery alias path (relative to project root)
			jQueryAlias: './jquery-global.cjs',
			...wpOptions,
		}),

		// Tailwind CSS
		tailwindcss(),
	],
});

// Re-export for convenience
export { wpVite, type WPViteOptions };
