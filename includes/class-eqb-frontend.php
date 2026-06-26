<?php
/**
 * Frontend: button, assets, popup shell.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Frontend {

	public static function init() {
		if ( ! EQB_Settings::is_enabled() ) {
			return;
		}

		add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'render_button' ), 15 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_popup_shell' ) );
	}

	public static function render_button() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		if ( ! $product->is_type( 'simple' ) && ! $product->is_type( 'variable' ) ) {
			return;
		}

		$options = EQB_Settings::get_all();
		include EQB_PATH . 'templates/button.php';
	}

	public static function enqueue_assets() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( get_queried_object_id() );
		}

		wp_enqueue_style(
			'eqb-quick-buy',
			EQB_URL . 'assets/css/quick-buy.css',
			array(),
			EQB_VERSION
		);

		$deps = array( 'jquery' );
		if ( $product && is_a( $product, 'WC_Product' ) && $product->is_type( 'variable' ) ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
			$deps[] = 'wc-add-to-cart-variation';
		}

		wp_enqueue_script(
			'eqb-quick-buy',
			EQB_URL . 'assets/js/quick-buy.js',
			$deps,
			EQB_VERSION,
			true
		);

		wp_localize_script(
			'eqb-quick-buy',
			'eqb_vars',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'eqb_quick_buy' ),
				'debug_note'       => '1' === EQB_Settings::get( 'debug_order_note', '0' ) ? '1' : '0',
				'address_optional' => EQB_Settings::is_address_optional() ? '1' : '0',
				'email_optional'   => EQB_Settings::is_email_optional() ? '1' : '0',
				'i18n'       => array(
					'loading'       => __( 'Đang tải...', 'echbay-quick-buy' ),
					'loadingWards'  => __( 'Đang tải phường/xã...', 'echbay-quick-buy' ),
					'submitting'    => __( 'Đang xử lý...', 'echbay-quick-buy' ),
					'error'         => EQB_Settings::get( 'error_message' ),
					'required'      => __( 'Vui lòng điền đầy đủ thông tin bắt buộc.', 'echbay-quick-buy' ),
					'selectWard'    => __( 'Phường/Xã *', 'echbay-quick-buy' ),
					'selectVariant' => __( 'Vui lòng chọn phân loại sản phẩm.', 'echbay-quick-buy' ),
					'consentRequired' => __( 'Vui lòng đồng ý với Điều khoản & Điều kiện trước khi đặt hàng.', 'echbay-quick-buy' ),
				),
			)
		);
	}

	public static function render_popup_shell() {
		if ( ! is_product() ) {
			return;
		}
		?>
		<div id="eqb-overlay" class="eqb-overlay eqb-hidden" aria-hidden="true">
			<div class="eqb-popup-wrap" role="dialog" aria-modal="true" aria-labelledby="eqb-popup-title">
				<div class="eqb-popup-loading eqb-hidden">
					<span class="eqb-spinner"></span>
					<span><?php esc_html_e( 'Đang tải...', 'echbay-quick-buy' ); ?></span>
				</div>
				<div id="eqb-popup-content"></div>
			</div>
		</div>
		<?php
	}
}
