<?php
/**
 * Background automated task scheduler and cron worker.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Cron {

	/**
	 * Attach scheduled cron hooks.
	 */
	public static function init() {
		add_action( 'yab_cron_cleanup_abandoned_bookings', array( __CLASS__, 'cleanup_abandoned' ) );
		add_action( 'yab_cron_dispatch_reminders', array( __CLASS__, 'dispatch_reminders' ) );
	}

	/**
	 * Run hourly cleanup of abandoned pending payment bookings (> 15 minutes).
	 */
	public static function cleanup_abandoned() {
		YAB_Booking::cleanup_expired_holds();
	}

	/**
	 * Dispatch 24-hour advance appointment reminder emails.
	 */
	public static function dispatch_reminders() {
		global $wpdb;

		$tomorrow_date  = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$bookings_table = YAB_Database::table( 'bookings' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$bookings_table} WHERE appointment_date = %s AND booking_status = 'confirmed'",
				$tomorrow_date
			)
		);

		if ( empty( $bookings ) ) {
			return;
		}

		foreach ( $bookings as $booking ) {
			// Ensure we don't send duplicate reminders
			$transient_key = 'yab_reminded_' . $booking->id;
			if ( get_transient( $transient_key ) ) {
				continue;
			}

			// Mark transient for 48 hours
			set_transient( $transient_key, 1, 48 * HOUR_IN_SECONDS );

			$placeholders = YAB_Email::get_placeholders( $booking );
			$subject      = sprintf( __( 'Reminder: Your Yasmine Artistry appointment is tomorrow (%s)', 'yasmine-artistry-booking' ), $placeholders['booking_time'] );
			$message      = sprintf(
				__( "Hello %s,\n\nThis is a friendly reminder that your home-service appointment is scheduled for tomorrow, %s at %s.\n\nAddress: %s\nRemaining Balance: %s\n\nManage or view details: %s\n\nThank you,\nYasmine Artistry", 'yasmine-artistry-booking' ),
				$booking->customer_name,
				$placeholders['booking_date'],
				$placeholders['booking_time'],
				$booking->service_address,
				$placeholders['balance'],
				$placeholders['manage_link']
			);

			wp_mail( $booking->customer_email, $subject, $message );
		}
	}
}
