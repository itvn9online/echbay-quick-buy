<?php
defined( 'ABSPATH' ) || exit;

/**
 * @param string $id       Input id.
 * @param string $name     Input name.
 * @param string $value    Input value.
 */
if ( ! function_exists( 'eqb_admin_password_field' ) ) {
	function eqb_admin_password_field( $id, $name, $value ) {
	?>
	<span class="eqb-password-field">
		<input type="password"
			class="regular-text eqb-password-input"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			autocomplete="new-password">
		<button type="button"
			class="button eqb-password-toggle"
			aria-pressed="false"
			aria-label="<?php esc_attr_e( 'Hiện mật khẩu', 'echbay-quick-buy' ); ?>"
			data-show-label="<?php esc_attr_e( 'Hiện', 'echbay-quick-buy' ); ?>"
			data-hide-label="<?php esc_attr_e( 'Ẩn', 'echbay-quick-buy' ); ?>">
			<?php esc_html_e( 'Hiện', 'echbay-quick-buy' ); ?>
		</button>
	</span>
	<?php
	}
}
?>
<style>
	.eqb-password-field {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		max-width: 32em;
		width: 100%;
	}
	.eqb-password-field .eqb-password-input {
		flex: 1;
		min-width: 0;
	}
	.eqb-password-toggle {
		flex-shrink: 0;
	}
</style>
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
				<th scope="row"><?php esc_html_e( 'Địa chỉ nhà (số nhà, đường)', 'echbay-quick-buy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[address_optional]" value="1" <?php checked( '1', $options['address_optional'] ); ?>>
						<?php esc_html_e( 'Không bắt buộc nhập (billing_address_1)', 'echbay-quick-buy' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Áp dụng cho trang Thanh toán WooCommerce và popup Mua ngay.', 'echbay-quick-buy' ); ?></p>
					<p>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[cancel_no_address]" value="1" <?php checked( '1', $options['cancel_no_address'] ); ?>>
							<?php esc_html_e( 'Tự động hủy đơn không có địa chỉ nhà (nghi spam)', 'echbay-quick-buy' ); ?>
						</label>
					</p>
					<p class="description"><?php esc_html_e( 'Khi bật: đơn thiếu số nhà/tên đường sẽ chuyển trạng thái Hủy thay vì xử lý bình thường. Tắt nếu shop cho phép đặt hàng chỉ với Tỉnh/Phường.', 'echbay-quick-buy' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email', 'echbay-quick-buy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[email_optional]" value="1" <?php checked( '1', $options['email_optional'] ); ?>>
						<?php esc_html_e( 'Không bắt buộc nhập (billing_email)', 'echbay-quick-buy' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Áp dụng cho trang Thanh toán WooCommerce và popup Mua ngay.', 'echbay-quick-buy' ); ?></p>
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

		<h2><?php esc_html_e( 'CAPTCHA chống spam', 'echbay-quick-buy' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Bật xác minh bên thứ ba trên form Mua ngay. Mặc định tắt — chỉ bật khi shop bị spam.', 'echbay-quick-buy' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="eqb-captcha-provider"><?php esc_html_e( 'Nhà cung cấp CAPTCHA', 'echbay-quick-buy' ); ?></label></th>
				<td>
					<select id="eqb-captcha-provider" name="<?php echo esc_attr( $key ); ?>[captcha_provider]">
						<option value="off" <?php selected( 'off', $options['captcha_provider'] ); ?>><?php esc_html_e( 'Tắt', 'echbay-quick-buy' ); ?></option>
						<option value="google_recaptcha" <?php selected( 'google_recaptcha', $options['captcha_provider'] ); ?>><?php esc_html_e( 'Google reCAPTCHA v2 (Checkbox)', 'echbay-quick-buy' ); ?></option>
						<option value="google_recaptcha_v3" <?php selected( 'google_recaptcha_v3', $options['captcha_provider'] ); ?>><?php esc_html_e( 'Google reCAPTCHA v3 (Vô hình)', 'echbay-quick-buy' ); ?></option>
						<option value="cloudflare_turnstile" <?php selected( 'cloudflare_turnstile', $options['captcha_provider'] ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'echbay-quick-buy' ); ?></option>
					</select>
				</td>
			</tr>
			<tbody class="eqb-captcha-group" data-provider="google_recaptcha">
				<tr>
					<th scope="row"><label for="eqb-recaptcha-site-key"><?php esc_html_e( 'Site Key', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="eqb-recaptcha-site-key" name="<?php echo esc_attr( $key ); ?>[recaptcha_site_key]" value="<?php echo esc_attr( $options['recaptcha_site_key'] ); ?>" autocomplete="off">
						<p class="description">
							<?php
							printf(
								/* translators: %s: Google reCAPTCHA admin URL */
								wp_kses_post( __( 'Lấy tại <a href="%s" target="_blank" rel="noopener noreferrer">Google reCAPTCHA Admin</a>. Chọn loại <strong>reCAPTCHA v2</strong> → <strong>“Tôi không phải người máy” Checkbox</strong>. Thêm domain website (ví dụ: example.com).', 'echbay-quick-buy' ) ),
								esc_url( 'https://www.google.com/recaptcha/admin' )
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="eqb-recaptcha-secret-key"><?php esc_html_e( 'Secret Key', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<?php
						eqb_admin_password_field(
							'eqb-recaptcha-secret-key',
							$key . '[recaptcha_secret_key]',
							$options['recaptcha_secret_key']
						);
						?>
						<p class="description"><?php esc_html_e( 'Secret Key hiển thị cùng Site Key sau khi tạo site trên Google reCAPTCHA.', 'echbay-quick-buy' ); ?></p>
					</td>
				</tr>
			</tbody>
			<tbody class="eqb-captcha-group" data-provider="google_recaptcha_v3">
				<tr>
					<th scope="row"><label for="eqb-recaptcha-v3-site-key"><?php esc_html_e( 'Site Key', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="eqb-recaptcha-v3-site-key" name="<?php echo esc_attr( $key ); ?>[recaptcha_v3_site_key]" value="<?php echo esc_attr( $options['recaptcha_v3_site_key'] ); ?>" autocomplete="off">
						<p class="description">
							<?php
							printf(
								/* translators: %s: Google reCAPTCHA admin URL */
								wp_kses_post( __( 'Lấy tại <a href="%s" target="_blank" rel="noopener noreferrer">Google reCAPTCHA Admin</a>. Chọn loại <strong>reCAPTCHA v3</strong> (Score based). Key v3 khác key v2 — cần tạo site riêng.', 'echbay-quick-buy' ) ),
								esc_url( 'https://www.google.com/recaptcha/admin' )
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="eqb-recaptcha-v3-secret-key"><?php esc_html_e( 'Secret Key', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<?php
						eqb_admin_password_field(
							'eqb-recaptcha-v3-secret-key',
							$key . '[recaptcha_v3_secret_key]',
							$options['recaptcha_v3_secret_key']
						);
						?>
						<p class="description"><?php esc_html_e( 'Secret Key của site reCAPTCHA v3.', 'echbay-quick-buy' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="eqb-recaptcha-v3-score"><?php esc_html_e( 'Ngưỡng điểm (score)', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="eqb-recaptcha-v3-score" name="<?php echo esc_attr( $key ); ?>[recaptcha_v3_score]" value="<?php echo esc_attr( $options['recaptcha_v3_score'] ); ?>" min="0" max="1" step="0.1">
						<p class="description"><?php esc_html_e( 'Google trả về điểm 0.0–1.0 (1.0 = người thật). Mặc định 0.5. Tăng lên (ví dụ 0.7) nếu vẫn bị spam; giảm nếu khách hợp lệ bị chặn.', 'echbay-quick-buy' ); ?></p>
					</td>
				</tr>
			</tbody>
			<tbody class="eqb-captcha-group" data-provider="cloudflare_turnstile">
				<tr>
					<th scope="row"><label for="eqb-turnstile-site-key"><?php esc_html_e( 'Site Key', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="eqb-turnstile-site-key" name="<?php echo esc_attr( $key ); ?>[turnstile_site_key]" value="<?php echo esc_attr( $options['turnstile_site_key'] ); ?>" autocomplete="off">
						<p class="description">
							<?php
							printf(
								/* translators: %s: Cloudflare Turnstile dashboard URL */
								wp_kses_post( __( 'Lấy tại <a href="%s" target="_blank" rel="noopener noreferrer">Cloudflare Turnstile</a>. Tạo widget mới, chọn chế độ Managed (khuyến nghị). Thêm hostname website.', 'echbay-quick-buy' ) ),
								esc_url( 'https://dash.cloudflare.com/?to=/:account/turnstile' )
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="eqb-turnstile-secret-key"><?php esc_html_e( 'Secret Key', 'echbay-quick-buy' ); ?></label></th>
					<td>
						<?php
						eqb_admin_password_field(
							'eqb-turnstile-secret-key',
							$key . '[turnstile_secret_key]',
							$options['turnstile_secret_key']
						);
						?>
						<p class="description"><?php esc_html_e( 'Secret Key hiển thị trong chi tiết widget Turnstile trên Cloudflare.', 'echbay-quick-buy' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<script>
		(function () {
			var select = document.getElementById('eqb-captcha-provider');
			var groups = document.querySelectorAll('.eqb-captcha-group');

			function toggleCaptchaGroups() {
				var provider = select ? select.value : 'off';
				groups.forEach(function (group) {
					group.style.display = group.getAttribute('data-provider') === provider ? '' : 'none';
				});
			}

			if (select) {
				select.addEventListener('change', toggleCaptchaGroups);
				toggleCaptchaGroups();
			}

			document.querySelectorAll('.eqb-password-toggle').forEach(function (button) {
				button.addEventListener('click', function () {
					var wrap = button.closest('.eqb-password-field');
					if (!wrap) {
						return;
					}

					var input = wrap.querySelector('.eqb-password-input');
					if (!input) {
						return;
					}

					var isHidden = input.type === 'password';
					input.type = isHidden ? 'text' : 'password';
					button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
					button.setAttribute(
						'aria-label',
						isHidden ? button.getAttribute('data-hide-label') : button.getAttribute('data-show-label')
					);
					button.textContent = isHidden ? button.getAttribute('data-hide-label') : button.getAttribute('data-show-label');
				});
			});
		})();
		</script>

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
