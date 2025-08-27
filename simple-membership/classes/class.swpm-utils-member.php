<?php

/**
 * SwpmMemberUtils
 * All the utility functions related to member records should be added to this class
 */
class SwpmMemberUtils {

	public static function create_swpm_member_entry_from_array_data( $fields ) {
		global $wpdb;
		$res = $wpdb->insert( $wpdb->prefix . 'swpm_members_tbl', $fields );

		if ( ! $res ) {
			//DB error occurred
			$error_msg = 'create_swpm_member_entry_from_array_data() - DB error occurred: ' . json_encode( $wpdb->last_result );
			SwpmLog::log_simple_debug( $error_msg, false );
		}

		$member_id = $wpdb->insert_id;
		SwpmLog::log_simple_debug( 'create_swpm_member_entry_from_array_data() - SWPM member entry created successfully. Member ID: ' . $member_id, true );
		return $member_id;
	}

	public static function is_member_logged_in() {
		$auth = SwpmAuth::get_instance();
		if ( $auth->is_logged_in() ) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_logged_in_members_id() {
		$auth = SwpmAuth::get_instance();
		if ( ! $auth->is_logged_in() ) {
			return SwpmUtils::_( 'User is not logged in.' );
		}
		return $auth->get( 'member_id' );
	}

	public static function get_logged_in_members_username() {
		$auth = SwpmAuth::get_instance();
		if ( ! $auth->is_logged_in() ) {
			return SwpmUtils::_( 'User is not logged in.' );
		}
		return $auth->get( 'user_name' );
	}

	public static function get_logged_in_members_level() {
		$auth = SwpmAuth::get_instance();
		if ( ! $auth->is_logged_in() ) {
			return SwpmUtils::_( 'User is not logged in.' );
		}
		return $auth->get( 'membership_level' );
	}

	public static function get_logged_in_members_level_name() {
		$auth = SwpmAuth::get_instance();
		if ( $auth->is_logged_in() ) {
			return $auth->get( 'alias' );
		}
		return SwpmUtils::_( 'User is not logged in.' );
	}

	public static function get_logged_in_members_email() {
		$auth = SwpmAuth::get_instance();
		if ( ! $auth->is_logged_in() ) {
			return SwpmUtils::_( 'User is not logged in.' );
		}
		return $auth->get( 'email' );
	}

	public static function get_member_field_by_id( $id, $field, $default = '' ) {
		global $wpdb;
		$query    = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE member_id = %d';
		$userData = $wpdb->get_row( $wpdb->prepare( $query, $id ) );
		if ( isset( $userData->$field ) ) {
			return $userData->$field;
		}

		return apply_filters( 'swpm_get_member_field_by_id', $default, $id, $field );
	}

	public static function get_formatted_expiry_date_by_user_id( $swpm_id ) {
		$expiry_timestamp = self::get_expiry_date_timestamp_by_user_id( $swpm_id );
		if ( $expiry_timestamp == PHP_INT_MAX ) {
			//No Expiry Setting
			$formatted_expiry_date = SwpmUtils::_( 'No Expiry' );
		} else {
			$expiry_date           = date( 'Y-m-d', $expiry_timestamp );
			$formatted_expiry_date = SwpmUtils::get_formatted_date_according_to_wp_settings( $expiry_date );
		}
		return $formatted_expiry_date;
	}

	public static function get_expiry_date_timestamp_by_user_id( $swpm_id ) {
		$swpm_user = self::get_user_by_id( $swpm_id );
		$expiry_timestamp = SwpmUtils::get_expiration_timestamp( $swpm_user );
		return $expiry_timestamp;
	}

	public static function member_record_exists( $swpm_id ) {
		// Checks if the SWPM user record exists for the given member ID.
		global $wpdb;
		$query = $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = %d", $swpm_id );
		$count = $wpdb->get_var( $query );
		return $count > 0;
	}
	
	public static function get_user_by_id( $swpm_id ) {
		//Retrieves the SWPM user record for the given member ID
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = %d", $swpm_id );
		$result = $wpdb->get_row( $query );
		return $result;
	}

	public static function get_user_by_user_name( $swpm_user_name ) {
		//Retrieves the SWPM user record for the given member username
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE user_name = %s", $swpm_user_name );
		$result = $wpdb->get_row( $query );
		return $result;
	}

	public static function get_user_by_email( $swpm_email ) {
		//Retrieves the SWPM user record for the given member email address
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE email = %s", $swpm_email );
		$result = $wpdb->get_row( $query );
		return $result;
	}

	public static function get_user_by_subsriber_id( $subsc_id ) {
		//Retrieves the SWPM user record for the given Subscription ID.
		//(Subscriber ID = Subscription ID)
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE subscr_id = %s", $subsc_id );
		$result = $wpdb->get_row( $query );
		return $result;
	}

	/*
	 * Retrieves the last SWPM user record for the given IP address. This method is not robust as multiple users can have the same IP address.
	 */
	public static function get_user_by_ip_address( $user_ip = '') {
		if( empty($user_ip) ){
			//Get the user's IP address if not provided
			$user_ip = SwpmUtils::get_user_ip_address();
		}

		if ( ! empty( $user_ip ) ) {
			//Lets check if a member profile exists with this IP address.
			global $wpdb;
			$query = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE last_accessed_from_ip=%s ORDER BY member_id DESC';
			$query = $wpdb->prepare( $query, $user_ip );
			$result = $wpdb->get_row( $query );
			return $result;
		}
		return null;
	}	

	public static function get_wp_user_from_swpm_user_id( $swpm_id ) {
		//Retrieves the WP user record for the given SWPM member ID.
		$swpm_user_row = self::get_user_by_id( $swpm_id );
		$username      = $swpm_user_row->user_name;
		$wp_user       = get_user_by( 'login', $username );
		return $wp_user;
	}

	public static function get_membership_level_id_of_a_member( $member_id ) {
		$user_row = SwpmMemberUtils::get_user_by_id( $member_id );
		$level_id = $user_row->membership_level;
		return $level_id;
	}

	public static function get_account_status_of_a_member( $member_id ) {
		$user_row = SwpmMemberUtils::get_user_by_id( $member_id );
		$account_status = $user_row->account_state;
		return $account_status;
	}	

	public static function get_all_members_of_a_level( $level_id ) {
		//Retrieves all the SWPM user records for the given membership level
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE membership_level = %s", $level_id );
		$result = $wpdb->get_results( $query );
		return $result;
	}

	public static function get_all_members_of_account_status( $status ) {
		//Retrieves all the SWPM user records for the given account status
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE account_state = %s", $status );
		$result = $wpdb->get_results( $query );
		return $result;
	}

	/**
	 * Retrieves all members with a given membership level id and a given account state
	 * '-1' as $level_id represents all membership levels.
	 * 'all' as $account_state represents all account states.
	 * 
	 * @param int|string $level_id Membership level id.
	 * @param string $account_state Account state.
	 * @return object Members
	 */
	public static function get_all_members_of_a_level_and_a_state($level_id, $account_state) {
		global $wpdb;

		$query  = "SELECT * FROM {$wpdb->prefix}swpm_members_tbl";
		
		// Check if 'WHERE' clause need to be used or not.
		if($level_id != -1 || $account_state != 'all'){

			$placeholders = array();
			$where_clauses = array();
			
			if ($level_id != -1) {
				$where_clauses[] = "membership_level = %d";
				$placeholders[] = $level_id;
			}
			if ($account_state != 'all') {     
				$where_clauses[] = "account_state = %s";
				$placeholders[] = $account_state;
			}
			
			$query .= " WHERE " . implode(" AND ", $where_clauses);
			
			// query preparation is not required as there are not placeholders.
			$query = $wpdb->prepare($query, $placeholders);
		}
		
		$result = $wpdb->get_results( $query );

		return $result;
	}

	public static function delete_member_and_wp_user( $member_id ) {
		//Deletes the SWPM user record and the associated WP user record.
		//The WP user record will be deleted only if the user is not an administrator user.
		SwpmMembers::delete_user_by_id( $member_id );
	}

	/*
	 * Use this function to update or set membership level of a member easily.
	 */
	public static function update_membership_level( $member_id, $target_membership_level ) {
		global $wpdb;
		$members_table_name = $wpdb->prefix . 'swpm_members_tbl';
		$query              = $wpdb->prepare( "UPDATE $members_table_name SET membership_level=%s WHERE member_id=%s", $target_membership_level, $member_id );
		$resultset          = $wpdb->query( $query );
	}

	/*
	 * Use this function to update or set membership level of a member and also update the role (according to the role specified in the new level).
	 */
	public static function update_membership_level_and_role( $member_id, $target_membership_level ) {
		//Get the old (before update) membership level.
		$old_membership_level = self::get_membership_level_id_of_a_member( $member_id );

		//Update the membership level.
		self::update_membership_level($member_id, $target_membership_level );

		//Trigger the level changed/updated action hook.
		do_action(
			'swpm_membership_level_changed',
			array(
				'member_id'  => $member_id,
				'from_level' => $old_membership_level,
				'to_level'   => $target_membership_level,
			)
		);
	}

	/*
	 * Use this function to update or set account status of a member easily.
	 */
	public static function update_account_state( $member_id, $new_status = 'active' ) {
		//Get the existing account status of the member.
		$existing_status = self::get_account_status_of_a_member( $member_id );

		global $wpdb;
		$members_table_name = $wpdb->prefix . 'swpm_members_tbl';

		SwpmLog::log_simple_debug( 'Updating the account status value of member (ID: ' . $member_id . ') to: ' . $new_status, true );
		$query = $wpdb->prepare( "UPDATE $members_table_name SET account_state=%s WHERE member_id=%s", $new_status, $member_id );
		$resultset = $wpdb->query( $query );

		//Trigger the account status updated action hook. It doesn't matter if the status is not changed. 
		//The function listening to this hook can decide what to do.
		do_action(
			'swpm_account_status_updated',
			array(
				'member_id' => $member_id,
				'from_status' => $existing_status,
				'to_status' => $new_status,
			)
		);

	}

	/*
	 * Use this function to update or set access starts date of a member easily.
	 */
	public static function update_access_starts_date( $member_id, $new_date ) {
		global $wpdb;
		$members_table_name = $wpdb->prefix . 'swpm_members_tbl';
		$query              = $wpdb->prepare( "UPDATE $members_table_name SET subscription_starts=%s WHERE member_id=%s", $new_date, $member_id );
		$resultset          = $wpdb->query( $query );
	}

	/*
	 * Use this function to update or set extra_info array data of a profile easily.
	 */
	public static function update_account_extra_info( $member_id, $extra_info = array() ) {
		//Example $extra_info value: array('last_webhook_status' => 'expired', 'plan_id' => '1234');
		global $wpdb;
		$members_table_name = $wpdb->prefix . 'swpm_members_tbl';
		$extra_info_serialized = serialize( $extra_info );
		//SwpmLog::log_simple_debug( 'Updating the extra_info value of member (' . $member_id . ') to: ' . $extra_info_serialized, true );
		$query = $wpdb->prepare( "UPDATE $members_table_name SET extra_info=%s WHERE member_id=%s", $extra_info_serialized, $member_id );
		$resultset = $wpdb->query( $query );
	}

	/*
	 * Use this function to get extra_info array data of a profile easily.
	 */	
	public static function get_account_extra_info( $member_id ){
		$member_record = self::get_user_by_id( $member_id );
		$extra_info = maybe_unserialize( $member_record->extra_info );
		return $extra_info;
	}

	/*
	 * Use this function to get extra_info array data of a profile using the subscr_id.
	 */	
	public static function get_account_extra_info_by_subscr_id( $subscr_id ){
		$member_record = self::get_user_by_subsriber_id( $subscr_id );
		if( !$member_record ){
			return false;
		}
		$extra_info = maybe_unserialize( $member_record->extra_info );
		return $extra_info;
	}

	/*
	 * Calculates the Access Starts date value considering the level and current expiry. Useful for after payment member profile update.
	 */
	public static function calculate_access_start_date_for_account_update( $args ) {
		$swpm_id              = $args['swpm_id'];
		$membership_level     = $args['membership_level'];
		$old_membership_level = $args['old_membership_level'];
		$old_account_state    = isset($args['old_account_state']) ? $args['old_account_state'] : '';

		//Start with default current date.
		$access_starts = SwpmUtils::get_current_date_in_wp_zone();//( date( 'Y-m-d' ) );

		//Check if the membership level is the same as the old level.
		if ( $membership_level == $old_membership_level ) {
			//Payment for the same membership level (renewal).
			//Algorithm - ONLY set the $access_starts date to current expiry date if the current expiry date is in the future AND account state is 'active'.
			//Otherwise set $access_starts to TODAY.
			SwpmLog::log_simple_debug( 'This is a payment for the same membership level (renewal scenario).', true );

			$expiry_timestamp = self::get_expiry_date_timestamp_by_user_id( $swpm_id );
			if ( $expiry_timestamp > time() && $old_account_state == 'active' ) {
				//Account is not expired or inactive AND the expiry date is in the future.
				SwpmLog::log_simple_debug( 'The account is active AND the expiry date is in the future.', true );
				$level_row = SwpmUtils::get_membership_level_row_by_id( $membership_level );
				$subs_duration_type = $level_row->subscription_duration_type;
				if ( $subs_duration_type == SwpmMembershipLevel::NO_EXPIRY ) {
					//No expiry type level.
					//Use todays date for $access_starts date parameter.
					SwpmLog::log_simple_debug( 'This is a NO EXPIRY level. Use todays date for Access Starts.', true );
				} elseif ( $subs_duration_type == SwpmMembershipLevel::FIXED_DATE ) {
					//Fixed date expiry level.
					//Use todays date for $access_starts date parameter.
					SwpmLog::log_simple_debug( 'This is a FIXED DATE expiry level. Use todays date for Access Starts.', true );
				} else {
					//Duration expiry level.
					//Set the $access_starts date to the current expiry date so the renewal time starts from then.
					$access_starts = date( 'Y-m-d', $expiry_timestamp );
					SwpmLog::log_simple_debug( 'This is a DURATION expiry level. Use the current expiry date for the Access Starts date.', true );
				}
			} else {
				//Account is already expired or inactive.
				//Use the standard todays date for $access_starts date parameter.
				SwpmLog::log_simple_debug( 'Account is already expired or inactive. Use todays date for Access Starts date.', true );
			}
		} else {
			//Payment for a NEW membership level (upgrade).
			//Use todays date for $access_starts date parameter.
			SwpmLog::log_simple_debug( 'This is a payment for a NEW membership level (upgrade scenario). Use todays date for Access Starts to begin the new level immediately.', true );
		}

		return $access_starts;
	}

	public static function is_valid_user_name( $user_name ) {
		return preg_match( '/^[a-zA-Z0-9.\-_*@]+$/', $user_name ) == 1;
	}

        public static function check_and_die_if_email_belongs_to_admin_user( $email_to_check ){
			//Check if the email belongs to an existing wp user account.
			$wp_user_id = email_exists( $email_to_check );
			if ( $wp_user_id ) {
                //A wp user account exist with this email.
                //Check if the user has admin role.
                $admin_user = SwpmMemberUtils::wp_user_has_admin_role( $wp_user_id );
                if ( $admin_user ) {
                    //This email belongs to an admin user. Cannot use/register using an admin user's email from front-end. Show error message then exit.
                    $error_msg = '<p>'.sprintf(__('This email address (%s) belongs to an admin user. This email cannot be used to register a new account on this site for security reasons. Contact site admin.', 'simple-membership'), $email_to_check).'</p>';
                    $error_msg .= '<p>'.__('For testing purpose, you can create another user account that is completely separate from the admin user account of this site.', 'simple-membership').'</p>';
                    wp_die( $error_msg );
                }
			}
        }

        public static function check_and_die_if_username_belongs_to_admin_user( $username_to_check ){
            //Check if the username belongs to an existing wp user account.
            $wp_user_id = username_exists( $username_to_check );
            if ( $wp_user_id ) {
                //A wp user account exists with this username.
                //Check if the user has admin role.
                $admin_user = SwpmMemberUtils::wp_user_has_admin_role( $wp_user_id );
                if ( $admin_user ) {
                    //This Username belongs to an admin user. Cannot use/register using an existing admin user's username from front-end. Show error message then exit.
                    $error_msg = '<p>'.sprintf(__('This username (%s) belongs to an admin user. It cannot be used to register a new account on this site for security reasons. Contact site admin.', 'simple-membership'), $username_to_check).'</p>';
                    $error_msg .= '<p>'.__('For testing purpose, you can create another user account that is completely separate from the admin user account of this site.', 'simple-membership').'</p>';
                    wp_die( $error_msg );
                }
            }
        }

        /**
         * Get wp user roles by user ID.
         *
         * @param int $id
         * @return array
         */
        public static function get_wp_user_roles_by_id( $wp_user_id )
        {
            $user = new WP_User( $wp_user_id );
            if ( empty ( $user->roles ) or ! is_array( $user->roles ) ){
                return array ();
            }
            $wp_roles = new WP_Roles;
            $names = $wp_roles->get_names();
            $out = array ();
            foreach ( $user->roles as $role ) {
                if ( isset ( $names[ $role ] ) ){
                    $out[ $role ] = $names[ $role ];
                }
            }

            return $out;
        }

	public static function wp_user_has_admin_role( $wp_user_id ) {
		$caps = get_user_meta( $wp_user_id, 'wp_capabilities', true );
		if ( is_array( $caps ) && in_array( 'administrator', array_keys( (array) $caps ) ) ) {
                    //This wp user has "administrator" role.
                    return true;
		}
                //Check if $caps was empty (It can happen on sites with customized roles and capbilities). If yes, then perform an additional role check.
                if ( empty ( $caps ) ){
                    //Try to retrieve roles from the user object.
                    SwpmLog::log_simple_debug( 'Empty caps. Calling get_wp_user_roles_by_id() to retrieve role.', true );
                    $roles = self::get_wp_user_roles_by_id($wp_user_id);
                    if ( is_array( $roles ) && in_array( 'administrator', array_keys( (array) $roles ) ) ) {
                        //This wp user has "administrator" role.
                        return true;
                    }
                }

		return false;
	}

	public static function update_wp_user_role_with_level_id( $wp_user_id, $level_id ) {
		$level_row = SwpmUtils::get_membership_level_row_by_id( $level_id );
		$user_role = $level_row->role;
		self::update_wp_user_role( $wp_user_id, $user_role );
	}

	public static function update_wp_user_role( $wp_user_id, $role ) {
		if ( SwpmUtils::is_multisite_install() ) {//MS install
			return; //TODO - don't do this for MS install
		}

		$admin_user = self::wp_user_has_admin_role( $wp_user_id );
		if ( $admin_user ) {
			SwpmLog::log_simple_debug( 'This user has admin role. No role modification will be done.', true );
			return;
		}

		//wp_update_user() function will trigger the 'set_user_role' hook.
		wp_update_user(
			array(
				'ID'   => $wp_user_id,
				'role' => $role,
			)
		);
		SwpmLog::log_simple_debug( 'User role updated.', true );
	}

	//if a username is provided, it will return sanitized email of the user
	//if no username is found, empty is returned
	public static function get_sanitized_email($username_or_email_address)
	{		
		if(is_email($username_or_email_address))
		{
			return sanitize_email($username_or_email_address);
		}
		else{
			$user_row = SwpmMemberUtils::get_user_by_user_name( $username_or_email_address );
						
			if ( $user_row ) {				
				//found a profile
				return $user_row->email;
			} 
		}
		return "";
	}

	/**
	 * Get subscription data by key from the member's extra info column.
	 *
	 * @param $member_id int The ID of the member.
	 * @param $key string The field to retrieve
	 * @param $default string Default value if result not found.
	 *
	 * @return string The value of provided key.
	 */
	public static function get_subscription_data_extra_info($member_id, $key, $default = ''){
		// Attached subsrc_id to members table.
		$sub_id = self::get_member_field_by_id( $member_id, 'subscr_id' );

		// Check if subscr_id not present.
		if (empty($sub_id)){
			return $default;
		}

		// Retrieve the extra_info column.
		$extra_info = self::get_account_extra_info( $member_id );

		// Check if target key value pair exists, then return it.
		if ( is_array($extra_info) && isset( $extra_info['subscription_details'][$sub_id][$key] ) ) {
			return $extra_info['subscription_details'][$sub_id][$key];
		}

		// Target key value pair not set, return default value.
		return $default;
	}

	/**
	 * Set subscription data by key in the member's extra info column.
	 *
	 * @param $member_id int The ID of the member.
	 * @param $key string The field to retrieve.
	 * @param $value string The value of the field.
	 * @param $subscr_id string The subscription id to retrieve data of.
	 *
	 * @return void
	*/
	public static function set_subscription_data_extra_info($member_id, $key, $value,  $subscr_id = ''){
		if (!empty($subscr_id)){
			$sub_id = $subscr_id;
		} else {
			// get attached subs id to members account.
			$sub_id = SwpmMemberUtils::get_member_field_by_id($member_id, 'subscr_id');
		}

		if (empty($sub_id)){
			// No subscription id found, so nothing to set.
			return;
		}

		$extra_info = SwpmMemberUtils::get_account_extra_info( $member_id );

		// Initialize an array if not set.
		if ( ! is_array( $extra_info ) ) {
			$extra_info = array();
		}

		// Check whether subscription_details array exists. If not, create one first.
		if ( !array_key_exists('subscription_details', $extra_info) || !is_array($extra_info['subscription_details'])){
			$extra_info['subscription_details'] = array();
		}

		// Check whether the array value of subscription_details exists. If not, create one first.
		if ( ! array_key_exists($sub_id, $extra_info['subscription_details']) ){
			$extra_info['subscription_details'][$sub_id] = array();
		}

		$extra_info['subscription_details'][$sub_id][$key] = $value;

		SwpmMemberUtils::update_account_extra_info( $member_id, $extra_info );
	}

	public static function get_membership_level_update_type( $args ){
		//Checks if the membership level is being upgraded or renewed.
		//If the membership level is being changed, it is considered an upgrade, otherwise it is considered a renewal.
		$membership_level = isset($args['membership_level']) ? $args['membership_level'] : '';
		$old_membership_level = isset($args['old_membership_level']) ? $args['old_membership_level'] : '';

		if ($membership_level != $old_membership_level){
			return 'upgrade';
		}

		//If the membership level is not being changed, it is considered a renewal.
		return 'renew';
	}
}
