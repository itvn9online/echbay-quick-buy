<?php
/**
 * Địa chỉ 2 cấp: đọc DB, cache file PHP trong data/cache/.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Address {

	const CACHE_DIR = 'data/cache';
	const TRANSIENT_TTL = DAY_IN_SECONDS;

	public static function cache_dir() {
		return EQB_PATH . self::CACHE_DIR . '/';
	}

	public static function get_provinces() {
		$file = self::cache_dir() . 'vn_tinh_thanh34.php';
		$data = self::load_cache_file( $file );

		if ( is_array( $data ) ) {
			return $data;
		}

		$data = self::query_provinces();
		self::write_cache_file( $file, $data );

		if ( empty( $data ) ) {
			$data = self::get_transient( 'provinces' );
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Danh sách tỉnh/thành cho hiển thị (sorted).
	 *
	 * @return array<int, array{ma_tinh:string, ten_tinh:string}>
	 */
	public static function get_provinces_list() {
		$provinces = self::get_provinces();
		$list      = array();

		foreach ( $provinces as $ma_tinh => $row ) {
			$list[] = array(
				'ma_tinh'  => (string) $ma_tinh,
				'ten_tinh' => (string) ( $row['ten_tinh'] ?? '' ),
			);
		}

		usort(
			$list,
			static function ( $a, $b ) {
				$cmp = strcmp( $a['ten_tinh'], $b['ten_tinh'] );
				if ( 0 !== $cmp ) {
					return $cmp;
				}
				return strcmp( $a['ma_tinh'], $b['ma_tinh'] );
			}
		);

		return $list;
	}

	public static function get_wards( $ma_tinh ) {
		$ma_tinh = self::sanitize_ma_tinh( $ma_tinh );
		if ( '' === $ma_tinh ) {
			return array();
		}

		$file = self::cache_dir() . 'vn_phuong_xa34_' . $ma_tinh . '.php';
		$data = self::load_cache_file( $file );

		if ( is_array( $data ) ) {
			return $data;
		}

		$data = self::query_wards( $ma_tinh );

		if ( ! empty( $data ) ) {
			self::write_cache_file( $file, $data );
		} else {
			self::set_transient( 'wards_' . $ma_tinh, array() );
		}

		if ( empty( $data ) ) {
			$cached = self::get_transient( 'wards_' . $ma_tinh );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		return $data;
	}

	/**
	 * Danh sách phường/xã cho AJAX (sorted).
	 *
	 * @return array<int, array{ma_xa:string, ten_xa:string}>
	 */
	public static function get_wards_list( $ma_tinh ) {
		$wards = self::get_wards( $ma_tinh );
		$list  = array();

		foreach ( $wards as $ma_xa => $row ) {
			$list[] = array(
				'ma_xa'  => (string) $ma_xa,
				'ten_xa' => (string) ( $row['ten_xa'] ?? '' ),
			);
		}

		usort(
			$list,
			static function ( $a, $b ) {
				$cmp = strcmp( $a['ten_xa'], $b['ten_xa'] );
				if ( 0 !== $cmp ) {
					return $cmp;
				}
				return strcmp( $a['ma_xa'], $b['ma_xa'] );
			}
		);

		return $list;
	}

	public static function flush_cache() {
		$dir = self::cache_dir();
		if ( is_dir( $dir ) ) {
			$files = glob( $dir . '*.php' );
			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_eqb_addr_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_eqb_addr_' ) . '%'
			)
		);

		return true;
	}

	/**
	 * Đăng ký hook dùng chung (checkout + admin order).
	 */
	public static function init() {
		add_filter( 'woocommerce_states', array( __CLASS__, 'woocommerce_states' ), 99999 );
	}

	/**
	 * @param array<string, array<string, string>> $states Country states.
	 */
	public static function woocommerce_states( $states ) {
		if ( ! EQB_Install::is_installed() ) {
			return $states;
		}

		$states['VN'] = self::get_province_state_labels();

		return $states;
	}

	/**
	 * @return array<string, string> ma_tinh => ten_tinh
	 */
	public static function get_province_state_labels() {
		$labels = array();

		foreach ( self::get_provinces_list() as $province ) {
			$labels[ $province['ma_tinh'] ] = $province['ten_tinh'];
		}

		return $labels;
	}

	/**
	 * @param string $ma_tinh  Province code.
	 * @param string $selected Selected ward code.
	 * @return array<string, string>
	 */
	public static function get_ward_select_options( $ma_tinh, $selected = '' ) {
		$options = array( '' => __( 'Chọn phường/xã', 'echbay-quick-buy' ) );
		$ma_tinh = self::sanitize_ma_tinh( $ma_tinh );

		if ( '' !== $ma_tinh ) {
			foreach ( self::get_wards_list( $ma_tinh ) as $ward ) {
				$options[ $ward['ma_xa'] ] = $ward['ten_xa'];
			}
		}

		$selected = sanitize_text_field( (string) $selected );
		if ( '' !== $selected && ! isset( $options[ $selected ] ) ) {
			$wards = self::get_wards( $ma_tinh );
			if ( isset( $wards[ $selected ]['ten_xa'] ) ) {
				$options[ $selected ] = (string) $wards[ $selected ]['ten_xa'];
			}
		}

		return $options;
	}

	public static function sanitize_ma_tinh( $ma_tinh ) {
		$ma_tinh = sanitize_text_field( (string) $ma_tinh );
		if ( ! preg_match( '/^[0-9A-Za-z]{1,10}$/', $ma_tinh ) ) {
			return '';
		}
		return $ma_tinh;
	}

	private static function query_provinces() {
		global $wpdb;

		if ( ! EQB_Install::is_installed() ) {
			return array();
		}

		$table = EQB_Install::TABLE_TINH;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT ma_tinh, ten_tinh FROM `{$table}` ORDER BY ten_tinh ASC, ma_tinh ASC", ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$data = array();
		foreach ( $rows as $row ) {
			$key = (string) $row['ma_tinh'];
			$data[ $key ] = array(
				'ten_tinh' => (string) $row['ten_tinh'],
			);
		}

		self::set_transient( 'provinces', $data );
		return $data;
	}

	private static function query_wards( $ma_tinh ) {
		global $wpdb;

		if ( ! EQB_Install::is_installed() ) {
			return array();
		}

		$table = EQB_Install::TABLE_XA;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ma_xa, ten_xa FROM `{$table}` WHERE ma_tinh = %s ORDER BY ten_xa ASC, ma_xa ASC",
				$ma_tinh
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$data = array();
		foreach ( $rows as $row ) {
			$key = (string) $row['ma_xa'];
			$data[ $key ] = array(
				'ten_xa' => (string) $row['ten_xa'],
			);
		}

		self::set_transient( 'wards_' . $ma_tinh, $data );
		return $data;
	}

	private static function load_cache_file( $file ) {
		if ( ! is_readable( $file ) ) {
			return null;
		}

		$data = include $file;
		return is_array( $data ) ? $data : null;
	}

	private static function write_cache_file( $file, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		wp_mkdir_p( dirname( $file ) );

		$content = "<?php\ndefined( 'ABSPATH' ) || exit;\n\nreturn " . var_export( $data, true ) . ";\n";

		if ( false !== @file_put_contents( $file, $content, LOCK_EX ) ) {
			return true;
		}

		return false;
	}

	private static function transient_key( $suffix ) {
		return 'eqb_addr_' . $suffix;
	}

	private static function set_transient( $suffix, $data ) {
		set_transient( self::transient_key( $suffix ), $data, self::TRANSIENT_TTL );
	}

	private static function get_transient( $suffix ) {
		$data = get_transient( self::transient_key( $suffix ) );
		return is_array( $data ) ? $data : null;
	}
}
