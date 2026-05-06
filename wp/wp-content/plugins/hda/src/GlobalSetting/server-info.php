<?php
/**
 * Server Info template.
 *
 * @var \wpdb  $wpdb
 * @var string $server_software
 * @var string $user_agent
 *
 * @package HDAddons\GlobalSetting
 */

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

?>
<div class="wrap">
	<div id="main">
		<h2 class="hide-text"></h2>
		<div class="server-info-body">
			<h2><?php echo esc_html__( 'Server info', HDA_TEXTDOMAIN ); ?></h2>
			<p class="desc"><?php echo esc_html__( 'System configuration information', HDA_TEXTDOMAIN ); ?></p>
			<div class="server-info-inner code">
				<ul>
					<li><?php printf( '<span>Platform:</span> %s', esc_html( php_uname() ) ); ?></li>
					<li><?php printf( '<span>Server:</span> %s', esc_html( $server_software ) ); ?></li>
					<li><?php printf( '<span>Server IP:</span> %s', esc_html( Helper::serverIpAddress() ) ); ?></li>
					<li><?php printf( '<span>IP:</span> %s', esc_html( Helper::ipAddress() ) ); ?></li>
					<?php

					$cpu_model = 'N/A';
					if ( is_readable( '/proc/cpuinfo' ) ) {
						try {
							// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- /proc/cpuinfo is a virtual file, WP_Filesystem may not support it.
							$cpuInfo = file_get_contents( '/proc/cpuinfo', false, null, 0, 5000 ); // Limit to 5KB
							if ( false !== $cpuInfo ) {
								preg_match( '/^model name\s*:\s*(.+)$/m', $cpuInfo, $matches );
								$cpu_model = isset( $matches[1] ) ? trim( $matches[1] ) : 'N/A';
							}
						} catch ( \Exception $e ) {
							Helper::errorLog( 'Failed to read CPU info: ' . $e->getMessage() );
						}
					}

					?>
					<li><?php printf( '<span>CPU Info:</span> %s', esc_html( $cpu_model ) ); ?></li>
					<li><?php printf( '<span>Memory Limit:</span> %s', esc_html( ini_get( 'memory_limit' ) ) ); ?></li>
					<li><?php printf( '<span>PHP version:</span> %s', esc_html( PHP_VERSION ) ); ?></li>
					<li><?php printf( '<span>PHP Max Upload Size:</span> %s', esc_html( ini_get( 'upload_max_filesize' ) ) ); ?></li>
					<li><?php printf( '<span>MySql version:</span> %s', esc_html( $wpdb->db_version() ) ); ?></li>
					<li><?php printf( '<span>WordPress version:</span> %s', esc_html( get_bloginfo( 'version' ) ) ); ?></li>
					<li><?php printf( '<span>WordPress multisite:</span> %s', ( is_multisite() ? 'Yes' : 'No' ) ); ?></li>
					<?php

					$openssl_status = __( 'Available', HDA_TEXTDOMAIN );
					$openssl_text   = '';
					if ( ! defined( 'OPENSSL_ALGO_SHA1' ) && ! extension_loaded( 'openssl' ) ) {
						$openssl_status = __( 'Not available', HDA_TEXTDOMAIN );
						$openssl_text   = __( ' (openssl extension is required in order to use any kind of encryption like TLS or SSL)', HDA_TEXTDOMAIN );
					}
					?>
					<li><?php printf( '<span>openssl:</span> %s%s', esc_html( $openssl_status ), esc_html( $openssl_text ) ); ?></li>
					<li><?php printf( '<span>allow_url_fopen:</span> %s', ( ini_get( 'allow_url_fopen' ) ? esc_html__( 'Enabled', HDA_TEXTDOMAIN ) : esc_html__( 'Disabled', HDA_TEXTDOMAIN ) ) ); ?></li>
					<?php

					$stream_socket_client_status = __( 'Not Available', HDA_TEXTDOMAIN );
					$fsockopen_status            = __( 'Not Available', HDA_TEXTDOMAIN );
					$socket_enabled              = false;

					if ( function_exists( 'stream_socket_client' ) ) {
						$stream_socket_client_status = __( 'Available', HDA_TEXTDOMAIN );
						$socket_enabled              = true;
					}
					if ( function_exists( 'fsockopen' ) ) {
						$fsockopen_status = __( 'Available', HDA_TEXTDOMAIN );
						$socket_enabled   = true;
					}

					$socket_text = '';
					if ( ! $socket_enabled ) {
						$socket_text = __( ' (In order to make a SMTP connection your server needs to have either stream_socket_client or fsockopen)', HDA_TEXTDOMAIN );
					}

					?>
					<li><?php printf( '<span>stream_socket_client:</span> %s', esc_html( $stream_socket_client_status ) ); ?></li>
					<li><?php printf( '<span>fsockopen:</span> %s%s', esc_html( $fsockopen_status ), esc_html( $socket_text ) ); ?></li>
					<?php if ( $user_agent ) : ?>
						<li><?php printf( '<span>User agent:</span> %s', esc_html( $user_agent ) ); ?></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	</div>
</div>
