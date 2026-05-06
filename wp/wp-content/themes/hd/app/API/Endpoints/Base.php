<?php
/**
 * Class Base
 *
 * Registers and handles all REST API endpoints for global utilities,
 * such as lighthouse checks, site configuration, and notification hooks, v.v...
 *
 * @author HD
 */

namespace HD\App\API\Endpoints;

use HD\App\API\AbstractAPI;

defined( 'ABSPATH' ) || die;

class Base extends AbstractAPI {
	public function __construct() {
		$this->namespace = self::REST_NAMESPACE;
		$this->rest_base = 'base';
	}

	/** ---------------------------------------- */
	protected function registerRoutes(): void {}
}
