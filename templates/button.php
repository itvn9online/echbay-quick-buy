<?php
defined( 'ABSPATH' ) || exit;
?>
<button type="button"
		class="eqb-buy-btn"
		data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
	<span class="eqb-buy-btn__title"><?php echo esc_html( $options['button_title'] ); ?></span>
	<span class="eqb-buy-btn__sub"><?php echo esc_html( $options['button_subtitle'] ); ?></span>
</button>
