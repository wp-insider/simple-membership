<?php

function swpm_handle_subsc_signup_stand_alone($ipn_data,$subsc_ref,$unique_ref,$swpm_id='')
{
    global $wpdb, $emember_config;
    $emember_config = Emember_Config::getInstance();    
    $members_table_name = $wpdb->prefix . "wp_eMember_members_tbl";

    if(empty($swpm_id))
    {
	    $email = $ipn_data['payer_email'];
	    $query_db = $wpdb->get_row("SELECT * FROM $members_table_name WHERE email = '$email'", OBJECT);	    
	    if(!$query_db){//try to retrieve the member details based on the unique_ref
		eMember_debug_log_subsc("Could not find any record using the given email address (".$email."). Attempting to query database using the unique reference: ".$unique_ref,true);
	    	if(!empty($unique_ref)){			
	    		$query_db = $wpdb->get_row("SELECT * FROM $members_table_name WHERE subscr_id = '$unique_ref'", OBJECT);
	    		$swpm_id = $query_db->member_id;
	    	}
	    	else{
	    		eMember_debug_log_subsc("Unique reference is missing in the notification so we have to assume that this is not a payment for an existing member.",true);
	    	}
	    }
	    else
	    {
	    	$swpm_id = $query_db->member_id;
	    	eMember_debug_log_subsc("Found a match in the member database. Member ID: ".$swpm_id,true);
	    }
    }
    
	if (!empty($swpm_id))//Update the existing member account
	{
		eMember_debug_log_subsc("Modifying the existing membership profile... Member ID: ".$swpm_id,true);
		// upgrade the member account
		$account_state = 'active';
		$membership_level = $subsc_ref;
		$subscription_starts = (date ("Y-m-d"));
		$subscr_id = $unique_ref;
		
		$resultset = "";
		$resultset = $wpdb->get_row("SELECT * FROM $members_table_name where member_id='$swpm_id'", OBJECT);
		if(!$resultset){
			eMember_debug_log_subsc("ERROR! Could not find a member account record for the given eMember ID: ".$swpm_id,false);
			return;
		}
		$old_membership_level = $resultset->membership_level;
		
		if($emember_config->getValue('eMember_enable_secondary_membership'))
		{
			eMember_debug_log_subsc("Using secondary membership level feature... adding additional levels to the existing profile",true);
			$additional_levels = $resultset->more_membership_levels;
			if(is_null($additional_levels))
			{					
				$additional_levels = $resultset->membership_level;
				eMember_debug_log_subsc("Current additional levels for this profile is null. Adding level: ".$additional_levels,true);
			}
			else if(empty($additional_levels))
			{					
				$additional_levels = $resultset->membership_level;
				eMember_debug_log_subsc("Current additional levels for this profile is empty. Adding level: ".$additional_levels,true);					
			}
			else
			{
				$additional_levels = $additional_levels.",".$resultset->membership_level;
				$sec_levels = explode(',', $additional_levels);
				$additional_levels = implode(',', array_unique($sec_levels));//make sure there is not duplicate entry					
				eMember_debug_log_subsc("New additional level set: ".$additional_levels,true);
			}
			eMember_debug_log_subsc("Updating additional levels column for username: ".$resultset->user_name." with value: ".$additional_levels,true);							
			$updatedb = "UPDATE $members_table_name SET more_membership_levels='$additional_levels' WHERE member_id='$swpm_id'";    	    	
			$results = $wpdb->query($updatedb);		

			eMember_debug_log_subsc("Upgrading the primary membership level to the recently paid level. New primary membership level ID for this member is: ".$membership_level,true);
			$updatedb = "UPDATE $members_table_name SET account_state='$account_state',membership_level='$membership_level',subscription_starts='$subscription_starts',subscr_id='$subscr_id' WHERE member_id='$swpm_id'";    	    	
			$results = $wpdb->query($updatedb);
			do_action('emember_membership_changed',array('member_id'=>$swpm_id, 'from_level'=>$old_membership_level, 'to_level'=>$membership_level));
		}
		else
		{
			eMember_debug_log_subsc("Not using secondary membership level feature... upgrading the current membership level.",true);
			$current_expiry_date = emember_get_expiry_by_member_id($swpm_id);
			if($current_expiry_date != "noexpire"){
				if (strtotime($current_expiry_date) > strtotime($subscription_starts)){//Expiry time is in the future
					$subscription_starts = $current_expiry_date;//Start at the end of the previous expiry date
					eMember_debug_log_subsc("Updating the subscription start date to the current expiry date value: ".$subscription_starts,true);
				}
			}
			$updatedb = "UPDATE $members_table_name SET account_state='$account_state',membership_level='$membership_level',subscription_starts='$subscription_starts',subscr_id='$subscr_id' WHERE member_id='$swpm_id'";    	
	    	$results = $wpdb->query($updatedb);
	    	do_action('emember_membership_changed', array('member_id'=>$swpm_id, 'from_level'=>$old_membership_level, 'to_level'=>$membership_level));
		}
		
    	//If using the WP user integration then update the role on WordPress too
    	$membership_level_table = $wpdb->prefix . "wp_eMember_membership_tbl";
    	if($emember_config->getValue('eMember_create_wp_user'))
    	{
			eMember_debug_log_subsc("Updating WordPress user role...",true);
			$resultset = $wpdb->get_row("SELECT * FROM $members_table_name where member_id='$swpm_id'", OBJECT);
    		$membership_level = $resultset->membership_level;
    		$username = $resultset->user_name;    		
	        $membership_level_resultset = $wpdb->get_row("SELECT * FROM $membership_level_table where id='$membership_level'", OBJECT);
			eMember_debug_log_subsc("Calling WP role update function. Current users membership level is: ".$membership_level,true);
			emember_update_wp_role_for_member($username,$membership_level_resultset->role);
	        //do_action( 'set_user_role', $wp_user_id, $membership_level_resultset->role );
	        eMember_debug_log_subsc("Current WP users role updated to: ".$membership_level_resultset->role,true);
    	}
    	
    	//Set Email details	for the account upgrade notification	
    	$email = $ipn_data['payer_email'];    	
    	$subject = $emember_config->getValue('eMember_account_upgrade_email_subject');
	    if (empty($subject))
	    {
	    	$subject = "Member Account Upgraded";
	    }    	
    	$body = $emember_config->getValue('eMember_account_upgrade_email_body');
    	if (empty($body))
    	{
    		$body = "Your account has been upgraded successfully";
    	}
		$from_address = get_option('senders_email_address');
		//$email_body = $body;
		$login_link = $emember_config->getValue('login_page_url');
		$tags1 = array("{first_name}","{last_name}","{user_name}","{login_link}");			
		$vals1 = array($resultset->first_name,$resultset->last_name,$resultset->user_name,$login_link);			
		$email_body = str_replace($tags1,$vals1,$body);				
	    $headers = 'From: '.$from_address . "\r\n";   	    					    	
	}// End of existing account upgrade
	else
	{
		// create new member account
		$user_name ='';
		$password = '';
	
		$first_name = $ipn_data['first_name'];
		$last_name = $ipn_data['last_name'];
		$email = $ipn_data['payer_email'];
		$membership_level = $subsc_ref;
		$subscr_id = $unique_ref;
		$gender = 'not specified';
		
		eMember_debug_log_subsc("Membership level ID: ".$membership_level,true);

	    $address_street = $ipn_data['address_street'];
	    $address_city = $ipn_data['address_city'];
	    $address_state = $ipn_data['address_state'];
	    $address_zipcode = $ipn_data['address_zip'];
	    $country = $ipn_data['address_country'];
	
		$date = (date ("Y-m-d"));
		$account_state = 'active';
		$reg_code = uniqid();//rand(10, 1000);
		$md5_code = md5($reg_code);

	    $updatedb = "INSERT INTO $members_table_name (user_name,first_name,last_name,password,member_since,membership_level,account_state,last_accessed,last_accessed_from_ip,email,address_street,address_city,address_state,address_zipcode,country,gender,referrer,extra_info,reg_code,subscription_starts,txn_id,subscr_id) VALUES ('$user_name','$first_name','$last_name','$password', '$date','$membership_level','$account_state','$date','IP','$email','$address_street','$address_city','$address_state','$address_zipcode','$country','$gender','','','$reg_code','$date','','$subscr_id')";
	    $results = $wpdb->query($updatedb);

		$results = $wpdb->get_row("SELECT * FROM $members_table_name where subscr_id='$subscr_id' and reg_code='$reg_code'", OBJECT);
		$id = $results->member_id; //Alternatively use $wpdb->insert_id;
		
	    $separator='?';
	    $url = $emember_config->getValue('eMember_registration_page');
	    if(empty($url)){$url=get_option('eMember_registration_page');}
		if(strpos($url,'?')!==false)
		{
			$separator='&';
		}
		$reg_url = $url.$separator.'member_id='.$id.'&code='.$md5_code;
		eMember_debug_log_subsc("Member signup URL :".$reg_url,true);
	
		$subject = get_option('eMember_email_subject');
		$body = get_option('eMember_email_body');
		$from_address = get_option('senders_email_address');
	
	    $tags = array("{first_name}","{last_name}","{reg_link}");
	    $vals = array($first_name,$last_name,$reg_url);
		$email_body    = str_replace($tags,$vals,$body);
	    $headers = 'From: '.$from_address . "\r\n";
	}

    wp_mail($email,$subject,$email_body,$headers);
    eMember_debug_log_subsc("Member signup/upgrade completion email successfully sent",true);
}

function swpm_handle_subsc_cancel_stand_alone($ipn_data,$refund=false)
{
	if($refund){		
		$subscr_id = $ipn_data['parent_txn_id'];
		eMember_debug_log_subsc("Refund notification check for eMember - check if a member account needs to be deactivated... subscr ID: ".$subscr_id,true); 
	}else{
    	$subscr_id = $ipn_data['subscr_id'];
	}    

    global $wpdb;
    $members_table_name = $wpdb->prefix . "wp_eMember_members_tbl";
    $membership_level_table   = $wpdb->prefix . "wp_eMember_membership_tbl";
    
    eMember_debug_log_subsc("Retrieving member account from the database...",true);
    $resultset = $wpdb->get_row("SELECT * FROM $members_table_name where subscr_id='$subscr_id'", OBJECT);
    if($resultset)
    {
    	$membership_level = $resultset->membership_level;
    	$level_query = $wpdb->get_row("SELECT * FROM $membership_level_table where id='$membership_level'", OBJECT);
    	if (empty($level_query->subscription_period) && empty($level_query->subscription_unit)){
    		//subscription duration is set to no expiry or until canceled so deactivate the account now
    		$account_state = 'inactive';
		    $updatedb = "UPDATE $members_table_name SET account_state='$account_state' WHERE subscr_id='$subscr_id'";
		    $results = $wpdb->query($updatedb);    		
		    eMember_debug_log_subsc("Subscription cancellation received! Member account deactivated.",true);
    	}
    	else if (empty($level_query->subscription_period ) && !empty($level_query->subscription_unit)){//Fixed expiry
			//Subscription duration is set to fixed expiry. Don't do anything.
			eMember_debug_log_subsc("Subscription cancellation received! Level is using fixed expiry date so account will not be deactivated now.",true);
		}
    	else{
    		//Set the account to unsubscribed and it will be set to inactive when the "Subscription duration" is over	
    		$account_state = 'unsubscribed';    
		    $updatedb = "UPDATE $members_table_name SET account_state='$account_state' WHERE subscr_id='$subscr_id'";
		    $results = $wpdb->query($updatedb);    		
		    eMember_debug_log_subsc("Subscription cancellation received! Member account set to unsubscribed.",true);
    	}
    }
    else
    {
    	eMember_debug_log_subsc("No member found for the given subscriber ID:".$subscr_id,false);
    	return;
    }      	
}

function swpm_update_member_subscription_start_date_if_applicable($ipn_data)
{
    global $wpdb;
    $members_table_name = $wpdb->prefix . "wp_eMember_members_tbl";
    $membership_level_table = $wpdb->prefix . "wp_eMember_membership_tbl";    
    $email = $ipn_data['payer_email'];
    $subscr_id = $ipn_data['subscr_id'];
	eMember_debug_log_subsc("Updating subscription start date if applicable for this subscription payment. Subscriber ID: ".$subscr_id." Email: ".$email,true);
	
	//We can also query using the email address
	$query_db = $wpdb->get_row("SELECT * FROM $members_table_name WHERE subscr_id = '$subscr_id'", OBJECT);
	if($query_db){
		$swpm_id = $query_db->member_id;
		$current_primary_level = $query_db->membership_level;
		eMember_debug_log_subsc("Found a record in the member table. The eMember ID of the account to check is: ".$swpm_id." Membership Level: ".$current_primary_level,true);
		
		$level_query = $wpdb->get_row("SELECT * FROM $membership_level_table where id='$current_primary_level'", OBJECT);
    	if(!empty($level_query->subscription_period) && !empty($level_query->subscription_unit)){//Duration value is used		
			$account_state = "active";
			$subscription_starts = (date ("Y-m-d"));

			$updatedb = "UPDATE $members_table_name SET account_state='$account_state',subscription_starts='$subscription_starts' WHERE member_id='$swpm_id'";    	    	
			$results = $wpdb->query($updatedb);
			eMember_debug_log_subsc("Updated the member profile with current date as the subscription start date.",true);
    	}else{
    		eMember_debug_log_subsc("This membership level is not using a duration/interval value as the subscription duration.",true);
    	}
	}else{
		eMember_debug_log_subsc("Did not find a record in the members table for subscriber ID: ".$subscr_id,true);
	}
}

function eMember_debug_log_subsc($message,$success,$end=false)
{
    // Timestamp
    $text = '['.date('m/d/Y g:i A').'] - '.(($success)?'SUCCESS :':'FAILURE :').$message. "\n";
    if ($end) {
    	$text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log
    $fp=fopen("subscription_handle_debug.log",'a');
    fwrite($fp, $text );
    fclose($fp);  // close file
}
