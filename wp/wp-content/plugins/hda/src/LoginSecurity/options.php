<?php

use HDAddons\Helper;
use HDAddons\LoginSecurity\Gateway\GatewayFactory;
use HDAddons\LoginSecurity\LoginAttempts;
use HDAddons\LoginSecurity\LoginSecurity;
use HDAddons\LoginSecurity\Totp\TotpHandler;


\defined( 'ABSPATH' ) || exit;

$login_security_options = Helper::getOption( LoginSecurity::OPTION_NAME );

// Login URL section
$custom_login_uri     = $login_security_options[ LoginSecurity::KEY_CUSTOM_LOGIN_URI ] ?? '';
$login_token_ip_check = $login_security_options[ LoginSecurity::KEY_LOGIN_TOKEN_IP_CHECK ] ?? '';

// Login OTP section
$otp_mode             = $login_security_options[ LoginSecurity::KEY_OTP_MODE ] ?? 'disabled';
$otp_gateway          = $login_security_options[ LoginSecurity::KEY_OTP_GATEWAY ] ?? 'telegram';
$otp_gateway_config   = $login_security_options[ LoginSecurity::KEY_OTP_GATEWAY_CONFIG ] ?? [];
$otp_user_roles       = $login_security_options[ LoginSecurity::KEY_OTP_USER_ROLES ] ?? [ 'editor', 'administrator' ];
$otp_ip_binding       = $login_security_options[ LoginSecurity::KEY_OTP_IP_BINDING ] ?? '';


// Login Protection section
$login_ips_access     = $login_security_options[ LoginSecurity::KEY_LOGIN_IPS_ACCESS ] ?? [];
$illegal_users        = $login_security_options[ LoginSecurity::KEY_ILLEGAL_USERS ] ?? '';
$limit_login_attempts = $login_security_options[ LoginSecurity::KEY_LIMIT_LOGIN_ATTEMPTS ] ?? 0;

// Privileged user check
$_options_default    = Helper::filterSettingOptions( 'security', false );
$privileged_user_ids = $_options_default['privileged_user_ids'] ?? [];
$user_id             = get_current_user_id();
$privileged          = in_array( $user_id, $privileged_user_ids, true );

// Available gateways (single source of truth)
$available_gateways = GatewayFactory::getAvailable();

// All roles
$all_roles = wp_roles()->get_names();

// ── Current user OTP setup status ─────────────────────────
$current_user_sms_verified = (bool) get_user_meta( $user_id, '_otp_contact_verified', true );
$current_user_totp_setup   = TotpHandler::isUserSetup( $user_id );

// Check if user is privileged
if ( empty( $privileged ) ) {
	echo '<h3>' . esc_html__( 'You do not have permission to access this page', HDA_TEXTDOMAIN ) . '</h3>';
	return;
}

?>
<div class="container">
	<input type="hidden" name="login-security-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- LOGIN PROTECTION SECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="login-protection-fieldset container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Login Protection', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Protect your login page from brute-force attacks, bot access, and unauthorized login attempts.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-select">
				<label class="heading" for="login_ips_access"><?php esc_html_e( 'Allowlist IPs Login Access', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple placeholder="Enter IP addresses" class="select select2-ips !w[100%]" name="login_ips_access" id="login_ips_access">
								<?php
								if ( $login_ips_access ) {
									foreach ( (array) $login_ips_access as $ip ) {
										?>
										<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
										<?php
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc">
					<?php esc_html_e( 'Only listed IPs can access the login page. All others are blocked. Accepted formats:', HDA_TEXTDOMAIN ); ?>
					<ul style="margin:6px 0 0 18px;list-style:disc;">
						<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', HDA_TEXTDOMAIN ) ); ?></li>
					</ul>
					<?php
					printf(
						/* translators: %s: current IP address */
						wp_kses_post( __( '🌐 Your current IP: <code>%s</code>', HDA_TEXTDOMAIN ) ),
						esc_html( Helper::ipAddress() )
					);
					?>
					<br>
					<?php echo wp_kses_post( __( '💡 <b>Recommended for static IP environments only</b> (offices, fixed VPN). If you use a dynamic IP (home internet, mobile), consider using <b>Custom Login URL</b>, <b>Login Verification (OTP/TOTP)</b>, or <b>Limit Login Attempts</b> instead.', HDA_TEXTDOMAIN ) ); ?>
					<br>
					<?php echo wp_kses_post( __( '⚠️ <b>Locked out?</b> Add <code>define(\'HDA_DISABLE_LOGIN_SECURITY\', true);</code> to <b>wp-config.php</b> to regain access.', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading" for="illegal_users"><?php esc_html_e( 'Disable Common Usernames', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="illegal_users" id="illegal_users" <?php checked( $illegal_users, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( __( 'Blocks <b>login attempts</b> and <b>new registrations</b> using common usernames (<b>admin</b>, <b>root</b>, <b>test</b>, <b>user</b>, etc.). Login attempts with blocked names are rejected immediately without checking the database.', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>

			<div class="cell section section-select">
				<label class="heading" for="limit_login_attempts"><?php esc_html_e( 'Limit Login Attempts', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="limit_login_attempts" id="limit_login_attempts">
								<?php foreach ( LoginAttempts::$login_attempts_data as $key => $value ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>"<?php echo selected( $limit_login_attempts, $key, false ); ?>><?php echo esc_html( $value ); ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( __( 'Counts <strong>failed password attempts</strong>. After reaching the limit, the IP is locked out with escalating durations: <b>1 hour → 24 hours → 7 days</b>.<br><em>(Note: For pure request-rate limiting to prevent DDoS, use Firewall → Rate Limiting).</em>', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="activity_log_enabled"><?php esc_html_e( 'Enable Activity Log', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="<?php echo esc_attr( LoginSecurity::KEY_ACTIVITY_LOG_ENABLED ); ?>" id="<?php echo esc_attr( LoginSecurity::KEY_ACTIVITY_LOG_ENABLED ); ?>" <?php checked( $login_security_options[ LoginSecurity::KEY_ACTIVITY_LOG_ENABLED ] ?? '', 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'Records login, logout, and failed attempts with IP & timestamp. Auto-cleaned after <b>30 days</b>.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>

		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- LOGIN URL SECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="login-url-fieldset container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Login URL', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--warning">
			<p>
				<span class="dashicons dashicons-warning"></span>
				<?php _e( '<strong>Important:</strong> Remember your custom login URL! If forgotten, add <code>define(\'HDA_DISABLE_LOGIN_SECURITY\', true);</code> to wp-config.php to restore default login.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-text">
				<label class="heading" for="custom_login_uri"><?php esc_html_e( 'Custom Admin Login URL', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls control-prefix" style="height: unset;">
						<div class="prefix">
							<span class="input-txt" title="<?php echo esc_attr( home_url( '/' ) ); ?>"><?php echo esc_html( home_url( '/' ) ); ?></span>
						</div>
						<?php $custom_login_uri = $custom_login_uri ?: 'wp-login.php'; ?>
						<input value="<?php echo esc_attr( $custom_login_uri ); ?>" class="input" type="text" id="custom_login_uri" name="custom_login_uri" placeholder="<?php echo esc_attr( $custom_login_uri ); ?>" style="max-width: 250px;">
					</div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( __( 'Hide the default <b>/wp-login.php</b> behind a custom slug. Automated bots targeting the default URL will get a <b>404</b>.', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading" for="login_token_ip_check"><?php esc_html_e( 'Strict IP Validation for Login Token', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="login_token_ip_check" id="login_token_ip_check" <?php checked( $login_token_ip_check, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( __( 'Login token is bound to the IP that generated it — prevents token theft via session hijacking.<br><span style="color:#b32d2e"><b>⚠️</b> May cause issues on mobile networks or VPNs where IP changes frequently.</span>', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- LOGIN VERIFICATION SECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="login-otp-fieldset container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Login Verification', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Two-Factor Authentication: Users must verify their identity via Email OTP, SMS/Messaging apps, or Authenticator App (TOTP).', HDA_TEXTDOMAIN ); ?>
			</p>
			<p class="hda-notice__detail">
				<?php esc_html_e( 'For SMS mode, each user must configure their phone/Telegram ID in their profile page. For TOTP mode, each user must scan a QR code with an authenticator app.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<!-- OTP Mode -->
		<div class="section section-radio section-otp-mode">
			<span class="heading"><?php esc_html_e( 'Login Verification Mode', HDA_TEXTDOMAIN ); ?></span>
			<div class="option inline-option">
				<div class="controls">
					<div class="inline-group">
						<label class="radio-label">
							<input type="radio" name="otp_mode" value="disabled" <?php checked( $otp_mode, 'disabled' ); ?>>
							<span><?php esc_html_e( 'Disabled', HDA_TEXTDOMAIN ); ?></span>
						</label>
						<label class="radio-label">
							<input type="radio" name="otp_mode" value="email" <?php checked( $otp_mode, 'email' ); ?>>
							<span><?php esc_html_e( 'Email OTP', HDA_TEXTDOMAIN ); ?></span>
						</label>
						<label class="radio-label">
							<input type="radio" name="otp_mode" value="sms" <?php checked( $otp_mode, 'sms' ); ?>>
							<span><?php esc_html_e( 'SMS / Messaging', HDA_TEXTDOMAIN ); ?></span>
							<?php if ( $current_user_sms_verified ) : ?>
								<em class="otp-setup-note ok">✓ <?php esc_html_e( 'Your account is verified', HDA_TEXTDOMAIN ); ?></em>
							<?php else : ?>
								<em class="otp-setup-note warning">⚠ <?php esc_html_e( 'Your account is not verified', HDA_TEXTDOMAIN ); ?></em>
							<?php endif; ?>
						</label>
						<label class="radio-label">
							<input type="radio" name="otp_mode" value="totp" <?php checked( $otp_mode, 'totp' ); ?>>
							<span><?php esc_html_e( 'Authenticator App (TOTP)', HDA_TEXTDOMAIN ); ?></span>
							<?php if ( $current_user_totp_setup ) : ?>
								<em class="otp-setup-note ok">✓ <?php esc_html_e( 'Your account is set up', HDA_TEXTDOMAIN ); ?></em>
							<?php else : ?>
								<em class="otp-setup-note warning">⚠ <?php esc_html_e( 'Your account is not set up', HDA_TEXTDOMAIN ); ?></em>
							<?php endif; ?>
						</label>
						<label class="radio-label">
							<input type="radio" name="otp_mode" value="magic_link" <?php checked( $otp_mode, 'magic_link' ); ?>>
							<span><?php esc_html_e( 'Magic Link (Passwordless)', HDA_TEXTDOMAIN ); ?></span>
							<em class="otp-setup-note ok hidden!"><?php esc_html_e( '✉ Email-based, no setup needed', HDA_TEXTDOMAIN ); ?></em>
						</label>
					</div>
				</div>
			</div>
			<div class="desc">
				<ul class="hda-otp-mode-list">
					<li><i>Email OTP</i> — one-time code sent via email after password login</li>
					<li><i>SMS / Messaging</i> — OTP via Telegram, Zalo, WhatsApp, SMSGate, Viber, LINE, or Discord (requires gateway setup). <a href="<?php echo esc_url( get_edit_profile_url( $user_id ) . '#hda-otp-section' ); ?>"><?php _e( 'Configure your account →', HDA_TEXTDOMAIN ); ?></a></li>
					<li><i>TOTP</i> — time-based code from authenticator apps (Google Authenticator, Authy, etc.). <a href="<?php echo esc_url( get_edit_profile_url( $user_id ) . '#hda-totp-section' ); ?>"><?php _e( 'Set up your account →', HDA_TEXTDOMAIN ); ?></a></li>
					<li><i>Magic Link</i> — <em>replaces</em> the password form entirely with email-based login</li>
				</ul>
			</div>
		</div>

		<!-- Gateway Selector (visible when SMS mode) -->
		<div class="section section-select otp-sms-only" style="<?php echo $otp_mode !== 'sms' ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_gateway"><?php esc_html_e( 'SMS Gateway', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<div class="select_wrapper">
						<select class="select" name="otp_gateway" id="otp_gateway">
							<?php foreach ( $available_gateways as $gateway_key => $gateway_label ) : ?>
								<option value="<?php echo esc_attr( $gateway_key ); ?>" <?php selected( $otp_gateway, $gateway_key ); ?>>
									<?php echo esc_html( $gateway_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="desc mb-6!"><?php esc_html_e( 'Select the gateway to send OTP messages.', HDA_TEXTDOMAIN ); ?></div>
		</div>

		<!-- Telegram Config -->
		<div class="section section-text otp-gateway-config gateway-telegram mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'telegram' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_telegram_bot_token"><?php esc_html_e( 'Telegram Bot Token', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="text"
						class="input !w[100%]"
						id="otp_telegram_bot_token"
						name="otp_gateway_config[telegram][bot_token]"
						value="<?php echo esc_attr( $otp_gateway_config['telegram']['bot_token'] ?? '' ); ?>"
						placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxYZ"
					>
				</div>
			</div>
			<div class="desc">
				<?php _e( 'Get your bot token from <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer"><strong>@BotFather</strong></a> on Telegram. This is <strong>FREE</strong> and unlimited.', HDA_TEXTDOMAIN ); ?>
				<details class="hda-gateway-details">
					<summary class="hda-gateway-details__summary"><?php _e( '📖 How to create a Telegram Bot', HDA_TEXTDOMAIN ); ?></summary>
					<ol class="hda-gateway-steps">
						<li><?php _e( 'Open Telegram and search for <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer"><strong>@BotFather</strong></a> (or click the link).', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Send the command <code>/newbot</code>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Enter a <strong>display name</strong> for your bot (e.g., <em>My Site OTP</em>).', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Enter a <strong>username</strong> ending with <code>bot</code> (e.g., <em>mysite_otp_bot</em>).', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'BotFather will reply with your <strong>Bot Token</strong> — copy and paste it into the field above.', HDA_TEXTDOMAIN ); ?></li>
					</ol>
				</details>
			</div>
		</div>

		<!-- Zalo Config -->
		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-text otp-gateway-config gateway-zalo" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'zalo' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_zalo_app_id"><?php esc_html_e( 'App ID', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_zalo_app_id"
							name="otp_gateway_config[zalo][app_id]"
							value="<?php echo esc_attr( $otp_gateway_config['zalo']['app_id'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="cell section section-text otp-gateway-config gateway-zalo" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'zalo' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_zalo_secret_key"><?php esc_html_e( 'Secret Key', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_zalo_secret_key"
							name="otp_gateway_config[zalo][secret_key]"
							value="<?php echo esc_attr( $otp_gateway_config['zalo']['secret_key'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
		</div>
		<div class="container flex flex-x gap sm-up-1 lg-up-2 mt-6">
			<div class="cell section section-text otp-gateway-config gateway-zalo" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'zalo' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_zalo_refresh_token"><?php esc_html_e( 'Refresh Token', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_zalo_refresh_token"
							name="otp_gateway_config[zalo][refresh_token]"
							value="<?php echo esc_attr( $otp_gateway_config['zalo']['refresh_token'] ?? '' ); ?>"
						>
					</div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( __( 'Refresh Token is used to auto-renew the Access Token. Valid for <b>30 days</b> — automatically extended on each use.', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>
			<div class="cell section section-text otp-gateway-config gateway-zalo" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'zalo' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_zalo_template_id"><?php esc_html_e( 'Template ID', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_zalo_template_id"
							name="otp_gateway_config[zalo][template_id]"
							value="<?php echo esc_attr( $otp_gateway_config['zalo']['template_id'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
		</div>
		<div class="section otp-gateway-config gateway-zalo" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'zalo' ) ? 'display:none;' : ''; ?>">
			<div class="desc">
				<?php _e( 'Get your credentials from <a href="https://developers.zalo.me/" target="_blank" rel="noopener noreferrer"><strong>Zalo for Developers</strong></a>. Access Token is <strong>auto-refreshed</strong> — you only need to set up once.', HDA_TEXTDOMAIN ); ?>
				<details class="hda-gateway-details">
					<summary class="hda-gateway-details__summary"><?php _e( '📖 How to set up Zalo ZNS', HDA_TEXTDOMAIN ); ?></summary>
					<ol class="hda-gateway-steps">
						<li><?php _e( 'Go to <a href="https://developers.zalo.me/" target="_blank" rel="noopener noreferrer"><strong>developers.zalo.me</strong></a> and log in with your Zalo account.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Create a new application → copy the <strong>App ID</strong> and <strong>Secret Key</strong>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Connect your <strong>Zalo Official Account (OA)</strong> to the application.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Go to <strong>ZNS</strong> → create an OTP <strong>message template</strong> and copy the <strong>Template ID</strong>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Use the <strong>API Explorer</strong> or OAuth flow to authorize your OA and obtain a <strong>Refresh Token</strong>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Paste all 4 values into the fields above. The plugin will <strong>auto-refresh</strong> the Access Token.', HDA_TEXTDOMAIN ); ?></li>
					</ol>
					<p class="hda-gateway-doc-link">
						<a href="https://developers.zalo.me/docs/zalo-notification-service/gui-zns/gui-zns-bang-api" target="_blank" rel="noopener noreferrer"><?php _e( '📚 Zalo ZNS API documentation →', HDA_TEXTDOMAIN ); ?></a>
					</p>
				</details>
			</div>
		</div>

		<!-- WhatsApp Config (Meta Cloud API) -->
		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-text otp-gateway-config gateway-whatsapp mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'whatsapp' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_whatsapp_phone_number_id"><?php esc_html_e( 'Phone Number ID', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_whatsapp_phone_number_id"
							name="otp_gateway_config[whatsapp][phone_number_id]"
							value="<?php echo esc_attr( $otp_gateway_config['whatsapp']['phone_number_id'] ?? '' ); ?>"
							placeholder="123456789012345"
						>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Your WhatsApp Business Phone Number ID from Meta Business Suite.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-text otp-gateway-config gateway-whatsapp mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'whatsapp' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_whatsapp_access_token"><?php esc_html_e( 'Access Token', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_whatsapp_access_token"
							name="otp_gateway_config[whatsapp][access_token]"
							value="<?php echo esc_attr( $otp_gateway_config['whatsapp']['access_token'] ?? '' ); ?>"
						>
					</div>
				</div>
				<div class="desc">
					<?php _e( 'Permanent Access Token from <a href="https://business.facebook.com/" target="_blank" rel="noopener noreferrer"><strong>Meta Business Suite</strong></a>. Free: 1,000 messages/month.', HDA_TEXTDOMAIN ); ?>
					<details class="hda-gateway-details">
						<summary class="hda-gateway-details__summary"><?php _e( '📖 How to set up WhatsApp Cloud API', HDA_TEXTDOMAIN ); ?></summary>
						<ol class="hda-gateway-steps">
							<li><?php _e( 'Go to <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener noreferrer"><strong>Meta for Developers</strong></a> and create or select an app.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Add the <strong>WhatsApp</strong> product to your app.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'In <strong>WhatsApp → API Setup</strong>, copy the <strong>Phone Number ID</strong> and paste it above.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Generate a <strong>Permanent Access Token</strong> (System User → Generate Token with <code>whatsapp_business_messaging</code> permission).', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Paste the token into the <strong>Access Token</strong> field above.', HDA_TEXTDOMAIN ); ?></li>
						</ol>
						<p class="hda-gateway-doc-link">
							<a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank" rel="noopener noreferrer"><?php _e( '📚 Official documentation →', HDA_TEXTDOMAIN ); ?></a>
						</p>
					</details>
				</div>
			</div>
		</div>

		<!-- SMSGate Config (Android SMS Gateway) -->
		<div class="container flex flex-x gap sm-up-1 lg-up-3">
			<div class="cell section section-text otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_smsgate_username"><?php esc_html_e( 'Username', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_smsgate_username"
							name="otp_gateway_config[smsgate][username]"
							value="<?php echo esc_attr( $otp_gateway_config['smsgate']['username'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="cell section section-text otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_smsgate_password"><?php esc_html_e( 'Password', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_smsgate_password"
							name="otp_gateway_config[smsgate][password]"
							value="<?php echo esc_attr( $otp_gateway_config['smsgate']['password'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="cell section section-text otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_smsgate_server_url"><?php esc_html_e( 'Server URL', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="url"
							class="input !w[100%]"
							id="otp_smsgate_server_url"
							name="otp_gateway_config[smsgate][server_url]"
							value="<?php echo esc_attr( $otp_gateway_config['smsgate']['server_url'] ?? '' ); ?>"
							placeholder="https://api.sms-gate.app"
						>
					</div>
				</div>
			</div>
			<div class="cell section otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<div class="desc">
					<?php _e( 'Turn your Android phone into an SMS gateway. <strong>Free & unlimited</strong>. Sends real SMS using your phone\'s SIM.', HDA_TEXTDOMAIN ); ?>
					<details class="hda-gateway-details">
						<summary class="hda-gateway-details__summary"><?php _e( '📖 How to set up SMSGate', HDA_TEXTDOMAIN ); ?></summary>
						<ol class="hda-gateway-steps">
							<li><?php _e( 'Install <a href="https://sms-gate.app" target="_blank" rel="noopener noreferrer"><strong>SMSGate</strong></a> app on an Android phone (5.0+).', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Open the app → select <strong>Cloud Mode</strong> (recommended).', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Copy the <strong>Username</strong> and <strong>Password</strong> from the app\'s Home screen.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Paste them into the fields above. Leave Server URL empty for Cloud mode.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Keep the phone <strong>always on</strong> with battery optimization disabled for SMSGate.', HDA_TEXTDOMAIN ); ?></li>
						</ol>
						<p class="hda-gateway-doc-link">
							<a href="https://docs.sms-gate.app/getting-started/" target="_blank" rel="noopener noreferrer"><?php _e( '📚 Official documentation →', HDA_TEXTDOMAIN ); ?></a>
						</p>
					</details>
				</div>
			</div>
		</div>

		<!-- Viber Config -->
		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-text otp-gateway-config gateway-viber" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'viber' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_viber_auth_token"><?php esc_html_e( 'Bot Auth Token', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_viber_auth_token"
							name="otp_gateway_config[viber][auth_token]"
							value="<?php echo esc_attr( $otp_gateway_config['viber']['auth_token'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="cell section section-text otp-gateway-config gateway-viber mt-6" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'viber' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_viber_sender_name"><?php esc_html_e( 'Sender Name', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_viber_sender_name"
							name="otp_gateway_config[viber][sender_name]"
							value="<?php echo esc_attr( $otp_gateway_config['viber']['sender_name'] ?? '' ); ?>"
							placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="cell section otp-gateway-config gateway-viber" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'viber' ) ? 'display:none;' : ''; ?>">
				<div class="desc">
					<?php _e( '1:1 messages via Viber Bot are <strong>free & unlimited</strong>. Users must send a message to the bot first.', HDA_TEXTDOMAIN ); ?>
					<details class="hda-gateway-details">
						<summary class="hda-gateway-details__summary"><?php _e( '📖 How to set up Viber Bot', HDA_TEXTDOMAIN ); ?></summary>
						<ol class="hda-gateway-steps">
							<li><?php _e( 'Go to <a href="https://partners.viber.com/account/create-bot-account" target="_blank" rel="noopener noreferrer"><strong>Viber Admin Panel</strong></a> and create a bot.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Copy the <strong>Auth Token</strong> from the bot settings.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Set up a <strong>webhook</strong> (your site URL + endpoint) to receive user subscriptions.', HDA_TEXTDOMAIN ); ?></li>
							<li><?php _e( 'Each user must <strong>open the bot in Viber</strong> and send a message — this registers their Viber User ID.', HDA_TEXTDOMAIN ); ?></li>
						</ol>
						<p class="hda-gateway-doc-link">
							<a href="https://developers.viber.com/docs/api/rest-bot-api/" target="_blank" rel="noopener noreferrer"><?php _e( '📚 Official documentation →', HDA_TEXTDOMAIN ); ?></a>
						</p>
					</details>
				</div>
			</div>
		</div>

		<!-- LINE Config -->
		<div class="section section-text otp-gateway-config gateway-line mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'line' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_line_channel_access_token"><?php esc_html_e( 'Channel Access Token', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="password"
						class="input !w[100%]"
						id="otp_line_channel_access_token"
						name="otp_gateway_config[line][channel_access_token]"
						value="<?php echo esc_attr( $otp_gateway_config['line']['channel_access_token'] ?? '' ); ?>"
					>
				</div>
			</div>
			<div class="desc">
				<?php _e( 'Free tier: <strong>500 messages/month</strong>. Users must add the bot as a friend.', HDA_TEXTDOMAIN ); ?>
				<details class="hda-gateway-details">
					<summary class="hda-gateway-details__summary"><?php _e( '📖 How to set up LINE Bot', HDA_TEXTDOMAIN ); ?></summary>
					<ol class="hda-gateway-steps">
						<li><?php _e( 'Go to <a href="https://developers.line.biz/console/" target="_blank" rel="noopener noreferrer"><strong>LINE Developers Console</strong></a> and create a provider.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Create a <strong>Messaging API</strong> channel.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'In the channel settings, issue a <strong>Channel Access Token</strong> (long-lived).', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Set up a <strong>webhook URL</strong> to capture user events and get LINE User IDs.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Each user must <strong>add the bot as a friend</strong> on LINE.', HDA_TEXTDOMAIN ); ?></li>
					</ol>
					<p class="hda-gateway-doc-link">
						<a href="https://developers.line.biz/en/docs/messaging-api/" target="_blank" rel="noopener noreferrer"><?php _e( '📚 Official documentation →', HDA_TEXTDOMAIN ); ?></a>
					</p>
				</details>
			</div>
		</div>

		<!-- Discord Config -->
		<div class="section section-text otp-gateway-config gateway-discord mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'discord' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_discord_bot_token"><?php esc_html_e( 'Discord Bot Token', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="password"
						class="input !w[100%]"
						id="otp_discord_bot_token"
						name="otp_gateway_config[discord][bot_token]"
						value="<?php echo esc_attr( $otp_gateway_config['discord']['bot_token'] ?? '' ); ?>"
					>
				</div>
			</div>
			<div class="desc">
				<?php _e( '<strong>Free & unlimited</strong>. Sends OTP as a Direct Message (DM). Users must share a server with the bot.', HDA_TEXTDOMAIN ); ?>
				<details class="hda-gateway-details">
					<summary class="hda-gateway-details__summary"><?php _e( '📖 How to set up Discord Bot', HDA_TEXTDOMAIN ); ?></summary>
					<ol class="hda-gateway-steps">
						<li><?php _e( 'Go to <a href="https://discord.com/developers/applications" target="_blank" rel="noopener noreferrer"><strong>Discord Developer Portal</strong></a> → <strong>New Application</strong>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Go to <strong>Bot</strong> tab → click <strong>Add Bot</strong> → copy the <strong>Bot Token</strong>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Under <strong>Privileged Gateway Intents</strong>, enable <strong>Server Members Intent</strong>.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Go to <strong>OAuth2 → URL Generator</strong>, select <code>bot</code> scope, then invite the bot to a shared server.', HDA_TEXTDOMAIN ); ?></li>
						<li><?php _e( 'Users provide their <strong>Discord User ID</strong>: enable Developer Mode (Settings → Advanced), then right-click username → <strong>Copy User ID</strong>.', HDA_TEXTDOMAIN ); ?></li>
					</ol>
					<p class="hda-gateway-doc-link">
						<a href="https://discord.com/developers/docs/intro" target="_blank" rel="noopener noreferrer"><?php _e( '📚 Official documentation →', HDA_TEXTDOMAIN ); ?></a>
					</p>
				</details>
			</div>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">

			<!-- OTP User Roles (visible when not disabled) -->
			<div class="cell section section-select otp-enabled-only mt-6" style="<?php echo $otp_mode === 'disabled' ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_user_roles"><?php esc_html_e( 'Required for Roles', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple class="select select2 select2-multiple !w[100%]" name="otp_user_roles" id="otp_user_roles">
								<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
									<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, (array) $otp_user_roles, true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $role_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'Only selected roles are required to verify. Unselected roles log in normally. Default: <b>Editor</b>, <b>Administrator</b>.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>

			<!-- OTP IP Binding (visible when not disabled) -->
			<div class="cell section section-checkbox otp-enabled-only mt-6" style="<?php echo $otp_mode === 'disabled' ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_ip_binding"><?php esc_html_e( 'Device Trust with IP Binding', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="otp_ip_binding"
								id="otp_ip_binding" <?php checked( $otp_ip_binding, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc">
					<?php echo wp_kses_post( __( 'Trusted-device cookie is bound to the user\'s IP — changing IP forces re-verification.<br><span style="color:#b32d2e"><b>⚠️</b> Not recommended for mobile users or VPN environments.</span>', HDA_TEXTDOMAIN ) ); ?>
				</div>
			</div>
		</div>
	</fieldset>
</div>

