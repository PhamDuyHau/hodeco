<?php
/**
 * Third-party plugin license/feature bypass for development testing.
 *
 * WARNING: This file is for development/testing purposes only.
 * Do not use in production environments.
 *
 * @author HD
 */

namespace HDAddons\ThirdParty;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Faker {
	// -------------------------------------------------------------

	/**
	 * Initialize faker hooks.
	 */
	public function __construct() {
		// ACF Pro license bypass (commented - enable for testing)
		// add_filter( 'pre_http_request', $this->acfLicenseRequest( ... ), 10, 3 );

		// Wordfence Security
		add_action( 'wp_loaded', $this->wordfencePre( ... ), 99 );

		// CF7 Google Sheet Connector Pro (commented - enable for testing)
		// add_action( 'wp_loaded', $this->cf7GscPro( ... ), 99 );

		// WooCommerce GSheetConnector Pro (commented - enable for testing)
		// add_action( 'wp_loaded', $this->woocommerceGscPro( ... ), 99 );
	}

	// -------------------------------------------------------------

	/**
	 * Intercept ACF Pro license HTTP requests.
	 *
	 * @param mixed $preempt Whether to preempt the request.
	 * @param array $parsed_args HTTP request arguments.
	 * @param string $url Request URL.
	 *
	 * @return mixed Modified response or original preempt value.
	 */
	public function acfLicenseRequest( mixed $preempt, array $parsed_args, string $url ): mixed {
		if ( ! Helper::isAcfProActive() ) {
			return $preempt;
		}

		// Check update request
		$acf_update_check_disabled = Helper::getOption( '_acf_update_check_disabled', true );
		if (
			$acf_update_check_disabled
			&& str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/update-check' )
		) {
			return $this->buildAcfResponse( [ 'checked' => [] ] );
		}

		// Intercept ACF activation request
		if ( str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/activate?p=pro' ) ) {
			return $this->buildAcfResponse(
				[
					'message'        => 'Licence key activated. Updates are now enabled',
					'license'        => 'GPL001122334455AA6677BB8899CC000',
					'license_status' => [
						'status'            => 'active',
						'lifetime'          => true,
						'name'              => 'Agency',
						'view_licenses_url' => 'https://www.advancedcustomfields.com/my-account/view-licenses/',
					],
					'status'         => 1,
				]
			);
		}

		// Intercept ACF validation request
		if ( str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/validate?p=pro' ) ) {
			return $this->buildAcfResponse(
				[
					'expiration'     => 864000,
					'license_status' => [
						'status'            => 'active',
						'lifetime'          => true,
						'name'              => 'Agency',
						'view_licenses_url' => 'https://www.advancedcustomfields.com/my-account/view-licenses/',
					],
					'status'         => 1,
				]
			);
		}

		// Intercept ACF get-info request
		if ( str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/get-info?p=pro' ) ) {
			return $this->buildAcfResponse(
				[
					'name'    => 'Advanced Custom Fields PRO',
					'slug'    => 'advanced-custom-fields-pro',
					'version' => '6.x.x',
				]
			);
		}

		return $preempt;
	}

	/**
	 * Build ACF HTTP response array.
	 *
	 * @param array $data Response data.
	 *
	 * @return array HTTP response array.
	 */
	private function buildAcfResponse( array $data ): array {
		return [
			'headers'  => [],
			'body'     => wp_json_encode( $data, JSON_INVALID_UTF8_IGNORE ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
		];
	}

	// -------------------------------------------------------------

	/**
	 * Set CF7 Google Sheet Connector Pro license.
	 */
	public function cf7GscPro(): void {
		if ( ! Helper::checkPluginActive( 'cf7-google-sheets-connector-pro/google-sheet-connector-pro.php' ) ) {
			return;
		}

		$this->setLicenseOptions(
			[
				'gs_license_key'    => 'license',
				'gs_license_status' => 'valid',
			]
		);
	}

	// -------------------------------------------------------------

	/**
	 * Set WooCommerce GSheetConnector Pro license.
	 */
	public function woocommerceGscPro(): void {
		if ( ! Helper::checkPluginActive( 'wc-gsheetconnector-pro/wc-gsheetconnector-pro.php' ) ) {
			return;
		}

		$this->setLicenseOptions(
			[
				'gs_woo_license_key'    => 'license',
				'gs_woo_license_status' => 'valid',
			]
		);
	}

	/**
	 * Set license options if not already set.
	 *
	 * @param array $options Option name => value pairs.
	 */
	private function setLicenseOptions( array $options ): void {
		foreach ( $options as $option_name => $new_value ) {
			$current_value = Helper::getOption( $option_name );
			if ( $current_value === false || $current_value !== $new_value ) {
				Helper::updateOption( $option_name, $new_value );
			}
		}
	}

	// -------------------------------------------------------------

	/**
	 * Configure Wordfence settings for development.
	 */
	public function wordfencePre(): void {
		if ( ! Helper::checkPluginActive( 'wordfence/wordfence.php' ) ) {
			return;
		}

		global $wpdb;

		$remaining_days = 365;
		$table_name     = $wpdb->prefix . 'wfconfig';

		// Add hd-addons to scan exclusions
		$this->addWordfenceScanExclusion( $wpdb, $table_name, '/hd-addons/*' );

		// Configure Wordfence license
		$this->configureWordfenceLicense( $remaining_days );
	}

	/**
	 * Add path to Wordfence scan exclusions.
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 * @param string $table_name Wordfence config table name.
	 * @param string $path Path to exclude from scanning.
	 */
	private function addWordfenceScanExclusion( \wpdb $wpdb, string $table_name, string $path ): void {
		$name = 'scan_exclude';

		$existing_val = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT val FROM {$wpdb->prefix}wfconfig WHERE name = %s",
				$name
			)
		);

		if ( $existing_val === null ) {
			// Insert new record
			$wpdb->insert(
				$table_name,
				[
					'name'     => $name,
					'val'      => $path,
					'autoload' => 'yes',
				],
				[ '%s', '%s', '%s' ]
			);
		} elseif ( empty( $existing_val ) ) {
			// Update empty value
			$wpdb->update(
				$table_name,
				[ 'val' => $path ],
				[ 'name' => $name ],
				[ '%s' ],
				[ '%s' ]
			);
		} elseif ( ! str_contains( $existing_val, $path ) ) {
			// Append to existing value
			$wpdb->update(
				$table_name,
				[ 'val' => $existing_val . ',' . $path ],
				[ 'name' => $name ],
				[ '%s' ],
				[ '%s' ]
			);
		}
	}

	/**
	 * Configure Wordfence license settings.
	 *
	 * @param int $remaining_days Days until license expiration.
	 */
	private function configureWordfenceLicense( int $remaining_days ): void {
		if ( ! class_exists( 'wfLicense' ) ) {
			return;
		}

		try {
			$date = new \DateTime();
			$date->modify( "+{$remaining_days} days" );

			\wfOnboardingController::_markAttempt1Shown();
			\wfConfig::set( 'onboardingAttempt3', \wfOnboardingController::ONBOARDING_LICENSE );

			if ( empty( \wfConfig::get( 'apiKey' ) ) ) {
				\wordfence::ajax_downgradeLicense_callback();
			}

			\wfConfig::set( 'isPaid', true );
			\wfConfig::set( 'keyType', \wfLicense::KEY_TYPE_PAID_CURRENT );
			\wfConfig::set( 'premiumNextRenew', $date->getTimestamp() );
			\wfWAF::getInstance()->getStorageEngine()->setConfig( 'wafStatus', \wfFirewall::FIREWALL_MODE_ENABLED );

			$wfLicense = \wfLicense::current();
			if ( $wfLicense ) {
				$wfLicense->setType( \wfLicense::TYPE_RESPONSE );
				$wfLicense->setPaid( true );
				$wfLicense->setRemainingDays( $remaining_days );
				$wfLicense->setConflicting( false );
				$wfLicense->setDeleted( false );
				$wfLicense->getKeyType();
			}
		} catch ( \Throwable $e ) {
			// Log error for debugging but don't break execution
			Helper::errorLog( '[Faker] Wordfence config error: ' . $e->getMessage() );
		}
	}
}
