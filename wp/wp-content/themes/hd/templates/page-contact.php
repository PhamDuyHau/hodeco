<?php
/**
 * The template for displaying `contact`
 * Template Name: Page Contact Us
 * Template Post Type: page
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// header
get_header();

if ( have_posts() ) {
	the_post();
}
$ACF           = \HD_Helper::getFields( $post->ID );
$heading_title = $ACF['heading_title'] ?? '';
$sl_form       = $ACF['sl_form'] ?? '';
$m_hotline     = \HD_Helper::getField( 'm_hotline', 'option' );
$time_work     = \HD_Helper::getField( 'time_work', 'option' );
$time_work_en  = \HD_Helper::getField( 'time_work_en', 'option' );
$m_email       = \HD_Helper::getField( 'm_email', 'option' );
$m_address     = \HD_Helper::getField( 'm_address', 'option' );
$iframe_map    = \HD_Helper::getField( 'iframe_map', 'option' );
$lang          = \HD_Helper::currentLanguage();
?>
<?php \HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' ); ?>
<section class="section page-contact-us pt-10 pb-10">
	<div class="u-container">
		<h2 class="heading-title capitalize text-[var(--color-title)] text-[24px] lg:text-[36px] font-semibold mb-5">
			<?php echo $heading_title; ?>
		</h2>
		<div class="wrapper-inf-form flex flex-wrap pb-8 xl:pb-15">
			<div class="col-inf w-full lg:w-[38%] mb-6 lg:mb-0">
				<div class="inner relative rounded-xl bg-gradient-to-r from-[#CA8C1C] to-[#82590F] p-5">
					<div class="inner-img relative">
						<img src="<?php echo THEME_URL . 'resources/img/model-contact.png'; ?>"
							class="w-[240px] h-[245px] mx-auto object-contain" alt="<?php echo $heading_title; ?>">
					</div>
					<?php
					if ( $m_hotline ) {
						echo '<div class="inner-hotline relative">';
						echo '<a href="tel:' . $m_hotline . '" class="flex flex-wrap items-center justify-center bg-secondary px-3 py-4 rounded-lg text-white">';
						echo '<span class="text-xl font-normal">' . __( 'Hỗ trợ 24/7:', TEXT_DOMAIN ) . '</span>';
						echo '<span class="text-2xl xl:text-3xl font-bold pl-2">' . $m_hotline . '</span>';
						echo '</a>';
						echo '</div>';
					}
					?>
					<div class="inner-inf mt-4 mb-4 relative">
						<ul>
							<?php
							if ( $lang === 'vi' && $time_work ) {
								echo '<li class="mb-2 text-white font-semibold">' . $time_work . '</li>';
							} elseif ( $lang === 'en' && $time_work_en ) {
								echo '<li class="mb-2 text-white font-semibold">' . $time_work_en . '</li>';
							}
							if ( $m_email ) {
								echo '<li><a href="mailto:' . $m_email . '" class="text-white">' . $m_email . '</a></li>';
							}
							?>
						</ul>
					</div>
					<?php
					echo '<div class="inner-social relative">';
					echo '<p class="font-semibold text-white mb-2">' . __( 'Theo dõi Kim Law:', TEXT_DOMAIN ) . '</p>';
					echo \HD_Helper::doShortcode( 'social_menu' );
					echo '</div>';
					?>
				</div>
			</div>
			<div class="col-form w-full lg:w-[62%] lg:pl-8">
				<div class="inner">
					<?php echo do_shortcode( '[contact-form-7 id="' . esc_attr( $sl_form ) . '"]' ); ?>
				</div>
			</div>
		</div>
		<div class="wrapper-branch">
			<h2
				class="heading-title capitalize text-[var(--color-title)] text-[24px] lg:text-[36px] font-semibold mb-5 xl:mb-8">
				<?php echo __( 'Các chi nhánh của Kim Law', TEXT_DOMAIN ); ?>
			</h2>
			<?php
			if ( $m_address ) {
				echo '<ul class="grid grid-cols-1 xl:grid-cols-3 gap-4 xl:gap-5 mb-8 xl:mb-15">';
				foreach ( $m_address as $item ) {
					$label   = $item['title'];
					$address = $item['address'];
					echo '<li class="p-5 xl:p-8 rounded-lg backdrop-blur-[30px] shadow-[0_0_6px_0_rgba(0,0,0,0.06)] text-center">';
					if ( $label ) {
						echo '<p class="text-xl xl:text-2xl font-medium text-[var(--color-title)] mb-4 relative after:content-[\'\'] after:block after:w-16 after:h-1 after:mx-auto after:translate-y-2 after:rounded after:bg-[#D7CEBE]">' . $label . '</p>';
					}
					if ( $address ) {
						echo '<div class="font-medium">' . $address . '</div>';
					}
					echo '</li>';
				}
				echo '</ul>';
			}
			if ( $iframe_map ) {
				echo '<div class="iframe-map max-w-full xl:max-w-[90%] mx-auto rounded-2xl border-3 border-[#ba9545] overflow-hidden">' . $iframe_map . '</div>';
			}
			?>
		</div>
		<div class="wrapper-block pt-8 xl:pt-15">
			<?php echo \HD_Helper::doShortcode( 'block_fixed_info' ); ?>
		</div>
	</div>
</section>
<?php

// footer
get_footer();