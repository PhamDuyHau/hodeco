<?php
/**
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

$object           = get_queried_object();
$breadcrumb_class = '';
$breadcrumb_bg    = \HD_Helper::getThemeMod( 'breadcrumb_bg_setting' );
if ( $breadcrumb_bg ) {
	$breadcrumb_class = ' has-background';
	$breadcrumb_bg    = attachment_url_to_postid( $breadcrumb_bg );
}

$image_for_breadcrumb = \HD_Helper::getField( 'image_for_breadcrumb', $object );
if ( $image_for_breadcrumb ) {
	$breadcrumb_class = ' has-background w-full h-full object-cover';
	$breadcrumb_bg    = $image_for_breadcrumb;
}

$breadcrumb_max = \HD_Helper::getThemeMod( 'breadcrumb_max_height_setting', 0 );
$breadcrumb_min = \HD_Helper::getThemeMod( 'breadcrumb_min_height_setting', 0 );
if ( $breadcrumb_max > 0 || $breadcrumb_min > 0 ) {
	$breadcrumb_class .= ' has-sizes';
}

$title = '';
if ( is_search() ) {
	$title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', TEXT_DOMAIN ), get_search_query() );
	if ( get_query_var( 'paged' ) ) {
		$title .= sprintf( __( '&nbsp;&ndash; page %s', TEXT_DOMAIN ), get_query_var( 'paged' ) );
	}
} elseif ( is_singular() ) {
	$title = get_the_title();
} elseif ( is_archive() ) {
	$title = get_the_archive_title();
}

if ( ! empty( $args['title'] ) ) {
	$title = $args['title'];
}

?>
<section class="section section-breadcrumb<?php echo $breadcrumb_class; ?> relative">
	<?php echo $breadcrumb_bg ? \HD_Helper::pictureHTML( $breadcrumb_bg, 0, 'full', 'breadcrumb-bg' ) : ''; ?>
	<div class="u-container breadcrumb-box">
		<?php if ( ! empty( $title ) ) : ?>
		<p
			class="breadcrumb-title font-header font-bold max-w-full xl:max-w-[95%] mx-auto mb-2 leading-normal text-center text-white text-2xl md:text-3xl xl:text-5xl">
			<?php echo wp_kses_post( $title ); ?></p>
		<?php endif; ?>
		<nav>
			<?php \HD_Helper::breadCrumbs(); ?>
		</nav>
	</div>
</section>