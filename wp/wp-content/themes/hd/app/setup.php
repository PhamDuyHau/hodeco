<?php
/**
 * Theme Setup & Configuration
 *
 * This file handles:
 * - WordPress menu registration
 * - Theme + HDAddons plugin settings via 'hd_settings_filter'
 *
 * @package HD
 * @author  HD
 */

use HD\Utilities\Helper;

\defined( 'ABSPATH' ) || die;

// ════════════════════════════════════════════════════════════════════════════════
// NAVIGATION MENUS
// ════════════════════════════════════════════════════════════════════════════════

add_action( 'after_setup_theme', 'register_nav_menu_callback', 11 );
function register_nav_menu_callback(): void {
	register_nav_menus(
		[
			'main-nav'   => __( 'Primary Menu', TEXT_DOMAIN ),
			'mobile-nav' => __( 'Mobile Menu', TEXT_DOMAIN ),
			'policy-nav' => __( 'Term Menu', TEXT_DOMAIN ),
		]
	);
}

// ════════════════════════════════════════════════════════════════════════════════
// THEME & PLUGIN SETTINGS
// ════════════════════════════════════════════════════════════════════════════════

add_filter( 'hd_settings_filter', 'hd_settings_filter_callback', 99, 1 );
function hd_settings_filter_callback( array $arr ): array {
	static $cache = [];

	if ( ! empty( $cache['settings'] ) ) {
		return $cache['settings'];
	}

	$settings = array_merge(
		$arr,
		_get_performance_settings(),
		_get_admin_settings(),
		_get_security_settings(),
		_get_social_settings(),
		_get_contact_settings(),
		_get_misc_settings()
	);

	// WooCommerce additions
	if ( Helper::isWoocommerceActive() ) {
		$settings = _add_woocommerce_settings( $settings );
	}

	$cache['settings'] = $settings;

	return $settings;
}

// ════════════════════════════════════════════════════════════════════════════════
// PERFORMANCE SETTINGS (Scripts & Styles)
// ════════════════════════════════════════════════════════════════════════════════

function _get_performance_settings(): array {
	return [
		'defer_script' => [
			// Defer loading (non-blocking)
			'admin-bar'       => 'defer',
			// 'swv'             => 'defer',
			// 'contact-form-7'  => 'defer',
			'toc-front'       => 'defer',

			// Delay loading (after user interaction, default 4s)
			'kk-star-ratings' => 'delay',
			'comment-reply'   => 'delay',
			'wp-embed'        => 'delay',
		],

		'defer_style'  => [
			'dashicons',
			// 'contact-form-7',
			'kk-star-ratings',
		],
	];
}


// ════════════════════════════════════════════════════════════════════════════════
// ADMIN SETTINGS (Dashboard, Menus, ACF)
// ════════════════════════════════════════════════════════════════════════════════

function _get_admin_settings(): array {
	return [
		'admin_list_table' => [
			'term_row_actions'                => [ 'category', 'post_tag' ],
			'post_row_actions'                => [ 'user', 'post', 'page' ],
			'term_thumb_columns'              => [ 'category' ],
			'post_type_exclude_thumb_columns' => [ 'page', 'filter-set', 'wpcf7_contact_form' ],
		],

		'admin_menu'       => [
			'admin_hide_menu'             => [],
			'admin_hide_submenu'          => [],
			'admin_hide_menu_ignore_user' => [ 1 ],
		],

		'acf_menu'         => [
			'acf_menu_items_locations' => [ 'main-nav', 'header-nav', 'policy-nav' ],
			'acf_mega_menu_locations'  => [ 'main-nav' ],
		],
	];
}

// ════════════════════════════════════════════════════════════════════════════════
// SECURITY SETTINGS (IP, Users, Proxies)
// ════════════════════════════════════════════════════════════════════════════════

function _get_security_settings(): array {
	return [
		'security' => [
			// IP Access Control
			'allowlist_ips_login_access'          => [],
			'blocked_ips_login_access'            => [],

			// Trusted Proxies (CloudFlare auto-trusted, add custom proxies here)
			// Supports CIDR: '10.0.0.1', '192.168.1.0/24'
			'trusted_proxies'                     => [],

			// User Permissions (by user ID)
			'privileged_user_ids'                 => [ 1, 2 ],
			'allowed_users_ids_show_plugins'      => [ 1, 2 ],
			'allowed_users_ids_install_plugins'   => [ 1 ],
			'disallowed_users_ids_delete_account' => [ 1 ],
		],
	];
}

// ════════════════════════════════════════════════════════════════════════════════
// SOCIAL LINKS (Footer, Header)
// ════════════════════════════════════════════════════════════════════════════════

function _get_social_settings(): array {
	$socials = [
		'facebook'  => [ 'Facebook', 'facebook', 'https://www.facebook.com' ],
		'instagram' => [ 'Instagram', 'instagram', 'https://www.instagram.com' ],
		'youtube'   => [ 'Youtube', 'youtube', 'https://www.youtube.com' ],
		'x'         => [ 'X (Twitter)', 'x', 'https://x.com' ],
		'tiktok'    => [ 'Tiktok', 'tiktok', 'https://www.tiktok.com' ],
		'telegram'  => [ 'Telegram', 'telegram', 'https://t.me' ],
	];

	$links = [];
	foreach ( $socials as $key => [$name, $icon, $placeholder] ) {
		$links[ $key ] = [
			'name'        => $name,
			'icon'        => \hd_svg( $icon ),
			'placeholder' => $placeholder,
			'url'         => '',
		];
	}

	return [ 'social_follows_links' => $links ];
}

// ════════════════════════════════════════════════════════════════════════════════
// CONTACT LINKS (Floating buttons, CTA)
// ════════════════════════════════════════════════════════════════════════════════

function _get_contact_settings(): array {
	$contacts = [
		'messenger' => [ 'Messenger', 'messenger', 'https://m.me/username', '_blank' ],
		'zalo'      => [ 'Zalo', 'zalo', 'https://zalo.me/0123456789', '_blank' ],
		'hotline'   => [ 'Hotline', 'phone', '0123456789', null ],
		'tiktok'    => [ 'Tiktok', 'tiktok', 'https://www.tiktok.com/@username', '_blank' ],
		'whatsapp'  => [ 'Whatsapp', 'whatsapp', 'https://wa.me/0123456789', '_blank' ],
		'viber'     => [ 'Viber', 'viber', 'viber://chat?number=0123456789', '_blank' ],
	];

	$links = [];
	foreach ( $contacts as $key => [$name, $icon, $placeholder, $target] ) {
		$links[ $key ] = [
			'name'        => $name,
			'icon'        => \hd_svg( $icon ),
			'value'       => '',
			'placeholder' => $placeholder,
			'class'       => str_replace( '_', '-', $key ),
		];

		if ( $target ) {
			$links[ $key ]['target'] = $target;
		}
	}

	return [ 'contact_links' => $links ];
}

// ════════════════════════════════════════════════════════════════════════════════
// MISC SETTINGS (Post Types, Aspect Ratio, Emails)
// ════════════════════════════════════════════════════════════════════════════════

function _get_misc_settings(): array {
	return [
		'post_type_terms' => [ 'post' => 'category' ],
		'custom_emails'   => [],

		'aspect_ratio'    => [
			'post_type_term'       => [ 'post' ],
			'aspect_ratio_default' => [ '1-1', '3-2', '4-3', '16-9' ],
		],
	];
}

// ════════════════════════════════════════════════════════════════════════════════
// WOOCOMMERCE ADDITIONS
// ════════════════════════════════════════════════════════════════════════════════

function _add_woocommerce_settings( array $settings ): array {
	$settings['aspect_ratio']['post_type_term'][] = 'product';
	$settings['aspect_ratio']['post_type_term'][] = 'product_cat';

	$settings['admin_list_table']['term_row_actions'][]                = 'product_cat';
	$settings['admin_list_table']['post_type_exclude_thumb_columns'][] = 'product';
	$settings['post_type_terms']['product']                            = 'product_cat';

	return $settings;
}
