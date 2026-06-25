<?php
/**
 * Cập nhật plugin từ GitHub Releases (itvn9online/echbay-quick-buy).
 */

defined( 'ABSPATH' ) || exit;

class EQB_Updater {

	const GITHUB_USER = 'itvn9online';
	const GITHUB_REPO = 'echbay-quick-buy';
	const CACHE_KEY     = 'eqb_github_release';
	const CACHE_TTL     = 12 * HOUR_IN_SECONDS;

	/**
	 * @return void
	 */
	public static function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_dir' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * @return object|null
	 */
	private static function get_release() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_object( $cached ) ? $cached : null;
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			self::GITHUB_USER,
			self::GITHUB_REPO
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => self::github_user_agent(),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::CACHE_KEY, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! is_object( $data ) || empty( $data->tag_name ) ) {
			set_transient( self::CACHE_KEY, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$tag      = ltrim( (string) $data->tag_name, 'vV' );
		$tag_name = (string) $data->tag_name;

		$release = (object) array(
			'version'      => $tag,
			'tag_name'     => $tag_name,
			'download_url' => sprintf(
				'https://github.com/%s/%s/archive/refs/tags/%s.zip',
				self::GITHUB_USER,
				self::GITHUB_REPO,
				rawurlencode( $tag_name )
			),
			'homepage'     => isset( $data->html_url ) ? (string) $data->html_url : '',
			'changelog'    => isset( $data->body ) ? (string) $data->body : '',
			'published_at' => isset( $data->published_at ) ? (string) $data->published_at : '',
		);

		set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * @param object $transient Site transient.
	 * @return object
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = self::get_release();
		if ( ! $release || empty( $release->version ) ) {
			return $transient;
		}

		if ( version_compare( EQB_VERSION, $release->version, '>=' ) ) {
			return $transient;
		}

		$plugin_file = EQB_BASENAME;

		$transient->response[ $plugin_file ] = (object) array(
			'slug'        => dirname( $plugin_file ),
			'plugin'      => $plugin_file,
			'new_version' => $release->version,
			'url'         => $release->homepage,
			'package'     => $release->download_url,
			'tested'      => get_bloginfo( 'version' ),
			'requires'    => '6.0',
			'requires_php' => '7.4',
		);

		return $transient;
	}

	/**
	 * @param false|object|array $result API result.
	 * @param string             $action API action.
	 * @param object             $args   Query args.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}

		if ( dirname( EQB_BASENAME ) !== $args->slug ) {
			return $result;
		}

		$release = self::get_release();
		if ( ! $release ) {
			return $result;
		}

		$changelog = trim( $release->changelog );
		if ( '' === $changelog ) {
			$changelog = sprintf(
				/* translators: %s: version number */
				__( 'Phiên bản %s.', 'echbay-quick-buy' ),
				$release->version
			);
		}

		return (object) array(
			'name'          => 'Echbay Quick Buy',
			'slug'          => dirname( EQB_BASENAME ),
			'version'       => $release->version,
			'author'        => '<a href="https://echbay.com/">Dao Quoc Dai</a>',
			'homepage'      => 'https://echbay.com/',
			'download_link' => $release->download_url,
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'tested'        => get_bloginfo( 'version' ),
			'last_updated'  => $release->published_at,
			'sections'      => array(
				'description' => __( 'Nút Mua ngay trên trang sản phẩm WooCommerce — popup đặt hàng nhanh qua AJAX.', 'echbay-quick-buy' ),
				'changelog'   => wpautop( esc_html( $changelog ) ),
			),
		);
	}

	/**
	 * GitHub zip giải nén vào thư mục echbay-quick-buy-1.1.x — đổi tên cho khớp slug plugin.
	 *
	 * @param string                           $source       Đường dẫn thư mục nguồn.
	 * @param string                           $remote_source Thư mục tạm.
	 * @param WP_Upgrader                      $upgrader     Upgrader instance.
	 * @param array<string, mixed>             $hook_extra   Extra args.
	 * @return string|WP_Error
	 */
	public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || EQB_BASENAME !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return $source;
		}

		$plugin_slug = dirname( EQB_BASENAME );
		$main_file   = trailingslashit( $source ) . basename( EQB_BASENAME );

		if ( ! $wp_filesystem->exists( $main_file ) ) {
			return new WP_Error(
				'eqb_invalid_package',
				__( 'Gói cập nhật không hợp lệ: thiếu file plugin chính.', 'echbay-quick-buy' )
			);
		}

		if ( basename( $source ) === $plugin_slug ) {
			return $source;
		}

		$new_source = trailingslashit( $remote_source ) . $plugin_slug;

		if ( $wp_filesystem->exists( $new_source ) ) {
			$wp_filesystem->delete( $new_source, true );
		}

		$moved = $wp_filesystem->move( $source, $new_source, true );
		if ( is_wp_error( $moved ) ) {
			return $moved;
		}

		return $new_source;
	}

	/**
	 * @param string[] $links Plugin row meta links.
	 * @param string   $file  Plugin basename.
	 * @return string[]
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( EQB_BASENAME !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url(
				sprintf(
					'https://github.com/%s/%s/releases',
					self::GITHUB_USER,
					self::GITHUB_REPO
				)
			),
			esc_html__( 'Releases', 'echbay-quick-buy' )
		);

		return $links;
	}

	/**
	 * @return string
	 */
	private static function github_user_agent() {
		return sprintf(
			'WordPress/%s; %s; Echbay-Quick-Buy/%s',
			get_bloginfo( 'version' ),
			home_url( '/' ),
			EQB_VERSION
		);
	}
}
