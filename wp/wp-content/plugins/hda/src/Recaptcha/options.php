<?php
/**
 * CAPTCHA module options panel.
 *
 * Supports Google reCAPTCHA v2 and Cloudflare Turnstile.
 *
 * @package HDAddons\Recaptcha
 */

use HDAddons\Recaptcha\Recaptcha;

\defined( 'ABSPATH' ) || exit;

$recaptcha_options = Recaptcha::getOptions();

// Google reCAPTCHA v2.
$recaptcha_v2_site_key   = $recaptcha_options[ Recaptcha::KEY_V2_SITE_KEY ] ?? '';
$recaptcha_v2_secret_key = $recaptcha_options[ Recaptcha::KEY_V2_SECRET_KEY ] ?? '';
$recaptcha_global        = $recaptcha_options[ Recaptcha::KEY_GLOBAL ] ?? false;

// Cloudflare Turnstile.
$turnstile_site_key   = $recaptcha_options[ Recaptcha::KEY_TURNSTILE_SITE_KEY ] ?? '';
$turnstile_secret_key = $recaptcha_options[ Recaptcha::KEY_TURNSTILE_SECRET_KEY ] ?? '';

// General.
$recaptcha_allowlist_ips = $recaptcha_options[ Recaptcha::KEY_ALLOWLIST_IPS ] ?? [];

// Provider & form protection.
$captcha_provider = $recaptcha_options[ Recaptcha::KEY_CAPTCHA_PROVIDER ] ?? '';

$hasRecaptchaKeys = ! empty( $recaptcha_v2_site_key ) && ! empty( $recaptcha_v2_secret_key );
$hasTurnstileKeys = ! empty( $turnstile_site_key ) && ! empty( $turnstile_secret_key );

?>
<div class="container">
	<input type="hidden" name="recaptcha-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- API KEYS -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'API Keys', HDA_TEXTDOMAIN ); ?></legend>

		<!-- Google reCAPTCHA v2 -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Google reCAPTCHA v2', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Protect your forms with Google reCAPTCHA v2 (Checkbox).', HDA_TEXTDOMAIN ); ?>
				<a target="_blank" href="https://www.google.com/recaptcha/admin"><?php esc_html_e( 'Get keys →', HDA_TEXTDOMAIN ); ?></a>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-text">
				<label class="heading" for="recaptcha_v2_site_key"><?php esc_html_e( 'Site Key', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $recaptcha_v2_site_key ); ?>" class="input" type="text" id="recaptcha_v2_site_key" name="recaptcha_v2_site_key">
					</div>
				</div>
			</div>

			<div class="cell section section-text">
				<label class="heading inline-heading" for="recaptcha_v2_secret_key"><?php esc_html_e( 'Secret Key', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $recaptcha_v2_secret_key ); ?>" class="input" type="text" id="recaptcha_v2_secret_key" name="recaptcha_v2_secret_key">
					</div>
				</div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading" for="recaptcha_global"><?php esc_html_e( 'Use recaptcha.net Domain', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="recaptcha_global" id="recaptcha_global" <?php checked( $recaptcha_global, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses( __( 'Load from <code>recaptcha.net</code> instead of <code>google.com</code> — required in regions where Google is blocked.', HDA_TEXTDOMAIN ), [ 'code' => [] ] ); ?></div>
			</div>
		</div>

		<!-- Cloudflare Turnstile -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Cloudflare Turnstile', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-cloud"></span>
				<?php esc_html_e( 'A privacy-friendly, free CAPTCHA alternative by Cloudflare. No puzzle-solving required.', HDA_TEXTDOMAIN ); ?>
				<a target="_blank" href="https://dash.cloudflare.com/?to=/:account/turnstile"><?php esc_html_e( 'Get keys →', HDA_TEXTDOMAIN ); ?></a>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-text">
				<label class="heading" for="turnstile_site_key"><?php esc_html_e( 'Site Key', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $turnstile_site_key ); ?>" class="input" type="text" id="turnstile_site_key" name="turnstile_site_key" placeholder="0x4AAAAAAA...">
					</div>
				</div>
			</div>

			<div class="cell section section-text">
				<label class="heading inline-heading" for="turnstile_secret_key"><?php esc_html_e( 'Secret Key', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $turnstile_secret_key ); ?>" class="input" type="text" id="turnstile_secret_key" name="turnstile_secret_key" placeholder="0x4AAAAAAA...">
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FORM PROTECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Form Protection', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-forms"></span>
				<?php esc_html_e( 'Select a CAPTCHA provider and choose which forms to protect. API keys must be configured above first.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-select">
				<label class="heading" for="captcha_provider"><?php esc_html_e( 'CAPTCHA Provider', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="captcha_provider" id="captcha_provider">
								<option value="" <?php selected( $captcha_provider, '' ); ?>><?php esc_html_e( 'Disabled', HDA_TEXTDOMAIN ); ?></option>
								<option value="recaptcha_v2" <?php selected( $captcha_provider, 'recaptcha_v2' ); ?> <?php disabled( ! $hasRecaptchaKeys ); ?>>
									<?php esc_html_e( 'Google reCAPTCHA v2', HDA_TEXTDOMAIN ); ?>
									<?php if ( ! $hasRecaptchaKeys ) : ?>
										— <?php esc_html_e( '(keys not configured)', HDA_TEXTDOMAIN ); ?>
									<?php endif; ?>
								</option>
								<option value="turnstile" <?php selected( $captcha_provider, 'turnstile' ); ?> <?php disabled( ! $hasTurnstileKeys ); ?>>
									<?php esc_html_e( 'Cloudflare Turnstile', HDA_TEXTDOMAIN ); ?>
									<?php if ( ! $hasTurnstileKeys ) : ?>
										— <?php esc_html_e( '(keys not configured)', HDA_TEXTDOMAIN ); ?>
									<?php endif; ?>
								</option>
							</select>
						</div>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Choose which CAPTCHA provider to use for form protection.', HDA_TEXTDOMAIN ); ?></div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading"><?php esc_html_e( 'Protected Forms', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls" style="flex-direction:column;gap:8px;">
						<label style="display:flex;align-items:center;gap:6px;">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Recaptcha::KEY_FORM_LOGIN ); ?>"
								<?php checked( $recaptcha_options[ Recaptcha::KEY_FORM_LOGIN ] ?? '', 1 ); ?> value="1">
							<?php esc_html_e( 'Login Form', HDA_TEXTDOMAIN ); ?>
						</label>
						<label style="display:flex;align-items:center;gap:6px;">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Recaptcha::KEY_FORM_REGISTER ); ?>"
								<?php checked( $recaptcha_options[ Recaptcha::KEY_FORM_REGISTER ] ?? '', 1 ); ?> value="1">
							<?php esc_html_e( 'Registration Form', HDA_TEXTDOMAIN ); ?>
						</label>
						<label style="display:flex;align-items:center;gap:6px;">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Recaptcha::KEY_FORM_LOST_PASSWORD ); ?>"
								<?php checked( $recaptcha_options[ Recaptcha::KEY_FORM_LOST_PASSWORD ] ?? '', 1 ); ?> value="1">
							<?php esc_html_e( 'Lost Password Form', HDA_TEXTDOMAIN ); ?>
						</label>
						<label style="display:flex;align-items:center;gap:6px;">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( Recaptcha::KEY_FORM_COMMENT ); ?>"
								<?php checked( $recaptcha_options[ Recaptcha::KEY_FORM_COMMENT ] ?? '', 1 ); ?> value="1">
							<?php esc_html_e( 'Comment Form', HDA_TEXTDOMAIN ); ?>
						</label>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Check which forms should require CAPTCHA verification.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>

		<!-- IP Allowlist -->
		<h4 class="section-subtitle"><?php esc_html_e( 'IP Allowlist', HDA_TEXTDOMAIN ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Listed IPs skip CAPTCHA verification entirely — useful for development, staging environments, or trusted office networks.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
		<div class="section section-select">
			<label class="heading" for="recaptcha_allowlist_ips"><?php esc_html_e( 'Bypass CAPTCHA for IPs', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<div class="select_wrapper">
						<select multiple class="select select2-ips !w[100%]" name="recaptcha_allowlist_ips[]" id="recaptcha_allowlist_ips">
							<?php foreach ( (array) $recaptcha_allowlist_ips as $ip ) : ?>
							<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="desc">
				<?php esc_html_e( 'Listed IPs skip CAPTCHA entirely — useful for development or trusted networks. Accepted formats:', HDA_TEXTDOMAIN ); ?>
				<ul style="margin:6px 0 0 18px;list-style:disc;">
					<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', HDA_TEXTDOMAIN ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', HDA_TEXTDOMAIN ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', HDA_TEXTDOMAIN ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', HDA_TEXTDOMAIN ) ); ?></li>
				</ul>
			</div>
		</div>
	</fieldset>
</div>
