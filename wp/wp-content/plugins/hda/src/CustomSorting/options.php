<?php
/**
 * Custom Sorting module options panel.
 */

use HDAddons\CustomSorting\CustomSorting;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$custom_sorting_options = Helper::getOption( CustomSorting::OPTION_NAME, [] );
$order_post_type        = $custom_sorting_options[ CustomSorting::KEY_ORDER_POST_TYPE ] ?? [];
$order_taxonomy         = $custom_sorting_options[ CustomSorting::KEY_ORDER_TAXONOMY ] ?? [];

?>
<div class="container">
	<input type="hidden" name="custom-sorting-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Custom Sorting', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-sort"></span>
				<?php esc_html_e( 'Enable drag-and-drop sorting for posts and taxonomy terms. After enabling, go to the post type list or taxonomy page to reorder items.', HDA_TEXTDOMAIN ); ?>
			</p>
			<p class="hda-notice__detail">
				<?php esc_html_e( 'Custom order is applied on frontend queries automatically (menu_order for posts, term_order for taxonomies).', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<div class="cell section section-checkbox">
				<span class="heading"><?php esc_html_e( 'Check to Sort Post Types', HDA_TEXTDOMAIN ); ?></span>
				<div class="desc"><?php echo wp_kses_post( __( 'Enabled post types get a drag-and-drop sorter in the admin list. Order is saved to <code>menu_order</code>.', HDA_TEXTDOMAIN ) ); ?></div>
		<?php
		$post_types        = get_post_types( [ 'show_ui' => true ], 'objects' );
		$exclude_post_type = [
			'attachment',
			'wp_navigation',
			'product',
		];

		if ( ! current_user_can( \HDAddons\Plugin::CAPABILITY ) ) {
			array_push( $exclude_post_type, 'acf-taxonomy', 'acf-post-type', 'acf-ui-options-page', 'acf-field-group' );
		}

		foreach ( $post_types as $post_type ) :
			if ( in_array( $post_type->name, $exclude_post_type, true ) ) {
				continue;
			}

			$label = esc_html( $post_type->label );
			if ( str_starts_with( $post_type->name, 'shop_' ) ) {
				$label = 'WooCommerce ' . $label;
			}
			if ( str_starts_with( $post_type->name, 'acf-' ) ) {
				$label = 'ACF ' . $label;
			}
			$label .= ' <span>(' . esc_html( $post_type->name ) . ')</span>';
			?>
		<div class="option">
			<label class="controls">
				<input type="checkbox" class="checkbox" name="order_post_type[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php Helper::inArrayChecked( $order_post_type, $post_type->name ); ?>>
			</label>
			<div class="explain"><?php echo wp_kses_post( $label ); ?></div>
		</div>
		<?php endforeach; ?>
			</div>

			<div class="cell section section-checkbox">
				<span class="heading"><?php esc_html_e( 'Check to Sort Taxonomies', HDA_TEXTDOMAIN ); ?></span>
				<div class="desc"><?php echo wp_kses_post( __( 'Enabled taxonomies get a drag-and-drop sorter on the term list page. Order is saved to <code>term_order</code>.', HDA_TEXTDOMAIN ) ); ?></div>
		<?php
		$taxonomies       = get_taxonomies( [ 'show_ui' => true ], 'objects' );
		$exclude_taxonomy = [
			'link_category',
			'wp_pattern_category',
			'product_cat',
			'product_brand',
		];

		foreach ( $taxonomies as $taxonomy ) :
			if ( in_array( $taxonomy->name, $exclude_taxonomy, true ) ) {
				continue;
			}

			$label  = esc_html( $taxonomy->label );
			$label .= ' <span>(' . esc_html( $taxonomy->name ) . ')</span>';
			?>
		<div class="option">
			<label class="controls">
				<input type="checkbox" class="checkbox" name="order_taxonomy[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php Helper::inArrayChecked( $order_taxonomy, $taxonomy->name ); ?>>
			</label>
			<div class="explain"><?php echo wp_kses_post( $label ); ?></div>
		</div>
		<?php endforeach; ?>
			</div>

			<div class="cell section section-checkbox" style="grid-column:1/-1;">
				<span class="heading"><?php esc_html_e( 'Check to reset order', HDA_TEXTDOMAIN ); ?></span>
				<div class="option">
					<label class="controls">
						<input type="checkbox" class="checkbox" name="order_reset" id="order_reset" value="1">
					</label>
					<div class="explain"><?php esc_html_e( 'Reset all', HDA_TEXTDOMAIN ); ?></div>
				</div>
			</div>
		</div>
	</fieldset>
</div>
