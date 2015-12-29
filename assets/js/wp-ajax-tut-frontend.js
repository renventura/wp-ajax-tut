jQuery(document).ready(function($) {

	/**
	 *	Process request to end the user's login session
	 */
	$('#wp-ajax-tut-logout').click( function() {

		var $this = $(this),
			ajaxurl = wp_ajax_tut_frontend.ajaxurl;

		//* Data to make available via the $_POST variable
		data = {
			action: 'wp_ajax_tut_logout',
			wp_ajax_tut_frontend_nonce: wp_ajax_tut_frontend.wp_ajax_tut_frontend_nonce
		};

		// Provide some feedback to let the user know they clicked the button
		$this.text('Logging out...');

		//* Process the AJAX POST request
		$.post( ajaxurl, data, function(response) {

			// Disable button, change its text, then fade it out after 2 seconds
			$this.prop('disabled','disabled').text(response.message).delay(2000).fadeOut();
		});

		return false;
	});
});