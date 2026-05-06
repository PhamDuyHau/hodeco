<?php
/**
 * Redirect Import/Export — CSV and XLSX support via OpenSpout.
 *
 * @package HDAddons\Redirect
 */

namespace HDAddons\Redirect;

use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row;

\defined( 'ABSPATH' ) || exit;

final class RedirectImportExport {

	/**
	 * Expected column order: from, to, type.
	 */
	private const array HEADER = [ 'from', 'to', 'type' ];

	// ─── Import ──────────────────────────────────────

	/**
	 * Parse an uploaded file (CSV or XLSX) into redirect rules.
	 *
	 * @param string $filePath Absolute path to the uploaded file.
	 * @param string $mimeType File MIME type.
	 *
	 * @return array{rules: array, errors: string[]}
	 */
	public static function parseFile( string $filePath, string $mimeType ): array {
		$ext = strtolower( pathinfo( $filePath, PATHINFO_EXTENSION ) );

		if ( 'xlsx' === $ext || str_contains( $mimeType, 'spreadsheetml' ) ) {
			return self::parseXlsx( $filePath );
		}

		return self::parseCsv( $filePath );
	}

	/**
	 * Parse CSV file.
	 */
	private static function parseCsv( string $filePath ): array {
		$options = new CsvOptions();
		$reader  = new CsvReader( $options );
		$reader->open( $filePath );

		return self::extractRows( $reader );
	}

	/**
	 * Parse XLSX file.
	 */
	private static function parseXlsx( string $filePath ): array {
		$reader = new XlsxReader();
		$reader->open( $filePath );

		return self::extractRows( $reader );
	}

	/**
	 * Extract rules from reader rows.
	 *
	 * @param CsvReader|XlsxReader $reader
	 *
	 * @return array{rules: array, errors: string[]}
	 */
	private static function extractRows( CsvReader|XlsxReader $reader ): array {
		$rules       = [];
		$errors      = [];
		$rowIndex    = 0;
		$headerFound = false;

		foreach ( $reader->getSheetIterator() as $sheet ) {
			foreach ( $sheet->getRowIterator() as $row ) {
				$rowIndex++;

				// Skip empty rows.
				if ( $row->isEmpty() ) {
					continue;
				}

				// Convert to plain values array.
				$cells = array_map( static fn( $v ) => trim( (string) ( $v ?? '' ) ), $row->toArray() );

				// Try to detect header row (skip it).
				if ( ! $headerFound && self::isHeaderRow( $cells ) ) {
					$headerFound = true;
					continue;
				}

				$headerFound = true;

				// Need at least 2 columns (from, to).
				if ( count( $cells ) < 2 ) {
					$errors[] = sprintf( 'Row %d: insufficient columns (need at least from, to).', $rowIndex );
					continue;
				}

				$from = $cells[0] ?? '';
				$to   = $cells[1] ?? '';
				$type = isset( $cells[2] ) ? (int) $cells[2] : 301;

				if ( empty( $from ) || empty( $to ) ) {
					$errors[] = sprintf( 'Row %d: empty "from" or "to" value, skipped.', $rowIndex );
					continue;
				}

				// Normalize "from": extract path if full URL provided.
				if ( preg_match( '#^https?://#i', $from ) ) {
					$parsed = wp_parse_url( $from, PHP_URL_PATH );
					$from   = $parsed ?: '/';
				} elseif ( ! str_starts_with( $from, '/' ) ) {
					$from = '/' . $from;
				}

				// Validate type.
				if ( ! in_array( $type, [ 301, 302 ], true ) ) {
					$type = 301;
				}

				$rules[] = [
					'from' => $from,
					'to'   => $to,
					'type' => $type,
				];
			}

			break; // Only process the first sheet.
		}

		$reader->close();

		return [
			'rules'  => $rules,
			'errors' => $errors,
		];
	}

	/**
	 * Check if a row looks like a header.
	 */
	private static function isHeaderRow( array $cells ): bool {
		$first = strtolower( $cells[0] ?? '' );

		return in_array( $first, [ 'from', 'source', 'from (path)', 'path', 'url_from' ], true );
	}

	// ─── Export ──────────────────────────────────────

	/**
	 * Export rules to a temporary file.
	 *
	 * @param array  $rules  Redirect rules.
	 * @param string $format 'csv' or 'xlsx'.
	 *
	 * @return string Absolute path to the generated temp file.
	 */
	public static function exportToFile( array $rules, string $format = 'csv' ): string {
		$ext      = 'xlsx' === $format ? 'xlsx' : 'csv';
		$tempFile = wp_tempnam( 'hda-redirects.' . $ext );

		if ( 'xlsx' === $ext ) {
			self::writeXlsx( $tempFile, $rules );
		} else {
			self::writeCsv( $tempFile, $rules );
		}

		return $tempFile;
	}

	/**
	 * Write CSV file.
	 */
	private static function writeCsv( string $filePath, array $rules ): void {
		$writer = new CsvWriter();
		$writer->openToFile( $filePath );

		// Header.
		$writer->addRow( Row::fromValues( self::HEADER ) );

		foreach ( $rules as $rule ) {
			$writer->addRow( Row::fromValues( [
				$rule['from'] ?? '',
				$rule['to']   ?? '',
				(string) ( $rule['type'] ?? 301 ),
			] ) );
		}

		$writer->close();
	}

	/**
	 * Write XLSX file.
	 */
	private static function writeXlsx( string $filePath, array $rules ): void {
		$writer = new XlsxWriter();
		$writer->openToFile( $filePath );

		// Header.
		$writer->addRow( Row::fromValues( self::HEADER ) );

		foreach ( $rules as $rule ) {
			$writer->addRow( Row::fromValues( [
				$rule['from'] ?? '',
				$rule['to']   ?? '',
				(string) ( $rule['type'] ?? 301 ),
			] ) );
		}

		$writer->close();
	}
}
