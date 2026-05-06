<?php
/**
 * Settings utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Settings {
	// --------------------------------------------------

	/**
	 * Get filtered setting options.
	 *
	 * @param string $name
	 * @param mixed $fallback
	 *
	 * @return mixed
	 */
	public static function filterSettingOptions( string $name, mixed $fallback = [] ): mixed {
		$filters = apply_filters( 'hd_settings_filter', self::themeSettingDefault() );

		return isset( $filters[ $name ] ) ? ( $filters[ $name ] ?: $fallback ) : $fallback;
	}

	// --------------------------------------------------

	/**
	 * Get default theme settings.
	 *
	 * @return array
	 */
	public static function themeSettingDefault(): array {
		return apply_filters(
			'hd_settings_defaults',
			[
				'aspect_ratio' => [
					'post_type_term' => [ 'post' ],
				],

				'admin_menu'   => [
					'admin_hide_menu_ignore_user' => [ 1 ],
				],

				'security'     => [
					'privileged_user_ids'                 => [ 1 ],
					'allowed_users_ids_show_plugins'      => [ 1 ],
					'allowed_users_ids_install_plugins'   => [ 1 ],
					'disallowed_users_ids_delete_account' => [ 1 ],
				],
			]
		);
	}
}
