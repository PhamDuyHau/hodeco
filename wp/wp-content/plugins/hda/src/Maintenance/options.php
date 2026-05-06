<?php
/**
 * Maintenance module options panel.
 *
 * @package HDAddons\Maintenance
 */

use HDAddons\Helper;
use HDAddons\Maintenance\Maintenance;

\defined( 'ABSPATH' ) || exit;

$mt_options      = Maintenance::getOptions();
$enabled         = $mt_options[ Maintenance::KEY_ENABLED ] ?? false;
$title           = $mt_options[ Maintenance::KEY_TITLE ] ?? '';
$message         = $mt_options[ Maintenance::KEY_MESSAGE ] ?? '';
$allowlist_ips   = $mt_options[ Maintenance::KEY_ALLOWLIST_IPS ] ?? [];
$allowlist_roles = $mt_options[ Maintenance::KEY_ALLOWLIST_ROLES ] ?? [];

// Get editable roles for selection.
$wp_roles = wp_roles()->get_names();
unset( $wp_roles['administrator'] ); // Admins always bypass.

?>
<div class="container">
	<input type="hidden" name="maintenance-hidden" value="1">

	<?php if ( $enabled ) : ?>
		<div class="hda-notice hda-notice--warning">
			<p>
				<strong>🚧 <?php esc_html_e( 'Maintenance mode is currently ACTIVE.', HDA_TEXTDOMAIN ); ?></strong>
				<?php esc_html_e( 'The frontend returns a 503 response to non-privileged visitors. Admins always have access.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>
	<?php endif; ?>

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Maintenance Settings', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php echo wp_kses_post( __( 'When enabled, all non-privileged visitors receive a <code>503 Service Unavailable</code> response with a <code>Retry-After</code> header. Search engines will understand the site is temporarily unavailable and will not de-index your pages.', HDA_TEXTDOMAIN ) ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="mt_enabled"><?php esc_html_e( 'Enable Maintenance Mode', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="mt_enabled" id="mt_enabled" <?php checked( $enabled, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Check to activate', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses( __( 'Returns <code>503 Service Unavailable</code> to all non-privileged visitors. Admins always bypass.', HDA_TEXTDOMAIN ), [ 'code' => [] ] ); ?></div>
			</div>
			<div class="cell section section-text mt-depends-enabled">
				<label class="heading" for="mt_title"><?php esc_html_e( 'Page Title', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="text" class="input" name="mt_title" id="mt_title" value="<?php echo esc_attr( $title ); ?>" placeholder="Under Maintenance">
					</div>
				</div>
			</div>
			<div class="cell section section-textarea mt-depends-enabled" style="grid-column:1/-1;">
				<label class="heading" for="mt_message"><?php esc_html_e( 'Message', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<textarea class="textarea" name="mt_message" id="mt_message" rows="3" placeholder="<?php esc_attr_e( 'We are currently performing scheduled maintenance...', HDA_TEXTDOMAIN ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
					</div>
				</div>
				<div class="desc"><?php echo wp_kses( __( 'Supports basic HTML (<code>&lt;a&gt;</code>, <code>&lt;strong&gt;</code>). Leave empty for default.', HDA_TEXTDOMAIN ), [ 'code' => [] ] ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- Access Control -->
	<fieldset class="container-fieldset mt-depends-enabled">
		<legend class="section-legend"><?php esc_html_e( 'Access Control', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Control who can bypass maintenance mode. Bypass priority: Administrators (always) → Allowlisted Roles → Allowlisted IPs. WP-Cron, AJAX, REST API, and WP-CLI requests are never blocked.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-select">
				<label class="heading" for="mt_allowlist_ips"><?php esc_html_e( 'Allowlist IP Addresses', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple placeholder="<?php esc_attr_e( 'Enter IP addresses', HDA_TEXTDOMAIN ); ?>" class="select select2-ips !w[100%]" name="mt_allowlist_ips[]" id="mt_allowlist_ips">
								<?php
								foreach ( $allowlist_ips as $ip ) {
									?>
									<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class="desc">
					<?php esc_html_e( 'Listed IPs can view the frontend during maintenance. Accepted formats:', HDA_TEXTDOMAIN ); ?>
					<ul style="margin:6px 0 0 18px;list-style:disc;">
						<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', HDA_TEXTDOMAIN ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', HDA_TEXTDOMAIN ) ); ?></li>
					</ul>
					<?php
					$current_ip = Helper::ipAddress();
					if ( $current_ip ) {
						printf(
							wp_kses_post( __( 'Your current IP: <code>%s</code>', HDA_TEXTDOMAIN ) ),
							esc_html( $current_ip )
						);
					}
					?>
				</div>
			</div>
			<div class="cell section section-radio">
				<span class="heading"><?php esc_html_e( 'Allowlist User Roles', HDA_TEXTDOMAIN ); ?></span>
				<div class="desc"><?php echo wp_kses( __( '<strong>Administrator</strong> always bypasses — cannot be unchecked.', HDA_TEXTDOMAIN ), [ 'strong' => [] ] ); ?></div>
				<div class="option inline-option">
					<div class="controls">
						<div class="inline-group">
							<?php foreach ( $wp_roles as $role_slug => $role_name ) : ?>
							<label>
								<input type="checkbox" class="checkbox" name="mt_allowlist_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $allowlist_roles, true ) ); ?>>
								<span><?php echo esc_html( translate_user_role( $role_name ) ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="desc"><?php esc_html_e( 'Checked roles can browse the site normally while maintenance mode is on.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>
</div>
