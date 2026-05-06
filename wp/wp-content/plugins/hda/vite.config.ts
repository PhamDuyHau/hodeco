/**
 * HD Addons Plugin Vite Configuration (Simplified with wpVite plugin)
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../vite.config.shared';

// Entry points
const jsFiles = ['hda', 'admin', 'login', 'otp-login', 'sorting', 'otp-profile', 'cookie-consent'];
const scssFiles = ['login', 'admin', 'hda', 'otp-profile', 'cookie-consent'];

export default defineConfig(
	getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
			scss: scssFiles,
		},
		enableChunkDetection: false, // Plugins don't need chunk detection
		// Custom manualChunks for plugin-specific behavior
		manualChunks: (id) => {
			if (id.includes('node_modules') || id.includes('scripts/3rd') || id.includes('styles/3rd')) {
				return 'vendor';
			}
			return undefined;
		},
	}),
);
