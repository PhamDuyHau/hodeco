<?php

namespace HD\App\Events;

use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

abstract class AbstractEvent {
	protected string $hookName;
	protected string $interval = 'weekly';

	/** ---------------------------------------- */

	/**
	 * @param string $hookName
	 * @param string $interval
	 */
	public function __construct( string $hookName = '', string $interval = 'weekly' ) {
		$this->hookName = $hookName;
		$this->interval = $interval;

		if ( ! $this->hookName ) {
			return;
		}

		add_action( $this->hookName, $this->handle( ... ) );
	}

	/** ---------------------------------------- */
	abstract public function handle(): void;

	/** ---------------------------------------- */
	public function schedule(): void {
		if ( ! $this->hookName || ! $this->interval ) {
			return;
		}

		if ( wp_next_scheduled( $this->hookName ) ) {
			return;
		}

		wp_schedule_event( time(), $this->interval, $this->hookName );
		$this->log( "Cron job scheduled for '{$this->hookName}' with interval '{$this->interval}'." );
	}

	/** ---------------------------------------- */
	public function unschedule(): void {
		if ( ! $this->hookName ) {
			return;
		}

		$timestamp = wp_next_scheduled( $this->hookName );
		$timestamp && wp_unschedule_event( $timestamp, $this->hookName );
	}

	/** ---------------------------------------- */

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function log( string $message ): void {
		Helper::errorLog( '[' . static::class . '] ' . $message );
	}
}
