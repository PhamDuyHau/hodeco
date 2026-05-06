<?php
/**
 * Plugin Manager.
 *
 * Registers all plugin integrations explicitly.
 *
 * @author HD
 */

namespace HD\Plugins;

use HD\Plugins\Integrations\ACF\ACF;
use HD\Plugins\Integrations\CF7;
use HD\Plugins\Integrations\RankMath;
use HD\Plugins\Integrations\WooCommerce\WooCommerce;
use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class Plugin {
	use Singleton;

	private array $integrations = [];

	// -----------------------------------------

	/**
	 * Explicit integration class list.
	 * Add new plugin integrations here instead of relying on filesystem auto-discovery.
	 *
	 * @return string[]
	 */
	private function integrationClasses(): array {
		return [
			ACF::class,
			//CF7::class,
			RankMath::class,
			WooCommerce::class,
		];
	}

	// -----------------------------------------

	private function init(): void {
		$this->loadIntegrations();
	}

	// -----------------------------------------

	/**
	 * Load registered integration classes.
	 *
	 * @return void
	 */
	private function loadIntegrations(): void {
		foreach ( $this->integrationClasses() as $className ) {
			if ( ! class_exists( $className ) || ! is_subclass_of( $className, PluginIntegration::class ) ) {
				continue;
			}

			// Check if plugin is active
			if ( ! $className::isActive() ) {
				continue;
			}

			try {
				$this->integrations[] = $className::get_instance();
			} catch ( \Throwable $e ) {
				Helper::errorLog( "[Plugin] Failed to load integration {$className}: " . $e->getMessage() );
			}
		}
	}

	// -----------------------------------------

	/**
	 * Get all loaded integrations.
	 *
	 * @return array
	 */
	public function getIntegrations(): array {
		return $this->integrations;
	}
}
