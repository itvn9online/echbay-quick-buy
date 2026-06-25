<?php
/**
 * Anti-spam hidden fields for quick buy form.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Honeypot {

	const PREFIX_HONEYPOT = 'company';
	const PREFIX_SOURCE   = 'source';
	const PREFIX_CONSENT  = 'consent';
	const CONSENT_VALUE   = '1';

	/**
	 * @param string $prefix Field prefix (e.g. company, source).
	 * @param string $date   Date in Y-m-d format (site timezone).
	 */
	private static function build_field_name( $prefix, $date ) {
		return $prefix . '_' . md5( $date . wp_salt( 'eqb_honeypot' ) );
	}

	/**
	 * @param string $prefix Field prefix.
	 * @return string
	 */
	public static function get_field_name( $prefix ) {
		return self::build_field_name( $prefix, wp_date( 'Y-m-d' ) );
	}

	/**
	 * @param array $post Raw POST data.
	 * @return true|WP_Error
	 */
	public static function validate( $post ) {
		$honeypot = self::validate_empty_field( $post, self::PREFIX_HONEYPOT );
		if ( is_wp_error( $honeypot ) ) {
			return $honeypot;
		}

		$source = self::validate_source_url( $post );
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		$consent = self::validate_consent( $post );
		if ( is_wp_error( $consent ) ) {
			return $consent;
		}

		return true;
	}

	/**
	 * Hidden field must exist and stay empty (classic honeypot).
	 *
	 * @param array  $post   Raw POST data.
	 * @param string $prefix Field prefix.
	 * @return true|WP_Error
	 */
	private static function validate_empty_field( $post, $prefix ) {
		$field = self::get_field_name( $prefix );

		if ( ! is_array( $post ) || ! array_key_exists( $field, $post ) ) {
			return self::reject();
		}

		$value = is_string( $post[ $field ] ) ? trim( wp_unslash( $post[ $field ] ) ) : '';

		if ( '' !== $value ) {
			return self::reject();
		}

		return true;
	}

	/**
	 * Source URL field must be filled on real interaction and contain product permalink.
	 *
	 * @param array $post Raw POST data.
	 * @return true|WP_Error
	 */
	private static function validate_source_url( $post ) {
		$field = self::get_field_name( self::PREFIX_SOURCE );

		if ( ! is_array( $post ) || ! array_key_exists( $field, $post ) ) {
			return self::reject();
		}

		$value = is_string( $post[ $field ] ) ? trim( wp_unslash( $post[ $field ] ) ) : '';

		if ( '' === $value ) {
			return self::reject();
		}

		$product_id = isset( $post['product_id'] ) ? absint( $post['product_id'] ) : 0;
		if ( ! $product_id ) {
			return self::reject();
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return self::reject();
		}

		$product_url = get_permalink( $product->get_id() );
		if ( ! $product_url || ! self::url_contains_product( $value, $product_url ) ) {
			return self::reject();
		}

		return true;
	}

	/**
	 * Consent checkbox must be checked (field present with value "1").
	 *
	 * @param array $post Raw POST data.
	 * @return true|WP_Error
	 */
	private static function validate_consent( $post ) {
		$field = self::get_field_name( self::PREFIX_CONSENT );

		if ( ! is_array( $post ) || ! array_key_exists( $field, $post ) ) {
			return self::reject();
		}

		$value = is_string( $post[ $field ] ) ? trim( wp_unslash( $post[ $field ] ) ) : '';

		if ( self::CONSENT_VALUE !== $value ) {
			return self::reject();
		}

		return true;
	}

	/**
	 * @param string $submitted_url URL sent from browser.
	 * @param string $product_url   Product permalink from database.
	 */
	private static function url_contains_product( $submitted_url, $product_url ) {
		if ( false !== stripos( $submitted_url, $product_url ) ) {
			return true;
		}

		$path = wp_parse_url( $product_url, PHP_URL_PATH );
		if ( $path && false !== stripos( $submitted_url, $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return WP_Error
	 */
	private static function reject() {
		return new WP_Error(
			'eqb_honeypot',
			EQB_Settings::get( 'error_message' )
		);
	}
}
