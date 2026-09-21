<?php
/**
 * Admin view: Single booking detail and audit ledger.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$booking_id = absint( $_GET['booking_id'] ?? 0 );
$booking    = YAB_Booking::get( $booking_id );

if ( ! $booking ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking not found.', 'yasmine-artistry-booking' ) . '</p></div>';
	return;
}

$service      = YAB_Service::get( $booking->service_id );
$location     = YAB_Location::get( $booking->location_id );
$placeholders = YAB_Email::get_placeholders( $booking );

global $wpdb;
$payments_table = YAB_Database::table( 'payments' );
$payments       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$payments_table} WHERE booking_id = %d ORDER BY id DESC", $booking->id ) );

$logs_table = YAB_Database::table( 'logs' );
$logs       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$logs_table} WHERE booking_id = %d ORDER BY id DESC", $booking->id ) );
?>

<div class="wrap yab-admin-wrap">
	<h1 class="wp-heading-inline">
		<?php printf( esc_html__( 'Booking Details: %s', 'yasmine-artistry-booking' ), esc_html( $booking->booking_reference ) ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to All Bookings', 'yasmine-artistry-booking' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Booking updated successfully.', 'yasmine-artistry-booking' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="yab-detail-grid">

		<!-- Left Column: Details & Finances -->
		<div class="yab-col-main">

			<!-- Appointment Information Card -->
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php esc_html_e( 'Appointment Information', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Service Name', 'yasmine-artistry-booking' ); ?></th>
							<td><strong><?php echo esc_html( $service ? $service->name : 'N/A' ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Scheduled Date', 'yasmine-artistry-booking' ); ?></th>
							<td><?php echo esc_html( $placeholders['booking_date'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Time Window', 'yasmine-artistry-booking' ); ?></th>
							<td>
								<strong><?php echo esc_html( $placeholders['booking_time'] ); ?></strong>
								<span class="description">
									(<?php printf( esc_html__( 'Ends at %s, buffer cleared at %s', 'yasmine-artistry-booking' ), esc_html( $booking->end_time ), esc_html( $booking->buffer_end_time ) ); ?>)
								</span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Area / Coverage Zone', 'yasmine-artistry-booking' ); ?></th>
							<td><?php echo esc_html( $location ? $location->name : 'N/A' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Home Service Address', 'yasmine-artistry-booking' ); ?></th>
							<td>
								<p><strong><?php echo nl2br( esc_html( $booking->service_address ) ); ?></strong></p>
								<?php if ( ! empty( $booking->address_notes ) ) : ?>
									<p class="description"><strong><?php esc_html_e( 'Landmark Notes:', 'yasmine-artistry-booking' ); ?></strong> <?php echo esc_html( $booking->address_notes ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<?php if ( ! empty( $booking->customer_notes ) ) : ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Client Special Notes', 'yasmine-artistry-booking' ); ?></th>
								<td><em><?php echo nl2br( esc_html( $booking->customer_notes ) ); ?></em></td>
							</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>

			<!-- Financial Ledger Breakdown -->
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php esc_html_e( 'Financial Breakdown', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<table class="widefat striped">
						<tbody>
							<tr>
								<td><?php esc_html_e( 'Base Service Price', 'yasmine-artistry-booking' ); ?></td>
								<td class="text-right"><strong><?php echo esc_html( YAB_Pricing::format_amount( $booking->base_price, $booking->currency . ' ' ) ); ?></strong></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Location Travel Surcharge', 'yasmine-artistry-booking' ); ?></td>
								<td class="text-right"><strong><?php echo esc_html( YAB_Pricing::format_amount( $booking->location_fee, $booking->currency . ' ' ) ); ?></strong></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Total Booking Amount', 'yasmine-artistry-booking' ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( YAB_Pricing::format_amount( $booking->total_amount, $booking->currency . ' ' ) ); ?></strong></td>
							</tr>
							<tr style="background: #f0fff4;">
								<td style="color: #276749;"><strong><?php esc_html_e( 'Deposit Paid (via Paystack)', 'yasmine-artistry-booking' ); ?></strong></td>
								<td class="text-right" style="color: #276749;"><strong><?php echo esc_html( YAB_Pricing::format_amount( $booking->deposit_paid, $booking->currency . ' ' ) ); ?></strong></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Balance Remaining to Collect at Appointment', 'yasmine-artistry-booking' ); ?></strong></td>
								<td class="text-right"><strong style="color: #c53030; font-size: 15px;"><?php echo esc_html( YAB_Pricing::format_amount( $booking->balance_remaining, $booking->currency . ' ' ) ); ?></strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Paystack Transactions Audit -->
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php esc_html_e( 'Paystack Payment Transactions', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<?php if ( ! empty( $payments ) ) : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Reference', 'yasmine-artistry-booking' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'yasmine-artistry-booking' ); ?></th>
									<th><?php esc_html_e( 'Channel', 'yasmine-artistry-booking' ); ?></th>
									<th><?php esc_html_e( 'Status', 'yasmine-artistry-booking' ); ?></th>
									<th><?php esc_html_e( 'Date', 'yasmine-artistry-booking' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $payments as $pay ) : ?>
									<tr>
										<td><code><?php echo esc_html( $pay->transaction_reference ); ?></code></td>
										<td><?php echo esc_html( YAB_Pricing::format_amount( $pay->amount, $pay->currency . ' ' ) ); ?></td>
										<td><?php echo esc_html( strtoupper( $pay->channel ? $pay->channel : 'online' ) ); ?></td>
										<td>
											<span class="yab-status-badge yab-status-<?php echo esc_attr( $pay->status ); ?>">
												<?php echo esc_html( ucfirst( $pay->status ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $pay->verified_at ? $pay->verified_at : $pay->created_at ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php esc_html_e( 'No payment attempts recorded for this booking.', 'yasmine-artistry-booking' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Activity Audit Logs -->
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php esc_html_e( 'Activity & Security Audit Log', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<?php if ( ! empty( $logs ) ) : ?>
						<ul class="yab-log-list">
							<?php foreach ( $logs as $l ) : ?>
								<li>
									<span class="yab-log-time"><?php echo esc_html( $l->created_at ); ?>:</span>
									<strong>[<?php echo esc_html( $l->event_type ); ?>]</strong>
									<?php echo esc_html( $l->message ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php esc_html_e( 'No log entries for this booking.', 'yasmine-artistry-booking' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<!-- Right Column: Status & Customer Contact -->
		<div class="yab-col-sidebar">

			<!-- Status & Actions Card -->
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php esc_html_e( 'Status & Actions', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<p>
						<strong><?php esc_html_e( 'Booking Status:', 'yasmine-artistry-booking' ); ?></strong>
						<span class="yab-status-badge yab-status-<?php echo esc_attr( $booking->booking_status ); ?>">
							<?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->booking_status ) ) ); ?>
						</span>
					</p>
					<p>
						<strong><?php esc_html_e( 'Payment Status:', 'yasmine-artistry-booking' ); ?></strong>
						<span class="yab-status-badge yab-status-<?php echo esc_attr( $booking->payment_status ); ?>">
							<?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->payment_status ) ) ); ?>
						</span>
					</p>
					<p>
						<strong><?php esc_html_e( 'Reschedule Count:', 'yasmine-artistry-booking' ); ?></strong>
						<?php echo esc_html( $booking->reschedule_count ); ?>
					</p>

					<hr>

					<form method="post" onsubmit="return confirm('Confirm updating this booking status?');">
						<?php wp_nonce_field( 'yab_update_booking_status', 'yab_status_nonce' ); ?>
						<input type="hidden" name="yab_admin_booking_action" value="1">
						<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->id ); ?>">

						<p>
							<label for="new_status"><strong><?php esc_html_e( 'Change Status:', 'yasmine-artistry-booking' ); ?></strong></label>
							<select name="new_status" id="new_status" class="widefat" style="margin-top: 6px;">
								<option value="confirmed" <?php selected( $booking->booking_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed (Manual)', 'yasmine-artistry-booking' ); ?></option>
								<option value="completed" <?php selected( $booking->booking_status, 'completed' ); ?>><?php esc_html_e( 'Mark Completed', 'yasmine-artistry-booking' ); ?></option>
								<option value="cancelled" <?php selected( $booking->booking_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancel Booking', 'yasmine-artistry-booking' ); ?></option>
							</select>
						</p>

						<p id="yab-cancel-reason-wrap" style="display: none;">
							<label for="cancellation_reason"><?php esc_html_e( 'Cancellation Reason:', 'yasmine-artistry-booking' ); ?></label>
							<textarea name="cancellation_reason" id="cancellation_reason" class="widefat" rows="2"></textarea>
						</p>

						<button type="submit" class="button button-primary widefat">
							<?php esc_html_e( 'Update Booking Status', 'yasmine-artistry-booking' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Customer Profile -->
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php esc_html_e( 'Client Profile', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<p><strong><?php esc_html_e( 'Name:', 'yasmine-artistry-booking' ); ?></strong> <?php echo esc_html( $booking->customer_name ); ?></p>
					<p><strong><?php esc_html_e( 'Phone:', 'yasmine-artistry-booking' ); ?></strong> <a href="tel:<?php echo esc_attr( $booking->customer_phone ); ?>"><?php echo esc_html( $booking->customer_phone ); ?></a></p>
					<p><strong><?php esc_html_e( 'Email:', 'yasmine-artistry-booking' ); ?></strong> <a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a></p>

					<hr>
					<p>
						<a href="<?php echo esc_url( $placeholders['manage_link'] ); ?>" target="_blank" class="button button-secondary widefat">
							<?php esc_html_e( 'Open Client Portal ↗', 'yasmine-artistry-booking' ); ?>
						</a>
					</p>
				</div>
			</div>

		</div>

	</div>

</div>
