<?php
/**
 * Singleton Trait.
 *
 * Provides singleton pattern implementation.
 * Usage: use HD\Utilities\Traits\Singleton;
 *
 * @package HD\Utilities\Traits
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait Singleton {

	/**
	 * Stores instances of each class using this trait.
	 * Key: fully qualified class name, Value: class instance.
	 *
	 * @var array<class-string, static>
	 */
	private static array $instances = [];

	/**
	 * Get the singleton instance of the class.
	 *
	 * Uses null coalescing assignment operator (??=) introduced in PHP 7.4:
	 * - If instance doesn't exist or is null -> creates new instance and assigns it
	 * - If instance already exists -> returns the existing one
	 *
	 * @return static The singleton instance.
	 */
	final public static function get_instance(): static {
		return self::$instances[ static::class ] ??= new static();
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 * Called only once when get_instance() creates the first instance.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the singleton.
	 * Override this method in child classes to add initialization logic.
	 *
	 * @return void
	 */
	protected function init(): void {
	}

	/**
	 * Prevent cloning of the singleton instance.
	 *
	 * @return void
	 */
	final public function __clone(): void {
	}

	/**
	 * Prevent unserialization of the singleton instance.
	 *
	 * @return void
	 * @throws \RuntimeException Always throws exception.
	 */
	final public function __wakeup(): void {
		throw new \RuntimeException( 'Cannot unserialize singleton.' );
	}
}
