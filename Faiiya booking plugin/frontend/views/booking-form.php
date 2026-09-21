<?php
/**
 * Frontend multi-step home-service booking interface.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories = YAB_Category::get_all( array( 'active_only' => true ) );
$locations  = YAB_Location::get_all( array( 'active_only' => true ) );
$currency   = YAB_Settings::get( 'general', 'currency_symbol', '₦' );
?>
<div id="yab-booking-app" class="yab-booking-container">

	<!-- Progress Header -->
	<div class="yab-progress-bar">
		<div class="yab-step active" data-step="1">
			<span class="yab-step-number">1</span>
			<span class="yab-step-title"><?php esc_html_e( 'Service & Area', 'yasmine-artistry-booking' ); ?></span>
		</div>
		<div class="yab-step" data-step="2">
			<span class="yab-step-number">2</span>
			<span class="yab-step-title"><?php esc_html_e( 'Date & Time', 'yasmine-artistry-booking' ); ?></span>
		</div>
		<div class="yab-step" data-step="3">
			<span class="yab-step-number">3</span>
			<span class="yab-step-title"><?php esc_html_e( 'Your Details', 'yasmine-artistry-booking' ); ?></span>
		</div>
		<div class="yab-step" data-step="4">
			<span class="yab-step-number">4</span>
			<span class="yab-step-title"><?php esc_html_e( 'Confirm & Pay', 'yasmine-artistry-booking' ); ?></span>
		</div>
	</div>

	<!-- Alert / Notification Box -->
	<div id="yab-alert-box" class="yab-alert" style="display: none;" role="alert"></div>

	<!-- Multi-Step Form -->
	<form id="yab-form" novalidate>

		<!-- STEP 1: SERVICE & LOCATION -->
		<section class="yab-step-pane active" id="yab-step-1">
			<h3 class="yab-pane-title"><?php esc_html_e( 'Select Your Home Service & Location', 'yasmine-artistry-booking' ); ?></h3>
			<p class="yab-pane-subtitle"><?php esc_html_e( 'Choose the beauty service you desire and your coverage zone.', 'yasmine-artistry-booking' ); ?></p>

			<!-- Category Filter Tabs -->
			<?php if ( ! empty( $categories ) && count( $categories ) > 1 ) : ?>
				<div class="yab-category-tabs">
					<button type="button" class="yab-tab-btn active" data-category="all"><?php esc_html_e( 'All Services', 'yasmine-artistry-booking' ); ?></button>
					<?php foreach ( $categories as $cat ) : ?>
						<button type="button" class="yab-tab-btn" data-category="<?php echo esc_attr( $cat->id ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Service Selection Cards -->
			<div class="yab-form-group">
				<label class="yab-label"><?php esc_html_e( 'Available Services', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
				<div id="yab-services-list" class="yab-cards-grid">
					<div class="yab-loading-placeholder"><?php esc_html_e( 'Loading service catalog...', 'yasmine-artistry-booking' ); ?></div>
				</div>
				<input type="hidden" name="service_id" id="yab-input-service-id" required>
			</div>

			<!-- Location Selection -->
			<div class="yab-form-group">
				<label for="yab-select-location" class="yab-label"><?php esc_html_e( 'Your Location / Coverage Area', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
				<select name="location_id" id="yab-select-location" class="yab-select" required>
					<option value=""><?php esc_html_e( '-- Choose your area --', 'yasmine-artistry-booking' ); ?></option>
					<?php foreach ( $locations as $loc ) : ?>
						<option value="<?php echo esc_attr( $loc->id ); ?>" data-type="<?php echo esc_attr( $loc->fee_type ); ?>" data-fee="<?php echo esc_attr( $loc->fee_amount ); ?>">
							<?php
							$fee_text = ( 'percentage' === $loc->fee_type )
								? sprintf( '+%0.1f%% area surcharge', $loc->fee_amount )
								: ( $loc->fee_amount > 0 ? sprintf( '+%s%0.2f travel fee', $currency, $loc->fee_amount ) : 'Free travel zone' );
							echo esc_html( $loc->name . ' (' . $fee_text . ')' );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="yab-actions">
				<span></span>
				<button type="button" class="yab-btn yab-btn-primary" id="yab-btn-to-step-2" disabled>
					<?php esc_html_e( 'Next: Choose Date & Time →', 'yasmine-artistry-booking' ); ?>
				</button>
			</div>
		</section>

		<!-- STEP 2: DATE & TIME AVAILABILITY -->
		<section class="yab-step-pane" id="yab-step-2">
			<h3 class="yab-pane-title"><?php esc_html_e( 'Choose Appointment Date & Time', 'yasmine-artistry-booking' ); ?></h3>
			<p class="yab-pane-subtitle"><?php esc_html_e( 'Available slots are verified in real-time including buffer windows.', 'yasmine-artistry-booking' ); ?></p>

			<div class="yab-form-row">
				<div class="yab-form-group">
					<label for="yab-input-date" class="yab-label"><?php esc_html_e( 'Appointment Date', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
					<input type="date" id="yab-input-date" name="appointment_date" class="yab-input" required>
				</div>
			</div>

			<div class="yab-form-group">
				<label class="yab-label"><?php esc_html_e( 'Available Start Times', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
				<div id="yab-slots-container" class="yab-slots-grid">
					<p class="yab-placeholder-text"><?php esc_html_e( 'Please select an appointment date above to view open slots.', 'yasmine-artistry-booking' ); ?></p>
				</div>
				<input type="hidden" name="start_time" id="yab-input-start-time" required>
			</div>

			<div class="yab-actions">
				<button type="button" class="yab-btn yab-btn-secondary yab-btn-prev" data-goto="1">
					<?php esc_html_e( '← Back', 'yasmine-artistry-booking' ); ?>
				</button>
				<button type="button" class="yab-btn yab-btn-primary" id="yab-btn-to-step-3" disabled>
					<?php esc_html_e( 'Next: Enter Details →', 'yasmine-artistry-booking' ); ?>
				</button>
			</div>
		</section>

		<!-- STEP 3: CUSTOMER CONTACT & HOME ADDRESS -->
		<section class="yab-step-pane" id="yab-step-3">
			<h3 class="yab-pane-title"><?php esc_html_e( 'Contact Information & Home Address', 'yasmine-artistry-booking' ); ?></h3>
			<p class="yab-pane-subtitle"><?php esc_html_e( 'Our artist will arrive at this address for your scheduled service.', 'yasmine-artistry-booking' ); ?></p>

			<div class="yab-form-group">
				<label for="yab-customer-name" class="yab-label"><?php esc_html_e( 'Full Name', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
				<input type="text" id="yab-customer-name" name="customer_name" class="yab-input" required placeholder="e.g. Amina Bello">
			</div>

			<div class="yab-form-row">
				<div class="yab-form-group">
					<label for="yab-customer-email" class="yab-label"><?php esc_html_e( 'Email Address', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
					<input type="email" id="yab-customer-email" name="customer_email" class="yab-input" required placeholder="name@example.com">
				</div>
				<div class="yab-form-group">
					<label for="yab-customer-phone" class="yab-label"><?php esc_html_e( 'Phone Number (WhatsApp)', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
					<input type="tel" id="yab-customer-phone" name="customer_phone" class="yab-input" required placeholder="+234 800 000 0000">
				</div>
			</div>

			<div class="yab-form-group">
				<label for="yab-service-address" class="yab-label"><?php esc_html_e( 'Home / Service Street Address', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
				<textarea id="yab-service-address" name="service_address" class="yab-textarea" rows="3" required placeholder="House number, Street name, Estate / Apartment details..."></textarea>
			</div>

			<div class="yab-form-group">
				<label for="yab-address-notes" class="yab-label"><?php esc_html_e( 'Landmarks / Direction Notes', 'yasmine-artistry-booking' ); ?></label>
				<input type="text" id="yab-address-notes" name="address_notes" class="yab-input" placeholder="e.g. Near St. Mary Church, green gate with black rails">
			</div>

			<div class="yab-form-group">
				<label for="yab-customer-notes" class="yab-label"><?php esc_html_e( 'Special Requests / Notes for the Artist', 'yasmine-artistry-booking' ); ?></label>
				<textarea id="yab-customer-notes" name="customer_notes" class="yab-textarea" rows="2" placeholder="Skin sensitivities, desired look, or questions..."></textarea>
			</div>

			<div class="yab-actions">
				<button type="button" class="yab-btn yab-btn-secondary yab-btn-prev" data-goto="2">
					<?php esc_html_e( '← Back', 'yasmine-artistry-booking' ); ?>
				</button>
				<button type="button" class="yab-btn yab-btn-primary" id="yab-btn-to-step-4">
					<?php esc_html_e( 'Next: Review & Payment →', 'yasmine-artistry-booking' ); ?>
				</button>
			</div>
		</section>

		<!-- STEP 4: REVIEW & PAY DEPOSIT -->
		<section class="yab-step-pane" id="yab-step-4">
			<h3 class="yab-pane-title"><?php esc_html_e( 'Review & Secure Deposit', 'yasmine-artistry-booking' ); ?></h3>
			<p class="yab-pane-subtitle"><?php esc_html_e( 'Verify your appointment details and pay the reservation deposit via Paystack.', 'yasmine-artistry-booking' ); ?></p>

			<div class="yab-summary-card">
				<div class="yab-summary-header">
					<h4><?php esc_html_e( 'Appointment Summary', 'yasmine-artistry-booking' ); ?></h4>
				</div>
				<div class="yab-summary-body" id="yab-quote-details">
					<div class="yab-loading-placeholder"><?php esc_html_e( 'Calculating verified pricing...', 'yasmine-artistry-booking' ); ?></div>
				</div>
			</div>

			<div class="yab-payment-choice-wrapper" id="yab-payment-choice-card">
				<h4 class="yab-payment-choice-title"><?php esc_html_e( 'Select Payment Option', 'yasmine-artistry-booking' ); ?></h4>
				<div class="yab-payment-options-grid">
					<label class="yab-payment-option selected" id="yab-opt-deposit-label">
						<input type="radio" name="yab_payment_mode" value="deposit" id="yab-pay-deposit" checked>
						<div class="yab-payment-option-body">
							<span class="yab-opt-title"><?php esc_html_e( 'Pay Deposit Now', 'yasmine-artistry-booking' ); ?></span>
							<span class="yab-opt-desc" id="yab-deposit-desc"><?php esc_html_e( 'Lock and secure your appointment slot now. Pay the remaining balance upon service completion.', 'yasmine-artistry-booking' ); ?></span>
							<span class="yab-opt-price" id="yab-badge-deposit-amt">--</span>
						</div>
					</label>
					<label class="yab-payment-option" id="yab-opt-full-label">
						<input type="radio" name="yab_payment_mode" value="full" id="yab-pay-full">
						<div class="yab-payment-option-body">
							<span class="yab-opt-title"><?php esc_html_e( 'Pay in Full (100%)', 'yasmine-artistry-booking' ); ?></span>
							<span class="yab-opt-desc"><?php esc_html_e( 'Clear the entire appointment cost in advance. No outstanding balance to settle on appointment day.', 'yasmine-artistry-booking' ); ?></span>
							<span class="yab-opt-price" id="yab-badge-full-amt">--</span>
						</div>
					</label>
				</div>
			</div>

			<div class="yab-payment-notice">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
				<span id="yab-payment-notice-text"><?php esc_html_e( 'Payments are processed securely via Paystack. Your deposit reserves your home service slot. The remaining balance is paid upon appointment completion.', 'yasmine-artistry-booking' ); ?></span>
			</div>

			<div class="yab-actions">
				<button type="button" class="yab-btn yab-btn-secondary yab-btn-prev" data-goto="3">
					<?php esc_html_e( '← Back', 'yasmine-artistry-booking' ); ?>
				</button>
				<button type="button" class="yab-btn yab-btn-success" id="yab-btn-submit-booking">
					<span class="yab-btn-text"><?php esc_html_e( 'Pay Deposit & Confirm Booking', 'yasmine-artistry-booking' ); ?></span>
					<span class="yab-btn-spinner" style="display: none;"></span>
				</button>
			</div>
		</section>

	</form>

</div>
