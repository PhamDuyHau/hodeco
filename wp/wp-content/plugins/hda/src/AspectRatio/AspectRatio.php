<?php
/**
 * Aspect Ratio — per-post-type aspect ratio control via inline CSS.
 *
 * Generates custom `aspect-ratio` CSS rules for post types configured
 * in theme settings. Predefined ratios (from theme CSS) are skipped.
 *
 * @package HDAddons\AspectRatio
 * @author  HD
 */

namespace HDAddons\AspectRatio;

use HDAddons\Asset;
use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\CSS;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class AspectRatio implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'aspect_ratio__options';

	/** Key used with filterSettingOptions() */
	public const string SETTINGS_FILTER = 'aspect_ratio';

	/** Sub-keys within the filter settings */
	public const string SETTING_POST_TYPE_TERM  = 'post_type_term';
	public const string SETTING_DEFAULT_RATIOS  = 'aspect_ratio_default';

	/**
	 * Default aspect ratio class when no custom ratio is set.
	 */
	private const string DEFAULT_RATIO_CLASS = 'as-3-2';

	/**
	 * Default style handle for inline CSS.
	 */
	private const string DEFAULT_STYLE_HANDLE = 'index-css';

	/**
	 * Cached settings from config.
	 */
	private array $settings;

	/**
	 * User-defined aspect ratio options.
	 */
	private array $options;

	/**
	 * List of predefined aspect ratios.
	 */
	private array $defaultRatios;

	// --------------------------------------------------

	/**
	 * Initialize aspect ratio functionality.
	 */
	public function __construct() {
		$this->settings      = Helper::filterSettingOptions( self::SETTINGS_FILTER );
		$this->options       = Helper::getOption( self::OPTION_NAME, [] );
		$this->defaultRatios = $this->settings[ self::SETTING_DEFAULT_RATIOS ] ?? [];

		add_action( 'wp_enqueue_scripts', $this->enqueueAspectRatioStyles( ... ), 39 );
	}

	// --------------------------------------------------

	/**
	 * Enqueue inline CSS for custom aspect ratios.
	 *
	 * @return void
	 */
	public function enqueueAspectRatioStyles(): void {
		$postTypes = $this->settings[ self::SETTING_POST_TYPE_TERM ] ?? [];
		if ( empty( $postTypes ) ) {
			return;
		}

		$processedClasses = [];
		$cssRules         = [];

		foreach ( $postTypes as $postType ) {
			$ratioData = $this->buildRatioData( $postType );

			// Skip if no custom style or already processed
			if ( empty( $ratioData['style'] ) || isset( $processedClasses[ $ratioData['class'] ] ) ) {
				continue;
			}

			$processedClasses[ $ratioData['class'] ] = true;
			$cssRules[]                              = $ratioData['style'];
		}

		if ( ! empty( $cssRules ) ) {
			$handle = apply_filters( 'hda_aspect_ratio_style_handle', self::DEFAULT_STYLE_HANDLE );
			Asset::inlineStyle( $handle, implode( '', $cssRules ) );
		}
	}

	// --------------------------------------------------

	/**
	 * Build ratio class and style for a post type.
	 *
	 * @param string $postType Post type or taxonomy slug.
	 *
	 * @return array{class: string, style: string}
	 */
	private function buildRatioData( string $postType ): array {
		$ratio = $this->getRatio( $postType );

		if ( null === $ratio ) {
			return [
				'class' => self::DEFAULT_RATIO_CLASS,
				'style' => '',
			];
		}

		[ $width, $height ] = $ratio;
		$ratioClass = "as-{$width}-{$height}";
		$ratioKey   = "{$width}-{$height}";

		// No custom CSS needed if it's a predefined ratio
		if ( $this->isDefaultRatio( $ratioKey ) ) {
			return [
				'class' => $ratioClass,
				'style' => '',
			];
		}

		// Generate custom CSS
		$css = new CSS();
		$css->setSelector( ".{$ratioClass}" )
		    ->addProperty( 'aspect-ratio', "{$width}/{$height}" );

		return [
			'class' => $ratioClass,
			'style' => $css->cssOutput(),
		];
	}

	// --------------------------------------------------

	/**
	 * Get aspect ratio values for a post type.
	 *
	 * @param string $postType Post type or taxonomy slug.
	 *
	 * @return array{0: int, 1: int}|null [width, height] or null if not set.
	 */
	private function getRatio( string $postType ): ?array {
		$width  = $this->options[ "as-{$postType}-width" ] ?? null;
		$height = $this->options[ "as-{$postType}-height" ] ?? null;

		if ( empty( $width ) || empty( $height ) ) {
			return null;
		}

		return [ (int) $width, (int) $height ];
	}

	// --------------------------------------------------

	/**
	 * Check if the ratio is predefined in theme CSS.
	 *
	 * @param string $ratioKey Format: "width-height" (e.g., "16-9").
	 *
	 * @return bool
	 */
	private function isDefaultRatio( string $ratioKey ): bool {
		return in_array( $ratioKey, $this->defaultRatios, true );
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'aspect-ratio-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$options  = [];
		$settings = Helper::filterSettingOptions( self::SETTINGS_FILTER, [] );

		foreach ( $settings[ self::SETTING_POST_TYPE_TERM ] ?? [] as $ar ) {
			if ( isset( $data[ $ar . '-width' ], $data[ $ar . '-height' ] ) ) {
				$options[ 'as-' . $ar . '-width' ]  = absint( $data[ $ar . '-width' ] );
				$options[ 'as-' . $ar . '-height' ] = absint( $data[ $ar . '-height' ] );
			}
		}

		self::saveOrRemove( self::OPTION_NAME, $options );
	}
}
