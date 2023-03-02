<?php

class SwpmInitTimeTasks {

	public function __construct() {

	}

	public function do_init_tasks() {

		//Set up localisation. First loaded ones will override strings present in later loaded file.
		//Allows users to have a customized language in a different folder.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'simple-membership' );
		load_textdomain( 'simple-membership', WP_LANG_DIR . "/simple-membership-$locale.mo" );
		load_plugin_textdomain( 'simple-membership', false, SIMPLE_WP_MEMBERSHIP_DIRNAME . '/languages/' );

		if ( ! isset( $_COOKIE['swpm_session'] ) ) { // give a unique ID to current session.
			$uid                     = md5( microtime() );
			$_COOKIE['swpm_session'] = $uid; // fake it for current session/
			if ( ! headers_sent() ) {
				setcookie( 'swpm_session', $uid, 0, '/' );
			}
		}

		//Crete the custom post types
		$this->create_post_type();

		//Do frontend-only init time tasks
		if ( ! is_admin() ) {
                        //Trigger an action hook 
                        do_action('swpm_do_init_time_tasks_front_end');
                        
			SwpmAuth::get_instance();

			$this->check_and_handle_auto_login();
			$this->verify_and_delete_account();

			$swpm_logout = filter_input( INPUT_GET, 'swpm-logout' );
			if ( ! empty( $swpm_logout ) ) {
				SwpmAuth::get_instance()->logout();
				$redirect_url = apply_filters( 'swpm_after_logout_redirect_url', SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL );
				wp_redirect( trailingslashit( $redirect_url ) );
				exit( 0 );
			}
			$this->process_password_reset();
			$this->process_password_reset_using_link();
			$this->register_member();
			$this->check_and_do_email_activation();
			$this->edit_profile();
			SwpmCommentFormRelated::check_and_restrict_comment_posting_to_members();
		} else {
			//Do admin side init time tasks
			if ( current_user_can( SWPM_MANAGEMENT_PERMISSION ) ) {
				//Admin dashboard side stuff
				$this->admin_init();
			}
		}
	}

	public function admin_init() {
		$createswpmuser = filter_input( INPUT_POST, 'createswpmuser' );
		if ( ! empty( $createswpmuser ) ) {
			SwpmAdminRegistration::get_instance()->register_admin_end();
		}
		$editswpmuser = filter_input( INPUT_POST, 'editswpmuser' );
		if ( ! empty( $editswpmuser ) ) {
			$id = filter_input( INPUT_GET, 'member_id', FILTER_VALIDATE_INT );
			SwpmAdminRegistration::get_instance()->edit_admin_end( $id );
		}
		$createswpmlevel = filter_input( INPUT_POST, 'createswpmlevel' );
		if ( ! empty( $createswpmlevel ) ) {
			SwpmMembershipLevel::get_instance()->create_level();
		}
		$editswpmlevel = filter_input( INPUT_POST, 'editswpmlevel' );
		if ( ! empty( $editswpmlevel ) ) {
			$id = filter_input( INPUT_GET, 'id' );
			SwpmMembershipLevel::get_instance()->edit_level( $id );
		}
		$update_category_list = filter_input( INPUT_POST, 'update_category_list' );
		if ( ! empty( $update_category_list ) ) {
			include_once 'class.swpm-category-list.php';
			SwpmCategoryList::update_category_list();
		}
		$update_post_list = filter_input( INPUT_POST, 'update_post_list' );
		if ( ! empty( $update_post_list ) ) {
			include_once 'class.swpm-post-list.php';
			SwpmPostList::update_post_list();
		}
	}

	public function create_post_type() {
		//The payment button data for membership levels will be stored using this CPT
		register_post_type(
			'swpm_payment_button',
			array(
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'page',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'editor' ),
			)
		);

		//Transactions will be stored using this CPT in parallel with swpm_payments_tbl DB table
		$args = array(
			'supports'            => array( '' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
		);
		register_post_type( 'swpm_transactions', $args );
	}

	private function verify_and_delete_account() {
		include_once SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-members.php';
		$delete_account = filter_input( INPUT_GET, 'swpm_delete_account' );
		if ( empty( $delete_account ) ) {
			return;
		}
		$password = filter_input( INPUT_POST, 'account_delete_confirm_pass', FILTER_UNSAFE_RAW );

		$auth = SwpmAuth::get_instance();
		if ( ! $auth->is_logged_in() ) {
			return;
		}
		if ( empty( $password ) ) {
			SwpmUtils::account_delete_confirmation_ui();
		}

		$nonce_field = filter_input( INPUT_POST, 'account_delete_confirm_nonce' );
		if ( empty( $nonce_field ) || ! wp_verify_nonce( $nonce_field, 'swpm_account_delete_confirm' ) ) {
			SwpmUtils::account_delete_confirmation_ui( SwpmUtils::_( 'Sorry, Nonce verification failed.' ) );
		}
		if ( $auth->match_password( $password ) ) {
			$auth->delete();
			wp_safe_redirect( get_home_url() );
			exit( 0 );
		} else {
			SwpmUtils::account_delete_confirmation_ui( SwpmUtils::_( "Sorry, Password didn't match." ) );
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
		$user_data = get_user_by( "login", $user_login );
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

				$after_pwd_reset = '<div class="swpm-reset-password-success-msg">' . SwpmUtils::_( 'Password Reset Successful. ' ) . SwpmUtils::_( 'Please' ) . ' <a href="' . $login_page_url . '">' . SwpmUtils::_( 'Log In' ) . '</a></div>';
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

	private function register_member() {
		$registration = filter_input( INPUT_POST, 'swpm_registration_submit' );
		if ( ! empty( $registration ) ) {
			SwpmFrontRegistration::get_instance()->register_front_end();
		}
	}

	private function check_and_do_email_activation() {
		$email_activation = filter_input( INPUT_GET, 'swpm_email_activation', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $email_activation ) ) {
			SwpmFrontRegistration::get_instance()->email_activation();
		}
		//also check activation email resend request
		$email_activation_resend = filter_input( INPUT_GET, 'swpm_resend_activation_email', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $email_activation_resend ) ) {
			SwpmFrontRegistration::get_instance()->resend_activation_email();
		}
	}

	private function edit_profile() {
		$swpm_editprofile_submit = filter_input( INPUT_POST, 'swpm_editprofile_submit' );
		if ( ! empty( $swpm_editprofile_submit ) ) {
			SwpmFrontRegistration::get_instance()->edit_profile_front_end();
			//TODO - allow an option to do a redirect if successful edit profile form submission?
		}
	}

	public function check_and_handle_auto_login() {

		if ( isset( $_REQUEST['swpm_auto_login'] ) && $_REQUEST['swpm_auto_login'] == '1' ) {
			//Handle the auto login
			SwpmLog::log_simple_debug( 'Handling auto login request...', true );

			$enable_auto_login = SwpmSettings::get_instance()->get_value( 'auto-login-after-rego' );
			if ( empty( $enable_auto_login ) ) {
				SwpmLog::log_simple_debug( 'Auto login after registration feature is disabled in settings.', true );
				return;
			}

			//Check auto login nonce value
			$auto_login_nonce = isset( $_REQUEST['swpm_auto_login_nonce'] ) ? $_REQUEST['swpm_auto_login_nonce'] : '';
			if ( ! wp_verify_nonce( $auto_login_nonce, 'swpm-auto-login-nonce' ) ) {
				SwpmLog::log_simple_debug( 'Error! Auto login nonce verification check failed!', false );
				wp_die( 'Auto login nonce verification check failed!' );
			}

			//Perform the login
			$auth         = SwpmAuth::get_instance();
			$user         = apply_filters( 'swpm_user_name', filter_input( INPUT_GET, 'swpm_user_name' ) );
			$user         = sanitize_user( $user );
			$encoded_pass = filter_input( INPUT_GET, 'swpm_encoded_pw' );
			$pass         = base64_decode( $encoded_pass );
			$auth->login( $user, $pass );
			SwpmLog::log_simple_debug( 'Auto login request completed for: ' . $user, true );
		}
	}

}
