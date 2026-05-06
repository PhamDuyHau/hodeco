<?php
/**
 * Generator Trait
 *
 * Provides static methods for generating random strings and unique identifiers.
 *
 * @package HD\Utilities\Traits
 * @author  HD
 */

namespace HD\Utilities\Traits;

use Random\RandomException;

defined( 'ABSPATH' ) || exit;

trait Generator {

	/**
	 * @param int $length
	 *
	 * @return string
	 * @throws RandomException
	 */
	public static function makeUsername( int $length = 8 ): string {
		if ( $length < 1 ) {
			return '';
		}

		$letters       = 'abcdefghijklmnopqrstuvwxyz';
		$lettersDigits = 'abcdefghijklmnopqrstuvwxyz0123456789';

		$username = $letters[ random_int( 0, strlen( $letters ) - 1 ) ];

		for ( $i = 1; $i < $length; $i++ ) {
			$username .= $lettersDigits[ random_int( 0, strlen( $lettersDigits ) - 1 ) ];
		}

		return $username;
	}

	// --------------------------------------------------

	/**
	 * Generate a unique slug with desired length.
	 *
	 * @param int $length Total desired slug length
	 * @param string $prefix
	 *
	 * @return string
	 * @throws RandomException
	 */
	public static function makeUnique( int $length = 32, string $prefix = '' ): string {
		$time        = microtime( true );
		$timeEncoded = base_convert( (string) floor( $time * 1e6 ), 10, 36 );
		$pidEncoded  = base_convert( (string) getmypid(), 10, 36 );
		$uniqEncoded = base_convert( str_replace( '.', '', uniqid( '', true ) ), 10, 36 );

		$base = $timeEncoded . $pidEncoded . $uniqEncoded;

		$bytes  = random_bytes( (int) ceil( $length * 0.75 ) );
		$random = substr( base_convert( bin2hex( $bytes ), 16, 36 ), 0, $length );

		return $prefix . substr( $base . $random, 0, $length );
	}
}
