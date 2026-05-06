<?php
/**
 * File management module - handles upload size limits and SVG support.
 *
 * @author HD
 */

namespace HDAddons\File;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\File\FileIntegrity\FileIntegrity;
use HDAddons\File\FileIntegrity\FileIntegrityAdmin;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class File implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME           = 'file__options';
	public const string KEY_UPLOAD_SIZE_LIMIT = 'upload_size_limit';
	public const string KEY_SVGS              = 'svgs';

	/**
	 * File configuration options.
	 */
	private array $fileOptions;

	// ------------------------------------------------------

	/**
	 * Initialize file handling features.
	 */
	public function __construct() {
		$this->fileOptions = Helper::getOption( self::OPTION_NAME, [] );

		// Initialize SVG support module (only when not disabled).
		if ( 'disable' !== ( $this->fileOptions[ self::KEY_SVGS ] ?? 'disable' ) ) {
			new SVG();
		}

		add_action( 'init', $this->initFilters( ... ), 99 );

		// ── Sub-module: File Integrity Scanner ──
		// Admin page (manual scans) is always available.
		if ( is_admin() ) {
			new FileIntegrityAdmin();
		}

		// Automated cron scanning only when enabled.
		$integrityOptions = Helper::getOption( FileIntegrity::OPTION_NAME, [] );
		if ( ! empty( $integrityOptions[ FileIntegrity::KEY_ENABLED ] ) ) {
			new FileIntegrity();
		}
	}

	// ------------------------------------------------------

	/**
	 * Register file-related filters.
	 *
	 * @return void
	 */
	public function initFilters(): void {
		add_filter( 'upload_size_limit', $this->customUploadSizeLimit( ... ) );
	}

	// ------------------------------------------------------

	/**
	 * Override the maximum upload size limit.
	 *
	 * @param int $size Current upload size limit in bytes.
	 *
	 * @return int Modified upload size limit.
	 */
	public function customUploadSizeLimit( int $size ): int {
		$uploadSizeLimit = (int) ( $this->fileOptions[ self::KEY_UPLOAD_SIZE_LIMIT ] ?? 0 );

		if ( $uploadSizeLimit > 0 ) {
			return $uploadSizeLimit * 1024 * 1024; // Convert MB to bytes
		}

		return $size;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'file-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$fields  = [ self::KEY_UPLOAD_SIZE_LIMIT, self::KEY_SVGS ];
		$options = self::extractFields( $data, $fields, true );
		self::saveOrRemove( self::OPTION_NAME, $options );

		// Delegate to FileIntegrity if its form section is present.
		if ( isset( $data[ FileIntegrity::getFormKey() ] ) ) {
			FileIntegrity::sanitizeAndSave( $data );
		}
	}
}
