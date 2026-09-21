<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Yasmine_Artistry_Booking
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Ensure database class is available
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

// Only drop tables and remove options if user explicitly requested complete data cleanup in settings
$delete_all_data = get_option( 'yab_delete_data_on_uninstall', false );

if ( $delete_all_data ) {
	YAB_Database::drop_tables();

	// Delete plugin options
	delete_option( 'yab_db_version' );
	delete_option( 'yab_general_settings' );
	delete_option( 'yab_business_hours' );
	delete_option( 'yab_deposit_settings' );
	delete_option( 'yab_paystack_settings' );
	delete_option( 'yab_email_settings' );
	delete_option( 'yab_delete_data_on_uninstall' );
}
