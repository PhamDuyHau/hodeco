<?php
/**
 * Event Manager.
 *
 * @author HD
 */

namespace HD\App\Events;

use HD\App\Events\Handlers\PostViewsCleaner;
use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class Event {
	use Singleton;

	private array $handlers = array();

	// -----------------------------------------

	/**
	 * Explicit handler class list.
	 * Add new event handlers here instead of relying on filesystem auto-discovery.
	 *
	 * @return string[]
	 */
	private function handlerClasses(): array {
		return array(
			PostViewsCleaner::class,
		);
	}

	// -----------------------------------------

	private function init(): void {
		// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_filter( 'cron_schedules', Cron::register( ... ) );

		$this->loadHandlers();
		$this->registerCrons();
	}

	// -----------------------------------------

	private function loadHandlers(): void {
		foreach ( $this->handlerClasses() as $className ) {
			if ( ! class_exists( $className ) ) {
				continue;
			}

			try {
				$this->handlers[] = new $className();
			} catch ( \Throwable $e ) {
				Helper::errorLog( "[Event] Failed to load handler {$className}: " . $e->getMessage() );
			}
		}
	}

	// -----------------------------------------

	private function registerCrons(): void {
		foreach ( $this->handlers as $handler ) {
			if ( ! method_exists( $handler, 'schedule' ) ) {
				continue;
			}

			try {
				$handler->schedule();
			} catch ( \Throwable $e ) {
				Helper::errorLog( '[Event] Failed to schedule cron for ' . $handler::class . ': ' . $e->getMessage() );
			}
		}
	}
}
