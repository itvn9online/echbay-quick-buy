<?php
/**
 * Create WooCommerce orders from quick buy form.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Order {

	/**
	 * @return array<string, WC_Payment_Gateway>
	 */
	public static function get_payment_gateways() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		$available = WC()->payment_gateways()->get_available_payment_gateways();
		if ( ! empty( $available ) ) {
			return $available;
		}

		$all = WC()->payment_gateways()->payment_gateways();
		$out = array();

		if ( is_array( $all ) ) {
			foreach ( $all as $id => $gateway ) {
				if ( is_object( $gateway ) && 'yes' === $gateway->enabled ) {
					$out[ $id ] = $gateway;
				}
			}
		}

		return $out;
	}

	/**
	 * @param array $data Form data.
	 * @return array|WP_Error
	 */
	public static function create( $data ) {
		$name            = isset( $data['name'] ) ? sanitize_text_field( wp_unslash( $data['name'] ) ) : '';
		$phone           = isset( $data['phone'] ) ? sanitize_text_field( wp_unslash( $data['phone'] ) ) : '';
		$email           = isset( $data['email'] ) ? sanitize_email( wp_unslash( $data['email'] ) ) : '';
		$note            = isset( $data['note'] ) ? sanitize_textarea_field( wp_unslash( $data['note'] ) ) : '';
		$address_street  = isset( $data['address'] ) ? sanitize_text_field( wp_unslash( $data['address'] ) ) : '';
		$ma_tinh         = isset( $data['ma_tinh'] ) ? EQB_Address::sanitize_ma_tinh( wp_unslash( $data['ma_tinh'] ) ) : '';
		$ma_xa           = isset( $data['ma_xa'] ) ? sanitize_text_field( wp_unslash( $data['ma_xa'] ) ) : '';
		$payment_method  = isset( $data['payment_method'] ) ? sanitize_text_field( wp_unslash( $data['payment_method'] ) ) : 'cod';
		$qty             = isset( $data['quantity'] ) ? absint( $data['quantity'] ) : 1;
		$product_id      = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
		$variation_id    = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : 0;

		if ( $qty < 1 ) {
			$qty = 1;
		}

		if ( '' === $name || '' === $phone ) {
			return new WP_Error( 'eqb_required', __( 'Họ tên và số điện thoại là bắt buộc.', 'echbay-quick-buy' ) );
		}

		if ( ! preg_match( '/^(0|\+84)[0-9]{9,10}$/', $phone ) ) {
			return new WP_Error( 'eqb_phone', __( 'Số điện thoại không hợp lệ.', 'echbay-quick-buy' ) );
		}

		if ( ! EQB_Settings::is_email_optional() && '' === trim( $email ) ) {
			return new WP_Error( 'eqb_email', __( 'Vui lòng nhập địa chỉ email.', 'echbay-quick-buy' ) );
		}

		if ( '' === $ma_tinh || '' === $ma_xa ) {
			return new WP_Error( 'eqb_address', __( 'Vui lòng chọn Tỉnh/Thành phố và Phường/Xã.', 'echbay-quick-buy' ) );
		}

		$provinces = EQB_Address::get_provinces();
		if ( ! isset( $provinces[ $ma_tinh ] ) ) {
			return new WP_Error( 'eqb_province', __( 'Tỉnh/Thành phố không hợp lệ.', 'echbay-quick-buy' ) );
		}

		$wards = EQB_Address::get_wards( $ma_tinh );
		if ( ! isset( $wards[ $ma_xa ] ) ) {
			return new WP_Error( 'eqb_ward', __( 'Phường/Xã không hợp lệ.', 'echbay-quick-buy' ) );
		}

		if ( ! EQB_Settings::is_address_optional() && '' === trim( $address_street ) ) {
			return new WP_Error( 'eqb_street', __( 'Vui lòng nhập số nhà, tên đường.', 'echbay-quick-buy' ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'eqb_product', __( 'Sản phẩm không tồn tại.', 'echbay-quick-buy' ) );
		}

		if ( $product->is_type( 'variable' ) ) {
			if ( $variation_id <= 0 ) {
				return new WP_Error( 'eqb_variation', __( 'Vui lòng chọn phân loại sản phẩm.', 'echbay-quick-buy' ) );
			}
			$product = wc_get_product( $variation_id );
			if ( ! $product || ! $product->is_type( 'variation' ) ) {
				return new WP_Error( 'eqb_variation', __( 'Biến thể sản phẩm không hợp lệ.', 'echbay-quick-buy' ) );
			}
			if ( (int) $product->get_parent_id() !== $product_id ) {
				return new WP_Error( 'eqb_variation', __( 'Biến thể không thuộc sản phẩm này.', 'echbay-quick-buy' ) );
			}
		}

		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return new WP_Error( 'eqb_stock', __( 'Sản phẩm không còn hàng.', 'echbay-quick-buy' ) );
		}

		if ( $product->managing_stock() && null !== $product->get_stock_quantity() && $product->get_stock_quantity() < $qty ) {
			return new WP_Error( 'eqb_stock_qty', __( 'Số lượng vượt quá tồn kho.', 'echbay-quick-buy' ) );
		}

		$gateways = self::get_payment_gateways();
		if ( empty( $gateways ) ) {
			$payment_method = 'cod';
		} elseif ( ! isset( $gateways[ $payment_method ] ) ) {
			return new WP_Error( 'eqb_payment', __( 'Phương thức thanh toán không hợp lệ.', 'echbay-quick-buy' ) );
		}

		try {
			$order = wc_create_order();
			if ( is_wp_error( $order ) ) {
				return $order;
			}

			$cancel_as_spam = self::is_missing_street_address( $address_street );

			$order->add_product( $product, $qty );

			$order->set_billing_first_name( $name );
			$order->set_billing_phone( $phone );
			$order->set_billing_email( $email );
			$order->set_billing_address_1( $address_street );
			$order->set_billing_city( $ma_xa );
			$order->set_billing_state( $ma_tinh );
			$order->set_billing_country( 'VN' );

			$order->set_shipping_first_name( $name );
			$order->set_shipping_address_1( $address_street );
			$order->set_shipping_city( $ma_xa );
			$order->set_shipping_state( $ma_tinh );
			$order->set_shipping_country( 'VN' );

			if ( '' !== $note ) {
				$order->set_customer_note( $note );
			}

			$gateway_title = __( 'Thanh toán khi nhận hàng (COD)', 'echbay-quick-buy' );
			if ( isset( $gateways[ $payment_method ] ) ) {
				$gateway_title = $gateways[ $payment_method ]->get_title();
			}

			$order->set_payment_method( $payment_method );
			$order->set_payment_method_title( $gateway_title );

			$order->calculate_totals();

			$status = $cancel_as_spam ? 'cancelled' : self::resolve_order_status( $payment_method );
			$order->set_status( $status );
			$order->save();

			$order->add_order_note(
				__( 'Đơn hàng từ plugin Echbay Quick Buy.', 'echbay-quick-buy' )
			);

			if ( $cancel_as_spam ) {
				$order->add_order_note(
					__( '[EQB] Đơn không có địa chỉ nhà — tự động hủy (nghi spam).', 'echbay-quick-buy' ),
					false
				);
			}

			self::maybe_add_debug_order_note( $order, $data );

			if ( is_user_logged_in() ) {
				$order->set_customer_id( get_current_user_id() );
				$order->save();
			}

			if ( ! $cancel_as_spam ) {
				wc_reduce_stock_levels( $order->get_id() );

				do_action( 'woocommerce_checkout_order_processed', $order->get_id(), array(), $order );
			}

			do_action( 'eqb_order_created', $order, $data );

			$redirect = '';
			if ( ! $cancel_as_spam && '1' === EQB_Settings::get( 'redirect_thankyou', '1' ) ) {
				$redirect = $order->get_checkout_order_received_url();
			}

			if ( ! $cancel_as_spam && isset( $gateways[ $payment_method ] ) && is_callable( array( $gateways[ $payment_method ], 'process_payment' ) ) ) {
				$pay_result = $gateways[ $payment_method ]->process_payment( $order->get_id() );
				if ( is_array( $pay_result ) && ! empty( $pay_result['redirect'] ) ) {
					$redirect = $pay_result['redirect'];
				}
			}

			$message = str_replace(
				'%order_id%',
				(string) $order->get_id(),
				EQB_Settings::get( 'success_message' )
			);

			return array(
				'order_id'     => $order->get_id(),
				'message'      => $message,
				'redirect_url' => $redirect,
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'eqb_failed', EQB_Settings::get( 'error_message' ) );
		}
	}

	private static function resolve_order_status( $payment_method ) {
		$cod_like = array( 'cod', 'bacs', 'cheque' );
		if ( in_array( $payment_method, $cod_like, true ) ) {
			return 'processing';
		}
		return 'on-hold';
	}

	/**
	 * @param string $address_street Street address from form.
	 */
	private static function is_missing_street_address( $address_street ) {
		return '' === trim( $address_street );
	}

	/**
	 * @param WC_Order $order
	 * @param array    $data
	 */
	private static function maybe_add_debug_order_note( $order, $data ) {
		if ( '1' !== EQB_Settings::get( 'debug_order_note', '0' ) ) {
			return;
		}

		$note = self::build_debug_order_note( $data );
		if ( '' !== $note ) {
			$order->add_order_note( $note );
		}
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private static function build_debug_order_note( $data ) {
		$lines   = array( '[EQB Debug]' );
		$lines[] = 'IP: ' . self::get_client_ip();
		$lines[] = 'User-Agent: ' . self::get_server_value( 'HTTP_USER_AGENT' );

		$page_url = isset( $data['debug_page_url'] ) ? $data['debug_page_url'] : '';
		if ( '' === $page_url ) {
			$page_url = self::get_server_value( 'HTTP_REFERER' );
		}
		$lines[] = 'URL trang đặt hàng: ' . ( $page_url ? $page_url : '(không có)' );

		$referrer = isset( $data['debug_referrer'] ) ? $data['debug_referrer'] : '';
		$lines[]    = 'Referrer (client): ' . ( $referrer ? $referrer : '(không có)' );

		$server_referer = self::get_server_value( 'HTTP_REFERER' );
		if ( $server_referer && $server_referer !== $referrer ) {
			$lines[] = 'Referrer (server): ' . $server_referer;
		}

		$lang = self::get_server_value( 'HTTP_ACCEPT_LANGUAGE' );
		$lines[] = 'Ngôn ngữ trình duyệt: ' . ( $lang ? $lang : '(không có)' );

		if ( is_user_logged_in() ) {
			$lines[] = 'Đăng nhập: có (user ID ' . get_current_user_id() . ')';
		} else {
			$lines[] = 'Đăng nhập: không (khách)';
		}

		$lines[] = 'Thời gian server: ' . wp_date( 'Y-m-d H:i:s T' );

		$screen = isset( $data['debug_screen'] ) ? $data['debug_screen'] : '';
		if ( $screen ) {
			$lines[] = 'Màn hình: ' . $screen;
		}

		$timezone = isset( $data['debug_timezone'] ) ? $data['debug_timezone'] : '';
		if ( $timezone ) {
			$lines[] = 'Timezone client: ' . $timezone;
		}

		$request_uri = self::get_server_value( 'REQUEST_URI' );
		if ( $request_uri ) {
			$lines[] = 'Request URI: ' . $request_uri;
		}

		return implode( "\n", $lines );
	}

	/**
	 * @return string
	 */
	private static function get_client_ip() {
		if ( class_exists( 'WC_Geolocation' ) ) {
			$ip = WC_Geolocation::get_ip_address();
			if ( $ip ) {
				return sanitize_text_field( $ip );
			}
		}

		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}
			$raw = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			if ( strpos( $raw, ',' ) !== false ) {
				$parts = explode( ',', $raw );
				$raw   = trim( $parts[0] );
			}
			if ( $raw ) {
				return $raw;
			}
		}

		return '(không xác định)';
	}

	/**
	 * @param string $key
	 * @return string
	 */
	private static function get_server_value( $key ) {
		if ( empty( $_SERVER[ $key ] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
	}
}
