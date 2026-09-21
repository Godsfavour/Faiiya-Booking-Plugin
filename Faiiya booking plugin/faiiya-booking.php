<?php
/**
 * Plugin Name: Faiiya Booking Plugin
 * Plugin URI: https://faiiya-booking.com/
 * Description: A modular booking engine with Google Calendar, Paystack, WhatsApp Business Cloud API, and Twilio SMS integrations.
 * Version: 1.2
 * Author: Faiiya Solutions
 * Author URI: https://faiiya-booking.com/
 * License: Apache-2.0
 * Text Domain: faiiya-booking
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'FAIIYA_BOOKING_VERSION', '1.2' );
define( 'FAIIYA_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'FAIIYA_BOOKING_URL', plugin_dir_url( __FILE__ ) );

// Include required core files
require_once FAIIYA_BOOKING_PATH . 'includes/class-yasmine-booking-db.php';
require_once FAIIYA_BOOKING_PATH . 'includes/class-yasmine-booking-integrations.php';
require_once FAIIYA_BOOKING_PATH . 'admin/class-yasmine-booking-admin.php';
require_once FAIIYA_BOOKING_PATH . 'public/class-yasmine-booking-public.php';

/**
 * The code that runs during plugin activation.
 */
function activate_faiiya_booking() {
	Yasmine_Booking_DB::create_tables();
	
	// Register default settings if not already present
	if ( ! get_option( 'yasmine_booking_business_details' ) ) {
		update_option( 'yasmine_booking_business_details', array(
			'name' => get_bloginfo( 'name' ),
			'logo_url' => '',
			'address' => '',
			'working_hours_start' => '09:00',
			'working_hours_end' => '17:00',
			'timezone' => get_option( 'timezone_string' ) ?: 'UTC',
			'brand_color' => '#10B981',
		) );
	}
	
	// Set up CRON events
	if ( ! wp_next_scheduled( 'yasmine_booking_cron_reminders' ) ) {
		wp_schedule_event( time(), 'hourly', 'yasmine_booking_cron_reminders' );
	}
	if ( ! wp_next_scheduled( 'yasmine_booking_cron_cleanup' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'yasmine_booking_cron_cleanup' );
	}
}
register_activation_hook( __FILE__, 'activate_faiiya_booking' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_faiiya_booking() {
	wp_clear_scheduled_hook( 'yasmine_booking_cron_reminders' );
	wp_clear_scheduled_hook( 'yasmine_booking_cron_cleanup' );
}
register_deactivation_hook( __FILE__, 'deactivate_faiiya_booking' );

/**
 * Initialize all components.
 */
function init_faiiya_booking() {
	// Load Text Domain for Internationalization
	load_plugin_textdomain( 'faiiya-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	// Initialize admin and public hooks
	new Yasmine_Booking_Admin();
	new Yasmine_Booking_Public();
}
add_action( 'plugins_loaded', 'init_faiiya_booking' );
