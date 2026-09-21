<?php
/**
 * Admin view: Settings tabs and configurations.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab = sanitize_key( $_GET['tab'] ?? 'general' );
$webhook_url = rest_url( 'yab/v1/paystack-webhook' );
?>

<div class="wrap yab-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Booking Settings', 'yasmine-artistry-booking' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved successfully.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php endif; ?>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-settings&tab=general' ) ); ?>" class="nav-tab <?php echo 'general' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'yasmine-artistry-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-settings&tab=rules' ) ); ?>" class="nav-tab <?php echo 'rules' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Rules & Availability', 'yasmine-artistry-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-settings&tab=pricing' ) ); ?>" class="nav-tab <?php echo 'pricing' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Deposits & Pricing', 'yasmine-artistry-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-settings&tab=paystack' ) ); ?>" class="nav-tab <?php echo 'paystack' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Paystack Gateway', 'yasmine-artistry-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-settings&tab=emails' ) ); ?>" class="nav-tab <?php echo 'emails' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Emails', 'yasmine-artistry-booking' ); ?>
		</a>
	</nav>

	<div class="yab-settings-content" style="margin-top: 20px;">
		<form method="post">
			<?php wp_nonce_field( 'yab_save_settings_nonce', 'yab_settings_nonce' ); ?>
			<input type="hidden" name="yab_settings_action" value="save">
			<input type="hidden" name="current_tab" value="<?php echo esc_attr( $tab ); ?>">

			<?php if ( 'general' === $tab ) : ?>
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Business Information', 'yasmine-artistry-booking' ); ?></h2></div>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="business_name"><?php esc_html_e( 'Business / Brand Name', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="business_name" id="business_name" class="regular-text" value="<?php echo esc_attr( YAB_Settings::get( 'general', 'business_name', 'Yasmine Artistry' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="admin_email"><?php esc_html_e( 'Admin Notification Email', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="email" name="admin_email" id="admin_email" class="regular-text" value="<?php echo esc_attr( YAB_Settings::get( 'general', 'admin_email', get_option( 'admin_email' ) ) ); ?>">
									<p class="description"><?php esc_html_e( 'Receives new appointment alerts and payment logs.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="currency"><?php esc_html_e( 'Currency Code', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="currency" id="currency" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'general', 'currency', 'NGN' ) ); ?>">
									<span class="description"><?php esc_html_e( 'ISO currency code (e.g. NGN, GHS, ZAR, USD).', 'yasmine-artistry-booking' ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="currency_symbol"><?php esc_html_e( 'Currency Symbol', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="currency_symbol" id="currency_symbol" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'general', 'currency_symbol', '₦' ) ); ?>">
								</td>
							</tr>
						</table>
					</div>
				</div>

			<?php elseif ( 'rules' === $tab ) : ?>
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Scheduling & Travel Buffer Rules', 'yasmine-artistry-booking' ); ?></h2></div>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="slot_interval_minutes"><?php esc_html_e( 'Slot Interval (Minutes)', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="number" name="slot_interval_minutes" id="slot_interval_minutes" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'rules', 'slot_interval_minutes', 30 ) ); ?>">
									<p class="description"><?php esc_html_e( 'Frequency of selectable appointment start times (e.g. 15, 30, or 60 minutes).', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="default_buffer_minutes"><?php esc_html_e( 'Default Travel Buffer (Minutes)', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="number" name="default_buffer_minutes" id="default_buffer_minutes" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'rules', 'default_buffer_minutes', 30 ) ); ?>">
									<p class="description"><?php esc_html_e( 'Mandatory cleanup & travel padding between home appointments.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="min_advance_hours"><?php esc_html_e( 'Minimum Advance Notice (Hours)', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="number" name="min_advance_hours" id="min_advance_hours" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'rules', 'min_advance_hours', 4 ) ); ?>">
									<p class="description"><?php esc_html_e( 'Prevents clients from booking on zero notice.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="max_future_days"><?php esc_html_e( 'Future Booking Horizon (Days)', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="number" name="max_future_days" id="max_future_days" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'rules', 'max_future_days', 60 ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="max_reschedules"><?php esc_html_e( 'Max Self-Service Reschedules', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="number" name="max_reschedules" id="max_reschedules" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'rules', 'max_reschedules', 2 ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Allow Client Self-Service Cancellation', 'yasmine-artistry-booking' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="cancellation_allowed" value="1" <?php checked( YAB_Settings::get( 'rules', 'cancellation_allowed', 1 ), 1 ); ?>>
										<?php esc_html_e( 'Allow clients to cancel via their secure link', 'yasmine-artistry-booking' ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>
				</div>

			<?php elseif ( 'pricing' === $tab ) : ?>
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Deposit & Payment Policy', 'yasmine-artistry-booking' ); ?></h2></div>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="require_deposit"><?php esc_html_e( 'Require Advance Payment', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<label>
										<input type="checkbox" name="require_deposit" id="require_deposit" value="1" <?php checked( YAB_Settings::get( 'pricing', 'require_deposit', 1 ), 1 ); ?>>
										<strong><?php esc_html_e( 'Require deposit payment before confirming slot', 'yasmine-artistry-booking' ); ?></strong>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="deposit_type"><?php esc_html_e( 'Deposit Calculation Mode', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<select name="deposit_type" id="deposit_type">
										<option value="percentage" <?php selected( YAB_Settings::get( 'pricing', 'deposit_type', 'percentage' ), 'percentage' ); ?>><?php esc_html_e( 'Percentage of Total Quote', 'yasmine-artistry-booking' ); ?></option>
										<option value="fixed" <?php selected( YAB_Settings::get( 'pricing', 'deposit_type', 'percentage' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed Deposit Amount', 'yasmine-artistry-booking' ); ?></option>
										<option value="full" <?php selected( YAB_Settings::get( 'pricing', 'deposit_type', 'percentage' ), 'full' ); ?>><?php esc_html_e( '100% Full Payment Upfront', 'yasmine-artistry-booking' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="deposit_value"><?php esc_html_e( 'Deposit Value (% or Amount)', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="number" step="0.01" name="deposit_value" id="deposit_value" class="small-text" value="<?php echo esc_attr( YAB_Settings::get( 'pricing', 'deposit_value', 50 ) ); ?>">
									<p class="description"><?php esc_html_e( 'If percentage mode, 50 = 50% deposit required via Paystack. Note: When a deposit is required, customers also have the option to pay their full balance directly at checkout.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

			<?php elseif ( 'paystack' === $tab ) : ?>
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Paystack Gateway Credentials', 'yasmine-artistry-booking' ); ?></h2></div>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Environment Mode', 'yasmine-artistry-booking' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="test_mode" value="1" <?php checked( YAB_Settings::get( 'paystack', 'test_mode', 1 ), 1 ); ?>>
										<strong><?php esc_html_e( 'Enable Paystack Test Mode (Sandbox)', 'yasmine-artistry-booking' ); ?></strong>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="test_public_key"><?php esc_html_e( 'Test Public Key', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="test_public_key" id="test_public_key" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'paystack', 'test_public_key', '' ) ); ?>" placeholder="pk_test_...">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="test_secret_key"><?php esc_html_e( 'Test Secret Key', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="password" name="test_secret_key" id="test_secret_key" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'paystack', 'test_secret_key', '' ) ); ?>" placeholder="sk_test_...">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="live_public_key"><?php esc_html_e( 'Live Public Key', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="live_public_key" id="live_public_key" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'paystack', 'live_public_key', '' ) ); ?>" placeholder="pk_live_...">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="live_secret_key"><?php esc_html_e( 'Live Secret Key', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="password" name="live_secret_key" id="live_secret_key" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'paystack', 'live_secret_key', '' ) ); ?>" placeholder="sk_live_...">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Paystack Webhook URL', 'yasmine-artistry-booking' ); ?></th>
								<td>
									<code><?php echo esc_html( $webhook_url ); ?></code>
									<p class="description"><?php esc_html_e( 'Copy this URL into your Paystack Dashboard > Settings > API Keys & Webhooks to receive automatic payment confirmations.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

			<?php elseif ( 'emails' === $tab ) : ?>
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Email Notification Settings & Templates', 'yasmine-artistry-booking' ); ?></h2></div>
					<div class="inside">
						<p class="description" style="margin-bottom: 20px;">
							<?php esc_html_e( 'Customize subjects and HTML message bodies for all automated emails. Available placeholders: {customer_name}, {booking_reference}, {service_name}, {booking_date}, {booking_time}, {location}, {service_address}, {total_amount}, {deposit_amount}, {balance}, {manage_link}, {reason}, {old_date}, {old_time}.', 'yasmine-artistry-booking' ); ?>
						</p>

						<h3 class="yab-section-subtitle"><?php esc_html_e( 'Sender Information', 'yasmine-artistry-booking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="sender_name"><?php esc_html_e( 'Sender Name', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="sender_name" id="sender_name" class="regular-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'sender_name', 'Yasmine Artistry' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="sender_email"><?php esc_html_e( 'Sender Email Address', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="email" name="sender_email" id="sender_email" class="regular-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'sender_email', get_option( 'admin_email' ) ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="admin_notify_email"><?php esc_html_e( 'Admin Alert Email Address', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="email" name="admin_notify_email" id="admin_notify_email" class="regular-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'admin_notify_email', get_option( 'admin_email' ) ) ); ?>">
								</td>
							</tr>
						</table>

						<hr style="margin: 24px 0; border: 0; border-top: 1px solid #e2e8f0;">

						<h3 class="yab-section-subtitle"><?php esc_html_e( '1. Booking Confirmation Email (to Client)', 'yasmine-artistry-booking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="customer_conf_subject"><?php esc_html_e( 'Subject', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="customer_conf_subject" id="customer_conf_subject" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'customer_conf_subject', 'Appointment Confirmed - {service_name}' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="customer_body_conf"><?php esc_html_e( 'Email Body Content', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<textarea name="customer_body_conf" id="customer_body_conf" class="large-text code" rows="6" placeholder="<?php esc_attr_e( 'Leave empty to use the default clean HTML appointment receipt card.', 'yasmine-artistry-booking' ); ?>"><?php echo esc_textarea( YAB_Settings::get( 'emails', 'customer_body_conf', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Supports HTML tags & variables (e.g. {customer_name}, {booking_reference}, {service_name}, {booking_date}, {deposit_amount}, {manage_link}).', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
						</table>

						<hr style="margin: 24px 0; border: 0; border-top: 1px solid #e2e8f0;">

						<h3 class="yab-section-subtitle"><?php esc_html_e( '2. New Booking Notification (to Admin)', 'yasmine-artistry-booking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="admin_subj_new"><?php esc_html_e( 'Subject', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="admin_subj_new" id="admin_subj_new" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'admin_subj_new', 'New Confirmed Booking: {booking_reference} - {customer_name}' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="admin_body_new"><?php esc_html_e( 'Email Body Content', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<textarea name="admin_body_new" id="admin_body_new" class="large-text code" rows="6" placeholder="<?php esc_attr_e( 'Leave empty to use default admin booking notification template.', 'yasmine-artistry-booking' ); ?>"><?php echo esc_textarea( YAB_Settings::get( 'emails', 'admin_body_new', '' ) ); ?></textarea>
								</td>
							</tr>
						</table>

						<hr style="margin: 24px 0; border: 0; border-top: 1px solid #e2e8f0;">

						<h3 class="yab-section-subtitle"><?php esc_html_e( '3. Appointment Rescheduled Email (to Client & Admin)', 'yasmine-artistry-booking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="customer_resched_subject"><?php esc_html_e( 'Subject', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="customer_resched_subject" id="customer_resched_subject" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'customer_resched_subject', 'Appointment Rescheduled - {service_name}' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="customer_body_resch"><?php esc_html_e( 'Email Body Content', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<textarea name="customer_body_resch" id="customer_body_resch" class="large-text code" rows="6" placeholder="<?php esc_attr_e( 'Leave empty to use default reschedule notice card.', 'yasmine-artistry-booking' ); ?>"><?php echo esc_textarea( YAB_Settings::get( 'emails', 'customer_body_resch', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Variables include {old_date}, {old_time}, {booking_date}, {booking_time}, and {manage_link}.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
						</table>

						<hr style="margin: 24px 0; border: 0; border-top: 1px solid #e2e8f0;">

						<h3 class="yab-section-subtitle"><?php esc_html_e( '4. Appointment Cancelled Email (to Client & Admin)', 'yasmine-artistry-booking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="customer_cancel_subject"><?php esc_html_e( 'Subject', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<input type="text" name="customer_cancel_subject" id="customer_cancel_subject" class="large-text" value="<?php echo esc_attr( YAB_Settings::get( 'emails', 'customer_cancel_subject', 'Appointment Cancelled - {service_name}' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="customer_body_canc"><?php esc_html_e( 'Email Body Content', 'yasmine-artistry-booking' ); ?></label></th>
								<td>
									<textarea name="customer_body_canc" id="customer_body_canc" class="large-text code" rows="6" placeholder="<?php esc_attr_e( 'Leave empty to use default cancellation notice card.', 'yasmine-artistry-booking' ); ?>"><?php echo esc_textarea( YAB_Settings::get( 'emails', 'customer_body_canc', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Variables include {reason}, {booking_reference}, {service_name}.', 'yasmine-artistry-booking' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
			<?php endif; ?>

			<p class="submit">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Changes', 'yasmine-artistry-booking' ); ?></button>
			</p>
		</form>
	</div>
</div>
