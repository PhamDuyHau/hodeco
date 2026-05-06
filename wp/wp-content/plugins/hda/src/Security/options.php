<?php
/**
 * Security module options panel.
 */

use HDAddons\Helper;
use HDAddons\Security\Security;
use HDAddons\Security\Countries;
use HDAddons\Security\AccessControl;
use HDAddons\Security\ServerConfig\ServerConfig;

\defined( 'ABSPATH' ) || exit;

$security_options    = Helper::getOption( Security::OPTION_NAME, [] );
$comments_off        = $security_options[ Security::KEY_COMMENTS_OFF ] ?? false;
$xmlrpc_off          = $security_options[ Security::KEY_XMLRPC_OFF ] ?? false;
$hide_wp_version     = $security_options[ Security::KEY_HIDE_WP_VERSION ] ?? false;
$wp_links_opml_off   = $security_options[ Security::KEY_WP_LINKS_OPML_OFF ] ?? false;
$rss_feed_off        = $security_options[ Security::KEY_RSS_FEED_OFF ] ?? false;
$remove_readme       = $security_options[ Security::KEY_REMOVE_README ] ?? false;

$app_passwords_off   = $security_options[ Security::KEY_APP_PASSWORDS_OFF ] ?? false;
$server_config       = $security_options[ Security::KEY_SERVER_CONFIG ] ?? false;
$lock_files          = $security_options[ Security::KEY_LOCK_FILES ] ?? false;

// WAF options (separate wp_option for clean separation).
$waf_options    = Helper::getOption( AccessControl::OPTION_NAME, [] );
$blocked        = $waf_options[ AccessControl::KEY_BLOCKED_COUNTRIES ] ?? [];
$country_mode   = $waf_options[ AccessControl::KEY_COUNTRY_MODE ] ?? 'block_selected';
$block_unknown  = ! empty( $waf_options[ AccessControl::KEY_BLOCK_UNKNOWN ] );
$blocked_ips    = $waf_options[ AccessControl::KEY_BLOCKED_IPS ] ?? [];
$countries      = Countries::getAll();
$is_cf          = AccessControl::isCloudflare();

// Server config detection.
$server_type    = ServerConfig::detectServerType();
$server_label   = ServerConfig::getServerLabel();
$is_apache      = ServerConfig::isApache();
$is_nginx       = ServerConfig::isNginx();
$has_block      = ServerConfig::hasBlock();
$config_file    = $is_apache ? ServerConfig::getHtaccessPath() : ( $is_nginx ? ServerConfig::getNginxConfPath() : '' );

// File lock status.
$file_lock_status = ServerConfig::getFileLockStatus();

?>
<div class="container">
	<input type="hidden" name="security-hidden" value="1">

	<?php if ( ! $is_cf ) : ?>
	<div class="hda-notice hda-notice--warning">
		<p>
			<span class="dashicons dashicons-cloud"></span>
			<strong><?php esc_html_e( 'Cloudflare Recommended', HDA_TEXTDOMAIN ); ?></strong> —
			<?php esc_html_e( 'Your site does not appear to be behind Cloudflare. For the best protection, we strongly recommend using Cloudflare\'s free WAF and IP/country blocking at the edge — traffic is blocked before it reaches your server.', HDA_TEXTDOMAIN ); ?>
			<a href="https://www.cloudflare.com/waf/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more →', HDA_TEXTDOMAIN ); ?></a>
		</p>
	</div>
	<?php endif; ?>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- WORDPRESS SECURITY HARDENING -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'WordPress Security Hardening', HDA_TEXTDOMAIN ); ?></legend>

		<!-- ══════════════════════════════════════════════════════════════════ -->
		<!-- NOTICE -->
		<!-- ══════════════════════════════════════════════════════════════════ -->
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'These settings harden your WordPress installation by disabling unnecessary features and blocking common attack vectors. Changes take effect immediately.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<div class="cell section section-checkbox">
				<label class="heading" for="comments_off"><?php esc_html_e( 'Disable Comments', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="comments_off" id="comments_off" <?php checked( $comments_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Disable Comments', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Completely disables comments, pingbacks, and trackbacks. Removes comment forms, admin menu items, toolbar links, and all related functionality site-wide.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="xmlrpc_off"><?php esc_html_e( 'Disable XMLRPC', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="xmlrpc_off" id="xmlrpc_off" <?php checked( $xmlrpc_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Disable xmlrpc.php', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Disables XML-RPC — the legacy remote publishing protocol. Commonly exploited for brute-force login attacks and DDoS amplification. Safe to disable unless you use external apps (e.g. WordPress mobile app, Jetpack) that rely on it.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="hide_wp_version"><?php esc_html_e( 'Hide WordPress Version', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="hide_wp_version" id="hide_wp_version" <?php checked( $hide_wp_version, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Hide WP Version', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Removes the WordPress version number from the HTML generator meta tag, RSS feed headers, and admin footer text. Prevents attackers from identifying your exact WP version.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="wp_links_opml_off"><?php esc_html_e( 'Disable wp-links-opml', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="wp_links_opml_off" id="wp_links_opml_off" <?php checked( $wp_links_opml_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Disable wp-links-opml.php', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Blocks access to wp-links-opml.php — a legacy blogroll export file. Rarely used in modern WordPress, but publicly exposes site metadata.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="rss_feed_off"><?php esc_html_e( 'Disable RSS Feeds', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="rss_feed_off" id="rss_feed_off" <?php checked( $rss_feed_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Disable RSS', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Disables all RSS and Atom feed endpoints. Redirects feed URLs to the homepage. Prevents automated content scraping via feeds. Do not enable if you need RSS for newsletters or feed readers.', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="cell section section-checkbox">
				<label class="heading" for="remove_readme"><?php esc_html_e( 'Delete readme.html', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="remove_readme" id="remove_readme" <?php checked( $remove_readme, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Remove readme.html', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php echo wp_kses_post( __( 'Automatically deletes <code>readme.html</code> from the WordPress root directory. This file publicly discloses the WordPress version and is regenerated after each core update.', HDA_TEXTDOMAIN ) ); ?></div>
			</div>

			<div class="cell section section-checkbox">
				<label class="heading" for="app_passwords_off"><?php esc_html_e( 'Disable Application Passwords', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="app_passwords_off" id="app_passwords_off" <?php checked( $app_passwords_off, 1 ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Disable App Passwords (WP 5.6+)', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Disables the Application Passwords feature introduced in WordPress 5.6. Removes the UI from user profiles and blocks the REST API authentication endpoint. Recommended if you do not use external apps that authenticate via the REST API.', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- ACCESS CONTROL (Country / IP / Range blocking) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Access Control', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php
				printf(
					/* translators: %s: server type */
					esc_html__( 'Detected server: %s. IP and range blocking uses native server rules — no extra modules needed. Country blocking uses MaxMind GeoLite2 database at PHP level.', HDA_TEXTDOMAIN ),
					'<strong>' . esc_html( $server_label ) . '</strong>'
				);
				?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<!-- Country Blocking Mode -->
			<div class="cell section section-radio">
				<label class="heading"><?php esc_html_e( 'Country Blocking Mode', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<label style="margin-right:16px;">
							<input type="radio" name="country_mode" value="block_selected" <?php checked( $country_mode, 'block_selected' ); ?>>
							<?php esc_html_e( 'Block Selected Countries', HDA_TEXTDOMAIN ); ?>
						</label>
						<label>
							<input type="radio" name="country_mode" value="allow_selected" <?php checked( $country_mode, 'allow_selected' ); ?>>
							<?php esc_html_e( 'Allow Selected Countries Only', HDA_TEXTDOMAIN ); ?>
						</label>
					</div>
				</div>
				<div class="desc">
					<?php esc_html_e( 'Block Selected — blocks only the countries you pick; everything else is allowed. Allow Selected Only — blocks ALL countries except the ones you pick.', HDA_TEXTDOMAIN ); ?>
				</div>
			</div>

			<!-- Block Unknown Countries -->
			<div class="cell section section-checkbox">
				<label class="heading" for="block_unknown_countries"><?php esc_html_e( 'Block Unknown Countries', HDA_TEXTDOMAIN ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="checkbox" class="checkbox" name="block_unknown_countries" id="block_unknown_countries" <?php checked( $block_unknown ); ?> value="1">
					</div>
					<div class="explain"><?php esc_html_e( 'Block when country cannot be determined', HDA_TEXTDOMAIN ); ?></div>
				</div>
				<div class="desc"><?php esc_html_e( 'Block requests when GeoIP cannot determine the visitor\'s country (e.g. private VPN, corrupted IP).', HDA_TEXTDOMAIN ); ?></div>
			</div>
		</div>

		<!-- Country Blocking -->
		<div class="cell section section-select">
			<label class="heading" for="hda-country-select" id="hda-country-heading">
				<?php
				if ( 'allow_selected' === $country_mode ) {
					esc_html_e( 'Allowed Countries', HDA_TEXTDOMAIN );
				} else {
					esc_html_e( 'Blocked Countries', HDA_TEXTDOMAIN );
				}
				?>
			</label>
			<div class="option">
				<div class="controls">
					<div class="hda-country-selector">
						<select id="hda-country-select" class="select">
							<option value="" id="hda-country-placeholder">
								<?php
								if ( 'allow_selected' === $country_mode ) {
									esc_html_e( 'Select a country to allow...', HDA_TEXTDOMAIN );
								} else {
									esc_html_e( 'Select a country to block...', HDA_TEXTDOMAIN );
								}
								?>
							</option>
							<?php foreach ( $countries as $code => $name ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php disabled( in_array( $code, $blocked, true ) ); ?>>
									<?php echo esc_html( $name . ' (' . $code . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button button-primary hda-add-country-btn" id="hda-add-country-btn">
							<span class="dashicons dashicons-shield"></span>
							<span id="hda-country-btn-text">
							<?php
							if ( 'allow_selected' === $country_mode ) {
								esc_html_e( 'Add to Allowlist', HDA_TEXTDOMAIN );
							} else {
								esc_html_e( 'Add to Blocklist', HDA_TEXTDOMAIN );
							}
							?>
							</span>
						</button>
					</div>

					<div class="hda-blocked-list-wrap">
						<ul id="hda-blocked-list" class="hda-blocked-list">
							<?php if ( empty( $blocked ) ) : ?>
								<li class="empty-msg">
									<?php
									if ( 'allow_selected' === $country_mode ) {
										esc_html_e( 'No countries in allowlist. All traffic will be blocked!', HDA_TEXTDOMAIN );
									} else {
										esc_html_e( 'No countries blocked.', HDA_TEXTDOMAIN );
									}
									?>
								</li>
							<?php else : ?>
								<?php foreach ( $blocked as $code ) : ?>
									<?php $name = $countries[ $code ] ?? $code; ?>
									<li class="blocked-item">
										<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $code ) ); ?>.png" width="16" height="12" alt="">
										<span><?php echo esc_html( $name ); ?></span>
										<input type="hidden" name="blocked_countries[]" value="<?php echo esc_attr( $code ); ?>">
										<button type="button" class="remove-country" aria-label="<?php esc_attr_e( 'Remove', HDA_TEXTDOMAIN ); ?>">&times;</button>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</div>
				</div>
			</div>
			<div class="desc">
				<?php esc_html_e( 'Uses MaxMind GeoLite2 database for IP geolocation at PHP level. Place the .mmdb file in the plugin resources/geoip/ folder or wp-content/uploads/hda/.', HDA_TEXTDOMAIN ); ?>
			</div>
		</div>

		<!-- IP Blocklist -->
		<div class="section section-select mt-6">
			<label class="heading" for="waf_blocked_ips"><?php esc_html_e( 'IP Blocklist', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<div class="select_wrapper">
						<select multiple
							placeholder="<?php esc_attr_e( 'Type an IP address or range and press Enter', HDA_TEXTDOMAIN ); ?>"
							class="select select2-ips !w[100%]"
							name="waf_blocked_ips[]"
							id="waf_blocked_ips"
						>
							<?php foreach ( $blocked_ips as $entry ) : ?>
								<option selected value="<?php echo esc_attr( $entry ); ?>"><?php echo esc_html( $entry ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="desc">
				<?php esc_html_e( 'Each IP/CIDR is written as a native server deny rule (Apache/Nginx) — no PHP overhead. Dash ranges are handled by PHP-level blocking. Accepted formats:', HDA_TEXTDOMAIN ); ?>
				<ul style="margin:6px 0 0 18px;list-style:disc;">
					<li><?php echo wp_kses_post( __( 'Single IPv4: <code>203.0.113.50</code>', HDA_TEXTDOMAIN ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', HDA_TEXTDOMAIN ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', HDA_TEXTDOMAIN ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'Dash range (last octet): <code>10.0.0.1-100</code>', HDA_TEXTDOMAIN ) ); ?></li>
				</ul>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- SERVER CONFIG HARDENING -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Server Config Hardening', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-admin-tools"></span>
				<?php
				printf(
					/* translators: %s: server type label */
					esc_html__( 'Detected server: %s. This feature manages security rules (headers, file protection, bot blocking, compression, caching) directly in the server configuration file.', HDA_TEXTDOMAIN ),
					'<strong>' . esc_html( $server_label ) . '</strong>'
				);
				?>
			</p>
			<?php if ( $is_nginx ) : ?>
				<p style="margin:5px 0 0;font-size:12px;color:#666;">
					<?php
					printf(
						/* translators: %s: nginx config file path */
						esc_html__( 'Nginx: A config file will be generated at %s. You must include it in your server block and reload Nginx manually.', HDA_TEXTDOMAIN ),
						'<code>' . esc_html( $config_file ) . '</code>'
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( $is_apache && $has_block ) : ?>
				<p class="hda-notice__detail" style="color:#46b450;">
					<span class="dashicons dashicons-yes-alt" style="vertical-align:middle;"></span>
					<?php
					printf(
						/* translators: %s: config file path */
						esc_html__( 'Config block is active in: %s', HDA_TEXTDOMAIN ),
						'<code>' . esc_html( $config_file ) . '</code>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<div class="section section-checkbox">
			<label class="heading" for="server_config"><?php esc_html_e( 'Enable Server Config Rules', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<input type="checkbox" class="checkbox" name="server_config" id="server_config" <?php checked( $server_config, 1 ); ?> value="1">
				</div>
				<div class="explain"><?php esc_html_e( 'Manage server-level security rules', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="desc">
				<?php if ( $is_apache ) : ?>
					<?php
					printf(
						/* translators: %1$s: begin marker, %2$s: end marker, %3$s: file path */
						esc_html__( 'Add/remove the %1$s / %2$s block in %3$s. Includes:', HDA_TEXTDOMAIN ),
						'<code># BEGIN HDA</code>',
						'<code># END HDA</code>',
						'<code>.htaccess</code>'
					);
					?>
				<?php elseif ( $is_nginx ) : ?>
					<?php
					printf(
						/* translators: %s: file name */
						esc_html__( 'Generate/remove the %s file. Includes:', HDA_TEXTDOMAIN ),
						'<code>nginx-theme.conf</code>'
					);
					?>
				<?php else : ?>
					<?php esc_html_e( 'Server-level security rules include:', HDA_TEXTDOMAIN ); ?>
				<?php endif; ?>
				<ul style="margin:5px 0 0 20px;list-style:disc;">
					<li><?php esc_html_e( 'Security headers (HSTS, X-Frame-Options, CSP, etc.)', HDA_TEXTDOMAIN ); ?></li>
					<li><?php esc_html_e( 'Sensitive file protection (.env, composer.json, wp-config, etc.)', HDA_TEXTDOMAIN ); ?></li>
					<li><?php esc_html_e( 'Bad bot blocking and user-agent filtering', HDA_TEXTDOMAIN ); ?></li>
					<li><?php esc_html_e( 'GZIP compression and static file caching', HDA_TEXTDOMAIN ); ?></li>
					<li><?php esc_html_e( 'Disable PHP in uploads, VCS folder blocking', HDA_TEXTDOMAIN ); ?></li>
				</ul>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FILE PROTECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'File Protection', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Lock critical files to read-only (chmod 0444) to prevent malware or unauthorized modifications. The plugin will auto-unlock .htaccess when managing server config blocks.', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="section section-checkbox">
			<label class="heading" for="lock_files"><?php esc_html_e( 'Lock Critical Files', HDA_TEXTDOMAIN ); ?></label>
			<div class="option">
				<div class="controls">
					<input type="checkbox" class="checkbox" name="lock_files" id="lock_files" <?php checked( $lock_files, 1 ); ?> value="1">
				</div>
				<div class="explain"><?php esc_html_e( 'Set .htaccess, index.php, wp-config.php, wp-settings.php, wp-load.php, wp-blog-header.php, wp-login.php to read-only', HDA_TEXTDOMAIN ); ?></div>
			</div>
			<div class="desc">
				<?php echo wp_kses_post( __( 'Sets files to <code>chmod 0444</code> (read-only). Auto-unlocks <code>.htaccess</code> when writing server config.', HDA_TEXTDOMAIN ) ); ?>
				<table class="widefat striped" style="margin-top:10px;max-width:500px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Permissions', HDA_TEXTDOMAIN ); ?></th>
							<th><?php esc_html_e( 'Status', HDA_TEXTDOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $file_lock_status as $label => $info ) : ?>
							<tr>
								<td><code><?php echo esc_html( $label ); ?></code></td>
								<td><code><?php echo esc_html( $info['perms'] ); ?></code></td>
								<td>
									<?php if ( $info['locked'] ) : ?>
										<span style="color:#46b450;"><span class="dashicons dashicons-lock" style="font-size:14px;vertical-align:middle;"></span> <?php esc_html_e( 'Locked', HDA_TEXTDOMAIN ); ?></span>
									<?php else : ?>
										<span style="color:#dc3232;"><span class="dashicons dashicons-unlock" style="font-size:14px;vertical-align:middle;"></span> <?php esc_html_e( 'Unlocked', HDA_TEXTDOMAIN ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</fieldset>
</div>
<?php

// ── Sub-module options (WAF Firewall + Security Log) ──
include __DIR__ . '/Firewall/options.php';
include __DIR__ . '/TrafficMonitor/options.php';
