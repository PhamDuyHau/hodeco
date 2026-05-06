<?php
/**
 * The template for displaying archive post.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'archive' );

$object = get_queried_object();
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' );
?>
<section class="section archive archive-post pt-10 pb-10">
	<div class="u-container">
		<div class="latest-article mb-8 xl:mb-10">
			<p
				class="heading-title capitalize font-header text-[var(--color-title)] text-xl xl:text-4xl font-semibold mb-6">
				<?php echo __( 'Tin mới nhất', TEXT_DOMAIN ); ?>
			</p>
			<?php \HD_Helper::blockTemplate( 'parts/post/latest-posts' ); ?>
		</div>
		<div class="category-article">
			<h1
				class="heading-title capitalize font-header text-[var(--color-title)] text-xl xl:text-4xl font-semibold mb-6">
				<?php echo esc_html( get_the_archive_title() ); ?>
			</h1>
			<?php if ( have_posts() ) : ?>
			<div class="flex flex-wrap xl:flex-nowrap">
				<div class="grid-posts w-full xl:w-[75%] pr-0 xl:pr-7">
					<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 xl:gap-x-5 xl:gap-y-7">
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
					<?php
					if ( isset( $object->term_id ) ) {
						echo '<div class="term-description mt-8">';
						echo \HD_Helper::termExcerpt( $object->term_id, 'excerpt', 'div' );
						echo '</div>';
					}
					?>
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