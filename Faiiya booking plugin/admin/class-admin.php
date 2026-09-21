<?php
/**
 * WordPress Admin Dashboard orchestrator and menu registry.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Admin {

	/**
	 * Initialize admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register menus.
	 */
	public static function register_admin_menus() {
		$cap = 'manage_options';

		// Top Level Menu
		add_menu_page(
			__( 'Yasmine Booking', 'yasmine-artistry-booking' ),
			__( 'Yasmine Booking', 'yasmine-artistry-booking' ),
			$cap,
			'yab-bookings',
			array( 'YAB_Admin_Bookings', 'render' ),
			'dashicons-calendar-alt',
			26
		);

		// Submenus
		add_submenu_page(
			'yab-bookings',
			__( 'All Bookings', 'yasmine-artistry-booking' ),
			__( 'Bookings', 'yasmine-artistry-booking' ),
			$cap,
			'yab-bookings',
			array( 'YAB_Admin_Bookings', 'render' )
		);

		add_submenu_page(
			'yab-bookings',
			__( 'Services', 'yasmine-artistry-booking' ),
			__( 'Services', 'yasmine-artistry-booking' ),
			$cap,
			'yab-services',
			array( 'YAB_Admin_Services', 'render' )
		);

		add_submenu_page(
			'yab-bookings',
			__( 'Categories', 'yasmine-artistry-booking' ),
			__( 'Categories', 'yasmine-artistry-booking' ),
			$cap,
			'yab-categories',
			array( 'YAB_Admin_Categories', 'render' )
		);

		add_submenu_page(
			'yab-bookings',
			__( 'Home Service Locations', 'yasmine-artistry-booking' ),
			__( 'Locations & Fees', 'yasmine-artistry-booking' ),
			$cap,
			'yab-locations',
			array( 'YAB_Admin_Locations', 'render' )
		);

		add_submenu_page(
			'yab-bookings',
			__( 'Settings', 'yasmine-artistry-booking' ),
			__( 'Settings', 'yasmine-artistry-booking' ),
			$cap,
			'yab-settings',
			array( 'YAB_Admin_Settings', 'render' )
		);
	}

	/**
	 * Enqueue admin CSS and JS only on plugin screens.
	 *
	 * @param string $hook
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'yab-' ) ) {
			return;
		}

		wp_enqueue_style(
			'yab-admin-css',
			YAB_PLUGIN_URL . 'assets/css/yab-admin.css',
			array(),
			YAB_VERSION
		);

		wp_enqueue_script(
			'yab-admin-js',
			YAB_PLUGIN_URL . 'assets/js/yab-admin.js',
			array( 'jquery' ),
			YAB_VERSION,
			true
		);

		wp_localize_script(
			'yab-admin-js',
			'yabAdminConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'yab_admin_action' ),
			)
		);
	}
}
