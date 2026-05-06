<?php
/**
 * Redirect module - Manage 301/302 URL redirects.
 *
 * Stores redirect rules as JSON in a custom post (hda_storage CPT)
 * and hooks into `template_redirect` to perform server-side redirects.
 *
 * @package HDAddons\Redirect
 * @author  HD
 */

namespace HDAddons\Redirect;

use HDAddons\Asset;
use HDAddons\Contracts\SettingsAware;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class Redirect implements SettingsAware {

	/**
	 * Cache key for redirect rules (object cache).
	 */
	private const string CACHE_KEY   = 'hda_redirect_rules';
	private const string CACHE_GROUP = 'hda';

	/**
	 * Items per page for the admin table.
	 */
	public const int PER_PAGE = 20;

	// ─── Option Keys (single source of truth) ───────────

	/**
	 * Storage key for redirect rules (stored option / CPT).
	 */
	public const string OPTION_NAME = 'redirect_rules';

	// ------------------------------------------------------

	/**
	 * Initialize redirect handling.
	 */
	public function __construct() {
		// Frontend: handle redirects.
		if ( ! is_admin() ) {
			add_action( 'template_redirect', $this->handleRedirects( ... ), 1 );

			return;
		}

		// Admin: AJAX endpoints.
		add_action( 'wp_ajax_hda_redirect_import', self::ajaxImport( ... ) );
		add_action( 'wp_ajax_hda_redirect_export', self::ajaxExport( ... ) );
		add_action( 'wp_ajax_hda_redirect_delete', self::ajaxDelete( ... ) );
		add_action( 'wp_ajax_hda_redirect_delete_all', self::ajaxDeleteAll( ... ) );
		add_action( 'wp_ajax_hda_redirect_check_dupe', self::ajaxCheckDupe( ... ) );

		// Localize nonce for JS module.
		add_action( 'admin_enqueue_scripts', static function (): void {
			$handle = Asset::handle( 'hda.js' );
			if ( $handle ) {
				Asset::localize( $handle, 'hdaRedirect', [
					'nonce' => wp_create_nonce( 'hda_redirect_manage' ),
					'i18n'  => [
						'importing'    => __( 'Importing...', HDA_TEXTDOMAIN ),
						'import_done'  => __( 'Import complete!', HDA_TEXTDOMAIN ),
						'import_error' => __( 'Import failed.', HDA_TEXTDOMAIN ),
						'confirm_replace' => __( 'This will replace ALL existing rules with the imported ones. Continue?', HDA_TEXTDOMAIN ),
					],
				] );
			}
		}, 50 );
	}

	// ------------------------------------------------------

	/**
	 * Get stored redirect rules (with object caching).
	 *
	 * @return array<int, array{from: string, to: string, type: int}>
	 */
	public static function getRules(): array {
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$json = Helper::getStoredOptionContent( self::OPTION_NAME );

		if ( empty( $json ) ) {
			wp_cache_set( self::CACHE_KEY, [], self::CACHE_GROUP );

			return [];
		}

		try {
			$rules = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			Helper::errorLog( '[HDA Redirect] Invalid JSON in stored rules: ' . $e->getMessage() );

			return [];
		}

		$rules = is_array( $rules ) ? $rules : [];

		wp_cache_set( self::CACHE_KEY, $rules, self::CACHE_GROUP );

		return $rules;
	}

	// ------------------------------------------------------

	/**
	 * Get paginated redirect rules for the admin table.
	 *
	 * @param int $page Current page (1-indexed).
	 *
	 * @return array{rules: array, total: int, total_pages: int, page: int}
	 */
	public static function getPaginated( int $page = 1 ): array {
		$rules      = self::getRules();
		$total      = count( $rules );
		$totalPages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$page       = max( 1, min( $page, $totalPages ) );
		$offset     = ( $page - 1 ) * self::PER_PAGE;

		return [
			'rules'       => array_slice( $rules, $offset, self::PER_PAGE ),
			'total'       => $total,
			'total_pages' => $totalPages,
			'page'        => $page,
			'offset'      => $offset,
		];
	}

	// ------------------------------------------------------

	/**
	 * Save redirect rules.
	 *
	 * @param array $rules Redirect rules.
	 *
	 * @return void
	 */
	public static function saveRules( array $rules ): void {
		$sanitized = []; // Keyed by normalized path — last occurrence wins.

		foreach ( $rules as $rule ) {
			$from = isset( $rule['from'] ) ? trim( sanitize_text_field( $rule['from'] ) ) : '';
			$to   = isset( $rule['to'] ) ? trim( esc_url_raw( $rule['to'] ) ) : '';
			$type = isset( $rule['type'] ) ? (int) $rule['type'] : 301;

			if ( empty( $from ) || empty( $to ) ) {
				continue;
			}

			// Normalize: ensure "from" starts with /.
			if ( ! str_starts_with( $from, '/' ) ) {
				$from = '/' . $from;
			}

			// Only allow 301 or 302.
			if ( ! in_array( $type, [ 301, 302 ], true ) ) {
				$type = 301;
			}

			// De-duplicate: use normalized path as key — last occurrence wins.
			$normalizedKey                = strtolower( rtrim( $from, '/' ) );
			$sanitized[ $normalizedKey ]  = [
				'from' => $from,
				'to'   => $to,
				'type' => $type,
			];
		}

		// Re-index to sequential array.
		$sanitized = array_values( $sanitized );

		if ( empty( $sanitized ) ) {
			Helper::deleteStoredOption( self::OPTION_NAME );
		} else {
			$json = wp_json_encode( $sanitized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			Helper::updateStoredOption( self::OPTION_NAME, $json, 'application/json' );
		}

		// Invalidate object cache.
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}

	// ------------------------------------------------------

	/**
	 * Perform redirects based on stored rules.
	 *
	 * @return void
	 */
	public function handleRedirects(): void {
		$rules = self::getRules();

		if ( empty( $rules ) ) {
			return;
		}

		// Get current request path.
		$requestUri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		if ( empty( $requestUri ) ) {
			return;
		}

		// Parse path and query string separately.
		$requestPath  = wp_parse_url( $requestUri, PHP_URL_PATH );
		$requestPath  = $requestPath ? strtolower( rtrim( $requestPath, '/' ) ) : '';
		$requestQuery = wp_parse_url( $requestUri, PHP_URL_QUERY );

		if ( empty( $requestPath ) ) {
			return;
		}

		// Build hashmap for O(1) lookup (last occurrence wins for duplicates).
		$lookup = [];
		foreach ( $rules as $rule ) {
			$key            = strtolower( rtrim( $rule['from'], '/' ) );
			$lookup[ $key ] = $rule;
		}

		$match = $lookup[ $requestPath ] ?? null;

		if ( ! $match ) {
			return;
		}

		$destination = $match['to'];
		$statusCode  = $match['type'];

		// Prevent redirect loops (only for same-host or relative destinations).
		$destHost = wp_parse_url( $destination, PHP_URL_HOST );
		$destPath = wp_parse_url( $destination, PHP_URL_PATH );
		$sameHost = ! $destHost || strcasecmp( $destHost, wp_parse_url( home_url(), PHP_URL_HOST ) ) === 0;

		if ( $sameHost && $destPath && strtolower( rtrim( $destPath, '/' ) ) === $requestPath ) {
			return;
		}

		// Preserve original query string if the destination has none.
		if ( $requestQuery && ! wp_parse_url( $destination, PHP_URL_QUERY ) ) {
			$destination .= '?' . $requestQuery;
		}

		wp_redirect( $destination, $statusCode );
		exit;
	}

	// ─── AJAX: Check Duplicate ──────────────────────────

	/**
	 * Check if a "from" path already exists in stored rules.
	 *
	 * @return void
	 */
	public static function ajaxCheckDupe(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		$from = isset( $_POST['from'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['from'] ) ) ) : '';

		if ( empty( $from ) ) {
			wp_send_json_success( [ 'exists' => false ] );
		}

		// Normalize.
		if ( ! str_starts_with( $from, '/' ) ) {
			$from = '/' . $from;
		}

		$key   = strtolower( rtrim( $from, '/' ) );
		$rules = self::getRules();

		foreach ( $rules as $rule ) {
			if ( strtolower( rtrim( $rule['from'], '/' ) ) === $key ) {
				wp_send_json_success( [
					'exists'      => true,
					'existing_to' => $rule['to'],
				] );
			}
		}

		wp_send_json_success( [ 'exists' => false ] );
	}

	// ─── AJAX: Delete ───────────────────────────────────

	/**
	 * Delete specific rules by their global indices.
	 *
	 * Expects POST `indices[]` — array of 0-based indices.
	 *
	 * @return void
	 */
	public static function ajaxDelete(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		$indices = isset( $_POST['indices'] ) ? array_map( 'intval', (array) $_POST['indices'] ) : [];

		if ( empty( $indices ) ) {
			wp_send_json_error( [ 'message' => __( 'No rules specified.', HDA_TEXTDOMAIN ) ] );
		}

		$rules = self::getRules();

		// Remove by indices (highest first to preserve positions).
		rsort( $indices );
		foreach ( $indices as $idx ) {
			if ( isset( $rules[ $idx ] ) ) {
				array_splice( $rules, $idx, 1 );
			}
		}

		self::saveRules( $rules );

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %d: number of deleted rules */
				__( 'Deleted %d rule(s). Remaining: %d.', HDA_TEXTDOMAIN ),
				count( $indices ),
				count( $rules )
			),
			'total' => count( $rules ),
		] );
	}

	/**
	 * Delete all redirect rules.
	 *
	 * @return void
	 */
	public static function ajaxDeleteAll(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		self::saveRules( [] );

		wp_send_json_success( [
			'message' => __( 'All redirect rules have been deleted.', HDA_TEXTDOMAIN ),
			'total'   => 0,
		] );
	}

	// ─── AJAX: Import ───────────────────────────────────

	/**
	 * Handle file import via AJAX.
	 *
	 * @return void
	 */
	public static function ajaxImport(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		if ( empty( $_FILES['import_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded.', HDA_TEXTDOMAIN ) ] );
		}

		$file     = $_FILES['import_file'];
		$mimeType = $file['type'] ?? '';
		$tmpName  = $file['tmp_name'] ?? '';
		$mode     = sanitize_key( $_POST['import_mode'] ?? 'append' );

		if ( empty( $tmpName ) || ! is_uploaded_file( $tmpName ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid file upload.', HDA_TEXTDOMAIN ) ] );
		}

		// Validate extension.
		$ext = strtolower( pathinfo( $file['name'] ?? '', PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, [ 'csv', 'xlsx' ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Only CSV and XLSX files are accepted.', HDA_TEXTDOMAIN ) ] );
		}

		try {
			$result = RedirectImportExport::parseFile( $tmpName, $mimeType );
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA Redirect] Import parse error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => __( 'Failed to parse file.', HDA_TEXTDOMAIN ) . ' ' . $e->getMessage() ] );
		}

		$importedRules = $result['rules'] ?? [];

		if ( empty( $importedRules ) ) {
			wp_send_json_error( [
				'message' => __( 'No valid rules found in the file.', HDA_TEXTDOMAIN ),
				'errors'  => $result['errors'] ?? [],
			] );
		}

		// Merge or replace — with duplicate detection.
		$existing = self::getRules();
		$skipped  = 0;

		if ( 'replace' === $mode ) {
			// Replace all: deduplicate within the imported file only.
			$finalRules = $importedRules;
		} else {
			// Append: skip imported rules whose "from" already exists in DB.
			$existingPaths = [];
			foreach ( $existing as $rule ) {
				$key                   = strtolower( rtrim( $rule['from'], '/' ) );
				$existingPaths[ $key ] = true;
			}

			$uniqueImported = [];
			foreach ( $importedRules as $rule ) {
				$key = strtolower( rtrim( $rule['from'], '/' ) );

				if ( isset( $existingPaths[ $key ] ) ) {
					$skipped++;
					continue;
				}

				// Also deduplicate within the file itself.
				$existingPaths[ $key ] = true;
				$uniqueImported[]      = $rule;
			}

			$finalRules = array_merge( $existing, $uniqueImported );
		}

		self::saveRules( $finalRules );

		$savedCount   = count( self::getRules() );
		$addedCount   = 'replace' === $mode ? count( $importedRules ) : count( $uniqueImported ?? $importedRules );
		$parseErrors  = $result['errors'] ?? [];

		if ( $skipped > 0 ) {
			$parseErrors[] = sprintf(
				/* translators: %d: number of skipped duplicate rules */
				__( '%d rule(s) skipped — "from" path already exists.', HDA_TEXTDOMAIN ),
				$skipped
			);
		}

		wp_send_json_success( [
			'message'  => sprintf(
				/* translators: %1$d: added count, %2$d: total count */
				__( 'Added %1$d rules. Total rules: %2$d.', HDA_TEXTDOMAIN ),
				$addedCount,
				$savedCount
			),
			'imported' => $addedCount,
			'skipped'  => $skipped,
			'total'    => $savedCount,
			'errors'   => $parseErrors,
		] );
	}

	// ─── AJAX: Export ───────────────────────────────────

	/**
	 * Handle export download via AJAX.
	 *
	 * @return void
	 */
	public static function ajaxExport(): void {
		// Verify nonce via GET parameter.
		$nonce = isset( $_GET['_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'hda_redirect_manage' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', HDA_TEXTDOMAIN ), 403 );
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', HDA_TEXTDOMAIN ), 403 );
		}

		$format = sanitize_key( $_GET['format'] ?? 'csv' );

		if ( ! in_array( $format, [ 'csv', 'xlsx' ], true ) ) {
			$format = 'csv';
		}

		$rules = self::getRules();

		if ( empty( $rules ) ) {
			wp_die( esc_html__( 'No redirect rules to export.', HDA_TEXTDOMAIN ) );
		}

		try {
			$filePath = RedirectImportExport::exportToFile( $rules, $format );
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA Redirect] Export error: ' . $e->getMessage() );
			wp_die( esc_html__( 'Export failed.', HDA_TEXTDOMAIN ) );
		}

		$filename    = 'hda-redirects-' . gmdate( 'Y-m-d' ) . '.' . $format;
		$contentType = 'xlsx' === $format
			? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			: 'text/csv';

		header( 'Content-Type: ' . $contentType );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $filePath ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $filePath );

		wp_delete_file( $filePath );
		exit;
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'redirect-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$page    = max( 1, (int) ( $data['redirect_page'] ?? 1 ) );
		$perPage = self::PER_PAGE;

		// Build new rules from the submitted form data (current page only).
		$newPageRules = [];

		if ( ! empty( $data['redirect_from'] ) ) {
			$fromArr = (array) $data['redirect_from'];
			$toArr   = isset( $data['redirect_to'] ) ? (array) $data['redirect_to'] : [];
			$typeArr = isset( $data['redirect_type'] ) ? (array) $data['redirect_type'] : [];

			foreach ( $fromArr as $i => $from ) {
				$newPageRules[] = [
					'from' => $from,
					'to'   => $toArr[ $i ] ?? '',
					'type' => (int) ( $typeArr[ $i ] ?? 301 ),
				];
			}
		}

		// Get all existing rules, replace the current page's slice.
		$existingRules = self::getRules();
		$offset        = ( $page - 1 ) * $perPage;

		// Remove old page rules, insert new ones at the same position.
		array_splice( $existingRules, $offset, $perPage, $newPageRules );

		self::saveRules( $existingRules );
	}
}
