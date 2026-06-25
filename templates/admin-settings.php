<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Echbay Quick Buy', 'echbay-quick-buy' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'eqb_options_group' );
		$key = EQB_Settings::OPTION_KEY;
		?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Bật plugin', 'echbay-quick-buy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[enable]" value="1" <?php checked( '1', $options['enable'] ); ?>>
						<?php esc_html_e( 'Hiển thị nút Mua ngay', 'echbay-quick-buy' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="eqb-button-title"><?php esc_html_e( 'Dòng 1 nút', 'echbay-quick-buy' ); ?></label></th>
				<td><input type="text" class="regular-text" id="eqb-button-title" name="<?php echo esc_attr( $key ); ?>[button_title]" value="<?php echo esc_attr( $options['button_title'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="eqb-button-sub"><?php esc_html_e( 'Dòng 2 nút', 'echbay-quick-buy' ); ?></label></th>
				<td><input type="text" class="regular-text" id="eqb-button-sub" name="<?php echo esc_attr( $key ); ?>[button_subtitle]" value="<?php echo esc_attr( $options['button_subtitle'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="eqb-popup-prefix"><?php esc_html_e( 'Tiền tố header popup', 'echbay-quick-buy' ); ?></label></th>
				<td><input type="text" class="regular-text" id="eqb-popup-prefix" name="<?php echo esc_attr( $key ); ?>[popup_prefix]" value="<?php echo esc_attr( $options['popup_prefix'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="eqb-phone-notice"><?php esc_html_e( 'Ghi chú dưới số lượng', 'echbay-quick-buy' ); ?></label></th>
				<td><textarea class="large-text" rows="3" id="eqb-phone-notice" name="<?php echo esc_attr( $key ); ?>[phone_notice]"><?php echo esc_textarea( $options['phone_notice'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="eqb-success"><?php esc_html_e( 'Thông báo thành công', 'echbay-quick-buy' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="2" id="eqb-success" name="<?php echo esc_attr( $key ); ?>[success_message]"><?php echo esc_textarea( $options['success_message'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Dùng %order_id% cho mã đơn hàng.', 'echbay-quick-buy' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="eqb-error"><?php esc_html_e( 'Thông báo lỗi', 'echbay-quick-buy' ); ?></label></th>
				<td><textarea class="large-text" rows="2" id="eqb-error" name="<?php echo esc_attr( $key ); ?>[error_message]"><?php echo esc_textarea( $options['error_message'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Chuyển trang cảm ơn', 'echbay-quick-buy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[redirect_thankyou]" value="1" <?php checked( '1', $options['redirect_thankyou'] ); ?>>
						<?php esc_html_e( 'Sau khi đặt hàng thành công', 'echbay-quick-buy' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Form checkout VN', 'echbay-quick-buy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[checkout_vn_form]" value="1" <?php checked( '1', $options['checkout_vn_form'] ); ?>>
						<?php esc_html_e( 'Tối ưu trang Thanh toán WooCommerce (Họ tên, Tỉnh/TP, Phường/Xã)', 'echbay-quick-buy' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Ẩn field thừa; billing_state = Tỉnh/TP, billing_city = Phường/Xã (mã vn_tinh_thanh34 / vn_phuong_xa34).', 'echbay-quick-buy' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Ghi chú debug đơn hàng', 'echbay-quick-buy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[debug_order_note]" value="1" <?php checked( '1', $options['debug_order_note'] ); ?>>
						<?php esc_html_e( 'Thêm ghi chú nội bộ (IP, trình duyệt, URL…) khi tạo đơn qua popup', 'echbay-quick-buy' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Dùng tạm để điều tra spam; nên tắt sau khi debug xong.', 'echbay-quick-buy' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Địa chỉ hành chính', 'echbay-quick-buy' ); ?></h2>
	<?php if ( ! empty( $_GET['eqb_cache_flushed'] ) ) : ?>
		<div class="notice notice-success inline"><p><?php esc_html_e( 'Đã xóa cache địa chỉ.', 'echbay-quick-buy' ); ?></p></div>
	<?php endif; ?>
	<p>
		<?php if ( $address_ready ) : ?>
			<?php esc_html_e( 'Bảng vn_tinh_thanh34 / vn_phuong_xa34 đã sẵn sàng.', 'echbay-quick-buy' ); ?>
		<?php else : ?>
			<span style="color:#b32d2e;"><?php esc_html_e( 'Chưa có dữ liệu địa chỉ — deactivate/activate plugin để import SQL.', 'echbay-quick-buy' ); ?></span>
		<?php endif; ?>
	</p>
	<p>
		<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=echbay-quick-buy&eqb_flush_cache=1' ), 'eqb_flush_cache' ) ); ?>">
			<?php esc_html_e( 'Xóa cache địa chỉ', 'echbay-quick-buy' ); ?>
		</a>
		<span class="description"><?php esc_html_e( 'Xóa file trong data/cache/ — lần sau sẽ đọc lại từ database.', 'echbay-quick-buy' ); ?></span>
	</p>

	<p><a href="https://echbay.com/" target="_blank" rel="noopener noreferrer">Powered by Echbay</a></p>
</div>
