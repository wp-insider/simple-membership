<?php

/**
 * Description of BFrontRegistration
 *
 * @author nur
 */
class SwpmFrontRegistration extends SwpmRegistration {

	public static function get_instance() {
		self::$_intance = empty( self::$_intance ) ? new SwpmFrontRegistration() : self::$_intance;
		return self::$_intance;
	}

	public function regigstration_ui( $level ) {

		$settings_configs = SwpmSettings::get_instance();

		//Check if the hide rego from logged-in users feature is enabled before rendering the registration form.
		$hide_rego_to_logged_users = $settings_configs->get_value( 'hide-rego-form-to-logged-users' );
		if ( ! empty( $hide_rego_to_logged_users ) ){
			//Hide registration form to logged-in users feature is enabled. Check if the form should be hidden.
			if ( SwpmMemberUtils::is_member_logged_in() ) {
				$rego_hidden_to_logged_users_msg = '<div class="swpm_registration_hidden_to_logged_users_msg">';
				$rego_hidden_to_logged_users_msg .= '<div class="hide-rego-form-msg">' . SwpmUtils::_( "You are already logged in. You don't need to create another account. So the registration form is hidden." ) . '</div>';
				$rego_hidden_to_logged_users_msg .= '</div>';
				$rego_hidden_to_logged_users_msg = apply_filters('swpm_registration_form_hidden_message', $rego_hidden_to_logged_users_msg);
				return $rego_hidden_to_logged_users_msg;
			}
		}
		
		//Check if the registration completion link (if present in the URL) has been already used. Show an  appropriate error message to prevent confusion.
		if( SwpmUtils::is_registration_completion_link_already_used() ){
			$already_used_rego_complete_link_msg = '<div class="swpm_already_used_registration_complete_link_msg">';
			$already_used_rego_complete_link_msg .= SwpmUtils::_( "This unique registration link (see the URL in the browser's address bar) has already been used. You don't need to create another account. Log into the existing account to access the protected content." );
			$already_used_rego_complete_link_msg .= '</div>';
			$already_used_rego_complete_link_msg = apply_filters('swpm_already_used_registration_link_message', $already_used_rego_complete_link_msg);
			return $already_used_rego_complete_link_msg;
		}
		
		//Check if the registration completion link (if present in the URL) is invalid. Show an appropriate error message to prevent confusion (when they are clicking an invalid/deleted/old link).
		if( SwpmUtils::is_registration_completion_link_invalid() ){
				$rego_complete_link_invalid_msg = '<div class="swpm_registration_complete_link_invalid_msg">';
				$rego_complete_link_invalid_msg .= SwpmUtils::_( "This unique registration link (see the URL in the browser's address bar) is invalid. Could not find a match for the given member ID and the security code. Please contact the site administrator." );
				$rego_complete_link_invalid_msg .= '</div>';
				$rego_complete_link_invalid_msg = apply_filters('swpm_registration_completion_link_invalid_message', $rego_complete_link_invalid_msg);
				return $rego_complete_link_invalid_msg;
		}

		//Trigger the filter to override the registration form (the form builder addon uses this filter)
		$form = apply_filters( 'swpm_registration_form_override', '', $level ); //The $level value could be empty also so the code handling the filter need to check for it.
		if ( ! empty( $form ) ) {
			//An addon has overridden the registration form. So use that one.
			return $form;
		}

		$joinuspage_url = $settings_configs->get_value( 'join-us-page-url' );
		$membership_level = '';

		if ( SwpmUtils::is_paid_registration() ) {
			//Lets check if this is a registration for paid membership
			$member = SwpmUtils::get_paid_member_info();
			if ( empty( $member ) ) {
				_e( 'Error! Invalid Request. Could not find a match for the given security code and the user ID.', 'simple-membership' );
			} else {
				$membership_level = $member->membership_level;
			}
		} elseif ( ! empty( $level ) ) {
			//Membership level is specified in the shortcode (level specific registration form).
			$member           = SwpmTransfer::$default_fields;
			$membership_level = absint( $level );
		}
                
		//Check if free membership registration is disalbed on the site
		if ( empty( $membership_level ) ) {
			$joinuspage_link         = '<a href="' . $joinuspage_url . '">' . SwpmUtils::_( 'Join Us' ) . '</a>';
			$free_rego_disabled_msg  = '<p>';
			$free_rego_disabled_msg .= __( 'Free membership is disabled on this site. ', 'simple-membership' );

			//Check if the "Hide Join Us Link" feature is enabled. If it is enabled, don't show the link.
			$hide_join_us_link_enabled = SwpmSettings::get_instance()->get_value('hide-join-us-link');
			if (empty($hide_join_us_link_enabled)){
				//Show the "Join Us" option.
				$free_rego_disabled_msg .= __( 'Please make a payment from the ', 'simple-membership'  );
				$free_rego_disabled_msg .= SwpmUtils::_( $joinuspage_link );
				$free_rego_disabled_msg .= SwpmUtils::_( ' page to pay for a premium membership.' );
				$free_rego_disabled_msg .= '</p><p>';
				$free_rego_disabled_msg .= SwpmUtils::_( 'You will receive a unique link via email after the payment. You will be able to use that link to complete the premium membership registration.' );
			}

			$free_rego_disabled_msg .= '</p>';
			return $free_rego_disabled_msg;
		}

		//Handle the registration form in core plugin
		$membership_info = SwpmPermission::get_instance( $membership_level );
		$membership_level = $membership_info->get( 'id' );
		if ( empty( $membership_level ) ) {
			return 'Error! Failed to retrieve membership level ID from the membership info object.';
		}
		$level_identifier = md5( $membership_level );
		$membership_level_alias = $membership_info->get( 'alias' );
		$swpm_registration_submit = filter_input( INPUT_POST, 'swpm_registration_submit' );
		if ( ! empty( $swpm_registration_submit ) ) {
			$member = array_map( 'sanitize_text_field', $_POST );
		}
		ob_start();

		$hide_membership_level_field = $settings_configs->get_value( 'hide-reg-form-membership-level-field' );

		$tpl_data = (array) $member;
		$tpl_data['hide_membership_level_field'] = $hide_membership_level_field;
		$tpl_data['membership_level'] = $membership_level;
		$tpl_data['membership_level_alias'] = $membership_level_alias;
		$tpl_data['level_identifier'] = $level_identifier;

		$render_new_form_ui = $settings_configs->get_value('use-new-form-ui');
		if (!empty($render_new_form_ui)) {
			SwpmUtilsTemplate::swpm_load_template('add-v2.php', false, $tpl_data);
		}else{
			SwpmUtilsTemplate::swpm_load_template('add.php', false, $tpl_data);
		}
		return ob_get_clean();
	}

	public function register_front_end() {
		//Registration data update sequence:
		//1. Save the data in the simple membership member table.
		//2. Create a corresponding WP user account.
		//3. Send the registration complete email.

		//Trigger action hook
		do_action( 'swpm_front_end_registration_form_submitted' );

		//If captcha is present and validation failed, it returns an error string. If validation succeeds, it returns an empty string.
		$captcha_validation_output = apply_filters( 'swpm_validate_registration_form_submission', '' );
		if ( ! empty( $captcha_validation_output ) ) {
			$message = array(
				'succeeded' => false,
				'message'   => SwpmUtils::_( 'Security check: captcha validation failed.' ),
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return;
		}

		//Check if Terms and Conditions enabled
		$terms_enabled = SwpmSettings::get_instance()->get_value( 'enable-terms-and-conditions' );
		if ( ! empty( $terms_enabled ) ) {
			//check if user checked "I accept terms" checkbox
			if ( empty( $_POST['accept_terms'] ) ) {
				$message = array(
					'succeeded' => false,
					'message'   => SwpmUtils::_( 'You must accept the terms and conditions.' ),
				);
				SwpmTransfer::get_instance()->set( 'status', $message );
				return;
			}
		}

		//Check if Privacy Policy enabled
		$pp_enabled = SwpmSettings::get_instance()->get_value( 'enable-privacy-policy' );
		if ( ! empty( $pp_enabled ) ) {
			//check if user checked "I agree with Privacy Policy" checkbox
			if ( empty( $_POST['accept_pp'] ) ) {
				$message = array(
					'succeeded' => false,
					'message'   => SwpmUtils::_( 'You must agree to the privacy policy.' ),
				);
				SwpmTransfer::get_instance()->set( 'status', $message );
				return;
			}
		}

		//Validate swpm level hash data.
		$hash_val_posted = sanitize_text_field( $_POST['swpm_level_hash'] );
		$level_value     = SwpmForm::get_membership_level_from_request();
		$swpm_p_key      = get_option( 'swpm_private_key_one' );
		$hash_val        = md5( $swpm_p_key . '|' . $level_value );
		if ( $hash_val != $hash_val_posted ) {//Level hash validation failed.
			$msg  = '<p>Error! Security check failed for membership level validation.</p>';
			$msg .= '<p>The submitted membership level data does not seem to be authentic.</p>';
			$msg .= '<p>If you are using caching please empty the cache data and try again.</p>';
			wp_die( $msg );
		}

		$this->email_activation = get_option( 'swpm_email_activation_lvl_' . $level_value );

		//Crete the member profile and send notification
		if ( $this->create_swpm_user() ) {
			//SWPM user creation was successful. Now create the corresponding WP user record and send the notification email.
			if ( $this->prepare_and_create_wp_user_front_end() && $this->send_reg_email() ){
				do_action( 'swpm_front_end_registration_complete' ); //Keep this action hook for people who are using it (so their implementation doesn't break).
				do_action( 'swpm_front_end_registration_complete_user_data', $this->member_info );

				//Check if there is after registration redirect (for non-email activation scenario).
				if ( ! $this->email_activation ) {
					//This is a non-email activation scenario.
					$after_rego_url = SwpmSettings::get_instance()->get_value( 'after-rego-redirect-page-url' );
					$after_rego_url = apply_filters( 'swpm_after_registration_redirect_url', $after_rego_url );
					if ( ! empty( $after_rego_url ) ) {
						//Yes. Need to redirect to this after registration page
						SwpmLog::log_simple_debug( 'After registration redirect is configured in settings. Redirecting user to: ' . $after_rego_url, true );
						wp_redirect( $after_rego_url );
						exit( 0 );
					}
				}

				//Set the registration complete message
				if ( $this->email_activation ) {
					//This is an email activation scenario.
					$email_act_msg  = '<div class="swpm-registration-success-msg">';
					$email_act_msg .= SwpmUtils::_( 'You need to confirm your email address. Please check your email and follow instructions to complete your registration.' );
					$email_act_msg .= '</div>';
					$email_act_msg = apply_filters( 'swpm_registration_email_activation_msg', $email_act_msg );//Can be added to the custom messages addon.
					$message        = array(
						'succeeded' => true,
						'message'   => $email_act_msg,
					);
				} else {
					$login_page_url = SwpmSettings::get_instance()->get_value( 'login-page-url' );

					// Allow hooks to change the value of login_page_url
					$login_page_url = apply_filters('swpm_register_front_end_login_page_url', $login_page_url);

					$after_rego_msg = '<div class="swpm-registration-success-msg">' . SwpmUtils::_( 'Registration Successful. ' ) . SwpmUtils::_( 'Please' ) . ' <a href="' . $login_page_url . '">' . SwpmUtils::_( 'Log In' ) . '</a></div>';
					$after_rego_msg = apply_filters( 'swpm_registration_success_msg', $after_rego_msg );
					$message        = array(
						'succeeded' => true,
						'message'   => $after_rego_msg,
					);
				}
				SwpmTransfer::get_instance()->set( 'status', $message );
				return;
			}
		}
	}

	/*
	 * This function creates/updates the SWPM user record in the database.
	 * It returns true if the user creation was successful. Otherwise, it returns false.
	 */
	private function create_swpm_user() {
		/*
		 * Create the $member_info array with the sanitized form data. Then save the data in the members table.
		 */

		global $wpdb;
		$member = SwpmTransfer::$default_fields;
		$form   = new SwpmFrontForm( $member );
		if ( ! $form->is_valid() ) {
			$message = array(
				'succeeded' => false,
				'message'   => SwpmUtils::_( 'Please correct the following' ),
				'extra'     => $form->get_errors(),
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return false;
		}

		$member_info = $form->get_sanitized_member_form_data();

		//Check if the email belongs to an existing wp user account with admin role.
        SwpmMemberUtils::check_and_die_if_email_belongs_to_admin_user($member_info['email']);

		//Go ahead and create the SWPM user record.
		$free_level = SwpmUtils::get_free_level();
		$member_info['last_accessed_from_ip'] = SwpmUtils::get_user_ip_address();
		$member_info['member_since'] = SwpmUtils::get_current_date_in_wp_zone(); //date( 'Y-m-d' );
		$member_info['subscription_starts'] = SwpmUtils::get_current_date_in_wp_zone(); //date( 'Y-m-d' );

		$membership_level_id = filter_input( INPUT_POST, 'swpm_membership_level', FILTER_SANITIZE_NUMBER_INT );

		/**
		 * Determine the account status for the new member record.
		 * First, check if email activation is required. If so, assign the 'activation_required' account status.
		 * If not, check if a default account status is set per membership level.
		 * If a membership level default is set, use that setting; otherwise, use the global settings.
		 */
		if ( $this->email_activation ) {
			//Email activation is enabled. Set the account status to 'activation_required'.
			$account_status = 'activation_required';
		} else {
			//Check if a default account status is set per membership level.
			$level_custom_fields = SwpmMembershipLevelCustom::get_instance_by_id($membership_level_id);

			// Get per membership level default account status settings (if any).
			$account_status = sanitize_text_field($level_custom_fields->get('default_account_status'));

			if ( !isset( $account_status ) || empty( $account_status ) ){
				//Fallback. Use the value from the global settings.
				$account_status = SwpmSettings::get_instance()->get_value( 'default-account-status', 'active' );
			}
		}
		$member_info['account_state'] = $account_status;
		SwpmLog::log_simple_debug("Creating new swpm user. Account status: ". $account_status . ", Membership Level ID: ".$membership_level_id, true);

		//Save the plain password in temporary variable for use in the later execution steps.
		$plain_password = $member_info['plain_password'];
		unset( $member_info['plain_password'] );

		if ( SwpmUtils::is_paid_registration() ) {
			/* Paid membership registration path (the member's record is originally created after the payment). */

			//Remove any empty values from the array. This will preserve address information if it was received via the payment gateway.
			$member_info = array_filter($member_info);

			//Handle DB insert for paid registration scenario.
			$member_info['reg_code'] = '';
			$member_id = filter_input( INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT );
			$code = isset( $_GET['code'] ) ? sanitize_text_field( stripslashes ( $_GET['code'] ) ) : '';

			//Trigger the before member data save filter hook. It can be used to customize the member data before it gets saved in the database.
			$member_info = apply_filters( 'swpm_registration_data_before_save', $member_info );

			//Update the member's record in the database. 
			$query_result = $wpdb->update(
				$wpdb->prefix . 'swpm_members_tbl',/*table*/
				$member_info,/*data*/
				array(/*where*/
					'member_id' => $member_id,
					'reg_code'  => $code,
				)
			);

			//Verify that the update was successful. Otherwise, set error message and return false so the process stops here.
			if ( $query_result === false ) {
				SwpmLog::log_simple_debug( 'Error! Failed to update the member record on registration form submit. Check that the member ID ('.$member_id.') and the reg_code ('.$code.') are correct.', false );
				$message = array(
					'succeeded' => false,
					'message'   => SwpmUtils::_( 'Unexpected Error! Failed to update the member record. Enable the debug log file then try the process again to get more details.' ),
				);
				SwpmTransfer::get_instance()->set( 'status', $message );
				return false;
			}

			$query = $wpdb->prepare( 'SELECT membership_level FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE member_id=%d', $member_id );
			$member_info['membership_level'] = $wpdb->get_var( $query );
			$last_insert_id = $member_id;
		} elseif ( ! empty( $free_level ) ) {
			/* Free account/membership registration path. */

			$member_info['membership_level'] = $free_level;

			//Trigger the before member data save filter hook. It can be used to customize the member data before it gets saved in the database.
			$member_info = apply_filters( 'swpm_registration_data_before_save', $member_info );

			//Create a new member record in the database for the free account/member registration.
			$wpdb->insert( $wpdb->prefix . 'swpm_members_tbl', $member_info );
			$last_insert_id = $wpdb->insert_id;
		} else {
			/* Error condition. Show an error message and return false so the process stops here. */
			$message = array(
				'succeeded' => false,
				'message'   => SwpmUtils::_( 'Membership Level Couldn\'t be found.' ),
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return false;
		}
		$member_info['plain_password'] = $plain_password;

		//Save the updated member info in the class property so it can be used in the later execution steps.
		$this->member_info = $member_info;
		return true;
	}

	private function prepare_and_create_wp_user_front_end() {
		global $wpdb;
		$member_info = $this->member_info;

		//Retrieve the user role assigned for this level
		$query     = $wpdb->prepare( 'SELECT role FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE id = %d', $member_info['membership_level'] );
		$user_role = $wpdb->get_var( $query );
		//Check to make sure that the user role of this level is not admin.
		if ( $user_role == 'administrator' ) {
			//For security reasons we don't allow users with administrator role to be creted from the front-end. That can only be done from the admin dashboard side.
			$error_msg  = '<p>Error! The user role for this membership level (level ID: ' . $member_info['membership_level'] . ') is set to "Administrator".</p>';
			$error_msg .= '<p>For security reasons, member registration to this level is not permitted from the front end.</p>';
			$error_msg .= '<p>An administrator of the site can manually create a member record with this access level from the admin dashboard side.</p>';
			wp_die( $error_msg );
		}

		$wp_user_info                    = array();
		$wp_user_info['user_nicename']   = implode( '-', explode( ' ', $member_info['user_name'] ) );
		$wp_user_info['display_name']    = isset($member_info['user_name']) ? $member_info['user_name'] : '';
		$wp_user_info['user_email']      = isset($member_info['email']) ? $member_info['email'] : '';
		$wp_user_info['nickname']        = isset($member_info['user_name']) ? $member_info['user_name'] : '';
		$wp_user_info['first_name']      = isset($member_info['first_name']) ? $member_info['first_name'] : '';
		$wp_user_info['last_name']       = isset($member_info['last_name']) ? $member_info['last_name'] : '';
		$wp_user_info['user_login']      = isset($member_info['user_name']) ? $member_info['user_name'] : '';
		$wp_user_info['password']        = isset($member_info['plain_password']) ? $member_info['plain_password'] : '';
		$wp_user_info['role']            = $user_role;
		$wp_user_info['user_registered'] = date( 'Y-m-d H:i:s' );
		SwpmUtils::create_wp_user( $wp_user_info );
		return true;
	}

	public function edit_profile_front_end() {
		global $wpdb;
		//Check that the member is logged in
		$auth = SwpmAuth::get_instance();
		if ( ! $auth->is_logged_in() ) {
			return;
		}

		//Check nonce
		if ( ! isset( $_POST['swpm_profile_edit_nonce_val'] ) || ! wp_verify_nonce( $_POST['swpm_profile_edit_nonce_val'], 'swpm_profile_edit_nonce_action' ) ) {
			//Nonce check failed.
			wp_die( __( 'Error! Nonce verification failed for front end profile edit.', 'simple-membership' ) );
		}
                
        //Trigger action hook
        do_action( 'swpm_front_end_edit_profile_form_submitted' );
                
		$user_data = (array) $auth->userData;
		unset( $user_data['permitted'] );
		$form = new SwpmForm( $user_data );
		if ( $form->is_valid() ) {
			//Successful form submission. Proceed with the profile update.
			
			/********************************
			//Profile update sequence:
			1) Update the WP user entry with the new data.
			2) Update the SWPM member entry with the new data.
			3) Reload the user data so the profile page reflects the new data.
			4) Reset the auth cookies (if the password was updated).
			*********************************/

			global $wpdb;
			$msg_str = '<div class="swpm-profile-update-success">' . __( 'Profile updated successfully.', 'simple-membership' ) . '</div>';
			$message = array(
				'succeeded' => true,
				'message'   => $msg_str,
			);

			//Get the sanitized member form data.
			$member_info = $form->get_sanitized_member_form_data();

            //Check if membrship_level value has been posted.
            if ( isset( $member_info['membership_level'] ) ){
                //For edit profile, remove the membership level from the array (because we don't allow level updating in profile edit)
                unset( $member_info['membership_level'] );
            }

			//Update the corresponding wp user record.
			SwpmUtils::update_wp_user( $auth->get( 'user_name' ), $member_info ); 

			//Lets check if password was also changed.
			$password_also_changed = false;
			if ( isset( $member_info['plain_password'] ) ) {
				//Password was also changed.
				$msg_str = '<div class="swpm-profile-update-success">' . __( 'Profile updated successfully.', 'simple-membership') . '</div>';
				$message = array(
					'succeeded' => true,
					'message'   => $msg_str,
				);
				//unset the plain password from the member info array so it doesn't try to save it in the database.
				unset( $member_info['plain_password'] );
				//Set the password changed flag.
				$password_also_changed = true;
			}

			// Only these fields are whitelisted for front end profile update.
			$accepted_fields = array(
    			'email',
				'password',
    			'first_name',
    			'last_name',
    			'phone',
    			'address_street',
    			'address_city',
    			'address_state',
    			'address_zipcode',
    			'country',
    			'company_name',
			);

			// Remove unwanted fields:
			$member_info = array_intersect_key($member_info, array_flip($accepted_fields));

			//Update the data in the swpm database.
			$swpm_id = $auth->get( 'member_id' );
			//SwpmLog::log_simple_debug("Updating member profile data with SWPM ID: " . $swpm_id, true);
			$member_info = array_filter( $member_info, array($this, 'filter_empty_member_info_fields'), ARRAY_FILTER_USE_BOTH  );//Remove any null values (except first_name and last_name).
			$wpdb->update( $wpdb->prefix . 'swpm_members_tbl', $member_info, array( 'member_id' => $swpm_id ) );

			//Reload user data after update so the profile page reflects the new data.
			$auth->reload_user_data();

			//Check if password was also changed.
			if ( $password_also_changed ) {
				//Password was also changed. Clear and reset the user's auth cookies so they can stay logged in.
				SwpmLog::log_simple_debug( 'Member has updated the password from the SWPM profile edit page. Member ID: ' . $swpm_id, true );

				$auth_object = SwpmAuth::get_instance();
				$swpm_user_name = $auth_object->get( 'user_name' );
				$user_info_params = array(
					'member_id' => $swpm_id,
					'user_name' => $swpm_user_name,
					'new_enc_password' => $member_info['password'],
				);
				$auth_object->reset_auth_cookies_after_pass_change($user_info_params);

				//Trigger action hook
				do_action( 'swpm_front_end_profile_password_changed', $member_info );
			}

			//This message will be persistent (for this user's session) until the message is displayed.
			SwpmTransfer::get_instance()->set( 'status', $message );

			//Trigger action hook
			do_action( 'swpm_front_end_profile_edited', $member_info );

			//Do a page refresh to reflect the new data.
			//This is specially useful when the user changes their password which will invalidate the current auth cookies and the nonce values. 
			//A page refresh will generate new nonce values (using the new auth cookies) and the user can submit the profile form again without any issues.		
			$current_page_url = SwpmMiscUtils::get_current_page_url();
			SwpmMiscUtils::redirect_to_url( $current_page_url );

			//Success. Profile updated.
			return true; 
		} else {
			$msg_str = '<div class="swpm-profile-update-error">' . SwpmUtils::_( 'Please correct the following.' ) . '</div>';
			$message = array(
				'succeeded' => false,
				'message'   => $msg_str,
				'extra'     => $form->get_errors(),
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return false; //Error in the form submission.
		}
	}

	/**
	 * array_filter callback to remove any null values except for the first_name and last_name array indexes.
	 */
	public function filter_empty_member_info_fields ($item_value, $item_key){
		//Returning 'true' will keep the item in the array (so it will not be filtered out). We want to keep the first_name and last_name fields even if they are empty.
		//Returning 'false' will perform the filtering and remove the item from the array if it is empty (so the null/empty value will not be saved in the database).
		if (in_array($item_key, array('first_name', 'last_name'))){
			//Keep the first_name and last_name fields even if they are empty.
			return true;
		}

		return !empty($item_value);
	}

	public function reset_password( $email ) {

		//If captcha is present and validation failed, it returns an error string. If validation succeeds, it returns an empty string.
		$captcha_validation_output = apply_filters( 'swpm_validate_pass_reset_form_submission', '' );
		if ( ! empty( $captcha_validation_output ) ) {
			$message = '<div class="swpm-reset-pw-error">' . SwpmUtils::_( 'Captcha validation failed.' ) . '</div>';
			$message = array(
				'succeeded' => false,
				'message'   => $message,
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return;
		}

		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			$message = '<div class="swpm-reset-pw-error">' . SwpmUtils::_( 'Email address not valid.' ) . '</div>';
			$message = array(
				'succeeded' => false,
				'message'   => $message,
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return;
		}

		global $wpdb;
		$query = 'SELECT member_id,user_name,first_name, last_name FROM ' .
				$wpdb->prefix . 'swpm_members_tbl ' .
				' WHERE email = %s';

		$user  = $wpdb->get_row( $wpdb->prepare( $query, $email ) );
		if ( empty( $user ) ) {
			$message  = '<div class="swpm-reset-pw-error">' . SwpmUtils::_( 'No user found with that email address.' ) . '</div>';
			$message .= '<div class="swpm-reset-pw-error-email">' . SwpmUtils::_( 'Email Address: ' ) . $email . '</div>';
			$message  = array(
				'succeeded' => false,
				'message'   => $message,
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			return;
		}

		// Check if incomplete member account
		if ( !isset($user->user_name) || empty($user->user_name)){
			$message  = '<div class="swpm-reset-pw-error">';
			$message  .= __('Your account registration is not yet complete. Please finish the registration process before using the password reset option. If you need assistance, contact the site administrator.', 'simple-membership');
			$message  .= '</div>';
			$message_ary = array(
				'succeeded'       => false,
				'message'         => $message,
				'pass_reset_sent' => false,
			);

			SwpmTransfer::get_instance()->set( 'status', $message_ary );
			// Redirecting to current page to avoid password reset request form resubmission on page reload.
			SwpmMiscUtils::redirect_to_url( SwpmMiscUtils::get_current_page_url() );
		}

		$settings = SwpmSettings::get_instance();
		$password_reset_link='';		
		$additional_args =array();
		$message  = '<div class="swpm-reset-pw-success-box">';
                
		$password_reset_using_link = $settings->get_value( 'password-reset-using-link' );
		if( $password_reset_using_link ) {
			$user_data = get_user_by( "email", $email );
			if( $user_data ) {
				$key = get_password_reset_key( $user_data );
				$user_login = $user_data->user_login;
				$password_reset_link = esc_url_raw( $settings->get_value("reset-page-url") . "?action=swpm-reset-using-link&key=$key&login=" . rawurlencode($user_login) );
				$additional_args["password_reset_link"] = $password_reset_link;

				//Skip the {password} tag
				$additional_args["password"] = "Reset password using link";

				SwpmLog::log_simple_debug( 'Reset password using link option is enabled.', true );
				$message .= '<div class="swpm-reset-pw-success">' . SwpmUtils::_( 'Password reset link has been sent to your email address.' ) . '</div>';
			}
		}
		else {
			$password = wp_generate_password();
                
			//Trigger a hook
			$password = apply_filters( 'swpm_password_reset_generated_pass', $password );
	
			$password_hash = SwpmUtils::encrypt_password( trim( $password ) );
			$wpdb->update( $wpdb->prefix . 'swpm_members_tbl', array( 'password' => $password_hash ), array( 'member_id' => $user->member_id ) );
	
			//Update wp user password
			add_filter( 'send_password_change_email', array( &$this, 'dont_send_password_change_email' ), 1, 3 ); //Stop WordPress from sending a reset password email to admin.
			SwpmUtils::update_wp_user( $user->user_name, array( 'plain_password' => $password ) );

			$additional_args["password"] = $password;
			SwpmLog::log_simple_debug( 'Member password has been reset.', true );
			$message .= '<div class="swpm-reset-pw-success">' . SwpmUtils::_( 'New password has been sent to your email address.' ) . '</div>';
		}

                //Password reset email header and subject.
		$from = $settings->get_value( 'email-from' );
		$headers = 'From: ' . $from . "\r\n";
                $subject = $settings->get_value( 'reset-mail-subject' );
                
                //Password reset email body
		$body = $settings->get_value( 'reset-mail-body' );
		$body = html_entity_decode( $body );
		//If the tag {password_reset_link} is not present in the email body, add it at the bottom of the email body.
		if( $password_reset_using_link && ( !SwpmMiscUtils::has_email_merge_tag( $body, "{password_reset_link}" ) ) ) {
			$body .= "\n\nPassword Reset Link: {password_reset_link}";
		}
                //Merge tag replacement.
		$body = SwpmMiscUtils::replace_dynamic_tags( $body, $user->member_id, $additional_args );

                //Trigger the filters for password reset email subject and body.
		$subject = apply_filters( 'swpm_email_password_reset_subject', $subject );
		$body = apply_filters( 'swpm_email_password_reset_body', $body );

		SwpmMiscUtils::mail( $email, $subject, $body, $headers );
                SwpmLog::log_simple_debug( 'Member password reset email sent to: ' . $email, true );
		
		$message .= '<div class="swpm-reset-pw-success-email">' . SwpmUtils::_( 'Email Address: ' ) . $email . '</div>';
		$message .= '</div>';

		$message_ary = array(
			'succeeded'       => true,
			'message'         => $message,
			'pass_reset_sent' => true,
		);

		SwpmTransfer::get_instance()->set( 'status', $message_ary );

		// Redirecting to current page to avoid password reset request form resubmission on page reload.
		SwpmMiscUtils::redirect_to_url( SwpmMiscUtils::get_current_page_url() );
	}

	public function reset_password_using_link( $user_data, $password ) {
		$email = $user_data->user_email;
		
		global $wpdb;
		$query = 'SELECT member_id,user_name,first_name, last_name FROM ' .
				$wpdb->prefix . 'swpm_members_tbl ' .
				' WHERE email = %s';

		$user  = $wpdb->get_row( $wpdb->prepare( $query, $email ) );
		if ( empty( $user ) ) {
			$message  = '<div class="swpm-reset-pw-error">' . SwpmUtils::_( 'No user found with that email address.' ) . '</div>';
			$message .= '<div class="swpm-reset-pw-error-email">' . SwpmUtils::_( 'Email Address: ' ) . $email . '</div>';
			$message  = array(
				'succeeded' => false,
				'message'   => $message,
			);
			
			set_transient("swpm-passsword-reset-error",$message);
			SwpmLog::log_simple_debug( 'No member is found with email'.$user_data->user_email, true );
			return false;
		}
	
		$password_hash = SwpmUtils::encrypt_password( trim( $password ) );
		$wpdb->update( $wpdb->prefix . 'swpm_members_tbl', array( 'password' => $password_hash ), array( 'member_id' => $user->member_id ) );

		//Update wp user password
		add_filter( 'send_password_change_email', array( &$this, 'dont_send_password_change_email' ), 1, 3 ); //Stop WordPress from sending a reset password email to admin.
		SwpmUtils::update_wp_user( $user->user_name, array( 'plain_password' => $password ) );

		SwpmLog::log_simple_debug( 'Member password has been reset. Email: ' . $email, true );

		//Trigger action hook
		do_action( 'swpm_front_end_reset_password_using_link_completed', $user_data, $password);

		return true;
	}

	function dont_send_password_change_email( $send = false, $user = '', $userdata = '' ) {
		//Stop the WordPress's default password change email notification to site admin
		//Only the simple membership plugin's password reset email will be sent.
		return false;
	}

	/*
	 * This function is called when the user clicks on the activation link in the email.
	 */
	public function handle_email_activation() {
		//The email activation link contains the member ID and the activation code.

		$login_page_url = SwpmSettings::get_instance()->get_value( 'login-page-url' );

		// Allow hooks to change the value of login_page_url
		$login_page_url = apply_filters('swpm_email_activation_login_page_url', $login_page_url);

		$member_id = FILTER_INPUT( INPUT_GET, 'swpm_member_id', FILTER_SANITIZE_NUMBER_INT );

		$member = SwpmMemberUtils::get_user_by_id( $member_id );
		if ( empty( $member ) ) {
			//can't find member
			echo SwpmUtils::_( "Can't find member account." );
			wp_die();
		}
		if ( $member->account_state !== 'activation_required' ) {
			//account already active
			echo SwpmUtils::_( 'Account already active. ' ) . '<a href="' . $login_page_url . '">' . SwpmUtils::_( 'click here' ) . '</a>' . SwpmUtils::_( ' to log in.' );
			wp_die();
		}

		$code = isset( $_GET['swpm_token'] ) ? sanitize_text_field( stripslashes ( $_GET['swpm_token'] ) ) : '';
		$act_data = get_option( 'swpm_email_activation_data_usr_' . $member_id );
		if ( empty( $code ) || empty( $act_data ) || $act_data['act_code'] !== $code ) {
			//code mismatch
			wp_die( SwpmUtils::_( 'Activation code mismatch. Cannot activate this account. Please contact the site admin.' ) );
		}
		//activation code match
		delete_option( 'swpm_email_activation_data_usr_' . $member_id );
		//store rego form id in constant so FB addon could use it
		if ( ! empty( $act_data['fb_form_id'] ) ) {
			define( 'SWPM_EMAIL_ACTIVATION_FORM_ID', $act_data['fb_form_id'] );
		}
		$activation_account_status = apply_filters( 'swpm_activation_feature_override_account_status', 'active' );
		SwpmMemberUtils::update_account_state( $member_id, $activation_account_status );
		$this->member_info = (array) $member;
		$this->member_info['plain_password'] = SwpmUtils::crypt( $act_data['plain_password'], 'd' );
		$this->send_reg_email();

		//Setup the success message.
		$msg = '<div class="swpm_temporary_msg" style="font-weight: bold;">' . SwpmUtils::_( 'Success! Your account has been activated successfully.' ) . '</div>';

		// Check and retrieve membership level specific after activation redirect URL data.
		$membership_level = SwpmUtils::get_membership_level_row_by_id($member->membership_level);
		$level_custom_data = SwpmMembershipLevelCustom::get_instance_by_id($membership_level->id);
		$after_activation_redirect_page = sanitize_url($level_custom_data->get('after_activation_redirect_page'));

		// Check Whether the membership_level has a dedicated after activation redirect URL.
		if (!empty($after_activation_redirect_page)){
			//There is a dedicated after activation redirect URL for this membership level.
			SwpmLog::log_simple_debug( 'There is a dedicated after email activation redirect URL for this membership level. Setting this as the redirect URL: ' . $after_activation_redirect_page, true );
			$after_email_activation_url = $after_activation_redirect_page;
		}else{
			//No dedicated after activation redirect URL for this membership level.
			//For backwards compatibility - Use the fallback to after registration redirect URL from the settings.
			$after_email_activation_url = SwpmSettings::get_instance()->get_value( 'after-rego-redirect-page-url' );
		}

		//Trigger hooks to allow other plugins to change the after activation redirect URL.
		//Keeping this one for backwards compatibility for now.
		$after_email_activation_url = apply_filters( 'swpm_after_registration_redirect_url', $after_email_activation_url );//TODO - remove later.
		$after_email_activation_url = apply_filters( 'swpm_after_email_activation_redirect_url', $after_email_activation_url );

		if ( ! empty( $after_email_activation_url ) ) {
			//Yes. Need to redirect to this after registration confirmation page.
			SwpmLog::log_simple_debug( 'After email activation redirect is configured. Redirecting the user to: ' . $after_email_activation_url, true );
			SwpmMiscUtils::show_temporary_message_then_redirect( $msg, $after_email_activation_url );
			exit( 0 );
		}

		//No redirection has been configured. show success message and redirect to the standard login page.
		SwpmMiscUtils::show_temporary_message_then_redirect( $msg, $login_page_url );
		exit( 0 );
	}

	public function resend_activation_email() {
		$login_page_url = SwpmSettings::get_instance()->get_value( 'login-page-url' );

		// Allow hooks to change the value of login_page_url
		$login_page_url = apply_filters('swpm_resend_activation_email_login_page_url', $login_page_url);

		$member_id = FILTER_INPUT( INPUT_GET, 'swpm_member_id', FILTER_SANITIZE_NUMBER_INT );

		$member = SwpmMemberUtils::get_user_by_id( $member_id );
		if ( empty( $member ) ) {
			//can't find member
			echo SwpmUtils::_( 'Cannot find member account.' );
			wp_die();
		}
		if ( $member->account_state !== 'activation_required' ) {
			//account already active
			$acc_active_msg = SwpmUtils::_( 'Account already active. ' ) . '<a href="' . $login_page_url . '">' . SwpmUtils::_( 'click here' ) . '</a>' . SwpmUtils::_( ' to log in.' );
			echo $acc_active_msg;
			wp_die();
		}
		$act_data = get_option( 'swpm_email_activation_data_usr_' . $member_id );
		if ( ! empty( $act_data ) ) {
			//looks like activation data has been removed for some reason. We won't be able to have member's plain password in this case
			$act_data['plain_password'] = '';
		}

		delete_option( 'swpm_email_activation_data_usr_' . $member_id );

		$this->member_info = (array) $member;
		$this->member_info['plain_password'] = SwpmUtils::crypt( $act_data['plain_password'], 'd' );
		$this->email_activation = true;
		$this->send_reg_email();

		$msg = '<div class="swpm_temporary_msg" style="font-weight: bold;">' . SwpmUtils::_( 'Activation email has been sent. Please check your email and activate your account.' ) . '</div>';
		SwpmMiscUtils::show_temporary_message_then_redirect( $msg, $login_page_url );
		wp_die();
	}

}
