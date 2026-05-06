<?php
/**
 * Theme functions and definitions.
 *
 * Initializes the HD Theme, loads dependencies via Composer autoload,
 * defines constants, and ensures compatibility with PHP 8.3 or newer.
 *
 * Directory Structure:
 * - src/       Stable, reusable utilities (Admin, Utilities, Bootstrap, Theme)
 * - app/       Project-specific code (API, Events, Modules, Plugins)
 *
 * @package HD
 * @author  HD
 */

use HD\Bootstrap;
use HD\Utilities\Helper;
use HD\Utilities\Utils;

const THEME_VERSION = '2.1.1';
const TEXT_DOMAIN   = 'hd';
const AUTHOR        = 'HD';

const ASSETS_DIR    = 'assets';
const RESOURCES_DIR = 'resources';

define( 'THEME_PATH', get_template_directory() . '/' );
define( 'THEME_URL', get_template_directory_uri() . '/' );

/**
 * Display error message in admin and frontend.
 *
 * @param string $error_message
 *
 * @return void
 */
function _static_error( string $error_message ): void {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $error_message )
		)
	);

	if ( ! is_admin() ) {
		get_template_part( 'parts/blocks/php-error', null, [ 'error_message' => $error_message ] );
		die();
	}
}

// PHP version guard (8.3+).
if ( PHP_VERSION_ID < 80300 ) {
	_static_error( 'HD Theme: requires PHP 8.3 or newer.' );

	return;
}

// Autoload classes (PSR-4 via Composer).
$autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_file( $autoload ) ) {
	_static_error( 'HD Theme: missing vendor autoload. Run `composer install`.' );

	return;
}

require_once $autoload;

// Global aliases for commonly used classes.
class_alias( Helper::class, 'HD_Helper' );
class_alias( Helper::class, 'HD_Utils' );

// Bootstrap the theme.
Bootstrap::get_instance();
