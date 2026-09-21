<?php
/**
 * Admin view: Bookings list table.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_filter = sanitize_key( $_GET['status'] ?? '' );
$search_query  = sanitize_text_field( $_GET['s'] ?? '' );
$current_page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page      = 20;
$offset        = ( $current_page - 1 ) * $per_page;

$args = array(
	'limit'  => $per_page,
	'offset' => $offset,
);

if ( ! empty( $status_filter ) ) {
	$args['status'] = $status_filter;
}
if ( ! empty( $search_query ) ) {
	$args['search'] = $search_query;
}

$bookings = YAB_Booking::get_all( $args );

global $wpdb;
$table = YAB_Database::table( 'bookings' );

// Count by statuses
$count_all       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
$count_confirmed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE booking_status = 'confirmed'" );
$count_pending   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE booking_status = 'pending_payment'" );
$count_completed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE booking_status = 'completed'" );
$count_cancelled = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE booking_status = 'cancelled'" );
?>

<div class="wrap yab-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Home Service Bookings', 'yasmine-artistry-booking' ); ?></h1>
	<hr class="wp-header-end">

	<!-- Status Filter Tabs -->
	<ul class="subsubsub">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings' ) ); ?>" class="<?php echo empty( $status_filter ) ? 'current' : ''; ?>">
				<?php printf( esc_html__( 'All (%d)', 'yasmine-artistry-booking' ), $count_all ); ?>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings&status=confirmed' ) ); ?>" class="<?php echo ( 'confirmed' === $status_filter ) ? 'current' : ''; ?>">
				<?php printf( esc_html__( 'Confirmed (%d)', 'yasmine-artistry-booking' ), $count_confirmed ); ?>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings&status=pending_payment' ) ); ?>" class="<?php echo ( 'pending_payment' === $status_filter ) ? 'current' : ''; ?>">
				<?php printf( esc_html__( 'Pending Payment (%d)', 'yasmine-artistry-booking' ), $count_pending ); ?>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings&status=completed' ) ); ?>" class="<?php echo ( 'completed' === $status_filter ) ? 'current' : ''; ?>">
				<?php printf( esc_html__( 'Completed (%d)', 'yasmine-artistry-booking' ), $count_completed ); ?>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings&status=cancelled' ) ); ?>" class="<?php echo ( 'cancelled' === $status_filter ) ? 'current' : ''; ?>">
				<?php printf( esc_html__( 'Cancelled (%d)', 'yasmine-artistry-booking' ), $count_cancelled ); ?>
			</a>
		</li>
	</ul>

	<!-- Search Box -->
	<form method="get" class="yab-search-form" style="float: right; margin-bottom: 12px;">
		<input type="hidden" name="page" value="yab-bookings">
		<?php if ( ! empty( $status_filter ) ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
		<?php endif; ?>
		<p class="search-box">
			<label class="screen-reader-text" for="yab-search-input"><?php esc_html_e( 'Search Bookings:', 'yasmine-artistry-booking' ); ?></label>
			<input type="search" id="yab-search-input" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Name, Email, Phone, Ref...', 'yasmine-artistry-booking' ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search', 'yasmine-artistry-booking' ); ?>">
		</p>
	</form>

	<div class="clear"></div>

	<!-- Bookings Table -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col" style="width: 140px;"><?php esc_html_e( 'Reference', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Client Details', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Service', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Area & Address', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col" style="width: 160px;"><?php esc_html_e( 'Date & Time', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col" style="width: 110px;"><?php esc_html_e( 'Deposit Paid', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col" style="width: 110px;"><?php esc_html_e( 'Balance', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col" style="width: 110px;"><?php esc_html_e( 'Status', 'yasmine-artistry-booking' ); ?></th>
				<th scope="col" style="width: 90px;"><?php esc_html_e( 'Actions', 'yasmine-artistry-booking' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $bookings ) ) : ?>
				<?php foreach ( $bookings as $b ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings&action=view&booking_id=' . $b->id ) ); ?>">
									<?php echo esc_html( $b->booking_reference ); ?>
								</a>
							</strong>
						</td>
						<td>
							<strong><?php echo esc_html( $b->customer_name ); ?></strong><br>
							<small><?php echo esc_html( $b->customer_phone ); ?></small><br>
							<small><a href="mailto:<?php echo esc_attr( $b->customer_email ); ?>"><?php echo esc_html( $b->customer_email ); ?></a></small>
						</td>
						<td><?php echo esc_html( $b->service_name ? $b->service_name : 'N/A' ); ?></td>
						<td>
							<strong><?php echo esc_html( $b->location_name ? $b->location_name : 'General Area' ); ?></strong><br>
							<small><?php echo esc_html( wp_trim_words( $b->service_address, 8 ) ); ?></small>
						</td>
						<td>
							<?php
							$app_dt = new DateTime( $b->appointment_date . ' ' . $b->start_time, wp_timezone() );
							echo esc_html( $app_dt->format( 'M j, Y' ) );
							?><br>
							<small style="color: #2b6cb0; font-weight: 600;"><?php echo esc_html( $app_dt->format( 'g:i A' ) ); ?></small>
						</td>
						<td style="color: #276749; font-weight: 600;">
							<?php echo esc_html( YAB_Pricing::format_amount( $b->deposit_paid, $b->currency . ' ' ) ); ?>
						</td>
						<td style="font-weight: 600;">
							<?php echo esc_html( YAB_Pricing::format_amount( $b->balance_remaining, $b->currency . ' ' ) ); ?>
						</td>
						<td>
							<span class="yab-status-badge yab-status-<?php echo esc_attr( $b->booking_status ); ?>">
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $b->booking_status ) ) ); ?>
							</span>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-bookings&action=view&booking_id=' . $b->id ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View', 'yasmine-artistry-booking' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="9" style="text-align: center; padding: 32px 16px; color: #718096;">
						<p style="font-size: 15px; margin-bottom: 8px;"><strong><?php esc_html_e( 'No bookings found.', 'yasmine-artistry-booking' ); ?></strong></p>
						<p><?php esc_html_e( 'New client appointments booked on the frontend will appear here automatically with verified Paystack payment tracking.', 'yasmine-artistry-booking' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

</div>
