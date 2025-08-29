<?php

/**
 * Description of BAdminRegistration
 *
 */
class SwpmAdminRegistration extends SwpmRegistration {

	public static function get_instance() {
		self::$_intance = empty( self::$_intance ) ? new SwpmAdminRegistration() : self::$_intance;
		return self::$_intance;
	}

	public function show_form() {

	}

	public function register_admin_end() {
		//Check we are on the admin end and user has management permission
		SwpmMiscUtils::check_user_permission_and_is_admin( 'member creation by admin' );

		//Check nonce
		if ( ! isset( $_POST['_wpnonce_create_swpmuser_admin_end'] ) || ! wp_verify_nonce( $_POST['_wpnonce_create_swpmuser_admin_end'], 'create_swpmuser_admin_end' ) ) {
			//Nonce check failed.
			wp_die( __( 'Error! Nonce verification failed for user registration from admin end.', 'simple-membership' ) );
		}

		global $wpdb;
		$member = SwpmTransfer::$default_fields;
		$form   = new SwpmForm( $member );
		if ( $form->is_valid() ) {
			$member_info = $form->get_sanitized_member_form_data();

                        //First, check if email or username belongs to an existing admin user. Bail if it does.
                        SwpmMemberUtils::check_and_die_if_email_belongs_to_admin_user($member_info['email']);
                        SwpmMemberUtils::check_and_die_if_username_belongs_to_admin_user($member_info['user_name']);

			$account_status = SwpmSettings::get_instance()->get_value( 'default-account-status', 'active' );
			$member_info['account_state'] = $account_status;
			$plain_password = $member_info['plain_password'];
			unset( $member_info['plain_password'] );
                        //Create SWPM member entry
			$wpdb->insert( $wpdb->prefix . 'swpm_members_tbl', $member_info );

			//Register to WordPress
			$query = $wpdb->prepare( 'SELECT role FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE id = %d', $member_info['membership_level'] );
			$wp_user_info = array();
			$wp_user_info['user_nicename'] = implode( '-', explode( ' ', $member_info['user_name'] ) );
			$wp_user_info['display_name']  = apply_filters( 'swpm_admin_end_registration_display_name', $member_info['user_name'] );
			$wp_user_info['user_email']    = $member_info['email'];
			$wp_user_info['nickname']      = $member_info['user_name'];
			if ( isset( $member_info['first_name'] ) ) {
				$wp_user_info['first_name'] = $member_info['first_name'];
			}
			if ( isset( $member_info['last_name'] ) ) {
				$wp_user_info['last_name'] = $member_info['last_name'];
			}
			$wp_user_info['user_login']      = $member_info['user_name'];
			$wp_user_info['password']        = $plain_password;
			$wp_user_info['role']            = $wpdb->get_var( $query );
			$wp_user_info['user_registered'] = date( 'Y-m-d H:i:s' );
			SwpmUtils::create_wp_user( $wp_user_info );
			//End register to WordPress

			//Send notification
			$send_notification             = SwpmSettings::get_instance()->get_value( 'enable-notification-after-manual-user-add' );
			$member_info['plain_password'] = $plain_password;
			$this->member_info             = $member_info;
			if ( ! empty( $send_notification ) ) {
				$this->send_reg_email();
			}

			//Trigger action hook
			do_action( 'swpm_admin_end_registration_complete_user_data', $member_info );

			//Save the success message
			$message = array(
				'succeeded' => true,
				'message' => __( 'Member record added successfully.', 'simple-membership' ),
			);
			SwpmTransfer::get_instance()->set( 'status', $message );
			wp_redirect( 'admin.php?page=simple_wp_membership' );
			exit( 0 );
		}
		$message = array(
			'succeeded' => false,
			'message'   => __( 'Please correct the following:', 'simple-membership' ),
			'extra'     => $form->get_errors(),
		);
		SwpmTransfer::get_instance()->set( 'status', $message );
	}

    /**
     * Edit member profile handler of admin side.
     *
     * @param $id Member's ID (member_id) in 'swpm_members_tbl' table.
     *
     * @return void
     */
	public function edit_admin_end( $id ) {
		//Check we are on the admin end and user has management permission
		SwpmMiscUtils::check_user_permission_and_is_admin( 'member edit by admin' );

		//Check nonce
		if ( ! isset( $_POST['_wpnonce_edit_swpmuser_admin_end'] ) || ! wp_verify_nonce( $_POST['_wpnonce_edit_swpmuser_admin_end'], 'edit_swpmuser_admin_end' ) ) {
			//Nonce check failed.
			wp_die( __( 'Error! Nonce verification failed for user edit from admin end.', 'simple-membership' ) );
		}

		$id_of_profile_being_edited = intval( $id );
		global $wpdb;
		$query  = $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE member_id = %d', $id );
		$member = $wpdb->get_row( $query, ARRAY_A );
		// let's get previous membership level
		$prev_level = false;
		$prev_account_status = false;
		if ( $member ) {
			$prev_level = $member['membership_level'];
			$prev_account_status = $member['account_state'];
		}
		$email_address = $member['email'];
		$user_name = $member['user_name'];
		unset( $member['member_id'] );
		unset( $member['user_name'] );
		$form = new SwpmForm( $member );
		if ( $form->is_valid() ) {
			$member         = $form->get_sanitized_member_form_data();
			$plain_password = isset( $member['plain_password'] ) ? $member['plain_password'] : '';

            // Important: Get the currently logged in member's ID before calling the update_wp_user() function (since this function can invalidate the auth cookie if password is updated).
            $currently_logged_in_member_id = SwpmMemberUtils::get_logged_in_members_id();

            SwpmUtils::update_wp_user( $user_name, $member );
			unset( $member['plain_password'] );
			$wpdb->update( $wpdb->prefix . 'swpm_members_tbl', $member, array( 'member_id' => $id ) );

            // Check if the password has been updated and the profile being edited is the logged-in user's own profile.
			// If so, then we need to reset/update the auth cookies to keep the user logged in.
            if( !empty($plain_password) && ($currently_logged_in_member_id == $id_of_profile_being_edited) ){
				//The password has been updated and the profile being edited is the logged-in user's own profile.
				$auth_object = SwpmAuth::get_instance();
				$user_info_params = array(
					'member_id' => $id_of_profile_being_edited,
					'user_name' => $user_name,
					'new_enc_password' => $member['password'],
				);
				$auth_object->reset_auth_cookies_after_pass_change($user_info_params);
                SwpmLog::log_auth_debug( 'Profile edit from admin dashboard - The authentication cookies have been reset since the password was changed by the user (member_id: '. $id . ').', true );
            }

			$member['member_id'] = $id;//Add member ID to the member array.
			$member['prev_membership_level'] = $prev_level;//Add previous membership level to the member array.
			$member['prev_account_status'] = $prev_account_status;//Add previous account status to the member array.
			
			 //Trigger action hook
			do_action( 'swpm_admin_end_edit_complete_user_data', $member );

			//Trigger membership level change action hook
			if ( $member['prev_membership_level'] != $member['membership_level'] ) {
				do_action(
					'swpm_membership_level_changed',
					array(
						'member_id'  => $id,
						'from_level' => $member['prev_membership_level'],
						'to_level'   => $member['membership_level'],
					)
				);
			}

			//Trigger account status updated action hook
			if ( $member['prev_account_status'] != $member['account_state'] ) {
				//We will trigger two hooks for admin account status update so it can be targeted by other plugins.
				$hooks = array(
					'swpm_account_status_updated',
					'swpm_admin_account_status_updated',
				);
				
				foreach ($hooks as $hook) {
					do_action(
						$hook,
						array(
							'member_id'  => $id,
							'from_status' => $member['prev_account_status'],
							'to_status'   => $member['account_state'],
						)
					);
				}
			}

			//Set messages
			$message = array(
				'succeeded' => true,
				'message' => __( 'Member profile updated successfully.', 'simple-membership' ),
			);
			$error   = apply_filters( 'swpm_admin_edit_custom_fields', array(), $member + array( 'member_id' => $id ) );
			if ( ! empty( $error ) ) {
				$message = array(
					'succeeded' => false,
					'message'   => __( 'Please correct the following:', 'simple-membership' ),
					'extra'     => $error,
				);
				SwpmTransfer::get_instance()->set( 'status', $message );
				return;
			}
			SwpmTransfer::get_instance()->set( 'status', $message );
			$send_notification = filter_input( INPUT_POST, 'account_status_change' );
			if ( ! empty( $send_notification ) ) {
				$settings     = SwpmSettings::get_instance();
				$from_address = $settings->get_value( 'email-from' );
				$headers      = 'From: ' . $from_address . "\r\n";
				$subject      = filter_input( INPUT_POST, 'notificationmailhead' );
				$body         = filter_input( INPUT_POST, 'notificationmailbody' );
				$settings->set_value( 'account-change-email-body', $body )->set_value( 'account-change-email-subject', $subject )->save();
				$member['login_link'] = $settings->get_value( 'login-page-url' );
				$member['user_name'] = $user_name;
				$member['password'] = empty( $plain_password ) ? SwpmUtils::_( 'Your current password' ) : $plain_password;
				$values = array_values( $member );
				$keys = array_map( 'swpm_enclose_var', array_keys( $member ) );
				$body = html_entity_decode( str_replace( $keys, $values, $body ) );

                                //Do the standard email merge tag replacement.
                                $body = SwpmMiscUtils::replace_dynamic_tags( $body, $id );

                                //Trigger the filter hooks
				$subject = apply_filters( 'swpm_email_account_status_change_subject', $subject );
				$body = apply_filters( 'swpm_email_account_status_change_body', $body );

                                //Send the email
				SwpmMiscUtils::mail( $email_address, $subject, $body, $headers );
				SwpmLog::log_simple_debug( 'Notify email sent (after profile edit from admin side). Email sent to: ' . $email_address, true );
			}

			wp_redirect( 'admin.php?page=simple_wp_membership' );
			exit( 0 );
		}
		$message = array(
			'succeeded' => false,
			'message'   => SwpmUtils::_( 'Please correct the following:' ),
			'extra'     => $form->get_errors(),
		);
		SwpmTransfer::get_instance()->set( 'status', $message );
	}

	public function handle_manual_approval( $member_id ) {
		//Check we are on the admin end and user has management permission
		SwpmMiscUtils::check_user_permission_and_is_admin( 'member edit by admin' );

		//Check nonce
		if ( ! isset( $_POST['swpm_admin_member_account_approve_nonce'] ) || ! wp_verify_nonce( $_POST['swpm_admin_member_account_approve_nonce'], 'swpm_admin_member_account_approve' ) ) {
			//Nonce check failed.
			wp_die( __( 'Error! Nonce verification failed for manual account approval.', 'simple-membership' ) );
		}

		SwpmLog::log_simple_debug('Handling manual account approval for member ID: ' . $member_id, true);

		//Set the account status to 'active'
		SwpmMemberUtils::update_account_state( $member_id, 'active' );

		//Lets check if manual account approval notification is enabled.
		$settings = SwpmSettings::get_instance();
		$member_email_address = isset( $_POST['member_email'] ) ? sanitize_email( $_POST['member_email'] ) : '';
		$is_manual_account_approval_notification_enabled = $settings->get_value( 'manual-account-approve-member-mail-enable' );
		if ( !empty($is_manual_account_approval_notification_enabled) && is_email($member_email_address)){
			//Manual approval notification is enabled.
			$from_address = $settings->get_value( 'email-from' );
			$manual_approve_email_headers = 'From: ' . $from_address . "\r\n";

			$manual_approve_email_subject = $settings->get_value( 'manual-account-approve-member-mail-subject' );
			$manual_approve_email_body = $settings->get_value( 'manual-account-approve-member-mail-body' );

			//Do the standard email merge tag replacement.
			$manual_approve_email_body = SwpmMiscUtils::replace_dynamic_tags( $manual_approve_email_body, $member_id );

			//Trigger the filter hooks
			$manual_approve_email_subject = apply_filters( 'swpm_manual_account_approval_notification_subject', $manual_approve_email_subject );
			$manual_approve_email_body = apply_filters( 'swpm_manual_account_approval_notification_body', $manual_approve_email_body );

			//Send the email
			SwpmMiscUtils::mail( $member_email_address, $manual_approve_email_subject, $manual_approve_email_body, $manual_approve_email_headers );
			SwpmLog::log_simple_debug( 'Notification email sent (after manual approval of member account from admin side). Email sent to: ' . $member_email_address, true );
		}

		//Set the manual approval success messages
		$message = array(
			'succeeded' => true,
			'message'   => __('Account successfully approved.', 'simple-membership'),
		);
		SwpmTransfer::get_instance()->set( 'status', $message );

		wp_redirect( 'admin.php?page=simple_wp_membership' );
		exit( 0 );
	}

}
