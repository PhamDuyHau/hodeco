<?php
/**
 * Acts as the main bootloader for the theme.
 * Central service container - manages dependencies and loads services.
 *
 * @package HD
 * @author  HD
 */

namespace HD;

use HD\Admin\Admin;
use HD\Core\Cache;
use HD\Core\Customizer;
use HD\Core\Optimizer;
use HD\Utilities\Shortcode\Shortcode;
use HD\Utilities\Traits\Singleton;
use HD\App\API\API;
use HD\App\Events\Event;
use HD\App\Modules\Module;
use HD\Plugins\Plugin;

defined( 'ABSPATH' ) || exit;

final class Bootstrap {
	use Singleton;

	// --------------------------------------------------

	private function init(): void {
		// Register core services.
		add_action( 'after_setup_theme', $this->registerServices( ... ), 11 );

		// Initialize theme (frontend).
		Theme::get_instance();

		// Action hook after theme is fully loaded.
		add_action( 'after_setup_theme', static fn() => do_action( 'hd_theme_loaded' ), 99 );
	}

	// --------------------------------------------------

	/**
	 * Register core services.
	 * Services are loaded here instead of Theme to separate concerns.
	 *
	 * @return void
	 */
	private function registerServices(): void {
		// Admin only services.
		if ( is_admin() ) {
			Admin::get_instance();
		}

		Shortcode::get_instance();

		// Core services (from src/Core/) - rarely changed, reusable.
		Cache::get_instance();
		Customizer::get_instance();
		Optimizer::get_instance();

		// Plugin integrations (from src/Plugins/).
		Plugin::get_instance();

		// App services - each autoloads its own subcomponents.
		API::get_instance();
		Event::get_instance();
		Module::get_instance();
	}
}
