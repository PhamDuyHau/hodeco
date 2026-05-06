<?php

defined( 'ABSPATH' ) || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if ( ! $acf_fc_layout ) {
	return;
}

$heading_title = $args['heading_title'] ?? '';
$content       = $args['content'] ?? '';
$lists_content = $args['lists_content'] ?? '';
?>

<section class="section sc-about relative pt-10 pb-10">
	<div class="u-container">

		<?php
		if ( ! empty( $lists_content ) ) {

			echo '<ul class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6 xl:mt-10">';

			foreach ( $lists_content as $val ) {

				$title   = $val['title'] ?? '';
				$content = $val['content'] ?? '';
				$img     = $val['img'] ?? '';

				echo '<li class="group flex flex-col justify-start even:flex-col-reverse even:justify-center p-3 rounded-xl backdrop-blur-[30px] shadow-[0_2px_15px_0_#0000001A] bg-[#FBF6ED] even:bg-[var(--color-title)]">';

				if ( $img ) {
					echo '<div class="aspect-[16/10] overflow-hidden">';
					echo '<img class="rounded-xl w-full h-full object-cover" src="' . $img . '" alt="' . $title . '">';
					echo '</div>';
				}

				echo '<div class="pt-5 group-even:pt-0 group-even:pb-5 text-center">';

				if ( $title ) {
					echo '<h3 class="relative font-body text-xl xl:text-2xl pb-4 mb-4 text-[var(--color-title)] group-even:text-white after:content[\'\'] after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-16 after:h-1 after:bg-primary after:rounded">'
						. $title .
					'</h3>';
				}

				if ( $content ) {
					echo '<div class="font-light group-even:text-white">' . $content . '</div>';
				}

				echo '</div>'; // text wrapper
				echo '</li>';
			}

			echo '</ul>';
		}
		?>

	</div>
</section>