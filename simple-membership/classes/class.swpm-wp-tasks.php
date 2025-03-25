<?php

class SwpmWpTasks {

	public function __construct() {

	}

	public function do_wp_tasks(){
		if ( !is_admin() ){
			$this->process_password_reset();
			$this->process_password_reset_using_link();
		}
	}

	public function process_password_reset() {
		$message          = '';
		$swpm_reset       = filter_input( INPUT_POST, 'swpm-reset' );
		$swpm_reset_email = filter_input( INPUT_POST, 'swpm_reset_email', FILTER_UNSAFE_RAW );
		if ( ! empty( $swpm_reset ) ) {
			SwpmFrontRegistration::get_instance()->reset_password( $swpm_reset_email );
		}
	}

	public function process_password_reset_using_link() {
		$swpm_reset = filter_input( INPUT_POST, 'swpm-password-reset-using-link' );
		if( is_null( $swpm_reset ) ) {
			return;
		}

		$error_message = '';

		$user_login = filter_input( INPUT_POST, 'swpm_user_login', FILTER_UNSAFE_RAW );
		$user_login = sanitize_user( $user_login );

		//Check 'swpm_user_login' matches with $_GET['login']
		if( $user_login != $_GET['login'] ) {
			$error_message = __("Error! Invalid password reset request.", 'simple-membership');
		}

		//Validate password reset key
		$is_valid_key = check_password_reset_key($_GET['key'], $_GET['login']);
		if ( is_wp_error( $is_valid_key ) ) {
			$error_message = __("Error! A password reset request has been submitted but the password reset key is invalid. Please generate a new request.", "simple-membership");
		}

		//Validate password fields match
		$swpm_new_password = filter_input( INPUT_POST, 'swpm_new_password', FILTER_UNSAFE_RAW );
		$swpm_renew_password = filter_input( INPUT_POST, 'swpm_reenter_new_password', FILTER_UNSAFE_RAW );
		if( $swpm_new_password != $swpm_renew_password ) {
			$error_message = __("Error! Password fields do not match. Please try again.", 'simple-membership');
		}

		//Validate user exists
		$user_data = get_user_by( "login", $_GET['login'] );
		if( !$user_data ) {
			$error_message = __("Error! Invalid password reset request.", 'simple-membership');
		}

		if( strlen( $error_message) > 0 ) {
			//If any error messsage, save it in the transient for output later. The transient will be deleted after it is displayed.
			//The error output is displayed in the form's HTML output file.
			set_transient( "swpm-passsword-reset-error", $error_message );
			return;
		}

		if ( ! empty( $swpm_reset ) && strlen( $error_message ) == 0 ) {
			//Valiation passed. Lets try to reset the password.
			$is_password_reset = SwpmFrontRegistration::get_instance()->reset_password_using_link( $user_data, $swpm_new_password );
			if( $is_password_reset ) {
				$login_page_url = SwpmSettings::get_instance()->get_value( 'login-page-url' );

				// Allow hooks to change the value of login_page_url
				$login_page_url = apply_filters('swpm_register_front_end_login_page_url', $login_page_url);

				$after_pwd_reset = '<div class="swpm-reset-password-success-msg">' . __( 'Password Reset Successful. ', 'simple-membership' ) . __( 'Please' , 'simple-membership') . ' <a href="' . $login_page_url . '">' . __( 'Log In', 'simple-membership' ) . '</a></div>';
				$after_pwd_reset = apply_filters( 'swpm_password_reset_success_msg', $after_pwd_reset );
				$message_ary = array(
					'succeeded' => true,
					'message'   => $after_pwd_reset,
				);
				SwpmTransfer::get_instance()->set( 'status', $message_ary );
				return;
			}
		}
	}
}
