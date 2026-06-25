<?php
/**
 * Plugin Name:       Echbay Quick Buy
 * Plugin URI:        https://echbay.com/
 * Description:       Nút Mua ngay trên trang sản phẩm WooCommerce — popup đặt hàng nhanh qua AJAX.
 * Version:           1.1.14
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Dao Quoc Dai
 * Author URI:        https://echbay.com/
 * Text Domain:       echbay-quick-buy
 * Requires Plugins:  woocommerce
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// Phiên bản dùng làm query string (?ver=) khi enqueue CSS/JS — trình duyệt tải lại file sau khi đổi.
// Khi sửa assets/css/* hoặc assets/js/*: nâng EQB_VERSION và đồng bộ Version ở header plugin (dòng ~6).
// define( 'EQB_VERSION', time() ); // bật tạm khi dev để luôn bypass cache.
define( 'EQB_VERSION', '1.1.14' );
define( 'EQB_PATH', plugin_dir_path( __FILE__ ) );
define( 'EQB_URL', plugin_dir_url( __FILE__ ) );
define( 'EQB_BASENAME', plugin_basename( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

require_once EQB_PATH . 'includes/class-eqb-settings.php';
require_once EQB_PATH . 'includes/class-eqb-install.php';
require_once EQB_PATH . 'includes/class-eqb-address.php';
require_once EQB_PATH . 'includes/class-eqb-order.php';
require_once EQB_PATH . 'includes/class-eqb-honeypot.php';
require_once EQB_PATH . 'includes/class-eqb-ajax.php';
require_once EQB_PATH . 'includes/class-eqb-frontend.php';
require_once EQB_PATH . 'includes/class-eqb-checkout.php';
require_once EQB_PATH . 'includes/class-eqb-admin-order.php';

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'Echbay Quick Buy cần WooCommerce được cài và kích hoạt.', 'echbay-quick-buy' );
					echo '</p></div>';
				}
			);
			return;
		}

		EQB_Settings::init();
		EQB_Address::init();
		EQB_Ajax::init();
		EQB_Frontend::init();
		EQB_Checkout::init();
		EQB_Admin_Order::init();

		if ( is_admin() ) {
			add_action( 'admin_init', array( 'EQB_Install', 'admin_check' ) );
		}
	}
);

register_activation_hook( __FILE__, array( 'EQB_Install', 'activate' ) );
