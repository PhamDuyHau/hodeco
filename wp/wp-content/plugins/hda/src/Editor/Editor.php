<?php
/**
 * Editor module - manages Gutenberg and Classic Editor settings.
 *
 * @author HD
 */

namespace HDAddons\Editor;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Editor implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME                     = 'editor__options';
	public const string KEY_WIDGETS_BLOCK_EDITOR_OFF    = 'use_widgets_block_editor_off';
	public const string KEY_BLOCK_EDITOR_OFF            = 'use_block_editor_for_post_type_off';
	public const string KEY_BLOCK_STYLE_OFF             = 'block_style_off';
	public const string KEY_FONT_LIBRARY_OFF            = 'font_library_off';
	public const string KEY_REMOTE_PATTERNS_OFF         = 'remote_patterns_off';
	public const string KEY_OPENVERSE_OFF               = 'openverse_off';
	public const string KEY_SITE_EDITOR_OFF             = 'site_editor_off';

	/**
	 * Editor configuration options.
	 */
	private array $editorOptions;

	// ------------------------------------------------------

	/**
	 * Initialize editor management features.
	 */
	public function __construct() {
		$this->editorOptions = Helper::getOption( self::OPTION_NAME, [] );

		// Initialize TinyMCE enhancements
		new TinyMCE();

		add_action( 'admin_init', $this->initAdminSettings( ... ), 11 );
		add_action( 'wp_enqueue_scripts', $this->dequeueBlockStyles( ... ), 20 );

		// Remove WP_Duotone inline styles (core-block-supports-inline-css, global-styles-inline-css)
		add_action( 'wp_loaded', $this->removeDuotoneStyles( ... ) );

		// Initialize block editor restrictions (font library, patterns, openverse)
		$this->initBlockEditorRestrictions();

		// Hide Site Editor menu (for classic themes)
		if ( ! empty( $this->editorOptions[ self::KEY_SITE_EDITOR_OFF ] ) ) {
			add_action( 'admin_menu', $this->hideSiteEditorMenu( ... ), 999 );
		}
	}

	// ------------------------------------------------------

	/**
	 * Initialize admin-side editor settings.
	 *
	 * @return void
	 */
	public function initAdminSettings(): void {
		// Disables the block editor from managing widgets
		if ( ! empty( $this->editorOptions[ self::KEY_WIDGETS_BLOCK_EDITOR_OFF ] ) ) {
			add_filter( 'use_widgets_block_editor', '__return_false', 100 );
			add_filter( 'gutenberg_use_widgets_block_editor', '__return_false', 100 );
		}

		// Use Classic Editor - Disable Gutenberg Editor
		if ( ! empty( $this->editorOptions[ self::KEY_BLOCK_EDITOR_OFF ] ) ) {
			add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
		}
	}

	/**
	 * Initialize block editor restrictions (called from constructor).
	 *
	 * @return void
	 */
	private function initBlockEditorRestrictions(): void {
		// Skip if Block Editor is disabled entirely
		if ( ! empty( $this->editorOptions[ self::KEY_BLOCK_EDITOR_OFF ] ) ) {
			return;
		}

		$fontLibraryOff = ! empty( $this->editorOptions[ self::KEY_FONT_LIBRARY_OFF ] );
		$openverseOff   = ! empty( $this->editorOptions[ self::KEY_OPENVERSE_OFF ] );

		// Disable Font Library (WP 6.5+) and/or Openverse via single filter
		if ( $fontLibraryOff || $openverseOff ) {
			add_filter( 'block_editor_settings_all', static function ( $settings ) use ( $fontLibraryOff, $openverseOff ) {
				if ( $fontLibraryOff ) {
					$settings['fontLibraryEnabled'] = false;
				}
				if ( $openverseOff ) {
					$settings['enableOpenverseMediaCategory'] = false;
				}

				return $settings;
			}, 100 );
		}

		// Disable remote block patterns from WordPress.org
		if ( ! empty( $this->editorOptions[ self::KEY_REMOTE_PATTERNS_OFF ] ) ) {
			add_filter( 'should_load_remote_block_patterns', '__return_false' );
		}
	}

	// ------------------------------------------------------

	/**
	 * Remove block inline styles from frontend.
	 * Removes core-block-supports-inline-css and global-styles-inline-css.
	 *
	 * @return void
	 */
	public function removeDuotoneStyles(): void {
		if ( empty( $this->editorOptions[ self::KEY_BLOCK_STYLE_OFF ] ) ) {
			return;
		}

		// Remove core-block-supports-inline-css
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );

		// Remove global-styles-inline-css
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}

	// ------------------------------------------------------

	/**
	 * Dequeue and deregister block library styles on frontend.
	 * Updated for WordPress 6.x+
	 *
	 * @return void
	 */
	public function dequeueBlockStyles(): void {
		if ( empty( $this->editorOptions[ self::KEY_BLOCK_STYLE_OFF ] ) ) {
			return;
		}

		// Styles to completely remove
		$stylesToRemove = [
			'wp-block-library',
			'wp-block-library-theme',
			'classic-theme-styles',
			'global-styles',
			'wp-emoji-styles',
		];

		foreach ( $stylesToRemove as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * Hide Site Editor menu for classic themes.
	 *
	 * @return void
	 */
	public function hideSiteEditorMenu(): void {
		remove_submenu_page( 'themes.php', 'site-editor.php' );
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'editor-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$fields = [
			self::KEY_WIDGETS_BLOCK_EDITOR_OFF,
			self::KEY_BLOCK_EDITOR_OFF,
			self::KEY_BLOCK_STYLE_OFF,
			self::KEY_FONT_LIBRARY_OFF,
			self::KEY_REMOTE_PATTERNS_OFF,
			self::KEY_OPENVERSE_OFF,
			self::KEY_SITE_EDITOR_OFF,
		];

		$options = self::extractFields( $data, $fields );
		self::saveOrRemove( self::OPTION_NAME, $options );
	}
}

