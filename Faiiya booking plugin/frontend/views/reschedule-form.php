<?php
/**
 * Customer self-service portal for viewing, rescheduling, or cancelling an appointment.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token   = sanitize_text_field( $_GET['token'] ?? '' );
$booking = YAB_Booking::get_by_token( $token );

if ( ! $booking ) :
	?>
	<div class="yab-booking-container">
		<div class="yab-alert yab-alert-error">
			<?php esc_html_e( 'Access denied: The requested appointment link is invalid or expired.', 'yasmine-artistry-booking' ); ?>
		</div>
	</div>
	<?php
	return;
endif;

$service        = YAB_Service::get( $booking->service_id );
$location       = YAB_Location::get( $booking->location_id );
$placeholders   = YAB_Email::get_placeholders( $booking );
$max_reschedule = absint( YAB_Settings::get( 'rules', 'max_reschedules', 2 ) );
$can_reschedule = ( 'confirmed' === $booking->booking_status && intval( $booking->reschedule_count ) < $max_reschedule );
$can_cancel     = ( 'confirmed' === $booking->booking_status && (bool) YAB_Settings::get( 'rules', 'cancellation_allowed', 1 ) );
?>
<div class="yab-booking-container" id="yab-portal-container" data-token="<?php echo esc_attr( $booking->secure_token ); ?>" data-service-id="<?php echo esc_attr( $booking->service_id ); ?>">

	<div class="yab-portal-header">
		<h2><?php esc_html_e( 'Manage Appointment', 'yasmine-artistry-booking' ); ?></h2>
		<span class="yab-status-badge yab-status-<?php echo esc_attr( $booking->booking_status ); ?>">
			<?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->booking_status ) ) ); ?>
		</span>
	</div>

	<div id="yab-portal-alert" class="yab-alert" style="display: none;"></div>

	<div class="yab-summary-card">
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Booking Reference', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val yab-highlight"><?php echo esc_html( $booking->booking_reference ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Client Name', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $booking->customer_name ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Service', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( $service ? $service->name : 'Home Service' ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Scheduled Date & Time', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val" style="color: #2b6cb0; font-weight: 700;"><?php echo esc_html( $placeholders['booking_date'] . ' at ' . $placeholders['booking_time'] ); ?></span>
		</div>
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Home Address', 'yasmine-artistry-booking' ); ?></span>
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
		<div class="yab-summary-row">
			<span class="yab-sum-label"><?php esc_html_e( 'Reschedule Count', 'yasmine-artistry-booking' ); ?></span>
			<span class="yab-sum-val"><?php echo esc_html( sprintf( '%d of %d allowed', $booking->reschedule_count, $max_reschedule ) ); ?></span>
		</div>
	</div>

	<?php if ( $can_reschedule || $can_cancel ) : ?>
		<div class="yab-actions" style="margin-top: 24px; gap: 12px;">
			<?php if ( $can_reschedule ) : ?>
				<button type="button" class="yab-btn yab-btn-primary" id="yab-toggle-reschedule-btn">
					<?php esc_html_e( 'Reschedule Appointment', 'yasmine-artistry-booking' ); ?>
				</button>
			<?php endif; ?>
			<?php if ( $can_cancel ) : ?>
				<button type="button" class="yab-btn yab-btn-danger" id="yab-toggle-cancel-btn">
					<?php esc_html_e( 'Cancel Appointment', 'yasmine-artistry-booking' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<!-- Reschedule Section -->
		<div id="yab-reschedule-section" class="yab-portal-subpanel" style="display: none; margin-top: 24px;">
			<h4 class="yab-pane-title"><?php esc_html_e( 'Select New Date & Time Slot', 'yasmine-artistry-booking' ); ?></h4>
			<div class="yab-form-row">
				<div class="yab-form-group">
					<label for="yab-reschedule-date" class="yab-label"><?php esc_html_e( 'New Appointment Date', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
					<input type="date" id="yab-reschedule-date" class="yab-input">
				</div>
			</div>
			<div class="yab-form-group">
				<label class="yab-label"><?php esc_html_e( 'Available Time Slots', 'yasmine-artistry-booking' ); ?></label>
				<div id="yab-reschedule-slots" class="yab-slots-grid">
					<p class="yab-placeholder-text"><?php esc_html_e( 'Pick a date above to load available slots.', 'yasmine-artistry-booking' ); ?></p>
				</div>
				<input type="hidden" id="yab-reschedule-time">
			</div>
			<div class="yab-actions" style="margin-top: 16px;">
				<button type="button" class="yab-btn yab-btn-secondary" id="yab-cancel-reschedule-mode"><?php esc_html_e( 'Cancel', 'yasmine-artistry-booking' ); ?></button>
				<button type="button" class="yab-btn yab-btn-primary" id="yab-submit-reschedule-btn" disabled>
					<?php esc_html_e( 'Confirm New Time', 'yasmine-artistry-booking' ); ?>
				</button>
			</div>
		</div>

		<!-- Cancellation Section -->
		<div id="yab-cancel-section" class="yab-portal-subpanel" style="display: none; margin-top: 24px;">
			<h4 class="yab-pane-title" style="color: #e53e3e;"><?php esc_html_e( 'Confirm Cancellation', 'yasmine-artistry-booking' ); ?></h4>
			<p><?php esc_html_e( 'Are you sure you want to cancel this appointment? Please state the reason below.', 'yasmine-artistry-booking' ); ?></p>
			<div class="yab-form-group">
				<label for="yab-cancel-reason" class="yab-label"><?php esc_html_e( 'Reason for Cancellation', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
				<textarea id="yab-cancel-reason" class="yab-textarea" rows="3" placeholder="Please let us know why you need to cancel..."></textarea>
			</div>
			<div class="yab-actions" style="margin-top: 16px;">
				<button type="button" class="yab-btn yab-btn-secondary" id="yab-cancel-cancel-mode"><?php esc_html_e( 'Keep Appointment', 'yasmine-artistry-booking' ); ?></button>
				<button type="button" class="yab-btn yab-btn-danger" id="yab-submit-cancel-btn">
					<?php esc_html_e( 'Yes, Cancel Appointment', 'yasmine-artistry-booking' ); ?>
				</button>
			</div>
		</div>

	<?php else : ?>
		<p class="yab-note-text" style="margin-top: 20px;">
			<?php esc_html_e( 'This appointment can no longer be modified online. Please contact customer support if you need assistance.', 'yasmine-artistry-booking' ); ?>
		</p>
	<?php endif; ?>

</div>
