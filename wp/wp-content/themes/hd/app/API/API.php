<?php
/**
 * Main API controller for the theme/plugin.
 *
 * Handles REST API access restrictions, endpoint registration, and default route sanitization.
 *
 * @author HD
 */

namespace HD\App\API;

use HD\App\API\Endpoints\Base;
use HD\App\API\Endpoints\Single;
use HD\Utilities\Traits\Singleton;

final class API extends AbstractAPI {
	use Singleton;

	/* ---------- CONFIGURATION ------------------------------------------ */

	/**
	 * Allowlisted REST API namespaces - accessible without authentication.
	 */
	private readonly array $allowedNamespaces;

	/**
	 * Namespaces blocked from direct browser access (GET via URL bar).
	 * Only blocks GET requests without AJAX headers.
	 * POST/PUT/DELETE requests are allowed (handled by endpoint's own nonce check).
	 */
	private readonly array $browserBlockedNamespaces;

	/**
	 * Blocked WP core endpoints - require admin capability to access.
	 * Format: 'endpoint_type' => 'required_capability'
	 */
	private readonly array $blockedEndpoints;

	/* ---------- PRIVATE ------------------------------------------ */

	private array $endpointInstances = array();

	/**
	 * Explicit endpoint class list.
	 * Add new REST endpoints here instead of relying on filesystem auto-discovery.
	 *
	 * @return string[]
	 */
	private function endpointClasses(): array {
		return array(
			Base::class,
			Single::class,
		);
	}

	private function init(): void {
		/**
		 * Define RESTAPI_URL constant for easy access to the base REST API URL.
		 */
		if ( ! defined( 'RESTAPI_URL' ) ) {
			define( 'RESTAPI_URL', untrailingslashit( $this->restApiUrl() ) . '/' );
		}

		/**
		 * Allowlisted REST API namespaces for unauthenticated access.
		 * These namespaces are accessible without login.
		 */
		$this->allowedNamespaces = array(
			self::REST_NAMESPACE,      // Theme custom API (hd/v1)
			'contact-form-7/v1',       // Contact Form 7
			// 'api/v1',                // public API
		);

		/**
		 * Namespaces blocked from direct browser GET access.
		 * Prevents viewing API responses by typing URL in browser.
		 * Note: contact-form-7/v1 is handled by its own plugin, not included here.
		 */
		$this->browserBlockedNamespaces = array(
			self::REST_NAMESPACE,
		);

		/**
		 * Blocked WP core endpoints with required capabilities.
		 * Format: 'type' => 'capability'
		 * - 'post', 'page', 'attachment' → require 'edit_posts'
		 * - 'users' → require 'list_users' (admin only)
		 */
		$this->blockedEndpoints = array(
			'post'       => 'edit_posts',
			'page'       => 'edit_posts',
			'attachment' => 'edit_posts',
			'users'      => 'list_users',
		);

		/**
		 * Register hooks.
		 */
		add_action( 'init', $this->initRestClasses( ... ) );
		add_action( 'rest_api_init', $this->register_routes( ... ) );

		// Main REST API access control (handles authentication + capability check)
		add_filter( 'rest_authentication_errors', $this->restrictRestApi( ... ), 99 );

		// Hide blocked endpoints from REST discovery (security through obscurity)
		add_filter( 'rest_endpoints', $this->filterRestEndpoints( ... ) );
	}

	/* ---------- PROTECTED ------------------------------------------ */

	protected function registerRoutes(): void {
		foreach ( $this->endpointInstances as $api ) {
			if ( method_exists( $api, 'register_routes' ) ) {
				$api->register_routes();
			}
		}
	}

	/* ---------- PUBLIC: INITIALIZATION ------------------------------------- */

	/**
	 * Initialize REST endpoint classes from the explicit registry.
	 */
	public function initRestClasses(): void {
		foreach ( $this->endpointClasses() as $className ) {
			if ( class_exists( $className ) && is_subclass_of( $className, \WP_REST_Controller::class ) ) {
				$this->endpointInstances[] = new $className();
			}
		}
	}

	/* ---------- PUBLIC: MAIN ACCESS CONTROL ------------------------------------- */

	/**
	 * Main REST API access restriction.
	 *
	 * Security layers:
	 *  1. Check CSRF for protected namespaces (require nonce or AJAX header)
	 *  2. Allow whitelisted namespaces (allowedNamespaces) - public access
	 *  3. Block ALL wp-json/* for guests (not logged in)
	 *  4. Check blockedEndpoints capabilities for logged-in users
	 *
	 * @param mixed $result Current authentication result.
	 *
	 * @return mixed
	 */
	public function restrictRestApi( mixed $result ): mixed {
		if ( ! empty( $result ) ) {
			return $result;
		}

		$requestUri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		if ( empty( $requestUri ) ) {
			return $result;
		}

		// Layer 1: Block direct browser GET access to protected namespaces
		foreach ( $this->browserBlockedNamespaces as $ns ) {
			if ( str_contains( $requestUri, "/wp-json/{$ns}" ) ) {
				if ( $this->isDirectBrowserAccess() ) {
					return $this->createRestError(
						'rest_direct_access_forbidden',
						__( 'Direct browser access not allowed. Use AJAX to call this API.', TEXT_DOMAIN )
					);
				}

				// Not direct browser access, allow this request
				return $result;
			}
		}

		// Layer 2: Allow whitelisted namespaces (contact-form-7, api/v1, etc.)
		foreach ( $this->allowedNamespaces as $ns ) {
			if ( str_contains( $requestUri, "/wp-json/{$ns}" ) ) {
				return $result;
			}
		}

		// Layer 3: Block ALL wp-json/* for guests (not logged in)
		if ( str_contains( $requestUri, '/wp-json' ) && ! is_user_logged_in() ) {
			return $this->createRestError( 'rest_not_logged_in', __( 'Authentication required.', TEXT_DOMAIN ) );
		}

		// Layer 4: Check blocked endpoints for logged-in users
		if ( str_contains( $requestUri, '/wp-json/wp/v2' ) ) {
			$error = $this->checkBlockedEndpointAccess( $requestUri );
			if ( $error instanceof \WP_Error ) {
				return $error;
			}
		}

		return $result;
	}

	/**
	 * Check if request is a direct browser access (typing URL in browser).
	 *
	 * Direct browser access characteristics:
	 *  - GET request method
	 *  - No X-Requested-With header (AJAX)
	 *  - Accept header contains text/html (browser default)
	 *
	 * @return bool True if direct browser access, false if AJAX/programmatic.
	 */
	private function isDirectBrowserAccess(): bool {
		// Only check GET requests - POST/PUT/DELETE are likely from forms/AJAX
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if ( $method !== 'GET' ) {
			return false;
		}

		// Has X-Requested-With header = AJAX request
		$xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
		if ( strtolower( $xRequestedWith ) === 'xmlhttprequest' ) {
			return false;
		}

		// Has X-WP-Nonce header = programmatic request
		$nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
		if ( ! empty( $nonce ) ) {
			return false;
		}

		// Accept header contains text/html = browser
		// Fetch/AJAX typically sends application/json or */*
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		if ( str_contains( $accept, 'text/html' ) ) {
			return true;
		}

		// No clear indicators, allow by default (could be curl, Postman, etc.)
		return false;
	}

	/**
	 * Check if logged-in user has permission to access blocked endpoint.
	 *
	 * @param string $requestUri The request URI.
	 *
	 * @return \WP_Error|null WP_Error if blocked, null if allowed.
	 */
	private function checkBlockedEndpointAccess( string $requestUri ): ?\WP_Error {
		// Exception: /users/me is allowed for any logged-in user
		if ( preg_match( '#/wp-json/wp/v2/users/me\b#', $requestUri ) ) {
			return null;
		}

		foreach ( $this->blockedEndpoints as $type => $capability ) {
			// Get REST base for this type
			$restBase = $this->getRestBaseForType( $type );

			if ( ! $restBase ) {
				continue;
			}

			// Check if request matches this endpoint
			if ( str_contains( $requestUri, "/wp-json/wp/v2/{$restBase}" ) ) {
				if ( ! current_user_can( $capability ) ) {
					return $this->createRestError(
						'rest_forbidden',
						/* translators: %s: capability name */
						sprintf( __( 'You need "%s" capability to access this endpoint.', TEXT_DOMAIN ), $capability )
					);
				}

				// Found matching endpoint and user has permission
				return null;
			}
		}

		// No matching blocked endpoint found - allow by default
		return null;
	}

	/**
	 * Get REST base name for a given type.
	 *
	 * @param string $type Endpoint type (post type or 'users').
	 *
	 * @return string|null REST base or null if not found.
	 */
	private function getRestBaseForType( string $type ): ?string {
		if ( $type === 'users' ) {
			return 'users';
		}

		$obj = get_post_type_object( $type );

		if ( ! $obj ) {
			return null;
		}

		return $obj->rest_base ?: $obj->name;
	}

	/**
	 * Create a WP_Error for REST API responses.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return \WP_Error
	 */
	private function createRestError( string $code, string $message, int $status = 403 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/* ---------- PUBLIC: ENDPOINT FILTERING ------------------------------------- */

	/**
	 * Hide unwanted routes from REST discovery.
	 *
	 * @param array $endpoints All registered endpoints.
	 *
	 * @return array Filtered endpoints.
	 */
	public function filterRestEndpoints( array $endpoints ): array {
		// Hide root discovery endpoints
		unset( $endpoints['/'], $endpoints['/wp/v2'] );

		// Hide blocked endpoints from discovery
		foreach ( $this->blockedEndpoints as $type => $capability ) {
			if ( $type === 'users' ) {
				// Note: /users/me is NOT hidden - it's allowed for logged-in users
				unset(
					$endpoints['/wp/v2/users'],
					$endpoints['/wp/v2/users/(?P<id>[\d]+)']
				);
			} else {
				$obj = get_post_type_object( $type );
				if ( ! $obj ) {
					continue;
				}

				$base = $obj->rest_base ?: $obj->name;
				unset(
					$endpoints[ "/wp/v2/{$base}" ],
					$endpoints[ "/wp/v2/{$base}/(?P<id>[\\d]+)" ]
				);
			}
		}

		return $endpoints;
	}
}
