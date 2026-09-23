<?php
/**
 * Frontend multi-step luxury bridal & home-service booking interface.
 * Version 2.0 Luxury Bridal Atelier Edition
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories       = YAB_Category::get_all( array( 'active_only' => true ) );
$locations        = YAB_Location::get_all( array( 'active_only' => true ) );
$currency         = YAB_Settings::get( 'general', 'currency_symbol', '₦' );
$business_name    = YAB_Settings::get( 'general', 'business_name', 'Yasmine Artistry' );
$deposit_pct      = YAB_Settings::get( 'deposit', 'deposit_percentage', 50 );
$extra_look_rate  = YAB_Settings::get( 'deposit', 'extra_look_rate', 50000.00 );
?>
<div id="yab-booking-app" class="yab-booking-container yab-luxury-wrapper">

	<!-- Luxury Bridal Atelier Header -->
	<header class="ya-hero-header">
		<span class="ya-brand-eyebrow"><?php echo esc_html( $business_name ); ?> • BEAUTY SALON &amp; LUXURY STUDIO</span>
		<h2 class="ya-hero-title"><?php esc_html_e( 'Services & Price Menu', 'yasmine-artistry-booking' ); ?></h2>
		<p class="ya-hero-subtitle"><?php esc_html_e( 'Tailored beauty, hair, and aesthetic services for everyday luxury and special occasions. Select your service package and reserve your appointment', 'yasmine-artistry-booking' ); ?></p>
	</header>

	<!-- Progress Header -->
	<div class="yab-stepper-wrapper">
		<div class="yab-progress-bar">
			<div class="yab-step-item" data-step="1">
				<div class="yab-step active" data-step="1">
					<span class="yab-step-number">1</span>
					<span class="yab-step-title"><?php esc_html_e( 'Service & Location', 'yasmine-artistry-booking' ); ?></span>
				</div>
				<div class="yab-step-arrow">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</div>
			</div>
			<div class="yab-step-item" data-step="2">
				<div class="yab-step" data-step="2">
					<span class="yab-step-number">2</span>
					<span class="yab-step-title"><?php esc_html_e( 'Date & Time', 'yasmine-artistry-booking' ); ?></span>
				</div>
				<div class="yab-step-arrow">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</div>
			</div>
			<div class="yab-step-item" data-step="3">
				<div class="yab-step" data-step="3">
					<span class="yab-step-number">3</span>
					<span class="yab-step-title"><?php esc_html_e( 'Client Details', 'yasmine-artistry-booking' ); ?></span>
				</div>
				<div class="yab-step-arrow">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</div>
			</div>
			<div class="yab-step-item" data-step="4">
				<div class="yab-step" data-step="4">
					<span class="yab-step-number">4</span>
					<span class="yab-step-title"><?php esc_html_e( 'Confirm & Pay', 'yasmine-artistry-booking' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Breadcrumbs & Step Title Section (Two Distinct Lines) -->
		<div class="yab-step-status-bar" id="yab-step-status-bar">
			<!-- Line 1: Breadcrumbs -->
			<div class="yab-status-breadcrumbs-row">
				<span class="yab-status-badge-count" id="yab-status-current">Step 1 of 4</span>
				<span class="yab-status-dot">•</span>
				<span class="yab-status-remaining-badge" id="yab-status-remaining">3 steps remaining</span>
				<span class="yab-status-dot yab-next-dot">•</span>
				<span class="yab-status-next-label" id="yab-status-next">Next: Date &amp; Time &rarr;</span>
			</div>
			<!-- Line 2: Current Step Title (Bold) -->
			<div class="yab-status-title-row">
				<h3 class="yab-status-title" id="yab-status-title"><?php esc_html_e( 'Select Your Service & Service Location', 'yasmine-artistry-booking' ); ?></h3>
			</div>
		</div>
	</div>

	<!-- Alert / Notification Box -->
	<div id="yab-alert-box" class="yab-alert" style="display: none;" role="alert"></div>

	<!-- Multi-Step Form -->
	<form id="yab-form" novalidate>

		<!-- STEP 1: SERVICE & LOCATION -->
		<section class="yab-step-pane active" id="yab-step-1">
			<h3 class="yab-pane-title"><?php esc_html_e( 'Select Your Service & Service Location', 'yasmine-artistry-booking' ); ?></h3>

			<!-- Category Filter Tabs: Specific categories first, "All Services" last -->
			<?php if ( ! empty( $categories ) ) : ?>
				<div class="yab-category-tabs ya-luxury-tabs">
					<?php foreach ( $categories as $cat ) : ?>
						<button type="button" class="yab-tab-btn" data-category="<?php echo esc_attr( $cat->id ); ?>">
							<span class="yab-cat-dot">✦</span>
							<span><?php echo esc_html( $cat->name ); ?></span>
						</button>
					<?php endforeach; ?>
					<button type="button" class="yab-tab-btn active" data-category="all">
						<span><?php esc_html_e( 'All Packages', 'yasmine-artistry-booking' ); ?></span>
					</button>
				</div>
			<?php endif; ?>

			<!-- Service Selection Cards Grid (Dynamically Populated) -->
			<div class="yab-form-group">
				<label class="yab-label ya-step-section-heading">
					<?php esc_html_e( '1. Select Your Service Package', 'yasmine-artistry-booking' ); ?> <span class="req">*</span>
				</label>
				<div id="yab-services-list" class="ya-grid-container yab-cards-grid">
					<div class="yab-loading-placeholder"><?php esc_html_e( 'Loading luxury service catalog...', 'yasmine-artistry-booking' ); ?></div>
				</div>
				<input type="hidden" name="service_id" id="yab-input-service-id" required>
			</div>

			<!-- Location Selection Bar (Under Services - Populates Dynamically upon Selection) -->
			<div class="ya-location-bar" id="ya-location-bar">
				<div class="ya-location-info">
					<h4 class="ya-location-heading"><?php esc_html_e( '2. Select Service Location', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></h4>
					<span class="ya-location-sub" id="ya-location-sub-text"><?php esc_html_e( 'Select your service above to view available coverage locations & travel rates', 'yasmine-artistry-booking' ); ?></span>
				</div>
				<div class="ya-location-select-wrap">
					<select name="location_id" id="yab-select-location" class="yab-select" required>
						<option value=""><?php esc_html_e( '-- Choose your location --', 'yasmine-artistry-booking' ); ?></option>
						<?php foreach ( $locations as $loc ) : ?>
							<option value="<?php echo esc_attr( $loc->id ); ?>" data-type="<?php echo esc_attr( $loc->fee_type ); ?>" data-fee="<?php echo esc_attr( $loc->fee_amount ); ?>">
								<?php
								$fee_text = ( 'percentage' === $loc->fee_type )
									? sprintf( '+%0.1f%% area surcharge', $loc->fee_amount )
									: ( $loc->fee_amount > 0 ? sprintf( '+%s%0.2f travel fee', $currency, $loc->fee_amount ) : 'Standard coverage (included)' );
								echo esc_html( $loc->name . ' (' . $fee_text . ')' );
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<!-- Extra Looks Counter (Customizable from Admin Deposit Settings) -->
			<div class="ya-extra-looks-box" id="ya-extra-looks-box">
				<div class="ya-extra-looks-text">
					<span class="ya-extra-looks-title"><?php esc_html_e( 'Add Extra Bridal Looks / Outfit Changes?', 'yasmine-artistry-booking' ); ?></span>
					<span class="ya-extra-looks-rate" id="ya-extra-looks-rate-text">
						<?php
						printf(
							/* translators: 1: Currency, 2: Rate */
							esc_html__( '+%1$s%2$s per additional bridal look change', 'yasmine-artistry-booking' ),
							esc_html( $currency ),
							esc_html( number_format( floatval( $extra_look_rate ), 2 ) )
						);
						?>
					</span>
				</div>
				<div class="ya-counter-controls">
					<button type="button" class="ya-counter-btn" id="ya-extra-looks-minus" disabled aria-label="Decrease extra looks">&minus;</button>
					<span class="ya-counter-value" id="ya-extra-looks-count">0</span>
					<button type="button" class="ya-counter-btn" id="ya-extra-looks-plus" aria-label="Increase extra looks">&plus;</button>
					<input type="hidden" name="extra_looks" id="yab-input-extra-looks" value="0">
				</div>
			</div>

			<div class="yab-actions" style="margin-top: 30px;">
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

			<div class="yab-form-row yab-calendar-row">
				<div class="yab-form-group yab-calendar-group">
					<label class="yab-label"><?php esc_html_e( 'Select Date on Calendar', 'yasmine-artistry-booking' ); ?> <span class="req">*</span></label>
					
					<!-- Interactive Monthly Calendar -->
					<div class="yab-calendar-widget" id="yab-calendar-widget">
						<div class="yab-cal-nav">
							<button type="button" class="yab-cal-btn" id="yab-cal-prev" aria-label="Previous Month">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
							</button>
							<span class="yab-cal-month-title" id="yab-cal-month-title">Month Year</span>
							<button type="button" class="yab-cal-btn" id="yab-cal-next" aria-label="Next Month">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
							</button>
						</div>
						<div class="yab-cal-weekdays">
							<span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
						</div>
						<div class="yab-cal-days" id="yab-cal-days">
							<!-- Populated via JavaScript -->
						</div>
						<div class="yab-cal-selected-display" id="yab-cal-selected-display">
							<span><?php esc_html_e( 'Please click a date on the calendar.', 'yasmine-artistry-booking' ); ?></span>
						</div>
					</div>
					<input type="hidden" id="yab-input-date" name="appointment_date" required>
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
							<span class="yab-opt-title"><?php esc_html_e( 'Pay 50% Deposit Now', 'yasmine-artistry-booking' ); ?></span>
							<span class="yab-opt-desc" id="yab-deposit-desc"><?php esc_html_e( 'Lock and secure your appointment slot now. Pay the remaining 50% balance upon service completion.', 'yasmine-artistry-booking' ); ?></span>
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
