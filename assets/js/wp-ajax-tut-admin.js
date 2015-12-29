jQuery(document).ready(function($) {

	/**
	 *	Process request to dismiss our admin notice
	 */
	$('#wp-ajax-tut-notice .notice-dismiss').click(function() {

		//* Data to make available via the $_POST variable
		data = {
			action: 'wp_ajax_tut_admin_notice',
			wp_ajax_tut_admin_nonce: wp_ajax_tut_admin.wp_ajax_tut_admin_nonce
		};

		//* Process the AJAX POST request
		$.post( ajaxurl, data );

		return false;
	});
});