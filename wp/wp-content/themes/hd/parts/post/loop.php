<?php
/**
 * The loop.php file in WordPress handles displaying post's summaries in lists,
 * such as archives or blog pages v.v...
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

$item_id     = $args['id'] ?? 0;
$itemTitle   = $args['title'] ?? get_the_title( $item_id );
$itemTitle   = ! empty( $itemTitle ) ? $itemTitle : __( '(no title)', TEXT_DOMAIN );
$title_tag   = $args['title_tag'] ?? 'p';
$ratio       = $args['ratio'] ?? \HD_Helper::aspectRatioClass( get_post_type( $item_id ) );
$first_class = ! empty( $args['first_class'] ) ? ' ' . $args['first_class'] : '';
$pos         = $args['pos'] ?? 0;

$class = 'w-full object-cover ' . $ratio;

$thumbnail = \HD_Helper::postImageHTML(
	$item_id,
	$pos === 0 ? 'large' : 'medium',
	[
		'alt'   => \HD_Helper::escAttr( $itemTitle ),
		'class' => $class,
	]
);

if ( ! $thumbnail ) {
	$thumbnail = \HD_Helper::placeholderSrc( $class );
}

?>
<div class="item flex flex-col gap-4<?php echo $pos === 0 ? $first_class : ''; ?>">
	<div class="c-cover rounded-lg overflow-hidden relative">
		<a class="block w-full h-full transition-transform duration-500 hover:scale-110"
			href="<?php echo get_permalink( $item_id ); ?>"
			aria-label="<?php echo \HD_Helper::escAttr( $itemTitle ); ?>">
			<?php echo $thumbnail; ?>
		</a>
	</div>
	<div class="c-content flex flex-col gap-2 md:gap-3">
		<?php echo '<a class="block" href="' . get_permalink( $item_id ) . '" title="' . \HD_Helper::escAttr( $itemTitle ) . '"><' . $title_tag . ' class="title font-body text-[var(--color-title)] font-medium p-fs-clamp-[16,20] line-clamp-2 c-hover hover:text-primary">' . $itemTitle . '</' . $title_tag . '></a>'; ?>
		<div class="c-terms flex flex-wrap items-center gap-3">
			<?php
			echo \HD_Helper::getPrimaryTerm(
				[
					'post'          => $item_id,
					'taxonomy'      => 'category',
					'class'         => 'inline-flex items-center justify-center text-sm font-normal text-secondary',
					'wrapper_open'  => null,
					'wrapper_close' => null,
				]
			);
			?>
			<span
				class="c-date text-sm font-normal relative before:content-[''] before:absolute before:top-1/2 before:-left-[6px] before:-translate-y-1/2 before:w-[1px] before:h-[70%] before:bg-[var(--color-title)]/20"><?php echo get_the_time( 'd.m.Y', get_the_ID() ); ?></span>
		</div>
		<?php echo \HD_Helper::loopExcerpt( $item_id, 'excerpt text-[16px] hidden mb-0 line-clamp-2' ); ?>
		<!-- <a href="<?php //echo get_permalink( $item_id ); ?>" class="view-more flex items-center text-[14px] mt-2 text-primary hover:text-primary/80"
			title="<?php //echo esc_attr__( 'Xem chi tiết', TEXT_DOMAIN ); ?>">
			<?php //echo __( 'Chi tiết', TEXT_DOMAIN ); ?>
			<svg class="size-4 ml-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round" stroke-linejoin="round">
				<path d="M5 12h14"/>
				<path d="m12 5 7 7-7 7"/>
			</svg>
		</a> -->
	</div>
</div>