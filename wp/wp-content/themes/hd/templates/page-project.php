<?php
/**
 * The template for displaying `project`
 * Template Name: Page Dự án
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
$ACF           = \HD_Helper::getFields( $post->ID );
$heading_title = $ACF['heading_title'] ?? '';
$main_branch   = $ACF['main_branch'] ?? '';
$about_branch  = $ACF['about_branch'] ?? '';
?>
<?php \HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' ); ?>
<section class="section page-project pt-10 pb-10 xl:pb-15">
	<div class="u-container">
	
	</div>
</section>
<?php

// footer
get_footer();