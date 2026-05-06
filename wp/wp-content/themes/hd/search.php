<?php

/**
 * The template for displaying archive.
 *
 * @author Gaudev
 */

use HD\Helper;

\defined( 'ABSPATH' ) || die;

// header
get_header( 'archive' );

$object    = get_queried_object();
$object_id = $object->term_id;

// breadcrumbs
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' );
?>
<section class="section archive archive-post pt-10 pb-10">
	<div class="u-container">
		<div class="category-article">
			<h1
				class="heading-title !hidden capitalize font-header text-[var(--color-title)] text-xl xl:text-4xl font-semibold mb-6">
				<?php
				printf(
					esc_html__( 'Kết quả tìm kiếm cho: %s', 'text-domain' ),
					'<span>' . get_search_query() . '</span>'
				);
				?>
			</h1>
			<?php if ( have_posts() ) : ?>
			<div class="flex flex-wrap xl:flex-nowrap">
				<div class="grid-posts w-full xl:w-[75%] pr-0 xl:pr-7">
					<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 xl:gap-x-5 xl:gap-y-7">
						<?php
						while ( have_posts() ) :
							the_post();
							\HD_Helper::blockTemplate(
								'parts/post/loop',
								[
									'id'        => get_the_ID(),
									'title_tag' => 'h2',
								]
							);
							endwhile;
						?>
					</div>
					<?php \HD_Helper::paginateLinks(); ?>
				</div>
				<aside class="sidebar relative w-full xl:w-[25%]">
					<?php get_sidebar(); ?>
				</aside>
			</div>
			<?php else : ?>
				<?php \HD_Helper::blockTemplate( 'template-blocks/no-results' ); ?>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
// footer
get_footer( 'archive' );