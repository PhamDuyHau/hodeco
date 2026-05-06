<?php
/**
 * Module Manager.
 *
 * Registers all module services explicitly.
 * Services must extend AbstractModule to be loaded.
 *
 * @package HD\App\Modules
 * @author  HD
 */

namespace HD\App\Modules;

use HD\App\Modules\Services\Ajax;
use HD\App\Modules\Services\PostView;
use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class Module {
	use Singleton;

	private array $services = [];

	// -----------------------------------------

	/**
	 * Explicit service class list.
	 * Add new services here instead of relying on filesystem auto-discovery.
	 *
	 * @return string[]
	 */
	private function serviceClasses(): array {
		return [
			Ajax::class,
			PostView::class,
		];
	}

	// -----------------------------------------

	private function init(): void {
		$this->loadServices();
	}

	// -----------------------------------------

	/**
	 * Load registered service classes.
	 * Only classes extending AbstractModule and enabled will be loaded.
	 *
	 * @return void
	 */
	private function loadServices(): void {
		foreach ( $this->serviceClasses() as $className ) {
			if ( ! class_exists( $className ) || ! is_subclass_of( $className, AbstractModule::class ) ) {
				continue;
			}

			try {
				$instance = $className::get_instance();
				if ( ! $instance->isEnabled() ) {
					continue;
				}

				$this->services[] = $instance;
			} catch ( \Throwable $e ) {
				Helper::errorLog( "[Module] Failed to load service {$className}: " . $e->getMessage() );
			}
		}
	}

	// -----------------------------------------

	/**
	 * Get all loaded services.
	 *
	 * @return array<AbstractModule>
	 */
	public function getServices(): array {
		return $this->services;
	}

	// -----------------------------------------

	/**
	 * Get a specific service by class name.
	 *
	 * @param string $className
	 *
	 * @return AbstractModule|null
	 */
	public function getService( string $className ): ?AbstractModule {
		foreach ( $this->services as $service ) {
			if ( $service instanceof $className ) {
				return $service;
			}
		}

		return null;
	}
}
