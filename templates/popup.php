<?php

/**
 * Popup content (loaded via AJAX).
 *
 * @var WC_Product $product
 * @var array      $options
 */
defined('ABSPATH') || exit;

$popup_title = trim($options['popup_prefix'] . ' ' . $product->get_name());
$unit_price  = (float) $product->get_price();
$is_variable = $product->is_type('variable');
$provinces   = EQB_Address::get_provinces_list();
$gateways    = EQB_Order::get_payment_gateways();
$terms_page_id = absint(get_option('woocommerce_terms_page_id'));
$terms_url     = '';
if ($terms_page_id && 'publish' === get_post_status($terms_page_id)) {
	$terms_url = get_permalink($terms_page_id);
}
$address_optional = EQB_Settings::is_address_optional();
$email_optional   = EQB_Settings::is_email_optional();
$captcha_enabled  = EQB_Captcha::is_enabled();
?>
<div class="eqb-popup"
	data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>"
	data-unit-price="<?php echo esc_attr((string) $unit_price); ?>"
	data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>">
	<div class="eqb-popup__header">
		<h2 class="eqb-popup__title" id="eqb-popup-title"><?php echo esc_html($popup_title); ?></h2>
		<button type="button" class="eqb-popup__close" data-eqb-close aria-label="<?php esc_attr_e('Đóng', 'echbay-quick-buy'); ?>">&times;</button>
	</div>

	<div class="eqb-popup__body">
		<div class="eqb-popup__col eqb-popup__col--product">
			<?php if ($product->get_image_id()) : ?>
				<div class="eqb-popup__image">
					<?php echo wp_get_attachment_image($product->get_image_id(), 'woocommerce_thumbnail'); ?>
				</div>
			<?php endif; ?>

			<h3 class="eqb-popup__product-name"><?php echo esc_html($product->get_name()); ?></h3>
			<div class="eqb-popup__price" data-eqb-price><?php echo wp_kses_post($product->get_price_html()); ?></div>

			<?php if ($is_variable) : ?>
				<?php include EQB_PATH . 'templates/popup-variations.php'; ?>
			<?php endif; ?>

			<div class="eqb-qty-row">
				<span class="eqb-qty__label"><?php esc_html_e('Số lượng', 'echbay-quick-buy'); ?></span>
				<div class="eqb-qty">
					<button type="button" class="eqb-qty__btn" data-eqb-qty-minus aria-label="<?php esc_attr_e('Giảm', 'echbay-quick-buy'); ?>">−</button>
					<input type="number" class="eqb-qty__input" name="quantity" value="1" min="1" step="1" data-eqb-qty>
					<button type="button" class="eqb-qty__btn" data-eqb-qty-plus aria-label="<?php esc_attr_e('Tăng', 'echbay-quick-buy'); ?>">+</button>
				</div>
			</div>

			<?php if (! empty($options['phone_notice'])) : ?>
				<p class="eqb-popup__notice"><?php echo esc_html($options['phone_notice']); ?></p>
			<?php endif; ?>
		</div>

		<div class="eqb-popup__col eqb-popup__col--form">
			<h3 class="eqb-form__heading"><?php esc_html_e('Thông tin người mua', 'echbay-quick-buy'); ?></h3>

			<form class="eqb-form" data-eqb-form>
				<input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product->get_id()); ?>">
				<input type="hidden" name="variation_id" value="0" data-eqb-variation-id>
				<input type="text"
					name="<?php echo esc_attr(EQB_Honeypot::get_field_name(EQB_Honeypot::PREFIX_HONEYPOT)); ?>"
					value=""
					class="eqb-hp"
					data-eqb-hp
					tabindex="-1"
					autocomplete="off"
					aria-hidden="true">
				<input type="text"
					name="<?php echo esc_attr(EQB_Honeypot::get_field_name(EQB_Honeypot::PREFIX_SOURCE)); ?>"
					value=""
					class="eqb-hp"
					data-eqb-hp-source
					tabindex="-1"
					autocomplete="off"
					aria-hidden="true">

				<div class="eqb-field-row">
					<div class="eqb-field eqb-field--half">
						<input type="text" name="name" class="eqb-field__input" required autocomplete="name"
							placeholder="<?php esc_attr_e('Họ và tên *', 'echbay-quick-buy'); ?>"
							aria-label="<?php esc_attr_e('Họ và tên', 'echbay-quick-buy'); ?>">
					</div>
					<div class="eqb-field eqb-field--half">
						<input type="tel" name="phone" class="eqb-field__input" required autocomplete="tel" inputmode="tel"
							placeholder="<?php esc_attr_e('Số điện thoại *', 'echbay-quick-buy'); ?>"
							aria-label="<?php esc_attr_e('Số điện thoại', 'echbay-quick-buy'); ?>">
					</div>
				</div>

				<div class="eqb-field">
					<input type="email" name="email" class="eqb-field__input" autocomplete="email"
						<?php if ( ! $email_optional ) : ?>required<?php endif; ?>
						placeholder="<?php echo esc_attr( $email_optional
							? __( 'Địa chỉ email (Không bắt buộc)', 'echbay-quick-buy' )
							: __( 'Địa chỉ email *', 'echbay-quick-buy' )
						); ?>"
						aria-label="<?php esc_attr_e('Địa chỉ email', 'echbay-quick-buy'); ?>">
				</div>

				<div class="eqb-field-row eqb-field-row--3">
					<div class="eqb-field">
						<select name="ma_tinh" class="eqb-field__input" id="eqb-province" required data-eqb-province
							aria-label="<?php esc_attr_e('Tỉnh/Thành phố', 'echbay-quick-buy'); ?>">
							<option value=""><?php esc_html_e('Tỉnh/Thành phố *', 'echbay-quick-buy'); ?></option>
							<?php foreach ($provinces as $province) : ?>
								<option value="<?php echo esc_attr($province['ma_tinh']); ?>">
									<?php echo esc_html($province['ten_tinh']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="eqb-field eqb-field--span2">
						<select name="ma_xa" class="eqb-field__input" id="eqb-ward" required data-eqb-ward disabled
							aria-label="<?php esc_attr_e('Phường/Xã', 'echbay-quick-buy'); ?>">
							<option value=""><?php esc_html_e('Phường/Xã *', 'echbay-quick-buy'); ?></option>
						</select>
					</div>
				</div>

				<div class="eqb-field">
					<input type="text" name="address" class="eqb-field__input" autocomplete="street-address"
						<?php if ( ! $address_optional ) : ?>required<?php endif; ?>
						placeholder="<?php echo esc_attr( $address_optional
							? __( 'Số nhà, tên đường (Không bắt buộc)', 'echbay-quick-buy' )
							: __( 'Số nhà, tên đường *', 'echbay-quick-buy' )
						); ?>"
						aria-label="<?php esc_attr_e('Số nhà, tên đường', 'echbay-quick-buy'); ?>">
				</div>

				<div class="eqb-field">
					<textarea name="note" class="eqb-field__input eqb-field__textarea" rows="3"
						placeholder="<?php esc_attr_e('Ghi chú đơn hàng (Không bắt buộc)', 'echbay-quick-buy'); ?>"
						aria-label="<?php esc_attr_e('Ghi chú đơn hàng', 'echbay-quick-buy'); ?>"></textarea>
				</div>

				<?php if (! empty($gateways)) : ?>
					<fieldset class="eqb-field eqb-field--payment">
						<legend class="eqb-field__label"><?php esc_html_e('Hình thức thanh toán', 'echbay-quick-buy'); ?></legend>
						<?php
						$first = true;
						foreach ($gateways as $gid => $gateway) :
						?>
							<label class="eqb-payment-option">
								<input type="radio" name="payment_method" value="<?php echo esc_attr($gid); ?>" <?php checked($first); ?>>
								<?php echo esc_html($gateway->get_title()); ?>
							</label>
						<?php
							$first = false;
						endforeach;
						?>
					</fieldset>
				<?php endif; ?>

				<div class="eqb-form__total">
					<span><?php esc_html_e('Tổng:', 'echbay-quick-buy'); ?></span>
					<strong data-eqb-total><?php echo wp_kses_post(wc_price($unit_price)); ?></strong>
				</div>

				<div class="eqb-form__message eqb-hidden" data-eqb-message></div>

				<?php if ( $captcha_enabled ) : ?>
					<div class="eqb-field eqb-field--captcha">
						<div data-eqb-captcha></div>
					</div>
				<?php endif; ?>

				<label class="eqb-form__consent">
					<input type="checkbox"
						name="<?php echo esc_attr(EQB_Honeypot::get_field_name(EQB_Honeypot::PREFIX_CONSENT)); ?>"
						value="<?php echo esc_attr(EQB_Honeypot::CONSENT_VALUE); ?>"
						data-eqb-consent
						<?php checked( $captcha_enabled ); ?>>
					<span>
						<?php
						if ($terms_url) {
							$terms_link = sprintf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								esc_url($terms_url),
								esc_html__('Điều khoản & Điều kiện', 'echbay-quick-buy')
							);
							echo wp_kses_post(
								sprintf(
									/* translators: %s: terms and conditions link */
									__('Tôi đồng ý tuân theo %s của website.', 'echbay-quick-buy'),
									$terms_link
								)
							);
						} else {
							esc_html_e('Tôi đồng ý tuân theo Điều khoản & Điều kiện của website.', 'echbay-quick-buy');
						}
						?>
					</span>
				</label>

				<button type="submit" class="eqb-form__submit eqb-order-form-ui">
					<?php esc_html_e('ĐẶT HÀNG NGAY', 'echbay-quick-buy'); ?>
				</button>
				<button type="button" class="eqb-form__done-close eqb-hidden" data-eqb-close data-eqb-done-close>
					<?php esc_html_e('Đóng', 'echbay-quick-buy'); ?>
				</button>
			</form>
		</div>
	</div>
</div>