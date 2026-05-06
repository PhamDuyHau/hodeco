<?php
/**
 * The template for displaying sidebar.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

/**
 * This file is here to avoid the Deprecated Message for sidebar by wp-includes/theme-compat/sidebar.php.
 */
$sidebar_form     = \HD_Helper::getField( 'sidebar_form', 'option' );
$title_sidebar_vi = $sidebar_form['title_sidebar_vi'];
$title_sidebar_en = $sidebar_form['title_sidebar_en'];
$desc_sidebar_vi  = $sidebar_form['desc_sidebar_vi'];
$desc_sidebar_en  = $sidebar_form['desc_sidebar_en'];
$sl_form_sidebar  = $sidebar_form['sl_form_sidebar'];
$lang             = \HD_Helper::currentLanguage();
?>
<div class="sidebar-inner xl:sticky xl:top-22">
	<div class=" sidebar-category mb-5 xl:mb-6">
		<p class="mb-2"><?php echo __( 'Chuyên mục:', TEXT_DOMAIN ); ?></p>
		<select class="w-full h-11 border border-[#D0D0D0] rounded-lg px-3 cursor-pointer"
			onchange="if (this.value) window.location.href=this.value;">
			<option value=""><?php echo __( 'Tất cả', TEXT_DOMAIN ); ?></option>
			<?php
			$categories = get_categories(
				[
					'taxonomy'   => 'category',
					'hide_empty' => false,
				]
			);
			foreach ( $categories as $cat ) :
				?>
			<option value="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
				<?php echo esc_html( $cat->name ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="sidebar-form px-3 py-5 rounded-xl bg-[#F6F2EA] mb-5 xl:mb-6">
		<?php
		$title_sidebar = $lang === 'en' ? $title_sidebar_en : $title_sidebar_vi;
		$desc_sidebar  = $lang === 'en' ? $desc_sidebar_en : $desc_sidebar_vi;
		if ( $title_sidebar ) :
			?>
		<p class="heading-title capitalize font-header text-xl text-[var(--color-title)] font-semibold">
			<?php echo esc_html( $title_sidebar ); ?>
		</p>
			<?php
		endif;
		if ( $desc_sidebar ) :
			?>
		<div class="desc text-sm mb-3">
			<?php echo $desc_sidebar; ?>
		</div>
			<?php
		endif;
		if ( $sl_form_sidebar ) {
			echo do_shortcode( '[contact-form-7 id="' . esc_attr( $sl_form_sidebar ) . '"]' );
		}
		?>
	</div>
	<div class="sidebar-posts px-3 py-5 rounded-xl bg-[#F6F2EA]">
		<p class="heading-title capitalize font-header text-xl text-[var(--color-title)] font-semibold">
			<?php echo __( 'Bài viết mới nhất', TEXT_DOMAIN ); ?>
		</p>
		<?php
		$post_query = \HD_Helper::queryByLatestPosts(
			[
				'post_type'    => 'post',
				'limit'        => 3,
				'return_query' => false,
			]
		);
		if ( $post_query ) {
			echo '<div class="post-list">';
			foreach ( $post_query as $post_id ) {
				\HD_Helper::blockTemplate(
					'parts/post/loop',
					[
						'id'        => $post_id,
						'title_tag' => 'div',
					]
				);
			}
			echo '</div>';
		}
		?>
	</div>
</div>