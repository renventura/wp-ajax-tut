<?php
/**
 * Plugin Name: WordPress-AJAX Tutorial
 * Plugin URI: http://www.engagewp.com/
 * Description: This plugin is for the <a href="http://www.engagewp.com/making-sense-working-with-ajax-wordpress" target="_blank">WordPress AJAX tutorial on EngageWP.com</a>. It adds an admin notice that you can dismiss, and a logout button to end the user's login session. These operations are completed via AJAX to avoid reloading the page.
 * Version: 1.0
 * Author: Ren Ventura
 * Author URI: http://www.engagewp.com/
 *
 * Text Domain: wp-ajax-tut
 * Domain Path: /languages/
 *
 * License: GPL 2.0+
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */

 /*
	Copyright 2015  Ren Ventura, EngageWP.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	Permission is hereby granted, free of charge, to any person obtaining a copy of this
	software and associated documentation files (the "Software"), to deal in the Software
	without restriction, including without limitation the rights to use, copy, modify, merge,
	publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
	to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_AJAX_Tut' ) ) :

class WP_AJAX_Tut {

	private static $instance;

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_AJAX_Tut ) ) {
			
			self::$instance = new WP_AJAX_Tut;

			self::$instance->constants();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 *	Define plugin constants
	 */
	public function constants() {

		if ( ! defined( 'WP_AJAX_TUT_PLUGIN_DIR' ) ) {
			define( 'WP_AJAX_TUT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'WP_AJAX_TUT_PLUGIN_URL' ) ) {
			define( 'WP_AJAX_TUT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'WP_AJAX_TUT_PLUGIN_FILE' ) ) {
			define( 'WP_AJAX_TUT_PLUGIN_FILE', __FILE__ );
		}

		if ( ! defined( 'WP_AJAX_TUT_PLUGIN_BASENAME' ) ) {
			define( 'WP_AJAX_TUT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WP_AJAX_TUT_VERSION' ) ) {
			define( 'WP_AJAX_TUT_VERSION', 1.0 );
		}
	}

	/**
	 *	Kick everything off
	 */
	public function hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'wp_head', array( $this, 'logout_button' ) );

		// Will run for users that are logged in (wp_ajax_nopriv_{action} will run for users that are not logged in)
		add_action( 'wp_ajax_wp_ajax_tut_admin_notice', array( $this, 'dismiss_admin_notice' ) );
		add_action( 'wp_ajax_wp_ajax_tut_logout', array( $this, 'logout' ) );
	}

	/**
	 *	Enqueue the assets in the admin
	 */
	public function enqueues() {

		// Add the admin JS if the notice has not been dismissed
		if ( is_admin() && get_user_meta( get_current_user_id(), 'wp_ajax_tut_admin_notice', true ) !== 'dismissed' ) {
			
			// Adds our JS file to the queue that WordPress will load
			wp_enqueue_script( 'wp_ajax_tut_admin_script', WP_AJAX_TUT_PLUGIN_URL . 'assets/js/wp-ajax-tut-admin.js', array( 'jquery' ), WP_AJAX_TUT_VERSION, true );

			// Make some data available to our JS file
			wp_localize_script( 'wp_ajax_tut_admin_script', 'wp_ajax_tut_admin', array(
				'wp_ajax_tut_admin_nonce' => wp_create_nonce( 'wp_ajax_tut_admin_nonce' ),
			));
		}

		// Add the front-end JS for the logout link
		if ( ! is_admin() && is_user_logged_in() ) {

			wp_enqueue_script( 'wp_ajax_tut_frontend_script', WP_AJAX_TUT_PLUGIN_URL . 'assets/js/wp-ajax-tut-frontend.js', array( 'jquery' ), WP_AJAX_TUT_VERSION, true );
			
			/**
			 * The big difference here is the ajaxurl element; we need to make it
			 * available on the frontend because WordPress does not do this itself
			 */
			wp_localize_script( 'wp_ajax_tut_frontend_script', 'wp_ajax_tut_frontend', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'wp_ajax_tut_frontend_nonce' => wp_create_nonce( 'wp_ajax_tut_frontend_nonce' ),
			));
		}
	}

	/**
	 *	Add our admin notice if the user has not previously dismissed it.
	 */
	public function admin_notice() { ?>

		<?php

		// Bail if the user has previously dismissed the notice (doesn't show the notice)
		if ( get_user_meta( get_current_user_id(), 'wp_ajax_tut_admin_notice', true ) === 'dismissed' ) {
			return;
		}

		?>

		<div id="wp-ajax-tut-notice" class="notice is-dismissible update-nag">
			<?php _e( 'This is the admin notice for the WP-AJAX Tutorial plugin. It does nothing more than add this message. You should not see it anymore after clicking the dismiss icon.', 'wp-ajax-tut' ); ?>
		</div>

	<?php }

	/**
	 *	Process the AJAX request on the server and send a response back to the JS.
	 *	If nonce is valid, update the current user's meta to prevent notice from displaying.
	 */
	public function dismiss_admin_notice() {

		// Verify the security nonce and die if it fails
		if ( ! isset( $_POST['wp_ajax_tut_admin_nonce'] ) || ! wp_verify_nonce( $_POST['wp_ajax_tut_admin_nonce'], 'wp_ajax_tut_admin_nonce' ) ) {
			wp_die( __( 'Your request failed permission check.', 'wp-ajax-tut' ) );
		}

		// Store the user's dimissal so that the notice doesn't show again
		update_user_meta( get_current_user_id(), 'wp_ajax_tut_admin_notice', 'dismissed' );

		// Send success message
		wp_send_json( array(
			'status' => 'success',
			'message' => __( 'Your request was processed. See ya!', 'wp-ajax-tut' )
		) );
	}

	/**
	 *	Add the markup for the logout button
	 */
	public function logout_button() {

		if ( is_user_logged_in() ) {

			printf( '<button id="wp-ajax-tut-logout" style="position: fixed; top: 50px; left: 0; z-index: 999;">%s</button>', __( 'Logout', 'wp-ajax-tut' ) );
		}
	}

	/**
	 *	End the user's login session
	 */
	public function logout() {

		// Verify the security nonce and die if it fails
		if ( ! isset( $_POST['wp_ajax_tut_frontend_nonce'] ) || ! wp_verify_nonce( $_POST['wp_ajax_tut_frontend_nonce'], 'wp_ajax_tut_frontend_nonce' ) ) {
			wp_die( __( 'Your request failed permission check.', 'wp-ajax-tut' ) );
		}

		// Destroy user's session
		wp_logout();

		// Send success message
		wp_send_json( array(
			'status' => 'success',
			'message' => __( 'Logged out. See ya!', 'wp-ajax-tut' )
		) );
	}
}

endif;

/**
 *	Main function
 *	@return object WP_AJAX_Tut instance
 */
function WP_AJAX_Tut() {
	return WP_AJAX_Tut::instance();
}

WP_AJAX_Tut();
