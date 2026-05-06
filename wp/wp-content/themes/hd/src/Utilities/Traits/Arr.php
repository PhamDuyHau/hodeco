<?php
/**
 * Array and type casting utility trait.
 *
 * Provides array manipulation and type conversion utilities.
 *
 * @package HD\Utilities\Traits
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Arr {

	// --------------------------------------------------
	// Array utilities
	// --------------------------------------------------

	/**
	 * Convert a scalar (comma-separated string) or array into a filtered re-indexed array.
	 *
	 * @param mixed $value Value to convert.
	 * @param callable|null $callback Filter callback.
	 * @param string $separator String separator.
	 *
	 * @return array
	 */
	public static function convertFromString( mixed $value, ?callable $callback = null, string $separator = ',' ): array {
		if ( is_scalar( $value ) ) {
			$value = (string) $value;
			if ( trim( $value ) === '' ) {
				return [];
			}

			$value = array_map( 'trim', explode( $separator, $value ) );
		}

		$arr = (array) $value;

		$arr = $callback !== null
			? array_filter( $arr, $callback )
			: array_filter( $arr, static fn( $v ) => $v !== '' && $v !== null );

		return array_values( $arr );
	}

	// --------------------------------------------------

	/**
	 * Check whether array is a flat, indexed list.
	 *
	 * @param mixed $items Items to check.
	 *
	 * @return bool
	 */
	public static function isIndexedAndFlat( mixed $items ): bool {
		if ( ! is_array( $items ) ) {
			return false;
		}

		foreach ( $items as $v ) {
			if ( is_array( $v ) ) {
				return false;
			}
		}

		return array_is_list( $items );
	}

	// --------------------------------------------------

	/**
	 * Insert array after a given key.
	 *
	 * @param string|null $key Key to insert after.
	 * @param array $arr Original array.
	 * @param array $insertArray Array to insert.
	 *
	 * @return array
	 */
	public static function insertAfter( ?string $key, array $arr, array $insertArray ): array {
		return self::insert( $arr, $insertArray, $key, 'after' );
	}

	// --------------------------------------------------

	/**
	 * Insert array before a given key.
	 *
	 * @param string|null $key Key to insert before.
	 * @param array $arr Original array.
	 * @param array $insertArray Array to insert.
	 *
	 * @return array
	 */
	public static function insertBefore( ?string $key, array $arr, array $insertArray ): array {
		return self::insert( $arr, $insertArray, $key );
	}

	// --------------------------------------------------

	/**
	 * Insert an array before/after a specific key.
	 *
	 * @param array $arr Original array.
	 * @param array $insertArray Array to insert.
	 * @param string|null $key Key to insert at.
	 * @param string $position 'before' or 'after'.
	 *
	 * @return array
	 */
	public static function insert( array $arr, array $insertArray, ?string $key, string $position = 'before' ): array {
		if ( $key === null ) {
			return [ ...$arr, ...$insertArray ];
		}

		$keys = array_keys( $arr );
		$pos  = array_search( $key, $keys, true );

		if ( $pos === false ) {
			return [ ...$arr, ...$insertArray ];
		}

		if ( $position === 'after' ) {
			++$pos;
		}

		$left  = array_slice( $arr, 0, $pos, true );
		$right = array_slice( $arr, $pos, null, true );

		return $left + $insertArray + $right;
	}

	// --------------------------------------------------

	/**
	 * Prepend a value to an array.
	 *
	 * @param array $arr Original array.
	 * @param mixed $value Value to prepend.
	 * @param int|string|null $key Optional key.
	 *
	 * @return array
	 */
	public static function prepend( array $arr, mixed $value, int|string|null $key = null ): array {
		if ( $key !== null ) {
			return [
				$key => $value,
				...$arr,
			];
		}

		array_unshift( $arr, $value );

		return $arr;
	}

	// --------------------------------------------------
	// Type casting utilities (merged from Cast trait)
	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param bool $explode
	 *
	 * @return array
	 */
	public static function toArray( mixed $value, bool $explode = true ): array {
		if ( $value === null ) {
			return [];
		}

		if ( is_bool( $value ) ) {
			return [ $value ];
		}

		if ( is_array( $value ) ) {
			return $value;
		}

		if ( is_scalar( $value ) && $explode ) {
			return self::convertFromString( (string) $value );
		}

		if ( is_object( $value ) ) {
			return method_exists( $value, 'toArray' )
				? $value->toArray()
				: get_object_vars( $value );
		}

		return [];
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param bool $strict
	 *
	 * @return string
	 */
	public static function toString( mixed $value, bool $strict = true ): string {
		// int, float, string, bool
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		// Object with __toString method
		if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
			return (string) $value;
		}

		// Null, empty array, or other "empty" values
		if ( self::isEmpty( $value ) ) {
			return '';
		}

		// Indexed flat arrays
		if ( self::isIndexedAndFlat( $value ) ) {
			return implode( ', ', $value );
		}

		// Resource or Closure: cannot cast to string
		// Note: Using instanceof \Closure instead of is_callable() to avoid false positives
		// is_callable('strlen') returns true, but 'strlen' is a valid string
		if ( is_resource( $value ) || $value instanceof \Closure ) {
			return $strict ? '' : '[unsupported type]';
		}

		// Other types (associative array, object without __toString)
		return $strict ? '' : maybe_serialize( $value );
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function toBool( mixed $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 *
	 * @return object
	 */
	public static function toObject( mixed $value ): object {
		return is_object( $value ) ? $value : (object) self::toArray( $value );
	}
}
