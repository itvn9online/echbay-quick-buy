<?php
/**
 * Cài bảng địa chỉ VN (vn_tinh_thanh34, vn_phuong_xa34) từ file SQL khi activate.
 */

defined( 'ABSPATH' ) || exit;

class EQB_Install {

	const DB_VERSION_OPTION = 'eqb_address_db_version';
	const DB_VERSION        = '1';

	const TABLE_TINH = 'vn_tinh_thanh34';
	const TABLE_XA   = 'vn_phuong_xa34';

	/**
	 * Gọi từ register_activation_hook.
	 */
	public static function activate() {
		if ( ! get_option( EQB_Settings::OPTION_KEY ) ) {
			update_option( EQB_Settings::OPTION_KEY, EQB_Settings::defaults() );
		}

		self::maybe_install( true );
	}

	/**
	 * Fallback nếu activate timeout (file SQL lớn).
	 */
	public static function admin_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( self::is_installed() ) {
			delete_option( 'eqb_address_install_error' );
			return;
		}

		self::maybe_install( false );

		if ( ! self::is_installed() ) {
			add_action(
				'admin_notices',
				array( __CLASS__, 'install_error_notice' )
			);
		}
	}

	public static function install_error_notice() {
		$error = get_option( 'eqb_address_install_error' );
		if ( ! $error ) {
			$error = __( 'Chưa import được dữ liệu địa chỉ. Kiểm tra file trong data/sql/.', 'echbay-quick-buy' );
		}
		echo '<div class="notice notice-warning"><p><strong>Echbay Quick Buy:</strong> ';
		echo esc_html( $error );
		echo '</p></div>';
	}

	/**
	 * @param bool $from_activation Đang chạy lúc kích hoạt plugin.
	 */
	public static function maybe_install( $from_activation = false ) {
		if ( self::is_installed() ) {
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
			return true;
		}

		if ( $from_activation ) {
			@set_time_limit( 300 );
		}

		$ok = self::run_sql_file( 'vn_tinh_thanh34.sql' );
		if ( $ok ) {
			$ok = self::run_sql_file( 'vn_phuong_xa34.sql' );
		}

		if ( $ok && self::is_installed() ) {
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
			delete_option( 'eqb_address_install_error' );
			return true;
		}

		if ( ! $ok ) {
			update_option(
				'eqb_address_install_error',
				sprintf(
					/* translators: %s: sql folder path */
					__( 'Import SQL thất bại. Đặt vn_tinh_thanh34.sql và vn_phuong_xa34.sql vào %s', 'echbay-quick-buy' ),
					'wp-content/plugins/echbay-quick-buy/data/sql/'
				)
			);
		}

		return false;
	}

	/**
	 * Bảng tỉnh có đủ 34 dòng.
	 */
	public static function is_installed() {
		global $wpdb;

		$table = self::TABLE_TINH;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $found !== $table ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		return $count >= 34;
	}

	/**
	 * @param string $filename Tên file trong data/sql/.
	 */
	private static function run_sql_file( $filename ) {
		global $wpdb;

		$path = EQB_PATH . 'data/sql/' . $filename;
		if ( ! is_readable( $path ) ) {
			return false;
		}

		$sql = file_get_contents( $path );
		if ( false === $sql || '' === trim( $sql ) ) {
			return false;
		}

		// Bỏ comment MySQL conditional và dòng --
		$sql = preg_replace( '/\/\*!\d+\s.*?\*\//s', '', $sql );
		$sql = preg_replace( '/\/\*.*?\*\//s', '', $sql );
		$sql = preg_replace( '/^--.*$/m', '', $sql );

		$statements = preg_split( '/;\s*[\r\n]+/', $sql );

		foreach ( $statements as $statement ) {
			$statement = trim( $statement );
			if ( '' === $statement ) {
				continue;
			}

			$upper = strtoupper( substr( $statement, 0, 20 ) );
			if ( 0 === strpos( $upper, 'SET ' ) || 'START TRANSACTION' === $upper || 'COMMIT' === $upper ) {
				continue;
			}

			// Bỏ qua nếu bảng đã có dữ liệu (chỉ file tỉnh).
			if ( 0 === strpos( strtoupper( $statement ), 'CREATE TABLE' ) && self::table_exists_from_create( $statement ) ) {
				continue;
			}

			$result = $wpdb->query( $statement ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( false === $result && ! empty( $wpdb->last_error ) ) {
				// CREATE TABLE khi đã tồn tại — bỏ qua.
				if ( false !== stripos( $wpdb->last_error, 'already exists' ) ) {
					$wpdb->last_error = '';
					continue;
				}
				// Duplicate key khi chạy lại INSERT — bỏ qua.
				if ( false !== stripos( $wpdb->last_error, 'Duplicate entry' ) ) {
					$wpdb->last_error = '';
					continue;
				}
				update_option( 'eqb_address_install_error', $wpdb->last_error );
				return false;
			}
		}

		return true;
	}

	/**
	 * Lấy tên bảng từ câu CREATE TABLE.
	 */
	private static function table_exists_from_create( $statement ) {
		global $wpdb;

		if ( ! preg_match( '/CREATE\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $m ) ) {
			return false;
		}

		$table = $m[1];
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return $found === $table;
	}

	/**
	 * Tên bảng đầy đủ (không dùng wp prefix — giống file SQL).
	 */
	public static function table_tinh() {
		return self::TABLE_TINH;
	}

	public static function table_xa() {
		return self::TABLE_XA;
	}
}
