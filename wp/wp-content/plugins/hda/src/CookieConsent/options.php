<?php
/**
 * Cookie Consent module options panel.
 *
 * @package HDAddons\CookieConsent
 */

use HDAddons\CookieConsent\CookieConsent;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$cc_options   = Helper::getOption( CookieConsent::OPTION_NAME, [] );
$enabled      = $cc_options[ CookieConsent::KEY_ENABLED ] ?? false;
$message      = $cc_options[ CookieConsent::KEY_MESSAGE ] ?? '';
$accept_text  = $cc_options[ CookieConsent::KEY_ACCEPT_TEXT ] ?? '';
$dismiss_text = $cc_options[ CookieConsent::KEY_DISMISS_TEXT ] ?? '';
$privacy_url  = $cc_options[ CookieConsent::KEY_PRIVACY_URL ] ?? '';
$privacy_text = $cc_options[ CookieConsent::KEY_PRIVACY_TEXT ] ?? '';
$position     = $cc_options[ CookieConsent::KEY_POSITION ] ?? 'bottom';
$consent_days = $cc_options[ CookieConsent::KEY_CONSENT_DAYS ] ?? 180;
$dismiss_days = $cc_options[ CookieConsent::KEY_DISMISS_DAYS ] ?? 7;

?>
<div class="container">
	<input type="hidden" name="cookie_consent-hidden" value="1">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- BANNER SETTINGS -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Banner Settings', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php echo wp_kses_post( __( 'Displays a non-intrusive cookie consent banner on the frontend. Consent is stored client-side as a cookie — no server-side tracking or personal data is collected. <strong>Accept</strong> sets a long-term cookie, <strong>Dismiss</strong> hides the banner temporarily.', HDA_TEXTDOMAIN ) ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="cc_enabled"><?php esc_html_e( 'Enable Cookie Banner', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="cc_enabled" id="cc_enabled" <?php checked( $enabled, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
			<div class="cell section section-select cc-depends-enabled">
				<label class="heading" for="cc_position"><?php esc_html_e( 'Position', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="cc_position" id="cc_position">
								<option value="bottom" <?php selected( $position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', HDA_TEXTDOMAIN ); ?></option>
								<option value="top" <?php selected( $position, 'top' ); ?>><?php esc_html_e( 'Top', HDA_TEXTDOMAIN ); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Fixed bar at the top or bottom of the viewport.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-textarea cc-depends-enabled" style="grid-column:1/-1;">
				<label class="heading" for="cc_message"><?php esc_html_e( 'Banner Message', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<textarea class="textarea" name="cc_message" id="cc_message" rows="3" placeholder="<?php esc_attr_e( 'We use cookies to improve your experience...', HDA_TEXTDOMAIN ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
					</div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'Supports basic HTML (<code>&lt;a&gt;</code>, <code>&lt;strong&gt;</code>). Leave empty for default message.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>
			<div class="cell section section-text cc-depends-enabled">
				<label class="heading" for="cc_accept_text"><?php esc_html_e( 'Accept Button Text', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="text" class="input" name="cc_accept_text" id="cc_accept_text" value="<?php echo esc_attr( $accept_text ); ?>" placeholder="Accept">
					</div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'User agrees to cookies — banner hidden for <b>consent</b> duration.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>
			<div class="cell section section-text cc-depends-enabled">
				<label class="heading" for="cc_dismiss_text"><?php esc_html_e( 'Dismiss Button Text', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="text" class="input" name="cc_dismiss_text" id="cc_dismiss_text" value="<?php echo esc_attr( $dismiss_text ); ?>" placeholder="Close">
					</div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'User closes without accepting — banner reappears after <b>dismiss</b> duration.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>
			<div class="cell section section-text cc-depends-enabled">
				<label class="heading" for="cc_privacy_url"><?php esc_html_e( 'Privacy Policy URL', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="url" class="input" name="cc_privacy_url" id="cc_privacy_url" value="<?php echo esc_url( $privacy_url ); ?>" placeholder="https://">
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Adds a "Privacy Policy" link at the end of the banner message. Leave empty to hide.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-text cc-depends-enabled cc-depends-privacy-url">
				<label class="heading" for="cc_privacy_text"><?php esc_html_e( 'Privacy Link Text', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="text" class="input" name="cc_privacy_text" id="cc_privacy_text" value="<?php echo esc_attr( $privacy_text ); ?>" placeholder="Privacy Policy">
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Visible text for the privacy link. Only displays when a URL is set above.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- COOKIE DURATION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset cc-depends-enabled">
		<legend class="section-legend"><?php esc_html_e( 'Cookie Duration', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Controls how long the banner stays hidden after user interaction. Separate durations let you re-prompt visitors who only dismissed without consenting.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-text">
				<label class="heading" for="cc_consent_days"><?php esc_html_e( 'Consent Cookie (days)', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input" name="cc_consent_days" id="cc_consent_days" value="<?php echo esc_attr( $consent_days ); ?>" min="1" max="365" placeholder="180">
					</div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'Banner stays hidden after "Accept" for this many days. Default: <b>180</b>.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>
			<div class="cell section section-text">
				<label class="heading" for="cc_dismiss_days"><?php esc_html_e( 'Dismiss Cookie (days)', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input" name="cc_dismiss_days" id="cc_dismiss_days" value="<?php echo esc_attr( $dismiss_days ); ?>" min="1" max="90" placeholder="7">
					</div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'Banner reappears after "Close" once this period expires. Default: <b>7</b>.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>
		</div>
	</fieldset>
</div>
