jQuery(document).ready(function($) {

	/**
	 *	Process request to end the user's login session
	 */
	$('#wp-ajax-tut-logout').click( function() {

		var $this = $(this),
			button_text = $this.text(),
			ajaxurl = wp_ajax_tut_frontend.ajaxurl;

		//* Data to make available via the $_POST variable
		data = {
			action: 'wp_ajax_tut_logout',
			wp_ajax_tut_frontend_nonce: wp_ajax_tut_frontend.wp_ajax_tut_frontend_nonce
		};

		// Disable the button and provide some feedback to the user
		$this.prop('disabled','disabled').text('Logging out...');

		//* Process the AJAX POST request
		$.post( ajaxurl, data, function(response) {

			if ( response.status == 'success' ) {
				// Show success message, then fade out the button after 2 seconds
				$this.text(response.message).delay(2000).fadeOut();
			} else {
				// Re-enable the button and revert to original text
				$this.removeProp('disabled').text('Logout failed.').delay(2000).text(button_text);
			}
		});

		return false;
	});
});