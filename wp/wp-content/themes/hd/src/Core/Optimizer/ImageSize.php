<?php
/**
 * Image Size Configuration
 *
 * Manages custom image sizes and disables unwanted default sizes.
 *
 * @package HD\Core\Optimizer
 * @author  HD
 */

namespace HD\Core\Optimizer;

use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

final class ImageSize {

	/**
	 * Configure image sizes.
	 *
	 * @return void
	 */
	public static function run(): void {
		self::configureDefaultSizes();
		self::configureMediaDefaults();
		self::addCustomSizes();
		self::disableUnwantedSizes();
	}

	/** ---------------------------------------- */

	/**
	 * Configure default WordPress image sizes (one-time).
	 *
	 * @return void
	 */
	private static function configureDefaultSizes(): void {
		if ( Helper::getOption( '_image_sizes_updated' ) ) {
			return;
		}

		Helper::updateOption( '_image_sizes_updated', true );

		// Default thumbnail: width 480, proportional height
		Helper::updateOption( 'thumbnail_size_w', 480 );
		Helper::updateOption( 'thumbnail_size_h', 0 );
		Helper::updateOption( 'thumbnail_crop', 0 );

		// Medium size: width 768, proportional height
		Helper::updateOption( 'medium_size_w', 768 );
		Helper::updateOption( 'medium_size_h', 0 );

		// Large size: width 1024, proportional height
		Helper::updateOption( 'large_size_w', 1024 );
		Helper::updateOption( 'large_size_h', 0 );
	}

	/** ---------------------------------------- */

	/**
	 * Set media upload defaults (one-time).
	 *
	 * @return void
	 */
	private static function configureMediaDefaults(): void {
		if ( Helper::getOption( '_media_defaults_updated' ) ) {
			return;
		}

		Helper::updateOption( '_media_defaults_updated', true );
		Helper::updateOption( 'image_default_align', 'center' );
		Helper::updateOption( 'image_default_size', 'large' );
	}

	/** ---------------------------------------- */

	/**
	 * Add custom image sizes.
	 *
	 * Available sizes:
	 * - small-50 (50x0)
	 * - small-100 (100x0)
	 * - small-150 (150x0)
	 * - small-300 (300x0)
	 * - small-thumbnail (150x0)
	 * - widescreen (1920x0)
	 * - post-thumbnail (1200x0)
	 * - og-image (1200x0) - for Open Graph & Twitter Card (proportional height)
	 *
	 * @return void
	 */
	private static function addCustomSizes(): void {
		$sizes = [
			'small-50'        => [ 50, 0 ],
			'small-100'       => [ 100, 0 ],
			'small-150'       => [ 150, 0 ],
			'small-300'       => [ 300, 0 ],
			'small-thumbnail' => [ 150, 0 ],
			'widescreen'      => [ 1920, 0 ],
			'post-thumbnail'  => [ 1200, 0 ],
			'og-image'        => [ 1200, 0 ], // For Open Graph & Twitter Card (width 1200, proportional height)
		];

		foreach ( $sizes as $name => $config ) {
			$width  = $config[0];
			$height = $config[1];
			$crop   = $config[2] ?? false;
			add_image_size( $name, $width, $height, $crop );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Disable unwanted WordPress image sizes.
	 *
	 * @return void
	 */
	private static function disableUnwantedSizes(): void {
		// Disable unwanted sizes from generation
		add_filter(
			'intermediate_image_sizes_advanced',
			static function ( array $sizes ): array {
				unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'] );

				return $sizes;
			}
		);

		// Disable scaled images
		add_filter( 'big_image_size_threshold', '__return_false' );
	}
}
