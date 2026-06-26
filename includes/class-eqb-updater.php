<?php
/**
 * Cập nhật plugin từ nhánh main trên GitHub (itvn9online/echbay-quick-buy).
 */

defined( 'ABSPATH' ) || exit;

class EQB_Updater {

	const GITHUB_USER  = 'itvn9online';
	const GITHUB_REPO  = 'echbay-quick-buy';
	const GITHUB_BRANCH = 'main';
	const CACHE_KEY    = 'eqb_github_main_update';
	const CACHE_TTL    = 12 * HOUR_IN_SECONDS;

	/** @var string[] */
	const VERSION_FILES = array( 'version.txt', 'VERSION' );

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
		add_filter( 'upgrader_post_install', array( __CLASS__, 'post_install' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Slug thư mục plugin chuẩn (không có hậu tố -main).
	 *
	 * @return string
	 */
	private static function plugin_slug() {
		return self::GITHUB_REPO;
	}

	/**
	 * @param string $dirname Tên thư mục.
	 * @return string
	 */
	private static function strip_main_suffix( $dirname ) {
		$suffix = '-main';

		if ( strlen( $dirname ) > strlen( $suffix ) && substr( $dirname, -strlen( $suffix ) ) === $suffix ) {
			return substr( $dirname, 0, -strlen( $suffix ) );
		}

		return $dirname;
	}

	/**
	 * @return string
	 */
	private static function get_local_version() {
		foreach ( self::VERSION_FILES as $filename ) {
			$path = EQB_PATH . $filename;
			if ( ! is_readable( $path ) ) {
				continue;
			}

			$version = trim( (string) file_get_contents( $path ) );
			if ( '' !== $version ) {
				return ltrim( $version, 'vV' );
			}
		}

		return EQB_VERSION;
	}

	/**
	 * @return string|null
	 */
	private static function fetch_remote_version() {
		foreach ( self::VERSION_FILES as $filename ) {
			$url = sprintf(
				'https://raw.githubusercontent.com/%s/%s/refs/heads/%s/%s',
				self::GITHUB_USER,
				self::GITHUB_REPO,
				self::GITHUB_BRANCH,
				$filename
			);

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 15,
					'headers' => array(
						'User-Agent' => self::github_user_agent(),
					),
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}

			$version = trim( (string) wp_remote_retrieve_body( $response ) );
			if ( '' !== $version ) {
				return ltrim( $version, 'vV' );
			}
		}

		return null;
	}

	/**
	 * @return object|null
	 */
	private static function get_remote_update() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_object( $cached ) ? $cached : null;
		}

		$version = self::fetch_remote_version();
		if ( ! $version ) {
			set_transient( self::CACHE_KEY, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$update = (object) array(
			'version'      => $version,
			'download_url' => sprintf(
				'https://github.com/%s/%s/archive/refs/heads/%s.zip',
				self::GITHUB_USER,
				self::GITHUB_REPO,
				self::GITHUB_BRANCH
			),
			'homepage'     => sprintf(
				'https://github.com/%s/%s',
				self::GITHUB_USER,
				self::GITHUB_REPO
			),
		);

		set_transient( self::CACHE_KEY, $update, self::CACHE_TTL );

		return $update;
	}

	/**
	 * @param object $transient Site transient.
	 * @return object
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$remote = self::get_remote_update();
		if ( ! $remote || empty( $remote->version ) ) {
			return $transient;
		}

		if ( version_compare( self::get_local_version(), $remote->version, '>=' ) ) {
			return $transient;
		}

		$plugin_file = EQB_BASENAME;

		$transient->response[ $plugin_file ] = (object) array(
			'slug'         => self::plugin_slug(),
			'plugin'       => $plugin_file,
			'new_version'  => $remote->version,
			'url'          => $remote->homepage,
			'package'      => $remote->download_url,
			'tested'       => get_bloginfo( 'version' ),
			'requires'     => '6.0',
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

		if ( self::plugin_slug() !== $args->slug && self::strip_main_suffix( $args->slug ) !== self::plugin_slug() ) {
			return $result;
		}

		$remote = self::get_remote_update();
		if ( ! $remote ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Echbay Quick Buy',
			'slug'          => self::plugin_slug(),
			'version'       => $remote->version,
			'author'        => '<a href="https://echbay.com/">Dao Quoc Dai</a>',
			'homepage'      => 'https://echbay.com/',
			'download_link' => $remote->download_url,
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'tested'        => get_bloginfo( 'version' ),
			'sections'      => array(
				'description' => __( 'Nút Mua ngay trên trang sản phẩm WooCommerce — popup đặt hàng nhanh qua AJAX.', 'echbay-quick-buy' ),
				'changelog'   => sprintf(
					/* translators: %s: version number */
					'<p>' . esc_html__( 'Phiên bản %s từ nhánh main trên GitHub.', 'echbay-quick-buy' ) . '</p>',
					esc_html( $remote->version )
				),
			),
		);
	}

	/**
	 * GitHub zip giải nén vào echbay-quick-buy-main — đổi tên bỏ hậu tố -main.
	 *
	 * @param string               $source        Đường dẫn thư mục nguồn.
	 * @param string               $remote_source Thư mục tạm.
	 * @param WP_Upgrader          $upgrader      Upgrader instance.
	 * @param array<string, mixed> $hook_extra    Extra args.
	 * @return string|WP_Error
	 */
	public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || ! self::is_our_plugin( $hook_extra['plugin'] ) ) {
			return $source;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return $source;
		}

		$plugin_slug = self::plugin_slug();
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
	 * Dọn thư mục cũ có hậu tố -main và cập nhật active_plugins nếu đổi slug.
	 *
	 * @param bool|WP_Error        $response   Install response.
	 * @param array<string, mixed> $hook_extra Extra args.
	 * @param array<string, mixed> $result     Install result.
	 * @return bool|WP_Error
	 */
	public static function post_install( $response, $hook_extra, $result ) {
		if ( is_wp_error( $response ) || empty( $hook_extra['plugin'] ) || ! self::is_our_plugin( $hook_extra['plugin'] ) ) {
			return $response;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return $response;
		}

		$old_plugin = (string) $hook_extra['plugin'];
		$old_slug   = dirname( $old_plugin );
		$new_slug   = self::plugin_slug();
		$new_plugin = $new_slug . '/' . basename( $old_plugin );

		if ( $old_slug !== $new_slug && self::strip_main_suffix( $old_slug ) === $new_slug ) {
			$old_path = trailingslashit( WP_PLUGIN_DIR ) . $old_slug;
			if ( $wp_filesystem->exists( $old_path ) ) {
				$wp_filesystem->delete( $old_path, true );
			}

			$active = get_option( 'active_plugins', array() );
			if ( is_array( $active ) ) {
				$changed = false;
				foreach ( $active as $index => $plugin ) {
					if ( $plugin === $old_plugin ) {
						$active[ $index ] = $new_plugin;
						$changed          = true;
					}
				}
				if ( $changed ) {
					update_option( 'active_plugins', $active );
				}
			}
		}

		return $response;
	}

	/**
	 * @param string $plugin_file Plugin basename.
	 * @return bool
	 */
	private static function is_our_plugin( $plugin_file ) {
		if ( EQB_BASENAME === $plugin_file ) {
			return true;
		}

		$canonical = self::plugin_slug() . '/' . basename( EQB_BASENAME );

		return $canonical === $plugin_file;
	}

	/**
	 * @param string[] $links Plugin row meta links.
	 * @param string   $file  Plugin basename.
	 * @return string[]
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( ! self::is_our_plugin( $file ) ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url(
				sprintf(
					'https://github.com/%s/%s',
					self::GITHUB_USER,
					self::GITHUB_REPO
				)
			),
			esc_html__( 'GitHub', 'echbay-quick-buy' )
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
			self::get_local_version()
		);
	}
}
