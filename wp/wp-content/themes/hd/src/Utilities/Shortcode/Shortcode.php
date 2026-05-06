<?php
/**
 * Theme Shortcodes
 *
 * This file defines the Shortcode class, responsible for registering and managing
 * all custom shortcodes used in the theme.
 * It organizes shortcode logic into a single class and hooks them into WordPress
 * during initialization for cleaner and modular code.
 *
 * @author HD
 */

namespace HD\Utilities\Shortcode;

use HD\Utilities\Helper;
use HD\Utilities\Traits\Singleton;
use HD_Helper;

defined( 'ABSPATH' ) || die;

final class Shortcode extends AbstractShortcode {
	use Singleton;

	/* ---------- PROTECTED ------------------------------------- */

	protected function init(): void {
		$this->register();
	}

	// ------------------------------------------------------

	protected function getShortcodes(): array {
		return [
			'safe_mail'         => $this->safeMail( ... ),
			'site_logo'         => $this->siteLogo( ... ),
			'menu_logo'         => $this->menuLogo( ... ),
			'inline_search'     => $this->inlineSearch( ... ),
			'dropdown_search'   => $this->dropdownSearch( ... ),
			'off_canvas_button' => $this->offCanvasButton( ... ),
			'horizontal_menu'   => $this->horizontalMenu( ... ),
			'vertical_menu'     => $this->verticalMenu( ... ),
			'posts'             => $this->posts( ... ),
			'block_fixed_info'  => $this->block_fixed_info( ... ),
		];
	}

	/* ---------- PRIVATE --------------------------------------- */

	/**
	 * Generate unique ID for form elements.
	 */
	private static function generateUniqueId( string $prefix = 'id' ): string {
		static $counters = [];

		$counters[ $prefix ] = ( $counters[ $prefix ] ?? 0 ) + 1;

		return $prefix . '-' . substr( md5( $prefix . $counters[ $prefix ] ), 0, 10 );
	}

	/* ---------- PUBLIC ---------------------------------------- */

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function safeMail( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title' => '',
				'email' => '',
				'class' => '',
			],
			$atts,
			'safe_mail'
		);

		$attributes = [
			'title' => Helper::escAttr( $atts['title'] ?: $atts['email'] ),
		];

		if ( $atts['class'] ) {
			$attributes['class'] = Helper::escAttr( $atts['class'] );
		}

		return Helper::safeMailTo( $atts['email'], $atts['title'], $attributes ) ?? '';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function siteLogo( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'theme' => 'default',
				'class' => '',
			],
			$atts,
			'site_logo'
		);

		return Helper::siteLogo( $atts['theme'], $atts['class'] );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function menuLogo( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'heading' => false,
				'class'   => 'logo',
			],
			$atts,
			'menu_logo'
		);

		return Helper::siteTitleOrLogo( false, $atts['heading'], $atts['class'] ) ?? '';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function inlineSearch( array $atts = [] ): string {
		$defaultId = self::generateUniqueId( 'search' );

		$atts = shortcode_atts(
			[
				'title'       => '',
				'placeholder' => '',
				'class'       => '',
				'id'          => $defaultId,
			],
			$atts,
			'inline_search'
		);

		$title       = esc_html( $atts['title'] );
		$titleFor    = esc_attr__( 'Tìm kiếm', TEXT_DOMAIN );
		$placeholder = esc_attr( $atts['placeholder'] ?: __( 'Tìm kiếm...', TEXT_DOMAIN ) );
		$id          = Helper::escAttr( $atts['id'] ?: $defaultId );
		$class       = $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : '';

		ob_start();
		?>
<form action="<?php echo Helper::home(); ?>" class="frm-search" method="get" accept-charset="UTF-8">
	<label for="<?php echo $id; ?>" class="sr-only"><?php echo $titleFor; ?></label>
	<input id="<?php echo $id; ?>" required pattern="^(.*\S+.*)$" type="search" autocomplete="off" name="s"
		value="<?php echo get_search_query(); ?>" placeholder="<?php echo $placeholder; ?>">
	<button type="submit" aria-label="<?php echo esc_attr__( 'Tìm kiếm', TEXT_DOMAIN ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
			<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"
				d="m21 21l-3.5-3.5M17 10a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z" />
		</svg>
		<?php echo $title ? '<span>' . $title . '</span>' : ''; ?>
	</button>
	<input type="hidden" name="post_type" value="<?php echo Helper::isWoocommerceActive() ? 'product' : 'post'; ?>">
</form>
		<?php

		return '<div class="inline-search' . $class . '">' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function dropdownSearch( array $atts = [] ): string {
		$defaultId = self::generateUniqueId( 'search' );

		$atts = shortcode_atts(
			[
				'title' => '',
				'class' => '',
				'id'    => $defaultId,
				'align' => '',
			],
			$atts,
			'dropdown_search'
		);

		$title       = esc_html( $atts['title'] ?: __( 'Tìm kiếm', TEXT_DOMAIN ) );
		$titleAttr   = esc_attr( $atts['title'] ?: __( 'Tìm kiếm', TEXT_DOMAIN ) );
		$titleFor    = esc_attr__( 'Tìm kiếm cho', TEXT_DOMAIN );
		$placeholder = esc_attr__( 'Tìm kiếm...', TEXT_DOMAIN );
		$class       = $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : '';
		$align       = $atts['align'] ? ' alignment-' . Helper::escAttr( $atts['align'] ) : '';
		$id          = Helper::escAttr( $atts['id'] ?: $defaultId );

		ob_start();
		?>
<a class="dropdown-trigger" title="<?php echo $titleAttr; ?>" href="javascript:;"
	data-fx-dropdown-toggle="#dropdown-<?php echo $id; ?>">
	<img src="<?php echo THEME_URL . 'resources/img/ic-search.png'; ?>" alt="Search Icon" class="size-4 svg-search">
	<svg class="size-4 svg-close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
		<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
			d="M6 18L17.94 6M18 18L6.06 6" />
	</svg>
</a>
<div role="search" class="dropdown-pane<?php echo $align; ?>" id="dropdown-<?php echo $id; ?>" data-fx-dropdown
	data-auto-focus="true">
	<form action="<?php echo Helper::home(); ?>" class="frm-search" method="get" accept-charset="UTF-8">
		<div class="frm-container">
			<label for="<?php echo $id; ?>" class="sr-only"><?php echo $titleFor; ?></label>
			<input id="<?php echo $id; ?>" required pattern="^(.*\S+.*)$" type="search" name="s"
				value="<?php echo get_search_query(); ?>" placeholder="<?php echo $placeholder; ?>">
			<button class="btn-s" type="submit" aria-label="<?php echo esc_attr__( 'Tìm kiếm', TEXT_DOMAIN ); ?>">
				<svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"
						d="m21 21l-3.5-3.5M17 10a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z" />
				</svg>
				<span><?php echo $title; ?></span>
			</button>
		</div>
		<?php
				Helper::blockTemplate( 'parts/blocks/search-hint' );
				echo '<input type="hidden" name="post_type" value="' . ( Helper::isWoocommerceActive() ? 'product' : 'post' ) . '">';
		?>
	</form>
</div>
		<?php

		return '<div class="dropdown-search' . $class . '">' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function offCanvasButton( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title'           => '',
				'hide_if_desktop' => true,
				'class'           => '',
			],
			$atts,
			'offcanvas_button'
		);

		$title  = esc_html( $atts['title'] ?: __( 'Menu', TEXT_DOMAIN ) );
		$class  = Helper::toBool( $atts['hide_if_desktop'] ) ? ' lg:hidden!' : '';
		$class .= $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : '';

		ob_start();
		?>
<button class="menu-lines flex items-center gap-3 hover:text-black dark:hover:text-white" type="button"
	data-open="offCanvasMenu" aria-label="<?php echo esc_attr__( 'Menu', TEXT_DOMAIN ); ?>">
	<span class="line w-7 h-5 flex flex-col flex-nowrap justify-between">
		<span class="line-1 relative w-full"></span>
		<span class="line-2 relative w-full"></span>
		<span class="line-3 relative w-full"></span>
	</span>
	<span class="menu-txt text-[15px] font-light order-1 hidden"><?php echo $title; ?></span>
</button>
		<?php

		return '<div class="off-canvas-content' . $class . '" data-fx-off-canvas-content>' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|false
	 */
	public function horizontalMenu( array $atts = [] ): string|false {
		$defaultId = self::generateUniqueId( 'menu' );

		$atts = shortcode_atts(
			[
				'location'         => 'main-nav',
				'class'            => 'dropdown menu horizontal-menu',
				'extra_class'      => '',
				'id'               => $defaultId,
				'depth'            => 4,
				'li_class'         => '',
				'li_depth_class'   => '',
				'link_class'       => '',
				'link_depth_class' => '',
				'attr'             => '',
				'data_autohide'    => false,
				'data_hover'       => true,
			],
			$atts,
			'horizontal_menu'
		);

		$location   = Helper::escAttr( $atts['location'] ?: 'main-nav' );
		$class      = $atts['class'] ? Helper::escAttr( $atts['class'] ) . ' ' . $location : $location;
		$extraClass = $atts['extra_class'] ? Helper::escAttr( $atts['extra_class'] ) : '';

		return Helper::horizontalNav(
			[
				'menu_id'          => Helper::escAttr( $atts['id'] ?: $defaultId ),
				'menu_class'       => $extraClass ? $class . ' ' . $extraClass : $class,
				'theme_location'   => $location,
				'depth'            => $atts['depth'] ? absint( $atts['depth'] ) : 1,
				'li_class'         => $atts['li_class'] ? Helper::escAttr( $atts['li_class'] ) : '',
				'li_depth_class'   => $atts['li_depth_class'] ? Helper::escAttr( $atts['li_depth_class'] ) : '',
				'link_class'       => $atts['link_class'] ? Helper::escAttr( $atts['link_class'] ) : '',
				'link_depth_class' => $atts['link_depth_class'] ? Helper::escAttr( $atts['link_depth_class'] ) : '',
				'data_hover'       => Helper::toBool( $atts['data_hover'] ),
				'data_autohide'    => Helper::toBool( $atts['data_autohide'] ),
				'echo'             => false,
			]
		);
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|false
	 */
	public function verticalMenu( array $atts = [] ): string|false {
		$defaultId = self::generateUniqueId( 'menu' );

		$atts = shortcode_atts(
			[
				'location'         => 'mobile-nav',
				'class'            => 'menu vertical vertical-menu mobile-menu',
				'extra_class'      => '',
				'id'               => $defaultId,
				'depth'            => 4,
				'li_class'         => '',
				'li_depth_class'   => '',
				'link_class'       => '',
				'link_depth_class' => '',
			],
			$atts,
			'vertical_menu'
		);

		$location   = Helper::escAttr( $atts['location'] ?: 'mobile-nav' );
		$class      = $atts['class'] ? Helper::escAttr( $atts['class'] ) . ' ' . $location : $location;
		$extraClass = $atts['extra_class'] ? Helper::escAttr( $atts['extra_class'] ) : '';

		return Helper::verticalNav(
			[
				'menu_id'          => Helper::escAttr( $atts['id'] ?: $defaultId ),
				'menu_class'       => $extraClass ? $class . ' ' . $extraClass : $class,
				'theme_location'   => $location,
				'depth'            => $atts['depth'] ? absint( $atts['depth'] ) : 1,
				'li_class'         => $atts['li_class'] ? Helper::escAttr( $atts['li_class'] ) : '',
				'li_depth_class'   => $atts['li_depth_class'] ? Helper::escAttr( $atts['li_depth_class'] ) : '',
				'link_class'       => $atts['link_class'] ? Helper::escAttr( $atts['link_class'] ) : '',
				'link_depth_class' => $atts['link_depth_class'] ? Helper::escAttr( $atts['link_depth_class'] ) : '',
				'echo'             => false,
			]
		);
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|null
	 */
	public function posts( array $atts = [] ): ?string {
		// Allowed wrapper tags (whitelist for security)
		$allowedTags = [ 'div', 'article', 'section', 'li', 'span', '' ];
		$atts        = shortcode_atts(
			[
				'post_type'        => 'post',
				'taxonomy'         => 'category',
				'term_ids'         => [],
				'exclude_ids'      => [],
				'include_children' => false,
				'limit'            => 12,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'wrapper_tag'      => '',
				'wrapper_class'    => '',
				'show'             => [
					'title_tag'      => 'p',
					'thumbnail'      => true,
					'thumbnail_size' => 'medium',
					'scale'          => false,
					'time'           => true,
					'term'           => true,
					'desc'           => true,
					'view_more'      => true,
				],
			],
			$atts,
			'posts'
		);
		// Deep merge 'show' defaults (shortcode_atts only shallow-merges)
		$showDefaults = [
			'title_tag'      => 'p',
			'thumbnail'      => true,
			'thumbnail_size' => 'medium',
			'scale'          => false,
			'time'           => true,
			'term'           => true,
			'desc'           => true,
			'view_more'      => true,
		];
		$atts['show'] = wp_parse_args( $atts['show'] ?? [], $showDefaults );

		$termIds         = $atts['term_ids'] ?: [];
		$excludeIds      = $atts['exclude_ids'] ?: [];
		$limit           = $atts['limit'] ? absint( $atts['limit'] ) : Helper::getOption( 'posts_per_page' );
		$includeChildren = Helper::toBool( $atts['include_children'] );

		$r = Helper::queryByTerms(
			[
				'terms'            => $termIds,
				'post_type'        => $atts['post_type'],
				'taxonomy'         => $atts['taxonomy'],
				'limit'            => $limit,
				'return_query'     => true,
				'include_children' => $includeChildren,
				'exclude_ids'      => $excludeIds,
				'orderby'          => $atts['orderby'],
				'order'            => $atts['order'],
			]
		);
		if ( ! $r ) {
			return null;
		}
		// Sanitize wrapper tag with whitelist
		$wrapperTag = strtolower( trim( $atts['wrapper_tag'] ) );
		if ( ! in_array( $wrapperTag, $allowedTags, true ) ) {
			$wrapperTag = '';
		}
		$wrapperClass = $wrapperTag ? Helper::escAttr( $atts['wrapper_class'] ) : '';
		$wrapperOpen  = $wrapperTag ? '<' . $wrapperTag . ' class="' . $wrapperClass . '">' : '';
		$wrapperClose = $wrapperTag ? '</' . $wrapperTag . '>' : '';
		ob_start();
		while ( $r->have_posts() ) :
			$r->the_post();
			echo $wrapperOpen;
			get_template_part( 'parts/post/loop', null, $atts['show'] );
			echo $wrapperClose;
		endwhile;
		wp_reset_postdata();
		return ob_get_clean();
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|null
	 */
	public function block_fixed_info( array $atts = [] ): string {
		$atts         = shortcode_atts(
			[
				'class' => '',
			],
			$atts,
			'block_fixed_info'
		);
		$title_vi     = Helper::getField( 'title_fixed_post_vi', 'option' );
		$title_en     = Helper::getField( 'title_fixed_post_en', 'option' );
		$content_vi   = Helper::getField( 'content_fixed_post_vi', 'option' );
		$content_en   = Helper::getField( 'content_fixed_post_en', 'option' );
		$popup_advise = Helper::getField( 'popup_advise', 'option' );
		$popup_quote  = Helper::getField( 'popup_quote', 'option' );
		$link_chat    = Helper::getField( 'link_chat', 'option' );
		$m_hotline    = Helper::getField( 'm_hotline', 'option' );
		$lang         = Helper::currentLanguage();
		$title        = $lang === 'en' ? $title_en : $title_vi;
		$content      = $lang === 'en' ? $content_en : $content_vi;
		$classes      = 'sc-fixed-info' . ( $atts['class'] ? ' ' . $atts['class'] : '' );
		ob_start();
		?>
<div
	class="<?php echo esc_attr( $classes ); ?> text-center px-4 py-5 lg:px-5 lg:py-8 shadow-[0_3px_16px_0_#0B20301A] rounded-xl">
		<?php echo Helper::siteTitleOrLogo(); ?>
	<div class="fixed-content mb-4">
		<?php if ( $title ) : ?>
		<p class="text-secondary text-lg xl:text-[32px] font-bold"><?php echo esc_html( $title ); ?></p>
		<?php endif; ?>
		<?php if ( $content ) : ?>
		<div class="max-w-full xl:max-w-[80%] mx-auto font-normal">
			<?php echo wp_kses_post( $content ); ?>
		</div>
		<?php endif; ?>
		<?php
		if ( $m_hotline ) {
			echo '<div class="box-hotline mt-4">';
			echo '<a href="tel:' . $m_hotline . '" class="flex flex-wrap items-center justify-center font-medium gap-2">';
			echo '<span>HOTLINE:</span>';
			echo '<span class="text-lg xl:text-xl font-bold text-primary">' . $m_hotline . '</span>';
			echo '<span>' . __( '(HỖ TRỢ 24/7)', TEXT_DOMAIN ) . '</span>';
			echo '</a>';
			echo '</div>';
		}
		?>
	</div>
	<div class="group-btn flex flex-wrap justify-center md:grid gap-3 lg:gap-4 md:grid-cols-3">
		<?php if ( $popup_advise ) { ?>
		<a href="#popup-advise"
			class="fcy-popup flex items-center justify-center relative z-1 bg-[var(--color-title)] rounded-[30px] w-fit md:w-full h-12 xl:h-14 px-8 lg:px-1 text-white font-medium text-sm lg:text-xl before:content[''] before:absolute before:left-1/2 before:top-1/2 before:-translate-x-1/2 before:-translate-y-1/2 before:w-[calc(100%-10px)] before:h-[calc(100%-10px)] before:rounded-[30px] before:inset-0 before:p-[1px] before:-z-1 before:pointer-events-none before:bg-[linear-gradient(90deg,#FBA200_0%,#E9D7AB_82.05%)] before:[mask-composite:exclude] transition-all hover:bg-primary">
			<?php echo __( 'Yêu cầu tư vấn', TEXT_DOMAIN ); ?>
		</a>
			<?php
		}
		if ( $popup_quote ) {
			?>
		<a href="#popup-quote"
			class="fcy-popup flex items-center justify-center relative z-1 bg-[var(--color-title)] rounded-[30px] w-fit md:w-full h-12 xl:h-14 px-8 lg:px-1 text-white font-medium text-sm lg:text-xl before:content[''] before:absolute before:left-1/2 before:top-1/2 before:-translate-x-1/2 before:-translate-y-1/2 before:w-[calc(100%-10px)] before:h-[calc(100%-10px)] before:rounded-[30px] before:inset-0 before:p-[1px] before:-z-1 before:pointer-events-none before:bg-[linear-gradient(90deg,#FBA200_0%,#E9D7AB_82.05%)] before:[mask-composite:exclude] transition-all hover:bg-primary">
			<?php echo __( 'Yêu cầu báo giá', TEXT_DOMAIN ); ?>
		</a>
			<?php
		}
		if ( $link_chat ) {
			?>
		<a href="<?php echo $link_chat; ?>" target="_blank"
			class="flex items-center justify-center relative z-1 bg-[var(--color-title)] rounded-[30px] w-fit md:w-full max-md:flex-auto h-12 xl:h-14 px-8 lg:px-1 text-white font-medium text-sm lg:text-xl before:content[''] before:absolute before:left-1/2 before:top-1/2 before:-translate-x-1/2 before:-translate-y-1/2 before:w-[calc(100%-10px)] before:h-[calc(100%-10px)] before:rounded-[30px] before:inset-0 before:p-[1px] before:-z-1 before:pointer-events-none before:bg-[linear-gradient(90deg,#FBA200_0%,#E9D7AB_82.05%)] before:[mask-composite:exclude] transition-all hover:bg-primary">
			<?php echo __( 'Chat trực tiếp', TEXT_DOMAIN ); ?>
		</a>
			<?php
		}
		?>

	</div>
</div>
		<?php
		return ob_get_clean();
	}
}