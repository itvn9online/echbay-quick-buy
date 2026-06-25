<?php
/**
 * WooCommerce admin order: Tỉnh/TP + Phường/Xã dạng select.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Admin_Order {

	public static function init() {
		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'vn_address_format' ) );
		add_filter( 'woocommerce_admin_billing_fields', array( __CLASS__, 'billing_fields' ), 20, 3 );
		add_filter( 'woocommerce_admin_shipping_fields', array( __CLASS__, 'shipping_fields' ), 20, 3 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( __CLASS__, 'format_billing_address' ), 20, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'format_shipping_address' ), 20, 2 );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_eqb_admin_get_wards', array( __CLASS__, 'ajax_get_wards' ) );
	}

	/**
	 * @param array          $fields  Address fields.
	 * @param WC_Order|false $order   Order object.
	 * @param string         $context edit|view.
	 */
	public static function billing_fields( $fields, $order = false, $context = 'edit' ) {
		return self::filter_address_fields( $fields, $order, $context, 'billing' );
	}

	/**
	 * @param array          $fields  Address fields.
	 * @param WC_Order|false $order   Order object.
	 * @param string         $context edit|view.
	 */
	public static function shipping_fields( $fields, $order = false, $context = 'edit' ) {
		return self::filter_address_fields( $fields, $order, $context, 'shipping' );
	}

	/**
	 * @param array          $fields  Address fields.
	 * @param WC_Order|false $order   Order object.
	 * @param string         $context edit|view.
	 * @param string         $type    billing|shipping.
	 */
	private static function filter_address_fields( $fields, $order, $context, $type ) {
		if ( ! EQB_Install::is_installed() ) {
			return $fields;
		}

		if ( 'view' === $context ) {
			return self::view_address_fields( $fields, $order, $type );
		}

		if ( ! self::should_use_vn_fields( $order, $type ) ) {
			return $fields;
		}

		$ma_tinh = $order ? (string) $order->{'get_' . $type . '_state'}() : '';
		$ma_xa   = $order ? (string) $order->{'get_' . $type . '_city'}() : '';

		$fields['state'] = array(
			'label' => __( 'Tỉnh/Thành phố', 'echbay-quick-buy' ),
			'show'  => false,
			'class' => 'js_field-state select short eqb-admin-province',
		);

		$fields['city'] = array(
			'label'   => __( 'Phường/Xã', 'echbay-quick-buy' ),
			'show'    => false,
			'class'   => 'js_field-city select short eqb-admin-ward',
			'type'    => 'select',
			'options' => self::get_ward_options( $ma_tinh, $ma_xa ),
		);

		return self::reorder_state_before_city( $fields );
	}

	/**
	 * Hiển thị tên thay vì mã ở chế độ xem.
	 *
	 * @param array          $fields Address fields.
	 * @param WC_Order|false $order  Order object.
	 * @param string         $type   billing|shipping.
	 */
	private static function view_address_fields( $fields, $order, $type ) {
		if ( ! $order || ! self::should_use_vn_fields( $order, $type ) ) {
			return $fields;
		}

		$ma_tinh = (string) $order->{'get_' . $type . '_state'}();
		$ma_xa   = (string) $order->{'get_' . $type . '_city'}();

		if ( '' !== $ma_tinh && isset( $fields['state'] ) ) {
			$fields['state']['value'] = self::get_province_name( $ma_tinh );
		}

		if ( '' !== $ma_xa && isset( $fields['city'] ) ) {
			$fields['city']['value'] = self::get_ward_name( $ma_tinh, $ma_xa );
		}

		return $fields;
	}

	/**
	 * @param array    $address Address parts.
	 * @param WC_Order $order   Order object.
	 */
	public static function format_billing_address( $address, $order ) {
		return self::format_address_names( $address, $order, 'billing' );
	}

	/**
	 * @param array    $address Address parts.
	 * @param WC_Order $order   Order object.
	 */
	public static function format_shipping_address( $address, $order ) {
		return self::format_address_names( $address, $order, 'shipping' );
	}

	/**
	 * @param array    $address Address parts.
	 * @param WC_Order $order   Order object.
	 * @param string   $type    billing|shipping.
	 */
	private static function format_address_names( $address, $order, $type ) {
		if ( ! $order instanceof WC_Order || ! self::should_use_vn_fields( $order, $type ) || ! is_array( $address ) ) {
			return $address;
		}

		$ma_tinh = (string) $order->{'get_' . $type . '_state'}();
		$ma_xa   = (string) $order->{'get_' . $type . '_city'}();

		if ( '' !== $ma_tinh && isset( $address['state'] ) ) {
			$address['state'] = self::get_province_name( $ma_tinh );
		}

		if ( '' !== $ma_xa && isset( $address['city'] ) ) {
			$address['city'] = self::get_ward_name( $ma_tinh, $ma_xa );
		}

		// Đơn cũ có thể lưu tên phường/xã ở address_2 — bỏ trùng khi hiển thị.
		if ( isset( $address['address_2'], $address['city'] ) && $address['address_2'] === $address['city'] ) {
			$address['address_2'] = '';
		}

		return $address;
	}

	/**
	 * @param WC_Order|false $order Order object.
	 * @param string         $type  billing|shipping.
	 */
	private static function should_use_vn_fields( $order, $type ) {
		if ( ! $order ) {
			return true;
		}

		$country = (string) $order->{'get_' . $type . '_country'}();
		return '' === $country || 'VN' === $country;
	}

	/**
	 * WooCommerce mặc định format VN không có {state} — bổ sung để hiện Tỉnh/TP.
	 *
	 * @param array<string, string> $formats Country address formats.
	 */
	public static function vn_address_format( $formats ) {
		$formats['VN'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{country}";

		return $formats;
	}

	/**
	 * @return array<string, string>
	 */
	private static function get_province_options() {
		$options = array( '' => __( 'Chọn tỉnh/thành', 'echbay-quick-buy' ) );

		foreach ( EQB_Address::get_province_state_labels() as $ma_tinh => $ten_tinh ) {
			$options[ (string) $ma_tinh ] = (string) $ten_tinh;
		}

		return $options;
	}

	/**
	 * @param string $ma_tinh  Province code.
	 * @param string $selected Selected ward code.
	 * @return array<string, string>
	 */
	private static function get_ward_options( $ma_tinh, $selected = '' ) {
		$options = array( '' => __( 'Chọn phường/xã', 'echbay-quick-buy' ) );
		$ma_tinh = EQB_Address::sanitize_ma_tinh( $ma_tinh );

		if ( '' !== $ma_tinh ) {
			foreach ( EQB_Address::get_wards_list( $ma_tinh ) as $ward ) {
				$options[ $ward['ma_xa'] ] = $ward['ten_xa'];
			}
		}

		$selected = sanitize_text_field( (string) $selected );
		if ( '' !== $selected && ! isset( $options[ $selected ] ) ) {
			$options[ $selected ] = self::get_ward_name( $ma_tinh, $selected );
		}

		return $options;
	}

	private static function get_province_name( $ma_tinh ) {
		$ma_tinh   = EQB_Address::sanitize_ma_tinh( $ma_tinh );
		$provinces = EQB_Address::get_provinces();

		return isset( $provinces[ $ma_tinh ]['ten_tinh'] )
			? (string) $provinces[ $ma_tinh ]['ten_tinh']
			: $ma_tinh;
	}

	private static function get_ward_name( $ma_tinh, $ma_xa ) {
		$ma_tinh = EQB_Address::sanitize_ma_tinh( $ma_tinh );
		$ma_xa   = sanitize_text_field( (string) $ma_xa );
		$wards   = EQB_Address::get_wards( $ma_tinh );

		return isset( $wards[ $ma_xa ]['ten_xa'] )
			? (string) $wards[ $ma_xa ]['ten_xa']
			: $ma_xa;
	}

	/**
	 * Đặt Tỉnh/TP trước Phường/Xã.
	 *
	 * @param array<string, array<string, mixed>> $fields Address fields.
	 */
	private static function reorder_state_before_city( $fields ) {
		if ( ! isset( $fields['state'], $fields['city'] ) ) {
			return $fields;
		}

		$state = $fields['state'];
		$city  = $fields['city'];
		unset( $fields['state'], $fields['city'] );

		$ordered = array();
		foreach ( $fields as $key => $field ) {
			if ( 'postcode' === $key ) {
				$ordered['state'] = $state;
				$ordered['city']  = $city;
			}
			$ordered[ $key ] = $field;
		}

		if ( ! isset( $ordered['state'] ) ) {
			$ordered['state'] = $state;
			$ordered['city']  = $city;
		}

		return $ordered;
	}

	public static function enqueue_assets( $hook ) {
		if ( ! self::is_order_edit_screen( $hook ) ) {
			return;
		}

		wp_enqueue_script(
			'eqb-admin-order',
			EQB_URL . 'assets/js/admin-order.js',
			array( 'jquery' ),
			EQB_VERSION,
			true
		);

		wp_localize_script(
			'eqb-admin-order',
			'eqb_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'eqb_admin_order' ),
				'i18n'     => array(
					'selectWard' => __( 'Chọn phường/xã', 'echbay-quick-buy' ),
					'error'      => __( 'Không tải được phường/xã.', 'echbay-quick-buy' ),
				),
			)
		);
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	private static function is_order_edit_screen( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'shop_order' === $post_type ) {
				return true;
			}

			if ( 'post.php' === $hook && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$post_id = absint( wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return 'shop_order' === get_post_type( $post_id );
			}

			return 'post-new.php' === $hook && 'shop_order' === $post_type;
		}

		if ( 'woocommerce_page_wc-orders' === $hook ) {
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return in_array( $action, array( 'edit', 'new' ), true );
		}

		return false;
	}

	public static function ajax_get_wards() {
		check_ajax_referer( 'eqb_admin_order', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'echbay-quick-buy' ) ), 403 );
		}

		$ma_tinh = isset( $_POST['ma_tinh'] ) ? EQB_Address::sanitize_ma_tinh( wp_unslash( $_POST['ma_tinh'] ) ) : '';

		if ( '' === $ma_tinh ) {
			wp_send_json_error( array( 'message' => __( 'Mã tỉnh không hợp lệ.', 'echbay-quick-buy' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'wards' => EQB_Address::get_wards_list( $ma_tinh ),
			)
		);
	}
}
