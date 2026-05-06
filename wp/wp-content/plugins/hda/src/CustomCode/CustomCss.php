<?php
/**
 * Custom CSS module - outputs user-defined CSS to frontend.
 *
 * @author HD
 */

namespace HDAddons\CustomCode;

use HDAddons\Asset;
use HDAddons\Contracts\SettingsAware;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CustomCss implements SettingsAware {

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'hda_css';

	/** HTML form field name used by the handler */
	public const string KEY_FORM_CSS = 'html_custom_css';

	/**
	 * Default style handle for inline CSS.
	 */
	private const string DEFAULT_STYLE_HANDLE = 'index-css';

	// ------------------------------------------------------

	/**
	 * Initialize custom CSS output.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', $this->enqueueInlineCustomCss( ... ), 99 );
	}

	// ------------------------------------------------------

	/**
	 * Enqueue minified custom CSS as inline style.
	 *
	 * @return void
	 */
	public function enqueueInlineCustomCss(): void {
		$css = Helper::getStoredOptionContent( self::OPTION_NAME );

		if ( empty( $css ) ) {
			return;
		}

		$minified = Helper::cssMinify( $css, true );

		if ( empty( $minified ) ) {
			return;
		}

		/**
		 * Filter the style handle for custom CSS.
		 *
		 * @param string $handle The style handle to attach inline CSS to.
		 */
		$handle = apply_filters( 'hda_custom_css_handle', self::DEFAULT_STYLE_HANDLE );

		Asset::inlineStyle( $handle, $minified );
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return self::KEY_FORM_CSS;
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		Helper::updateStoredOption( self::OPTION_NAME, $data[ self::KEY_FORM_CSS ], 'text/css' );
	}
}
