<?php
/**
 * Availability, business hours, and conflict prevention engine.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Availability {

	/**
	 * Retrieve available time slots for a given date and service.
	 *
	 * @param string $date ISO date string 'YYYY-MM-DD'.
	 * @param int $service_id Target service ID.
	 * @return array List of available start times in 'H:i' format, or empty if none.
	 */
	public static function get_available_slots( $date, $service_id ) {
		$service = YAB_Service::get( $service_id );
		if ( ! $service || ! $service->is_active ) {
			return array();
		}

		$date_obj = self::validate_date( $date );
		if ( ! $date_obj ) {
			return array();
		}

		$wp_tz = wp_timezone();
		$now   = new DateTime( 'now', $wp_tz );

		// 1. Check booking horizons (min advance hours and max future window)
		$min_advance_hours = absint( YAB_Settings::get( 'rules', 'min_advance_hours', 12 ) );
		$max_future_days   = absint( YAB_Settings::get( 'rules', 'max_future_days', 60 ) );

		$earliest_allowed = ( clone $now )->modify( "+{$min_advance_hours} hours" );
		$latest_allowed   = ( clone $now )->modify( "+{$max_future_days} days" );

		// Target date at end of day
		$target_end = new DateTime( $date . ' 23:59:59', $wp_tz );
		if ( $target_end < $earliest_allowed || $date_obj > $latest_allowed ) {
			return array();
		}

		// 2. Check operating hours for target date
		$operating_window = self::get_operating_hours_for_date( $date );
		if ( ! $operating_window || $operating_window['is_closed'] ) {
			return array();
		}

		$open_time_str  = $operating_window['open_time'];
		$close_time_str = $operating_window['close_time'];

		$open_dt  = new DateTime( $date . ' ' . $open_time_str, $wp_tz );
		$close_dt = new DateTime( $date . ' ' . $close_time_str, $wp_tz );

		// 3. Query existing bookings on this date (confirmed or active pending)
		$occupied_intervals = self::get_occupied_intervals( $date );

		// 4. Candidate slot generation
		// Step interval: 30 minutes for slot start options
		$slot_interval_minutes = 30;
		$duration_minutes      = intval( $service->duration_minutes );
		$buffer_minutes        = intval( $service->buffer_minutes );
		$total_blocked_minutes = $duration_minutes + $buffer_minutes;

		$available_slots = array();
		$cursor          = clone $open_dt;

		while ( true ) {
			$appointment_end = ( clone $cursor )->modify( "+{$duration_minutes} minutes" );
			$buffer_end      = ( clone $cursor )->modify( "+{$total_blocked_minutes} minutes" );

			// Appointment work must finish by or before close of business
			if ( $appointment_end > $close_dt ) {
				break;
			}

			// Slot must satisfy minimum advance notice constraint
			if ( $cursor >= $earliest_allowed ) {
				$candidate_start = $cursor->format( 'H:i:s' );
				$candidate_end   = $buffer_end->format( 'H:i:s' );

				// Check overlap with any occupied interval
				$has_collision = false;
				foreach ( $occupied_intervals as $occ ) {
					// Two time intervals [A, B] and [C, D] overlap if A < D and B > C
					if ( $candidate_start < $occ['buffer_end'] && $candidate_end > $occ['start'] ) {
						$has_collision = true;
						break;
					}
				}

				if ( ! $has_collision ) {
					$available_slots[] = array(
						'time'            => $cursor->format( 'H:i' ),
						'display'         => $cursor->format( YAB_Settings::get( 'general', 'time_format', 'g:i A' ) ),
						'duration_mins'   => $duration_minutes,
						'buffer_mins'     => $buffer_minutes,
					);
				}
			}

			// Advance cursor
			$cursor->modify( "+{$slot_interval_minutes} minutes" );
		}

		return $available_slots;
	}

	/**
	 * Atomic validation to confirm a specific slot remains completely free before recording a booking.
	 *
	 * @param string $date 'YYYY-MM-DD'
	 * @param string $start_time 'HH:MM:SS'
	 * @param int $duration_minutes
	 * @param int $buffer_minutes
	 * @param int $exclude_booking_id Optional booking ID to exclude (used during rescheduling)
	 * @return bool True if completely available, false otherwise.
	 */
	public static function is_slot_available( $date, $start_time, $duration_minutes, $buffer_minutes, $exclude_booking_id = 0 ) {
		global $wpdb;

		$start_dt   = new DateTime( $date . ' ' . $start_time, wp_timezone() );
		$buffer_end = ( clone $start_dt )->modify( '+' . ( $duration_minutes + $buffer_minutes ) . ' minutes' );

		$start_str      = $start_dt->format( 'H:i:s' );
		$buffer_end_str = $buffer_end->format( 'H:i:s' );

		// Check operating window
		$operating = self::get_operating_hours_for_date( $date );
		if ( ! $operating || $operating['is_closed'] ) {
			return false;
		}

		$service_end = ( clone $start_dt )->modify( "+{$duration_minutes} minutes" );
		$close_dt    = new DateTime( $date . ' ' . $operating['close_time'], wp_timezone() );
		if ( $service_end > $close_dt ) {
			return false;
		}

		// Query collisions directly against DB
		$bookings_table = YAB_Database::table( 'bookings' );
		$now_mysql      = current_time( 'mysql' );

		$sql = "SELECT COUNT(*) FROM {$bookings_table} 
				WHERE appointment_date = %s 
				AND id != %d 
				AND (
					booking_status = 'confirmed' 
					OR (booking_status = 'pending_payment' AND expires_at > %s)
				)
				AND start_time < %s 
				AND buffer_end_time > %s";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$collisions = $wpdb->get_var(
			$wpdb->prepare(
				$sql,
				$date,
				absint( $exclude_booking_id ),
				$now_mysql,
				$buffer_end_str,
				$start_str
			)
		);

		return ( intval( $collisions ) === 0 );
	}

	/**
	 * Retrieve effective operating hours for a specific date (evaluates special days/holidays first).
	 *
	 * @param string $date 'YYYY-MM-DD'.
	 * @return array|null Array with 'is_closed' (bool), 'open_time' (string), 'close_time' (string).
	 */
	public static function get_operating_hours_for_date( $date ) {
		global $wpdb;

		// 1. Check special days table first
		$special_table = YAB_Database::table( 'special_days' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$special = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$special_table} WHERE special_date = %s LIMIT 1", $date ) );

		if ( $special ) {
			if ( intval( $special->is_closed ) === 1 ) {
				return array(
					'is_closed'  => true,
					'open_time'  => null,
					'close_time' => null,
					'note'       => $special->note,
				);
			}

			if ( ! empty( $special->open_time ) && ! empty( $special->close_time ) ) {
				return array(
					'is_closed'  => false,
					'open_time'  => $special->open_time,
					'close_time' => $special->close_time,
					'note'       => $special->note,
				);
			}
		}

		// 2. Fallback to weekly business hours
		$date_obj    = new DateTime( $date, wp_timezone() );
		$day_of_week = intval( $date_obj->format( 'w' ) ); // 0=Sunday, 1=Monday ... 6=Saturday

		$hours_table = YAB_Database::table( 'business_hours' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$hours = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$hours_table} WHERE day_of_week = %d LIMIT 1", $day_of_week ) );

		if ( ! $hours || intval( $hours->is_open ) === 0 ) {
			return array(
				'is_closed'  => true,
				'open_time'  => null,
				'close_time' => null,
				'note'       => '',
			);
		}

		return array(
			'is_closed'  => false,
			'open_time'  => $hours->open_time,
			'close_time' => $hours->close_time,
			'note'       => '',
		);
	}

	/**
	 * Retrieve all busy intervals for a date from confirmed or active pending bookings.
	 *
	 * @param string $date 'YYYY-MM-DD'.
	 * @return array
	 */
	private static function get_occupied_intervals( $date ) {
		global $wpdb;

		$table     = YAB_Database::table( 'bookings' );
		$now_mysql = current_time( 'mysql' );

		$sql = "SELECT start_time, buffer_end_time 
				FROM {$table} 
				WHERE appointment_date = %s 
				AND (
					booking_status = 'confirmed' 
					OR (booking_status = 'pending_payment' AND expires_at > %s)
				)
				ORDER BY start_time ASC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $date, $now_mysql ) );

		$intervals = array();
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$intervals[] = array(
					'start'      => $row->start_time,
					'buffer_end' => $row->buffer_end_time,
				);
			}
		}

		return $intervals;
	}

	/**
	 * Validate a date string format.
	 *
	 * @param string $date
	 * @return DateTime|false
	 */
	private static function validate_date( $date ) {
		$d = DateTime::createFromFormat( 'Y-m-d', $date, wp_timezone() );
		return ( $d && $d->format( 'Y-m-d' ) === $date ) ? $d : false;
	}
}
