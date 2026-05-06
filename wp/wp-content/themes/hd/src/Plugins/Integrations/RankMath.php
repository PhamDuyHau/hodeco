<?php
/**
 * RankMath SEO Integration
 *
 * Customizations for RankMath SEO plugin:
 * - Custom breadcrumb markup
 * - Show focus keywords on frontend
 * - Remove RankMath from admin bar
 * - TOC plugin support
 *
 * @package HD\Plugins
 * @author  HD
 */

namespace HD\Plugins\Integrations;

use HD\Plugins\PluginIntegration;
use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;

defined( 'ABSPATH' ) || die;

final class RankMath implements PluginIntegration {
	use Singleton;

	/** ---------------------------------------- */

	/**
	 * Check if RankMath is active.
	 *
	 * @return bool
	 */
	public static function isActive(): bool {
		return Helper::isRankMathActive();
	}

	/** ---------------------------------------- */

	private function init(): void {
		// Custom breadcrumb markup
		add_filter( 'rank_math/frontend/breadcrumb/args', $this->breadcrumbArgs( ... ) );

		// Show focus keywords on frontend
		add_filter( 'rank_math/frontend/show_keywords', '__return_true' );

		// Remove RankMath from admin bar
		add_action( 'wp_before_admin_bar_render', $this->removeAdminBarMenu( ... ) );

		// Add TOC plugin support
		add_filter( 'rank_math/researches/toc_plugins', $this->tocPlugins( ... ), PHP_INT_MAX );
	}

	/** ---------------------------------------- */

	/**
	 * Remove RankMath menu from admin bar.
	 *
	 * @return void
	 */
	public function removeAdminBarMenu(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'rank-math' );
	}

	/** ---------------------------------------- */

	/**
	 * Filter TOC plugins to prioritize specific plugins.
	 *
	 * @param array $tocPlugins Default TOC plugins.
	 *
	 * @return array Filtered TOC plugins.
	 */
	public function tocPlugins( array $tocPlugins ): array {
		$preferred = [
			'table-of-contents-plus/toc.php'                    => 'Table of Contents Plus',
			'easy-table-of-contents/easy-table-of-contents.php' => 'Easy Table of Contents',
			'tocer/tocer.php'                                   => 'Tocer',
			'fixed-toc/fixed-toc.php'                           => 'Fixed TOC',
		];

		foreach ( $preferred as $file => $label ) {
			if ( Helper::checkPluginActive( $file ) ) {
				return [ $file => $label ];
			}
		}

		return $tocPlugins;
	}

	/** ---------------------------------------- */

	/**
	 * Customize breadcrumb HTML structure.
	 *
	 * @param array $args Original breadcrumb arguments.
	 *
	 * @return array Modified breadcrumb arguments.
	 */
	public function breadcrumbArgs( array $args ): array {
		return [
			'delimiter'   => '',
			'wrap_before' => '<ul id="breadcrumbs" class="breadcrumbs" aria-label="Breadcrumbs">',
			'wrap_after'  => '</ul>',
			'before'      => '<li><span property="itemListElement" typeof="ListItem">',
			'after'       => '</span></li>',
		];
	}
}
