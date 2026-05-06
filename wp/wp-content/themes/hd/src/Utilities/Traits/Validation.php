<?php
/**
 * Validation Trait
 *
 * Provides static validation utility methods.
 *
 * @package HD\Utilities\Traits
 * @author  HD
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Validation {

	/**
	 * @param mixed $phone
	 *
	 * @return bool
	 */
	public static function isValidPhone( mixed $phone ): bool {
		if ( ! is_string( $phone ) || trim( $phone ) === '' ) {
			return false;
		}

		$pattern = '/^\(?\+?(0|84)\)?[\s.\-]?(3[2-9]|5[689]|7[06-9]|(?:8[0-689]|87)|9[0-4|6-9])(\d{7}|\d[\s.\-]?\d{3}[\s.\-]?\d{3})$/';

		return preg_match( $pattern, $phone ) === 1;
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param int $min
	 * @param int $max
	 *
	 * @return bool
	 */
	public static function inRange( mixed $value, int $min, int $max ): bool {
		return filter_var(
			$value,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => $min,
					'max_range' => $max,
				],
			]
		) !== false;
	}

	// --------------------------------------------------

	/**
	 * @param array $arrayA
	 * @param array $arrayB
	 *
	 * @return bool
	 */
	public static function checkValuesNotInRanges( array $arrayA, array $arrayB ): bool {
		foreach ( $arrayA as $range ) {
			if ( count( $range ) !== 2 || ! is_numeric( $range[0] ) || ! is_numeric( $range[1] ) ) {
				continue;
			}

			$start = min( $range );
			$end   = max( $range );

			foreach ( $arrayB as $value ) {
				if ( $value >= $start && $value < $end ) {
					return false;
				}
			}

			if ( min( $arrayB ) <= $start && max( $arrayB ) >= $end ) {
				return false;
			}
		}

		return true;
	}
}
