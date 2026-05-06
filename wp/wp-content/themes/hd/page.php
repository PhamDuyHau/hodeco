<?php
/**
 * The Template for displaying all pages.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;
use HD\Utilities\Helper;

// header
get_header( 'page' );

if ( have_posts() ) {
	the_post();
}
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' ); ?>
<section class="section page-default pt-10 pb-10 xl:pb-15">
	<div class="u-container">
		<div class="max-w-full lg:max-w-[85%] mx-auto">
			<?php the_content(); ?>
		</div>
	</div>
</section>
<?php
// footer
get_footer( 'page' );