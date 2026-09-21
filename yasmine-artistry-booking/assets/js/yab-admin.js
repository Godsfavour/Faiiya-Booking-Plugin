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

		// Service Image Upload / Media Library selection
		var yabServiceMediaFrame;
		$('#yab-upload-service-img-btn').on('click', function(e) {
			e.preventDefault();

			if (yabServiceMediaFrame) {
				yabServiceMediaFrame.open();
				return;
			}

			if (typeof wp === 'undefined' || !wp.media) {
				return;
			}

			yabServiceMediaFrame = wp.media({
				title: 'Select or Upload Service Image',
				button: {
					text: 'Use this image'
				},
				multiple: false
			});

			yabServiceMediaFrame.on('select', function() {
				var attachment = yabServiceMediaFrame.state().get('selection').first().toJSON();
				if (attachment && attachment.url) {
					$('#yab-service-image-url').val(attachment.url);
					$('#yab-service-image-preview').attr('src', attachment.url).show();
					$('#yab-remove-service-img-btn').show();
				}
			});

			yabServiceMediaFrame.open();
		});

		$('#yab-remove-service-img-btn').on('click', function(e) {
			e.preventDefault();
			$('#yab-service-image-url').val('');
			$('#yab-service-image-preview').attr('src', '').hide();
			$(this).hide();
		});
	});
})(jQuery);
