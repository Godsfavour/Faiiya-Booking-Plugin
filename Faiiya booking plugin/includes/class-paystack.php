<?php
/**
 * Paystack API integration, transaction verifier, and webhook handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Paystack {

	const API_BASE = 'https://api.paystack.co';

	/**
	 * Retrieve active secret key based on configured mode.
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		$mode = YAB_Settings::get( 'paystack', 'mode', 'test' );
		return ( 'live' === $mode )
			? YAB_Settings::get( 'paystack', 'live_secret_key', '' )
			: YAB_Settings::get( 'paystack', 'test_secret_key', '' );
	}

	/**
	 * Retrieve active public key based on configured mode.
	 *
	 * @return string
	 */
	public static function get_public_key() {
		$mode = YAB_Settings::get( 'paystack', 'mode', 'test' );
		return ( 'live' === $mode )
			? YAB_Settings::get( 'paystack', 'live_public_key', '' )
			: YAB_Settings::get( 'paystack', 'test_public_key', '' );
	}

	/**
	 * Initialize a Paystack checkout transaction for a pending booking.
	 *
	 * @param int $booking_id
	 * @param string $callback_url URL for customer return
	 * @return array|WP_Error Paystack response data containing authorization_url, access_code, reference
	 */
	public static function initialize_transaction( $booking_id, $callback_url = '' ) {
		$booking = YAB_Booking::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'not_found', __( 'Booking record not found.', 'yasmine-artistry-booking' ) );
		}

		$secret_key = self::get_secret_key();
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'missing_keys', __( 'Paystack payment keys are not configured. Please contact the administrator.', 'yasmine-artistry-booking' ) );
		}

		// Deposit amount in Kobo (1 NGN = 100 Kobo)
		$deposit_ngn = floatval( $booking->deposit_required );
		$amount_kobo = round( $deposit_ngn * 100 );

		if ( $amount_kobo <= 0 ) {
			return new WP_Error( 'invalid_amount', __( 'Deposit amount must be greater than zero.', 'yasmine-artistry-booking' ) );
		}

		// Generate unique transaction reference
		$trx_reference = 'TRX-' . $booking->booking_reference . '-' . time();

		$payload = array(
			'email'        => $booking->customer_email,
			'amount'       => $amount_kobo,
			'currency'     => $booking->currency ? $booking->currency : 'NGN',
			'reference'    => $trx_reference,
			'callback_url' => ! empty( $callback_url ) ? esc_url_raw( $callback_url ) : home_url( '/' ),
			'metadata'     => array(
				'booking_id'        => $booking->id,
				'booking_reference' => $booking->booking_reference,
				'customer_name'     => $booking->customer_name,
				'customer_phone'    => $booking->customer_phone,
				'custom_fields'     => array(
					array(
						'display_name'  => 'Booking Ref',
						'variable_name' => 'booking_reference',
						'value'         => $booking->booking_reference,
					),
				),
			),
		);

		// Record initial pending payment in ledger
		self::record_payment_attempt( $booking->id, $trx_reference, $deposit_ngn, $booking->currency );

		$response = wp_remote_post(
			self::API_BASE . '/transaction/initialize',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			YAB_Logger::log( 'paystack_error', 'Paystack init request failed: ' . $response->get_error_message(), $booking->id );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || empty( $body['status'] ) ) {
			$msg = $body['message'] ?? __( 'Paystack initialization failed.', 'yasmine-artistry-booking' );
			YAB_Logger::log( 'paystack_error', 'Paystack rejected initialization: ' . $msg, $booking->id, (array) $body );
			return new WP_Error( 'paystack_rejected', $msg );
		}

		return array(
			'authorization_url' => $body['data']['authorization_url'],
			'access_code'       => $body['data']['access_code'],
			'reference'         => $body['data']['reference'],
			'public_key'        => self::get_public_key(),
		);
	}

	/**
	 * Verify transaction status against Paystack REST API.
	 *
	 * @param string $reference
	 * @return array|WP_Error
	 */
	public static function verify_transaction( $reference ) {
		$secret_key = self::get_secret_key();
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'missing_keys', __( 'Paystack keys are not configured.', 'yasmine-artistry-booking' ) );
		}

		$sanitized_ref = sanitize_text_field( $reference );

		$response = wp_remote_get(
			self::API_BASE . '/transaction/verify/' . rawurlencode( $sanitized_ref ),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret_key,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || empty( $body['status'] ) ) {
			return new WP_Error( 'verification_failed', $body['message'] ?? __( 'Verification request failed.', 'yasmine-artistry-booking' ) );
		}

		return $body['data'];
	}

	/**
	 * Process a successful payment confirmation from either frontend verify or server webhook.
	 *
	 * @param array $trx_data Verified transaction data from Paystack
	 * @return bool|WP_Error
	 */
	public static function process_verified_payment( $trx_data ) {
		global $wpdb;

		$reference   = sanitize_text_field( $trx_data['reference'] );
		$status      = sanitize_text_field( $trx_data['status'] );
		$amount_kobo = floatval( $trx_data['amount'] );
		$amount_ngn  = round( $amount_kobo / 100, 2 );

		if ( 'success' !== $status ) {
			return new WP_Error( 'not_successful', sprintf( __( 'Payment status is %s', 'yasmine-artistry-booking' ), $status ) );
		}

		// Find associated booking ID from metadata or payments table
		$booking_id = 0;
		if ( ! empty( $trx_data['metadata']['booking_id'] ) ) {
			$booking_id = absint( $trx_data['metadata']['booking_id'] );
		} else {
			$payments_table = YAB_Database::table( 'payments' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$booking_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT booking_id FROM {$payments_table} WHERE transaction_reference = %s LIMIT 1", $reference ) ) );
		}

		$booking = YAB_Booking::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'booking_not_found', __( 'Associated booking could not be located.', 'yasmine-artistry-booking' ) );
		}

		// IDEMPOTENCY: Check if this payment reference was already recorded as success
		$payments_table = YAB_Database::table( 'payments' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$payments_table} WHERE transaction_reference = %s LIMIT 1", $reference ) );

		if ( $existing_payment && 'success' === $existing_payment->status ) {
			// Already verified and recorded
			return true;
		}

		// AMOUNT VERIFICATION: Ensure received amount meets deposit required
		$required_deposit = floatval( $booking->deposit_required );
		if ( $amount_ngn < $required_deposit ) {
			YAB_Logger::log(
				'paystack_underpayment',
				sprintf( 'Underpayment detected! Paid: %0.2f, Required: %0.2f (Ref: %s)', $amount_ngn, $required_deposit, $reference ),
				$booking->id
			);
			return new WP_Error( 'underpayment', __( 'Paid amount is less than the required booking deposit.', 'yasmine-artistry-booking' ) );
		}

		// Update or record payment in ledger
		$channel          = sanitize_text_field( $trx_data['channel'] ?? 'card' );
		$gateway_response = sanitize_text_field( $trx_data['gateway_response'] ?? 'Successful' );
		$ip_address       = sanitize_text_field( $trx_data['ip_address'] ?? '' );
		$now_mysql        = current_time( 'mysql' );

		if ( $existing_payment ) {
			$wpdb->update(
				$payments_table,
				array(
					'status'             => 'success',
					'paystack_reference' => sanitize_text_field( $trx_data['id'] ?? '' ),
					'channel'            => $channel,
					'gateway_response'   => $gateway_response,
					'verified_at'        => $now_mysql,
				),
				array( 'id' => $existing_payment->id ),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$payments_table,
				array(
					'booking_id'            => $booking->id,
					'transaction_reference' => $reference,
					'paystack_reference'    => sanitize_text_field( $trx_data['id'] ?? '' ),
					'amount'                => $amount_ngn,
					'currency'              => $booking->currency,
					'status'                => 'success',
					'channel'               => $channel,
					'gateway_response'      => $gateway_response,
					'ip_address'            => $ip_address,
					'created_at'            => $now_mysql,
					'verified_at'           => $now_mysql,
				),
				array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		// Confirm booking
		return YAB_Booking::confirm_payment( $booking->id, $amount_ngn, $reference );
	}

	/**
	 * Log a pending payment attempt in the ledger.
	 *
	 * @param int $booking_id
	 * @param string $reference
	 * @param float $amount
	 * @param string $currency
	 */
	private static function record_payment_attempt( $booking_id, $reference, $amount, $currency ) {
		global $wpdb;
		$table = YAB_Database::table( 'payments' );

		$wpdb->insert(
			$table,
			array(
				'booking_id'            => $booking_id,
				'transaction_reference' => $reference,
				'amount'                => $amount,
				'currency'              => $currency ? $currency : 'NGN',
				'status'                => 'pending',
				'created_at'            => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%f', '%s', '%s', '%s' )
		);
	}
}
