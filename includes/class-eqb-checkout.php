<?php
/**
 * Checkout WooCommerce — form địa chỉ VN (2 cấp).
 */

defined( 'ABSPATH' ) || exit;

class EQB_Checkout {

	public static function init() {
		if ( ! self::is_active() ) {
			return;
		}

		$prio = 99999;

		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'customize_fields' ), $prio );
		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'customize_legacy_billing' ), $prio );
		add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'customize_legacy_shipping' ), $prio );
		add_filter( 'woocommerce_get_country_locale', array( __CLASS__, 'country_locale' ), $prio );
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'default_address_fields' ), $prio );
		add_filter( 'default_checkout_billing_country', array( __CLASS__, 'default_country' ) );
		add_filter( 'default_checkout_shipping_country', array( __CLASS__, 'default_country' ) );
		add_filter( 'woocommerce_checkout_posted_data', array( __CLASS__, 'sync_shipping_posted_data' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'sync_shipping_on_order' ), 20, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 9999 );
	}

	/**
	 * @param array<string, array<string, mixed>> $fields Billing fields.
	 */
	public static function customize_legacy_billing( $fields ) {
		return self::customize_address_group( $fields, 'billing' );
	}

	/**
	 * @param array<string, array<string, mixed>> $fields Shipping fields.
	 */
	public static function customize_legacy_shipping( $fields ) {
		return self::customize_address_group( $fields, 'shipping' );
	}

	/**
	 * @param array<string, array<string, mixed>> $fields Default address fields.
	 */
	public static function default_address_fields( $fields ) {
		if ( isset( $fields['state'] ) ) {
			$fields['state']['label'] = __( 'Tỉnh/Thành phố', 'echbay-quick-buy' );
		}
		if ( isset( $fields['city'] ) ) {
			$fields['city']['label'] = __( 'Phường/Xã', 'echbay-quick-buy' );
		}
		if ( isset( $fields['address_1'] ) ) {
			$fields['address_1']['label'] = __( 'Địa chỉ', 'echbay-quick-buy' );
		}

		return $fields;
	}

	public static function is_active() {
		return '1' === (string) EQB_Settings::get( 'checkout_vn_form', '1' ) && EQB_Install::is_installed();
	}

	public static function default_country() {
		return 'VN';
	}

	/**
	 * Các trường địa chỉ giao hàng cần đồng bộ từ billing khi không giao địa chỉ khác.
	 *
	 * @return string[]
	 */
	private static function shipping_sync_keys() {
		return array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
		);
	}

	/**
	 * Đảm bảo shipping_* có trong posted data trước khi WooCommerce tạo đơn.
	 *
	 * @param array<string, mixed> $data Posted checkout data.
	 * @return array<string, mixed>
	 */
	public static function sync_shipping_posted_data( $data ) {
		if ( ! empty( $data['ship_to_different_address'] ) ) {
			return $data;
		}

		foreach ( self::shipping_sync_keys() as $key ) {
			$billing_key  = 'billing_' . $key;
			$shipping_key = 'shipping_' . $key;

			if ( isset( $data[ $billing_key ] ) && '' !== (string) $data[ $billing_key ] ) {
				$data[ $shipping_key ] = $data[ $billing_key ];
			}
		}

		if ( empty( $data['shipping_country'] ) ) {
			$data['shipping_country'] = ! empty( $data['billing_country'] ) ? $data['billing_country'] : 'VN';
		}

		return $data;
	}

	/**
	 * Ghi shipping vào order object (WC có thể bỏ qua khi needs_shipping_address() = false).
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $data  Posted checkout data.
	 */
	public static function sync_shipping_on_order( $order, $data ) {
		if ( ! $order instanceof WC_Order || ! empty( $data['ship_to_different_address'] ) ) {
			return;
		}

		foreach ( self::shipping_sync_keys() as $key ) {
			$billing_key  = 'billing_' . $key;
			$shipping_key = 'shipping_' . $key;
			$setter       = 'set_shipping_' . $key;

			if ( ! method_exists( $order, $setter ) ) {
				continue;
			}

			if ( isset( $data[ $shipping_key ] ) && '' !== (string) $data[ $shipping_key ] ) {
				$order->$setter( $data[ $shipping_key ] );
				continue;
			}

			if ( isset( $data[ $billing_key ] ) && '' !== (string) $data[ $billing_key ] ) {
				$order->$setter( $data[ $billing_key ] );
			}
		}

		if ( '' === (string) $order->get_shipping_country() ) {
			$order->set_shipping_country( '' !== (string) $order->get_billing_country() ? $order->get_billing_country() : 'VN' );
		}
	}

	/**
	 * @param array<string, array<string, mixed>> $fields Checkout fields.
	 */
	public static function customize_fields( $fields ) {
		if ( isset( $fields['billing'] ) ) {
			$fields['billing'] = self::customize_address_group( $fields['billing'], 'billing' );
		}
		if ( isset( $fields['shipping'] ) ) {
			$fields['shipping'] = self::customize_address_group( $fields['shipping'], 'shipping' );
		}

		return $fields;
	}

	/**
	 * @param array<string, array<string, mixed>> $fields  Field group.
	 * @param string                              $prefix billing|shipping.
	 */
	private static function customize_address_group( $fields, $prefix ) {
		$first = $prefix . '_first_name';
		$state = $prefix . '_state';
		$city  = $prefix . '_city';

		if ( isset( $fields[ $first ] ) ) {
			$fields[ $first ]['label']    = __( 'Họ và tên', 'echbay-quick-buy' );
			$fields[ $first ]['class']    = array( 'form-row-wide' );
			$fields[ $first ]['priority'] = 10;
		}

		unset( $fields[ $prefix . '_last_name' ] );
		unset( $fields[ $prefix . '_company' ] );
		unset( $fields[ $prefix . '_address_2' ] );
		unset( $fields[ $prefix . '_postcode' ] );

		if ( isset( $fields[ $prefix . '_phone' ] ) ) {
			$fields[ $prefix . '_phone' ]['label']    = __( 'Số điện thoại', 'echbay-quick-buy' );
			$fields[ $prefix . '_phone' ]['class']    = array( 'form-row-first', 'eqb-checkout-phone' );
			$fields[ $prefix . '_phone' ]['priority'] = 20;
			$fields[ $prefix . '_phone' ]['required'] = true;
		}

		if ( isset( $fields[ $prefix . '_email' ] ) ) {
			$fields[ $prefix . '_email' ]['label']    = __( 'Địa chỉ email', 'echbay-quick-buy' );
			$fields[ $prefix . '_email' ]['class']    = array( 'form-row-last', 'eqb-checkout-email' );
			$fields[ $prefix . '_email' ]['priority'] = 21;
		}

		if ( isset( $fields[ $state ] ) ) {
			$fields[ $state ]['type']     = 'state';
			$fields[ $state ]['label']    = __( 'Tỉnh/Thành phố', 'echbay-quick-buy' );
			$fields[ $state ]['class']    = array( 'form-row-first', 'address-field', 'validate-required', 'eqb-checkout-province' );
			$fields[ $state ]['priority'] = 30;
			$fields[ $state ]['required'] = true;
		}

		$ma_tinh = '';
		$ma_xa   = '';
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$getter_state = 'get_' . $prefix . '_state';
			$getter_city  = 'get_' . $prefix . '_city';
			if ( method_exists( WC()->customer, $getter_state ) ) {
				$ma_tinh = (string) WC()->customer->$getter_state();
			}
			if ( method_exists( WC()->customer, $getter_city ) ) {
				$ma_xa = (string) WC()->customer->$getter_city();
			}
		}

		if ( isset( $fields[ $city ] ) ) {
			$fields[ $city ]['type']     = 'select';
			$fields[ $city ]['label']    = __( 'Phường/Xã', 'echbay-quick-buy' );
			$fields[ $city ]['class']    = array( 'form-row-last', 'address-field', 'validate-required', 'eqb-checkout-ward' );
			$fields[ $city ]['priority'] = 31;
			$fields[ $city ]['required'] = true;
			$fields[ $city ]['options']  = EQB_Address::get_ward_select_options( $ma_tinh, $ma_xa );
		}

		if ( isset( $fields[ $prefix . '_address_1' ] ) ) {
			$fields[ $prefix . '_address_1' ]['label']    = __( 'Địa chỉ', 'echbay-quick-buy' );
			$fields[ $prefix . '_address_1' ]['class']    = array( 'form-row-wide' );
			$fields[ $prefix . '_address_1' ]['priority'] = 40;
			$fields[ $prefix . '_address_1' ]['placeholder'] = __( 'Số nhà, tên đường', 'echbay-quick-buy' );
		}

		if ( isset( $fields[ $prefix . '_country' ] ) ) {
			$fields[ $prefix . '_country' ]['type']     = 'hidden';
			$fields[ $prefix . '_country' ]['default']  = 'VN';
			$fields[ $prefix . '_country' ]['required'] = false;
			$fields[ $prefix . '_country' ]['priority'] = 1;
		}

		return self::sort_fields( $fields );
	}

	/**
	 * @param array<string, array<string, mixed>> $fields Address fields.
	 */
	private static function sort_fields( $fields ) {
		uasort(
			$fields,
			static function ( $a, $b ) {
				$p_a = isset( $a['priority'] ) ? (int) $a['priority'] : 0;
				$p_b = isset( $b['priority'] ) ? (int) $b['priority'] : 0;
				if ( $p_a === $p_b ) {
					return 0;
				}
				return $p_a <=> $p_b;
			}
		);

		return $fields;
	}

	/**
	 * @param array<string, array<string, mixed>> $locale Country locale.
	 */
	public static function country_locale( $locale ) {
		$locale['VN'] = array_merge(
			isset( $locale['VN'] ) && is_array( $locale['VN'] ) ? $locale['VN'] : array(),
			array(
				'postcode' => array(
					'hidden'   => true,
					'required' => false,
				),
				'state'    => array(
					'label'       => __( 'Tỉnh/Thành phố', 'echbay-quick-buy' ),
					'required'    => true,
					'priority'    => 30,
					'placeholder' => __( 'Chọn tỉnh/thành', 'echbay-quick-buy' ),
				),
				'city'     => array(
					'label'       => __( 'Phường/Xã', 'echbay-quick-buy' ),
					'required'    => true,
					'priority'    => 31,
					'placeholder' => __( 'Chọn phường/xã', 'echbay-quick-buy' ),
				),
				'address_1' => array(
					'label'    => __( 'Địa chỉ', 'echbay-quick-buy' ),
					'priority' => 40,
				),
			)
		);

		return $locale;
	}

	/**
	 * @param array    $data   Posted checkout data.
	 * @param WP_Error $errors Validation errors.
	 */
	public static function validate( $data, $errors ) {
		foreach ( array( 'billing', 'shipping' ) as $prefix ) {
			if ( 'shipping' === $prefix && empty( $data['ship_to_different_address'] ) ) {
				continue;
			}

			$ma_tinh = isset( $data[ $prefix . '_state' ] ) ? EQB_Address::sanitize_ma_tinh( $data[ $prefix . '_state' ] ) : '';
			$ma_xa   = isset( $data[ $prefix . '_city' ] ) ? sanitize_text_field( $data[ $prefix . '_city' ] ) : '';

			if ( '' === $ma_tinh || '' === $ma_xa ) {
				$errors->add( 'eqb_' . $prefix . '_address', __( 'Vui lòng chọn Tỉnh/Thành phố và Phường/Xã.', 'echbay-quick-buy' ) );
				continue;
			}

			$provinces = EQB_Address::get_provinces();
			if ( ! isset( $provinces[ $ma_tinh ] ) ) {
				$errors->add( 'eqb_' . $prefix . '_province', __( 'Tỉnh/Thành phố không hợp lệ.', 'echbay-quick-buy' ) );
				continue;
			}

			$wards = EQB_Address::get_wards( $ma_tinh );
			if ( ! isset( $wards[ $ma_xa ] ) ) {
				$errors->add( 'eqb_' . $prefix . '_ward', __( 'Phường/Xã không hợp lệ.', 'echbay-quick-buy' ) );
			}
		}
	}

	public static function enqueue_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		self::dequeue_conflicting_scripts();

		wp_enqueue_style(
			'eqb-checkout',
			EQB_URL . 'assets/css/checkout.css',
			array(),
			EQB_VERSION
		);

		wp_enqueue_script(
			'eqb-checkout',
			EQB_URL . 'assets/js/checkout.js',
			array( 'jquery', 'wc-checkout' ),
			EQB_VERSION,
			true
		);

		wp_localize_script(
			'eqb-checkout',
			'eqb_checkout',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'eqb_quick_buy' ),
				'i18n'     => array(
					'selectProvince' => __( 'Chọn tỉnh/thành', 'echbay-quick-buy' ),
					'selectWard'     => __( 'Chọn phường/xã', 'echbay-quick-buy' ),
					'loadingWards'   => __( 'Đang tải phường/xã...', 'echbay-quick-buy' ),
					'error'          => __( 'Không tải được phường/xã.', 'echbay-quick-buy' ),
				),
			)
		);
	}

	private static function dequeue_conflicting_scripts() {
		$handles = apply_filters(
			'eqb_dequeue_vn_checkout_scripts',
			array(
				'devvn-checkout-js',
				'devvn-woo-address',
				'woocommerce-vietnam-checkout',
				'vietnam-checkout',
				'woo-vietnam-checkout',
				'vn-checkout',
				'wc-vietnam-checkout',
				'devvn-localization-js',
			)
		);

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}
