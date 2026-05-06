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

\defined('ABSPATH') || die;

// ============================================================
// WP_HEAD HOOKS
// ============================================================

add_action('wp_head', 'hd_wp_head_base', 1);
function hd_wp_head_base(): void
{
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
	echo '<meta name="format-detection" content="telephone=no,email=no,address=no" />';
}

// ----------------------------------------

add_action('wp_head', 'hd_wp_head_other', 97);
function hd_wp_head_other(): void
{
	// Theme Color
	$theme_color = Helper::getThemeMod('theme_color_setting');
	if ($theme_color) {
		printf('<meta name="theme-color" content="%s" />', Helper::escAttr($theme_color));
	}

	// Preload JS imports (modulepreload)
	Asset::preload('index.js');

	// Note: All CSS (tailwind.css, main.scss, share/page.scss) are loaded blocking
	// via Theme::enqueueAssets() - no preload needed to avoid duplicate loading
}

// ============================================================
// HEADER HOOKS
add_action('hd_header_action', 'construct_header_action', 10);
function construct_header_action(): void
{
	/**
	 * @see _masthead_home_seo_header - 10
	 * @see _masthead_top_header - 12
	 * @see _masthead_header - 13
	 * @see _masthead_bottom_header - 14
	 * @see _masthead_custom - 98
	 */
	do_action('masthead');
}
// -----------------------------------------------
add_action('masthead', '_masthead_header', 13);
function _masthead_header(): void
{ ?>
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
					echo Helper::doShortcode('horizontal_menu', $atts);
					?>
				</nav>
			</div>
			<div class="cell-right flex flex-wrap items-center gap-3">
				<div class="search-wrap flex items-center">
					<span class="line"></span>
					<?php echo Helper::doShortcode('dropdown_search'); ?>
					<span class="line"></span>
				</div>
				<div class="header-social flex items-center gap-2">
					<?php if (have_rows('socials', 'option')) : ?>
						<?php while (have_rows('socials', 'option')) : the_row();
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
add_action('hd_footer_action', 'construct_footer_action', 10);
function construct_footer_action(): void
{
	/**
	 * @see _construct_footer_columns - 11
	 * @see _construct_footer_credit - 12
	 * @see _construct_footer_custom - 98
	 */
	do_action('construct_footer');
}
// ============================================================
add_action('hd_header_after_action', 'hd_construct_off_canvas', 10);
function hd_construct_off_canvas(): void
{
	get_template_part('parts/blocks/off-canvas');
}

// ============================================================
// FOOTER CREDIT HOOK
// ============================================================

add_action('construct_footer', '_construct_footer_credit', 12);
function _construct_footer_credit(): void {
    ?>
        <div class="footer-credit py-8 border-t border-white/10 relative z-10">
            <div class="u-container flex flex-wrap items-center justify-between gap-4">
                <?php
                echo '<div class="footer-copyright text-sm text-white/70">';
                $copyright = sprintf(
                    '<span class="copyright">&copy; %1$s %2$s,&nbsp;</span><span class="hd">%3$s <a class="hover:text-white transition-colors" title="%6$s" href="%4$s" %5$s>%6$s</a></span>',
                    date('Y'),
                    get_bloginfo('name'),
                    __('design by', TEXT_DOMAIN),
                    esc_url('https://webhd.vn/'),
                    HD\Utilities\Helper::microdata('url'),
                    __('HD Agency', TEXT_DOMAIN)
                );
                echo apply_filters('hd_copyright', $copyright);
                echo '</div>';

                if ( is_active_sidebar( 'social-menu-sidebar' ) ) :
                    echo '<div class="social-menu-sidebar text-white/70">';
                    dynamic_sidebar( 'social-menu-sidebar' );
                    echo '</div>';
                endif;
                ?>
            </div>
        </div>
    </div> 
    <?php
}

// ============================================================
// FOOTER HOOKS
// hd_footer_before_action
add_action('hd_footer_before_action', 'hd_before_footer', 5);
function hd_before_footer(): void {}

add_action('construct_footer', '_construct_footer_columns', 11);
function _construct_footer_columns(): void
{
    $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'vi';
    $re_address     = ($current_lang === 'en') ? 're_address_en' : 're_address';
    $footer_bg    = get_template_directory_uri() . '/assets/img/bg-footer.png';
    $company_title = get_field('company_title', 'option') ?: ''; 
?>
    <div id="footer-main-wrap" 
         class="footer-main-wrap bg-primary text-white bg-cover bg-center bg-no-repeat relative"
         style="background-image: url('<?php echo esc_url($footer_bg); ?>');">
        
        <div class="u-container pt-16 relative z-10">
			<div class="footer-brand-overlay mb-10 lg:mb-16 text-center overflow-hidden">
				<span class="text-[12vw] font-semibold uppercase tracking-widest text-transparent block leading-none select-none"
					style="-webkit-text-stroke: 1px rgba(255,255,255,0.2); font-size: clamp(40px, 15vw, 220px);">
					<?php echo esc_html($company_title); ?>
				</span>
			</div>

            <?php if (have_rows($re_address, 'option')) : ?>
                <div class="footer-address-container p-8 md:p-12 rounded-3xl border border-white/20 bg-white/5 backdrop-blur-sm relative mb-16">
                    <div class="footer-address-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-10 md:gap-x-12">
                        <?php
                        $rows = get_field($re_address, 'option');
                        $total = count($rows);
                        $i = 0;
                        while (have_rows($re_address, 'option')) : the_row();
                            $i++;
                            $title   = get_sub_field('heading_title');
                            $address = get_sub_field('address');
                            $phone   = get_sub_field('phone');
                        ?>
                            <div class="address-item flex flex-col relative">
                                <?php if ($title) : ?>
                                    <h4 class="address-title text-lg font-bold uppercase mb-4"><?php echo esc_html($title); ?></h4>
                                <?php endif; ?>

                                <div class="flex flex-col gap-3">
                                    <?php if ($address) : ?>
                                        <div class="flex items-start gap-3">
                                            <i class="fa-solid fa-location-dot mt-1 text-white/60 text-sm"></i>
                                            <p class="text-sm leading-relaxed text-white/90"><?php echo esc_html($address); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($phone) : ?>
                                        <div class="flex items-center gap-3">
                                            <i class="fa-solid fa-phone text-white/60 text-sm"></i>
                                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>" class="text-sm hover:text-white text-white/90">
                                                <?php echo esc_html($phone); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($i < $total) : ?>
                                    <div class="hidden lg:block absolute -right-6 top-0 bottom-0 w-[1px] bg-white/10"></div>
                                    <div class="block lg:hidden w-full h-[1px] bg-white/10 mt-10"></div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- Note: We do NOT close the main background div here yet -->
<?php
}

add_action('construct_footer', '_construct_footer_custom', 98);
function _construct_footer_custom(): void
{
	$popup_advise = Helper::getField('popup_advise', 'option');
	$popup_quote  = Helper::getField('popup_quote', 'option');
?>
	<div class="popup-content rounded-lg !bg-[#F6F2EA] hidden" id="popup-advise">
		<div class="inner">
			<div class="inner-content text-center mb-3">
				<p class="text-[var(--color-title)] font-semibold text-xl capitalize xl:text-2xl mb-2">
					<?php echo __('Gửi yêu cầu tư vấn', TEXT_DOMAIN); ?></p>
				<div class="">
					<?php echo __('Vui lòng để lại thông tin, chúng tôi sẽ liên hệ tư vấn trong thời gian sớm nhất!', TEXT_DOMAIN); ?>
				</div>
			</div>
			<?php
			echo do_shortcode('[contact-form-7 id="' . esc_attr($popup_advise) . '"]');
			?>
		</div>
	</div>

	<div class="popup-content rounded-lg !bg-[#F6F2EA] hidden" id="popup-quote">
		<div class="inner">
			<div class="inner-content text-center mb-3">
				<p class="text-[var(--color-title)] font-semibold text-xl capitalize xl:text-2xl mb-2">
					<?php echo __('Gửi yêu cầu báo giá', TEXT_DOMAIN); ?></p>
				<div class="">
					<?php echo __('Vui lòng để lại thông tin, chúng tôi sẽ liên hệ tư vấn trong thời gian sớm nhất!', TEXT_DOMAIN); ?>
				</div>
			</div>
			<?php
			echo do_shortcode('[contact-form-7 id="' . esc_attr($popup_quote) . '"]');
			?>
		</div>
	</div>
<?php
}

add_action('wp_footer', 'back_to_top', 32);
function back_to_top(): void
{
	if (apply_filters('hd_back_to_top_filter', true)) {
		echo apply_filters(
			'hd_back_to_top_output_filter',
			sprintf(
				'<a title="%1$s" aria-label="%1$s" rel="nofollow" href="#" class="c-back-to-top w-8 h-8 right-4 bottom-15 border bg-white hover:bg-primary hover:border-white hover:text-white" data-fx-scroll-top data-show="false" data-scroll-speed="%2$s" data-scroll-start="%3$s">%4$s</a>',
				esc_attr__('Scroll back to top', TEXT_DOMAIN),
				absint(apply_filters('hd_back_to_top_scroll_speed_filter', 400)),
				absint(apply_filters('hd_back_to_top_scroll_start_filter', 300)),
				'<svg class="w-6 h-6 relative block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up-icon lucide-chevron-up"><path d="m18 15-6-6-6 6"/></svg>'
			)
		);
	}
}
// ============================================================

// ============================================================
// WP_ENQUEUE_SCRIPTS HOOKS
// ============================================================

add_action('wp_enqueue_scripts', 'hd_enqueue_external_fonts', 999);
function hd_enqueue_external_fonts(): void
{
	// Preconnect & DNS prefetch for external domains
	add_filter(
		'wp_resource_hints',
		static function (array $urls, string $relation_type): array {
			// Preconnect to fonts.gstatic.com (font files origin)
			// WP auto adds dns-prefetch for fonts.googleapis.com from enqueued style
			if ('preconnect' === $relation_type) {
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
