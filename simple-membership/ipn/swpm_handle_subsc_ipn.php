<?php
/* 
* Note: We are passing the $ipn_data parameter by reference because we try to add the 'member_id' value to it (from our search or when we insert a new record).
* This is helpful to save the member_id reference in save transaction function later. 
*/
function swpm_handle_subsc_signup_stand_alone( &$ipn_data, $subsc_ref, $unique_ref, $swpm_id = '' ) {
	global $wpdb;
	$settings         = SwpmSettings::get_instance();
	$membership_level = $subsc_ref;

	if ( isset( $ipn_data['subscr_id'] ) && ! empty( $ipn_data['subscr_id'] ) ) {
		$subscr_id = $ipn_data['subscr_id'];
	} else {
		$subscr_id = $unique_ref;
	}

	$ipn_data['custom'] = isset( $ipn_data['custom'] ) ? $ipn_data['custom'] : ''; // Make sure the custom field is set. (It should be set by the payment gateway.
	swpm_debug_log_subsc( 'swpm_handle_subsc_signup_stand_alone(). Custom value: ' . $ipn_data['custom'] . ', Unique reference: ' . $unique_ref, true );
	parse_str( $ipn_data['custom'], $custom_vars );

	if ( empty( $swpm_id ) ) {
		// Lets try to find an existing user profile for this payment.
		$email = $ipn_data['payer_email'];
		$query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE email = %s", $email ), OBJECT ); // db call ok; no-cache ok.
		if ( ! $query_db ) { // try to retrieve the member details based on the unique_ref.
			swpm_debug_log_subsc( 'Could not find any record using the given email address (' . $email . '). Attempting to query database using the unique reference: ' . $unique_ref, true );
			if ( ! empty( $unique_ref ) ) {
				$query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE subscr_id = %s", $unique_ref ), OBJECT ); // db call ok; no-cache ok.
				if ( $query_db ) {
					$swpm_id = $query_db->member_id;
					swpm_debug_log_subsc( 'Found a match in the member database using unique reference. Member ID: ' . $swpm_id, true );
				} else {
					swpm_debug_log_subsc( 'Did not find a match for an existing member profile for the given reference. This must be a new payment from a new member.', true );
				}
			} else {
				swpm_debug_log_subsc( 'Unique reference is missing in the notification so we have to assume that this is not a payment for an existing member.', true );
			}
		} else {
			$swpm_id = $query_db->member_id;
			swpm_debug_log_subsc( 'Found a match in the member database. Member ID: ' . $swpm_id, true );
		}
	}

	if ( ! empty( $swpm_id ) ) {
		// This is payment from an existing member/user. Update the existing member account.
		swpm_debug_log_subsc( 'Modifying the existing membership profile... Member ID: ' . $swpm_id, true );

		//Add the member ID value to the $ipn_data (pass by reference will ensure that we will have it available in our save transaction function).
		$ipn_data['member_id'] = $swpm_id;

		// Upgrade the member account.
		$account_state = 'active'; // This is renewal or upgrade of a previously active account. So the status should be set to active.

		$resultset = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = %d", $swpm_id ), OBJECT );
		if ( ! $resultset ) {
			swpm_debug_log_subsc( 'ERROR! Could not find a member account record for the given Member ID: ' . $swpm_id, false );
			return;
		}
		$old_membership_level = $resultset->membership_level;
		$old_account_state = $resultset->account_state;

		// If the payment is for the same/existing membership level, then this is a renewal. Refresh the start date as appropriate.
		$args = array(
			'swpm_id'              => $swpm_id,
			'membership_level'     => $membership_level,
			'old_membership_level' => $old_membership_level,
			'old_account_state'    => $old_account_state
		);
		$subscription_starts = SwpmMemberUtils::calculate_access_start_date_for_account_update( $args );
		$subscription_starts = apply_filters( 'swpm_account_update_subscription_starts', $subscription_starts, $args );
		swpm_debug_log_subsc( 'Setting access starts date value to: ' . $subscription_starts . '. Existing account status value: ' . $old_account_state, true );

		// Check whether it is an account upgrade or renew event.
		$level_update_type = SwpmMemberUtils::get_membership_level_update_type($args);
		if ( $level_update_type == 'upgrade') {
			// This is an account upgrade event.
			swpm_debug_log_subsc( 'Updating the current membership level (' . $old_membership_level . ') of this member to the newly paid level (' . $membership_level . ')', true );
		} else {
			// This is an account renew event.
			swpm_debug_log_subsc( 'Renewing the existing level of the member. Membership level: ' . $old_membership_level, true );
		}

		// Set account status to active, update level to the newly paid level, update access start date, update subsriber ID (if applicable).
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}swpm_members_tbl SET account_state=%s, membership_level=%d,subscription_starts=%s,subscr_id=%s WHERE member_id=%d",
				$account_state,
				$membership_level,
				$subscription_starts,
				$subscr_id,
				$swpm_id
			)
		);

		// Trigger level changed/updated action hook.
		do_action(
			'swpm_membership_level_changed',
			array(
				'member_id'  => $swpm_id,
				'from_level' => $old_membership_level,
				'to_level'   => $membership_level,
			)
		);

		//Trigger the account status refreshed action hook.
		do_action(
			'swpm_account_status_refreshed',
			array(
				'member_id' => $swpm_id,
				'from'      => $old_account_state,
				'to'        => $account_state,
			)
		);


		$email   = $ipn_data['payer_email'];
		$from_address = $settings->get_value( 'email-from' );
		$headers         = 'From: ' . $from_address . "\r\n";
		$additional_args = array();

		if ($level_update_type == 'upgrade'){
			if ( $settings->get_value( 'disable-email-after-upgrade' ) ) {
				swpm_debug_log_subsc( 'The disable upgrade email settings is checked. No account upgrade/update email will be sent.', true );
				//Nothing to do.
			} else {
				// Set Email details for the account upgrade notification.
				$subject = $settings->get_value( 'upgrade-complete-mail-subject' );
				if ( empty( $subject ) ) {
					$subject = 'Member Account Upgraded';
				}
				$body = $settings->get_value( 'upgrade-complete-mail-body' );
				if ( empty( $body ) ) {
					$body = 'Your account has been upgraded successfully';
				}

				$email_body = SwpmMiscUtils::replace_dynamic_tags( $body, $swpm_id, $additional_args );

				$subject    = apply_filters( 'swpm_email_upgrade_complete_subject', $subject );
				$email_body = apply_filters( 'swpm_email_upgrade_complete_body', $email_body );

				SwpmMiscUtils::mail( $email, $subject, $email_body, $headers );
				swpm_debug_log_subsc( 'Member upgrade/update completion email successfully sent to: ' . $email, true );
			}
		} else {
			// It is an account renew event.
			if ( $settings->get_value( 'disable-email-after-renew' ) ) {
				swpm_debug_log_subsc( 'The disable renew email settings is checked. No account renewal email will be sent.', true );
				//Nothing to do.
			} else {
				// Set Email details for the account renew notification.
				$subject = $settings->get_value( 'renew-complete-mail-subject' );
				if ( empty( $subject ) ) {
					$subject = 'Member Account Renewed';
				}
				$body = $settings->get_value( 'renew-complete-mail-body' );
				if ( empty( $body ) ) {
					$body = 'Your account has been renewed successfully';
				}

				$email_body = SwpmMiscUtils::replace_dynamic_tags( $body, $swpm_id, $additional_args );

				$subject    = apply_filters( 'swpm_email_renew_complete_subject', $subject );
				$email_body = apply_filters( 'swpm_email_renew_complete_body', $email_body );

				SwpmMiscUtils::mail( $email, $subject, $email_body, $headers );
				swpm_debug_log_subsc( 'Member account renewal completion email successfully sent to: ' . $email, true );
			}
		}

		// End of existing user account upgrade/update.
	} else {
		// create new member account.
		$default_account_status = $settings->get_value( 'default-account-status-after-payment', 'active' );

		$data = array();
		$data['user_name'] = '';
		$data['password']  = '';

		$data['first_name']       = $ipn_data['first_name'];
		$data['last_name']        = $ipn_data['last_name'];
		$data['email']            = $ipn_data['payer_email'];
		$data['membership_level'] = $membership_level;
		$data['subscr_id']        = $subscr_id;

		$data['gender']                = 'not specified';
		$data['address_street']        = $ipn_data['address_street'];
		$data['address_city']          = $ipn_data['address_city'];
		$data['address_state']         = $ipn_data['address_state'];
		$data['address_zipcode']       = isset( $ipn_data['address_zip'] ) ? $ipn_data['address_zip'] : '';
		$data['country']               = isset( $ipn_data['address_country'] ) ? $ipn_data['address_country'] : '';
		$data['member_since']          = $data['subscription_starts'] = $data['last_accessed'] = SwpmUtils::get_current_date_in_wp_zone();
		$data['account_state']         = $default_account_status;
		$reg_code                      = uniqid();
		$md5_code                      = md5( $reg_code );
		$data['reg_code']              = $md5_code;
		$data['referrer']              = $data['txn_id'] = '';
		$data['last_accessed_from_ip'] = isset( $custom_vars['user_ip'] ) ? $custom_vars['user_ip'] : ''; // Save the users IP address.

		swpm_debug_log_subsc( 'Creating new member account. Membership level ID: ' . $membership_level . ', Subscriber ID value: ' . $data['subscr_id'], true );

		$data = array_filter( $data ); // Remove any null values.
		$wpdb->insert( "{$wpdb->prefix}swpm_members_tbl", $data ); // Create the member record.
		$member_id = $wpdb->insert_id;
		if ( empty( $member_id ) ) {
			swpm_debug_log_subsc( 'Error! Failed to insert a new member record to the database. This request will fail.', false );
			return;
		}

		//Add the member ID value to the $ipn_data (pass by reference will ensure that we will have it available in our save transaction function).
		$ipn_data['member_id'] = $member_id;

		//Create the signup/registration complete URL for the paid membership.
		$rego_page_url = $settings->get_value( 'registration-page-url' );
		$reg_url = add_query_arg(
			array(
				'member_id' => $member_id,
				'code' => $md5_code,
			),
			$rego_page_url
		);		
		swpm_debug_log_subsc( 'Member signup URL: ' . $reg_url, true );

		$subject = $settings->get_value( 'reg-prompt-complete-mail-subject' );
		if ( empty( $subject ) ) {
			$subject = 'Please complete your registration';
		}
		$body = $settings->get_value( 'reg-prompt-complete-mail-body' );
		if ( empty( $body ) ) {
			$body = "Please use the following link to complete your registration. \n {reg_link}";
		}
		$from_address = $settings->get_value( 'email-from' );
		$body = html_entity_decode( $body );

		$additional_args = array( 'reg_link' => $reg_url );
		$email_body = SwpmMiscUtils::replace_dynamic_tags( $body, $member_id, $additional_args );
		$headers = 'From: ' . $from_address . "\r\n";

		//Trigger filter hooks for prompt to complete email subject and body.
		$subject = apply_filters( 'swpm_email_complete_registration_subject', $subject );
		$email_body = apply_filters( 'swpm_email_complete_registration_body', $email_body );//Old filter hook for backward compatibility.
		$email_body = apply_filters( 'swpm_email_prompt_to_complete_registration_body', $email_body );//The new filter hook.

		if ( empty( $email_body ) ) {
			swpm_debug_log_subsc( 'Notice: Member signup (prompt to complete registration) email body has been set empty via the filter hook. No email will be sent.', true );
		} else {
			SwpmMiscUtils::mail( $email, $subject, $email_body, $headers );
			swpm_debug_log_subsc( 'Member signup (prompt to complete registration) email successfully sent to: ' . $email, true );
		}
	}

}

/*
 * This function will handle the refund notification from PayPal as long as the parent transction ID is present.
 * It will deactivate the corresponding member account.
 * It will also mark the transaction as "Refunded" in the transactions list.
 */
function swpm_handle_refund_using_parent_txn_id( $ipn_data ){
	swpm_debug_log_subsc( 'Refund notification - lets see if a member account needs to be deactivated.', true );

	//Find the transaction record that matches the parent transaction ID.
	$parent_txn_id = isset($ipn_data['parent_txn_id']) ? $ipn_data['parent_txn_id'] : '';
	if(empty($parent_txn_id)){
		swpm_debug_log_subsc("Parent txn id field is empty. cannot process this request.", true);
		return;
	}
	$txn_db_row = SwpmTransactions::get_transaction_row_by_txn_id( $parent_txn_id, true);
	if( ! $txn_db_row ){
		swpm_debug_log_subsc("No transaction record found for the transaction id: " . $parent_txn_id, true);
		return;
	}
	//Mark the transaction as refunded.
	$txn_row_id = $txn_db_row->id;
	// Update the postmeta for the corresponding transaction cpt.
	SwpmTransactions::update_transaction_status( $txn_row_id, 'Refunded' );

	//Get the member's ID associated with this transaction
	$member_id = isset($txn_db_row->member_id) ? $txn_db_row->member_id : '';
	if( empty ( $member_id )){
		//Lets try to retrieve the member ID using the subscr_id field.
		$subscr_id = $txn_db_row->subscr_id;//The member account can be pulled up from this subscr_id value.
		if(empty($subscr_id)){
			//If subscr_id value is empty, try using the transaction id as the subscr_id value.
			$subscr_id = $parent_txn_id;
		}
		$member_db_row = SwpmMemberUtils::get_user_by_subsriber_id( $subscr_id );
		$member_id = isset($member_db_row->member_id) ? $member_db_row->member_id : '';
	}

	if( empty ( $member_id )){
		swpm_debug_log_subsc("No associated member account found for the transaction id: " . $parent_txn_id. ". The member account may have been deleted.", true);
		return;
	}

	//Deactivate the member account.
	SwpmMemberUtils::update_account_state( $member_id, 'inactive' ); //Set the account status to inactive.
	swpm_debug_log_subsc( 'Member account deactivated.', true );
}

/*
* All in one function that can handle notification for refund, cancellation, end of term
*/
function swpm_handle_subsc_cancel_stand_alone( $ipn_data, $refund = false ) {

	swpm_debug_log_subsc( "Refund/Cancellation Check - Let's see if a member's profile needs to be updated or deactivated.", true );
	global $wpdb;

	$swpm_id = '';
	if ( isset( $ipn_data['custom'] ) && !empty( $ipn_data['custom'] ) ){
		$customvariables = SwpmTransactions::parse_custom_var( $ipn_data['custom'] );
		$swpm_id = $customvariables['swpm_id'];
	}

    $subscr_id = isset( $ipn_data['subscr_id'] ) && ! empty( $ipn_data['subscr_id'] ) ? $ipn_data['subscr_id'] : '';

	if ( ! empty( $swpm_id ) ) {
		// This IPN has the SWPM ID. Retrieve the member record using member ID.
		swpm_debug_log_subsc( 'Member ID is present. Retrieving member account from the database. Member ID: ' . $swpm_id, true );
		$resultset = SwpmMemberUtils::get_user_by_id( $swpm_id );
	} else if ( ! empty( $subscr_id ) ) {
		// This IPN has the subscriber ID. Retrieve the member record using subscr_id.
		swpm_debug_log_subsc( 'Subscriber ID is present. Retrieving member account from the database. Subscr_id: ' . $subscr_id, true );
		$resultset = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}swpm_members_tbl where subscr_id LIKE %s",
				'%' . $wpdb->esc_like( $subscr_id ) . '%'
			),
			OBJECT
		);
	} else if ( isset($ipn_data['parent_txn_id']) && !empty($ipn_data['parent_txn_id'] )){
		// Refund for a one time transaction. Use the parent transaction ID to retrieve the profile.
		swpm_debug_log_subsc( 'Parent transaction ID is present. Goign to search for member account that might be associated with it. Parent Transaction ID: ' . $ipn_data['parent_txn_id'], true );
		$subscr_id = $ipn_data['parent_txn_id'];
		$resultset = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}swpm_members_tbl where subscr_id LIKE %s",
				'%' . $wpdb->esc_like( $subscr_id ) . '%'
			),
			OBJECT
		);
	} else {
		// No member ID or subscriber ID or parent transaction ID found in the IPN data. Return from here.
		swpm_debug_log_subsc( 'No member ID or subscriber ID or parent transaction ID found in the IPN data. Nothing to do here.', true );
		return;
	}

	if ( $resultset ) {
		// We have found a member profile for this notification.

		$member_id = $resultset->member_id;

		// First, check if this is a refund notification.
		if ( $refund ) {
			// This is a refund (not just a subscription cancellation or end). So deactivate the account regardless and bail.
			SwpmMemberUtils::update_account_state( $member_id, 'inactive' ); // Set the account status to inactive.
			swpm_debug_log_subsc( 'Subscription refund notification received! Member account deactivated.', true );
			return;
		}

		// This is a cancellation or end of subscription term (no refund).
		// Lets retrieve the membership level and details.
		$level_id = $resultset->membership_level;
		swpm_debug_log_subsc( 'Membership level ID of the member is: ' . $level_id, true );
		$level_row          = SwpmUtils::get_membership_level_row_by_id( $level_id );
		$subs_duration_type = $level_row->subscription_duration_type;

		swpm_debug_log_subsc( 'Subscription duration type: ' . $subs_duration_type, true );

		if ( SwpmMembershipLevel::NO_EXPIRY == $subs_duration_type ) {
			// This is a level with "no expiry" or "until cancelled" duration.
			swpm_debug_log_subsc( 'This is a level with "no expiry" or "until cancelled" duration', true );

			// Deactivate this account as the membership level is "no expiry" or "until cancelled".
			$account_state = 'inactive';
			SwpmMemberUtils::update_account_state( $member_id, $account_state );
			swpm_debug_log_subsc( 'Subscription cancellation or end of term received! Member account deactivated. Member ID: ' . $member_id, true );
		} elseif ( SwpmMembershipLevel::FIXED_DATE == $subs_duration_type ) {
			// This is a level with a "fixed expiry date" duration.
			swpm_debug_log_subsc( 'This is a level with a "fixed expiry date" duration.', true );
			swpm_debug_log_subsc( 'Nothing to do here. The account will expire on the fixed set date.', true );
		} else {
			// This is a level with "duration" type expiry (example: 30 days, 1 year etc). subscription_period has the duration/period.
			$subs_period      = $level_row->subscription_period;
			$subs_period_unit = SwpmMembershipLevel::get_level_duration_type_string( $level_row->subscription_duration_type );

			swpm_debug_log_subsc( 'This is a level with "duration" type expiry. Duration period: ' . $subs_period . ', Unit: ' . $subs_period_unit, true );
			swpm_debug_log_subsc( 'Nothing to do here. The account will expire after the duration time is over.', true );

			// TODO Later as an improvement. If you wanted to segment the members who have unsubscribed, you can set the account status to "unsubscribed" here.
			// Make sure the cronjob to do expiry check and deactivate the member accounts treat this status as if it is "active".
		}

		//Check if subscription_id is still empty (due to it not being present in the $ipn_data array), then try to get it from the member record.
		if( empty( $subscr_id ) ){
			$subscr_id = isset($resultset->subscr_id) ? $resultset->subscr_id : '';
		}

		//Update the swpm_transactions CPT record to mark the subscription as "Cancelled".
		$swpm_txn_cpt_id = SwpmTransactions::get_original_swpm_txn_cpt_id_by_subscr_id( $subscr_id );
		if ( ! empty( $swpm_txn_cpt_id ) ) {
			swpm_debug_log_subsc( 'Updating the the swpm_transaction CPT record (Post ID: '.$swpm_txn_cpt_id.') to mark the subscription as "cancelled".', true );
			update_post_meta( $swpm_txn_cpt_id, 'subscr_status', 'cancelled' );
		}

		// Update attached subcription status.
		SwpmMemberUtils::set_subscription_data_extra_info( $member_id, 'subscription_status', 'inactive');

        // Update subscription status of the subscription agreement record in transactions cpt table.
        SWPM_Utils_Subscriptions::update_subscription_agreement_record_status_to_cancelled($subscr_id);

		// Send subscription cancel notification emails
		swpm_send_subscription_cancel_notification_email($member_id, $subscr_id);

		// Trigger hook for subscription payment cancelled.
		$ipn_data['member_id'] = $member_id;
		do_action( 'swpm_subscription_payment_cancelled', $ipn_data );

	} else {
		swpm_debug_log_subsc( 'No associated active member record found for this notification. The profile may have been updated, deleted or attached to another subscription or transaction. Nothing to do.', true );
		return;
	}
}

/**
 * Sends notification email to member and admin.
 */
function swpm_send_subscription_cancel_notification_email($member_id, $subscription_id){
	$settings = SwpmSettings::get_instance();

	$member = SwpmMemberUtils::get_user_by_id( $member_id );
	if ( empty( $member ) ) {
		//can't find recipient member
		wp_die(__( "Can't find member account to send notification email." , 'simple-membership'));
	}

	//Additional arguments for dynamic tag replacement
	$additional_args = array('subscription_id' => $subscription_id);

	// Set Email details for the notification.
	$from_address = $settings->get_value( 'email-from' );
	$headers = array();
	$headers[] = 'From: ' . $from_address . "\r\n";

	$is_html_email_enabled = $settings->get_value('email-enable-html', false);

	// Send email to Member
	$member_email_notification_enable = $settings->get_value('subscription-cancel-member-mail-enable', false);
	if(!empty($member_email_notification_enable)){
		$member_email_address = $member->email;
		$member_email_subject = $settings->get_value( 'subscription-cancel-member-mail-subject' );
		$member_email_body = $settings->get_value( 'subscription-cancel-member-mail-body' );

		$member_email_body = SwpmMiscUtils::replace_dynamic_tags($member_email_body, $member->member_id, $additional_args);
		if (!empty($is_html_email_enabled)){
			$headers[] = "Content-Type: text/html; charset=UTF-8\r\n";
			$member_email_body = nl2br($member_email_body);
		}

		wp_mail($member_email_address, $member_email_subject , $member_email_body, $headers);
		SwpmLog::log_simple_debug( 'Sending subscription payment canceled or expired notification email to: '.$member_email_address, true );
	}

	// Send email to Admin
	$admin_email_notification_enable = $settings->get_value('subscription-cancel-admin-mail-enable', false);
	if(!empty($admin_email_notification_enable)){
		$admin_email_address = $settings->get_value( 'subscription-cancel-admin-mail-address' );

		// The default email subject and body for admin
		$admin_email_subject = "A subscription payment agreement has been canceled or expired";
		$admin_email_body = "Dear Admin" .
                "\n\nA subscription payment agreement has been canceled or has expired." .
				"\n\nSubscription ID: {subscription_id}" .
                "\n\nMember ID: {member_id}" .
                "\n\nYou can view more details in the Payments menu of the plugin.";

		$admin_email_body = SwpmMiscUtils::replace_dynamic_tags($admin_email_body, $member->member_id, $additional_args);
		if (!empty($is_html_email_enabled)){
			$headers[] = "Content-Type: text/html; charset=UTF-8\r\n";
			$admin_email_body = nl2br($admin_email_body);
		}

		wp_mail($admin_email_address, $admin_email_subject , $admin_email_body, $headers);
		SwpmLog::log_simple_debug( 'Sending subscription payment canceled or expired notification email to: '.$admin_email_address, true );
	}
}

function swpm_update_member_subscription_start_date_if_applicable( $ipn_data ) {
	global $wpdb;
	$email = isset( $ipn_data['payer_email'] ) ? $ipn_data['payer_email'] : '';
	$subscr_id = isset( $ipn_data['subscr_id'] ) ? $ipn_data['subscr_id'] : '';
	$account_state = SwpmSettings::get_instance()->get_value( 'default-account-status-after-payment', 'active' );
    $account_state = apply_filters( 'swpm_account_status_for_subscription_start_date_update', $account_state );

	if( empty( $subscr_id ) ) {
		swpm_debug_log_subsc( 'Subscription ID is empty in the IPN data. A Subscription ID value is required to update the access start date of a profile.', false );
		return;
	}
	swpm_debug_log_subsc( 'Updating the access start date if applicable for this subscription payment. Subscriber ID: ' . $subscr_id . ', Email: ' . $email . ', Account status: ' . $account_state, true );

	// We can also query using the email address or SWPM ID (if present in custom var).

    //Try to find the profile with the given subscr_id. It will exact match subscr_id or match subscr_id|123
    $query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE subscr_id = %s OR subscr_id LIKE %s", $subscr_id, $subscr_id.'|%' ), OBJECT );
	if ( $query_db ) {
		$swpm_id               = $query_db->member_id;
		$current_primary_level = $query_db->membership_level;
		swpm_debug_log_subsc( 'Found a record in the member table. The Member ID of the account to check is: ' . $swpm_id . ' Membership Level: ' . $current_primary_level, true );

		$ipn_data['member_id'] = $swpm_id;
		do_action( 'swpm_recurring_payment_received', $ipn_data ); // Hook for recurring payment received.

		$subscription_starts = SwpmUtils::get_current_date_in_wp_zone();

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}swpm_members_tbl SET account_state=%s,subscription_starts=%s WHERE member_id=%d",
				$account_state,
				$subscription_starts,
				$swpm_id
			)
		);
		swpm_debug_log_subsc( 'Updated the member profile with current date as the subscription start date.', true );
		// Lets check to see if the subscriber ID and the subscription start date value was updated correctly.
		$member_record = SwpmMemberUtils::get_user_by_id( $swpm_id );
		swpm_debug_log_subsc( 'Value after update - Subscriber ID: ' . $member_record->subscr_id . ', Start Date: ' . $member_record->subscription_starts, true );
	} else {
		swpm_debug_log_subsc( 'Did not find an existing record in the members table for subscriber ID: ' . $subscr_id, true );
		swpm_debug_log_subsc( 'This could be a new subscription payment for a new subscription agreement.', true );
	}
}

function swpm_is_paypal_recurring_payment($payment_data){
    $recurring_payment = false;
    $transaction_type = $payment_data['txn_type'];

    if ($transaction_type == "recurring_payment") {
        $recurring_payment = true;

    } else if ($transaction_type == "subscr_payment") {
        $item_number = $payment_data['item_number'];
        $subscr_id = $payment_data['subscr_id'];
        swpm_debug_log_subsc('Is recurring payment check debug data: ' . $item_number . "|" . $subscr_id, true);

        $result = SwpmTransactions::get_transaction_row_by_subscr_id($subscr_id, true);
        if (isset($result) && strtolower($result->status) != 'subscription created') {
            swpm_debug_log_subsc('This subscr_id exists in the transactions db. Recurring payment check flag value is true.', true);
            $recurring_payment = true;
            return $recurring_payment;
        }
    }
    if ($recurring_payment) {
        swpm_debug_log_subsc('Recurring payment check flag value is true.', true);
    }
    return $recurring_payment;
}

function swpm_debug_log_subsc( $message, $success, $end = false ) {
	SwpmLog::log_simple_debug( $message, $success, $end);
}
