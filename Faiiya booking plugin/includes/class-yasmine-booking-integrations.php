<?php
/**
 * Out-of-the-box support for API Integrations.
 * Each module is fully isolated and only fires when enabled with valid keys.
 */
class Yasmine_Booking_Integrations {

	// ==========================================
	// PAYSTACK MODULE
	// ==========================================

	public static function get_paystack_config() {
		return get_option( 'yasmine_booking_paystack_config', array(
			'enabled' => false,
			'public_key' => '',
			'secret_key' => '',
			'test_mode' => true,
			'validated' => false,
		) );
	}

	public static function initialize_paystack_payment( $booking_id, $amount, $email, $callback_url ) {
		$config = self::get_paystack_config();
		if ( ! $config['enabled'] || empty( $config['secret_key'] ) ) {
			return new WP_Error( 'paystack_disabled', 'Paystack integration is currently disabled.' );
		}

		$endpoint = 'https://api.paystack.co/transaction/initialize';
		$payload = array(
			'email' => $email,
			'amount' => round( $amount * 100 ), // Paystack counts in kobo/cents
			'reference' => 'YBK-' . $booking_id . '-' . time(),
			'callback_url' => $callback_url,
			'metadata' => array(
				'booking_id' => $booking_id,
			),
		);

		$response = wp_remote_post( $endpoint, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $config['secret_key'],
				'Content-Type'  => 'application/json',
			),
			'body' => json_encode( $payload ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['status'] ) || ! $body['status'] ) {
			return new WP_Error( 'paystack_error', $body['message'] ?? 'Unable to initialize transaction' );
		}

		return $body['data']; // Returns authorization_url, access_code, reference
	}

	public static function verify_paystack_payment( $reference ) {
		$config = self::get_paystack_config();
		if ( empty( $config['secret_key'] ) ) {
			return false;
		}

		$endpoint = 'https://api.paystack.co/transaction/verify/' . rawurlencode( $reference );
		$response = wp_remote_get( $endpoint, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $config['secret_key'],
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['status'] ) && $body['status'] && $body['data']['status'] === 'success' ) {
			return $body['data']; // Returns full transaction detail payload
		}

		return false;
	}


	// ==========================================
	// GOOGLE CALENDAR MODULE (OAuth 2.0)
	// ==========================================

	public static function get_google_config() {
		return get_option( 'yasmine_booking_google_config', array(
			'enabled' => false,
			'client_id' => '',
			'client_secret' => '',
			'redirect_uri' => admin_url( 'admin.php?page=yasmine-booking-settings&tab=google-calendar' ),
			'access_token' => '',
			'refresh_token' => '',
			'token_expiry' => 0,
			'connected' => false,
		) );
	}

	public static function get_google_oauth_url() {
		$config = self::get_google_config();
		if ( empty( $config['client_id'] ) ) {
			return '';
		}

		$auth_endpoint = 'https://accounts.google.com/o/oauth2/v2/auth';
		$params = array(
			'client_id' => $config['client_id'],
			'redirect_uri' => $config['redirect_uri'],
			'response_type' => 'code',
			'scope' => 'https://www.googleapis.com/auth/calendar.events',
			'access_type' => 'offline',
			'prompt' => 'consent',
		);

		return add_query_arg( $params, $auth_endpoint );
	}

	public static function refresh_google_token() {
		$config = self::get_google_config();
		if ( empty( $config['refresh_token'] ) ) {
			return false;
		}

		// Check if token is still valid (using 5-minute safety margin)
		if ( time() < ($config['token_expiry'] - 300) ) {
			return $config['access_token'];
		}

		$endpoint = 'https://oauth2.googleapis.com/token';
		$response = wp_remote_post( $endpoint, array(
			'body' => array(
				'client_id' => $config['client_id'],
				'client_secret' => $config['client_secret'],
				'refresh_token' => $config['refresh_token'],
				'grant_type' => 'refresh_token',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['access_token'] ) ) {
			$config['access_token'] = $body['access_token'];
			$config['token_expiry'] = time() + intval( $body['expires_in'] );
			update_option( 'yasmine_booking_google_config', $config );
			return $body['access_token'];
		}

		return false;
	}

	public static function create_calendar_event( $booking_id ) {
		$config = self::get_google_config();
		if ( ! $config['enabled'] ) {
			return false;
		}

		$access_token = self::refresh_google_token();
		if ( ! $access_token ) {
			return false;
		}

		global $wpdb;
		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT b.*, s.name as service_name, s.duration FROM {$wpdb->prefix}yasmine_bookings b 
			 JOIN {$wpdb->prefix}yasmine_services s ON b.service_id = s.id 
			 WHERE b.id = %s",
			$booking_id
		) );

		if ( ! $booking ) {
			return false;
		}

		$start_time = strtotime( $booking->datetime );
		$end_time = $start_time + ($booking->duration * 60);

		$event_payload = array(
			'summary' => 'Booking: ' . $booking->service_name . ' - ' . $booking->customer_name,
			'description' => 'Customer Email: ' . $booking->customer_email . '\nPhone: ' . $booking->customer_phone . '\nNotes: ' . $booking->notes,
			'start' => array(
				'dateTime' => date( 'c', $start_time ),
				'timeZone' => get_option( 'timezone_string' ) ?: 'UTC',
			),
			'end' => array(
				'dateTime' => date( 'c', $end_time ),
				'timeZone' => get_option( 'timezone_string' ) ?: 'UTC',
			),
		);

		$endpoint = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
		$response = wp_remote_post( $endpoint, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json',
			),
			'body' => json_encode( $event_payload ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['id'] ) ) {
			$wpdb->update(
				$wpdb->prefix . 'yasmine_bookings',
				array( 'google_event_id' => $body['id'] ),
				array( 'id' => $booking_id )
			);
			return $body['id'];
		}

		return false;
	}

	public static function delete_calendar_event( $event_id ) {
		$config = self::get_google_config();
		if ( ! $config['enabled'] || empty( $event_id ) ) {
			return false;
		}

		$access_token = self::refresh_google_token();
		if ( ! $access_token ) {
			return false;
		}

		$endpoint = 'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . rawurlencode( $event_id );
		wp_remote_request( $endpoint, array(
			'method' => 'DELETE',
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 10,
		) );

		return true;
	}


	// ==========================================
	// WHATSAPP BUSINESS API MODULE
	// ==========================================

	public static function get_whatsapp_config() {
		return get_option( 'yasmine_booking_whatsapp_config', array(
			'enabled' => false,
			'access_token' => '',
			'phone_number_id' => '',
			'template_name' => '',
			'validated' => false,
		) );
	}

	public static function send_whatsapp_message( $booking_id, $recipient_phone, $template_name, $parameters ) {
		$config = self::get_whatsapp_config();
		if ( ! $config['enabled'] || empty( $config['phone_number_id'] ) ) {
			return false;
		}

		$endpoint = 'https://graph.facebook.com/v17.0/' . $config['phone_number_id'] . '/messages';

		// format parameters into Meta template parameters structure
		$formatted_params = array();
		foreach ( $parameters as $param ) {
			$formatted_params[] = array(
				'type' => 'text',
				'text' => $param,
			);
		}

		$payload = array(
			'messaging_product' => 'whatsapp',
			'to' => $recipient_phone,
			'type' => 'template',
			'template' => array(
				'name' => $template_name,
				'language' => array(
					'code' => 'en_US',
				),
				'components' => array(
					array(
						'type' => 'body',
						'parameters' => $formatted_params,
					),
				),
			),
		);

		$response = wp_remote_post( $endpoint, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $config['access_token'],
				'Content-Type' => 'application/json',
			),
			'body' => json_encode( $payload ),
			'timeout' => 15,
		) );

		global $wpdb;
		$status = 'success';
		$error_msg = null;

		if ( is_wp_error( $response ) ) {
			$status = 'failed';
			$error_msg = $response->get_error_message();
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['error'] ) ) {
				$status = 'failed';
				$error_msg = $body['error']['message'];
			}
		}

		// Log into logs database
		$wpdb->insert( $wpdb->prefix . 'yasmine_logs', array(
			'booking_id' => $booking_id,
			'type' => 'whatsapp',
			'recipient' => $recipient_phone,
			'content' => sprintf( 'Template: %s | Params: %s', $template_name, implode( ', ', $parameters ) ),
			'status' => $status,
			'error_message' => $error_msg,
		) );

		return $status === 'success';
	}


	// ==========================================
	// TWILIO SMS MODULE
	// ==========================================

	public static function get_twilio_config() {
		return get_option( 'yasmine_booking_twilio_config', array(
			'enabled' => false,
			'account_sid' => '',
			'auth_token' => '',
			'sender_phone' => '',
			'validated' => false,
		) );
	}

	public static function send_sms_message( $booking_id, $recipient_phone, $message_text ) {
		$config = self::get_twilio_config();
		if ( ! $config['enabled'] || empty( $config['account_sid'] ) ) {
			return false;
		}

		$endpoint = 'https://api.twilio.com/2010-04-01/Accounts/' . $config['account_sid'] . '/Messages.json';

		$response = wp_remote_post( $endpoint, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $config['account_sid'] . ':' . $config['auth_token'] ),
			),
			'body' => array(
				'To' => $recipient_phone,
				'From' => $config['sender_phone'],
				'Body' => $message_text,
			),
			'timeout' => 15,
		) );

		global $wpdb;
		$status = 'success';
		$error_msg = null;

		if ( is_wp_error( $response ) ) {
			$status = 'failed';
			$error_msg = $response->get_error_message();
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['status'] ) && $body['status'] === 'failed' ) {
				$status = 'failed';
				$error_msg = $body['message'] ?? 'Unknown Twilio Error';
			}
		}

		// Log into logs database
		$wpdb->insert( $wpdb->prefix . 'yasmine_logs', array(
			'booking_id' => $booking_id,
			'type' => 'sms',
			'recipient' => $recipient_phone,
			'content' => $message_text,
			'status' => $status,
			'error_message' => $error_msg,
		) );

		return $status === 'success';
	}
}
