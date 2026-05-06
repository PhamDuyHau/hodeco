<?php
/**
 * The template for displaying `hie`
 * Template Name: Page Tuyển dụng
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
$ACF  = \HD_Helper::getFields( $post->ID );
$name = $ACF['name'] ?? '';
$job  = $ACF['job'] ?? '';
$img  = $ACF['img'] ?? '';
?>
<?php \HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' ); ?>
<section class="section page-hire pt-10 pb-10">
	<div class="u-container">
		

	</div>
</section>
<?php

// footer
get_footer();