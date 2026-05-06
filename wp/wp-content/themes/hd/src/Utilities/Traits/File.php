<?php
/**
 * File system utility trait.
 *
 * Provides file reading, writing, and manipulation utilities.
 *
 * @package HD\Utilities\Traits
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait File {
	// --------------------------------------------------

	/**
	 * @return \WP_Filesystem_Base|null
	 */
	private static function wpFileSystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 *
	 * @return string|null
	 */
	public static function fileRead( string $path ): ?string {
		$fs = self::wpFileSystem();

		if ( ! $fs ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file fallback when WP_Filesystem unavailable.
			return is_file( $path ) ? file_get_contents( $path ) : null;
		}

		return $fs->is_file( $path ) ? $fs->get_contents( $path ) : null;
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param string $content
	 * @param bool $lock
	 *
	 * @return bool
	 */
	public static function fileWrite( string $path, string $content, bool $lock = false ): bool {
		$fs = self::wpFileSystem();
		if ( $fs ) {
			return $fs->put_contents( $path, $content, FS_CHMOD_FILE );
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions
		if ( ! $lock ) {
			return (bool) file_put_contents( $path, $content );
		}

		$fp = fopen( $path, 'cb' );
		if ( ! $fp ) {
			return false;
		}

		flock( $fp, LOCK_EX );
		ftruncate( $fp, 0 ); // Clear existing content to avoid leftover data
		fwrite( $fp, $content );
		fflush( $fp );
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return true;
		// phpcs:enable
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function deleteFile( string $path ): bool {
		$fs = self::wpFileSystem();
		if ( $fs ) {
			return $fs->exists( $path ) && $fs->delete( $path, false, 'f' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Local file fallback when WP_Filesystem unavailable.
		return is_file( $path ) && unlink( $path );
	}
}
