<?php
/**
 * User OTP Profile Fields
 *
 * Adds phone number field to user profile for OTP verification.
 * Includes verification flow to confirm contact before use.
 *
 * @package HDAddons\LoginSecurity
 */

namespace HDAddons\LoginSecurity;

use HDAddons\Asset;
use HDAddons\LoginSecurity\ActivityLog\ActivityLog;
use HDAddons\LoginSecurity\Gateway\GatewayFactory;
use HDAddons\LoginSecurity\Totp\TotpHandler;

\defined( 'ABSPATH' ) || exit;

class UserOtpProfile {

	/**
	 * Meta key for verified contact status
	 */
	private const string META_VERIFIED = '_otp_contact_verified';

	/**
	 * Meta key for pending verification code
	 */
	private const string META_PENDING_CODE = '_otp_pending_code';

	/**
	 * Meta key for pending code expiry
	 */
	private const string META_PENDING_EXPIRY = '_otp_pending_expiry';

	/**
	 * Verification code expiry time (5 minutes)
	 */
	private const int CODE_EXPIRY = 300;

	/**
	 * Constructor - register hooks
	 */
	public function __construct() {
		$mode = $this->getOtpMode();

		// Only load if OTP SMS or TOTP mode is enabled
		if ( $mode !== 'sms' && $mode !== 'totp' ) {
			return;
		}

		// Add fields to user profile
		add_action( 'show_user_profile', $this->addOtpFields( ... ) );
		add_action( 'edit_user_profile', $this->addOtpFields( ... ) );

		// Save fields (SMS mode only — TOTP uses AJAX exclusively)
		if ( $mode === 'sms' ) {
			add_action( 'personal_options_update', $this->saveOtpFields( ... ) );
			add_action( 'edit_user_profile_update', $this->saveOtpFields( ... ) );

			// AJAX handlers for SMS verification
			add_action( 'wp_ajax_hda_send_otp_verification', $this->ajaxSendVerification( ... ) );
			add_action( 'wp_ajax_hda_verify_otp_code', $this->ajaxVerifyCode( ... ) );
		}

		// TOTP AJAX handlers
		if ( $mode === 'totp' ) {
			add_action( 'wp_ajax_hda_totp_generate', $this->ajaxTotpGenerate( ... ) );
			add_action( 'wp_ajax_hda_totp_verify', $this->ajaxTotpVerify( ... ) );
			add_action( 'wp_ajax_hda_totp_reset', $this->ajaxTotpReset( ... ) );
		}

		// Enqueue scripts on profile page
		add_action( 'admin_enqueue_scripts', $this->enqueueProfileScripts( ... ) );
	}

	/**
	 * Enqueue scripts for profile verification
	 *
	 * @param string $hookSuffix Current admin page
	 *
	 * @return void
	 */
	public function enqueueProfileScripts( string $hookSuffix ): void {
		if ( ! in_array( $hookSuffix, [ 'profile.php', 'user-edit.php' ], true ) ) {
			return;
		}

		// Enqueue CSS
		Asset::enqueueCSS( 'otp-profile.scss' );

		// Enqueue JS
		Asset::enqueueJS( 'otp-profile.js', [ 'jquery' ], null, true, [ 'defer', 'module' ] );

		// Localize script data
		Asset::localize( Asset::handle( 'otp-profile.js' ), 'hdaOtpVerify', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hda_otp_verify' ),
			'mode'    => $this->getOtpMode(),
			'i18n'    => [
				'sending'     => __( 'Sending...', HDA_TEXTDOMAIN ),
				'sendCode'    => __( 'Send Test Code', HDA_TEXTDOMAIN ),
				'verifying'   => __( 'Verifying...', HDA_TEXTDOMAIN ),
				'verify'      => __( 'Verify', HDA_TEXTDOMAIN ),
				'verified'    => __( 'Verified', HDA_TEXTDOMAIN ),
				'notVerified' => __( 'Not verified', HDA_TEXTDOMAIN ),
				'error'       => __( 'Error occurred', HDA_TEXTDOMAIN ),
				'enterValue'  => __( 'Please enter a value first.', HDA_TEXTDOMAIN ),
				'enterCode'   => __( 'Please enter the code.', HDA_TEXTDOMAIN ),
				// TOTP
				'generating'  => __( 'Generating...', HDA_TEXTDOMAIN ),
				'setupTotp'   => __( 'Set Up Authenticator', HDA_TEXTDOMAIN ),
				'resetting'   => __( 'Resetting...', HDA_TEXTDOMAIN ),
				'resetTotp'   => __( 'Reset Authenticator', HDA_TEXTDOMAIN ),
				'confirmReset' => __( 'Are you sure you want to reset your authenticator? You will need to scan a new QR code.', HDA_TEXTDOMAIN ),
			],
		] );
	}

	/**
	 * Get the current OTP mode.
	 *
	 * @return string 'disabled', 'email', 'sms', or 'totp'
	 */
	private function getOtpMode(): string {
		$options = LoginSecurity::getOptions();

		return $options[ LoginSecurity::KEY_OTP_MODE ] ?? 'disabled';
	}

	/**
	 * Check if OTP SMS mode is enabled
	 *
	 * @return bool
	 */
	private function isOtpSmsEnabled(): bool {
		return $this->getOtpMode() === 'sms';
	}

	/**
	 * Get the current gateway
	 *
	 * @return string
	 */
	private function getCurrentGateway(): string {
		$options = LoginSecurity::getOptions();

		return $options[ LoginSecurity::KEY_OTP_GATEWAY ] ?? 'telegram';
	}

	/**
	 * Check if user's contact is verified
	 *
	 * @param int $userId User ID
	 *
	 * @return bool
	 */
	private function isContactVerified( int $userId ): bool {
		$gateway  = $this->getCurrentGateway();
		$instance = GatewayFactory::create( $gateway );
		$metaKey  = $instance ? $instance->getUserMetaKey() : 'phone_number';
		$value    = get_user_meta( $userId, $metaKey, true );

		if ( empty( $value ) ) {
			return false;
		}

		// Check if this specific value is verified
		$verifiedData = get_user_meta( $userId, self::META_VERIFIED, true );

		return is_array( $verifiedData )
			&& ( $verifiedData['value'] ?? '' ) === $value
			&& ( $verifiedData['gateway'] ?? '' ) === $gateway;
	}

	/**
	 * Add OTP fields to user profile
	 *
	 * @param \WP_User $user
	 *
	 * @return void
	 */
	public function addOtpFields( \WP_User $user ): void {
		$mode = $this->getOtpMode();

		if ( $mode === 'totp' ) {
			$this->renderTotpSection( $user );
			return;
		}

		// SMS mode — dynamically render field based on gateway meta key
		$gateway  = $this->getCurrentGateway();
		$instance = GatewayFactory::create( $gateway );

		if ( ! $instance ) {
			return;
		}

		$metaKey    = $instance->getUserMetaKey();
		$metaValue  = get_user_meta( $user->ID, $metaKey, true );
		$isVerified = $this->isContactVerified( $user->ID );
		$isPhone    = $metaKey === 'phone_number';

		// Gateway-specific field labels and descriptions
		$fieldConfig = $this->getFieldConfig( $gateway );

		?>
		<h3 id="hda-otp-section"><?php esc_html_e( 'OTP Verification', HDA_TEXTDOMAIN ); ?></h3>
		<p class="description" style="margin-bottom: 15px;">
			<?php esc_html_e( 'Configure your contact for receiving OTP codes. If not set, OTP will be sent via email.', HDA_TEXTDOMAIN ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th>
					<label for="<?php echo esc_attr( $metaKey ); ?>"><?php echo esc_html( $fieldConfig['label'] ); ?></label>
				</th>
				<td>
					<div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
						<input
							type="<?php echo $isPhone ? 'tel' : 'text'; ?>"
							name="<?php echo esc_attr( $metaKey ); ?>"
							id="<?php echo esc_attr( $metaKey ); ?>"
							value="<?php echo esc_attr( $metaValue ); ?>"
							class="regular-text"
							placeholder="<?php echo esc_attr( $fieldConfig['placeholder'] ); ?>"
							data-original="<?php echo esc_attr( $metaValue ); ?>"
						>
						<button type="button" class="button hda-otp-send-code" data-user="<?php echo esc_attr( $user->ID ); ?>" data-field="<?php echo esc_attr( $metaKey ); ?>">
							<?php esc_html_e( 'Send Test Code', HDA_TEXTDOMAIN ); ?>
						</button>
						<span class="hda-otp-status <?php echo $isVerified ? 'verified' : 'not-verified'; ?>">
							<?php echo $isVerified ? '✓ ' . esc_html__( 'Verified', HDA_TEXTDOMAIN ) : '⚠ ' . esc_html__( 'Not verified', HDA_TEXTDOMAIN ); ?>
						</span>
					</div>
					<div class="hda-otp-message-wrap" style="margin-top: 8px;"><span class="hda-otp-message"></span></div>
					<div class="hda-otp-verify-row" style="display: none; margin-top: 10px;">
						<input type="text" class="hda-otp-code-input" placeholder="<?php esc_attr_e( 'Enter code', HDA_TEXTDOMAIN ); ?>" maxlength="6" style="width: 120px;">
						<button type="button" class="button hda-otp-verify-code" data-user="<?php echo esc_attr( $user->ID ); ?>">
							<?php esc_html_e( 'Verify', HDA_TEXTDOMAIN ); ?>
						</button>
					</div>
					<p class="description">
						<?php echo esc_html( $fieldConfig['description'] ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Get field configuration per gateway (label, placeholder, description).
	 *
	 * @param string $gateway
	 *
	 * @return array{label: string, placeholder: string, description: string}
	 */
	private function getFieldConfig( string $gateway ): array {
		return match ( $gateway ) {
			'telegram' => [
				'label'       => __( 'Telegram Chat ID', HDA_TEXTDOMAIN ),
				'placeholder' => '123456789',
				'description' => __( 'To get your Chat ID: 1) Open Telegram → 2) Search for our bot → 3) Send /start → 4) Copy the Chat ID shown.', HDA_TEXTDOMAIN ),
			],
			'zalo' => [
				'label'       => __( 'Phone Number', HDA_TEXTDOMAIN ),
				'placeholder' => '0912345678',
				'description' => __( 'Your phone number registered with Zalo. OTP will be sent via Zalo message.', HDA_TEXTDOMAIN ),
			],
			'whatsapp' => [
				'label'       => __( 'Phone Number', HDA_TEXTDOMAIN ),
				'placeholder' => '+84912345678',
				'description' => __( 'Your WhatsApp phone number in international format (+84...). OTP will be sent via WhatsApp.', HDA_TEXTDOMAIN ),
			],
			'smsgate' => [
				'label'       => __( 'Phone Number', HDA_TEXTDOMAIN ),
				'placeholder' => '+84912345678',
				'description' => __( 'Your phone number. Real SMS will be sent via the Android gateway device.', HDA_TEXTDOMAIN ),
			],
			'viber' => [
				'label'       => __( 'Viber User ID', HDA_TEXTDOMAIN ),
				'placeholder' => 'A1b2C3d4E5f6=',
				'description' => __( 'Open the Viber bot and send a message to get your User ID. Contact your admin for the bot link.', HDA_TEXTDOMAIN ),
			],
			'line' => [
				'label'       => __( 'LINE User ID', HDA_TEXTDOMAIN ),
				'placeholder' => 'U1234567890abcdef',
				'description' => __( 'Add the LINE bot as a friend and send a message to get your User ID. Contact your admin for the bot link.', HDA_TEXTDOMAIN ),
			],
			'discord' => [
				'label'       => __( 'Discord User ID', HDA_TEXTDOMAIN ),
				'placeholder' => '123456789012345678',
				'description' => __( 'Enable Developer Mode in Discord (Settings → Advanced), then right-click your username → Copy User ID.', HDA_TEXTDOMAIN ),
			],
			default => [
				'label'       => __( 'Contact ID', HDA_TEXTDOMAIN ),
				'placeholder' => '',
				'description' => __( 'Your contact identifier for receiving OTP codes.', HDA_TEXTDOMAIN ),
			],
		};
	}

	// ──────────────────────────────────────────────────────────
	// TOTP Profile Section
	// ──────────────────────────────────────────────────────────

	/**
	 * Render the TOTP setup section on the user profile page.
	 *
	 * @param \WP_User $user
	 *
	 * @return void
	 */
	private function renderTotpSection( \WP_User $user ): void {
		$isSetup = TotpHandler::isUserSetup( $user->ID );
		?>
		<h3 id="hda-totp-section"><?php esc_html_e( 'Authenticator App (TOTP)', HDA_TEXTDOMAIN ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th>
					<label><?php esc_html_e( 'Status', HDA_TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<div id="hda-totp-wrap" data-user="<?php echo esc_attr( $user->ID ); ?>">
						<?php if ( $isSetup ) : ?>
							<!-- Configured state -->
							<div id="hda-totp-configured">
								<span class="hda-otp-status verified">✓ <?php esc_html_e( 'Authenticator configured', HDA_TEXTDOMAIN ); ?></span>
								<p class="description" style="margin: 8px 0;">
									<?php esc_html_e( 'Your authenticator app is set up. You will be prompted for a code during login.', HDA_TEXTDOMAIN ); ?>
								</p>
								<button type="button" class="button button-link-delete" id="hda-totp-reset-btn">
									<?php esc_html_e( 'Reset Authenticator', HDA_TEXTDOMAIN ); ?>
								</button>
							</div>
						<?php else : ?>
							<!-- Not configured state -->
							<div id="hda-totp-not-configured">
								<span class="hda-otp-status not-verified">⚠ <?php esc_html_e( 'Not configured', HDA_TEXTDOMAIN ); ?></span>
								<p class="description" style="margin: 8px 0;">
									<?php esc_html_e( 'Set up an authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.) for two-factor authentication. Until setup, email OTP will be used as fallback.', HDA_TEXTDOMAIN ); ?>
								</p>
								<button type="button" class="button button-primary" id="hda-totp-setup-btn">
									<?php esc_html_e( 'Set Up Authenticator', HDA_TEXTDOMAIN ); ?>
								</button>
							</div>
						<?php endif; ?>

						<!-- Setup panel (shown via JS after clicking 'Set Up') -->
						<div id="hda-totp-setup-panel" style="display:none; margin-top:15px; padding:15px; border:1px solid #ccd0d4; background:#f9f9f9; max-width:500px;">
							<p><strong><?php esc_html_e( 'Step 1:', HDA_TEXTDOMAIN ); ?></strong> <?php esc_html_e( 'Scan this QR code with your authenticator app:', HDA_TEXTDOMAIN ); ?></p>
							<div id="hda-totp-qrcode" style="margin:10px 0;"></div>
							<p><strong><?php esc_html_e( 'Step 2:', HDA_TEXTDOMAIN ); ?></strong> <?php esc_html_e( 'Or enter this key manually:', HDA_TEXTDOMAIN ); ?></p>
							<code id="hda-totp-secret" style="display:block; padding:8px; font-size:14px; letter-spacing:1px; background:#fff; border:1px solid #ddd; user-select:all;"></code>
							<p style="margin-top:12px;"><strong><?php esc_html_e( 'Step 3:', HDA_TEXTDOMAIN ); ?></strong> <?php esc_html_e( 'Enter the 6-digit code from your app to verify:', HDA_TEXTDOMAIN ); ?></p>
							<div style="display:flex; gap:8px; align-items:center;">
								<input type="text" id="hda-totp-verify-input" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" style="width:120px; font-size:16px; letter-spacing:2px; text-align:center;">
								<button type="button" class="button button-primary" id="hda-totp-verify-btn">
									<?php esc_html_e( 'Verify & Activate', HDA_TEXTDOMAIN ); ?>
								</button>
							</div>
							<div id="hda-totp-message" style="margin-top:8px;"></div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	// ──────────────────────────────────────────────────────────
	// TOTP AJAX Handlers
	// ──────────────────────────────────────────────────────────

	/**
	 * AJAX: Generate a new TOTP secret and return otpauth URI.
	 */
	public function ajaxTotpGenerate(): void {
		check_ajax_referer( 'hda_otp_verify', 'nonce' );

		$userId = absint( $_POST['user_id'] ?? 0 );
		if ( ! $userId || ! current_user_can( 'edit_user', $userId ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		// Lazy-create table if needed
		if ( ! TotpHandler::tableExists() ) {
			TotpHandler::createTable();
		}

		$secret = TotpHandler::generateSecret();
		TotpHandler::saveSecret( $userId, $secret );

		$user   = get_user_by( 'id', $userId );
		$issuer = wp_parse_url( home_url(), PHP_URL_HOST ) ?: get_bloginfo( 'name' );
		$uri    = TotpHandler::getOtpauthUri( $secret, $user->user_login, $issuer );

		wp_send_json_success( [
			'secret' => $secret,
			'uri'    => $uri,
		] );
	}

	/**
	 * AJAX: Verify a TOTP code and activate for the user.
	 */
	public function ajaxTotpVerify(): void {
		check_ajax_referer( 'hda_otp_verify', 'nonce' );

		$userId = absint( $_POST['user_id'] ?? 0 );
		$code   = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );

		if ( ! $userId || ! $code || ! current_user_can( 'edit_user', $userId ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', HDA_TEXTDOMAIN ) ] );
		}

		$secret = TotpHandler::getUserSecret( $userId );
		if ( empty( $secret ) ) {
			wp_send_json_error( [ 'message' => __( 'No TOTP secret found. Please generate a new one.', HDA_TEXTDOMAIN ) ] );
		}

		$timeSlice = TotpHandler::verify( $secret, $code );
		if ( $timeSlice === false ) {
			wp_send_json_error( [ 'message' => __( 'Invalid code. Make sure your app is synced and try again.', HDA_TEXTDOMAIN ) ] );
		}

		TotpHandler::enableForUser( $userId );
		TotpHandler::setLastUsed( $userId, $timeSlice );

		// Log the event
		$user = get_user_by( 'id', $userId );
		ActivityLog::logEvent( $userId, $user ? $user->user_login : '', 'totp_setup' );

		wp_send_json_success( [ 'message' => __( 'Authenticator activated successfully!', HDA_TEXTDOMAIN ) ] );
	}

	/**
	 * AJAX: Reset (remove) TOTP for a user.
	 */
	public function ajaxTotpReset(): void {
		check_ajax_referer( 'hda_otp_verify', 'nonce' );

		$userId = absint( $_POST['user_id'] ?? 0 );
		if ( ! $userId || ! current_user_can( 'edit_user', $userId ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		TotpHandler::disableForUser( $userId );

		// Log the event
		$user = get_user_by( 'id', $userId );
		ActivityLog::logEvent( $userId, $user ? $user->user_login : '', 'totp_reset' );

		wp_send_json_success( [ 'message' => __( 'Authenticator has been reset.', HDA_TEXTDOMAIN ) ] );
	}

	/**
	 * AJAX: Send verification code
	 *
	 * @return void
	 */
	public function ajaxSendVerification(): void {
		check_ajax_referer( 'hda_otp_verify', 'nonce' );

		$userId = absint( $_POST['user_id'] ?? 0 );
		$field  = sanitize_key( $_POST['field'] ?? '' );
		$value  = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );

		if ( ! $userId || ! $field || ! $value ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', HDA_TEXTDOMAIN ) ] );
		}

		if ( ! current_user_can( 'edit_user', $userId ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		$gateway = GatewayFactory::create();
		if ( ! $gateway || ! $gateway->validateConfig() ) {
			wp_send_json_error( [ 'message' => __( 'Gateway not configured.', HDA_TEXTDOMAIN ) ] );
		}

		// Generate 6-digit code
		try {
			$code = str_pad( (string) random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => __( 'Failed to generate code.', HDA_TEXTDOMAIN ) ] );
		}

		// Send via gateway
		$sent = $gateway->send( $value, $code );

		if ( ! $sent ) {
			wp_send_json_error( [ 'message' => $gateway->getLastError() ?: __( 'Failed to send code.', HDA_TEXTDOMAIN ) ] );
		}

		// Store pending verification
		update_user_meta( $userId, self::META_PENDING_CODE, hash_hmac( 'sha256', $code, AUTH_SALT ) );
		update_user_meta( $userId, self::META_PENDING_EXPIRY, time() + self::CODE_EXPIRY );

		// Also store the pending value to verify
		update_user_meta( $userId, '_otp_pending_value', $value );
		update_user_meta( $userId, '_otp_pending_field', $field );

		wp_send_json_success( [ 'message' => __( 'Code sent! Check your device.', HDA_TEXTDOMAIN ) ] );
	}

	/**
	 * AJAX: Verify the code
	 *
	 * @return void
	 */
	public function ajaxVerifyCode(): void {
		check_ajax_referer( 'hda_otp_verify', 'nonce' );

		$userId = absint( $_POST['user_id'] ?? 0 );
		$code   = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );

		if ( ! $userId || ! $code ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', HDA_TEXTDOMAIN ) ] );
		}

		if ( ! current_user_can( 'edit_user', $userId ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', HDA_TEXTDOMAIN ) ] );
		}

		$storedHash  = get_user_meta( $userId, self::META_PENDING_CODE, true );
		$expiry      = (int) get_user_meta( $userId, self::META_PENDING_EXPIRY, true );
		$pendingValue = get_user_meta( $userId, '_otp_pending_value', true );

		if ( empty( $storedHash ) || time() > $expiry ) {
			wp_send_json_error( [ 'message' => __( 'Code expired. Please request a new one.', HDA_TEXTDOMAIN ) ] );
		}

		$codeHash = hash_hmac( 'sha256', $code, AUTH_SALT );
		if ( ! hash_equals( $storedHash, $codeHash ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid code.', HDA_TEXTDOMAIN ) ] );
		}

		// Mark as verified
		update_user_meta( $userId, self::META_VERIFIED, [
			'value'   => $pendingValue,
			'gateway' => $this->getCurrentGateway(),
			'time'    => time(),
		] );

		// Update the actual field value
		$field = get_user_meta( $userId, '_otp_pending_field', true );
		if ( $field ) {
			update_user_meta( $userId, $field, $pendingValue );
		}

		// Cleanup
		delete_user_meta( $userId, self::META_PENDING_CODE );
		delete_user_meta( $userId, self::META_PENDING_EXPIRY );
		delete_user_meta( $userId, '_otp_pending_value' );
		delete_user_meta( $userId, '_otp_pending_field' );

		wp_send_json_success( [ 'message' => __( 'Verified successfully!', HDA_TEXTDOMAIN ) ] );
	}

	/**
	 * Save OTP fields
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function saveOtpFields( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$gateway  = $this->getCurrentGateway();
		$instance = GatewayFactory::create( $gateway );

		if ( ! $instance ) {
			return;
		}

		$metaKey = $instance->getUserMetaKey();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WP core on profile save
		if ( ! isset( $_POST[ $metaKey ] ) ) {
			return;
		}

		$newValue = sanitize_text_field( wp_unslash( $_POST[ $metaKey ] ) );

		// Phone numbers: sanitize format
		if ( $metaKey === 'phone_number' ) {
			$newValue = in_array( $gateway, [ 'whatsapp', 'smsgate' ], true )
				? preg_replace( '/[^\d+]/', '', $newValue )  // Keep + for international
				: preg_replace( '/\D/', '', $newValue );      // Digits only (Zalo)
		}

		$oldValue = get_user_meta( $user_id, $metaKey, true );

		if ( ! empty( $newValue ) ) {
			update_user_meta( $user_id, $metaKey, $newValue );

			// Clear verification if value changed
			if ( $newValue !== $oldValue ) {
				delete_user_meta( $user_id, self::META_VERIFIED );
			}
		} else {
			delete_user_meta( $user_id, $metaKey );
			delete_user_meta( $user_id, self::META_VERIFIED );
		}
	}
}
