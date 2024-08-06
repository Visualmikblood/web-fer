jQuery(document).ready(function() {
	let popupPosition = function() {
		let pad = jQuery('#wpcontent').css('padding-left');

		jQuery('.trustindex-popup').css({
			right: pad,
			'margin-left': pad
		});
	};

	popupPosition();
	jQuery(window).resize(popupPosition);

	jQuery(document).on('click', '.trustindex-notification-row .ti-close-notification', function(event) {
		let container = jQuery(this).closest('.trustindex-notification-row');
		container.data('close-url', "").find('.notice-dismiss').trigger('click');
	});

	jQuery(document).on('click', '.trustindex-notification-row .ti-remind-later, .trustindex-notification-row .ti-hide-notification', function(event) {
		event.preventDefault();

		let container = jQuery(this).closest('.trustindex-notification-row');
		container.data('close-url', jQuery(this).attr('href')).find('.notice-dismiss').trigger('click');

		return false;
	});

	jQuery(document).on('click', '.trustindex-notification-row .notice-dismiss', function(event) {
		event.preventDefault();

		let closeUrl = jQuery(this).closest('.trustindex-notification-row').data('close-url');
		if (closeUrl) {
			jQuery.post(closeUrl, {});
		}
	});
});