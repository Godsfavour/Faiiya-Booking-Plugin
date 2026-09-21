<?php
/**
 * Admin menus and back-end logic.
 */
class Yasmine_Booking_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_yasmine_confirm_payment', array( $this, 'handle_manual_payment_confirmation' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Yasmine Booking', 'yasmine-booking' ),
			__( 'Yasmine Booking', 'yasmine-booking' ),
			'manage_options',
			'yasmine-booking',
			array( $this, 'render_bookings_dashboard' ),
			'dashicons-calendar-alt',
			25
		);

		add_submenu_page(
			'yasmine-booking',
			__( 'Services', 'yasmine-booking' ),
			__( 'Services', 'yasmine-booking' ),
			'manage_options',
			'yasmine-booking-services',
			array( $this, 'render_services_page' )
		);

		add_submenu_page(
			'yasmine-booking',
			__( 'Settings', 'yasmine-booking' ),
			__( 'Settings', 'yasmine-booking' ),
			'manage_options',
			'yasmine-booking-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'yasmine_booking_business_group', 'yasmine_booking_business_details' );
		register_setting( 'yasmine_booking_paystack_group', 'yasmine_booking_paystack_config' );
		register_setting( 'yasmine_booking_google_group', 'yasmine_booking_google_config' );
		register_setting( 'yasmine_booking_whatsapp_group', 'yasmine_booking_whatsapp_config' );
		register_setting( 'yasmine_booking_twilio_group', 'yasmine_booking_twilio_config' );
	}

	public function render_bookings_dashboard() {
		global $wpdb;
		$table_bookings = $wpdb->prefix . 'yasmine_bookings';
		$table_services = $wpdb->prefix . 'yasmine_services';

		// Query bookings
		$bookings = $wpdb->get_results( "
			SELECT b.*, s.name as service_name 
			FROM $table_bookings b 
			LEFT JOIN $table_services s ON b.service_id = s.id 
			ORDER BY b.datetime DESC
		" );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings Dashboard', 'yasmine-booking' ); ?></h1>
			<hr class="wp-header-end">

			<div class="card" style="max-width:100%; margin-top:20px; padding:20px; background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
				<h3><?php esc_html_e( 'Current Scheduled Appointments', 'yasmine-booking' ); ?></h3>
				<table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:15px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Booking ID / Ref', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Service', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Date & Time', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Status', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Payment Method', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Deposit Paid', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Balance Due', 'yasmine-booking' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'yasmine-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $bookings ) ) : ?>
							<tr>
								<td colspan="9"><?php esc_html_e( 'No bookings found.', 'yasmine-booking' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $bookings as $b ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $b->id ); ?></strong><br>
										<span class="description" style="font-size:11px;"><?php echo esc_html( $b->reference_code ); ?></span>
									</td>
									<td>
										<?php echo esc_html( $b->customer_name ); ?><br>
										<span class="description" style="font-size:11px;"><?php echo esc_html( $b->customer_email ); ?></span><br>
										<span class="description" style="font-size:11px;"><?php echo esc_html( $b->customer_phone ); ?></span>
									</td>
									<td><?php echo esc_html( $b->service_name ); ?></td>
									<td><?php echo esc_html( date( 'Y-m-d H:i', strtotime( $b->datetime ) ) ); ?></td>
									<td>
										<span class="badge" style="padding:4px 8px; border-radius:12px; font-size:11px; font-weight:bold; background: <?php 
											echo $b->status === 'confirmed' ? '#D1FAE5; color:#065F46;' : 
												($b->status === 'pending_payment' ? '#FEF3C7; color:#92400E;' : 
												($b->status === 'cancelled' ? '#FEE2E2; color:#991B1B;' : '#E5E7EB; color:#374151;'));
										?>">
											<?php echo esc_html( strtoupper( $b->status ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( strtoupper( str_replace( '_', ' ', $b->payment_method ) ) ); ?></td>
									<td><?php echo esc_html( '$' . number_format( $b->deposit_paid, 2 ) ); ?></td>
									<td><?php echo esc_html( '$' . number_format( $b->balance_due, 2 ) ); ?></td>
									<td>
										<?php if ( $b->status === 'pending_payment' && $b->payment_method === 'bank_transfer' ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=yasmine_confirm_payment&booking_id=' . $b->id ) ); ?>" class="button button-primary button-small"><?php esc_html_e( 'Confirm Payment', 'yasmine-booking' ); ?></a>
										<?php endif; ?>
										<button class="button button-secondary button-small reschedule-trigger" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e( 'Edit', 'yasmine-booking' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public function handle_manual_payment_confirmation() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'yasmine-booking' ) );
		}

		$booking_id = sanitize_text_field( $_GET['booking_id'] ?? '' );
		if ( empty( $booking_id ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=yasmine-booking' ) );
			exit;
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'yasmine_bookings',
			array( 'status' => 'confirmed' ),
			array( 'id' => $booking_id )
		);

		// Execute hooks for notifications & sync
		do_action( 'yasmine_booking_payment_confirmed', $booking_id );

		wp_safe_redirect( admin_url( 'admin.php?page=yasmine-booking&message=payment_confirmed' ) );
		exit;
	}

	public function render_services_page() {
		// Services CRUD placeholder for WordPress administration page
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Service Offerings', 'yasmine-booking' ); ?></h1>
			<p><?php esc_html_e( 'Manage the services your clients can schedule. Each service dictates pricing and deposit policies.', 'yasmine-booking' ); ?></p>
			<!-- Service List and add service form in standard WordPress styling -->
		</div>
		<?php
	}

	public function render_settings_page() {
		// Render settings configuration tabs
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Yasmine Booking Settings', 'yasmine-booking' ); ?></h1>
			<!-- Render configuration tabs for Modular Architecture -->
		</div>
		<?php
	}
}
