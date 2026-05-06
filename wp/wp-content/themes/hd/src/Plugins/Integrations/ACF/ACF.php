<?php
/**
 * Advanced Custom Fields (ACF) integration handler.
 *
 * This class manages all theme-level integrations with the ACF plugin.
 * It hides the ACF admin UI in production, extends allowed HTML sanitization
 * for ACF fields, customizes WYSIWYG and TinyMCE toolbars, and enriches
 * navigation menu items with ACF-based properties such as icons, labels,
 * and mega menu support.
 *
 * @author HD
 */

namespace HD\Plugins\Integrations\ACF;

use HD\Plugins\Integrations\ACF\FieldTypes\NavMenu;
use HD\Plugins\PluginIntegration;
use HD\Utilities\Helper;
use HD\Utilities\Utils;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class ACF implements PluginIntegration {
	use Singleton;

	/* ---------- STATIC ---------------------------------------- */

	/**
	 * Check if ACF plugin is active.
	 *
	 * @return bool
	 */
	public static function isActive(): bool {
		return Helper::isAcfActive();
	}

	/* ---------- CONSTRUCT ---------------------------------------- */

	private function init(): void {
		// Hide the ACF Admin UI in production
		if ( ! Helper::development() ) {
			add_filter( 'acf/settings/show_admin', '__return_false' );
		}

		// Register custom ACF field types
		add_action( 'acf/include_field_types', $this->registerFieldTypes( ... ) );

		add_filter( 'wp_kses_allowed_html', $this->ksesAllowedHtml( ... ), 11, 2 );
		add_filter( 'acf/fields/wysiwyg/toolbars', $this->wysiwygToolbars( ... ), 98, 1 );

		add_filter( 'teeny_mce_buttons', $this->teenyMceButtons( ... ), 99, 2 );
		add_filter( 'wp_nav_menu_objects', $this->navMenuObjects( ... ), 998, 2 );
		add_filter( 'nav_menu_item_title', $this->navMenuItemTitle( ... ), 999, 4 );
		add_filter( 'nav_menu_link_attributes', $this->navMenuLinkAttributes( ... ), 999, 4 );

		// Load custom field definitions from fields/ directory
		$fields = [
			'author_meta.php',
			'mega_menu.php',
			'menu.php',
			'suggestion_page.php',
			'suggestion_post.php',
			'taxonomy.php',
		];

		foreach ( $fields as $file ) {
			$path = __DIR__ . '/fields/' . $file;
			if ( is_file( $path ) ) {
				require_once $path;
			}
		}
	}

	/* ---------- FIELD TYPES ---------------------------------------- */

	/**
	 * Register custom ACF field types.
	 *
	 * @return void
	 */
	public function registerFieldTypes(): void {
		// Skip if ACF Nav Menu Field plugin is already active
		if (
			Helper::checkPluginActive( 'acf-nav-menu-field/advanced-custom-nav-menu-field.php' )
			|| Helper::checkPluginActive( 'advanced-custom-nav-menu-field/advanced-custom-nav-menu-field.php' )
		) {
			return;
		}

		// Register Nav Menu field
		new NavMenu();
	}

	/* ---------- PUBLIC ------------------------------------------- */

	/**
	 * @param array $tags
	 * @param string $context
	 *
	 * @return array
	 */
	public function ksesAllowedHtml( array $tags, string $context ): array {
		if ( $context !== 'acf' ) {
			return $tags;
		}

		foreach ( Helper::ksesSVG() as $tag => $attrs ) {
			$tags[ $tag ] = isset( $tags[ $tag ] ) ? [ ...$tags[ $tag ], ...$attrs ] : $attrs;
		}

		return $tags;
	}

	// -------------------------------------------------------------

	/**
	 * @param array $toolbars
	 *
	 * @return array
	 */
	public function wysiwygToolbars( array $toolbars ): array {
		return $toolbars;
	}

	// -------------------------------------------------------------

	/**
	 * @param array $teenyMceButtons
	 * @param string $editorId
	 *
	 * @return array
	 */
	public function teenyMceButtons( array $teenyMceButtons, string $editorId ): array {
		return [
			'formatselect',
			'bold',
			'underline',
			'bullist',
			'numlist',
			'link',
			'unlink',
			'forecolor',
			'blockquote',
			'table',
			'codesample',
			'subscript',
			'superscript',
			'fullscreen',
		];
	}

	// -------------------------------------------------------------

	/**
	 * @param array $items
	 * @param object $args
	 *
	 * @return array
	 */
	public function navMenuObjects( array $items, object $args ): array {
		foreach ( $items as $item ) {
			$ACF = Helper::getFields( $item, true );
			if ( ! $ACF ) {
				continue;
			}

			$item->menu_mega             = $ACF->menu_mega ?? false;
			$item->menu_link_class       = $ACF->menu_link_class ?? '';
			$item->menu_span             = $ACF->menu_span ?? '';
			$item->menu_span_css         = $ACF->menu_span_css ?? '';
			$item->menu_svg              = $ACF->menu_svg ?? '';
			$item->menu_image            = $ACF->menu_image ?? 0;
			$item->menu_label_text       = $ACF->menu_label_text ?? '';
			$item->menu_label_color      = $ACF->menu_label_color ?? '';
			$item->menu_label_background = $ACF->menu_label_background ?? '';

			// Add classes based on properties
			if ( $item->menu_mega ) {
				$item->classes[] = 'menu-mega';
			}
			if ( $item->menu_svg ) {
				$item->classes[] = 'menu-svg';
			}
			if ( $item->menu_image ) {
				$item->classes[] = 'menu-thumb';
			}
			if ( $item->menu_label_text ) {
				$item->classes[] = 'menu-label';
			}
		}

		return $items;
	}

	// -------------------------------------------------------------

	/**
	 * @param string $title
	 * @param \WP_Post $item
	 * @param object $args
	 * @param int $depth
	 *
	 * @return string
	 */
	public function navMenuItemTitle( string $title, \WP_Post $item, object $args, int $depth ): string {
		// Label <sup>
		if ( ! empty( $item->menu_label_text ) ) {
			$css = '';
			if ( ! empty( $item->menu_label_color ) ) {
				$css .= 'color:' . $item->menu_label_color . ';';
			}
			if ( ! empty( $item->menu_label_background ) ) {
				$css .= 'background-color:' . $item->menu_label_background . ';';
			}

			$style  = $css ? ' style="' . Helper::CSSMinify( $css, true ) . '"' : '';
			$title .= '<sup' . $style . '>' . esc_html( $item->menu_label_text ) . '</sup>';
		}

		// span + span css
		if ( ! empty( $item->menu_span ) ) {
			$spanOpen = ! empty( $item->menu_span_css )
				? '<span class="' . esc_attr( $item->menu_span_css ) . '">'
				: '<span>';
			$title    = $spanOpen . $title . '</span>';
		}

		// SVG inline
		if ( ! empty( $item->menu_svg ) ) {
			$title = $item->menu_svg . $title;
		}

		// IMG
		if ( ! empty( $item->menu_image ) ) {
			$img   = Helper::attachmentImageHTML(
				$item->menu_image,
				'thumbnail',
				[
					'loading' => 'lazy',
					'alt'     => wp_strip_all_tags( $item->title ?? '' ),
				]
			);
			$title = $img . $title;
		}

		return $title;
	}

	// -------------------------------------------------------------

	/**
	 * @param array $atts
	 * @param \WP_Post $menuItem
	 * @param object $args
	 * @param int $depth
	 *
	 * @return array
	 */
	public function navMenuLinkAttributes( array $atts, \WP_Post $menuItem, object $args, int $depth ): array {
		if ( empty( $menuItem->menu_link_class ) ) {
			return $atts;
		}

		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' ' . esc_attr( $menuItem->menu_link_class ) );

		return $atts;
	}
}
