<?php
/**
 * Class Single
 *
 * Registers and handles all REST API endpoints for single resources in WordPress
 * (e.g., posts, pages, attachments).
 *
 * @author HD
 */

namespace HD\App\API\Endpoints;

use HD\App\API\AbstractAPI;
use HD\App\Modules\Services\PostView;
use HD\App\Modules\Services\Ajax;
use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

final class Single extends AbstractAPI {
	public function __construct() {
		$this->namespace = self::REST_NAMESPACE;
		$this->rest_base = 'single';
	}

	/** ---------------------------------------- */

	/**
	 * Register custom REST routes.
	 *
	 * @return void
	 */
	protected function registerRoutes(): void {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/track_views",
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => $this->trackViewsCallback( ... ),
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => static fn( $v ): bool => $v > 0,
					],
				],
			]
		);
	}

	/** ---------------------------------------- */

	/**
	 * Actual endpoint callback.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function trackViewsCallback( \WP_REST_Request $request ): \WP_Error|\WP_REST_Response {
		$nonceCheck = $this->verifyNonce( $request );
		if ( $nonceCheck instanceof \WP_REST_Response ) {
			return $nonceCheck;
		}

		$id = absint( $request['id'] ?? 0 );
		if ( ! $id || ! get_post( $id ) ) {
			return $this->sendResponse(
				[
					'success' => false,
					'message' => 'Invalid post ID.',
				],
				400
			);
		}

		$ip = Helper::ipAddress();

		try {
			$service = PostView::get_instance();
			$service->record_view( $id, $ip );
			$totalViews = $service->get_total_views( $id );
		} catch ( \Throwable $e ) {
			$message = WP_DEBUG
				? 'Failed to record view: ' . $e->getMessage()
				: 'An error occurred while recording view.';

			return $this->sendResponse(
				[
					'success' => false,
					'message' => $message,
				],
				500
			);
		}

		return $this->sendResponse(
			[
				'success' => true,
				'post_id' => $id,
				'ip'      => $ip,
				'time'    => time(),
				'views'   => $totalViews,
				'date'    => Helper::humanizeTime( $id ),
				'message' => 'View recorded successfully.',
			]
		);
	}
}
