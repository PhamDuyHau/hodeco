/**
 * HD Theme Vite Configuration (Simplified with wpVite plugin)
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../vite.config.shared';

// Entry points
const jsFiles = ['preflight', 'admin', 'index', 'extra', 'home', 'about', 'branch'];
const scssFiles = ['main', 'admin', 'editor-style', 'share', 'page', 'home', 'contact', 'single', 'archive', 'about'];

// Chunk directories to scan (relative to scripts/3rd/)
const chunkDirs = ['fx', 'plugins', 'modules'];

// Lazy-loaded node_modules (won't be bundled into vendor chunk)
const lazyNodeModules = ['swiper', 'ensemble', 'ensemble-social-share', '@fancyapps/ui'];

export default defineConfig(
	getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
			scss: scssFiles,
		},
		chunkDirs,
		lazyNodeModules,
	}),
);
