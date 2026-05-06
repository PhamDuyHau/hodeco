<?php
/**
 * The latest-posts.php file in WordPress is responsible for displaying the latest posts as a list,
 * such as archives or blog pages v.v...
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

$latest_query = \HD_Helper::queryByLatestPosts(
	[
		'post_type'    => 'post',
		'limit'        => 5,
		'return_query' => true,
	]
);

if ( $latest_query && $latest_query->have_posts() ) :
	$latests     = $latest_query->posts;
	$left_posts  = array_slice( $latests, 0, 1 );
	$right_posts = array_slice( $latests, 1, 4 );
	?>
<div class="post-grid grid grid-cols-1 md:grid-cols-2 gap-5">
	<div class="post-grid__left">
		<?php
		foreach ( $left_posts as $latest ) :
			setup_postdata( $latest );
			\HD_Helper::blockTemplate(
				'parts/post/loop',
				[
					'id'        => $latest->ID,
					'title_tag' => 'h2',
				]
			);
			endforeach;
		?>
	</div>
	<div class="post-grid__right grid grid-cols-1 sm:grid-cols-2 gap-5">
		<?php
		foreach ( $right_posts as $latest ) :
			setup_postdata( $latest );
			\HD_Helper::blockTemplate(
				'parts/post/loop',
				[
					'id'        => $latest->ID,
					'title_tag' => 'h2',
				]
			);
			endforeach;
		?>
	</div>
</div>
	<?php
	wp_reset_postdata();
else :
	echo '<p>' . __( 'Không tìm thấy bài viết!', TEXT_DOMAIN ) . '</p>';
endif;
?>