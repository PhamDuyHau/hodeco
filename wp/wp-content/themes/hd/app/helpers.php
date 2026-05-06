<?php
/**
 * Theme Helper Functions
 *
 * Contains reusable utility functions used across templates and core files.
 * Merged from: helpers.php, template-tags.php, translations.php
 *
 * @package HD
 * @author  HD
 */

use HD\Utilities\Helper;

\defined( 'ABSPATH' ) || die;

// --------------------------------------------------
// SVG Functions
// --------------------------------------------------

/**
 * @param string|null $name
 * @param string      $cssClass
 *
 * @return string
 */
function hd_svg( ?string $name, string $cssClass = '' ): string {
	if ( ! $name ) {
		return '';
	}

	if ( empty( $cssClass ) ) {
		$cssClass = 'fill-current';
	}

	// Lazy-load SVG definitions from config file (only when first called)
	static $icons = null;
	$icons      ??= (array) require __DIR__ . '/svg-icons.php';

	if ( empty( $icons[ $name ] ) ) {
		return '';
	}

	// Inject CSS class into the SVG element
	return str_replace( '<svg ', '<svg class="' . esc_attr( $cssClass ) . '" ', $icons[ $name ] );
}

// --------------------------------------------------
// Translation Functions
// --------------------------------------------------

/**
 * Get JavaScript localization strings.
 *
 * @return array Translation strings for JS.
 */
function hd_get_js_translations(): array {
	return [
		// General
		'view_more'     => __( 'Xem thêm', TEXT_DOMAIN ),
		'loading'       => __( 'Đang tải...', TEXT_DOMAIN ),
		'error'         => __( 'Có lỗi xảy ra', TEXT_DOMAIN ),
		'success'       => __( 'Thành công', TEXT_DOMAIN ),
		'confirm'       => __( 'Xác nhận', TEXT_DOMAIN ),
		'cancel'        => __( 'Hủy', TEXT_DOMAIN ),
		'close'         => __( 'Đóng', TEXT_DOMAIN ),
		'search'        => __( 'Tìm kiếm', TEXT_DOMAIN ),
		'no_results'    => __( 'Không tìm thấy kết quả', TEXT_DOMAIN ),

		// Forms
		'required'      => __( 'Trường này là bắt buộc', TEXT_DOMAIN ),
		'invalid_email' => __( 'Email không hợp lệ', TEXT_DOMAIN ),
		'invalid_phone' => __( 'Số điện thoại không hợp lệ', TEXT_DOMAIN ),

		// Share
		'share'         => __( 'Chia sẻ', TEXT_DOMAIN ),
		'copy_link'     => __( 'Sao chép liên kết', TEXT_DOMAIN ),
		'link_copied'   => __( 'Đã sao chép liên kết', TEXT_DOMAIN ),
	];
}

// --------------------------------------------------

/**
 * Get WooCommerce localization strings.
 *
 * @return array WooCommerce translation strings for JS.
 */
function hd_get_wc_translations(): array {
	return [
		'added_to_cart' => __( 'Đã thêm vào giỏ hàng', TEXT_DOMAIN ),
		'view_cart'     => __( 'Xem giỏ hàng', TEXT_DOMAIN ),
		'checkout'      => __( 'Thanh toán', TEXT_DOMAIN ),
		'cart_empty'    => __( 'Giỏ hàng trống', TEXT_DOMAIN ),
		'remove_item'   => __( 'Xóa sản phẩm', TEXT_DOMAIN ),
		'update_cart'   => __( 'Cập nhật giỏ hàng', TEXT_DOMAIN ),
		'cart_updated'  => __( 'Giỏ hàng đã được cập nhật', TEXT_DOMAIN ),
		'out_of_stock'  => __( 'Hết hàng', TEXT_DOMAIN ),
		'add_to_cart'   => __( 'Thêm vào giỏ', TEXT_DOMAIN ),
		'quantity'      => __( 'Số lượng', TEXT_DOMAIN ),
	];
}
