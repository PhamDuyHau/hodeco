<?php
/**
 * Trait for handling taxonomy order customization.
 *
 * @package HDAddons\CustomSorting
 */

namespace HDAddons\CustomSorting;

\defined( 'ABSPATH' ) || exit;

trait TaxonomyOrderTrait {

	/**
	 * Filter terms orderby.
	 *
	 * @param string $orderby Orderby string.
	 * @param array $args Query args.
	 *
	 * @return string Modified orderby.
	 */
	public function customOrderGetTermsOrderby( string $orderby, array $args ): string {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $orderby;
		}

		if ( empty( $args['taxonomy'] ) ) {
			return $orderby;
		}

		$taxonomies = $this->orderTaxonomy;
		$taxonomy = is_array( $args['taxonomy'] )
			? ( $args['taxonomy'][0] ?? '' )
			: $args['taxonomy'];

		if ( ! $taxonomy || ! in_array( $taxonomy, $taxonomies, true ) ) {
			return $orderby;
		}

		return 't.term_order';
	}

	// ------------------------------------------------------

	/**
	 * Sort terms by term_order.
	 *
	 * @param array $terms Terms array.
	 *
	 * @return array Sorted terms.
	 */
	public function customOrderGetObjectTerms( array $terms ): array {
		if ( isset( $_GET['orderby'] ) && is_admin() && ! wp_doing_ajax() ) {
			return $terms;
		}

		if ( empty( $terms ) ) {
			return $terms;
		}

		$taxonomies = $this->orderTaxonomy;

		// Check first term's taxonomy
		$firstTerm = reset( $terms );
		if ( ! is_object( $firstTerm ) || ! isset( $firstTerm->taxonomy ) ) {
			return $terms;
		}

		if ( ! in_array( $firstTerm->taxonomy, $taxonomies, true ) ) {
			return $terms;
		}

		// Validate all terms have same taxonomy
		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) || ! isset( $term->taxonomy ) ) {
				return $terms;
			}
		}

		usort( $terms, static fn( $a, $b ) => ( $a->term_order ?? 0 ) <=> ( $b->term_order ?? 0 ) );

		return $terms;
	}
}
