/**
 * Yasmine Artistry Booking - Frontend Client Controller
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initBookingApp();
		initPortalApp();
	});

	/**
	 * Main Multi-Step Booking Flow Controller.
	 */
	function initBookingApp() {
		var container = document.getElementById('yab-booking-app');
		if (!container) return;

		var config = window.yabConfig || {};
		var currentStep = 1;

		var state = {
			serviceId: null,
			serviceName: '',
			basePrice: 0,
			durationMins: 60,
			locationId: null,
			locationName: '',
			locationFee: 0,
			extraLooks: 0,
			date: '',
			startTime: '',
			quote: null,
			paymentChoice: 'deposit'
		};

		// DOM Elements
		var servicesListEl = document.getElementById('yab-services-list');
		var locationSelectEl = document.getElementById('yab-select-location');
		var dateInputEl = document.getElementById('yab-input-date');
		var slotsContainerEl = document.getElementById('yab-slots-container');
		var quoteDetailsEl = document.getElementById('yab-quote-details');
		var alertBoxEl = document.getElementById('yab-alert-box');

		var btnStep2 = document.getElementById('yab-btn-to-step-2');
		var btnStep3 = document.getElementById('yab-btn-to-step-3');
		var btnStep4 = document.getElementById('yab-btn-to-step-4');
		var btnSubmit = document.getElementById('yab-btn-submit-booking');

		var servicesCache = [];
		var locationsCache = [];

		// Cache all initial location options from DOM
		function cacheLocationsFromDom() {
			if (!locationSelectEl) return;
			locationsCache = [];
			Array.from(locationSelectEl.options).forEach(function(opt) {
				if (opt.value) {
					locationsCache.push({
						id: parseInt(opt.value, 10),
						name: opt.text,
						type: opt.getAttribute('data-type'),
						fee: opt.getAttribute('data-fee'),
						rawHtml: opt.innerHTML
					});
				}
			});
		}
		cacheLocationsFromDom();

		function filterLocationsForService(service) {
			if (!locationSelectEl) return;
			if (locationsCache.length === 0) {
				cacheLocationsFromDom();
			}
			var prevVal = locationSelectEl.value || (state.locationId ? String(state.locationId) : '');
			locationSelectEl.innerHTML = '<option value="">-- Choose your location --</option>';

			var linkedIds = (service && Array.isArray(service.linked_location_ids)) ? service.linked_location_ids.map(Number) : [];
			var eligibleLocations = (linkedIds.length === 0)
				? locationsCache
				: locationsCache.filter(function(loc) { return linkedIds.includes(loc.id); });

			eligibleLocations.forEach(function(loc) {
				var opt = document.createElement('option');
				opt.value = loc.id;
				opt.setAttribute('data-type', loc.type);
				opt.setAttribute('data-fee', loc.fee);
				opt.innerHTML = loc.rawHtml;
				locationSelectEl.appendChild(opt);
			});

			// Update subtext dynamically
			var subTextEl = document.getElementById('ya-location-sub-text');
			if (subTextEl && service) {
				subTextEl.textContent = 'Showing ' + eligibleLocations.length + ' available coverage zone' + (eligibleLocations.length !== 1 ? 's' : '') + ' for ' + (service.name || 'this service');
			}

			// If previous selection is still eligible, keep it; otherwise if only 1 location is assigned, select it!
			var stillValid = eligibleLocations.some(function(loc) { return String(loc.id) === String(prevVal); });
			if (stillValid && prevVal) {
				locationSelectEl.value = prevVal;
				state.locationId = parseInt(prevVal, 10);
			} else if (eligibleLocations.length === 1) {
				locationSelectEl.value = eligibleLocations[0].id;
				state.locationId = eligibleLocations[0].id;
			} else {
				locationSelectEl.value = '';
				state.locationId = null;
			}

			// Smoothly highlight and scroll into location bar if user needs to pick
			var locBarEl = document.getElementById('ya-location-bar');
			if (locBarEl && !state.locationId) {
				locBarEl.classList.add('ya-location-bar-highlight');
				setTimeout(function() {
					locBarEl.classList.remove('ya-location-bar-highlight');
				}, 1200);
			}

			checkStep1Validity();
		}

		// Set minimum selectable date to today
		if (dateInputEl) {
			var today = new Date().toISOString().split('T')[0];
			dateInputEl.min = today;
		}

		// 1. Fetch Services Catalog
		loadServices();

		function loadServices() {
			fetch(config.restUrl + '/services', {
				headers: { 'X-WP-Nonce': config.nonce }
			})
			.then(function(res) { return res.json(); })
			.then(function(data) {
				if (data.success && Array.isArray(data.data)) {
					servicesCache = data.data;
					renderServices(servicesCache);
				} else {
					servicesListEl.innerHTML = '<p class="yab-placeholder-text">No active services currently available.</p>';
				}
			})
			.catch(function() {
				servicesListEl.innerHTML = '<p class="yab-placeholder-text">Failed to load services. Please refresh the page.</p>';
			});
		}

		function renderServices(services) {
			if (!services || services.length === 0) {
				servicesListEl.innerHTML = '<p class="yab-placeholder-text">No packages found in this category.</p>';
				return;
			}

			var html = '';
			services.forEach(function(svc, index) {
				var isSelected = (state.serviceId === parseInt(svc.id, 10));
				var formattedPrice = parseFloat(svc.base_price).toLocaleString('en-US', { minimumFractionDigits: 0 });
				var depositAmount = parseFloat(svc.base_price * 0.50).toLocaleString('en-US', { minimumFractionDigits: 0 });
				
				// Styling theme variations: Alternating burgundy, terracotta, and light luxury
				var cardVariantClass = '';
				if (index % 3 === 1) {
					cardVariantClass = ' ya-card-terracotta';
				} else if (index % 3 === 2) {
					cardVariantClass = ' ya-card-light';
				}

				// Check if service is flagged as featured or highest tier
				var isFeatured = (index === 1 || svc.name.toLowerCase().indexOf('splendor') !== -1 || svc.name.toLowerCase().indexOf('popular') !== -1);

				html += '<div class="ya-card' + cardVariantClass + (isSelected ? ' selected' : '') + '" data-id="' + svc.id + '">';
				
				if (isFeatured) {
					html += '<span class="ya-badge-pill">Most Requested</span>';
				}

				if (svc.category_name) {
					html += '<span class="ya-category-tag">' + escapeHtml(svc.category_name) + '</span>';
				}

				html += '<h3 class="ya-card-title">' + escapeHtml(svc.name) + '</h3>';
				html += '<div class="ya-card-duration"><span>⏱ ' + svc.duration_minutes + ' Mins Dedicated Artistry</span></div>';

				// Perks / Feature list parsed from description lines
				var descText = svc.description || '';
				var lines = descText.split('\n').filter(function(l) { return l.trim() !== ''; });
				
				if (lines.length > 0) {
					html += '<ul class="ya-features-list">';
					lines.forEach(function(line) {
						var cleanLine = line.replace(/^[•\-\*]\s*/, '').trim();
						if (cleanLine) {
							html += '<li class="ya-feature-item">';
							html += '<svg class="ya-check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
							html += '<span>' + escapeHtml(cleanLine) + '</span>';
							html += '</li>';
						}
					});
					html += '</ul>';
				}

				// Visible Assigned Location Coverage
				var linkedIds = (svc.linked_location_ids && Array.isArray(svc.linked_location_ids)) ? svc.linked_location_ids.map(Number) : [];
				var locRowHtml = '';
				if (linkedIds.length > 0 && locationsCache.length > 0) {
					var matchedNames = [];
					linkedIds.forEach(function(lid) {
						var matched = locationsCache.find(function(l) { return l.id === lid; });
						if (matched) {
							matchedNames.push(escapeHtml(matched.name.replace(/\s*\(.*\)/, '')));
						}
					});
					if (matchedNames.length > 0) {
						locRowHtml = '<div class="ya-card-locations-row"><span>📍 Coverage:</span> <strong>' + matchedNames.join(', ') + '</strong></div>';
					} else {
						locRowHtml = '<div class="ya-card-locations-row"><span>📍 Coverage:</span> <strong>Designated zones</strong></div>';
					}
				} else {
					locRowHtml = '<div class="ya-card-locations-row"><span>📍 Coverage:</span> <strong>All zones available</strong></div>';
				}
				html += locRowHtml;

				// Pricing & 50% Deposit Summary Block
				html += '<div class="ya-card-pricing-block">';
				html += '<span class="ya-price-label">Complete Investment</span>';
				html += '<div class="ya-price-slot">';
				html += '<span class="ya-currency">' + config.currencySymbol + '</span>';
				html += '<span class="ya-amount">' + formattedPrice + '</span>';
				html += '</div>';
				html += '<span class="ya-card-deposit-note">50% Deposit: ' + config.currencySymbol + depositAmount + ' to reserve</span>';
				
				html += '<button type="button" class="ya-btn-select-card">';
				html += isSelected ? '<span>✓ Selected Package</span>' : '<span>Select Package &rarr;</span>';
				html += '</button>';

				html += '</div>'; // .ya-card-pricing-block
				html += '</div>'; // .ya-card
			});

			servicesListEl.innerHTML = html;

			// Attach Card Click Handlers
			var cards = servicesListEl.querySelectorAll('.ya-card');
			cards.forEach(function(card) {
				card.addEventListener('click', function() {
					cards.forEach(function(c) {
						c.classList.remove('selected');
						var btn = c.querySelector('.ya-btn-select-card span');
						if (btn) btn.innerHTML = 'Select Package &rarr;';
					});

					card.classList.add('selected');
					var selfBtn = card.querySelector('.ya-btn-select-card span');
					if (selfBtn) selfBtn.innerHTML = '✓ Selected Package';

					var id = parseInt(card.getAttribute('data-id'), 10);
					var found = servicesCache.find(function(s) { return parseInt(s.id, 10) === id; });

					if (found) {
						state.serviceId = id;
						state.serviceName = found.name;
						state.basePrice = parseFloat(found.base_price);
						state.durationMins = parseInt(found.duration_minutes, 10);
						document.getElementById('yab-input-service-id').value = id;

						// Dynamic filter of available locations for this selected service
						filterLocationsForService(found);

						// Reset dependent steps
						state.startTime = '';
						document.getElementById('yab-input-start-time').value = '';

						checkStep1Validity();
					}
				});
			});
		}

		// Category Tabs Filtering
		var categoryTabs = container.querySelectorAll('.yab-tab-btn');
		categoryTabs.forEach(function(tab) {
			tab.addEventListener('click', function() {
				categoryTabs.forEach(function(t) { t.classList.remove('active'); });
				tab.classList.add('active');

				var catId = tab.getAttribute('data-category');
				if (catId === 'all') {
					renderServices(servicesCache);
				} else {
					var filtered = servicesCache.filter(function(s) { return s.category_id == catId; });
					renderServices(filtered);
				}
			});
		});

		// Location Selection Change
		if (locationSelectEl) {
			locationSelectEl.addEventListener('change', function() {
				var locId = parseInt(this.value, 10);
				state.locationId = locId ? locId : null;
				checkStep1Validity();
			});
		}

		// Extra Looks Counter Handlers
		var extraLooksMinusBtn = document.getElementById('ya-extra-looks-minus');
		var extraLooksPlusBtn  = document.getElementById('ya-extra-looks-plus');
		var extraLooksCountEl  = document.getElementById('ya-extra-looks-count');
		var extraLooksInputEl  = document.getElementById('yab-input-extra-looks');

		if (extraLooksMinusBtn && extraLooksPlusBtn && extraLooksCountEl) {
			extraLooksMinusBtn.addEventListener('click', function() {
				if (state.extraLooks > 0) {
					state.extraLooks--;
					extraLooksCountEl.textContent = state.extraLooks;
					if (extraLooksInputEl) extraLooksInputEl.value = state.extraLooks;
					extraLooksMinusBtn.disabled = (state.extraLooks === 0);
				}
			});

			extraLooksPlusBtn.addEventListener('click', function() {
				if (state.extraLooks < 10) {
					state.extraLooks++;
					extraLooksCountEl.textContent = state.extraLooks;
					if (extraLooksInputEl) extraLooksInputEl.value = state.extraLooks;
					extraLooksMinusBtn.disabled = false;
				}
			});
		}

		function checkStep1Validity() {
			var isValid = (state.serviceId !== null && state.locationId !== null);
			btnStep2.disabled = !isValid;
		}

		// Navigation: Step 1 -> Step 2
		btnStep2.addEventListener('click', function() {
			goToStep(2);
			if (state.date) {
				loadAvailability();
			}
		});

		// Interactive Month Calendar Controller
		var calPrevBtn = document.getElementById('yab-cal-prev');
		var calNextBtn = document.getElementById('yab-cal-next');
		var calMonthTitle = document.getElementById('yab-cal-month-title');
		var calDaysEl = document.getElementById('yab-cal-days');
		var calSelectedDisplay = document.getElementById('yab-cal-selected-display');

		var calDate = new Date();
		var calCurrentYear = calDate.getFullYear();
		var calCurrentMonth = calDate.getMonth();

		var monthNames = [
			'January', 'February', 'March', 'April', 'May', 'June',
			'July', 'August', 'September', 'October', 'November', 'December'
		];

		function initCalendar() {
			if (!calDaysEl) return;
			renderCalendar(calCurrentYear, calCurrentMonth);

			if (calPrevBtn) {
				calPrevBtn.addEventListener('click', function(e) {
					e.preventDefault();
					calCurrentMonth--;
					if (calCurrentMonth < 0) {
						calCurrentMonth = 11;
						calCurrentYear--;
					}
					renderCalendar(calCurrentYear, calCurrentMonth);
				});
			}

			if (calNextBtn) {
				calNextBtn.addEventListener('click', function(e) {
					e.preventDefault();
					calCurrentMonth++;
					if (calCurrentMonth > 11) {
						calCurrentMonth = 0;
						calCurrentYear++;
					}
					renderCalendar(calCurrentYear, calCurrentMonth);
				});
			}
		}

		function renderCalendar(year, month) {
			if (!calDaysEl || !calMonthTitle) return;

			calMonthTitle.textContent = monthNames[month] + ' ' + year;

			var now = new Date();
			var todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');

			var firstDayIndex = new Date(year, month, 1).getDay();
			var totalDays = new Date(year, month + 1, 0).getDate();

			var html = '';

			for (var i = 0; i < firstDayIndex; i++) {
				html += '<div class="yab-cal-day empty"></div>';
			}

			for (var d = 1; d <= totalDays; d++) {
				var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
				var isPast = dateStr < todayStr;
				var isToday = dateStr === todayStr;
				var isSelected = (state.date === dateStr);

				var classNames = ['yab-cal-day'];
				if (isPast) classNames.push('past');
				if (isToday) classNames.push('today');
				if (isSelected) classNames.push('selected');

				html += '<button type="button" class="' + classNames.join(' ') + '" data-date="' + dateStr + '"' + (isPast ? ' disabled' : '') + '>';
				html += '<span>' + d + '</span>';
				html += '</button>';
			}

			calDaysEl.innerHTML = html;

			var dayBtns = calDaysEl.querySelectorAll('.yab-cal-day:not(.empty):not(.past)');
			dayBtns.forEach(function(btn) {
				btn.addEventListener('click', function() {
					var dateVal = this.getAttribute('data-date');
					state.date = dateVal;
					if (dateInputEl) {
						dateInputEl.value = dateVal;
					}

					var dateParts = dateVal.split('-');
					var dateObj = new Date(parseInt(dateParts[0], 10), parseInt(dateParts[1], 10) - 1, parseInt(dateParts[2], 10));
					var formattedDate = dateObj.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
					if (calSelectedDisplay) {
						calSelectedDisplay.innerHTML = '<span class="yab-selected-tag">✓ Selected: <strong>' + formattedDate + '</strong></span>';
					}

					renderCalendar(calCurrentYear, calCurrentMonth);

					state.startTime = '';
					document.getElementById('yab-input-start-time').value = '';
					btnStep3.disabled = true;

					if (state.serviceId) {
						loadAvailability();
					}
				});
			});
		}

		initCalendar();

		// Date Selection -> Load Availability
		if (dateInputEl) {
			dateInputEl.addEventListener('change', function() {
				state.date = this.value;
				state.startTime = '';
				document.getElementById('yab-input-start-time').value = '';
				btnStep3.disabled = true;

				if (state.date && state.serviceId) {
					loadAvailability();
				}
			});
		}

		function loadAvailability() {
			slotsContainerEl.innerHTML = '<p class="yab-placeholder-text">Checking live availability...</p>';

			var url = config.restUrl + '/availability?service_id=' + state.serviceId + '&date=' + encodeURIComponent(state.date);

			fetch(url, { headers: { 'X-WP-Nonce': config.nonce } })
			.then(function(res) { return res.json(); })
			.then(function(data) {
				if (data.success && Array.isArray(data.data) && data.data.length > 0) {
					renderSlots(data.data);
				} else {
					slotsContainerEl.innerHTML = '<p class="yab-placeholder-text" style="color:#c53030;">No available slots on this date. Please select another date.</p>';
				}
			})
			.catch(function() {
				slotsContainerEl.innerHTML = '<p class="yab-placeholder-text" style="color:#c53030;">Failed to check availability.</p>';
			});
		}

		function renderSlots(slots) {
			var html = '';
			slots.forEach(function(slot) {
				var isSelected = (state.startTime === slot.time);
				html += '<button type="button" class="yab-slot-btn ' + (isSelected ? 'selected' : '') + '" data-time="' + slot.time + '">';
				html += slot.display;
				html += '</button>';
			});

			slotsContainerEl.innerHTML = html;

			var btns = slotsContainerEl.querySelectorAll('.yab-slot-btn');
			btns.forEach(function(btn) {
				btn.addEventListener('click', function() {
					btns.forEach(function(b) { b.classList.remove('selected'); });
					btn.classList.add('selected');

					state.startTime = btn.getAttribute('data-time');
					document.getElementById('yab-input-start-time').value = state.startTime;
					btnStep3.disabled = false;
				});
			});
		}

		// Navigation: Step 2 -> Step 3
		btnStep3.addEventListener('click', function() {
			if (!state.startTime) {
				showAlert('Please select a time slot.', 'error');
				return;
			}
			goToStep(3);
		});

		// Navigation: Step 3 -> Step 4 (Quote Review)
		btnStep4.addEventListener('click', function() {
			var name = document.getElementById('yab-customer-name').value.trim();
			var email = document.getElementById('yab-customer-email').value.trim();
			var phone = document.getElementById('yab-customer-phone').value.trim();
			var address = document.getElementById('yab-service-address').value.trim();

			if (!name || !email || !phone || !address) {
				showAlert('Please fill in all required fields (Name, Email, Phone, Address).', 'error');
				return;
			}

			if (!email.includes('@')) {
				showAlert('Please enter a valid email address.', 'error');
				return;
			}

			hideAlert();
			goToStep(4);
			loadQuoteSummary();
		});

		function loadQuoteSummary() {
			quoteDetailsEl.innerHTML = '<p class="yab-placeholder-text">Calculating verified price and required payment...</p>';
			btnSubmit.disabled = true;

			var mode = state.paymentChoice || 'deposit';
			var url = config.restUrl + '/quote?service_id=' + state.serviceId + '&location_id=' + state.locationId + '&extra_looks=' + (state.extraLooks || 0) + '&payment_choice=' + encodeURIComponent(mode);

			fetch(url, { headers: { 'X-WP-Nonce': config.nonce } })
			.then(function(res) { return res.json(); })
			.then(function(resData) {
				if (resData.success && resData.data) {
					state.quote = resData.data;
					renderQuote(resData.data);
					btnSubmit.disabled = false;
				} else {
					quoteDetailsEl.innerHTML = '<p class="yab-placeholder-text" style="color:#c53030;">Unable to compute quote: ' + (resData.message || 'Error') + '</p>';
				}
			})
			.catch(function() {
				quoteDetailsEl.innerHTML = '<p class="yab-placeholder-text" style="color:#c53030;">Network error calculating pricing.</p>';
			});
		}

		function renderQuote(q) {
			var name = document.getElementById('yab-customer-name').value.trim();
			var address = document.getElementById('yab-service-address').value.trim();

			var isFull = (state.paymentChoice === 'full' || q.payment_choice === 'full');

			var html = '';
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Client Name:</span><span class="yab-sum-val">' + escapeHtml(name) + '</span></div>';
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Service:</span><span class="yab-sum-val">' + escapeHtml(q.service_name) + ' (' + q.duration_minutes + ' mins)</span></div>';
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Appointment Date & Time:</span><span class="yab-sum-val">' + state.date + ' at ' + state.startTime + '</span></div>';
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Service Address:</span><span class="yab-sum-val">' + escapeHtml(address) + '</span></div>';
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Base Service Fee:</span><span class="yab-sum-val">' + q.formatted_base + '</span></div>';
			if (q.extra_looks && q.extra_looks > 0) {
				var extraLooksTotalFormatted = (q.currency_symbol || '₦') + parseFloat(q.extra_looks_fee || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
				html += '<div class="yab-summary-row"><span class="yab-sum-label">Extra Looks (' + q.extra_looks + '):</span><span class="yab-sum-val">' + extraLooksTotalFormatted + '</span></div>';
			}
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Area Travel Fee (' + escapeHtml(q.location_name) + '):</span><span class="yab-sum-val">' + q.formatted_fee + '</span></div>';
			html += '<div class="yab-summary-row"><span class="yab-sum-label">Total Booking Amount:</span><span class="yab-sum-val highlight">' + q.formatted_total + '</span></div>';
			
			if (isFull) {
				html += '<div class="yab-summary-row"><span class="yab-sum-label">Payment Mode:</span><span class="yab-sum-val" style="color:#276749; font-weight:600;">Full Payment Upfront (100%)</span></div>';
				html += '<div class="yab-summary-row"><span class="yab-sum-label">Amount Payable Now:</span><span class="yab-sum-val" style="color:#276749; font-size:16px; font-weight:700;">' + q.formatted_deposit + '</span></div>';
				html += '<div class="yab-summary-row"><span class="yab-sum-label">Balance Due at Appointment:</span><span class="yab-sum-val" style="color:#2b6cb0; font-weight:600;">₦0.00 (Fully Settled)</span></div>';
			} else {
				html += '<div class="yab-summary-row"><span class="yab-sum-label">50% Deposit Due Now:</span><span class="yab-sum-val" style="color:#276749; font-size:16px; font-weight:700;">' + q.formatted_deposit + '</span></div>';
				html += '<div class="yab-summary-row"><span class="yab-sum-label">Balance Due at Appointment:</span><span class="yab-sum-val">' + q.formatted_balance + '</span></div>';
			}

			quoteDetailsEl.innerHTML = html;

			// Update option badges
			var badgeDeposit = document.getElementById('yab-badge-deposit-amt');
			var badgeFull = document.getElementById('yab-badge-full-amt');
			var noticeText = document.getElementById('yab-payment-notice-text');
			var submitBtnText = document.querySelector('#yab-btn-submit-booking .yab-btn-text');

			if (badgeFull) {
				badgeFull.textContent = q.formatted_total;
			}
			if (badgeDeposit) {
				// If quote was computed for full, compute normal deposit preview from base total or percentage
				badgeDeposit.textContent = isFull ? (q.currency_symbol || '₦') + Number(q.total_amount * 0.5).toLocaleString() : q.formatted_deposit;
			}

			if (isFull) {
				if (noticeText) {
					noticeText.textContent = 'Payments are processed securely via Paystack. You have selected 100% full payment, so your booking is completely settled with zero balance due on service day.';
				}
				if (submitBtnText) {
					submitBtnText.textContent = 'Pay Full Balance (' + q.formatted_deposit + ') & Confirm';
				}
			} else {
				if (noticeText) {
					noticeText.textContent = 'Payments are processed securely via Paystack. Your deposit reserves your home service slot. The remaining balance of ' + q.formatted_balance + ' is paid upon appointment completion.';
				}
				if (submitBtnText) {
					submitBtnText.textContent = 'Pay Deposit (' + q.formatted_deposit + ') & Confirm Booking';
				}
			}
		}

		// Payment Option Selection Listeners
		var optDeposit = document.getElementById('yab-pay-deposit');
		var optFull = document.getElementById('yab-pay-full');
		var optDepositLabel = document.getElementById('yab-opt-deposit-label');
		var optFullLabel = document.getElementById('yab-opt-full-label');

		if (optDeposit && optFull) {
			optDeposit.addEventListener('change', function() {
				if (this.checked) {
					state.paymentChoice = 'deposit';
					if (optDepositLabel) optDepositLabel.classList.add('selected');
					if (optFullLabel) optFullLabel.classList.remove('selected');
					loadQuoteSummary();
				}
			});

			optFull.addEventListener('change', function() {
				if (this.checked) {
					state.paymentChoice = 'full';
					if (optFullLabel) optFullLabel.classList.add('selected');
					if (optDepositLabel) optDepositLabel.classList.remove('selected');
					loadQuoteSummary();
				}
			});
		}

		// Prev Button Handlers
		var prevBtns = container.querySelectorAll('.yab-btn-prev');
		prevBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				var targetStep = parseInt(this.getAttribute('data-goto'), 10);
				goToStep(targetStep);
			});
		});

		var stepTitles = [
			'Select Your Service & Service Location',
			'Appointment Date & Time',
			'Contact Information & Address',
			'Confirm & Pay Deposit'
		];
		var nextStepLabels = [
			'Next: Date & Time →',
			'Next: Enter Details →',
			'Next: Review & Payment →',
			'Final Step: Complete Booking'
		];

		function goToStep(stepNumber) {
			currentStep = stepNumber;

			// Update Panes
			var panes = container.querySelectorAll('.yab-step-pane');
			panes.forEach(function(pane) { pane.classList.remove('active'); });
			var targetPane = document.getElementById('yab-step-' + stepNumber);
			if (targetPane) targetPane.classList.add('active');

			// Update Header Stepper Indicators
			var stepItems = container.querySelectorAll('.yab-step-item');
			stepItems.forEach(function(item) {
				var s = parseInt(item.getAttribute('data-step'), 10);
				item.classList.remove('active', 'completed');
				if (s === stepNumber) {
					item.classList.add('active');
				} else if (s < stepNumber) {
					item.classList.add('completed');
				}
			});

			var stepIndicators = container.querySelectorAll('.yab-step');
			stepIndicators.forEach(function(ind) {
				var s = parseInt(ind.getAttribute('data-step'), 10);
				ind.classList.remove('active', 'completed');
				if (s === stepNumber) {
					ind.classList.add('active');
				} else if (s < stepNumber) {
					ind.classList.add('completed');
				}
			});

			// Update arrows between steps
			var stepArrows = container.querySelectorAll('.yab-step-arrow');
			stepArrows.forEach(function(arrow, idx) {
				if (idx < stepNumber - 1) {
					arrow.classList.add('completed');
					arrow.classList.remove('active');
				} else if (idx === stepNumber - 1) {
					arrow.classList.add('active');
					arrow.classList.remove('completed');
				} else {
					arrow.classList.remove('active', 'completed');
				}
			});

			// Update Stepper Status Bar (Current, Remaining, and Next Preview on Line 1, Title on Line 2)
			var statusCurrentEl = document.getElementById('yab-status-current');
			var statusTitleEl = document.getElementById('yab-status-title');
			var statusRemainingEl = document.getElementById('yab-status-remaining');
			var statusNextEl = document.getElementById('yab-status-next');
			var nextDotEl = container.querySelector('.yab-next-dot');

			if (statusCurrentEl) {
				statusCurrentEl.textContent = 'Step ' + stepNumber + ' of 4';
			}
			if (statusTitleEl && stepTitles[stepNumber - 1]) {
				statusTitleEl.textContent = stepTitles[stepNumber - 1];
			}
			if (statusRemainingEl) {
				var remaining = 4 - stepNumber;
				if (remaining > 0) {
					statusRemainingEl.textContent = remaining + ' step' + (remaining > 1 ? 's' : '') + ' remaining';
					statusRemainingEl.style.display = 'inline-block';
					if (statusNextEl) statusNextEl.style.display = 'inline-block';
					if (nextDotEl) nextDotEl.style.display = 'inline-block';
				} else {
					statusRemainingEl.textContent = 'Final Step';
					statusRemainingEl.style.display = 'inline-block';
					if (statusNextEl) statusNextEl.style.display = 'none';
					if (nextDotEl) nextDotEl.style.display = 'none';
				}
			}
			if (statusNextEl && nextStepLabels[stepNumber - 1]) {
				statusNextEl.textContent = nextStepLabels[stepNumber - 1];
			}

			// Smooth in-place transition without disturbing user viewport
			// Removed jarring window.scrollTo as requested
		}

		// SUBMIT BOOKING & INITIALIZE PAYSTACK
		btnSubmit.addEventListener('click', function() {
			var name = document.getElementById('yab-customer-name').value.trim();
			var email = document.getElementById('yab-customer-email').value.trim();
			var phone = document.getElementById('yab-customer-phone').value.trim();
			var address = document.getElementById('yab-service-address').value.trim();
			var addrNotes = document.getElementById('yab-address-notes').value.trim();
			var custNotes = document.getElementById('yab-customer-notes').value.trim();

			setLoading(true);
			hideAlert();

			var payload = {
				service_id: state.serviceId,
				location_id: state.locationId,
				extra_looks: state.extraLooks || 0,
				appointment_date: state.date,
				start_time: state.startTime,
				customer_name: name,
				customer_email: email,
				customer_phone: phone,
				service_address: address,
				address_notes: addrNotes,
				customer_notes: custNotes,
				payment_choice: state.paymentChoice || 'deposit',
				callback_url: window.location.href
			};

			fetch(config.restUrl + '/bookings', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce
				},
				body: JSON.stringify(payload)
			})
			.then(function(res) { return res.json(); })
			.then(function(resp) {
				if (!resp.success) {
					setLoading(false);
					showAlert(resp.message || 'Booking reservation failed.', 'error');
					return;
				}

				// If deposit required > 0 and Paystack details returned
				if (resp.payment && resp.payment.access_code) {
					openPaystackModal(resp.payment, resp.booking_reference, email, resp.quote.deposit_required);
				} else {
					// Direct confirmation (zero deposit)
					window.location.href = addQueryParam(window.location.href, 'yab_action', 'confirmation', 'ref', resp.booking_reference);
				}
			})
			.catch(function(err) {
				setLoading(false);
				showAlert('Network error while creating booking.', 'error');
			});
		});

		function openPaystackModal(payment, bookingRef, email, amountNgn) {
			if (typeof PaystackPop === 'undefined') {
				// Fallback to direct authorization URL redirect
				if (payment.authorization_url) {
					window.location.href = payment.authorization_url;
					return;
				}
				setLoading(false);
				showAlert('Paystack payment SDK could not be loaded.', 'error');
				return;
			}

			var handler = PaystackPop.setup({
				key: payment.public_key || config.paystackKey,
				email: email,
				amount: Math.round(amountNgn * 100),
				currency: config.currency || 'NGN',
				ref: payment.reference,
				callback: function(response) {
					// Client received approval, verify on server
					verifyPaymentOnServer(response.reference, bookingRef);
				},
				onClose: function() {
					setLoading(false);
					showAlert('Payment checkout was closed. Your appointment slot is temporarily held for 15 minutes.', 'error');
				}
			});

			handler.openIframe();
		}

		function verifyPaymentOnServer(reference, bookingRef) {
			showAlert('Payment received! Finalizing and verifying your booking...', 'success');

			fetch(config.restUrl + '/verify-payment', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce
				},
				body: JSON.stringify({ reference: reference })
			})
			.then(function(res) { return res.json(); })
			.then(function(resp) {
				if (resp.success) {
					window.location.href = addQueryParam(window.location.href, 'yab_action', 'confirmation', 'ref', bookingRef);
				} else {
					setLoading(false);
					showAlert('Payment verification error: ' + (resp.message || 'Please contact support with ref: ' + reference), 'error');
				}
			})
			.catch(function() {
				// Fallback redirect to confirmation to let backend webhook finish verification
				window.location.href = addQueryParam(window.location.href, 'yab_action', 'confirmation', 'ref', bookingRef);
			});
		}

		function setLoading(isLoading) {
			if (isLoading) {
				btnSubmit.disabled = true;
				btnSubmit.querySelector('.yab-btn-text').textContent = 'Processing...';
			} else {
				btnSubmit.disabled = false;
				btnSubmit.querySelector('.yab-btn-text').textContent = 'Pay Deposit & Confirm Booking';
			}
		}

		function showAlert(msg, type) {
			alertBoxEl.textContent = msg;
			alertBoxEl.className = 'yab-alert yab-alert-' + (type === 'error' ? 'error' : 'success');
			alertBoxEl.style.display = 'block';
			alertBoxEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}

		function hideAlert() {
			alertBoxEl.style.display = 'none';
		}
	}

	/**
	 * Customer Self-Service Rescheduling & Cancellation Portal.
	 */
	function initPortalApp() {
		var portal = document.getElementById('yab-portal-container');
		if (!portal) return;

		var config = window.yabConfig || {};
		var token = portal.getAttribute('data-token');
		var serviceId = portal.getAttribute('data-service-id');

		var toggleRescheduleBtn = document.getElementById('yab-toggle-reschedule-btn');
		var toggleCancelBtn = document.getElementById('yab-toggle-cancel-btn');
		var rescheduleSection = document.getElementById('yab-reschedule-section');
		var cancelSection = document.getElementById('yab-cancel-section');

		var rescheduleDateInput = document.getElementById('yab-reschedule-date');
		var rescheduleSlotsEl = document.getElementById('yab-reschedule-slots');
		var rescheduleTimeInput = document.getElementById('yab-reschedule-time');
		var submitRescheduleBtn = document.getElementById('yab-submit-reschedule-btn');
		var cancelRescheduleMode = document.getElementById('yab-cancel-reschedule-mode');

		var submitCancelBtn = document.getElementById('yab-submit-cancel-btn');
		var cancelCancelMode = document.getElementById('yab-cancel-cancel-mode');
		var cancelReasonInput = document.getElementById('yab-cancel-reason');

		var portalAlert = document.getElementById('yab-portal-alert');

		if (rescheduleDateInput) {
			rescheduleDateInput.min = new Date().toISOString().split('T')[0];
		}

		// Reschedule Mode Toggle
		if (toggleRescheduleBtn) {
			toggleRescheduleBtn.addEventListener('click', function() {
				rescheduleSection.style.display = 'block';
				if (cancelSection) cancelSection.style.display = 'none';
			});
		}

		if (cancelRescheduleMode) {
			cancelRescheduleMode.addEventListener('click', function() {
				rescheduleSection.style.display = 'none';
			});
		}

		// Cancel Mode Toggle
		if (toggleCancelBtn) {
			toggleCancelBtn.addEventListener('click', function() {
				cancelSection.style.display = 'block';
				if (rescheduleSection) rescheduleSection.style.display = 'none';
			});
		}

		if (cancelCancelMode) {
			cancelCancelMode.addEventListener('click', function() {
				cancelSection.style.display = 'none';
			});
		}

		// Reschedule Date Change -> Load slots
		if (rescheduleDateInput) {
			rescheduleDateInput.addEventListener('change', function() {
				var dateVal = this.value;
				rescheduleTimeInput.value = '';
				submitRescheduleBtn.disabled = true;

				if (!dateVal) return;

				rescheduleSlotsEl.innerHTML = '<p class="yab-placeholder-text">Checking available slots...</p>';

				var url = config.restUrl + '/availability?service_id=' + serviceId + '&date=' + encodeURIComponent(dateVal);

				fetch(url, { headers: { 'X-WP-Nonce': config.nonce } })
				.then(function(res) { return res.json(); })
				.then(function(data) {
					if (data.success && Array.isArray(data.data) && data.data.length > 0) {
						var html = '';
						data.data.forEach(function(slot) {
							html += '<button type="button" class="yab-slot-btn" data-time="' + slot.time + '">' + slot.display + '</button>';
						});
						rescheduleSlotsEl.innerHTML = html;

						var btns = rescheduleSlotsEl.querySelectorAll('.yab-slot-btn');
						btns.forEach(function(btn) {
							btn.addEventListener('click', function() {
								btns.forEach(function(b) { b.classList.remove('selected'); });
								btn.classList.add('selected');
								rescheduleTimeInput.value = btn.getAttribute('data-time');
								submitRescheduleBtn.disabled = false;
							});
						});
					} else {
						rescheduleSlotsEl.innerHTML = '<p class="yab-placeholder-text" style="color:#c53030;">No available slots on this date.</p>';
					}
				})
				.catch(function() {
					rescheduleSlotsEl.innerHTML = '<p class="yab-placeholder-text" style="color:#c53030;">Failed to load slots.</p>';
				});
			});
		}

		// Submit Reschedule
		if (submitRescheduleBtn) {
			submitRescheduleBtn.addEventListener('click', function() {
				var newDate = rescheduleDateInput.value;
				var newTime = rescheduleTimeInput.value;

				if (!newDate || !newTime) {
					showPortalAlert('Please choose a date and time slot.', 'error');
					return;
				}

				submitRescheduleBtn.disabled = true;
				submitRescheduleBtn.textContent = 'Rescheduling...';

				fetch(config.restUrl + '/reschedule', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce
					},
					body: JSON.stringify({
						token: token,
						date: newDate,
						time: newTime
					})
				})
				.then(function(res) { return res.json(); })
				.then(function(resp) {
					if (resp.success) {
						showPortalAlert('Appointment successfully rescheduled! Refreshing...', 'success');
						setTimeout(function() { window.location.reload(); }, 1500);
					} else {
						submitRescheduleBtn.disabled = false;
						submitRescheduleBtn.textContent = 'Confirm New Time';
						showPortalAlert(resp.message || 'Rescheduling failed.', 'error');
					}
				})
				.catch(function() {
					submitRescheduleBtn.disabled = false;
					submitRescheduleBtn.textContent = 'Confirm New Time';
					showPortalAlert('Network error occurred.', 'error');
				});
			});
		}

		// Submit Cancellation
		if (submitCancelBtn) {
			submitCancelBtn.addEventListener('click', function() {
				var reason = cancelReasonInput.value.trim();
				if (!reason) {
					showPortalAlert('Please provide a brief reason for cancellation.', 'error');
					return;
				}

				submitCancelBtn.disabled = true;
				submitCancelBtn.textContent = 'Cancelling...';

				fetch(config.restUrl + '/cancel', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce
					},
					body: JSON.stringify({
						token: token,
						reason: reason
					})
				})
				.then(function(res) { return res.json(); })
				.then(function(resp) {
					if (resp.success) {
						showPortalAlert('Appointment cancelled. Refreshing...', 'success');
						setTimeout(function() { window.location.reload(); }, 1500);
					} else {
						submitCancelBtn.disabled = false;
						submitCancelBtn.textContent = 'Yes, Cancel Appointment';
						showPortalAlert(resp.message || 'Cancellation failed.', 'error');
					}
				})
				.catch(function() {
					submitCancelBtn.disabled = false;
					submitCancelBtn.textContent = 'Yes, Cancel Appointment';
					showPortalAlert('Network error occurred.', 'error');
				});
			});
		}

		function showPortalAlert(msg, type) {
			portalAlert.textContent = msg;
			portalAlert.className = 'yab-alert yab-alert-' + (type === 'error' ? 'error' : 'success');
			portalAlert.style.display = 'block';
		}
	}

	// Utility Helpers
	function escapeHtml(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function addQueryParam(url, k1, v1, k2, v2) {
		var u = new URL(url);
		u.searchParams.set(k1, v1);
		if (k2 && v2) u.searchParams.set(k2, v2);
		return u.toString();
	}
})();
