<?php
/**
 * Third-party CAPTCHA (Google reCAPTCHA v2/v3, Cloudflare Turnstile).
 */

defined( 'ABSPATH' ) || exit;

class EQB_Captcha {

	const PROVIDER_OFF          = 'off';
	const PROVIDER_RECAPTCHA      = 'google_recaptcha';
	const PROVIDER_RECAPTCHA_V3   = 'google_recaptcha_v3';
	const PROVIDER_TURNSTILE      = 'cloudflare_turnstile';

	const RECAPTCHA_V3_ACTION = 'eqb_quick_buy';

	const POST_TOKEN    = 'eqb_captcha_token';
	const POST_PROVIDER = 'eqb_captcha_provider';

	/**
	 * @return string[]
	 */
	public static function get_providers() {
		return array(
			self::PROVIDER_OFF,
			self::PROVIDER_RECAPTCHA,
			self::PROVIDER_RECAPTCHA_V3,
			self::PROVIDER_TURNSTILE,
		);
	}

	/**
	 * @return string
	 */
	public static function get_provider() {
		$provider = (string) EQB_Settings::get( 'captcha_provider', self::PROVIDER_OFF );

		if ( ! in_array( $provider, self::get_providers(), true ) ) {
			return self::PROVIDER_OFF;
		}

		return $provider;
	}

	public static function is_enabled() {
		$provider = self::get_provider();

		if ( self::PROVIDER_OFF === $provider ) {
			return false;
		}

		return '' !== self::get_site_key() && '' !== self::get_secret_key();
	}

	/**
	 * @return string
	 */
	public static function get_site_key() {
		$provider = self::get_provider();

		if ( self::PROVIDER_RECAPTCHA === $provider ) {
			return trim( (string) EQB_Settings::get( 'recaptcha_site_key', '' ) );
		}

		if ( self::PROVIDER_RECAPTCHA_V3 === $provider ) {
			return trim( (string) EQB_Settings::get( 'recaptcha_v3_site_key', '' ) );
		}

		if ( self::PROVIDER_TURNSTILE === $provider ) {
			return trim( (string) EQB_Settings::get( 'turnstile_site_key', '' ) );
		}

		return '';
	}

	/**
	 * @return string
	 */
	private static function get_secret_key() {
		$provider = self::get_provider();

		if ( self::PROVIDER_RECAPTCHA === $provider ) {
			return trim( (string) EQB_Settings::get( 'recaptcha_secret_key', '' ) );
		}

		if ( self::PROVIDER_RECAPTCHA_V3 === $provider ) {
			return trim( (string) EQB_Settings::get( 'recaptcha_v3_secret_key', '' ) );
		}

		if ( self::PROVIDER_TURNSTILE === $provider ) {
			return trim( (string) EQB_Settings::get( 'turnstile_secret_key', '' ) );
		}

		return '';
	}

	public static function enqueue_scripts() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$provider = self::get_provider();

		if ( self::PROVIDER_RECAPTCHA === $provider ) {
			wp_enqueue_script(
				'google-recaptcha',
				'https://www.google.com/recaptcha/api.js?render=explicit',
				array(),
				null,
				true
			);
			return;
		}

		if ( self::PROVIDER_RECAPTCHA_V3 === $provider ) {
			$site_key = self::get_site_key();
			if ( '' === $site_key ) {
				return;
			}

			wp_enqueue_script(
				'google-recaptcha-v3',
				'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ),
				array(),
				null,
				true
			);
			return;
		}

		if ( self::PROVIDER_TURNSTILE === $provider ) {
			wp_enqueue_script(
				'cloudflare-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
				array(),
				null,
				true
			);
		}
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_frontend_config() {
		if ( ! self::is_enabled() ) {
			return array(
				'provider' => self::PROVIDER_OFF,
				'site_key' => '',
				'action'   => '',
			);
		}

		$config = array(
			'provider' => self::get_provider(),
			'site_key' => self::get_site_key(),
			'action'   => '',
		);

		if ( self::PROVIDER_RECAPTCHA_V3 === $config['provider'] ) {
			$config['action'] = self::RECAPTCHA_V3_ACTION;
		}

		return $config;
	}

	/**
	 * @param array $post Raw POST data.
	 * @return true|WP_Error
	 */
	public static function validate( $post ) {
		if ( ! self::is_enabled() ) {
			return true;
		}

		if ( ! is_array( $post ) ) {
			return self::reject();
		}

		$token = isset( $post[ self::POST_TOKEN ] ) ? trim( wp_unslash( (string) $post[ self::POST_TOKEN ] ) ) : '';

		if ( '' === $token ) {
			return self::reject(
				__( 'Vui lòng hoàn thành xác minh CAPTCHA trước khi đặt hàng.', 'echbay-quick-buy' )
			);
		}

		$provider = self::get_provider();
		$posted_provider = isset( $post[ self::POST_PROVIDER ] ) ? sanitize_text_field( wp_unslash( (string) $post[ self::POST_PROVIDER ] ) ) : '';

		if ( $posted_provider !== $provider ) {
			return self::reject();
		}

		if ( self::PROVIDER_RECAPTCHA === $provider ) {
			return self::verify_recaptcha( $token );
		}

		if ( self::PROVIDER_RECAPTCHA_V3 === $provider ) {
			return self::verify_recaptcha_v3( $token );
		}

		if ( self::PROVIDER_TURNSTILE === $provider ) {
			return self::verify_turnstile( $token );
		}

		return self::reject();
	}

	/**
	 * @param string $token Response token from widget.
	 * @return true|WP_Error
	 */
	private static function verify_recaptcha( $token ) {
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => self::get_secret_key(),
					'response' => $token,
					'remoteip' => self::get_client_ip(),
				),
			)
		);

		return self::parse_remote_response( $response );
	}

	/**
	 * @param string $token Response token from grecaptcha.execute().
	 * @return true|WP_Error
	 */
	private static function verify_recaptcha_v3( $token ) {
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => self::get_secret_key(),
					'response' => $token,
					'remoteip' => self::get_client_ip(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::reject(
				__( 'Không thể xác minh CAPTCHA. Vui lòng thử lại sau.', 'echbay-quick-buy' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) || empty( $body['success'] ) ) {
			return self::reject(
				__( 'Xác minh CAPTCHA không hợp lệ. Vui lòng thử lại.', 'echbay-quick-buy' )
			);
		}

		$action = isset( $body['action'] ) ? (string) $body['action'] : '';
		if ( self::RECAPTCHA_V3_ACTION !== $action ) {
			return self::reject();
		}

		$score     = isset( $body['score'] ) ? (float) $body['score'] : 0.0;
		$threshold = self::get_recaptcha_v3_score_threshold();

		if ( $score < $threshold ) {
			return self::reject(
				__( 'Xác minh CAPTCHA không đạt ngưỡng tin cậy. Vui lòng thử lại.', 'echbay-quick-buy' )
			);
		}

		return true;
	}

	/**
	 * @return float Score threshold between 0.0 and 1.0.
	 */
	public static function get_recaptcha_v3_score_threshold() {
		$score = (float) EQB_Settings::get( 'recaptcha_v3_score', '0.5' );

		if ( $score < 0.0 ) {
			$score = 0.0;
		}
		if ( $score > 1.0 ) {
			$score = 1.0;
		}

		return $score;
	}

	/**
	 * @param string $token Response token from widget.
	 * @return true|WP_Error
	 */
	private static function verify_turnstile( $token ) {
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => self::get_secret_key(),
					'response' => $token,
					'remoteip' => self::get_client_ip(),
				),
			)
		);

		return self::parse_remote_response( $response );
	}

	/**
	 * @param array|WP_Error $response HTTP response.
	 * @return true|WP_Error
	 */
	private static function parse_remote_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return self::reject(
				__( 'Không thể xác minh CAPTCHA. Vui lòng thử lại sau.', 'echbay-quick-buy' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) || empty( $body['success'] ) ) {
			return self::reject(
				__( 'Xác minh CAPTCHA không hợp lệ. Vui lòng thử lại.', 'echbay-quick-buy' )
			);
		}

		return true;
	}

	/**
	 * @return string
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * @param string $message Optional custom message.
	 * @return WP_Error
	 */
	private static function reject( $message = '' ) {
		if ( '' === $message ) {
			$message = EQB_Settings::get( 'error_message' );
		}

		return new WP_Error( 'eqb_captcha', $message );
	}
}
