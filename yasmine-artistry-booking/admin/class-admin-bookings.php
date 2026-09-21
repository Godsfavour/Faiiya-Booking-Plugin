<?php
/**
 * Admin Bookings controller, status updater, and list view.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Admin_Bookings {

	/**
	 * Main controller entry point.
	 */
	public static function render() {
		YAB_Security::check_permissions();

		// Handle manual status changes
		self::handle_status_action();

		$action     = sanitize_key( $_GET['action'] ?? 'list' );
		$booking_id = absint( $_GET['booking_id'] ?? 0 );

		if ( 'view' === $action && $booking_id > 0 ) {
			include YAB_PLUGIN_DIR . 'admin/views/booking-detail.php';
		} else {
			include YAB_PLUGIN_DIR . 'admin/views/bookings-list.php';
		}
	}

	/**
	 * Process status change actions with nonce verification.
	 */
	private static function handle_status_action() {
		if ( empty( $_POST['yab_admin_booking_action'] ) ) {
			return;
		}

		check_admin_referer( 'yab_update_booking_status', 'yab_status_nonce' );

		$booking_id = absint( $_POST['booking_id'] ?? 0 );
		$new_status = sanitize_key( $_POST['new_status'] ?? '' );
		$booking    = YAB_Booking::get( $booking_id );

		if ( ! $booking ) {
			return;
		}

		if ( 'completed' === $new_status ) {
			YAB_Booking::complete( $booking_id );
		} elseif ( 'cancelled' === $new_status ) {
			$reason = sanitize_textarea_field( $_POST['cancellation_reason'] ?? 'Cancelled by Admin' );
			YAB_Booking::cancel( $booking_id, $reason, null, true );
		} elseif ( 'confirmed' === $new_status ) {
			// Manual confirmation by admin
			YAB_Booking::confirm_payment( $booking_id, $booking->deposit_required, 'MANUAL-ADMIN' );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'yab-bookings', 'action' => 'view', 'booking_id' => $booking_id, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
