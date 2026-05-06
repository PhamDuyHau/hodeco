<?php
/**
 * Theme Asset Manager
 *
 * This file defines the Asset class, a utility class responsible for managing
 * all CSS and JavaScript assets in the theme. It provides methods for collecting,
 * registering, and enqueueing styles and scripts efficiently.
 *
 * The class helps maintain a clean and optimized loading strategy for frontend
 * and backend assets.
 *
 * @author HD
 */

namespace HD\Core;

use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

final class Asset {
	// ----------------------------------------

	/**
	 * Return theme version helper passthrough.
	 *
	 * @return string|false
	 */
	public static function version(): string|false {
		return Helper::version();
	}

	// ----------------------------------------

	/**
	 * Resolve src from manifest
	 *
	 * @param string|null $entry
	 * @param bool $relativeLink
	 *
	 * @return string
	 */
	public static function src( ?string $entry = null, bool $relativeLink = false ): string {
		if ( ! $entry ) {
			return '';
		}

		$resolve = self::manifestResolve( $entry );
		$src     = $resolve['src'] ?? '';

		if ( ! $src ) {
			return '';
		}

		return $relativeLink ? str_replace( THEME_URL, '', $src ) : $src;
	}

	// ----------------------------------------

	/**
	 * Resolve handle for manifest entry
	 *
	 * @param string|null $entry
	 * @param string $handlePrefix
	 *
	 * @return string
	 */
	public static function handle( ?string $entry = null, string $handlePrefix = '' ): string {
		if ( ! $entry ) {
			return '';
		}

		return self::manifestResolve( $entry, $handlePrefix )['handle'] ?? '';
	}

	// ----------------------------------------

	/**
	 * Preload JS imports (modulepreload).
	 *
	 * @param string|null $entry
	 * @param string $handlePrefix
	 *
	 * @return void
	 */
	public static function preload( ?string $entry = null, string $handlePrefix = '' ): void {
		if ( ! $entry ) {
			return;
		}

		$tmp     = self::manifestResolve( $entry, $handlePrefix );
		$imports = [ 'vendor.js', ...( (array) ( $tmp['imports'] ?? [] ) ) ];
		$imports = array_unique( $imports );

		$resolved = [];
		$added    = [];
		$links    = '';

		foreach ( $imports as $import ) {
			$resolved[ $import ] ??= self::manifestResolve( $import );
			$resolve               = $resolved[ $import ];

			if ( empty( $resolve['src'] ) ) {
				continue;
			}

			$href = esc_url( $resolve['src'] );
			if ( isset( $added[ $href ] ) ) {
				continue;
			}

			$added[ $href ] = true;
			$links         .= sprintf( '<link rel="modulepreload" href="%s" as="script" type="module" crossorigin>', $href );
		}

		echo wp_kses(
			$links,
			[
				'link' => [
					'rel'         => [],
					'href'        => [],
					'as'          => [],
					'type'        => [],
					'crossorigin' => [],
				],
			]
		);
	}

	// ----------------------------------------

	/**
	 * Preload CSS file (non-critical CSS)
	 *
	 * @param string|null $entry
	 *
	 * @return void
	 */
	public static function preloadCSS( ?string $entry = null ): void {
		if ( ! $entry ) {
			return;
		}

		$resolve = self::manifestResolve( $entry );
		if ( empty( $resolve['src'] ) ) {
			return;
		}

		$href = esc_url( $resolve['src'] );
		$tag  = sprintf( '<link rel="preload" href="%s" as="style" onload="this.rel=\'stylesheet\'">', $href );

		echo wp_kses(
			$tag,
			[
				'link' => [
					'rel'    => [],
					'href'   => [],
					'as'     => [],
					'onload' => [],
				],
			]
		);
	}

	// ----------------------------------------

	/**
	 * Enqueue JS by manifest entry
	 *
	 * @param string|null $entry
	 * @param array $deps
	 * @param string|bool|null $ver
	 * @param bool $inFooter
	 * @param array $extra
	 *
	 * @return void
	 */
	public static function enqueueJS( ?string $entry = null, array $deps = [], string|bool|null $ver = null, bool $inFooter = true, array $extra = [] ): void {
		if ( ! $entry ) {
			return;
		}

		$resolve = self::manifestResolve( $entry );
		if ( ! $resolve ) {
			return;
		}

		$resolve['deps']      = $deps;
		$resolve['ver']       = $ver;
		$resolve['in_footer'] = $inFooter;
		$resolve['extra']     = $extra;

		self::enqueueScript( $resolve );

		// CSS dependencies from JS manifest
		if ( empty( $resolve['css'] ) || ! is_array( $resolve['css'] ) ) {
			return;
		}

		foreach ( $resolve['css'] as $key => $cssFile ) {
			$replacement = $key === 0 ? '-css' : '-' . $key . '-css';
			$handle      = preg_replace( '/-js$/', $replacement, $resolve['handle'] );

			// Skip if already enqueued or vendor CSS
			if ( wp_style_is( $handle )
				|| wp_style_is( $handle, 'registered' )
				|| str_contains( $cssFile, 'vendor.' )
			) {
				continue;
			}

			// Skip tailwind.css if already enqueued via PHP
			if ( ( str_contains( $cssFile, 'tailwind.' ) || str_contains( $cssFile, 'tw.' ) )
				&& wp_style_is( self::handle( 'tailwind.css' ) )
			) {
				continue;
			}

			$src     = THEME_URL . 'assets/' . $cssFile;
			$cssDeps = in_array( $entry, [ 'index.js', 'admin.js' ], true ) ? [] : [ self::handle( 'main.scss' ) ];

			self::enqueueStyle(
				[
					'handle' => $handle,
					'src'    => $src,
					'deps'   => $cssDeps,
					'ver'    => $ver,
					'media'  => 'all',
				]
			);
		}
	}

	// ----------------------------------------

	/**
	 * Enqueue CSS by manifest entry
	 *
	 * @param string|null $entry
	 * @param array $deps
	 * @param string|bool|null $ver
	 * @param string $media
	 *
	 * @return void
	 */
	public static function enqueueCSS( ?string $entry = null, array $deps = [], string|bool|null $ver = null, string $media = 'all' ): void {
		if ( ! $entry ) {
			return;
		}

		$resolve = self::manifestResolve( $entry );
		if ( ! $resolve ) {
			return;
		}

		$resolve['deps']  = $deps;
		$resolve['ver']   = $ver;
		$resolve['media'] = $media;

		self::enqueueStyle( $resolve );
	}

	// ----------------------------------------

	/**
	 * Enqueue style helper (supports array or scalar args)
	 *
	 * @param string|array $handle
	 * @param string|bool|null $src
	 * @param array $deps
	 * @param string|bool|null $ver
	 * @param string $media
	 *
	 * @return void
	 */
	public static function enqueueStyle( string|array $handle, string|bool|null $src = null, array $deps = [], string|bool|null $ver = null, string $media = 'all' ): void {
		$args = is_array( $handle )
			? wp_parse_args(
				$handle,
				[
					'handle' => '',
					'src'    => null,
					'deps'   => [],
					'ver'    => null,
					'media'  => 'all',
				]
			)
			: [
				'handle' => $handle,
				'src'    => $src,
				'deps'   => $deps,
				'ver'    => $ver,
				'media'  => $media,
			];

		if ( ! $args['handle'] || ! $args['src'] ) {
			return;
		}

		if ( ! wp_style_is( $args['handle'], 'registered' ) ) {
			wp_register_style( $args['handle'], $args['src'], $args['deps'], $args['ver'], $args['media'] );
		}

		wp_enqueue_style( $args['handle'] );
	}

	// ----------------------------------------

	/**
	 * Enqueue script helper (supports script attributes via 'extra' or 'attrs')
	 *
	 * @param string|array $handle
	 * @param string|bool|null $src
	 * @param array $deps
	 * @param string|bool|null $ver
	 * @param bool $inFooter
	 * @param array $extra
	 *
	 * @return void
	 */
	public static function enqueueScript( string|array $handle, string|bool|null $src = null, array $deps = [], string|bool|null $ver = null, bool $inFooter = true, array $extra = [] ): void {
		if ( is_array( $handle ) ) {
			$args = wp_parse_args(
				$handle,
				[
					'handle'    => '',
					'src'       => null,
					'url'       => null,
					'deps'      => [],
					'ver'       => null,
					'in_footer' => true,
					'extra'     => [],
					'attr'      => [],
				]
			);

			// Fallbacks
			$args['src']   = $args['src'] ?: $args['url'];
			$args['extra'] = $args['extra'] ?: $args['attr'];
		} else {
			$args = [
				'handle'    => $handle,
				'src'       => $src,
				'deps'      => $deps,
				'ver'       => $ver,
				'in_footer' => $inFooter,
				'extra'     => $extra,
			];
		}

		if ( ! $args['handle'] || ! $args['src'] ) {
			return;
		}

		if ( ! wp_script_is( $args['handle'], 'registered' ) ) {
			wp_register_script( $args['handle'], $args['src'], $args['deps'], $args['ver'], (bool) $args['in_footer'] );
		}

		wp_enqueue_script( $args['handle'] );

		if ( $args['extra'] ) {
			wp_script_add_data( $args['handle'], 'extra', $args['extra'] );
		}
	}

	// ----------------------------------------

	/**
	 * Localize JS data safely.
	 *
	 * @param string $handle
	 * @param string $objectName
	 * @param array|bool|null $l10n
	 * @param bool $asInlineJson If true, use wp_add_inline_script(json) instead of wp_localize_script
	 *
	 * @return void
	 */
	public static function localize( string $handle, string $objectName, array|bool|null $l10n, bool $asInlineJson = true ): void {
		if ( ! $objectName || ! $l10n ) {
			return;
		}

		if ( ! wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle ) ) {
			return;
		}

		if ( $asInlineJson ) {
			$json = wp_json_encode( $l10n );
			if ( $json === false ) {
				return;
			}
			wp_add_inline_script( $handle, sprintf( 'window.%s = %s;', $objectName, $json ), 'before' );
		} else {
			wp_localize_script( $handle, $objectName, $l10n );
		}
	}

	// ----------------------------------------

	/**
	 * Add inline CSS safely.
	 *
	 * @param string $handle
	 * @param string|bool|null $css
	 *
	 * @return void
	 */
	public static function inlineStyle( string $handle, string|null|bool $css ): void {
		if ( ! $css ) {
			return;
		}

		if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle ) ) {
			wp_add_inline_style( $handle, $css );
		} else {
			$fallback = 'inline-style-' . md5( $handle );
			wp_register_style( $fallback, false, [], self::version(), true );
			wp_enqueue_style( $fallback );
			wp_add_inline_style( $fallback, $css );
		}
	}

	// ----------------------------------------

	/**
	 * Add inline JS safely.
	 *
	 * @param string $handle
	 * @param string|bool|null $code
	 * @param string $position
	 *
	 * @return void
	 */
	public static function inlineScript( string $handle, string|null|bool $code, string $position = 'after' ): void {
		if ( ! $code ) {
			return;
		}

		if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle ) ) {
			wp_add_inline_script( $handle, $code, $position );
		} else {
			$fallback = 'inline-script-' . md5( $handle );
			wp_register_script( $fallback, false, [], self::version(), true );
			wp_enqueue_script( $fallback );
			wp_add_inline_script( $fallback, $code, $position );
		}
	}

	// ----------------------------------------

	/**
	 * Resolve a given asset entry from the Vite manifest.
	 *
	 * @param string|null $entry Ex: 'pages/page-home.js', 'index.scss', 'vendor.js', 'vendor.css'.
	 * @param string $handlePrefix
	 *
	 * @return array
	 */
	private static function manifestResolve( ?string $entry = null, string $handlePrefix = '' ): array {
		static $resolveCache = [];

		if ( ! is_string( $entry ) || ! trim( $entry ) ) {
			return [];
		}

		$key = $entry . '|' . $handlePrefix;
		if ( isset( $resolveCache[ $key ] ) ) {
			return $resolveCache[ $key ];
		}

		$manifest = self::manifest();
		if ( ! $manifest ) {
			$resolveCache[ $key ] = [];

			return $resolveCache[ $key ];
		}

		// Helper closure

		$makeHandle = static fn( $b, $kind ) => $handlePrefix . $b . '-' . $kind;

		// Normalize input
		$entry = wp_normalize_path( trim( $entry ) );

		// Vendor JS
		if ( preg_match( '/^_?vendor(\..+)?\.js$/', $entry ) ) {
			$resolveCache[ $key ] = self::resolveVendor( $manifest, $makeHandle, 'js' );

			return $resolveCache[ $key ];
		}

		// Vendor CSS
		if ( preg_match( '/^_?vendor(\..+)?\.css$/', $entry ) ) {
			$resolveCache[ $key ] = self::resolveVendor( $manifest, $makeHandle, 'css' );

			return $resolveCache[ $key ];
		}

		// Tailwind CSS
		if ( preg_match( '/^_?(tailwind|tw)(\..+)?\.css$/', $entry ) ) {
			$resolveCache[ $key ] = self::resolveTailwind( $manifest, $makeHandle );

			return $resolveCache[ $key ];
		}

		// Regular Entries
		$ext       = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
		$pathNoExt = preg_replace( '/\.' . preg_quote( $ext, '/' ) . '$/i', '', $entry );
		$baseSlug  = self::makeSlugFromPath( $pathNoExt );
		$isCss     = in_array( $ext, [ 'css', 'scss' ], true );
		$isJs      = ( $ext === 'js' );

		if ( ! $isCss && ! $isJs ) {
			$resolveCache[ $key ] = [];

			return $resolveCache[ $key ];
		}

		$srcTailCandidates = $isCss
			? [ $pathNoExt . '.scss', $pathNoExt . '.css' ]
			: [ $pathNoExt . '.js' ];

		$found = null;
		foreach ( $manifest as $k => $item ) {
			if ( ! is_array( $item )
				|| empty( $item['isEntry'] )
				|| empty( $item['src'] )
				|| ! is_string( $item['src'] )
				|| preg_match( '/^_?vendor\..+\.(js|css)$/', $k )
			) {
				continue;
			}

			foreach ( $srcTailCandidates as $tail ) {
				if ( str_ends_with( $item['src'], $tail ) ) {
					$found = $item;
					break 2;
				}
			}
		}

		if ( ! $found ) {
			$resolveCache[ $key ] = [];

			return $resolveCache[ $key ];
		}

		// JS entry
		if ( $isJs && ! empty( $found['file'] ) ) {
			$resolveCache[ $key ] = [
				'ext'     => 'js',
				'handle'  => $makeHandle( $baseSlug, 'js' ),
				'src'     => THEME_URL . 'assets/' . $found['file'],
				'file'    => $found['src'] ?? '',
				'imports' => $found['imports'] ?? [],
				'css'     => $found['css'] ?? [],
			];

			return $resolveCache[ $key ];
		}

		// CSS entry
		if ( $isCss ) {
			$file = $found['css'][0] ?? $found['file'] ?? null;
			if ( $file ) {
				$resolveCache[ $key ] = [
					'ext'    => 'css',
					'handle' => $makeHandle( $baseSlug, 'css' ),
					'src'    => THEME_URL . 'assets/' . $file,
					'file'   => $found['src'] ?? '',
				];

				return $resolveCache[ $key ];
			}
		}

		$resolveCache[ $key ] = [];

		return $resolveCache[ $key ];
	}

	// ----------------------------------------

	/**
	 * @param array $manifest
	 * @param callable $makeHandle
	 * @param string $type
	 *
	 * @return array
	 */
	private static function resolveVendor( array $manifest, callable $makeHandle, string $type ): array {
		$pattern = '/^_?vendor\..+\.' . $type . '$/';

		foreach ( $manifest as $k => $v ) {
			if ( is_array( $v ) && preg_match( $pattern, $k ) ) {
				$file = $v['file'] ?? '';
				if ( ! $file ) {
					return [];
				}

				return [
					'ext'    => $type,
					'handle' => $makeHandle( 'vendor', $type ),
					'src'    => THEME_URL . 'assets/' . $file,
					'file'   => $v['src'] ?? '',
					'css'    => $v['css'] ?? [],
				];
			}
		}

		// CSS fallback from JS vendor
		if ( $type === 'css' ) {
			foreach ( $manifest as $k => $v ) {
				if ( ! empty( $v['css'][0] ) && preg_match( '/^_?vendor\..+\.js$/', $k ) ) {
					return [
						'ext'    => 'css',
						'handle' => $makeHandle( 'vendor', 'css' ),
						'src'    => THEME_URL . 'assets/' . $v['css'][0],
					];
				}
			}
		}

		return [];
	}

	// ----------------------------------------

	/**
	 * @param array $manifest
	 * @param callable $makeHandle
	 *
	 * @return array
	 */
	private static function resolveTailwind( array $manifest, callable $makeHandle ): array {
		foreach ( $manifest as $k => $v ) {
			if ( is_array( $v ) && preg_match( '/^_?(tailwind|tw)\..+\.css$/', $k ) ) {
				$file = $v['file'] ?? '';
				if ( ! $file ) {
					return [];
				}

				return [
					'ext'    => 'css',
					'handle' => $makeHandle( 'tw', 'css' ),
					'src'    => THEME_URL . 'assets/' . $file,
					'file'   => $v['src'] ?? '',
				];
			}
		}

		return [];
	}

	// ----------------------------------------

	/**
	 * @return array
	 */
	private static function manifest(): array {
		static $cache = [];

		$manifestBase = rtrim( THEME_PATH, '/\\' ) . '/assets/';
		$manifestPath = $manifestBase . '.vite/manifest.json';
		$key          = 'manifest:' . $manifestPath;

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		if ( ! is_readable( $manifestPath ) || ! is_file( $manifestPath ) ) {
			$cache[ $key ] = [];

			return $cache[ $key ];
		}

		// Transient + filemtime
		$transientKey = 'theme_manifest_filtered_' . md5( $manifestPath );
		$fileMtime    = filemtime( $manifestPath ) ?: 0;
		$cached       = get_transient( $transientKey );

		if ( is_array( $cached ) && ( $cached['mtime'] ?? 0 ) === $fileMtime ) {
			$cache[ $key ] = $cached['data'] ?? [];

			return $cache[ $key ];
		}

		// Parse JSON
		$data = wp_json_file_decode(
			$manifestPath,
			[
				'associative' => true,
				'depth'       => 512,
			]
		);
		if ( is_wp_error( $data ) ) {
			Helper::errorLog( '[manifest] JSON decode error at ' . $manifestPath . ': ' . $data->get_error_message() );
			$cache[ $key ] = [];

			return $cache[ $key ];
		}

		if ( ! is_array( $data ) ) {
			$cache[ $key ] = [];

			return $cache[ $key ];
		}

		// Filter manifest
		$filtered   = [];
		$keepFields = [ 'file', 'name', 'src', 'css', 'isEntry', 'imports' ];

		foreach ( $data as $entryKey => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$isVendor = preg_match( '/^_?(vendor|tailwind|tw)\..+\.(js|css)$/', (string) $entryKey ) === 1;
			$isEntry  = ! empty( $entry['isEntry'] );

			if ( ! $isVendor && ! $isEntry ) {
				continue;
			}

			$filtered[ $entryKey ] = array_intersect_key( $entry, array_flip( $keepFields ) );
		}

		// Transient cache (1 day)
		set_transient(
			$transientKey,
			[
				'mtime' => $fileMtime,
				'data'  => $filtered,
			],
			DAY_IN_SECONDS
		);

		$cache[ $key ] = $filtered;

		return $cache[ $key ];
	}

	// ----------------------------------------

	/**
	 * Convert a file path (without extension) into a URL-safe slug.
	 *
	 * @param string $pathNoExt
	 *
	 * @return string
	 */
	private static function makeSlugFromPath( string $pathNoExt ): string {
		$pathNoExt = str_replace( '\\', '/', $pathNoExt );
		$pathNoExt = preg_replace( '#/+#', '/', $pathNoExt );
		$pathNoExt = trim( $pathNoExt, '/' );
		$slug      = strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $pathNoExt ) );

		return trim( $slug, '-' ) ?: 'entry';
	}
}
