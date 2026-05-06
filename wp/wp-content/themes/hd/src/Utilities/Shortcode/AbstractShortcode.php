<?php

namespace HD\Utilities\Shortcode;

defined( 'ABSPATH' ) || die;

abstract class AbstractShortcode {
	abstract protected function getShortcodes(): array;

	public function register(): void {
		foreach ( $this->getShortcodes() as $tag => $callback ) {
			if ( is_callable( $callback ) ) {
				$this->add( $tag, $callback );
			}
		}
	}

	/**
	 * @param string $tag
	 * @param callable $callback
	 *
	 * @return void
	 */
	protected function add( string $tag, callable $callback ): void {
		add_shortcode( $tag, $callback );
	}
}
