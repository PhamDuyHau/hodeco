<?php
/**
 * Shared utilities for modules implementing SettingsAware.
 *
 * Provides common methods for extracting/sanitizing form fields
 * and saving/removing options. Used as a trait in module classes.
 *
 * @package HDAddons\Contracts
 */

namespace HDAddons\Contracts;

use HDAddons\Helper;

defined( 'ABSPATH' ) || exit;

trait SettingsHelpers {

	/**
	 * Extract and sanitize fields from form data.
	 *
	 * @param array $data         Form data.
	 * @param array $fields       Field keys to extract.
	 * @param bool  $requireValue Only include non-empty values.
	 *
	 * @return array
	 */
	protected static function extractFields( array $data, array $fields, bool $requireValue = false ): array {
		$options = [];

		foreach ( $fields as $field ) {
			if ( $requireValue ) {
				if ( ! empty( $data[ $field ] ) ) {
					$options[ $field ] = self::sanitizeValue( $data[ $field ] );
				}
			} elseif ( isset( $data[ $field ] ) ) {
				$options[ $field ] = self::sanitizeValue( $data[ $field ] );
			}
		}

		return $options;
	}

	/**
	 * Save options or remove if empty.
	 *
	 * @param string    $optionName Option name.
	 * @param array     $options    Options to save.
	 * @param bool|null $autoload   Whether to autoload option.
	 *
	 * @return void
	 */
	protected static function saveOrRemove( string $optionName, array $options, ?bool $autoload = false ): void {
		if ( ! empty( $options ) ) {
			Helper::updateOption( $optionName, $options, 12, $autoload );
		} else {
			Helper::removeOption( $optionName );
		}
	}

	/**
	 * Sanitize a single value based on its type.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return mixed
	 */
	protected static function sanitizeValue( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( [ static::class, 'sanitizeValue' ], $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}
}
