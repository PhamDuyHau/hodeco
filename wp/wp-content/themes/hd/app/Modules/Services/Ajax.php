<?php
namespace HD\App\Modules\Services;

use HD\App\Modules\AbstractModule;
use HD\Core\DB;
use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

final class Ajax extends AbstractModule {
	protected function init(): void {
		// Đăng ký đúng cách cho method trong Class
		add_action( 'wp_ajax_filter_branches', [ $this, 'filter_branches_callback' ] );
		add_action( 'wp_ajax_nopriv_filter_branches', [ $this, 'filter_branches_callback' ] );
	}
	public function filter_branches_callback(): void {
		$term_id = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;
		$args    = [
			'post_type'      => 'branch',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		if ( $term_id > 0 ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'branch-tax',
					'field'    => 'term_id',
					'terms'    => $term_id,
				],
			];
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			$count = 1;
			while ( $query->have_posts() ) :
				$query->the_post();
				$address = get_the_content();
				?>
<div class="branch-item px-1 md:px-3 py-4 border-b border-dashed border-gray-300 last:border-none">
	<div class="flex items-start">
		<span class="font-semibold text-secondary mr-2 xl:mr-3"><?php echo $count; ?>.</span>
		<div>
			<h3 class="font-body font-medium text-lg text-[var(--color-title)]"><?php the_title(); ?></h3>
			<div class="mt-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:list-decimal [&_ol]:pl-5"><?php echo $address; ?></div>
		</div>
	</div>
</div>
				<?php
				++$count;
			endwhile;
		} else {
			echo '<p class="py-4 text-gray-500 italic">' . __( 'Đang cập nhật...', TEXT_DOMAIN ) . '</p>';
		}
		wp_reset_postdata();
		wp_die();
	}
}