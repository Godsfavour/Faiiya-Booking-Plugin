<?php
/**
 * REST API Endpoints for frontend booking, availability, payments, and self-service.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_REST_API {

	const NAMESPACE = 'yab/v1';

	/**
	 * Register REST routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Define endpoints.
	 */
	public static function register_routes() {
		// 1. Categories
		register_rest_route(
			self::NAMESPACE,
			'/categories',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_categories' ),
				'permission_callback' => '__return_true',
			)
		);

		// 2. Services
		register_rest_route(
			self::NAMESPACE,
			'/services',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_services' ),
				'permission_callback' => '__return_true',
			)
		);

		// 3. Locations
		register_rest_route(
			self::NAMESPACE,
			'/locations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_locations' ),
				'permission_callback' => '__return_true',
			)
		);

		// 4. Quote calculation
		register_rest_route(
			self::NAMESPACE,
			'/quote',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_quote' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service_id'  => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'location_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				),
			)
		);

		// 5. Availability slots
		register_rest_route(
			self::NAMESPACE,
			'/availability',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_availability' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'date'       => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		// 6. Submit Booking & Initiate Paystack
		register_rest_route(
			self::NAMESPACE,
			'/bookings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_booking' ),
				'permission_callback' => '__return_true',
			)
		);

		// 7. Verify Payment Reference
		register_rest_route(
			self::NAMESPACE,
			'/verify-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'verify_payment' ),
				'permission_callback' => '__return_true',
			)
		);

		// 8. Paystack Webhook
		register_rest_route(
			self::NAMESPACE,
			'/paystack-webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_paystack_webhook' ),
				'permission_callback' => '__return_true',
			)
		);

		// 9. Customer Self-Service View
		register_rest_route(
			self::NAMESPACE,
			'/manage-booking',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_managed_booking' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		// 10. Reschedule Booking
		register_rest_route(
			self::NAMESPACE,
			'/reschedule',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'reschedule_booking' ),
				'permission_callback' => '__return_true',
			)
		);

		// 11. Cancel Booking
		register_rest_route(
			self::NAMESPACE,
			'/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'cancel_booking' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function get_categories() {
		$categories = YAB_Category::get_all( array( 'active_only' => true ) );
		return rest_ensure_response( array( 'success' => true, 'data' => $categories ) );
	}

	public static function get_services( WP_REST_Request $request ) {
		$cat_id   = $request->get_param( 'category_id' );
		$args     = array( 'active_only' => true );
		if ( $cat_id ) {
			$args['category_id'] = absint( $cat_id );
		}

		$services = YAB_Service::get_all( $args );
		return rest_ensure_response( array( 'success' => true, 'data' => $services ) );
	}

	public static function get_locations( WP_REST_Request $request ) {
		$args = array( 'active_only' => true );
		$service_id = absint( $request->get_param( 'service_id' ) );
		if ( $service_id > 0 ) {
			$args['service_id'] = $service_id;
		}

		$locations = YAB_Location::get_all( $args );
		return rest_ensure_response( array( 'success' => true, 'data' => $locations ) );
	}

	public static function get_quote( WP_REST_Request $request ) {
		$service_id     = absint( $request->get_param( 'service_id' ) );
		$location_id    = absint( $request->get_param( 'location_id' ) );
		$payment_choice = sanitize_key( $request->get_param( 'payment_choice' ) ?: 'deposit' );
		$extra_looks    = absint( $request->get_param( 'extra_looks' ) ?: 0 );

		$quote = YAB_Pricing::calculate_quote( $service_id, $location_id, $payment_choice, $extra_looks );
		if ( is_wp_error( $quote ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $quote->get_error_message() ), 400 );
		}

		return rest_ensure_response( array( 'success' => true, 'data' => $quote ) );
	}

	public static function get_availability( WP_REST_Request $request ) {
		$service_id = absint( $request->get_param( 'service_id' ) );
		$date       = sanitize_text_field( $request->get_param( 'date' ) );

		$slots = YAB_Availability::get_available_slots( $date, $service_id );
		return rest_ensure_response( array( 'success' => true, 'data' => $slots ) );
	}

	public static function create_booking( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// Create pending reservation with 15-minute slot lock
		$result = YAB_Booking::create_pending_booking( $params );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 400 );
		}

		$booking_id = $result['booking_id'];
		$quote      = $result['quote'];

		// If deposit required > 0, initialize Paystack transaction
		$payment_init = null;
		if ( $quote['deposit_required'] > 0 ) {
			$callback_url = $params['callback_url'] ?? '';
			$payment_init = YAB_Paystack::initialize_transaction( $booking_id, $callback_url );

			if ( is_wp_error( $payment_init ) ) {
				return new WP_REST_Response(
					array(
						'success'           => false,
						'message'           => $payment_init->get_error_message(),
						'booking_reference' => $result['booking_reference'],
					),
					400
				);
			}
		} else {
			// No deposit required: instantly confirm
			YAB_Booking::confirm_payment( $booking_id, 0.00, 'NO-DEPOSIT' );
		}

		return rest_ensure_response(
			array(
				'success'           => true,
				'booking_id'        => $booking_id,
				'booking_reference' => $result['booking_reference'],
				'secure_token'      => $result['secure_token'],
				'quote'             => $quote,
				'payment'           => $payment_init,
			)
		);
	}

	public static function verify_payment( WP_REST_Request $request ) {
		$params    = $request->get_json_params();
		$reference = sanitize_text_field( $params['reference'] ?? '' );

		if ( empty( $reference ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Missing transaction reference.', 'yasmine-artistry-booking' ) ), 400 );
		}

		$verify_res = YAB_Paystack::verify_transaction( $reference );
		if ( is_wp_error( $verify_res ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $verify_res->get_error_message() ), 400 );
		}

		$process_res = YAB_Paystack::process_verified_payment( $verify_res );
		if ( is_wp_error( $process_res ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $process_res->get_error_message() ), 400 );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Payment verified and appointment confirmed.', 'yasmine-artistry-booking' ),
			)
		);
	}

	public static function handle_paystack_webhook( WP_REST_Request $request ) {
		$body      = $request->get_body();
		$signature = $request->get_header( 'x-paystack-signature' );

		$secret_key = YAB_Paystack::get_secret_key();

		// HMAC verification
		if ( ! YAB_Security::verify_paystack_signature( $body, $signature, $secret_key ) ) {
			YAB_Logger::log( 'webhook_rejected', 'Invalid Paystack webhook HMAC signature received' );
			return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
		}

		$event = json_decode( $body, true );
		if ( empty( $event['event'] ) ) {
			return new WP_REST_Response( array( 'status' => 'ignored' ), 200 );
		}

		if ( 'charge.success' === $event['event'] && ! empty( $event['data'] ) ) {
			$res = YAB_Paystack::process_verified_payment( $event['data'] );
			if ( is_wp_error( $res ) ) {
				YAB_Logger::log( 'webhook_error', 'Webhook process failed: ' . $res->get_error_message(), null, (array) $event['data'] );
			}
		}

		return new WP_REST_Response( array( 'status' => 'received' ), 200 );
	}

	public static function get_managed_booking( WP_REST_Request $request ) {
		$token   = sanitize_text_field( $request->get_param( 'token' ) );
		$booking = YAB_Booking::get_by_token( $token );

		if ( ! $booking ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid or expired booking link.', 'yasmine-artistry-booking' ) ), 404 );
		}

		$service  = YAB_Service::get( $booking->service_id );
		$location = YAB_Location::get( $booking->location_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'id'                 => $booking->id,
					'booking_reference'  => $booking->booking_reference,
					'customer_name'      => $booking->customer_name,
					'customer_email'     => $booking->customer_email,
					'customer_phone'     => $booking->customer_phone,
					'service_name'       => $service ? $service->name : '',
					'service_id'         => $booking->service_id,
					'location_name'      => $location ? $location->name : '',
					'service_address'    => $booking->service_address,
					'appointment_date'   => $booking->appointment_date,
					'start_time'         => $booking->start_time,
					'end_time'           => $booking->end_time,
					'booking_status'     => $booking->booking_status,
					'payment_status'     => $booking->payment_status,
					'total_amount'       => $booking->total_amount,
					'deposit_paid'       => $booking->deposit_paid,
					'balance_remaining'  => $booking->balance_remaining,
					'currency'           => $booking->currency,
					'reschedule_count'   => $booking->reschedule_count,
					'can_reschedule'     => ( 'confirmed' === $booking->booking_status && intval( $booking->reschedule_count ) < absint( YAB_Settings::get( 'rules', 'max_reschedules', 2 ) ) ),
				),
			)
		);
	}

	public static function reschedule_booking( WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$token    = sanitize_text_field( $params['token'] ?? '' );
		$new_date = sanitize_text_field( $params['date'] ?? '' );
		$new_time = sanitize_text_field( $params['time'] ?? '' );

		$booking = YAB_Booking::get_by_token( $token );
		if ( ! $booking ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid token.', 'yasmine-artistry-booking' ) ), 403 );
		}

		$res = YAB_Booking::reschedule( $booking->id, $new_date, $new_time, $token );
		if ( is_wp_error( $res ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $res->get_error_message() ), 400 );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => __( 'Appointment successfully rescheduled.', 'yasmine-artistry-booking' ) ) );
	}

	public static function cancel_booking( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$token  = sanitize_text_field( $params['token'] ?? '' );
		$reason = sanitize_textarea_field( $params['reason'] ?? 'Customer requested cancellation.' );

		$booking = YAB_Booking::get_by_token( $token );
		if ( ! $booking ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid token.', 'yasmine-artistry-booking' ) ), 403 );
		}

		$res = YAB_Booking::cancel( $booking->id, $reason, $token );
		if ( is_wp_error( $res ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $res->get_error_message() ), 400 );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => __( 'Appointment successfully cancelled.', 'yasmine-artistry-booking' ) ) );
	}
}
