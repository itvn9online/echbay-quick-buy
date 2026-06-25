<?php
/**
 * Biến thể sản phẩm trong popup (không dùng thẻ form — tránh lồng form).
 *
 * @var WC_Product_Variable $product
 */
defined( 'ABSPATH' ) || exit;

if ( ! $product->is_type( 'variable' ) ) {
	return;
}

$attributes           = $product->get_variation_attributes();
$available_variations = $product->get_available_variations();
$variations_json      = wp_json_encode( $available_variations );
?>
<!-- <div class="eqb-variations-label"><?php esc_html_e( 'Phân loại sản phẩm', 'echbay-quick-buy' ); ?></div> -->
<div class="variations_form cart eqb-variations"
	data-product_id="<?php echo absint( $product->get_id() ); ?>"
	data-product_variations="<?php echo $variations_json ? esc_attr( $variations_json ) : esc_attr( '[]' ); ?>">
	<table class="variations eqb-variations__table" cellspacing="0" role="presentation">
		<tbody>
		<?php foreach ( $attributes as $attribute_name => $options ) : ?>
			<tr>
				<th class="label">
					<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
						<?php echo esc_html( wc_attribute_label( $attribute_name ) ); ?>
					</label>
				</th>
				<td class="value">
					<?php
					wc_dropdown_variation_attribute_options(
						array(
							'options'   => $options,
							'attribute' => $attribute_name,
							'product'   => $product,
						)
					);
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<div class="single_variation_wrap eqb-hidden">
		<div class="woocommerce-variation single_variation"></div>
	</div>
</div>
