/**
 * Vite Plugin for WordPress Theme/Plugin
 */

import type { Plugin, PluginOption, UserConfig, ConfigEnv } from 'vite';
import * as path from 'path';
import * as fs from 'fs';
import autoprefixer from 'autoprefixer';

// Imagemin plugins
import viteImagemin from '@vheemstra/vite-plugin-imagemin';
import imageminMozjpeg from 'imagemin-mozjpeg';
import imageminPngquant from 'imagemin-pngquant';
import imageminSVGO from 'imagemin-svgo';
import imageminGifsicle from 'imagemin-gifsicle';
import imageminWebp from 'imagemin-webp';

// Static copy plugin
import { viteStaticCopy } from 'vite-plugin-static-copy';

// ==================== Types ====================

export interface WPViteOptions {
	/**
	 * Base path to the theme/plugin directory
	 * Can be relative to project root or absolute
	 */
	basePath: string;

	/**
	 * Input entry points configuration
	 */
	input: {
		/** JavaScript/TypeScript files (without extension, relative to scripts/) */
		js?: string[];

		/** SCSS/CSS files (without extension, relative to styles/) */
		scss?: string[];

		/** Additional files with full paths relative to resources/ */
		extra?: string[];
	};

	/**
	 * Subdirectories to scan for chunks (relative to scripts/3rd/)
	 * @default ['fx', 'plugins', 'modules']
	 */
	chunkDirs?: string[];

	/**
	 * Directory containing components to scan
	 * @default 'components'
	 */
	componentsDir?: string;

	/**
	 * Copy static images from resources/img to assets/img
	 * @default true
	 */
	copyImages?: boolean;

	/**
	 * Enable image optimization in production
	 * @default true
	 */
	imageMin?: boolean;

	/**
	 * Lazy-loaded node_modules (won't be bundled into vendor chunk)
	 * @default []
	 */
	lazyNodeModules?: string[];

	/**
	 * Custom manualChunks configuration
	 * Return undefined to use default logic
	 */
	manualChunks?: (id: string, meta: { isChunk: (name: string) => boolean }) => string | undefined;

	/**
	 * Output directory name (relative to basePath)
	 * @default 'assets'
	 */
	outDir?: string;

	/**
	 * Resources directory name (relative to basePath)
	 * @default 'resources'
	 */
	resourcesDir?: string;

	/**
	 * Enable chunk detection and separate chunk folders
	 * @default true
	 */
	enableChunkDetection?: boolean;

	/**
	 * Path to jQuery global shim file (for select2 compatibility)
	 * The file should export: module.exports = window.jQuery;
	 * Set to false to disable, or provide absolute path
	 * @default false
	 */
	jQueryAlias?: string | false;
}

// ==================== Constants ====================

const EXT = {
	fonts: /^(woff2?|eot|ttf|otf)$/,
	images: /^(png|jpe?g|svg|gif|tiff|bmp|ico|webp|avif)$/,
};

// ==================== Helpers ====================

/**
 * Get asset file extension
 */
const getAssetExt = (assetInfo: { names?: string[]; name?: string }): string => {
	const fileName = assetInfo.names?.[0] ?? assetInfo.name ?? '';
	return fileName.split('.').pop()?.toLowerCase() || '';
};

/**
 * Get asset output path based on extension
 */
const getAssetPath = (ext: string): string | null => {
	if (EXT.fonts.test(ext)) return `fonts/[name].[ext]`;
	if (EXT.images.test(ext)) return `img/[name].[ext]`;
	return null;
};

/**
 * Default fallback path for assets
 */
const defaultAssetFallback = `files/[name].[ext]`;

/**
 * Scan files matching extension pattern in a directory
 */
const scanFiles = (dir: string, ext: RegExp): string[] => {
	return fs.existsSync(dir) ? fs.readdirSync(dir).filter((f) => ext.test(f)) : [];
};

/**
 * Scan JS file names (without extension) from subdirectories.
 * e.g., basePath/fx/accordion/fx-accordion.js → 'fx-accordion'
 */
const scanChunkNames = (basePath: string, subDir: string): string[] => {
	const p = `${basePath}/${subDir}`;
	if (!fs.existsSync(p)) return [];

	const folders = fs.readdirSync(p).filter((f) => fs.statSync(`${p}/${f}`).isDirectory());
	const names: string[] = [];

	for (const folder of folders) {
		const folderPath = `${p}/${folder}`;
		const jsFiles = fs.readdirSync(folderPath).filter((f) => f.endsWith('.js') || f.endsWith('.ts'));
		jsFiles.forEach((file) => names.push(file.replace(/\.(js|ts)$/, '')));
	}

	return names;
};

/**
 * Normalize path to use forward slashes
 */
const normalizePath = (p: string): string => p.replace(/\\/g, '/');

/**
 * Create imagemin plugin with default settings
 */
const createImageminPlugin = (): PluginOption => {
	return viteImagemin({
		plugins: {
			jpg: imageminMozjpeg({ quality: 85 }),
			png: imageminPngquant({ quality: [0.95, 1], speed: 1, strip: true, dithering: 1 }),
			svg: imageminSVGO(),
			gif: imageminGifsicle({ optimizationLevel: 3, interlaced: true }),
		},
		makeWebp: {
			plugins: {
				jpg: imageminWebp({ quality: 85 }),
				png: imageminWebp({ quality: 90, lossless: false }),
			},
		},
	});
};

// ==================== Main Plugin ====================

/**
 * WordPress Vite Plugin
 *
 * This plugin handles:
 * - Input entry points
 * - Output paths and naming
 * - Chunk detection and splitting
 * - Static image copying
 * - Image optimization (production only)
 *
 * You should add other plugins separately:
 * - tailwindcss() for Tailwind CSS
 */
export function wpVite(options: WPViteOptions): PluginOption[] {
	// Will be set in config hook
	let isProduction = false;

	// Resolve paths
	const basePath = normalizePath(path.resolve(options.basePath));
	const resourcesDir = options.resourcesDir ?? 'resources';
	const outDir = options.outDir ?? 'assets';
	const resources = `${basePath}/${resourcesDir}`;
	const assets = `${basePath}/${outDir}`;
	const libsDir = `${resources}/scripts/3rd`;
	const componentsDir = options.componentsDir ?? 'components';
	const comDir = `${resources}/${componentsDir}`;

	// Chunk directories
	const chunkDirs = options.chunkDirs ?? ['fx', 'plugins', 'modules'];
	const lazyNodeModules = options.lazyNodeModules ?? [];
	const enableChunkDetection = options.enableChunkDetection !== false;

	// Scan component files
	const comFiles = scanFiles(comDir, /\.(js|ts|scss)$/);

	// Build chunk names set
	const chunkNames = new Set([...chunkDirs.flatMap((dir) => scanChunkNames(libsDir, dir)), ...comFiles.map((f) => f.replace(/\.(js|ts|scss)$/, ''))]);

	const isChunk = (name: string) => enableChunkDetection && chunkNames.has(name);

	// Build input array
	const inputFiles: string[] = [];

	// Add JS files
	if (options.input.js) {
		inputFiles.push(...options.input.js.map((f) => `${resources}/scripts/${f}.js`));
	}

	// Add SCSS files
	if (options.input.scss) {
		inputFiles.push(...options.input.scss.map((f) => `${resources}/styles/${f}.scss`));
	}

	// Add extra files
	if (options.input.extra) {
		inputFiles.push(...options.input.extra.map((f) => `${resources}/${f}`));
	}

	// Add component files
	if (comFiles.length > 0) {
		inputFiles.push(...comFiles.map((f) => `${comDir}/${f}`));
	}

	// Default manualChunks logic
	const defaultManualChunks = (id: string): string | undefined => {
		// Skip lazy-loaded modules
		if (lazyNodeModules.some((m) => id.includes(`node_modules/${m}`))) {
			return undefined;
		}

		// Tailwind utilities
		if (id.includes('styles/tailwind')) {
			return 'tw';
		}

		// Shared 3rd utilities -> vendor
		const sharedUtils = ['3rd/dom.js', '3rd/events.js', '3rd/weak.js', '3rd/dom.ts', '3rd/events.ts', '3rd/weak.ts'];
		if (id.includes('node_modules') || sharedUtils.some((u) => id.includes(u))) {
			return 'vendor';
		}

		return undefined;
	};

	// Build plugins array
	const plugins: PluginOption[] = [];

	// Main plugin
	const mainPlugin: Plugin = {
		name: 'vite-plugin-wp',

		config(_config: UserConfig, env: ConfigEnv): UserConfig {
			isProduction = env.mode === 'production';

			// Build resolve.alias config
			const resolveAlias: Record<string, string> = {};
			if (options.jQueryAlias) {
				resolveAlias.jquery = normalizePath(path.resolve(options.jQueryAlias));
			}

			return {
				base: './',
				resolve: Object.keys(resolveAlias).length > 0 ? { alias: resolveAlias } : undefined,
				css: {
					preprocessorOptions: {
						scss: { api: 'modern-compiler', quietDeps: true } as any,
					},
					postcss: {
						plugins: [autoprefixer({ remove: false, flexbox: 'no-2009' })],
					},
				},
				build: {
					target: ['es2020', 'edge88', 'firefox78', 'chrome87', 'safari14'],
					sourcemap: !isProduction,
					manifest: true,
					minify: isProduction ? 'terser' : false,
					watch: isProduction ? null : { exclude: 'node_modules/**' },
					cssCodeSplit: true,
					emptyOutDir: true,
					assetsInlineLimit: 4096,
					chunkSizeWarningLimit: 500,
					outDir: assets,
					assetsDir: '',
					terserOptions: {
						compress: { drop_console: true, drop_debugger: true, toplevel: true, passes: 2 },
						format: { comments: false },
					},
					rollupOptions: {
						input: inputFiles,
						output: {
							entryFileNames: ({ name = '' }) => (isChunk(name) ? `js/chunk/[name].[hash].js` : `js/[name].[hash].js`),
							chunkFileNames: ({ name = '' }) => (isChunk(name) ? `js/chunk/[name].[hash].js` : `js/[name].[hash].js`),
							assetFileNames: (assetInfo: any) => {
								const ext = getAssetExt(assetInfo);
								const baseName = (assetInfo.names?.[0] ?? '').replace('.css', '');

								if (ext === 'css') {
									return isChunk(baseName) ? `css/chunk/[name].[hash].css` : `css/[name].[hash].css`;
								}

								return getAssetPath(ext) ?? defaultAssetFallback;
							},
							manualChunks: (id: string) => {
								// Use custom manualChunks if provided
								if (options.manualChunks) {
									const result = options.manualChunks(id, { isChunk });
									if (result !== undefined) return result;
								}
								return defaultManualChunks(id);
							},
						},
					},
				},
			};
		},
	};

	plugins.push(mainPlugin);

	// Image optimization (production only) - use apply function for conditional
	if (options.imageMin !== false) {
		const imageminPlugins = createImageminPlugin();
		if (Array.isArray(imageminPlugins)) {
			imageminPlugins.forEach((p) => {
				if (p && typeof p === 'object' && 'name' in p) {
					plugins.push({
						...p,
						apply: (_config, env) => env.mode === 'production',
					});
				}
			});
		}
	}

	// Static copy for images
	if (options.copyImages !== false && fs.existsSync(`${resources}/img`)) {
		plugins.push(
			viteStaticCopy({
				targets: [{ src: `${resources}/img`, dest: '' }],
			}),
		);
	}

	return plugins;
}

// Export helpers for advanced usage
export { EXT, getAssetExt, getAssetPath, defaultAssetFallback, scanFiles, scanChunkNames };
