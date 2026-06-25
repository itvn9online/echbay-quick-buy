<?php
/**
 * AJAX handlers.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Ajax {

	public static function init() {
		$actions = array( 'eqb_load_popup', 'eqb_create_order', 'eqb_get_wards' );
		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, 'route' ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, 'route' ) );
		}
	}

	public static function route() {
		check_ajax_referer( 'eqb_quick_buy', 'nonce' );

		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';

		if ( 'eqb_load_popup' === $action ) {
			self::load_popup();
		} elseif ( 'eqb_create_order' === $action ) {
			self::create_order();
		} elseif ( 'eqb_get_wards' === $action ) {
			self::get_wards();
		} else {
			wp_send_json_error( array( 'message' => __( 'Hành động không hợp lệ.', 'echbay-quick-buy' ) ), 400 );
		}
	}

	private static function load_popup() {
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Sản phẩm không tồn tại.', 'echbay-quick-buy' ) ), 404 );
		}

		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			wp_send_json_error( array( 'message' => __( 'Sản phẩm không còn hàng.', 'echbay-quick-buy' ) ), 400 );
		}

		if ( $product->is_type( 'variable' ) ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}

		ob_start();
		$options = EQB_Settings::get_all();
		include EQB_PATH . 'templates/popup.php';
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'        => $html,
				'is_variable' => $product->is_type( 'variable' ),
			)
		);
	}

	private static function get_wards() {
		$ma_tinh = isset( $_POST['ma_tinh'] ) ? EQB_Address::sanitize_ma_tinh( wp_unslash( $_POST['ma_tinh'] ) ) : '';

		if ( '' === $ma_tinh ) {
			wp_send_json_error( array( 'message' => __( 'Mã tỉnh không hợp lệ.', 'echbay-quick-buy' ) ), 400 );
		}

		$list = EQB_Address::get_wards_list( $ma_tinh );

		wp_send_json_success( array( 'wards' => $list ) );
	}

	private static function create_order() {
		$honeypot = EQB_Honeypot::validate( $_POST );
		if ( is_wp_error( $honeypot ) ) {
			wp_send_json_error( array( 'message' => $honeypot->get_error_message() ), 400 );
		}

		$data = array(
			'product_id'     => isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
			'variation_id'   => isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0,
			'quantity'       => isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1,
			'name'           => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
			'phone'          => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
			'email'          => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
			'ma_tinh'        => isset( $_POST['ma_tinh'] ) ? wp_unslash( $_POST['ma_tinh'] ) : '',
			'ma_xa'          => isset( $_POST['ma_xa'] ) ? wp_unslash( $_POST['ma_xa'] ) : '',
			'address'        => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '',
			'note'           => isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '',
			'payment_method' => isset( $_POST['payment_method'] ) ? wp_unslash( $_POST['payment_method'] ) : '',
		);

		if ( '1' === EQB_Settings::get( 'debug_order_note', '0' ) ) {
			$data['debug_page_url'] = isset( $_POST['debug_page_url'] ) ? esc_url_raw( wp_unslash( $_POST['debug_page_url'] ) ) : '';
			$data['debug_referrer'] = isset( $_POST['debug_referrer'] ) ? esc_url_raw( wp_unslash( $_POST['debug_referrer'] ) ) : '';
			$data['debug_screen']   = isset( $_POST['debug_screen'] ) ? sanitize_text_field( wp_unslash( $_POST['debug_screen'] ) ) : '';
			$data['debug_timezone'] = isset( $_POST['debug_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['debug_timezone'] ) ) : '';
		}

		$result = EQB_Order::create( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}
}
