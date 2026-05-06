<?php
/**
 * The template for displaying `about`
 * Template Name: Page About Us
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

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer( 'home' );
	return;
}

$ACF                    = \HD_Helper::getFields( get_the_ID() );
$about_flexible_content = ! empty( $ACF['about_flexible_content'] ) ? (array) $ACF['about_flexible_content'] : false;
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' );
if ( $about_flexible_content ) {
	foreach ( $about_flexible_content as $section ) {
		$acf_fc_layout = $section['acf_fc_layout'] ?? '';

		if ( $acf_fc_layout ) {
			\HD_Helper::blockTemplate( 'parts/about/' . $acf_fc_layout, $section );
		}
	}
} else {
	\HD_Helper::blockTemplate( 'parts/blocks/static-page' );
}
// footer
get_footer();
