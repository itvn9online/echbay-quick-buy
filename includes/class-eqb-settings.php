<?php
/**
 * Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Settings {

	const OPTION_KEY = 'echbay_quickbuy_options';

	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
			add_action( 'admin_init', array( __CLASS__, 'handle_flush_cache' ) );
			add_filter( 'plugin_action_links_' . EQB_BASENAME, array( __CLASS__, 'add_plugin_action_links' ) );
		}
	}

	public static function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=echbay-quick-buy' ) ),
			esc_html__( 'Cài đặt', 'echbay-quick-buy' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	public static function defaults() {
		return array(
			'enable'            => '1',
			'button_title'      => 'MUA NGAY',
			'button_subtitle'   => 'Gọi điện xác nhận và giao hàng tận nơi',
			'popup_prefix'      => 'ĐẶT MUA',
			'phone_notice'      => 'Bạn vui lòng nhập đúng số điện thoại để chúng tôi sẽ gọi xác nhận đơn hàng trước khi giao hàng. Xin cảm ơn!',
			'success_message'   => 'Đặt hàng thành công! Mã đơn: %order_id%',
			'error_message'     => 'Đặt hàng thất bại, vui lòng thử lại.',
			'redirect_thankyou' => '1',
			'checkout_vn_form'  => '1',
			'debug_order_note'  => '0',
		);
	}

	public static function get_all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	public static function get( $key, $default = '' ) {
		$options = self::get_all();
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	public static function is_enabled() {
		return '1' === (string) self::get( 'enable', '1' );
	}

	public static function add_menu() {
		add_options_page(
			__( 'Echbay Quick Buy', 'echbay-quick-buy' ),
			__( 'Echbay Quick Buy', 'echbay-quick-buy' ),
			'manage_options',
			'echbay-quick-buy',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'eqb_options_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_all();
		}

		$out     = self::get_all();
		$texts   = array( 'button_title', 'button_subtitle', 'popup_prefix', 'phone_notice', 'success_message', 'error_message' );
		$checks  = array( 'enable', 'redirect_thankyou', 'checkout_vn_form', 'debug_order_note' );

		foreach ( $checks as $key ) {
			$out[ $key ] = ! empty( $input[ $key ] ) ? '1' : '0';
		}

		foreach ( $texts as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
			}
		}

		if ( isset( $input['phone_notice'] ) ) {
			$out['phone_notice'] = sanitize_textarea_field( wp_unslash( $input['phone_notice'] ) );
		}
		if ( isset( $input['success_message'] ) ) {
			$out['success_message'] = sanitize_textarea_field( wp_unslash( $input['success_message'] ) );
		}
		if ( isset( $input['error_message'] ) ) {
			$out['error_message'] = sanitize_textarea_field( wp_unslash( $input['error_message'] ) );
		}

		return $out;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options       = self::get_all();
		$address_ready = EQB_Install::is_installed();
		include EQB_PATH . 'templates/admin-settings.php';
	}

	public static function handle_flush_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['eqb_flush_cache'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'eqb_flush_cache' ) ) {
			return;
		}

		EQB_Address::flush_cache();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'echbay-quick-buy',
					'eqb_cache_flushed' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
}
