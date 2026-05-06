<?php
/**
 * Vite manifest utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Vite {
	/** @var string Static cache key for manifest data */
	private const string MANIFEST_CACHE_KEY = 'manifest:hda';

	/** @var string Transient key for manifest data */
	private const string MANIFEST_TRANSIENT_KEY = 'hda_manifest';

	/** @var array Static cache for manifest data */
	private static array $manifestCache = [];

	/** @var array Static cache for resolved entries */
	private static array $resolveCache = [];

	// --------------------------------------------------

	/**
	 * Get Vite manifest data with versioned caching.
	 *
	 * @return array
	 */
	public static function manifest(): array {
		$manifestPath = rtrim( HDA_PATH, '/\\' ) . '/assets/.vite/manifest.json';
		$cacheKey     = self::MANIFEST_CACHE_KEY;

		if ( isset( self::$manifestCache[ $cacheKey ] ) ) {
			return self::$manifestCache[ $cacheKey ];
		}

		if ( ! is_readable( $manifestPath ) || ! is_file( $manifestPath ) ) {
			self::$manifestCache[ $cacheKey ] = [];

			return self::$manifestCache[ $cacheKey ];
		}

		$transientKey = self::MANIFEST_TRANSIENT_KEY;
		$fileMtime    = filemtime( $manifestPath ) ?: 0;
		$cached       = get_transient( $transientKey );

		if ( is_array( $cached ) && ( $cached['mtime'] ?? 0 ) === $fileMtime ) {
			self::$manifestCache[ $cacheKey ] = $cached['data'] ?? [];

			return self::$manifestCache[ $cacheKey ];
		}

		$data = wp_json_file_decode(
			$manifestPath,
			[
				'associative' => true,
				'depth'       => 512,
			]
		);

		if ( ! is_array( $data ) || is_wp_error( $data ) ) {
			self::$manifestCache[ $cacheKey ] = [];

			return self::$manifestCache[ $cacheKey ];
		}

		$filtered = self::filterManifestEntries( $data );
		set_transient(
			$transientKey,
			[
				'mtime' => $fileMtime,
				'data'  => $filtered,
			],
			DAY_IN_SECONDS
		);

		self::$manifestCache[ $cacheKey ] = $filtered;

		return self::$manifestCache[ $cacheKey ];
	}

	// --------------------------------------------------

	/**
	 * Filter manifest entries.
	 */
	private static function filterManifestEntries( array $data ): array {
		$filtered   = [];
		$keepFields = [ 'file', 'name', 'src', 'css', 'isEntry', 'imports' ];

		foreach ( $data as $entryKey => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$isVendor = preg_match( '/^_?vendor\..+\.(js|css)$/', (string) $entryKey ) === 1;
			$isEntry  = ! empty( $entry['isEntry'] );

			if ( $isVendor || $isEntry ) {
				$filtered[ $entryKey ] = array_intersect_key( $entry, array_flip( $keepFields ) );
			}
		}

		return $filtered;
	}

	// --------------------------------------------------

	/**
	 * Resolve entry from Vite manifest.
	 *
	 * @param string|null $entry
	 * @param string $handlePrefix
	 *
	 * @return array
	 */
	public static function manifestResolve( ?string $entry = null, string $handlePrefix = 'hda-' ): array {
		if ( ! $entry || ! trim( $entry ) ) {
			return [];
		}

		$cacheKey = $entry . '|' . $handlePrefix;
		if ( isset( self::$resolveCache[ $cacheKey ] ) ) {
			return self::$resolveCache[ $cacheKey ];
		}

		$manifest = self::manifest();
		if ( ! $manifest ) {
			self::$resolveCache[ $cacheKey ] = [];

			return self::$resolveCache[ $cacheKey ];
		}

		$entry = trim( wp_normalize_path( $entry ) );

		// Check vendor entries
		if ( preg_match( '/^_?vendor(\..+)?\.(js|css)$/', $entry, $m ) ) {
			self::$resolveCache[ $cacheKey ] = self::resolveVendor( $manifest, $m[2], $handlePrefix );

			return self::$resolveCache[ $cacheKey ];
		}

		// Regular entries
		self::$resolveCache[ $cacheKey ] = self::resolveRegularEntry( $manifest, $entry, $handlePrefix );

		return self::$resolveCache[ $cacheKey ];
	}

	// --------------------------------------------------

	private static function resolveVendor( array $manifest, string $ext, string $prefix ): array {
		$pattern = '/^_?vendor\..+\.' . $ext . '$/';

		foreach ( $manifest as $k => $v ) {
			if ( is_array( $v ) && preg_match( $pattern, $k ) && ! empty( $v['file'] ) ) {
				return [
					'handle' => $prefix . 'vendor-' . $ext,
					'src'    => HDA_URL . 'assets/' . $v['file'],
					'file'   => $v['src'] ?? '',
				];
			}
		}

		// Fallback for CSS from JS vendor
		if ( $ext === 'css' ) {
			foreach ( $manifest as $k => $v ) {
				if ( ! empty( $v['css'][0] ) && preg_match( '/^_?vendor\..+\.js$/', $k ) ) {
					return [
						'handle' => $prefix . 'vendor-css',
						'src'    => HDA_URL . 'assets/' . $v['css'][0],
					];
				}
			}
		}

		return [];
	}

	// --------------------------------------------------

	private static function resolveRegularEntry( array $manifest, string $entry, string $prefix ): array {
		$ext       = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
		$pathNoExt = preg_replace( '/\.' . preg_quote( $ext, '/' ) . '$/i', '', $entry );
		$isCss     = in_array( $ext, [ 'css', 'scss' ], true );
		$isJs      = $ext === 'js';

		if ( ! $isCss && ! $isJs ) {
			return [];
		}

		$srcCandidates = $isCss ? [ $pathNoExt . '.scss', $pathNoExt . '.css' ] : [ $pathNoExt . '.js' ];

		$found = null;
		foreach ( $manifest as $k => $item ) {
			if ( ! is_array( $item ) || empty( $item['isEntry'] ) || empty( $item['src'] ) ) {
				continue;
			}
			if ( preg_match( '/^_?vendor\..+\.(js|css)$/', $k ) ) {
				continue;
			}

			foreach ( $srcCandidates as $tail ) {
				if ( str_ends_with( $item['src'], $tail ) ) {
					$found = $item;
					break 2;
				}
			}
		}

		if ( ! $found ) {
			return [];
		}

		$slug   = strtolower( preg_replace( '/[^a-z0-9]+/i', '-', trim( str_replace( [ '\\', '/' ], '-', $pathNoExt ), '-' ) ) ) ?: 'entry';
		$handle = $prefix . $slug . '-' . ( $isJs ? 'js' : 'css' );

		if ( $isJs && ! empty( $found['file'] ) ) {
			return [
				'handle'  => $handle,
				'src'     => HDA_URL . 'assets/' . $found['file'],
				'file'    => $found['src'] ?? '',
				'imports' => $found['imports'] ?? [],
				'css'     => $found['css'] ?? [],
			];
		}

		if ( $isCss ) {
			$file = $found['css'][0] ?? $found['file'] ?? null;
			if ( $file ) {
				return [
					'handle' => $handle,
					'src'    => HDA_URL . 'assets/' . $file,
					'file'   => $found['src'] ?? '',
				];
			}
		}

		return [];
	}
}
