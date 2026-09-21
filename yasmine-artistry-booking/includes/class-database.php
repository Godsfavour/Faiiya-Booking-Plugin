<?php
/**
 * Database installer, schema manager, and migration handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Database {

	/**
	 * Run database migrations and schema installation.
	 */
	public static function install() {
		self::create_tables();
		self::seed_default_business_hours();
		update_option( 'yab_db_version', YAB_DB_VERSION );
	}

	/**
	 * Return the full prefixed table name.
	 *
	 * @param string $name Short table name without prefix.
	 * @return string Full table name.
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'yab_' . sanitize_key( $name );
	}

	/**
	 * Create or alter all plugin custom database tables using dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}
		}

		$charset_collate = $wpdb->get_charset_collate();

		$categories_table     = self::table( 'categories' );
		$services_table       = self::table( 'services' );
		$locations_table      = self::table( 'locations' );
		$business_hours_table = self::table( 'business_hours' );
		$special_days_table   = self::table( 'special_days' );
		$bookings_table       = self::table( 'bookings' );
		$payments_table       = self::table( 'payments' );
		$logs_table           = self::table( 'logs' );

		// 1. Categories
		$sql_categories = "CREATE TABLE {$categories_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			description text NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_slug (slug),
			KEY idx_active_sort (is_active, sort_order)
		) {$charset_collate};";

		// 2. Services
		$sql_services = "CREATE TABLE {$services_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			category_id bigint(20) unsigned NOT NULL,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			description text NULL,
			image_url text NULL,
			base_price decimal(10,2) NOT NULL DEFAULT 0.00,
			duration_minutes int(11) NOT NULL DEFAULT 60,
			buffer_minutes int(11) NOT NULL DEFAULT 15,
			sort_order int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_slug (slug),
			KEY idx_category (category_id),
			KEY idx_active (is_active)
		) {$charset_collate};";

		// 3. Locations
		$sql_locations = "CREATE TABLE {$locations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			description text NULL,
			fee_type varchar(20) NOT NULL DEFAULT 'fixed',
			fee_amount decimal(10,2) NOT NULL DEFAULT 0.00,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_slug (slug),
			KEY idx_active (is_active)
		) {$charset_collate};";

		// 4. Business Hours
		$sql_business_hours = "CREATE TABLE {$business_hours_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			day_of_week tinyint(3) unsigned NOT NULL,
			is_open tinyint(1) NOT NULL DEFAULT 1,
			open_time time NOT NULL DEFAULT '09:00:00',
			close_time time NOT NULL DEFAULT '18:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY uq_day (day_of_week)
		) {$charset_collate};";

		// 5. Special Days (Blackout dates / holidays / modified hours)
		$sql_special_days = "CREATE TABLE {$special_days_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			special_date date NOT NULL,
			is_closed tinyint(1) NOT NULL DEFAULT 1,
			open_time time NULL,
			close_time time NULL,
			note varchar(255) NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_special_date (special_date)
		) {$charset_collate};";

		// 6. Bookings
		$sql_bookings = "CREATE TABLE {$bookings_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_reference varchar(32) NOT NULL,
			secure_token varchar(64) NOT NULL,
			customer_name varchar(191) NOT NULL,
			customer_email varchar(191) NOT NULL,
			customer_phone varchar(50) NOT NULL,
			service_id bigint(20) unsigned NOT NULL,
			category_id bigint(20) unsigned NOT NULL,
			location_id bigint(20) unsigned NOT NULL,
			service_address text NOT NULL,
			address_notes text NULL,
			customer_notes text NULL,
			appointment_date date NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			buffer_end_time time NOT NULL,
			base_price decimal(10,2) NOT NULL DEFAULT 0.00,
			location_fee decimal(10,2) NOT NULL DEFAULT 0.00,
			total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
			deposit_required decimal(10,2) NOT NULL DEFAULT 0.00,
			deposit_paid decimal(10,2) NOT NULL DEFAULT 0.00,
			balance_remaining decimal(10,2) NOT NULL DEFAULT 0.00,
			currency varchar(10) NOT NULL DEFAULT 'NGN',
			payment_status varchar(30) NOT NULL DEFAULT 'unpaid',
			booking_status varchar(30) NOT NULL DEFAULT 'pending_payment',
			reschedule_count int(11) NOT NULL DEFAULT 0,
			cancellation_reason text NULL,
			expires_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_reference (booking_reference),
			UNIQUE KEY uq_token (secure_token),
			KEY idx_schedule (appointment_date, start_time, buffer_end_time),
			KEY idx_status (booking_status, payment_status),
			KEY idx_customer (customer_email, customer_phone),
			KEY idx_expires (expires_at)
		) {$charset_collate};";

		// 7. Payments Audit Table
		$sql_payments = "CREATE TABLE {$payments_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			transaction_reference varchar(191) NOT NULL,
			paystack_reference varchar(191) NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(10) NOT NULL DEFAULT 'NGN',
			status varchar(30) NOT NULL DEFAULT 'pending',
			channel varchar(50) NULL,
			gateway_response text NULL,
			ip_address varchar(45) NULL,
			created_at datetime NOT NULL,
			verified_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_reference (transaction_reference),
			KEY idx_booking (booking_id),
			KEY idx_status (status)
		) {$charset_collate};";

		// 8. Logs Table
		$sql_logs = "CREATE TABLE {$logs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NULL,
			event_type varchar(50) NOT NULL,
			message text NOT NULL,
			context longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_event (event_type),
			KEY idx_booking (booking_id)
		) {$charset_collate};";

		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql_categories );
			dbDelta( $sql_services );
			dbDelta( $sql_locations );
			dbDelta( $sql_business_hours );
			dbDelta( $sql_special_days );
			dbDelta( $sql_bookings );
			dbDelta( $sql_payments );
			dbDelta( $sql_logs );
		} else {
			// Fallback direct execution if dbDelta is unavailable
			$wpdb->query( $sql_categories );
			$wpdb->query( $sql_services );
			$wpdb->query( $sql_locations );
			$wpdb->query( $sql_business_hours );
			$wpdb->query( $sql_special_days );
			$wpdb->query( $sql_bookings );
			$wpdb->query( $sql_payments );
			$wpdb->query( $sql_logs );
		}
	}

	/**
	 * Seed neutral base operating hours if table is empty.
	 * Does NOT populate fake bookings or fake services.
	 */
	public static function seed_default_business_hours() {
		global $wpdb;
		$table = self::table( 'business_hours' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( intval( $count ) === 0 ) {
			// Days 0 (Sunday) through 6 (Saturday)
			for ( $day = 0; $day <= 6; $day++ ) {
				// Default: Sunday closed (0), Monday-Saturday open 09:00 - 18:00
				$is_open = ( $day === 0 ) ? 0 : 1;
				$wpdb->insert(
					$table,
					array(
						'day_of_week' => $day,
						'is_open'     => $is_open,
						'open_time'   => '09:00:00',
						'close_time'  => '18:00:00',
					),
					array( '%d', '%d', '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Drop all plugin tables upon explicit uninstallation request.
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = array(
			self::table( 'logs' ),
			self::table( 'payments' ),
			self::table( 'bookings' ),
			self::table( 'special_days' ),
			self::table( 'business_hours' ),
			self::table( 'locations' ),
			self::table( 'services' ),
			self::table( 'categories' ),
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}
}
