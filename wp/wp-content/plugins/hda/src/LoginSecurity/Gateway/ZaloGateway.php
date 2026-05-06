<?php
/**
 * Zalo ZNS Gateway
 *
 * Sends OTP via Zalo Notification Service (ZNS) template messages.
 * Handles OAuth token auto-refresh so admin only configures once.
 *
 * @package HDAddons\LoginSecurity\Gateway
 */

namespace HDAddons\LoginSecurity\Gateway;

use HDAddons\Helper;
use HDAddons\LoginSecurity\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

class ZaloGateway extends AbstractGateway {

	private const string API_URL       = 'https://business.openapi.zalo.me/message/template';
	private const string TOKEN_URL     = 'https://oauth.zaloapp.com/v4/oa/access_token';
	private const string TOKEN_OPTION  = 'hda_zalo_token_data';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'zalo';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'Zalo ZNS', HDA_TEXTDOMAIN );
	}

	/**
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'phone_number';
	}

	/**
	 * Validate that required OAuth credentials are configured.
	 *
	 * @return bool
	 */
	public function validateConfig(): bool {
		$app_id        = $this->getConfig( 'app_id' );
		$secret_key    = $this->getConfig( 'secret_key' );
		$refresh_token = $this->getConfig( 'refresh_token' );
		$template_id   = $this->getConfig( 'template_id' );

		if ( empty( $app_id ) ) {
			$this->setError( __( 'Zalo App ID is required.', HDA_TEXTDOMAIN ) );

			return false;
		}

		if ( empty( $secret_key ) ) {
			$this->setError( __( 'Zalo Secret Key is required.', HDA_TEXTDOMAIN ) );

			return false;
		}

		if ( empty( $refresh_token ) ) {
			$this->setError( __( 'Zalo Refresh Token is required.', HDA_TEXTDOMAIN ) );

			return false;
		}

		if ( empty( $template_id ) ) {
			$this->setError( __( 'Zalo Template ID is required.', HDA_TEXTDOMAIN ) );

			return false;
		}

		return true;
	}

	/**
	 * Send OTP via Zalo ZNS template message.
	 *
	 * @param string $to  Recipient phone number.
	 * @param string $otp OTP code.
	 *
	 * @return bool
	 */
	public function send( string $to, string $otp ): bool {
		if ( ! $this->validateConfig() ) {
			return false;
		}

		if ( empty( $to ) ) {
			$this->setError( __( 'User has no phone number configured.', HDA_TEXTDOMAIN ) );

			return false;
		}

		// Get a valid access token (auto-refreshes if expired)
		$access_token = $this->getAccessToken();

		if ( empty( $access_token ) ) {
			// Error already set by getAccessToken()
			return false;
		}

		// Format phone number for Zalo (84xxxxxxxxx)
		$phone       = $this->formatPhoneNumber( $to );
		$template_id = $this->getConfig( 'template_id' );

		$result = $this->makeRequest(
			self::API_URL,
			[
				'Content-Type' => 'application/json',
				'access_token' => $access_token,
			],
			wp_json_encode( [
				'phone'         => $phone,
				'template_id'   => $template_id,
				'template_data' => [
					'otp' => $otp,
				],
			] ),
		);

		if ( $result['body'] === null ) {
			if ( empty( $this->lastError ) ) {
				$this->setError( __( 'Zalo returned an invalid response.', HDA_TEXTDOMAIN ) );
			}

			return false;
		}

		$body = $result['body'];

		if ( ( $body['error'] ?? -1 ) !== 0 ) {
			$this->setError( $body['message'] ?? __( 'Unknown Zalo error', HDA_TEXTDOMAIN ) );

			return false;
		}

		return true;
	}

	// ══════════════════════════════════════════════════════
	//  OAUTH TOKEN MANAGEMENT
	// ══════════════════════════════════════════════════════

	/**
	 * Get a valid access token, auto-refreshing if expired.
	 *
	 * Token data is stored in wp_options with expiry timestamp.
	 * If the stored access_token is still valid, returns it directly.
	 * Otherwise, uses the refresh_token to obtain a new one from Zalo OAuth.
	 *
	 * @return string Access token, or empty string on failure.
	 */
	private function getAccessToken(): string {
		$tokenData = Helper::getOption( self::TOKEN_OPTION, [] );

		// Check if cached access token is still valid (with 60s buffer)
		if (
			! empty( $tokenData['access_token'] ) &&
			! empty( $tokenData['expires_at'] ) &&
			$tokenData['expires_at'] > ( time() + 60 )
		) {
			return $tokenData['access_token'];
		}

		// Need to refresh — use refresh_token from gateway config
		return $this->refreshAccessToken();
	}

	/**
	 * Refresh the access token using Zalo OAuth API.
	 *
	 * POST https://oauth.zaloapp.com/v4/oa/access_token
	 * Content-Type: application/x-www-form-urlencoded
	 * Header: secret_key
	 * Body: refresh_token, app_id, grant_type=refresh_token
	 *
	 * @return string New access token, or empty string on failure.
	 */
	private function refreshAccessToken(): string {
		$app_id        = $this->getConfig( 'app_id' );
		$secret_key    = $this->getConfig( 'secret_key' );
		$refresh_token = $this->getConfig( 'refresh_token' );

		$response = wp_remote_post( self::TOKEN_URL, [
			'timeout' => 15,
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'secret_key'   => $secret_key,
			],
			'body'    => [
				'refresh_token' => $refresh_token,
				'app_id'        => $app_id,
				'grant_type'    => 'refresh_token',
			],
		] );

		if ( is_wp_error( $response ) ) {
			$this->setError(
				sprintf(
					/* translators: %s: error message */
					__( 'Zalo token refresh failed: %s', HDA_TEXTDOMAIN ),
					$response->get_error_message()
				)
			);
			Helper::errorLog( 'HDA Zalo: Token refresh HTTP error - ' . $response->get_error_message() );

			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$errorMsg = $body['error_description'] ?? $body['message'] ?? __( 'Unknown error', HDA_TEXTDOMAIN );
			$this->setError(
				sprintf(
					/* translators: %s: error description */
					__( 'Zalo token refresh failed: %s', HDA_TEXTDOMAIN ),
					$errorMsg
				)
			);
			Helper::errorLog( 'HDA Zalo: Token refresh API error - ' . $errorMsg );

			return '';
		}

		// Cache the new access token with expiry
		$expiresIn = (int) ( $body['expires_in'] ?? 3600 );
		Helper::updateOption( self::TOKEN_OPTION, [
			'access_token' => $body['access_token'],
			'expires_at'   => time() + $expiresIn,
		] );

		// If Zalo returns a new refresh_token, persist it to gateway config
		if ( ! empty( $body['refresh_token'] ) && $body['refresh_token'] !== $refresh_token ) {
			$this->updateRefreshToken( $body['refresh_token'] );
		}

		return $body['access_token'];
	}

	/**
	 * Persist a new refresh_token back to the gateway config in wp_options.
	 *
	 * @param string $newRefreshToken The new refresh token from Zalo.
	 */
	private function updateRefreshToken( string $newRefreshToken ): void {
		$options = LoginSecurity::getOptions();

		if ( ! isset( $options[ LoginSecurity::KEY_OTP_GATEWAY_CONFIG ]['zalo'] ) ) {
			return;
		}

		$options[ LoginSecurity::KEY_OTP_GATEWAY_CONFIG ]['zalo']['refresh_token'] = $newRefreshToken;
		Helper::updateOption( LoginSecurity::OPTION_NAME, $options );
	}

	// ══════════════════════════════════════════════════════
	//  HELPERS
	// ══════════════════════════════════════════════════════

	/**
	 * Format phone number for Zalo (Vietnam format: 84xxxxxxxxx).
	 *
	 * @param string $phone Raw phone number.
	 *
	 * @return string Formatted phone number.
	 */
	private function formatPhoneNumber( string $phone ): string {
		// Remove all non-digit characters
		$phone = preg_replace( '/\D/', '', $phone );

		// Convert 0xxx to 84xxx
		if ( str_starts_with( $phone, '0' ) ) {
			$phone = '84' . substr( $phone, 1 );
		}

		// Add 84 prefix if missing
		if ( ! str_starts_with( $phone, '84' ) ) {
			$phone = '84' . $phone;
		}

		return $phone;
	}
}
