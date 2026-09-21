<?php
/**
 * Frontend execution and shortcodes.
 */
class Yasmine_Booking_Public {

	public function __construct() {
		add_shortcode( 'yasmine_booking_form', array( $this, 'render_booking_form_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// AJAX entry points
		add_action( 'wp_ajax_yasmine_get_available_slots', array( $this, 'get_available_slots' ) );
		add_action( 'wp_ajax_nopriv_yasmine_get_available_slots', array( $this, 'get_available_slots' ) );
		add_action( 'wp_ajax_yasmine_submit_booking', array( $this, 'submit_booking' ) );
		add_action( 'wp_ajax_nopriv_yasmine_submit_booking', array( $this, 'submit_booking' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'yasmine-booking-css', YASMINE_BOOKING_URL . 'public/css/yasmine-booking-public.css', array(), YASMINE_BOOKING_VERSION );
		wp_enqueue_script( 'yasmine-booking-js', YASMINE_BOOKING_URL . 'public/js/yasmine-booking-public.js', array( 'jquery' ), YASMINE_BOOKING_VERSION, true );
		
		wp_localize_script( 'yasmine-booking-js', 'yasmine_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'yasmine_booking_nonce' ),
		) );
	}

	public function render_booking_form_shortcode() {
		global $wpdb;
		$table_services = $wpdb->prefix . 'yasmine_services';
		$services = $wpdb->get_results( "SELECT * FROM $table_services" );
		$business = get_option( 'yasmine_booking_business_details' );
		$paystack = Yasmine_Booking_Integrations::get_paystack_config();

		ob_start();
		include YASMINE_BOOKING_PATH . 'templates/booking-form-template.php';
		return ob_get_clean();
	}

	/**
	 * AJAX helper to output free time slots.
	 */
	public function get_available_slots() {
		check_ajax_referer( 'yasmine_booking_nonce', 'security' );

		$service_id = sanitize_text_field( $_POST['service_id'] ?? '' );
		$date = sanitize_text_field( $_POST['date'] ?? '' );

		if ( empty( $service_id ) || empty( $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid arguments' ) );
		}

		// Generate operational slots e.g. 09:00 - 17:00
		$business = get_option( 'yasmine_booking_business_details' );
		$start_str = $business['working_hours_start'] ?? '09:00';
		$end_str = $business['working_hours_end'] ?? '17:00';

		$start_time = strtotime( "$date $start_str" );
		$end_time = strtotime( "$date $end_str" );

		// Hourly intervals
		$slots = array();
		$current = $start_time;
		while ( $current < $end_time ) {
			$datetime_str = date( 'Y-m-d H:i:s', $current );
			
			// Verify available
			if ( Yasmine_Booking_DB::is_slot_available( $service_id, $datetime_str ) ) {
				$slots[] = array(
					'time' => date( 'H:i', $current ),
					'datetime' => $datetime_str
				);
			}
			$current += 3600; // Increment 1 hour
		}

		wp_send_json_success( array( 'slots' => $slots ) );
	}

	/**
	 * AJAX logic for form submission.
	 * Employs Strict Row locking with WPDB to prevent race-condition booking overlaps.
	 */
	public function submit_booking() {
		check_ajax_referer( 'yasmine_booking_nonce', 'security' );

		global $wpdb;
		$service_id = sanitize_text_field( $_POST['service_id'] ?? '' );
		$datetime = sanitize_text_field( $_POST['datetime'] ?? '' );
		$name = sanitize_text_field( $_POST['name'] ?? '' );
		$email = sanitize_email( $_POST['email'] ?? '' );
		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$payment_method = sanitize_text_field( $_POST['payment_method'] ?? 'bank_transfer' );
		$notes = sanitize_textarea_field( $_POST['notes'] ?? '' );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Please supply a valid email address.' ) );
		}

		// Perform row-locking transaction to block overlapping appointments
		$wpdb->query( 'START TRANSACTION' );

		// Double booking check inside transaction
		if ( ! Yasmine_Booking_DB::is_slot_available( $service_id, $datetime ) ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( array( 'message' => 'This slot was just reserved by another customer. Please choose a different slot.' ) );
		}

		// Load service price details
		$service = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}yasmine_services WHERE id = %s FOR UPDATE",
			$service_id
		) );

		if ( ! $service ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( array( 'message' => 'Service not found.' ) );
		}

		// Calculate deposit
		$deposit = 0.00;
		if ( $service->deposit_value > 0 ) {
			if ( $service->deposit_type === 'percentage' ) {
				$deposit = ($service->price * $service->deposit_value) / 100;
			} else {
				$deposit = $service->deposit_value;
			}
		}
		$balance = $service->price - $deposit;

		// Create Booking Entity
		$booking_id = uniqid( 'yb_' );
		$reference_code = strtoupper( wp_generate_password( 8, false ) );
		$status = $payment_method === 'paystack' ? 'pending_payment' : 'pending_payment';

		$inserted = $wpdb->insert( $wpdb->prefix . 'yasmine_bookings', array(
			'id' => $booking_id,
			'service_id' => $service_id,
			'customer_name' => $name,
			'customer_email' => $email,
			'customer_phone' => $phone,
			'datetime' => $datetime,
			'status' => $status,
			'payment_method' => $payment_method,
			'notes' => $notes,
			'deposit_paid' => $deposit,
			'balance_due' => $balance,
			'reference_code' => $reference_code,
		) );

		if ( ! $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( array( 'message' => 'Database failure. Please retry.' ) );
		}

		$wpdb->query( 'COMMIT' );

		// If bank transfer - generate details response, trigger notifications
		if ( $payment_method === 'bank_transfer' ) {
			do_action( 'yasmine_booking_created_bank', $booking_id );
			
			wp_send_json_success( array(
				'booking_id' => $booking_id,
				'reference_code' => $reference_code,
				'deposit' => $deposit,
				'balance' => $balance,
				'payment_method' => 'bank_transfer'
			) );
		} else {
			// Paystack initialization process
			$callback_url = add_query_arg( array(
				'yasmine_action' => 'paystack_callback',
				'booking_id' => $booking_id,
			), home_url() );

			$paystack_res = Yasmine_Booking_Integrations::initialize_paystack_payment( $booking_id, $deposit, $email, $callback_url );

			if ( is_wp_error( $paystack_res ) ) {
				wp_send_json_error( array( 'message' => $paystack_res->get_error_message() ) );
			}

			wp_send_json_success( array(
				'payment_url' => $paystack_res['authorization_url'],
				'payment_method' => 'paystack'
			) );
		}
	}
}
