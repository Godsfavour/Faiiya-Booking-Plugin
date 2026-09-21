<?php
/**
 * Booking entity model, lifecycle state machine, and atomic slot locker.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Booking {

	/**
	 * Create a new pending appointment with temporary 15-minute slot lock.
	 *
	 * @param array $data Input fields from customer submission.
	 * @return array|WP_Error Array with 'booking_id', 'booking_reference', 'secure_token', 'quote' or WP_Error.
	 */
	public static function create_pending_booking( $data ) {
		global $wpdb;

		$service_id     = absint( $data['service_id'] ?? 0 );
		$location_id    = absint( $data['location_id'] ?? 0 );
		$payment_choice = sanitize_key( $data['payment_choice'] ?? 'deposit' );

		// 1. Calculate price quote strictly on the server
		$quote = YAB_Pricing::calculate_quote( $service_id, $location_id, $payment_choice );
		if ( is_wp_error( $quote ) ) {
			return $quote;
		}

		$service = YAB_Service::get( $service_id );
		$date    = sanitize_text_field( $data['appointment_date'] ?? '' );
		$time    = sanitize_text_field( $data['start_time'] ?? '' );

		if ( empty( $date ) || empty( $time ) ) {
			return new WP_Error( 'missing_slot', __( 'Please select an appointment date and time.', 'yasmine-artistry-booking' ) );
		}

		// Normalize time to H:i:s
		if ( strlen( $time ) === 5 ) {
			$time .= ':00';
		}

		$duration_minutes = intval( $service->duration_minutes );
		$buffer_minutes   = intval( $service->buffer_minutes );

		// 2. ATOMIC AVAILABILITY CHECK
		// Verify slot is completely free before inserting lock
		$is_available = YAB_Availability::is_slot_available( $date, $time, $duration_minutes, $buffer_minutes );
		if ( ! $is_available ) {
			return new WP_Error( 'slot_unavailable', __( 'This appointment time has just been reserved. Please select another time slot.', 'yasmine-artistry-booking' ) );
		}

		// Calculate appointment end and buffer end
		$wp_tz          = wp_timezone();
		$start_dt       = new DateTime( $date . ' ' . $time, $wp_tz );
		$end_dt         = ( clone $start_dt )->modify( "+{$duration_minutes} minutes" );
		$buffer_end_dt  = ( clone $start_dt )->modify( '+' . ( $duration_minutes + $buffer_minutes ) . ' minutes' );

		$start_time_str      = $start_dt->format( 'H:i:s' );
		$end_time_str        = $end_dt->format( 'H:i:s' );
		$buffer_end_time_str = $buffer_end_dt->format( 'H:i:s' );

		// Customer contact fields
		$customer_name  = sanitize_text_field( $data['customer_name'] ?? '' );
		$customer_email = sanitize_email( $data['customer_email'] ?? '' );
		$customer_phone = YAB_Security::sanitize_phone( $data['customer_phone'] ?? '' );

		if ( empty( $customer_name ) ) {
			return new WP_Error( 'invalid_name', __( 'Please provide your full name.', 'yasmine-artistry-booking' ) );
		}
		if ( ! is_email( $customer_email ) ) {
			return new WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'yasmine-artistry-booking' ) );
		}
		if ( empty( $customer_phone ) || strlen( $customer_phone ) < 7 ) {
			return new WP_Error( 'invalid_phone', __( 'Please provide a valid phone number.', 'yasmine-artistry-booking' ) );
		}

		$service_address = sanitize_textarea_field( $data['service_address'] ?? '' );
		if ( empty( $service_address ) ) {
			return new WP_Error( 'invalid_address', __( 'Please provide the street address for this home service.', 'yasmine-artistry-booking' ) );
		}

		$address_notes  = sanitize_textarea_field( $data['address_notes'] ?? '' );
		$customer_notes = sanitize_textarea_field( $data['customer_notes'] ?? '' );

		// Generate secure tokens
		$booking_reference = YAB_Security::generate_booking_reference();
		$secure_token      = YAB_Security::generate_secure_token( 32 );

		// Lock expires in 15 minutes to allow customer checkout
		$now_dt     = new DateTime( 'now', $wp_tz );
		$expires_dt = ( clone $now_dt )->modify( '+15 minutes' );

		$table = YAB_Database::table( 'bookings' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'booking_reference'  => $booking_reference,
				'secure_token'       => $secure_token,
				'customer_name'      => $customer_name,
				'customer_email'     => $customer_email,
				'customer_phone'     => $customer_phone,
				'service_id'         => $service_id,
				'category_id'        => intval( $service->category_id ),
				'location_id'        => $location_id,
				'service_address'    => $service_address,
				'address_notes'      => $address_notes,
				'customer_notes'     => $customer_notes,
				'appointment_date'   => $date,
				'start_time'         => $start_time_str,
				'end_time'           => $end_time_str,
				'buffer_end_time'    => $buffer_end_time_str,
				'base_price'         => $quote['base_price'],
				'location_fee'       => $quote['location_fee'],
				'total_amount'       => $quote['total_amount'],
				'deposit_required'   => $quote['deposit_required'],
				'deposit_paid'       => 0.00,
				'balance_remaining'  => $quote['total_amount'],
				'currency'           => $quote['currency'],
				'payment_status'     => 'unpaid',
				'booking_status'     => 'pending_payment',
				'reschedule_count'   => 0,
				'expires_at'         => $expires_dt->format( 'Y-m-d H:i:s' ),
				'created_at'         => $now_dt->format( 'Y-m-d H:i:s' ),
				'updated_at'         => $now_dt->format( 'Y-m-d H:i:s' ),
			),
			array(
				'%s', '%s', '%s', '%s', '%s',
				'%d', '%d', '%d',
				'%s', '%s', '%s',
				'%s', '%s', '%s', '%s',
				'%f', '%f', '%f', '%f', '%f', '%f',
				'%s', '%s', '%s', '%d',
				'%s', '%s', '%s'
			)
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', __( 'Could not reserve appointment slot. Please try again.', 'yasmine-artistry-booking' ) );
		}

		$booking_id = $wpdb->insert_id;

		YAB_Logger::log(
			'booking_pending',
			sprintf( 'Slot locked for booking %s (ID: %d), expires at %s', $booking_reference, $booking_id, $expires_dt->format( 'H:i:s' ) ),
			$booking_id,
			array(
				'reference' => $booking_reference,
				'service'   => $service->name,
				'date'      => $date,
				'time'      => $start_time_str,
			)
		);

		return array(
			'booking_id'        => $booking_id,
			'booking_reference' => $booking_reference,
			'secure_token'      => $secure_token,
			'quote'             => $quote,
		);
	}

	/**
	 * Transition booking to confirmed upon verified Paystack deposit receipt.
	 *
	 * @param int $booking_id
	 * @param float $amount_paid
	 * @param string $paystack_ref
	 * @return bool|WP_Error
	 */
	public static function confirm_payment( $booking_id, $amount_paid, $paystack_ref ) {
		global $wpdb;

		$booking = self::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'not_found', __( 'Booking not found.', 'yasmine-artistry-booking' ) );
		}

		// Idempotency: if already confirmed, do not re-run notifications
		if ( 'confirmed' === $booking->booking_status && 'deposit_paid' === $booking->payment_status ) {
			return true;
		}

		$total_amount      = floatval( $booking->total_amount );
		$deposit_paid      = floatval( $amount_paid );
		$balance_remaining = max( 0.00, round( $total_amount - $deposit_paid, 2 ) );
		$payment_status    = ( $balance_remaining <= 0.00 ) ? 'fully_paid' : 'deposit_paid';

		$table = YAB_Database::table( 'bookings' );

		$updated = $wpdb->update(
			$table,
			array(
				'booking_status'    => 'confirmed',
				'payment_status'    => $payment_status,
				'deposit_paid'      => $deposit_paid,
				'balance_remaining' => $balance_remaining,
				'expires_at'        => null, // Clear expiration lock
				'updated_at'        => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s', '%f', '%f', null, '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'db_error', __( 'Failed to confirm booking status.', 'yasmine-artistry-booking' ) );
		}

		YAB_Logger::log(
			'booking_confirmed',
			sprintf( 'Booking %s confirmed with deposit of %0.2f (Ref: %s)', $booking->booking_reference, $deposit_paid, $paystack_ref ),
			$booking_id,
			array( 'paystack_ref' => $paystack_ref, 'deposit_paid' => $deposit_paid )
		);

		// Dispatch confirmation emails and calendar notifications
		do_action( 'yab_booking_confirmed', $booking_id );

		return true;
	}

	/**
	 * Customer or Admin Reschedule Handler.
	 *
	 * @param int $booking_id
	 * @param string $new_date
	 * @param string $new_time
	 * @param string|null $secure_token Required for customer, optional if admin
	 * @param bool $is_admin
	 * @return bool|WP_Error
	 */
	public static function reschedule( $booking_id, $new_date, $new_time, $secure_token = null, $is_admin = false ) {
		global $wpdb;

		$booking = self::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'not_found', __( 'Booking not found.', 'yasmine-artistry-booking' ) );
		}

		if ( ! $is_admin ) {
			// Verify token
			if ( empty( $secure_token ) || ! hash_equals( $booking->secure_token, $secure_token ) ) {
				return new WP_Error( 'unauthorized', __( 'Invalid or expired security token.', 'yasmine-artistry-booking' ) );
			}

			// Verify rescheduling allowed
			$allowed = (bool) YAB_Settings::get( 'rules', 'rescheduling_allowed', 1 );
			if ( ! $allowed ) {
				return new WP_Error( 'disabled', __( 'Online rescheduling is not enabled for this business.', 'yasmine-artistry-booking' ) );
			}

			// Check max reschedule count
			$max_count = absint( YAB_Settings::get( 'rules', 'max_reschedules', 2 ) );
			if ( intval( $booking->reschedule_count ) >= $max_count ) {
				return new WP_Error( 'limit_exceeded', sprintf( __( 'This booking has already reached the maximum allowed reschedules (%d).', 'yasmine-artistry-booking' ), $max_count ) );
			}

			// Check deadline (e.g. at least 24 hours prior to current appointment)
			$deadline_hours = absint( YAB_Settings::get( 'rules', 'reschedule_deadline_h', 24 ) );
			$current_app_dt = new DateTime( $booking->appointment_date . ' ' . $booking->start_time, wp_timezone() );
			$now            = new DateTime( 'now', wp_timezone() );
			$hours_diff     = ( $current_app_dt->getTimestamp() - $now->getTimestamp() ) / 3600;

			if ( $hours_diff < $deadline_hours ) {
				return new WP_Error( 'past_deadline', sprintf( __( 'Rescheduling must be completed at least %d hours prior to the scheduled appointment.', 'yasmine-artistry-booking' ), $deadline_hours ) );
			}
		}

		$service = YAB_Service::get( $booking->service_id );
		if ( ! $service ) {
			return new WP_Error( 'invalid_service', __( 'Service no longer available.', 'yasmine-artistry-booking' ) );
		}

		// Normalize new time
		if ( strlen( $new_time ) === 5 ) {
			$new_time .= ':00';
		}

		$duration_minutes = intval( $service->duration_minutes );
		$buffer_minutes   = intval( $service->buffer_minutes );

		// Atomic slot check for new slot (excluding current booking ID)
		$is_available = YAB_Availability::is_slot_available( $new_date, $new_time, $duration_minutes, $buffer_minutes, $booking_id );
		if ( ! $is_available ) {
			return new WP_Error( 'slot_taken', __( 'The chosen time slot is not available. Please choose another slot.', 'yasmine-artistry-booking' ) );
		}

		$start_dt      = new DateTime( $new_date . ' ' . $new_time, wp_timezone() );
		$end_dt        = ( clone $start_dt )->modify( "+{$duration_minutes} minutes" );
		$buffer_end_dt = ( clone $start_dt )->modify( '+' . ( $duration_minutes + $buffer_minutes ) . ' minutes' );

		$table = YAB_Database::table( 'bookings' );

		$old_date = $booking->appointment_date;
		$old_time = $booking->start_time;

		$updated = $wpdb->update(
			$table,
			array(
				'appointment_date' => $new_date,
				'start_time'       => $start_dt->format( 'H:i:s' ),
				'end_time'         => $end_dt->format( 'H:i:s' ),
				'buffer_end_time'  => $buffer_end_dt->format( 'H:i:s' ),
				'reschedule_count' => intval( $booking->reschedule_count ) + 1,
				'updated_at'       => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'db_error', __( 'Failed to save rescheduled appointment.', 'yasmine-artistry-booking' ) );
		}

		YAB_Logger::log(
			'booking_rescheduled',
			sprintf( 'Booking %s rescheduled from %s %s to %s %s', $booking->booking_reference, $old_date, $old_time, $new_date, $new_time ),
			$booking_id
		);

		do_action( 'yab_booking_rescheduled', $booking_id, $old_date, $old_time );

		return true;
	}

	/**
	 * Cancel an existing appointment.
	 *
	 * @param int $booking_id
	 * @param string $reason
	 * @param string|null $secure_token
	 * @param bool $is_admin
	 * @return bool|WP_Error
	 */
	public static function cancel( $booking_id, $reason, $secure_token = null, $is_admin = false ) {
		global $wpdb;

		$booking = self::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'not_found', __( 'Booking not found.', 'yasmine-artistry-booking' ) );
		}

		if ( ! $is_admin ) {
			if ( empty( $secure_token ) || ! hash_equals( $booking->secure_token, $secure_token ) ) {
				return new WP_Error( 'unauthorized', __( 'Invalid or expired security token.', 'yasmine-artistry-booking' ) );
			}

			$allowed = (bool) YAB_Settings::get( 'rules', 'cancellation_allowed', 1 );
			if ( ! $allowed ) {
				return new WP_Error( 'disabled', __( 'Online cancellations are not permitted. Please contact us directly.', 'yasmine-artistry-booking' ) );
			}

			$deadline_hours = absint( YAB_Settings::get( 'rules', 'cancel_deadline_h', 48 ) );
			$current_app_dt = new DateTime( $booking->appointment_date . ' ' . $booking->start_time, wp_timezone() );
			$now            = new DateTime( 'now', wp_timezone() );
			$hours_diff     = ( $current_app_dt->getTimestamp() - $now->getTimestamp() ) / 3600;

			if ( $hours_diff < $deadline_hours ) {
				return new WP_Error( 'past_deadline', sprintf( __( 'Cancellations must be made at least %d hours before the appointment.', 'yasmine-artistry-booking' ), $deadline_hours ) );
			}
		}

		$table = YAB_Database::table( 'bookings' );

		$updated = $wpdb->update(
			$table,
			array(
				'booking_status'      => 'cancelled',
				'cancellation_reason' => sanitize_textarea_field( $reason ),
				'expires_at'          => null,
				'updated_at'          => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s', null, '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'db_error', __( 'Failed to cancel appointment.', 'yasmine-artistry-booking' ) );
		}

		YAB_Logger::log(
			'booking_cancelled',
			sprintf( 'Booking %s cancelled. Reason: %s', $booking->booking_reference, $reason ),
			$booking_id
		);

		do_action( 'yab_booking_cancelled', $booking_id, $reason );

		return true;
	}

	/**
	 * Mark booking as concluded/completed.
	 *
	 * @param int $booking_id
	 * @return bool
	 */
	public static function complete( $booking_id ) {
		global $wpdb;

		$table = YAB_Database::table( 'bookings' );
		$res   = $wpdb->update(
			$table,
			array(
				'booking_status' => 'completed',
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => absint( $booking_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $res ) {
			YAB_Logger::log( 'booking_completed', sprintf( 'Booking ID %d marked as completed', $booking_id ), $booking_id );
			return true;
		}
		return false;
	}

	/**
	 * WP-Cron routine to release abandoned pending bookings older than 15 minutes.
	 *
	 * @return int Number of released records.
	 */
	public static function cleanup_expired_holds() {
		global $wpdb;

		$table     = YAB_Database::table( 'bookings' );
		$now_mysql = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expired_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE booking_status = 'pending_payment' AND expires_at < %s",
				$now_mysql
			)
		);

		if ( empty( $expired_ids ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $expired_ids as $id ) {
			$wpdb->update(
				$table,
				array(
					'booking_status'      => 'cancelled',
					'cancellation_reason' => 'Expired pending payment window (15 minutes elapsed)',
					'updated_at'          => $now_mysql,
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			$count++;
		}

		if ( $count > 0 ) {
			YAB_Logger::log( 'cron_cleanup', sprintf( 'Cleaned up %d expired pending appointment reservation(s).', $count ) );
		}

		return $count;
	}

	/**
	 * Fetch a booking by ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = YAB_Database::table( 'bookings' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) ) );
	}

	/**
	 * Fetch a booking by unique human reference (e.g. YAB-20260920-XXXX).
	 *
	 * @param string $reference
	 * @return object|null
	 */
	public static function get_by_reference( $reference ) {
		global $wpdb;
		$table = YAB_Database::table( 'bookings' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE booking_reference = %s LIMIT 1", sanitize_text_field( $reference ) ) );
	}

	/**
	 * Fetch a booking by 64-char security token.
	 *
	 * @param string $token
	 * @return object|null
	 */
	public static function get_by_token( $token ) {
		global $wpdb;
		$table = YAB_Database::table( 'bookings' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE secure_token = %s LIMIT 1", sanitize_text_field( $token ) ) );
	}

	/**
	 * Fetch bookings with filtering and pagination.
	 *
	 * @param array $args
	 * @return array
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;

		$bookings_table  = YAB_Database::table( 'bookings' );
		$services_table  = YAB_Database::table( 'services' );
		$locations_table = YAB_Database::table( 'locations' );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'b.booking_status = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['date'] ) ) {
			$where[]  = 'b.appointment_date = %s';
			$values[] = sanitize_text_field( $args['date'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(b.booking_reference LIKE %s OR b.customer_name LIKE %s OR b.customer_email LIKE %s OR b.customer_phone LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$limit     = absint( $args['limit'] ?? 20 );
		$offset    = absint( $args['offset'] ?? 0 );

		$sql = "SELECT b.*, s.name AS service_name, l.name AS location_name 
				FROM {$bookings_table} b 
				LEFT JOIN {$services_table} s ON b.service_id = s.id 
				LEFT JOIN {$locations_table} l ON b.location_id = l.id 
				WHERE {$where_sql} 
				ORDER BY b.appointment_date DESC, b.start_time DESC 
				LIMIT {$limit} OFFSET {$offset}";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $sql );
	}
}
