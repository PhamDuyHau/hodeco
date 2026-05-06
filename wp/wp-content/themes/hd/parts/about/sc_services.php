<?php
\defined( 'ABSPATH' ) || die;
$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if ( ! $acf_fc_layout ) {
	return;
}
$heading_title = $args['heading_title'] ?? '';
$desc          = $args['desc'] ?? '';
$list_services = $args['list_services'] ?? '';
$img           = $args['img'] ?? '';
$link_advise   = $args['link_advise'] ?? '';
?>
<section class="section sc-services relative pt-10 pb-10">
	<div class="u-container">
		<div class="px-4 py-5 xl:p-8 bg-gradient-to-b from-[#FBF7EE] to-[#FFFFFF] rounded-2xl">
			<div class="group-title mb-7">
				<?php
					echo '<h2 class="heading-title capitalize text-[var(--color-title)] text-[24px] xl:text-[36px] font-semibold mb-5">' . $heading_title . '</h2>';
				if ( $desc ) {
					echo '<div class="mx-auto text-center max-w-full xl:max-w-[80%]">' . $desc . '</div>';
				}
				?>
			</div>
			<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 xl:gap-5 mx-auto mb-7 xl:mb-10 max-w-full xl:max-w-[85%]">
				<?php
				if ( $img ) {
					echo '<div class="img text-center">';
					echo '<img class="w-[80%] lg:w-full mx-auto h-full object-contain" src="' . $img . '" alt="' . $heading_title . '">';
					echo '</div>';
				}
				if ( ! empty( $list_services ) ) {
					echo '<div>';
					echo '<ul>';
					foreach ( $list_services as $val ) {
						$title = $val['title'];
						$link  = ! empty( $val['link'] ) ? esc_url( $val['link'] ) : 'javascript:void(0)';
						?>
				<li class="mb-2 last:mb-0">
					<a href="<?php echo $link; ?>"
						class="group flex items-center justify-between px-4 py-3 border rounded-lg border-[#6F551C1A] bg-white/80 hover:bg-primary">
						<div class="flex items-center gap-3">
							<?php echo hd_svg( 'conclude', 'w-3 h-3 text-[#CA8C1C] group-hover:text-white' ); ?>
							<?php
							if ( $title ) {
								echo '<h3 class="text-base sm:text-lg font-body font-medium group-hover:text-white">' . $title . '</h3>';
							}
							?>
						</div>
						<div class="ic">
							<?php echo hd_svg( 'up-right', 'w-3 h-3 text-[#CA8C1C] transition-all group-hover:text-white group-hover:rotate-[45deg]' ); ?>
						</div>
					</a>
				</li>
						<?php
					}
					echo '</ul>';
					echo '</div>';
				}
				?>
			</div>
			<?php if ( $link_advise ) { ?>
			<a href="<?php echo $link_advise; ?>"
				class="flex items-center mx-auto w-fit gap-5 p-[12px_20px] border-1 border-[#45320A] rounded font-semibold text-[#45320A] hover:bg-[#45320A] hover:text-white hover:-translate-y-1 transition-all">
				<span><?php echo __( 'Cần tư vấn ngay', TEXT_DOMAIN ); ?></span>
				<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
					viewBox="0 0 32 32">
					<path d="M18 6l-1.43 1.393L24.15 15H4v2h20.15l-7.58 7.573L18 26l10-10L18 6z" fill="currentColor">
					</path>
				</svg>
			</a>
			<?php } ?>
		</div>

	</div>
</section>