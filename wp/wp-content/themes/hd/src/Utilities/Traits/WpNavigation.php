<?php
/**
 * Navigation menu helper methods.
 *
 * @author HD
 */

namespace HD\Utilities\Traits;

use HD\Core\Cache;
use HD\Utilities\HorizontalNavWalker;
use HD\Utilities\VerticalNavWalker;

defined( 'ABSPATH' ) || exit;

trait WpNavigation {
	// -------------------------------------------------------------

	/**
	 * @param array $args
	 *
	 * @return false|string|null
	 */
	public static function verticalNav( array $args = [] ): false|string|null {
		$args = wp_parse_args(
			$args,
			[
				'container'      => false,
				'menu_id'        => '',
				'menu_class'     => 'menu vertical',
				'theme_location' => '',
				'depth'          => 4,
				'fallback_cb'    => false,
				'walker'         => new VerticalNavWalker(),
				'items_wrap'     => '<ul id="%1$s" class="%2$s" data-fx-accordion-menu data-submenu-toggle="true" data-multi-selectable="true">%3$s</ul>',
				'echo'           => false,
			]
		);

		if ( $args['echo'] === true ) {
			echo self::getCachedMenu( $args, 'vertical' );

			return null;
		}

		return self::getCachedMenu( $args, 'vertical' );
	}

	// -------------------------------------------------------------

	/**
	 * @param array $args
	 *
	 * @return false|string|null
	 */
	public static function horizontalNav( array $args = [] ): false|string|null {
		$dataHover    = (bool) ( $args['data_hover'] ?? true );
		$dataAutohide = (bool) ( $args['data_autohide'] ?? false );

		$dataAttrs = ( $dataHover ? ' data-hover="true"' : '' ) . ( $dataAutohide ? ' data-autohide="true"' : '' );

		$args = wp_parse_args(
			$args,
			[
				'container'      => false,
				'menu_id'        => '',
				'menu_class'     => 'dropdown menu horizontal',
				'theme_location' => '',
				'depth'          => 4,
				'fallback_cb'    => false,
				'walker'         => new HorizontalNavWalker(),
				'items_wrap'     => '<ul id="%1$s" class="%2$s" data-fx-dropdown-menu' . $dataAttrs . '>%3$s</ul>',
				'echo'           => false,
			]
		);

		if ( $args['echo'] === true ) {
			echo self::getCachedMenu( $args, 'horizontal' );

			return null;
		}

		return self::getCachedMenu( $args, 'horizontal' );
	}

	// -------------------------------------------------------------

	/**
	 * Get cached menu HTML.
	 *
	 * @param array  $args Menu arguments.
	 * @param string $type Menu type (horizontal|vertical).
	 *
	 * @return string|false
	 */
	private static function getCachedMenu( array $args, string $type ): string|false {
		// Build cache key from location and serialized args (excluding walker object)
		$cacheArgs           = $args;
		$cacheArgs['walker'] = $type; // Replace walker object with type string for hashing
		$location            = $args['theme_location'] ?: 'default';
		$cacheKey            = "nav_{$type}_{$location}_" . md5( wp_json_encode( $cacheArgs ) );

		return Cache::remember(
			$cacheKey,
			static fn() => wp_nav_menu( $args ),
			'theme_menus',
			DAY_IN_SECONDS
		);
	}
}
