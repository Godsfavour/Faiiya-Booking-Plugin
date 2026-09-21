<div class="yasmine-booking-container" style="--brand-color: <?php echo esc_attr( $business['brand_color'] ?? '#10B981' ); ?>">
	<!-- Multi-Step Navigation Headers -->
	<div class="yb-steps-bar">
		<div class="yb-step-indicator active" data-step="1">1. <?php esc_html_e( 'Service', 'yasmine-booking' ); ?></div>
		<div class="yb-step-indicator" data-step="2">2. <?php esc_html_e( 'Schedule', 'yasmine-booking' ); ?></div>
		<div class="yb-step-indicator" data-step="3">3. <?php esc_html_e( 'Your Details', 'yasmine-booking' ); ?></div>
		<div class="yb-step-indicator" data-step="4">4. <?php esc_html_e( 'Payment', 'yasmine-booking' ); ?></div>
		<div class="yb-step-indicator" data-step="5">5. <?php esc_html_e( 'Confirm', 'yasmine-booking' ); ?></div>
	</div>

	<form id="yasmine-booking-form" action="" method="post">
		<!-- Step 1: Service Selection -->
		<div class="yb-form-step active" id="yb-step-1">
			<h3><?php esc_html_e( 'Choose a Service Offered', 'yasmine-booking' ); ?></h3>
			<div class="yb-services-grid">
				<?php foreach ( $services as $s ) : ?>
					<label class="yb-service-card">
						<input type="radio" name="service_id" value="<?php echo esc_attr( $s->id ); ?>" required>
						<div class="yb-service-info">
							<span class="service-title"><?php echo esc_html( $s->name ); ?></span>
							<span class="service-meta"><?php echo esc_html( $s->duration ); ?> mins • $<?php echo esc_html( number_format($s->price, 2) ); ?></span>
							<p class="service-desc"><?php echo esc_html( $s->description ); ?></p>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="yb-navigation-buttons">
				<button type="button" class="yb-btn-next" onclick="yasmineGoToStep(2)"><?php esc_html_e( 'Next: Choose Date', 'yasmine-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 2: Date and Time Picker -->
		<div class="yb-form-step" id="yb-step-2">
			<h3><?php esc_html_e( 'Select an Appointment Date & Time', 'yasmine-booking' ); ?></h3>
			<div class="yb-datetime-picker-layout">
				<input type="date" id="yb-booking-date" name="booking_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
				<div id="yb-slots-container">
					<p class="text-muted"><?php esc_html_e( 'Select a date above to display available timeslots.', 'yasmine-booking' ); ?></p>
				</div>
			</div>
			<input type="hidden" name="datetime" id="selected-datetime-input">
			<div class="yb-navigation-buttons">
				<button type="button" class="yb-btn-prev" onclick="yasmineGoToStep(1)"><?php esc_html_e( 'Previous', 'yasmine-booking' ); ?></button>
				<button type="button" class="yb-btn-next" onclick="yasmineGoToStep(3)"><?php esc_html_e( 'Next: Details', 'yasmine-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 3: Contact Details -->
		<div class="yb-form-step" id="yb-step-3">
			<h3><?php esc_html_e( 'Enter Your Information', 'yasmine-booking' ); ?></h3>
			<div class="form-group">
				<label><?php esc_html_e( 'Full Name', 'yasmine-booking' ); ?> *</label>
				<input type="text" name="name" class="form-control" required placeholder="Jane Doe">
			</div>
			<div class="form-group">
				<label><?php esc_html_e( 'Email Address', 'yasmine-booking' ); ?> *</label>
				<input type="email" name="email" class="form-control" required placeholder="jane@example.com">
			</div>
			<div class="form-group">
				<label><?php esc_html_e( 'Phone / WhatsApp Number (with international code)', 'yasmine-booking' ); ?> *</label>
				<input type="tel" name="phone" class="form-control" required placeholder="+2348000000000">
			</div>
			<div class="form-group">
				<label><?php esc_html_e( 'Special Requests or Notes', 'yasmine-booking' ); ?></label>
				<textarea name="notes" class="form-control" placeholder="Any special needs..."></textarea>
			</div>
			<div class="yb-navigation-buttons">
				<button type="button" class="yb-btn-prev" onclick="yasmineGoToStep(2)"><?php esc_html_e( 'Previous', 'yasmine-booking' ); ?></button>
				<button type="button" class="yb-btn-next" onclick="yasmineGoToStep(4)"><?php esc_html_e( 'Next: Select Payment', 'yasmine-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 4: Payments -->
		<div class="yb-form-step" id="yb-step-4">
			<h3><?php esc_html_e( 'Confirm Payment Policy', 'yasmine-booking' ); ?></h3>
			<p class="description"><?php esc_html_e( 'A secure deposit is required to lock in your appointment slot.', 'yasmine-booking' ); ?></p>
			
			<div class="yb-payment-methods">
				<?php if ( $paystack['enabled'] ) : ?>
					<label class="yb-payment-option">
						<input type="radio" name="payment_method" value="paystack" checked>
						<div class="payment-option-details">
							<strong><?php esc_html_e( 'Secure Online Card/Transfer (Paystack)', 'yasmine-booking' ); ?></strong>
							<span class="text-muted"><?php esc_html_e( 'Instant confirmation', 'yasmine-booking' ); ?></span>
						</div>
					</label>
				<?php endif; ?>
				
				<label class="yb-payment-option">
					<input type="radio" name="payment_method" value="bank_transfer" <?php echo ! $paystack['enabled'] ? 'checked' : ''; ?>>
					<div class="payment-option-details">
						<strong><?php esc_html_e( 'Manual Bank Transfer / Wire', 'yasmine-booking' ); ?></strong>
						<span class="text-muted"><?php esc_html_e( 'Manually verified within 24 hours', 'yasmine-booking' ); ?></span>
					</div>
				</label>
			</div>

			<div class="yb-navigation-buttons">
				<button type="button" class="yb-btn-prev" onclick="yasmineGoToStep(3)"><?php esc_html_e( 'Previous', 'yasmine-booking' ); ?></button>
				<button type="submit" class="yb-btn-submit" id="yb-btn-submit-form"><?php esc_html_e( 'Submit Booking Request', 'yasmine-booking' ); ?></button>
			</div>
		</div>
	</form>
</div>
