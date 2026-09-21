<?php
/**
 * Security and authentication utility handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Security {

	/**
	 * Verify that current user has administrative rights.
	 *
	 * @param string $capability Capability to check.
	 * @return bool True if authorized, dies otherwise if called in sensitive execution.
	 */
	public static function check_permissions( $capability = 'manage_options' ) {
		if ( ! current_user_can( $capability ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'yasmine-artistry-booking' ),
				esc_html__( 'Unauthorized Access', 'yasmine-artistry-booking' ),
				array( 'response' => 403 )
			);
		}
		return true;
	}

	/**
	 * Validate standard WordPress nonce.
	 *
	 * @param string $nonce Nonce token.
	 * @param string $action Nonce action name.
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action ) {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Generate a cryptographically secure token for booking management & rescheduling.
	 *
	 * @param int $bytes Number of random bytes (default 32 bytes = 64 hex characters).
	 * @return string Hexadecimal token.
	 */
	public static function generate_secure_token( $bytes = 32 ) {
		if ( function_exists( 'random_bytes' ) ) {
			try {
				return bin2hex( random_bytes( $bytes ) );
			} catch ( Exception $e ) {
				// Fallback to WordPress core password generator if entropy failure occurs
			}
		}
		return wp_generate_password( $bytes * 2, false, false );
	}

	/**
	 * Generate unique human-readable booking reference code.
	 * Format: YAB-YYYYMMDD-XXXXXX
	 *
	 * @return string
	 */
	public static function generate_booking_reference() {
		$date_prefix = gmdate( 'Ymd' );
		$random_hex  = strtoupper( substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 6 ) );
		return sprintf( 'YAB-%s-%s', $date_prefix, $random_hex );
	}

	/**
	 * Verify Paystack webhook HMAC SHA-512 signature.
	 *
	 * @param string $payload Raw request body string.
	 * @param string $signature Provided X-Paystack-Signature header.
	 * @param string $secret_key Configured Paystack Secret Key.
	 * @return bool True if authentic.
	 */
	public static function verify_paystack_signature( $payload, $signature, $secret_key ) {
		if ( empty( $payload ) || empty( $signature ) || empty( $secret_key ) ) {
			return false;
		}

		$computed_hash = hash_hmac( 'sha512', $payload, $secret_key );
		return hash_equals( $computed_hash, $signature );
	}

	/**
	 * Sanitize phone number to international/standard notation.
	 *
	 * @param string $phone Raw phone input.
	 * @return string
	 */
	public static function sanitize_phone( $phone ) {
		// Allow digits, plus, hyphens, and spaces
		return preg_replace( '/[^\d\+\-\s\(\)]/', '', trim( (string) $phone ) );
	}
}
