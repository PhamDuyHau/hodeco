<?php
/**
 * Contact Form 7 Optimization
 *
 * Optimizes CF7 performance without breaking AJAX submission.
 * - Conditionally loads assets only on pages with forms
 * - Removes autop for cleaner HTML
 *
 * @package HD\Plugins
 * @author  HD
 */

namespace HD\Plugins\Integrations;

use HD\Plugins\PluginIntegration;
use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class CF7 implements PluginIntegration {
	use Singleton;

	/** ---------------------------------------- */

	/**
	 * Check if Contact Form 7 is active.
	 *
	 * @return bool
	 */
	public static function isActive(): bool {
		return Helper::isCf7Active();
	}

	/** ---------------------------------------- */

	private function init(): void {
		// Remove auto <p> and <br> tags from form output
		add_filter( 'wpcf7_autop_or_not', '__return_false' );

		// Bypass nonce verification (use with caution - for cached pages)
		add_filter( 'wpcf7_verify_nonce', '__return_true' );

		// Dynamic taxonomy select support
		add_filter( 'wpcf7_form_tag', $this->dynamicSelectTerms( ... ), 10, 1 );

		// Add inputmode attributes for better mobile UX
		add_filter( 'wpcf7_form_elements', $this->addInputMode( ... ), 10, 1 );

		// Only load CF7 assets on pages that have the shortcode
		//add_action( 'wp', $this->conditionalAssets( ... ) );

		// Optional: Remove config errors from admin (doesn't affect frontend)
		if ( is_admin() ) {
			add_filter( 'wpcf7_skip_mail_validation', '__return_true' );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Dynamic Select Terms for Contact Form 7.
	 *
	 * Allows populating select fields with taxonomy terms dynamically.
	 *
	 * @usage [select name taxonomy:{taxonomy_name} parent:{term_id}]
	 *
	 * @param array $tag Form tag configuration.
	 *
	 * @return array Modified tag with dynamic term values.
	 */
	public function dynamicSelectTerms( array $tag ): array {
		// Only run on select fields
		if ( ! in_array( $tag['type'], [ 'select', 'select*' ], true ) ) {
			return $tag;
		}

		if ( empty( $tag['options'] ) ) {
			return $tag;
		}

		$termArgs = [];

		// Parse options for taxonomy and parent
		foreach ( $tag['options'] as $option ) {
			$matches = explode( ':', $option );
			if ( count( $matches ) < 2 ) {
				continue;
			}

			$termArgs = match ( $matches[0] ) {
				'taxonomy' => [
					...$termArgs,
					'taxonomy' => sanitize_key( $matches[1] ),
				],
				'parent'   => [
					...$termArgs,
					'parent' => absint( $matches[1] ),
				],
				default    => $termArgs,
			};
		}

		// Ensure we have taxonomy to work with
		if ( empty( $termArgs['taxonomy'] ) ) {
			return $tag;
		}

		// Merge dynamic arguments with defaults
		$termArgs = [
			...$termArgs,
			'hide_empty'   => false,
			'hierarchical' => true,
		];

		$terms = get_terms( $termArgs );

		// Add terms to select values
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tag['values'][] = $term->name;
			}
		}

		return $tag;
	}

	/** ---------------------------------------- */

	/**
	 * Add inputmode attributes to form inputs for better mobile keyboard.
	 *
	 * @param string $content Form HTML content.
	 *
	 * @return string Modified content with inputmode attributes.
	 */
	public function addInputMode( string $content ): string {
		return preg_replace(
			       [
				       '/(<input[^>]+type="tel"[^>]+)>/i',
				       '/(<input[^>]+type="email"[^>]+)>/i',
				       '/(<input[^>]+type="url"[^>]+)>/i',
			       ],
			       [
				       '$1 inputmode="tel">',
				       '$1 inputmode="email">',
				       '$1 inputmode="url">',
			       ],
			       $content
		       ) ?? $content;
	}

	/** ---------------------------------------- */

	/**
	 * Only load CF7 assets on pages that need them.
	 *
	 * Checks multiple locations:
	 * - Post/page content (shortcode or block)
	 * - Footer form (ACF option)
	 * - Sidebar widgets
	 * - Custom filter for manual control
	 *
	 * @return void
	 */
	public function conditionalAssets(): void {
		if ( is_admin() ) {
			return;
		}

		$hasCf7 = $this->detectCF7();

		// Allow filtering for custom conditions
		$hasCf7 = apply_filters( 'hd_load_cf7_assets', $hasCf7 );

		if ( ! $hasCf7 ) {
			add_action( 'wp_enqueue_scripts', $this->dequeueAssets( ... ), 100 );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Detect if CF7 is used anywhere on the current page.
	 *
	 * @return bool
	 */
	private function detectCF7(): bool {
		global $post;

		// 1. Check post/page content
		if ( $post instanceof \WP_Post ) {
			if ( has_shortcode( $post->post_content, 'contact-form-7' ) ) {
				return true;
			}

			if ( function_exists( 'has_block' ) && has_block( 'contact-form-7/contact-form-selector', $post ) ) {
				return true;
			}
		}

		// 2. Check footer form (ACF option) - you have CF7 in footer
		$contactForm = Helper::getField( 'contact_form', 'option' );
		if ( ! empty( $contactForm['form'] ) ) {
			return true;
		}

		// 3. Check active sidebar widgets for CF7
		if ( $this->checkWidgetsForCF7() ) {
			return true;
		}

		// 4. Always load on contact/form pages (by slug)
		$pages = [ 'contact', 'lien-he', 'lien-he-chung-toi' ];
		if ( is_page( $pages ) ) {
			return true;
		}

		return false;
	}

	/** ---------------------------------------- */

	/**
	 * Check if any active widget contains CF7 shortcode.
	 *
	 * @return bool
	 */
	private function checkWidgetsForCF7(): bool {
		$sidebars = wp_get_sidebars_widgets();

		if ( ! $sidebars ) {
			return false;
		}

		foreach ( $sidebars as $sidebarId => $widgets ) {
			if ( $sidebarId === 'wp_inactive_widgets' || ! $widgets ) {
				continue;
			}

			foreach ( $widgets as $widgetId ) {
				if ( $this->widgetContainsCF7( $widgetId ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/** ---------------------------------------- */

	/**
	 * Check if a widget contains CF7 shortcode.
	 *
	 * @param string $widgetId
	 *
	 * @return bool
	 */
	private function widgetContainsCF7( string $widgetId ): bool {
		$checks = [
			'text-'        => [ 'widget_text', 'text' ],
			'custom_html-' => [ 'widget_custom_html', 'content' ],
			'block-'       => [ 'widget_block', 'content' ],
		];

		foreach ( $checks as $prefix => [$optionName, $field] ) {
			if ( ! str_starts_with( $widgetId, $prefix ) ) {
				continue;
			}

			$widgetNumber = (int) str_replace( $prefix, '', $widgetId );
			$widgets      = get_option( $optionName, [] );
			$content      = $widgets[ $widgetNumber ][ $field ] ?? '';

			if ( $content && ( has_shortcode( $content, 'contact-form-7' ) || str_contains( $content, 'wpcf7' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/** ---------------------------------------- */

	/**
	 * Dequeue CF7 assets on pages without forms.
	 *
	 * @return void
	 */
	public function dequeueAssets(): void {
		// wp_dequeue_script( 'contact-form-7' );
		// wp_dequeue_style( 'contact-form-7' );
		// wp_dequeue_script( 'swv' );
	}
}
