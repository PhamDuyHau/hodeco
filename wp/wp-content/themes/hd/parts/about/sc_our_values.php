<?php
\defined( 'ABSPATH' ) || die;
$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if ( ! $acf_fc_layout ) {
	return;
}
$lists_content = $args['lists_content'] ?? '';
$img           = $args['img'] ?? '';
?>
<section class="section sc-our-values pt-10 pb-10">
	<div class="u-container">
		<?php
		if ( $img ) {
			echo '<img class="w-full h-full rounded-tl-xl rounded-tr-xl" src="' . $img . '" alt="img">';
		}
		if ( ! empty( $lists_content ) ) {
			echo '<div class="wrapper-values flex flex-wrap rounded-bl-xl rounded-br-xl overflow-hidden">';
			$i = 0;
			echo '<div class="tabs-nav w-full xl:w-[28%]">';
			foreach ( $lists_content as $title ) {
				++$i;
				echo '<div class="item group flex items-center justify-between gap-3 mb-[2px] bg-secondary/10 px-4 py-4 xl:px-8 xl:py-5 ' . ( $i === 1 ? 'active' : '' ) . ' [&.active]:bg-secondary cursor-pointer transition-all hover:bg-secondary" data-tab="tab-' . $i . '">';
				echo '<div class="flex items-center gap-3">';
				if ( $title['svg'] ) {
					echo '<div class="icon w-15 h-15 bg-white/50 u-flex-center rounded-full transition-all group-[.active]:bg-white/10 group-hover:bg-white/10">';
					echo wp_get_attachment_image(
						$title['svg'],
						'full',
						false,
						[ 'class' => 'w-8 h-8 object-contain transition-all group-[.active]:brightness-0 group-[.active]:invert group-hover:brightness-0 group-hover:invert' ]
					);
					echo '</div>';
				}
				if ( $title['title'] ) {
					echo '<h2 class="font-body text-xl xl:text-2xl font-medium text[#0A0A0A] transition-all group-[.active]:text-white group-hover:text-white">' . $title['title'] . '</h2>';
				}
				echo '</div>';
				echo '<svg class="size-6 text-white opacity-0 group-[.active]:opacity-100 transition-all group-hover:opacity-100" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 20 20"><g fill="none"><path d="M7.646 4.147a.5.5 0 0 1 .707-.001l5.484 5.465a.55.55 0 0 1 0 .779l-5.484 5.465a.5.5 0 0 1-.706-.708L12.812 10L7.647 4.854a.5.5 0 0 1-.001-.707z" fill="currentColor"></path></g></svg>';
				echo '</div>';
			}
			echo '</div>';
			?>
		<div class="tabs-content relative w-full xl:w-[72%] bg-[var(--color-title)]">
			<?php
				$j = 0;
			foreach ( $lists_content as $value ) :
				++$j;
				$classes = 'main-content transition-all duration-500 ease-in-out opacity-0 invisible absolute top-0 left-0 w-full h-full translate-y-4 z-0';
				if ( $j === 1 ) {
					$classes .= ' active';
				}
				?>
			<div class="<?php echo $classes; ?> [&.active]:opacity-100 [&.active]:visible [&.active]:relative [&.active]:z-[1] [&.active]:translate-y-0"
				id="tab-<?php echo $j; ?>">
				<div class="h-full px-4 py-6 xl:px-16 xl:py-12 text-base xl:text-lg text-white">
					<?php echo $value['content']; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
			<?php
		}
		?>
	</div>
</section>