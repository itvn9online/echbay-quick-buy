=== Echbay Quick Buy ===
Contributors: echbay
Tags: woocommerce, quick buy, mua nhanh, cod
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.17
License: GPLv2 or later

Nút Mua ngay trên trang sản phẩm WooCommerce — popup đặt hàng nhanh qua AJAX.

== Description ==

Phase 2: biến thể, địa chỉ 2 cấp (Tỉnh → Phường/Xã), chọn payment gateway, form đầy đủ.

== Installation ==

Upload thư mục vào wp-content/plugins/ và kích hoạt. Cần WooCommerce. Bảng địa chỉ import tự động lúc activate.

== Changelog ==

= 1.1.17 =
* Sửa lỗi checkout: Tỉnh/Thành phố (billing_state) không được chọn dù đã có eqb_ma_tinh trong localStorage — do danh sách tỉnh/SelectWoo của WooCommerce chưa kịp load khi script khôi phục địa chỉ chạy.
* Thêm retry tự động, đồng bộ SelectWoo sau khi set giá trị, và lắng nghe sự kiện country_to_state_changed.

= 1.1.16 =
* Tùy chọn không bắt buộc billing_address_1 và billing_email (checkout + popup Mua ngay).

= 1.1.15 =
* Cập nhật plugin tự động từ GitHub Releases (itvn9online/echbay-quick-buy).

= 1.1.2 =
* New update

= 1.1.0 =
* Phase 2: biến thể, địa chỉ cache PHP, payment gateway

= 1.0.0 =
* Phase 1 MVP
