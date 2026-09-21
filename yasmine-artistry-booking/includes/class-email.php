<?php
/**
 * Transactional email engine with calendar attachment support.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Email {

	/**
	 * Hook email dispatchers to booking lifecycle events.
	 */
	public static function init() {
		add_action( 'yab_booking_confirmed', array( __CLASS__, 'send_confirmation_emails' ), 10, 1 );
		add_action( 'yab_booking_rescheduled', array( __CLASS__, 'send_reschedule_emails' ), 10, 3 );
		add_action( 'yab_booking_cancelled', array( __CLASS__, 'send_cancellation_emails' ), 10, 2 );
	}

	/**
	 * Dispatch customer confirmation & admin alert upon confirmed booking.
	 *
	 * @param int $booking_id
	 */
	public static function send_confirmation_emails( $booking_id ) {
		$booking = YAB_Booking::get( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$placeholders = self::get_placeholders( $booking );
		$ics_path     = YAB_Calendar::get_ics_file_path( $booking );
		$attachments  = ( $ics_path && file_exists( $ics_path ) ) ? array( $ics_path ) : array();

		// 1. Customer Email
		$customer_subj = YAB_Settings::get( 'email', 'customer_subj_conf' );
		if ( empty( $customer_subj ) ) {
			$customer_subj = 'Booking Confirmed - ' . $booking->booking_reference . ' | Yasmine Artistry';
		}
		$customer_subj = self::replace_placeholders( $customer_subj, $placeholders );
		$customer_body = self::get_confirmation_html( $booking, $placeholders );

		self::send_mail( $booking->customer_email, $customer_subj, $customer_body, $attachments );

		// 2. Admin Alert Email
		$admin_email = YAB_Settings::get( 'email', 'admin_notify_email', get_option( 'admin_email' ) );
		$admin_subj  = YAB_Settings::get( 'email', 'admin_subj_new' );
		if ( empty( $admin_subj ) ) {
			$admin_subj = 'New Booking: ' . $booking->booking_reference . ' - ' . $booking->customer_name;
		}
		$admin_subj = self::replace_placeholders( $admin_subj, $placeholders );
		$admin_body = self::get_admin_new_booking_html( $booking, $placeholders );

		self::send_mail( $admin_email, $admin_subj, $admin_body, $attachments );

		// Clean up temp ics file
		if ( $ics_path && file_exists( $ics_path ) ) {
			@unlink( $ics_path );
		}
	}

	/**
	 * Send reschedule notice to customer and admin.
	 *
	 * @param int $booking_id
	 * @param string $old_date
	 * @param string $old_time
	 */
	public static function send_reschedule_emails( $booking_id, $old_date, $old_time ) {
		$booking = YAB_Booking::get( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$placeholders             = self::get_placeholders( $booking );
		$placeholders['old_date'] = date_i18n( YAB_Settings::get( 'general', 'date_format', 'F j, Y' ), strtotime( $old_date ) );
		$placeholders['old_time'] = date_i18n( YAB_Settings::get( 'general', 'time_format', 'g:i A' ), strtotime( $old_date . ' ' . $old_time ) );

		$ics_path    = YAB_Calendar::get_ics_file_path( $booking );
		$attachments = ( $ics_path && file_exists( $ics_path ) ) ? array( $ics_path ) : array();

		$subj = YAB_Settings::get( 'email', 'customer_subj_resch' );
		if ( empty( $subj ) ) {
			$subj = 'Booking Rescheduled - ' . $booking->booking_reference . ' | Yasmine Artistry';
		}
		$subj = self::replace_placeholders( $subj, $placeholders );
		$body = self::get_reschedule_html( $booking, $placeholders );

		self::send_mail( $booking->customer_email, $subj, $body, $attachments );

		$admin_email = YAB_Settings::get( 'email', 'admin_notify_email', get_option( 'admin_email' ) );
		self::send_mail( $admin_email, 'Rescheduled: ' . $subj, $body, $attachments );

		if ( $ics_path && file_exists( $ics_path ) ) {
			@unlink( $ics_path );
		}
	}

	/**
	 * Send cancellation notice to customer.
	 *
	 * @param int $booking_id
	 * @param string $reason
	 */
	public static function send_cancellation_emails( $booking_id, $reason ) {
		$booking = YAB_Booking::get( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$placeholders           = self::get_placeholders( $booking );
		$placeholders['reason'] = esc_html( $reason );

		$subj = YAB_Settings::get( 'email', 'customer_subj_canc' );
		if ( empty( $subj ) ) {
			$subj = 'Booking Cancelled - ' . $booking->booking_reference . ' | Yasmine Artistry';
		}
		$subj = self::replace_placeholders( $subj, $placeholders );
		$body = self::get_cancellation_html( $booking, $placeholders );

		self::send_mail( $booking->customer_email, $subj, $body );

		$admin_email = YAB_Settings::get( 'email', 'admin_notify_email', get_option( 'admin_email' ) );
		self::send_mail( $admin_email, 'Cancelled: ' . $subj, $body );
	}

	/**
	 * Build key-value map for string interpolation.
	 *
	 * @param object $booking
	 * @return array
	 */
	public static function get_placeholders( $booking ) {
		$service  = YAB_Service::get( $booking->service_id );
		$location = YAB_Location::get( $booking->location_id );

		$date_fmt = YAB_Settings::get( 'general', 'date_format', 'F j, Y' );
		$time_fmt = YAB_Settings::get( 'general', 'time_format', 'g:i A' );

		$start_dt = new DateTime( $booking->appointment_date . ' ' . $booking->start_time, wp_timezone() );

		// Build self-service management portal link
		$booking_page_id = YAB_Settings::get( 'general', 'booking_page_id', 0 );
		$base_url        = $booking_page_id ? get_permalink( $booking_page_id ) : home_url( '/' );
		$manage_link     = add_query_arg(
			array(
				'yab_action' => 'manage',
				'ref'        => $booking->booking_reference,
				'token'      => $booking->secure_token,
			),
			$base_url
		);

		return array(
			'customer_name'     => $booking->customer_name,
			'booking_reference' => $booking->booking_reference,
			'service_name'      => $service ? $service->name : 'Home Beauty Appointment',
			'booking_date'      => $start_dt->format( $date_fmt ),
			'booking_time'      => $start_dt->format( $time_fmt ),
			'location'          => $location ? $location->name : 'Home Service',
			'service_address'   => $booking->service_address,
			'total_amount'      => YAB_Pricing::format_amount( $booking->total_amount ),
			'deposit_amount'    => YAB_Pricing::format_amount( $booking->deposit_paid ),
			'balance'           => YAB_Pricing::format_amount( $booking->balance_remaining ),
			'manage_link'       => esc_url( $manage_link ),
		);
	}

	/**
	 * Replace {key} occurrences with data values.
	 *
	 * @param string $content
	 * @param array $placeholders
	 * @return string
	 */
	public static function replace_placeholders( $content, $placeholders ) {
		foreach ( $placeholders as $key => $val ) {
			$content = str_replace( '{' . $key . '}', (string) $val, $content );
		}
		return $content;
	}

	/**
	 * Wrapper for wp_mail with proper HTML headers and sender information.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $html_body
	 * @param array $attachments
	 * @return bool
	 */
	private static function send_mail( $to, $subject, $html_body, $attachments = array() ) {
		$sender_name  = YAB_Settings::get( 'email', 'sender_name', 'Yasmine Artistry' );
		$sender_email = YAB_Settings::get( 'email', 'sender_email', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', esc_html( $sender_name ), sanitize_email( $sender_email ) ),
		);

		return wp_mail( $to, $subject, $html_body, $headers, $attachments );
	}

	/**
	 * HTML layout wrapper for emails.
	 *
	 * @param string $title
	 * @param string $content
	 * @return string
	 */
	private static function wrap_template( $title, $content ) {
		$business_name = esc_html( YAB_Settings::get( 'general', 'business_name', 'Yasmine Artistry' ) );
		return '<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>' . esc_html( $title ) . '</title>
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f9; margin: 0; padding: 20px; color: #2d3748; line-height: 1.6; }
				.container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
				.header { background: #1a202c; color: #ffffff; padding: 28px 24px; text-align: center; }
				.header h1 { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 0.5px; }
				.content { padding: 32px 24px; }
				.card { background: #f8fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 20px; margin: 24px 0; }
				.card-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 14px; }
				.card-row:last-child { border-bottom: none; }
				.label { color: #718096; font-weight: 500; }
				.val { color: #1a202c; font-weight: 600; text-align: right; }
				.btn { display: inline-block; background: #2b6cb0; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 16px; }
				.footer { background: #f8fafc; border-top: 1px solid #edf2f7; padding: 20px; text-align: center; font-size: 12px; color: #a0aec0; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>' . $business_name . '</h1>
				</div>
				<div class="content">
					' . $content . '
				</div>
				<div class="footer">
					&copy; ' . date( 'Y' ) . ' ' . $business_name . '. Home-Service Salon & Artistry.
				</div>
			</div>
		</body>
		</html>';
	}

	/**
	 * Customer confirmation template content.
	 */
	private static function get_confirmation_html( $booking, $p ) {
		$custom_body = YAB_Settings::get( 'email', 'customer_body_conf' );
		if ( ! empty( $custom_body ) ) {
			$content = self::replace_placeholders( $custom_body, $p );
			return self::wrap_template( 'Appointment Confirmed', wpautop( $content ) );
		}

		$content = '<h2 style="color: #2b6cb0; margin-top: 0;">Appointment Confirmed!</h2>
		<p>Dear ' . esc_html( $p['customer_name'] ) . ',</p>
		<p>Thank you for choosing Yasmine Artistry. Your appointment deposit has been successfully verified, and our artist is scheduled to provide home service at your address.</p>
		
		<div class="card">
			<div class="card-row"><span class="label">Reference:</span><span class="val">' . esc_html( $p['booking_reference'] ) . '</span></div>
			<div class="card-row"><span class="label">Service:</span><span class="val">' . esc_html( $p['service_name'] ) . '</span></div>
			<div class="card-row"><span class="label">Date:</span><span class="val">' . esc_html( $p['booking_date'] ) . '</span></div>
			<div class="card-row"><span class="label">Time:</span><span class="val">' . esc_html( $p['booking_time'] ) . '</span></div>
			<div class="card-row"><span class="label">Area / Zone:</span><span class="val">' . esc_html( $p['location'] ) . '</span></div>
			<div class="card-row"><span class="label">Home Address:</span><span class="val">' . esc_html( $p['service_address'] ) . '</span></div>
			<div class="card-row"><span class="label">Total Amount:</span><span class="val">' . esc_html( $p['total_amount'] ) . '</span></div>
			<div class="card-row"><span class="label">Deposit Paid:</span><span class="val" style="color:#2f855a;">' . esc_html( $p['deposit_amount'] ) . '</span></div>
			<div class="card-row"><span class="label">Balance Due on Service:</span><span class="val">' . esc_html( $p['balance'] ) . '</span></div>
		</div>

		<p>An interactive calendar file (.ics) has been attached to this email so you can add this appointment directly to your phone calendar.</p>
		<p>Need to view or reschedule your appointment?</p>
		<p><a href="' . esc_url( $p['manage_link'] ) . '" class="btn">Manage Your Appointment</a></p>';

		return self::wrap_template( 'Appointment Confirmed', $content );
	}

	/**
	 * Admin new booking alert.
	 */
	private static function get_admin_new_booking_html( $booking, $p ) {
		$custom_body = YAB_Settings::get( 'email', 'admin_body_new' );
		if ( ! empty( $custom_body ) ) {
			$content = self::replace_placeholders( $custom_body, $p );
			return self::wrap_template( 'New Booking Alert', wpautop( $content ) );
		}

		$content = '<h2 style="color: #2f855a; margin-top: 0;">New Confirmed Appointment</h2>
		<p>A new home service appointment has been booked and deposit confirmed via Paystack.</p>
		<div class="card">
			<div class="card-row"><span class="label">Reference:</span><span class="val">' . esc_html( $p['booking_reference'] ) . '</span></div>
			<div class="card-row"><span class="label">Client:</span><span class="val">' . esc_html( $p['customer_name'] ) . '</span></div>
			<div class="card-row"><span class="label">Phone:</span><span class="val">' . esc_html( $booking->customer_phone ) . '</span></div>
			<div class="card-row"><span class="label">Email:</span><span class="val">' . esc_html( $booking->customer_email ) . '</span></div>
			<div class="card-row"><span class="label">Service:</span><span class="val">' . esc_html( $p['service_name'] ) . '</span></div>
			<div class="card-row"><span class="label">Date & Time:</span><span class="val">' . esc_html( $p['booking_date'] . ' at ' . $p['booking_time'] ) . '</span></div>
			<div class="card-row"><span class="label">Home Address:</span><span class="val">' . esc_html( $p['service_address'] ) . '</span></div>
			<div class="card-row"><span class="label">Deposit Paid:</span><span class="val">' . esc_html( $p['deposit_amount'] ) . '</span></div>
			<div class="card-row"><span class="label">Balance Due:</span><span class="val">' . esc_html( $p['balance'] ) . '</span></div>
		</div>';

		return self::wrap_template( 'New Booking Alert', $content );
	}

	/**
	 * Reschedule notification HTML.
	 */
	private static function get_reschedule_html( $booking, $p ) {
		$custom_body = YAB_Settings::get( 'email', 'customer_body_resch' );
		if ( ! empty( $custom_body ) ) {
			$content = self::replace_placeholders( $custom_body, $p );
			return self::wrap_template( 'Appointment Rescheduled', wpautop( $content ) );
		}

		$content = '<h2 style="color: #dd6b20; margin-top: 0;">Appointment Rescheduled</h2>
		<p>Dear ' . esc_html( $p['customer_name'] ) . ',</p>
		<p>Your appointment has been successfully moved to the new requested date and time.</p>
		<div class="card">
			<div class="card-row"><span class="label">Reference:</span><span class="val">' . esc_html( $p['booking_reference'] ) . '</span></div>
			<div class="card-row"><span class="label">Service:</span><span class="val">' . esc_html( $p['service_name'] ) . '</span></div>
			<div class="card-row"><span class="label">New Date:</span><span class="val" style="color:#2b6cb0;">' . esc_html( $p['booking_date'] ) . '</span></div>
			<div class="card-row"><span class="label">New Time:</span><span class="val" style="color:#2b6cb0;">' . esc_html( $p['booking_time'] ) . '</span></div>
			<div class="card-row"><span class="label">Previous Date:</span><span class="val">' . esc_html( $p['old_date'] . ' at ' . $p['old_time'] ) . '</span></div>
			<div class="card-row"><span class="label">Service Address:</span><span class="val">' . esc_html( $p['service_address'] ) . '</span></div>
		</div>
		<p><a href="' . esc_url( $p['manage_link'] ) . '" class="btn">View Appointment</a></p>';

		return self::wrap_template( 'Appointment Rescheduled', $content );
	}

	/**
	 * Cancellation notification HTML.
	 */
	private static function get_cancellation_html( $booking, $p ) {
		$custom_body = YAB_Settings::get( 'email', 'customer_body_canc' );
		if ( ! empty( $custom_body ) ) {
			$content = self::replace_placeholders( $custom_body, $p );
			return self::wrap_template( 'Appointment Cancelled', wpautop( $content ) );
		}

		$content = '<h2 style="color: #e53e3e; margin-top: 0;">Appointment Cancelled</h2>
		<p>Dear ' . esc_html( $p['customer_name'] ) . ',</p>
		<p>Your appointment has been cancelled.</p>
		<div class="card">
			<div class="card-row"><span class="label">Reference:</span><span class="val">' . esc_html( $p['booking_reference'] ) . '</span></div>
			<div class="card-row"><span class="label">Service:</span><span class="val">' . esc_html( $p['service_name'] ) . '</span></div>
			<div class="card-row"><span class="label">Scheduled Date:</span><span class="val">' . esc_html( $p['booking_date'] . ' at ' . $p['booking_time'] ) . '</span></div>
			<div class="card-row"><span class="label">Reason:</span><span class="val">' . esc_html( $p['reason'] ) . '</span></div>
		</div>
		<p>If you have questions about refund policies or wish to book a different date, please contact us.</p>';

		return self::wrap_template( 'Appointment Cancelled', $content );
	}
}
