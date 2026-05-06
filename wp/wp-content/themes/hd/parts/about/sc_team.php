<?php
\defined( 'ABSPATH' ) || die;
$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if ( ! $acf_fc_layout ) {
	return;
}
$heading_title   = $args['heading_title'] ?? '';
$desc            = $args['desc'] ?? '';
$list_management = $args['list_management'] ?? '';
$list_team       = $args['list_team'] ?? '';
$autoplay        = $args['autoplay'] ?? false;
$navigation      = $args['navigation'] ?? false;
$pagination      = $args['pagination'] ?? false;
?>

<section class="section sc-team relative pt-10 pb-10">
	<div class="u-container">
		<div class="px-2 lg:px-8 mb-7 xl:mb-12">
			<div class="group-title mb-5 xl:mb-7">
				<?php
				echo '<h2 class="heading-title capitalize text-[var(--color-title)] text-[24px] xl:text-[36px] font-semibold mb-5">' . $heading_title . '</h2>';
				if ( $desc ) {
					echo '<div class="mx-auto text-center max-w-full xl:max-w-[80%]">' . $desc . '</div>';
				}
				?>
			</div>
			<?php
			if ( ! empty( $list_management ) ) {
				echo '<ul class="max-w-full xl:max-w-[85%] mx-auto grid grid-cols-1 xl:grid-cols-2 gap-5">';
				foreach ( $list_management as $management ) {
					$avt      = $management['avt'];
					$name     = $management['name'] ?? '';
					$position = $management['position'];
					$content  = $management['content'];
					echo '<li>';
					if ( $avt ) {
						echo '<div class="avt">';
						echo '<img class="w-[85%] h-full mx-auto object-contain" src="' . $avt . '" alt="' . $name . '">';
						echo '</div>';
					}
					echo '<div class="mx-auto max-w-full xl:max-w-[90%] -mt-[50px] xl:-mt-[80px] relative z-1 text-center p-4 xl:p-5 rounded-xl bg-white shadow-[0_1px_8px_#0000001A]">';
					if ( $name ) {
						echo '<h3 class="capitalize font-body text-lg xl:text-2xl text-secondary mb-2 font-medium">' . $name . '</h3>';
					}
					if ( $position ) {
						echo '<div class="font-medium text-[var(--color-title)] mb-2">' . $position . '</div>';
					}
					if ( $content ) {
						echo '<div class="[&_strong]:text-secondary">' . $content . '</div>';
					}
					echo '</div>';
					echo '</li>';
				}
				echo '</ul>';
			}
			?>
		</div>
		<div class="list-team relative mx-auto max-w-full xl:max-w-[90%]">
			<?php
			$data        = [
				'loop'          => true,
				'navigation'    => $navigation,
				'pagination'    => $navigation,
				'autoplay'      => $autoplay,
				'slidesPerView' => 'auto',
				'centered'      => true,
				'sm'            => [
					'slidesPerView' => 1.5,
				],
				'md'            => [
					'slidesPerView' => 2,
				],
				'xl'            => [
					'slidesPerView' => 3,
				],
			];
			$swiper_data = wp_json_encode( $data, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE );
			if ( ! $swiper_data ) {
				$swiper_data = '';
			}
			?>
			<div class="swiper" data-fx-slider>
				<div class="swiper-wrapper items-center pb-8 xl:pb-10"
					data-swiper-options="<?php echo esc_attr( $swiper_data ); ?>">
					<?php
					if ( ! empty( $list_team ) ) {
						foreach ( $list_team as $item ) {
							$avt      = $item['avt'];
							$name     = $item['name'] ?? '';
							$position = $item['position'];
							$content  = $item['content'];
							?>
					<div class="swiper-slide group px-4 cursor-pointer">
							<?php
							if ( $avt ) {
								echo '<div class="avt overflow-hidden rounded-xl isolate aspect-[10/9]">';
								echo '<img class="w-full h-full mx-auto object-cover rounded transition-transform duration-500 group-hover:scale-110 will-change-transform" src="' . $avt . '" alt="' . $name . '">';
								echo '</div>';
							}
							echo '<div class="mx-auto max-w-full xl:max-w-[95%] -mt-[20px] xl:-mt-[40px] relative z-1 text-center p-4 xl:p-5 rounded-xl bg-white shadow-[0_1px_8px_#0000001A] transition-all duration-500">';
							if ( $name ) {
								echo '<h3 class="capitalize font-body text-lg xl:text-2xl text-[var(--color-title)] mb-1 font-medium transition-colors group-[.swiper-slide-active]:text-secondary">' . $name . '</h3>';
							}
							if ( $position ) {
								echo '<div class="font-medium text-[var(--color-title)] mb-1 opacity-80">' . $position . '</div>';
							}
							if ( $content ) {
								echo '<div class="grid grid-rows-[0fr] opacity-0 transition-all duration-500 ease-in-out group-[.swiper-slide-active]:grid-rows-[1fr] group-[.swiper-slide-active]:opacity-100">';
									echo '<div class="overflow-hidden text-gray-600 [&_strong]:text-secondary">';
										echo $content;
									echo '</div>';
								echo '</div>';
							}
							echo '</div>';
							?>
					</div>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
	</div>
</section>