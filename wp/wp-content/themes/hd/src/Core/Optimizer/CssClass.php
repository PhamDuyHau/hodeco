<?php
/**
 * CSS Class Optimizer
 *
 * Handles body_class, post_class, and nav_menu class modifications.
 *
 * @package HD\Core\Optimizer
 * @author  HD
 */

namespace HD\Core\Optimizer;

use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

final class CssClass {

	/**
	 * Register CSS class filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'body_class', self::bodyClass( ... ), 12 );
		add_filter( 'post_class', self::postClass( ... ), 12 );
		add_filter( 'nav_menu_css_class', self::navMenuCssClass( ... ), 12, 4 );
		add_filter( 'nav_menu_link_attributes', self::navMenuLinkAttributes( ... ), 12, 4 );
	}

	/** ---------------------------------------- */

	/**
	 * Filter body classes - remove unwanted classes, add custom ones.
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array
	 */
	public static function bodyClass( array $classes ): array {
		// Check whether we're in the customizer preview
		if ( is_customize_preview() ) {
			$classes[] = 'customizer-preview';
		}

		// Remove unwanted classes
		$unwantedPatterns = [
			'wp-custom-logo',
			'page-template-templates',
			'page-id-',
			'postid-',
			'single-format-standard',
			'no-customize-support',
		];

		$classes = array_filter(
			$classes,
			static fn( $cssClass ) => ! self::matchesAnyPattern( $cssClass, $unwantedPatterns )
		);

		// Add WooCommerce class if active
		if ( Helper::isWoocommerceActive() ) {
			$classes[] = 'woocommerce';
		}

		return $classes;
	}

	/** ---------------------------------------- */

	/**
	 * Filter post classes - rename sticky, remove tag/category classes.
	 *
	 * @param array $classes Post classes.
	 *
	 * @return array
	 */
	public static function postClass( array $classes ): array {
		// Rename sticky class to avoid CSS conflicts
		if ( in_array( 'sticky', $classes, true ) ) {
			$classes   = array_diff( $classes, [ 'sticky' ] );
			$classes[] = 'wp-sticky';
		}

		// Remove 'tag-', 'category-' classes
		return array_filter(
			$classes,
			static fn( $cssClass ) => ! str_contains( $cssClass, 'tag-' ) && ! str_contains( $cssClass, 'category-' )
		);
	}

	/** ---------------------------------------- */

	/**
	 * Filter nav menu item classes.
	 *
	 * @param array $classes Menu item classes.
	 * @param \WP_Post $menuItem Menu item object.
	 * @param object $args Menu arguments.
	 * @param int $depth Menu depth.
	 *
	 * @return array
	 */
	public static function navMenuCssClass( mixed $classes, \WP_Post $menuItem, object $args, int $depth ): array {
		$classes = (array) $classes;

		// Remove WordPress default menu classes
		$unwantedPatterns = [
			'menu-item-type-',
			'menu-item-object-',
			'menu-item-',
			'menu-item',
		];

		$classes = array_filter(
			$classes,
			static fn( $cssClass ) => ! self::matchesAnyPattern( $cssClass, $unwantedPatterns )
		);

		// Add active class
		if ( $menuItem->current || $menuItem->current_item_ancestor || $menuItem->current_item_parent ) {
			$classes[] = 'active';
		}

		// Add custom li_class based on depth
		if ( $depth === 0 && ! empty( $args->li_class ) ) {
			$classes[] = $args->li_class;
		} elseif ( $depth > 0 && ! empty( $args->li_depth_class ) ) {
			$classes[] = $args->li_depth_class;
		}

		return $classes;
	}

	/** ---------------------------------------- */

	/**
	 * Filter nav menu link attributes.
	 *
	 * @param array $atts Link attributes.
	 * @param \WP_Post $menuItem Menu item object.
	 * @param object $args Menu arguments.
	 * @param int $depth Menu depth.
	 *
	 * @return array
	 */
	public static function navMenuLinkAttributes( array $atts, \WP_Post $menuItem, object $args, int $depth ): array {
		$classProperty = match ( true ) {
			$depth === 0 && property_exists( $args, 'link_class' )     => $args->link_class,
			$depth > 0 && property_exists( $args, 'link_depth_class' ) => $args->link_depth_class,
			default                                                    => null,
		};

		if ( $classProperty ) {
			$atts['class'] = esc_attr( $classProperty );
		}

		return $atts;
	}

	/** ---------------------------------------- */

	/**
	 * Check if a class matches any pattern.
	 *
	 * @param string $cssClass
	 * @param array $patterns
	 *
	 * @return bool
	 */
	private static function matchesAnyPattern( string $cssClass, array $patterns ): bool {
		foreach ( $patterns as $pattern ) {
			if ( str_contains( $cssClass, $pattern ) ) {
				return true;
			}
		}

		return false;
	}
}
