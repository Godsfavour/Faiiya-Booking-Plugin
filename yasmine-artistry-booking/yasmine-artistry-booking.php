<?php
/**
 * Plugin Name: Yasmine Artistry Booking
 * Plugin URI: https://yasmineartistry.com/
 * Description: Production-grade salon and home-service booking engine with dynamic location pricing, Paystack deposits, atomic availability tracking, and frontend rescheduling.
 * Version: 1.2
 * Author: Custom WordPress Solutions
 * Author URI: https://yasmineartistry.com/
 * Text Domain: yasmine-artistry-booking
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'YAB_VERSION', '1.2' );
define( 'YAB_DB_VERSION', '1.2' );
define( 'YAB_PLUGIN_FILE', __FILE__ );
define( 'YAB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YAB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YAB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload / require core classes.
 */
require_once YAB_PLUGIN_DIR . 'includes/class-database.php';
require_once YAB_PLUGIN_DIR . 'includes/class-security.php';
require_once YAB_PLUGIN_DIR . 'includes/class-logger.php';
require_once YAB_PLUGIN_DIR . 'includes/class-settings.php';
require_once YAB_PLUGIN_DIR . 'includes/class-category.php';
require_once YAB_PLUGIN_DIR . 'includes/class-service.php';
require_once YAB_PLUGIN_DIR . 'includes/class-location.php';
require_once YAB_PLUGIN_DIR . 'includes/class-availability.php';
require_once YAB_PLUGIN_DIR . 'includes/class-pricing.php';
require_once YAB_PLUGIN_DIR . 'includes/class-booking.php';
require_once YAB_PLUGIN_DIR . 'includes/class-paystack.php';
require_once YAB_PLUGIN_DIR . 'includes/class-calendar.php';
require_once YAB_PLUGIN_DIR . 'includes/class-email.php';
require_once YAB_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once YAB_PLUGIN_DIR . 'includes/class-cron.php';
require_once YAB_PLUGIN_DIR . 'includes/class-elementor.php';

// Frontend
require_once YAB_PLUGIN_DIR . 'frontend/class-frontend.php';

// Admin
if ( is_admin() ) {
	require_once YAB_PLUGIN_DIR . 'admin/class-admin.php';
	require_once YAB_PLUGIN_DIR . 'admin/class-admin-bookings.php';
	require_once YAB_PLUGIN_DIR . 'admin/class-admin-services.php';
	require_once YAB_PLUGIN_DIR . 'admin/class-admin-categories.php';
	require_once YAB_PLUGIN_DIR . 'admin/class-admin-locations.php';
	require_once YAB_PLUGIN_DIR . 'admin/class-admin-settings.php';
}

/**
 * Main Plugin Orchestrator Singleton.
 */
final class Yasmine_Artistry_Booking {

	/**
	 * Singleton instance.
	 *
	 * @var Yasmine_Artistry_Booking|null
	 */
	private static $instance = null;

	/**
	 * Retrieve singleton instance.
	 *
	 * @return Yasmine_Artistry_Booking
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Protected to enforce singleton.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook registrations.
	 */
	private function init_hooks() {
		register_activation_hook( YAB_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( YAB_PLUGIN_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		add_action( 'init', array( $this, 'on_init' ) );

		// Initialize subsystem components
		YAB_REST_API::init();
		YAB_Cron::init();
		YAB_Email::init();
		YAB_Frontend::init();
		YAB_Elementor::init();

		if ( is_admin() ) {
			YAB_Admin::init();
		}
	}

	/**
	 * Activation callback.
	 */
	public function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		YAB_Database::install();

		// Schedule cron tasks
		if ( ! wp_next_scheduled( 'yab_cron_cleanup_abandoned_bookings' ) ) {
			wp_schedule_event( time(), 'hourly', 'yab_cron_cleanup_abandoned_bookings' );
		}

		if ( ! wp_next_scheduled( 'yab_cron_dispatch_reminders' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'yab_cron_dispatch_reminders' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'yab_cron_cleanup_abandoned_bookings' );
		wp_clear_scheduled_hook( 'yab_cron_dispatch_reminders' );
		flush_rewrite_rules();
	}

	/**
	 * Plugins loaded callback. Check for DB migrations.
	 */
	public function on_plugins_loaded() {
		load_plugin_textdomain( 'yasmine-artistry-booking', false, dirname( YAB_PLUGIN_BASENAME ) . '/languages' );

		// Check if DB migration is needed
		$installed_ver = get_option( 'yab_db_version', '0.0.0' );
		if ( version_compare( $installed_ver, YAB_DB_VERSION, '<' ) ) {
			YAB_Database::install();
		}
	}

	/**
	 * Init hook callback.
	 */
	public function on_init() {
		// Will register REST routes, shortcodes, and admin controllers in subsequent phases
	}
}

// Boot plugin.
function yab_plugin() {
	return Yasmine_Artistry_Booking::get_instance();
}
yab_plugin();
