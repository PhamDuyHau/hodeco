<?php
/**
 * Abstract base class for all module services.
 *
 * Provides a consistent structure for module services with
 * required initialization method.
 *
 * @package HD\App\Modules
 * @author  HD
 */

namespace HD\App\Modules;

use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

abstract class AbstractModule {
	use Singleton;

	/**
	 * Initialize the module.
	 * This method is called automatically when the module is instantiated.
	 *
	 * @return void
	 */
	abstract protected function init(): void;

	/** ---------------------------------------- */

	/**
	 * Log a message with module context.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	protected function log( string $message ): void {
		Helper::errorLog( '[' . static::class . '] ' . $message );
	}

	/** ---------------------------------------- */

	/**
	 * Check if the module is enabled.
	 * Override in child class if conditional loading is needed.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool {
		return true;
	}
}
