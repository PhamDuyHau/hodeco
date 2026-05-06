<?php
/**
 * WooCommerce Integration
 *
 * Handles WooCommerce-related theme integrations such as:
 * - Custom widget registration/unregistration
 * - Frontend scripts and styles
 * - Custom cart fragment refresh logic
 * - OTP-based customer login via email verification
 * - WooCommerce UI adjustments and cleanup
 *
 * @author HD
 */

namespace HD\Plugins\Integrations\WooCommerce;

use HD\Plugins\PluginIntegration;
use HD\Utilities\Helper;
use HD\Utilities\Utils;
use HD\Utilities\Traits\Singleton;
use Random\RandomException;

defined( 'ABSPATH' ) || die;

final class WooCommerce implements PluginIntegration {
	use Singleton;

	/* ---------- STATIC ---------------------------------------- */

	/**
	 * Check if WooCommerce plugin is active.
	 *
	 * @return bool
	 */
	public static function isActive(): bool {
		return Helper::isWoocommerceActive();
	}

	/* ---------- TRANSIENT & META KEYS ----------------------------------- */

	private const string KEY_OTP       = 'wc_loginotp_%d';     // hash (OTP)
	private const string KEY_ATTEMPT   = 'wc_loginotp_try_%d'; // int
	private const string META_LASTSEND = '_wc_otp_last_send';  // timestamp
	private const string META_TOKEN    = '_wc_otp_dnc_token';  // random

	/* ---------- CONFIG -------------------------------------------------- */

	public const int OTP_DIGITS            = 6;
	public const int|float OTP_LIFETIME    = 4 * MINUTE_IN_SECONDS; // 4 minutes (transient and form)
	public const int|float RESEND_INTERVAL = 4 * MINUTE_IN_SECONDS; // 4 minutes (cool-down email)
	public const int|float COOKIE_LIFETIME = DAY_IN_SECONDS; // 1 day
	public const int MAX_ATTEMPTS          = 5;
	public const string ACTION_VALIDATE    = '_wc_otp_validate';

	/* ---------- CONSTRUCT ----------------------------------------------- */

	private function init(): void {
		//-----------------------------------------------------------------
		// Setup
		//-----------------------------------------------------------------

		add_action( 'widgets_init', $this->unregisterDefaultWidgets( ... ), 33 );
		add_action( 'widgets_init', $this->registerWidgets( ... ), 34 );
		add_action( 'after_setup_theme', $this->afterSetupTheme( ... ), 33 );
		add_action( 'wp_enqueue_scripts', $this->enqueueAssets( ... ), 98 );

		add_filter( 'wp_theme_json_data_theme', $this->jsonDataTheme( ... ) );

		//-----------------------------------------------------------------
		// Custom Hooks
		//-----------------------------------------------------------------

		// Remove header from the WooCommerce administrator panel
		add_action(
			'admin_head',
			static function (): void {
				echo '<style>#wpadminbar ~ #wpbody { margin-top: 0 !important; }.woocommerce-layout__header { display: none !important; }</style>';
			}
		);

		add_filter( 'woocommerce_defer_transactional_emails', '__return_true' );
		add_filter( 'woocommerce_product_get_rating_html', $this->getRatingHtml( ... ), 10, 3 );
		add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
		add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
		add_filter( 'woocommerce_product_brands_output', '__return_empty_string' );
		add_filter( 'woocommerce_add_to_cart_fragments', $this->cartFragment( ... ), 11, 1 );
		add_filter( 'woocommerce_widget_cart_item_quantity', $this->wcMiniCartItemQuantity( ... ), 10, 3 );

		add_action( 'wp_ajax_update_mini_cart_qty', $this->wcAjaxUpdateMiniCartQty( ... ) );
		add_action( 'wp_ajax_nopriv_update_mini_cart_qty', $this->wcAjaxUpdateMiniCartQty( ... ) );

		// woocommerce_before_shop_loop
		add_action( 'woocommerce_before_shop_loop', static fn() => print '<div class="woocommerce-shop-info">', 19 );
		add_action( 'woocommerce_before_shop_loop', static fn() => print '</div>', 31 );

		// woocommerce_before_shop_loop_item_title
		add_action( 'woocommerce_before_shop_loop_item_title', static fn() => print '<span class="thumb wc-thumb">', 9 );
		add_action( 'woocommerce_before_shop_loop_item_title', static fn() => print '</span>', 11 );

		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10 );
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );

		add_action( 'woocommerce_before_shop_loop_item', $this->wcTemplateLoopProductLinkOpen( ... ), 10 );

		add_action( 'wp_loaded', $this->processOtpLogin( ... ), 20 );
		add_action( 'wp_loaded', $this->validateOtpLogin( ... ), 21 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * @param mixed $themeJson
	 *
	 * @return mixed
	 */
	public function jsonDataTheme( mixed $themeJson ): mixed {
		$themeJson->update_with(
			[
				'version'  => 1,
				'settings' => [
					'typography' => [
						'fontFamilies' => [ 'theme' => [] ],
					],
				],
			]
		);

		return $themeJson;
	}

	/**
	 * Register WooCommerce widgets.
	 * Add widget classes here when needed.
	 *
	 * @return void
	 */
	public function registerWidgets(): void {
		// Add widget classes here when needed:
		// $widgets = [
		//     Widgets\ProductFilterWidget::class,
		// ];
		// foreach ( $widgets as $widget ) {
		//     class_exists( $widget ) && register_widget( $widget );
		// }
	}

	/**
	 * Unregister a WP_Widget widget
	 *
	 * @return void
	 */
	public function unregisterDefaultWidgets(): void {
		unregister_widget( 'WC_Widget_Product_Search' );
		unregister_widget( 'WC_Widget_Products' );
	}

	/**
	 * @return void
	 */
	public function enqueueAssets(): void {}

	/**
	 * @return void
	 */
	public function afterSetupTheme(): void {
		add_theme_support( 'woocommerce' );
	}

	/**
	 * Cart Fragments, ensure cart contents update when products are added to the cart via AJAX
	 *
	 * @param array $fragments Fragments to refresh via AJAX.
	 *
	 * @return array Fragments to refresh via AJAX
	 */
	public function cartFragment( array $fragments ): array {
		ob_start();
		echo '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
		$fragments['.cart-count'] = ob_get_clean();

		ob_start();
		echo '<div class="mini-cart-dropdown">';
		\woocommerce_mini_cart();
		echo '</div>';
		$fragments['div.mini-cart-dropdown'] = ob_get_clean();

		return $fragments;
	}

	/**
	 * @param mixed $content
	 * @param array $cartItem
	 * @param string $cartItemKey
	 *
	 * @return void
	 */
	public function wcMiniCartItemQuantity( mixed $content, array $cartItem, string $cartItemKey ): void {
		$_product     = apply_filters( 'woocommerce_cart_item_product', $cartItem['data'], $cartItem, $cartItemKey );
		$productPrice = apply_filters( 'woocommerce_cart_item_price', \WC()->cart->get_product_price( $_product ), $cartItem, $cartItemKey );

		?>
		<div class="quantity">
			<span class="price"><?php echo number_format_i18n( $cartItem['quantity'] ) . ' x ' . $productPrice; ?></span>
			<div class="qty-control">
				<button class="minus" type="button">-</button>
				<input type="number" class="qty" name="qty" value="<?php echo $cartItem['quantity']; ?>" min="1" title="qty" />
				<button class="plus" type="button">+</button>
			</div>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	public function wcAjaxUpdateMiniCartQty(): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'update_mini_cart_qty_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Invalid security token.' ] );
		}

		$cartItemKey = \wc_clean( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
		$quantity    = max( 0, (int) ( $_POST['quantity'] ?? 0 ) );

		if ( ! $cartItemKey || ! \WC()->cart->get_cart_item( $cartItemKey ) ) {
			wp_send_json_error( [ 'message' => 'Invalid cart item.' ] );
		}

		\WC()->cart->set_quantity( $cartItemKey, $quantity );
		\WC_AJAX::get_refreshed_fragments();

		die();
	}

	/**
	 * @param string $html
	 * @param float $rating
	 * @param int $count
	 *
	 * @return string
	 */
	public function getRatingHtml( string $html, float $rating, int $count ): string {
		if ( $rating <= 0 ) {
			return '';
		}

		$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );

		return '<div class="loop-stars-rating" role="img" aria-label="' . esc_attr( $label ) . '">'
				. \wc_get_star_rating_html( $rating, $count )
				. '</div>';
	}

	/**
	 * @return void
	 */
	public function validateOtpLogin(): void {
		if (
			empty( $_POST['authcode'] )
			|| empty( $_POST['uid'] )
			|| empty( $_POST['_csrf_token'] )
			|| ! wp_verify_nonce( wp_unslash( $_POST['_csrf_token'] ), 'wc_otp_validate_nonce' )
		) {
			return;
		}

		$customerId = (int) $_POST['uid'];
		$entered    = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['authcode'] ) );

		// Transient data
		$hash     = get_transient( sprintf( self::KEY_OTP, $customerId ) );
		$attempts = (int) get_transient( sprintf( self::KEY_ATTEMPT, $customerId ) );

		try {
			$validationError  = new \WP_Error();
			$validationErrors = $validationError->get_error_messages();

			if ( count( $validationErrors ) === 1 ) {
				throw new \Exception( $validationError->get_error_message() );
			}

			if ( $validationErrors ) {
				foreach ( $validationErrors as $message ) {
					\wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $message, 'error' );
				}
				throw new \Exception();
			}

			if ( $hash === false ) {
				$this->loadOtpForm(
					[
						'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, \wc_get_page_permalink( 'myaccount' ) ) ),
						'template' => 'myaccount/page-otp-login.php',
						'uid'      => $customerId,
						'send_at'  => (int) get_user_meta( $customerId, self::META_LASTSEND, true ),
						'error'    => __( 'Verification code expired – please request a new code.', TEXT_DOMAIN ),
					]
				);
			}

			// Compare
			if ( ! hash_equals( $hash, wp_hash( $entered ) ) ) {
				// +1 failed attempt
				++$attempts;
				set_transient( sprintf( self::KEY_ATTEMPT, $customerId ), $attempts, self::OTP_LIFETIME );

				// Too many attempts?
				if ( $attempts >= self::MAX_ATTEMPTS ) {
					$this->clearOtpData( $customerId );
					throw new \Exception( __( 'Too many incorrect attempts. Please try again later.', TEXT_DOMAIN ) );
				}

				$this->loadOtpForm(
					[
						'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, \wc_get_page_permalink( 'myaccount' ) ) ),
						'template' => 'myaccount/page-otp-login.php',
						'uid'      => $customerId,
						'send_at'  => (int) get_user_meta( $customerId, self::META_LASTSEND, true ),
						'error'    => sprintf( __( 'Invalid code. You have %1$d of %2$d attempts left.', TEXT_DOMAIN ), self::MAX_ATTEMPTS - $attempts, self::MAX_ATTEMPTS ),
					]
				);
			}

			// Log the user in and redirect
			\wc_set_customer_auth_cookie( $customerId );

			$redirect = ! empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : \wc_get_page_permalink( 'myaccount' );
			wp_safe_redirect( esc_url_raw( wp_unslash( $redirect ) ) );
			exit;

		} catch ( \Exception $e ) {
			if ( $e->getMessage() ) {
				\wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * @return void
	 */
	public function processOtpLogin(): void {
		if (
			empty( $_POST['otp_register'] )
			|| empty( $_POST['email'] )
			|| empty( $_POST['_csrf_token'] )
			|| ! wp_verify_nonce( wp_unslash( $_POST['_csrf_token'] ), 'wc_otp_register_nonce' )
		) {
			return;
		}

		$username = '';
		$password = '';
		$email    = wp_unslash( $_POST['email'] );

		try {
			$validationError  = new \WP_Error();
			$validationErrors = $validationError->get_error_messages();

			if ( count( $validationErrors ) === 1 ) {
				throw new \Exception( $validationError->get_error_message() );
			}

			if ( $validationErrors ) {
				foreach ( $validationErrors as $message ) {
					\wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $message, 'error' );
				}
				throw new \Exception();
			}

			$newCustomer = $this->createNewCustomer( sanitize_email( $email ), \wc_clean( $username ), $password );

			if ( is_wp_error( $newCustomer ) ) {
				throw new \Exception( $newCustomer->get_error_message() );
			}

			// Send Email (respects cool-down)
			$result = $this->maybeSendOtpEmail( $newCustomer, $email );
			if ( $result === false ) {
				$this->clearOtpData( $newCustomer );
				throw new \Exception( __( 'OTP could not be sent.', TEXT_DOMAIN ) );
			}

			// Show an OTP form
			$this->loadOtpForm(
				[
					'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, \wc_get_page_permalink( 'myaccount' ) ) ),
					'template' => 'myaccount/page-otp-login.php',
					'uid'      => $newCustomer,
					'send_at'  => (int) get_user_meta( $newCustomer, self::META_LASTSEND, true ),
				]
			);

		} catch ( \Exception $e ) {
			if ( $e->getMessage() ) {
				\wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e->getMessage(), 'error' );
			}
		}
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * @param array $args
	 *
	 * @return void
	 */
	private function loadOtpForm( array $args ): void {
		if ( empty( $args['template'] ) ) {
			return;
		}

		$args = [
			...$args,
			'otp_digits'      => self::OTP_DIGITS,
			'resend_interval' => self::RESEND_INTERVAL,
			'redirect_to'     => \wc_get_page_permalink( 'myaccount' ),
		];

		if ( ! empty( $args['error'] ) ) {
			\wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $args['error'], 'error' );
		}

		\wc_get_template( $args['template'], $args );
		exit();
	}

	/**
	 * @param int $customerId
	 *
	 * @return void
	 */
	private function clearOtpData( int $customerId = 0 ): void {
		delete_transient( sprintf( self::KEY_OTP, $customerId ) );
		delete_transient( sprintf( self::KEY_ATTEMPT, $customerId ) );
		delete_user_meta( $customerId, self::META_LASTSEND );
		delete_user_meta( $customerId, self::META_TOKEN );
	}

	/**
	 * @param int $customerId
	 * @param string $email
	 *
	 * @return bool|null
	 * @throws RandomException
	 */
	private function maybeSendOtpEmail( int $customerId, string $email ): ?bool {
		$lastSent = (int) get_user_meta( $customerId, self::META_LASTSEND, true );
		if ( $lastSent && ( time() - $lastSent ) < self::RESEND_INTERVAL ) {
			return null;
		}

		// generate OTP
		$otp = str_pad( (string) random_int( 0, ( 10 ** self::OTP_DIGITS ) - 1 ), self::OTP_DIGITS, '0', STR_PAD_LEFT );

		$sent = \wp_mail(
			$email,
			__( 'Your One-Time OTP', TEXT_DOMAIN ),
			sprintf(
				__( "Hello %1\$s,\n\nYour OTP is: %2\$s\nThis code will expire in %3\$s minutes.\n\nIf you didn't request this login, please ignore this email.", TEXT_DOMAIN ),
				$email,
				$otp,
				self::OTP_LIFETIME / MINUTE_IN_SECONDS,
			)
		);

		if ( ! $sent ) {
			return false;
		}

		// Success → store cool-down and transients
		update_user_meta( $customerId, self::META_LASTSEND, time() );
		set_transient( sprintf( self::KEY_OTP, $customerId ), wp_hash( $otp ), self::OTP_LIFETIME );
		set_transient( sprintf( self::KEY_ATTEMPT, $customerId ), 0, self::OTP_LIFETIME );

		return true;
	}

	/**
	 * @param string $email
	 * @param string $username
	 * @param string $password
	 * @param array $args
	 *
	 * @return int|\WP_Error
	 * @throws RandomException
	 */
	private function createNewCustomer( string $email, string $username = '', string $password = '', array $args = [] ): \WP_Error|int {
		if ( ! $email || ! is_email( $email ) ) {
			return new \WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'woocommerce' ) );
		}

		// return customer ID if exists
		$customerId = email_exists( $email );
		if ( $customerId ) {
			return $customerId;
		}

		if ( ! $username ) {
			$username = \wc_create_new_customer_username( $email, $args );
		}

		$username = sanitize_user( $username );
		if ( ! $username || ! validate_username( $username ) ) {
			$username = Utils::makeUsername( 8 );
		}

		if ( username_exists( $username ) ) {
			return new \WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'woocommerce' ) );
		}

		// Handle password creation.
		$passwordGenerated = false;

		if ( ! $password ) {
			$password          = \wp_generate_password();
			$passwordGenerated = true;
		}

		if ( ! $password ) {
			return new \WP_Error( 'registration-error-missing-password', __( 'Please create a password for your account.', 'woocommerce' ) );
		}

		// Use WP_Error to handle registration errors.
		$errors = new \WP_Error();

		/**
		 * Fires before a customer account is registered.
		 */
		do_action( 'woocommerce_register_post', $username, $email, $errors );

		/**
		 * Filters registration errors before a customer account is registered.
		 */
		$errors = apply_filters( 'woocommerce_registration_errors', $errors, $username, $email );

		if ( is_wp_error( $errors ) && $errors->get_error_code() ) {
			return $errors;
		}

		// Merged passed args with sanitized username, email, and password.
		$customerData = [
			...$args,
			'user_login' => $username,
			'user_pass'  => $password,
			'user_email' => $email,
			'role'       => 'customer',
		];

		/**
		 * Filters customer data before a customer account is registered.
		 */
		$newCustomerData = apply_filters(
			'woocommerce_new_customer_data',
			wp_parse_args(
				$customerData,
				[
					'first_name' => '',
					'last_name'  => '',
					'source'     => 'unknown',
				]
			)
		);

		$customerId = wp_insert_user( $newCustomerData );

		if ( is_wp_error( $customerId ) ) {
			return $customerId;
		}

		// Set account flag to remind customer to update generated password.
		if ( $passwordGenerated ) {
			update_user_option( $customerId, 'default_password_nag', true, true );
		}

		/**
		 * Fires after a customer account has been registered.
		 */
		do_action( 'woocommerce_created_customer', $customerId, $newCustomerData, $passwordGenerated );

		return $customerId;
	}

	/**
	 * @return void
	 */
	public function wcTemplateLoopProductLinkOpen(): void {
		global $product;

		if ( ! ( $product instanceof \WC_Product ) ) {
			return;
		}

		$link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );

		echo '<a href="' . esc_url( $link ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link" title="' . esc_attr( $product->get_title() ) . '">';
	}
}
