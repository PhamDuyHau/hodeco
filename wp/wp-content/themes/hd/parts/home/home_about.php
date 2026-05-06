<?php

\defined( 'ABSPATH' ) || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if ( ! $acf_fc_layout ) {
	return;
}

$heading_title = $args['heading_title'] ?? '';
$content       = $args['content'] ?? '';
$img_id        = $args['img'] ?? '';
$bg_img_id     = $args['bg-img'] ?? '';
?>

<section class="section home-about relative pb-10">
	<div class="u-container">

		<div class="wrapper relative w-full lg:w-[70%] mx-auto lg:translate-x-[80px]">

			<!-- MAIN BOX -->
			<div class="content-box bg-white border border-[#ddd] rounded-[20px] my-20 py-15 pr-30 pl-[40px] lg:pl-[300px] relative">

				<?php if ( $heading_title ) { ?>
					<h2 class="heading-title inline-flex items-center gap-3 px-6 py-2 rounded-full border border-[#808080] font-medium text-[20px] leading-[30px] text-black">
						<span class="w-2.5 h-2.5 bg-red-500 rounded-full shrink-0"></span>
						<span class="capitalize">
							<?php echo $heading_title; ?>
						</span>
					</h2>

					<div class="mt-3">
						<img
							src="<?php echo get_template_directory_uri(); ?>/assets/img/quote-left-solid.png"
							alt="quote"
						>
					</div>
				<?php } ?>

				<?php
				if ( $content ) {
					echo '<div class="content font-medium mt-4">' . $content . '</div>';
				}
				?>
				<?php
				if ( $bg_img_id ) {
					echo '<div class="bg-img absolute bottom-0 right-0 z-0 pointer-events-none">';
					echo wp_get_attachment_image( $bg_img_id, 'full', false, [
						'class' => 'w-auto h-auto max-w-[300px] opacity-80'
					] );
					echo '</div>';
				}
				?>
				<?php
				if ( $img_id ) {
					echo '<div class="img absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 z-10">';
					echo wp_get_attachment_image( $img_id, 'full', false, [
						'class' => 'w-[450px] h-[450px] object-cover rounded-full'
					] );
					echo '</div>';
				}
				?>

			</div>

		</div>

	</div>
</section>