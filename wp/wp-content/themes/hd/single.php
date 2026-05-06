<?php
/**
 * The Template for displaying all single posts.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @package HD
 * @author  HD
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'single' );

if ( have_posts() ) {
	the_post();
}

$ACF = \HD_Helper::getFields( $post->ID );
// breadcrumb
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' );
?>
<section class="section singular section-post pt-10 xl:pt-12 pb-10 xl:pb-20">
	<div class="u-container">
		<div class="flex flex-wrap">
			<div class="col-content w-full xl:w-[75%] pr-0 xl:pr-7">
				<h1 class="font-body font-medium text-lg xl:text-2xl text-[var(--color-title)] mb-5"
					<?php echo \HD_Helper::microdata( 'headline' ); ?>>
					<?php the_title(); ?>
				</h1>
				<div class="_meta flex flex-wrap items-center justify-between max-md:gap-3 mb-5">
					<div class="_meta-wrap flex flex-wrap items-center">
						<div
							class="_author leading-snug pr-3 mr-3 flex flex-x items-center gap-3 border-r border-[#A4A8AB] text-[var(--color-title)]">
							<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
								xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 448 512">
								<path
									d="M313.6 304c-28.7 0-42.5 16-89.6 16c-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4c14.6 0 38.3 16 89.6 16c51.7 0 74.9-16 89.6-16c47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0S80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96s-96-43.1-96-96s43.1-96 96-96z"
									fill="currentColor"></path>
							</svg>
							<span class="text-[#828C83]"><?php echo get_the_author(); ?></span>
						</div>
						<div class="_date flex flex-x items-center gap-3 text-[var(--color-title)]">
							<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
								xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">
								<path
									d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8s8 3.58 8 8s-3.58 8-8 8zm-.22-13h-.06c-.4 0-.72.32-.72.72v4.72c0 .35.18.68.49.86l4.15 2.49c.34.2.78.1.98-.24a.71.71 0 0 0-.25-.99l-3.87-2.3V7.72c0-.4-.32-.72-.72-.72z"
									fill="currentColor"></path>
							</svg>
							<span class="text-[#5C5C5C]"><?php echo get_the_date( 'H:i - d/m/Y' ); ?></span>
						</div>
					</div>
					<?php \HD_Helper::blockTemplate( 'parts/blocks/social-share' ); ?>
				</div>
				<article class="entry-content" <?php echo \HD_Helper::microdata( 'article' ); ?>>
					<?php
					echo \HD_Helper::postExcerpt( $post, 'excerpt', 'div', false );
					the_content();
					echo '<div class="entry-extra mt-5">';
					\HD_Helper::blockTemplate( 'parts/post/suggestion-posts' );
					// echo \HD_Helper::postTerms( $post );
					\HD_Helper::hashTags();
					echo \HD_Helper::doShortcode( 'block_fixed_info' );
					comments_template();
					\HD_Helper::blockTemplate( 'parts/blocks/author' );
					echo '</div>';
					?>
					<!-- js hiển thị tên & email khi nhập ô bình luận -->
					<script>
					jQuery(document).ready(function($) {
						let $commentField = $("#comment");
						let $authorField = $(".comment-form-author");
						let $emailField = $(".comment-form-email");
						let $buttonField = $(".comment-form-button");

						if ($commentField.length) {
							$commentField.on("input", function() {
								if ($(this).val().trim() !== "") {
									$authorField.removeClass("hidden");
									$emailField.removeClass("hidden");
									$buttonField.removeClass("hidden");
								} else {
									$authorField.addClass("hidden");
									$emailField.addClass("hidden");
									$buttonField.addClass("hidden");
								}
							});
						}
					});
					</script>
				</article>
			</div>
			<aside class="sidebar relative w-full xl:w-[25%]">
				<?php get_sidebar(); ?>
			</aside>
		</div>

	</div>
</section>
<?php
// footer
get_footer( 'single' );