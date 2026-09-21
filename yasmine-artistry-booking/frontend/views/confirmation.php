<?php
/**
 * Frontend verified confirmation view.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ref     = sanitize_text_field( $_GET['ref'] ?? '' );
$booking = YAB_Booking::get_by_reference( $ref );

if ( ! $booking ) :
	?>
	<div class="yab-booking-container">
		<div class="yab-alert yab-alert-error">
			<?php esc_html_e( 'Booking reference could not be found.', 'yasmine-artistry-booking' ); ?>
		</div>
		<p><a href="<?php echo esc_url( remove_query_arg( array( 'yab_action', 'ref' ) ) ); ?>" class="yab-btn yab-btn-primary"><?php esc_html_e( '← Book an Appointment', 'yasmine-artistry-booking' ); ?></a></p>
	</div>
	<?php
	return;
endif;

$service      = YAB_Service::get( $booking->service_id );
$location     = YAB_Location::get( $booking->location_id );
$placeholders = YAB_Email::get_placeholders( $booking );
?>
<div class="yab-booking-container yab-confirmation-card">
	<div class="yab-conf-icon">
		<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
	</div>

	<h2 class="yab-conf-title"><?php esc_html_e( 'Appointment Confirmed!', 'yasmine-artistry-booking' ); ?></h2>
	<p class="yab-conf-sub"><?php esc_html_e( 'Your deposit has been verified and your home service appointment is booked.', 'yasmine-artistry-booking' ); ?></p>

	<div class="yab-summary-card">
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Booking Reference', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val yab-highlight"><?php echo esc_html( $booking->booking_reference ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Service', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $service ? $service->name : 'Home Service' ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Appointment Date', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $placeholders['booking_date'] ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Scheduled Time', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $placeholders['booking_time'] ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Service Address', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $booking->service_address . ( $location ? ', ' . $location->name : '' ) ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Deposit Paid', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val" style="color: #2f855a;"><?php echo esc_html( $placeholders['deposit_amount'] ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Balance Due on Service', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $placeholders['balance'] ); ?></span>
		</div>
	</div>

	<p class="yab-note-text">
		<?php
		printf(
			/* translators: %s: customer email */
			esc_html__( 'A full confirmation email with a calendar (.ics) invitation has been sent to %s.', 'yasmine-artistry-booking' ),
			'<strong>' . esc_html( $booking->customer_email ) . '</strong>'
		);
		?>
	</p>

	<div class="yab-actions" style="justify-content: center; gap: 12px; margin-top: 24px;">
		<a href="<?php echo esc_url( $placeholders['manage_link'] ); ?>" class="yab-btn yab-btn-primary">
			<?php esc_html_e( 'Manage or Reschedule Appointment', 'yasmine-artistry-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( remove_query_arg( array( 'yab_action', 'ref' ) ) ); ?>" class="yab-btn yab-btn-secondary">
			<?php esc_html_e( 'Book Another Service', 'yasmine-artistry-booking' ); ?>
		</a>
	</div>
</div>
