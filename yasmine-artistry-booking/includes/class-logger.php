<?php
/**
 * Activity and security logging handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Logger {

	/**
	 * Write an event entry into the database audit log.
	 *
	 * @param string   $event_type Event category (e.g. 'booking_created', 'payment_verified').
	 * @param string   $message Human-readable summary.
	 * @param int|null $booking_id Associated booking ID if available.
	 * @param array    $context Additional structured metadata (sensitive fields are filtered out).
	 * @return int|false Inserted log ID or false.
	 */
	public static function log( $event_type, $message, $booking_id = null, $context = array() ) {
		global $wpdb;

		$sanitized_context = self::filter_sensitive_data( $context );
		$context_json      = ! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : null;

		$table = YAB_Database::table( 'logs' );

		$result = $wpdb->insert(
			$table,
			array(
				'booking_id' => $booking_id ? absint( $booking_id ) : null,
				'event_type' => sanitize_text_field( $event_type ),
				'message'    => sanitize_textarea_field( $message ),
				'context'    => $context_json,
				'created_at' => current_time( 'mysql' ),
			),
			array(
				$booking_id ? '%d' : null,
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Recursively strip sensitive keys like secret keys, passwords, authorization tokens, or PANs.
	 *
	 * @param array $data Raw context data.
	 * @return array Sanitized context data.
	 */
	private static function filter_sensitive_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$sensitive_patterns = array( 'secret', 'key', 'token', 'authorization', 'password', 'cvv', 'card', 'pin' );

		foreach ( $data as $key => $value ) {
			$lower_key = strtolower( (string) $key );
			foreach ( $sensitive_patterns as $pattern ) {
				if ( false !== strpos( $lower_key, $pattern ) ) {
					$data[ $key ] = '[REDACTED]';
					break;
				}
			}

			if ( is_array( $data[ $key ] ) ) {
				$data[ $key ] = self::filter_sensitive_data( $data[ $key ] );
			}
		}

		return $data;
	}
}
