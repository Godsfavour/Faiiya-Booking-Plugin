/**
 * Yasmine Artistry Booking - Admin Script
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Toggle cancellation reason field on status select
		$('#new_status').on('change', function() {
			if ($(this).val() === 'cancelled') {
				$('#yab-cancel-reason-wrap').slideDown(150);
			} else {
				$('#yab-cancel-reason-wrap').slideUp(150);
			}
		});
	});
})(jQuery);
