<?php
/**
 * Template Hooks
 *
 * Contains all template-related hooks for header, footer, and enqueue.
 * Merged from: hooks/header.php, hooks/footer.php, hooks/enqueue.php
 *
 * @package HD\TemplateHooks
 * @author  HD
 */

use HD\Core\Asset;
use HD\Utilities\Helper;

\defined( 'ABSPATH' ) || die;

// ============================================================
// WP_HEAD HOOKS
// ============================================================

add_action( 'wp_head', 'hd_wp_head_base', 1 );
function hd_wp_head_base(): void {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
	echo '<meta name="format-detection" content="telephone=no,email=no,address=no" />';
}

// ----------------------------------------

add_action( 'wp_head', 'hd_wp_head_other', 97 );
function hd_wp_head_other(): void {
	// Theme Color
	$theme_color = Helper::getThemeMod( 'theme_color_setting' );
	if ( $theme_color ) {
		printf( '<meta name="theme-color" content="%s" />', Helper::escAttr( $theme_color ) );
	}

	// Preload JS imports (modulepreload)
	Asset::preload( 'index.js' );

	// Note: All CSS (tailwind.css, main.scss, share/page.scss) are loaded blocking
	// via Theme::enqueueAssets() - no preload needed to avoid duplicate loading
}

// ============================================================
// HEADER HOOKS
add_action( 'hd_header_action', 'construct_header_action', 10 );
function construct_header_action(): void {
	/**
	 * @see _masthead_home_seo_header - 10
	 * @see _masthead_top_header - 12
	 * @see _masthead_header - 13
	 * @see _masthead_bottom_header - 14
	 * @see _masthead_custom - 98
	 */
	do_action( 'masthead' );
}
// -----------------------------------------------
add_action( 'masthead', '_masthead_header', 13 );
function _masthead_header(): void { ?>
<div id="inside-header" class="relative">
	<div class="u-container flex sm:flex-wrap items-center justify-between gap-3 sm:gap-5 relative">
		<div class="w-60 xl:w-70 max-sm:w-45 cell-logo relative">
			<?php echo Helper::siteTitleOrLogo(); ?>
		</div>
		<div class="cell-menu xl:flex-1 max-lg:hidden">
			<nav class="nav">
				<?php
						$atts = [
							'location'    => 'main-nav',
							'extra_class' => 'main-nav flex flex-wrap items-center justify-center gap-8',
						];
						echo Helper::doShortcode( 'horizontal_menu', $atts );
						?>
			</nav>
		</div>
		<div class="cell-right flex flex-wrap items-center gap-3">
			<div class="search-wrap flex items-center">
				<span class="line"></span>
				<?php echo Helper::doShortcode( 'dropdown_search' ); ?>
				<span class="line"></span>
			</div>
			<div class="header-social flex items-center gap-2">
				<?php if ( have_rows('socials', 'option') ) : ?>
					<?php while ( have_rows('socials', 'option') ) : the_row(); 
						$icon = get_sub_field('icon');
						$link = get_sub_field('link');
						$icon_url = wp_get_attachment_image_url($icon, 'full');
					?>
						<a href="<?php echo esc_url($link); ?>" target="_blank">
							<img src="<?php echo esc_url($icon_url); ?>" class="w-5 h-5 object-contain">
						</a>
					<?php endwhile; ?>
				<?php endif; ?>
			</div>
			<span class="line"></span>
			<div class="header-lang">
				<ul class="lang-list">
					<?php pll_the_languages([
						'dropdown'      => 0,
						'show_flags'    => 1,
						'show_names'    => 0,
						'hide_current'  => 0,
						'echo'          => 1
					]); ?>
				</ul>
			</div>

		</div>

	</div>
</div>
	<?php
}
// ============================================================

// ============================================================
// WP_FOOTER HOOKS
add_action( 'hd_footer_action', 'construct_footer_action', 10 );
function construct_footer_action(): void {
	/**
	 * @see _construct_footer_columns - 11
	 * @see _construct_footer_credit - 12
	 * @see _construct_footer_custom - 98
	 */
	do_action( 'construct_footer' );
}
// ============================================================
add_action( 'hd_header_after_action', 'hd_construct_off_canvas', 10 );
function hd_construct_off_canvas(): void {
	get_template_part( 'parts/blocks/off-canvas' );
}


// ============================================================
// FOOTER HOOKS
// hd_footer_before_action
add_action( 'hd_footer_before_action', 'hd_before_footer', 5 );
function hd_before_footer(): void {

}

add_action( 'construct_footer', '_construct_footer_columns', 11 );
function _construct_footer_columns(): void {
	$desc_form_footer    = Helper::getField( 'desc_form_footer', 'option' );
	$desc_form_footer_en = Helper::getField( 'desc_form_footer_en', 'option' );
	$sl_form_footer      = Helper::getField( 'sl_form_footer', 'option' );
	$menu_footer_one     = Helper::getField( 'menu_footer_one', 'option' );
	$menu_footer_two     = Helper::getField( 'menu_footer_two', 'option' );
	$menu_footer_one_en  = Helper::getField( 'menu_footer_one_en', 'option' );
	$menu_footer_two_en  = Helper::getField( 'menu_footer_two_en', 'option' );
	$group_certificate   = Helper::getField( 'group_certificate', 'option' );
	$current_lang        = pll_current_language();
	?>
		<div id="footer-columns" class="footer-columns">
			<div class="u-container relative overflow-hidden">
				<div class="footer-main flex flex-wrap items-center gap-6 sm:gap-10 pt-10 lg:pt-15 pb-3">
					<div class="w-full lg:w-[calc(50%-20px)] col-form">
						<?php
						echo Helper::siteTitleOrLogo();
						if ( $current_lang === 'vi' && $desc_form_footer ) {
							echo '<div class="desc text-center max-w-full xl:max-w-[80%] mx-auto mt-5 mb-5">' . $desc_form_footer . '</div>';
						} elseif ( $current_lang === 'en' && $desc_form_footer_en ) {
							echo '<div class="desc text-center max-w-full xl:max-w-[80%] mx-auto mt-5 mb-5">' . $desc_form_footer_en . '</div>';
						}
						if ( $sl_form_footer ) {
							echo do_shortcode( '[contact-form-7 id="' . esc_attr( $sl_form_footer ) . '"]' );
						}
						?>
					</div>
					<div class="w-full lg:w-[calc(50%-20px)] col-menu">
						<div class="flex flex-wrap sm:justify-center lg:gap-20 max-lg:gap-10 max-sm:gap-7">
							<?php
							$footer_menus = ( $current_lang === 'vi' )
							? [ $menu_footer_one, $menu_footer_two ]
							: [ $menu_footer_one_en, $menu_footer_two_en ];
							foreach ( $footer_menus as $menu_data ) {
								if ( $menu_data ) {
									echo '<div class="menu-footer">';
									if ( ! empty( $menu_data['title_menu'] ) ) {
										echo '<p class="footer-menu-title mb-3 text-[var(--color-title)] text-[18px] font-medium">' . esc_html( $menu_data['title_menu'] ) . '</p>';
									}
									if ( ! empty( $menu_data['sl_menu'] ) ) {
										wp_nav_menu(
											[
												'menu' => $menu_data['sl_menu'],
												'menu_class' => 'footer-menu',
											]
										);
									}
									echo '</div>';
								}
							}
							?>
						</div>
					</div>
				</div>
				<div
					class="footer-bottom flex flex-wrap items-center justify-between max-sm:justify-center border-t border-[#36270533] pt-5 pb-5 gap-5 mt-5">
					<?php
					if ( $group_certificate ) {
						echo '<div class="col-certificates flex items-center gap-5 max-md:gap-3">';
						echo '<p class="title mb-0 font-medium max-md:text-sm">' . __( 'Chứng chỉ', TEXT_DOMAIN ) . '</p>';
						foreach ( $group_certificate as $item ) {
							if ( ! empty( $item['img'] ) ) {
								echo '<a href="' . esc_url( $item['link'] ?? 'javascript:void(0)' ) . '" target="_blank" rel="noopener noreferrer">';
								echo '<img src="' . esc_url( $item['img'] ) . '" alt="image" class="h-[40px] object-contain" />';
								echo '</a>';
							}
						}
						echo '</div>';
					}
					?>
					<div class="col-social flex items-center gap-5 max-md:gap-3">
						<p class="title mb-0 font-medium max-md:text-sm">
							<?php echo __( 'Theo dõi', TEXT_DOMAIN ); ?></p>
						<?php echo Helper::doShortcode( 'social_menu', [ 'class' => 'social-menu flex flex-row gap-2' ] ); ?>
					</div>
				</div>
				<img src="<?php echo THEME_URL . 'resources/img/fav-logo-blur.png'; ?>" alt="layer"
					class="absolute bottom-0 right-0 md:-right-[30px] size-[80px] md:size-[200px] object-cover -z-1">
			</div>
		</div>
		<?php
}

add_action( 'construct_footer', '_construct_footer_custom', 98 );
function _construct_footer_custom(): void {
	$popup_advise = Helper::getField( 'popup_advise', 'option' );
	$popup_quote  = Helper::getField( 'popup_quote', 'option' );
	?>
		<div class="popup-content rounded-lg !bg-[#F6F2EA] hidden" id="popup-advise">
			<div class="inner">
				<div class="inner-content text-center mb-3">
					<p class="text-[var(--color-title)] font-semibold text-xl capitalize xl:text-2xl mb-2">
						<?php echo __( 'Gửi yêu cầu tư vấn', TEXT_DOMAIN ); ?></p>
					<div class="">
						<?php echo __( 'Vui lòng để lại thông tin, chúng tôi sẽ liên hệ tư vấn trong thời gian sớm nhất!', TEXT_DOMAIN ); ?>
					</div>
				</div>
				<?php
				echo do_shortcode( '[contact-form-7 id="' . esc_attr( $popup_advise ) . '"]' );
				?>
			</div>
		</div>

		<div class="popup-content rounded-lg !bg-[#F6F2EA] hidden" id="popup-quote">
			<div class="inner">
				<div class="inner-content text-center mb-3">
					<p class="text-[var(--color-title)] font-semibold text-xl capitalize xl:text-2xl mb-2">
						<?php echo __( 'Gửi yêu cầu báo giá', TEXT_DOMAIN ); ?></p>
					<div class="">
						<?php echo __( 'Vui lòng để lại thông tin, chúng tôi sẽ liên hệ tư vấn trong thời gian sớm nhất!', TEXT_DOMAIN ); ?>
					</div>
				</div>
				<?php
				echo do_shortcode( '[contact-form-7 id="' . esc_attr( $popup_quote ) . '"]' );
				?>
			</div>
		</div>
		<?php
}

add_action( 'wp_footer', 'back_to_top', 32 );
function back_to_top(): void {
	if ( apply_filters( 'hd_back_to_top_filter', true ) ) {
		echo apply_filters(
			'hd_back_to_top_output_filter',
			sprintf(
				'<a title="%1$s" aria-label="%1$s" rel="nofollow" href="#" class="c-back-to-top w-8 h-8 right-4 bottom-15 border bg-white hover:bg-primary hover:border-white hover:text-white" data-fx-scroll-top data-show="false" data-scroll-speed="%2$s" data-scroll-start="%3$s">%4$s</a>',
				esc_attr__( 'Scroll back to top', TEXT_DOMAIN ),
				absint( apply_filters( 'hd_back_to_top_scroll_speed_filter', 400 ) ),
				absint( apply_filters( 'hd_back_to_top_scroll_start_filter', 300 ) ),
				'<svg class="w-6 h-6 relative block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up-icon lucide-chevron-up"><path d="m18 15-6-6-6 6"/></svg>'
			)
		);
	}
}
// ============================================================

// ============================================================
// WP_ENQUEUE_SCRIPTS HOOKS
// ============================================================

add_action( 'wp_enqueue_scripts', 'hd_enqueue_external_fonts', 999 );
function hd_enqueue_external_fonts(): void {
	// Preconnect & DNS prefetch for external domains
	add_filter(
		'wp_resource_hints',
		static function ( array $urls, string $relation_type ): array {
			// Preconnect to fonts.gstatic.com (font files origin)
			// WP auto adds dns-prefetch for fonts.googleapis.com from enqueued style
			if ( 'preconnect' === $relation_type ) {
				$urls[] = [
					'href'        => 'https://fonts.gstatic.com',
					'crossorigin' => 'anonymous',
				];
			}

			// Note: Add 'dns-prefetch' handling here if site uses external services
			// Example: if ( 'dns-prefetch' === $relation_type ) { $urls[] = 'https://...'; }

			return $urls;
		},
		10,
		2
	);
	// Enqueue Google Fonts
	Asset::enqueueStyle([
		'handle' => 'font-awesome',
		'src'    => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
	]);

	// Enqueue Google Fonts
	Asset::enqueueStyle(
		[
			'handle' => 'google-fonts-montserrat',
			'src'    => 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap',
		]
	);

	Asset::enqueueStyle(
		[
			'handle' => 'google-fonts-prata',
			'src'    => 'https://fonts.googleapis.com/css2?family=Prata&display=swap',
		]
	);

}