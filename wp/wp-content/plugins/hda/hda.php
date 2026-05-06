<?php
/**
 * Plugin Name: HD Addons
 * Plugin URI: https://webhd.vn
 * Version: 2.3.2
 * Requires PHP: 8.3
 * Author: HD
 * Author URI: https://webhd.vn
 * Description: Essential WordPress toolkit: Security, Custom Assets & Admin utilities.
 * License: MIT
 * ###Requires### Plugins: advanced-custom-fields-pro
 */

use HDAddons\Activator;
use HDAddons\Plugin;
use HDAddons\Helper;

defined( 'ABSPATH' ) || exit;

// Prevent double loading.
if ( defined( 'HDA_VERSION' ) ) {
	return;
}

const HDA_VERSION    = '2.3.2';
const HDA_TEXTDOMAIN = 'hda';

define( 'HDA_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'HDA_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/' );
define( 'HDA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Early autoload check and activation hooks (must be before plugins_loaded).
$hda_autoload = HDA_PATH . 'vendor/autoload.php';
if ( is_file( $hda_autoload ) ) {
	require_once $hda_autoload;

	// Register activation/deactivation/uninstall hooks early.
	register_activation_hook( __FILE__, Activator::activation(...) );
	register_deactivation_hook( __FILE__, Activator::deactivation(...) );
	register_uninstall_hook( __FILE__, [ Activator::class, 'uninstall' ] );
}

add_action( 'plugins_loaded', 'hda_init', 10 );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function hda_init(): void {
	load_plugin_textdomain( HDA_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// PHP version guard (8.3 or newer).
	if ( PHP_VERSION_ID < 80300 ) {
		add_action(
			'admin_notices',
			static function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'HDA requires PHP 8.3 or newer. Please upgrade your PHP version.', HDA_TEXTDOMAIN )
				);
			}
		);

		return;
	}

	// Composer autoload check.
	if ( ! class_exists( Helper::class ) ) {
		add_action(
			'admin_notices',
			static function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'HDA: missing vendor directory. Please run <code>composer install</code>.', HDA_TEXTDOMAIN )
				);
			}
		);

		return;
	}

	// Bootstrap.
	hda_bootstrap();
}

/**
 * Bootstrap the plugin after all checks pass.
 *
 * @return void
 */
function hda_bootstrap(): void {
	// ACF requirement check.
	if ( ! Helper::isAcfProActive() ) {
		if ( is_admin() ) {
			add_action(
				'admin_notices',
				static function () {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'HDA requires Advanced Custom Fields Pro plugin. Please install and activate it.', HDA_TEXTDOMAIN )
					);
				}
			);
		}

		return;
	}

	try {
		new Plugin();
	} catch ( \Throwable $e ) {
		Helper::errorLog( '[HDA] ' . $e->getMessage() );

		// Only show detailed error in development mode.
		if ( Helper::development() ) {
			add_action(
				'admin_notices',
				static function () use ( $e ) {
					printf(
						'<div class="notice notice-error"><p><strong>HDA Error:</strong> %s</p></div>',
						esc_html( $e->getMessage() )
					);
				}
			);
		}
	}
}
