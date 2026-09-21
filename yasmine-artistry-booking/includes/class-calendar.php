<?php
/**
 * RFC 5545 iCalendar (.ics) export generator.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Calendar {

	/**
	 * Generate an RFC 5545 compliant .ics string for a booking.
	 *
	 * @param object $booking
	 * @return string
	 */
	public static function generate_ics( $booking ) {
		$service  = YAB_Service::get( $booking->service_id );
		$location = YAB_Location::get( $booking->location_id );

		$service_name  = $service ? $service->name : 'Home Beauty Appointment';
		$location_name = $location ? $location->name : '';
		$full_address  = $booking->service_address . ( $location_name ? ', ' . $location_name : '' );

		$start_dt = new DateTime( $booking->appointment_date . ' ' . $booking->start_time, wp_timezone() );
		$end_dt   = new DateTime( $booking->appointment_date . ' ' . $booking->end_time, wp_timezone() );

		// RFC 5545 UTC timestamps
		$start_utc = clone $start_dt;
		$start_utc->setTimezone( new DateTimeZone( 'UTC' ) );

		$end_utc = clone $end_dt;
		$end_utc->setTimezone( new DateTimeZone( 'UTC' ) );

		$now_utc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		$uid     = md5( $booking->booking_reference . '@yasmineartistry.com' ) . '@yasmineartistry.com';
		$summary = self::escape_text( sprintf( '%s - Yasmine Artistry (%s)', $service_name, $booking->customer_name ) );

		$desc_lines = array(
			'Service: ' . $service_name,
			'Client: ' . $booking->customer_name,
			'Phone: ' . $booking->customer_phone,
			'Address: ' . $booking->service_address,
			'Booking Reference: ' . $booking->booking_reference,
			'Total: ' . YAB_Pricing::format_amount( $booking->total_amount ),
			'Deposit Paid: ' . YAB_Pricing::format_amount( $booking->deposit_paid ),
			'Balance Due: ' . YAB_Pricing::format_amount( $booking->balance_remaining ),
		);
		$description = self::escape_text( implode( "\n", $desc_lines ) );
		$loc_escaped = self::escape_text( $full_address );

		$ics  = "BEGIN:VCALENDAR\r\n";
		$ics .= "VERSION:2.0\r\n";
		$ics .= "PRODID:-//Yasmine Artistry//Booking Engine//EN\r\n";
		$ics .= "CALSCALE:GREGORIAN\r\n";
		$ics .= "METHOD:PUBLISH\r\n";
		$ics .= "BEGIN:VEVENT\r\n";
		$ics .= "UID:" . $uid . "\r\n";
		$ics .= "DTSTAMP:" . $now_utc->format( 'Ymd\THis\Z' ) . "\r\n";
		$ics .= "DTSTART:" . $start_utc->format( 'Ymd\THis\Z' ) . "\r\n";
		$ics .= "DTEND:" . $end_utc->format( 'Ymd\THis\Z' ) . "\r\n";
		$ics .= "SUMMARY:" . $summary . "\r\n";
		$ics .= "DESCRIPTION:" . $description . "\r\n";
		$ics .= "LOCATION:" . $loc_escaped . "\r\n";
		$ics .= "STATUS:CONFIRMED\r\n";
		$ics .= "END:VEVENT\r\n";
		$ics .= "END:VCALENDAR\r\n";

		return $ics;
	}

	/**
	 * Write .ics content to a temporary file for wp_mail attachment.
	 *
	 * @param object $booking
	 * @return string|false File path or false
	 */
	public static function get_ics_file_path( $booking ) {
		$ics_content = self::generate_ics( $booking );
		$upload_dir  = wp_upload_dir();
		$dir         = trailingslashit( $upload_dir['basedir'] ) . 'yab-calendar';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			// Write index.html to protect directory
			file_put_contents( $dir . '/index.html', '' );
		}

		$filename = sprintf( 'appointment-%s.ics', sanitize_file_name( $booking->booking_reference ) );
		$file     = $dir . '/' . $filename;

		if ( false !== file_put_contents( $file, $ics_content ) ) {
			return $file;
		}

		return false;
	}

	/**
	 * Escape special characters for RFC 5545 format.
	 *
	 * @param string $text
	 * @return string
	 */
	private static function escape_text( $text ) {
		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( ';', '\;', $text );
		$text = str_replace( ',', '\,', $text );
		$text = str_replace( "\n", '\n', $text );
		$text = str_replace( "\r", '', $text );
		return $text;
	}
}
