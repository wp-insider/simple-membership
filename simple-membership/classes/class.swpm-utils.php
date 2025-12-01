<?php

abstract class SwpmUtils {

	public static function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/*
	 * This function handles various initial setup tasks that need to be executed very early on (before other functions of the plugin is called).
	 */

	public static function do_misc_initial_plugin_setup_tasks() {

		//Management role/permission setup
		$admin_dashboard_permission = SwpmSettings::get_instance()->get_value( 'admin-dashboard-access-permission' );
		if ( empty( $admin_dashboard_permission ) ) {
			//By default only admins can manage/see admin dashboard
			define( 'SWPM_MANAGEMENT_PERMISSION', 'manage_options' );
		} else {
			define( 'SWPM_MANAGEMENT_PERMISSION', $admin_dashboard_permission );
		}

		//Override the settings menu (options.php) update capability according to the role set in "Admin Dashboard Access Permission" option.
		add_filter( 'option_page_capability_swpm-settings-tab-1', 'SwpmUtils::swpm_settings_update_capability' );
		add_filter( 'option_page_capability_swpm-settings-tab-2', 'SwpmUtils::swpm_settings_update_capability' );
		add_filter( 'option_page_capability_swpm-settings-tab-3', 'SwpmUtils::swpm_settings_update_capability' );
		add_filter( 'option_page_capability_swpm-settings-tab-4', 'SwpmUtils::swpm_settings_update_capability' );
		add_filter( 'option_page_capability_swpm-settings-tab-5', 'SwpmUtils::swpm_settings_update_capability' );

	}

	public static function swpm_settings_update_capability($capability){
		if ( defined('SWPM_MANAGEMENT_PERMISSION') ){
			//Use SWPM defined one.
			$capability = SWPM_MANAGEMENT_PERMISSION;
		} else {
			//Use default.
			$capability = 'manage_options';
		}
		return $capability;
	}

	public static function subscription_type_dropdown( $selected ) {
		return '<option ' . ( ( $selected == SwpmMembershipLevel::NO_EXPIRY ) ? 'selected="selected"' : '' ) . ' value="' . SwpmMembershipLevel::NO_EXPIRY . '">No Expiry</option>' .
				'<option ' . ( ( $selected == SwpmMembershipLevel::DAYS ) ? 'selected="selected"' : '' ) . ' value="' . SwpmMembershipLevel::DAYS . '">Day(s)</option>' .
				'<option ' . ( ( $selected == SwpmMembershipLevel::WEEKS ) ? 'selected="selected"' : '' ) . ' value="' . SwpmMembershipLevel::WEEKS . '">Week(s)</option>' .
				'<option ' . ( ( $selected == SwpmMembershipLevel::MONTHS ) ? 'selected="selected"' : '' ) . ' value="' . SwpmMembershipLevel::MONTHS . '">Month(s)</option>' .
				'<option ' . ( ( $selected == SwpmMembershipLevel::YEARS ) ? 'selected="selected"' : '' ) . ' value="' . SwpmMembershipLevel::YEARS . '">Year(s)</option>' .
				'<option ' . ( ( $selected == SwpmMembershipLevel::FIXED_DATE ) ? 'selected="selected"' : '' ) . ' value="' . SwpmMembershipLevel::FIXED_DATE . '">Fixed Date</option>';
	}

	// $subscript_period must be integer.
	public static function calculate_subscription_period_days( $subcript_period, $subscription_duration_type ) {
		if ( $subscription_duration_type == SwpmMembershipLevel::NO_EXPIRY ) {
			return 'noexpire';
		}
		if ( ! is_numeric( $subcript_period ) ) {
			throw new Exception( ' subcript_period parameter must be integer in SwpmUtils::calculate_subscription_period_days method' );
		}
		switch ( strtolower( $subscription_duration_type ) ) {
			case SwpmMembershipLevel::DAYS:
				break;
			case SwpmMembershipLevel::WEEKS:
				$subcript_period = $subcript_period * 7;
				break;
			case SwpmMembershipLevel::MONTHS:
				$subcript_period = $subcript_period * 30;
				break;
			case SwpmMembershipLevel::YEARS:
				$subcript_period = $subcript_period * 365;
				break;
		}
		return $subcript_period;
	}

	public static function get_expiration_timestamp( $user ) {
		//Check and make sure that the user object has a valid membership level assigned.
        if ( !isset($user->membership_level) || !is_numeric($user->membership_level) || !SwpmMembershipLevelUtils::check_if_membership_level_exists($user->membership_level) ){
            //This is a critical error. The user object does not have a valid membership level assigned.
			//Log this critical error and end the script with an error message.
			$member_id = isset($user->member_id) ? $user->member_id : '';
			$critical_error_msg = "Error! This member profile (Member ID: ". $member_id .") does not have a valid membership level assigned. The site admin needs to assign a valid membership level to this member profile.";
			SwpmLog::log_simple_debug($critical_error_msg, false);
			if(is_admin()){
				//This is getting called from the admin dashboard side. Just return from here so the rest of the code can execute.
				//This allows the admin to edit/update the member's profile with a valid membership level.
				return;
			}else{
				//This is getting called from the front-end side (example: at member login time). So we need to show a critical error message to the member and end the script.
            	wp_die($critical_error_msg);
				//The script will die here. So the rest of the code will not be executed.
			}
        }

		//Get the permission object for the user's membership level
		$permission = SwpmPermission::get_instance( $user->membership_level );
		if ( SwpmMembershipLevel::FIXED_DATE == $permission->get( 'subscription_duration_type' ) ) {
			return strtotime( $permission->get( 'subscription_period' ) );
		}
		$days = self::calculate_subscription_period_days( $permission->get( 'subscription_period' ), $permission->get( 'subscription_duration_type' ) );
		if ( $days == 'noexpire' ) {
			return PHP_INT_MAX; // which is equivalent to
		}
		return strtotime( $user->subscription_starts . ' ' . $days . ' days' );
	}

	public static function is_subscription_expired( $user ) {
		$expiration_timestamp = self::get_expiration_timestamp( $user );
		if ( $expiration_timestamp < time() ) {
			//Account expired.
			return true;
		}
		return false;
	}

	/*
	 * Returns a formatted expiry date string (of a member). This can be useful to echo the date value.
	 */

	public static function get_formatted_expiry_date( $start_date, $subscription_duration, $subscription_duration_type ) {
		if ( $subscription_duration_type == SwpmMembershipLevel::FIXED_DATE ) {
			//Membership will expire after a fixed date.
			return self::get_formatted_and_translated_date_according_to_wp_settings( $subscription_duration );
		}

		$expires = self::calculate_subscription_period_days( $subscription_duration, $subscription_duration_type );
		if ( $expires == 'noexpire' ) {
			//Membership is set to no expiry or until cancelled.
			return self::_( 'Never' );
		}

		//Membership is set to a duration expiry settings.
		return date_i18n( get_option( 'date_format' ), strtotime( $start_date . ' ' . $expires . ' days' ) );
	}

	public static function gender_dropdown( $selected = 'not specified' ) {
		return '<option ' . ( ( strtolower( $selected ) == 'male' ) ? 'selected="selected"' : '' ) . ' value="male">' . SwpmUtils::_('Male') . '</option>' .
				'<option ' . ( ( strtolower( $selected ) == 'female' ) ? 'selected="selected"' : '' ) . ' value="female">' . SwpmUtils::_('Female') . '</option>' .
				'<option ' . ( ( strtolower( $selected ) == 'not specified' ) ? 'selected="selected"' : '' ) . ' value="not specified">' . SwpmUtils::_('Not Specified') . '</option>';
	}

	public static function get_account_state_options() {
		return array(
			'active'              => __( 'Active', 'simple-membership' ),
			'inactive'            => __( 'Inactive', 'simple-membership' ),
			'activation_required' => __( 'Activation Required', 'simple-membership' ),
			'pending'             => __( 'Pending', 'simple-membership' ),
			'expired'             => __( 'Expired', 'simple-membership' ),
		);
	}

	public static function account_state_dropdown( $selected = 'active' , $option_all = false) {
		$options = self::get_account_state_options();
		$html    = '';
		foreach ( $options as $key => $value ) {
			$html .= '<option ' . ( ( strtolower( $selected ) == $key ) ? 'selected="selected"' : '' ) . '  value="' . $key . '"> ' . $value . '</option>';
		}
		return $html;
	}

	public static function membership_level_dropdown( $selected = 0, $option_all = false ) {
		$options = '';
		global $wpdb;
		$query  = 'SELECT alias, id FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE id != 1';
		$levels = $wpdb->get_results( $query );
		foreach ( $levels as $level ) {
			$options .= '<option ' . ( $selected == $level->id ? 'selected="selected"' : '' ) . ' value="' . $level->id . '" >' . $level->alias . '</option>';
		}
		return $options;
	}

	public static function get_all_membership_level_ids() {
		global $wpdb;
		$query = 'SELECT id FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE id != 1';
		return $wpdb->get_col( $query );
	}

	public static function get_membership_level_row_by_id( $level_id ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE id=%d', $level_id );
		$level_resultset = $wpdb->get_row( $query );
		return $level_resultset;
	}

	public static function membership_level_id_exists( $level_id ) {
		//Returns true if the specified membership level exists in the system. Returns false if the level has been deleted (or doesn't exist).
		$all_level_ids = self::get_all_membership_level_ids();
		if ( in_array( $level_id, $all_level_ids ) ) {
			//Valid level ID
			return true;
		} else {
			return false;
		}
	}

	public static function get_registration_complete_prompt_link( $for = 'all', $send_email = false, $member_id = '' ) {
		$members = array();
		global $wpdb;
		switch ( $for ) {
			case 'one':
				if ( empty( $member_id ) ) {
					return array();
				}
				$query   = $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}swpm_members_tbl WHERE member_id =  %d", $member_id );
				$members = $wpdb->get_results( $query );
				break;
			case 'all':
				$query   = "SELECT * FROM  {$wpdb->prefix}swpm_members_tbl WHERE reg_code != '' ";
				$members = $wpdb->get_results( $query );
				break;
		}
		$settings  = SwpmSettings::get_instance();
		$separator = '?';
		$url       = $settings->get_value( 'registration-page-url' );
		if ( strpos( $url, '?' ) !== false ) {
			$separator = '&';
		}

		$links = array();
		foreach ( $members as $member ) {
			$reg_url = $url . $separator . 'member_id=' . $member->member_id . '&code=' . $member->reg_code;
			if ( $send_email && empty( $member->user_name ) ) {
				$tags = array( '{first_name}', '{last_name}', '{reg_link}' );
				$vals = array( $member->first_name, $member->last_name, $reg_url );

				$subject = $settings->get_value( 'reg-prompt-complete-mail-subject' );
				if ( empty( $subject ) ) {
					$subject = 'Please complete your registration';
				}

				$body = $settings->get_value( 'reg-prompt-complete-mail-body' );
				if ( empty( $body ) ) {
					$body = "Please use the following link to complete your registration. \n {reg_link}";
				}
				$body       = html_entity_decode( $body );
				$email_body = str_replace( $tags, $vals, $body );

				$from_address = $settings->get_value( 'email-from' );
				$headers      = 'From: ' . $from_address . "\r\n";

				$subject    = apply_filters( 'swpm_email_complete_your_registration_subject', $subject );
				$email_body = apply_filters( 'swpm_email_complete_your_registration_body', $email_body );
				SwpmMiscUtils::mail( $member->email, $subject, $email_body, $headers );
				SwpmLog::log_simple_debug( 'Prompt to complete registration email sent to: ' . $member->email . '. From email address value used: ' . $from_address, true );
			}
			$links[] = $reg_url;
		}
		return $links;
	}

	/* This function is deprecated and will be removed in the future. Use SwpmMemberUtils::update_wp_user_role() instead */

	public static function update_wp_user_Role( $wp_user_id, $role ) {
		// Deprecated function.
		SwpmMemberUtils::update_wp_user_role( $wp_user_id, $role );
	}

	public static function update_wp_user( $wp_user_name, $swpm_data ) {
		$wp_user_info = array();
		if ( isset( $swpm_data['email'] ) ) {
			$wp_user_info['user_email'] = $swpm_data['email'];
		}
		if ( isset( $swpm_data['first_name'] ) ) {
			$wp_user_info['first_name'] = $swpm_data['first_name'];
		}
		if ( isset( $swpm_data['last_name'] ) ) {
			$wp_user_info['last_name'] = $swpm_data['last_name'];
		}
		if ( isset( $swpm_data['plain_password'] ) ) {
			$wp_user_info['user_pass'] = $swpm_data['plain_password'];
		}

		$wp_user = get_user_by( 'login', $wp_user_name );

		if ( $wp_user ) {
			$wp_user_info['ID'] = $wp_user->ID;
			return wp_update_user( $wp_user_info );
		}
		return false;
	}

	public static function create_wp_user( $wp_user_data ) {

                //First, check if email or username belongs to an existing admin user.
                SwpmMemberUtils::check_and_die_if_email_belongs_to_admin_user($wp_user_data['user_email']);
                SwpmMemberUtils::check_and_die_if_username_belongs_to_admin_user($wp_user_data['user_login']);

                //At this point, the username or the email is not taken by any existing wp user with admin role.
                //Lets continue the normal registration process.

		//Check if the email belongs to an existing wp user account.
		$wp_user_id = email_exists( $wp_user_data['user_email'] );
		if ( $wp_user_id ) {
			//A wp user account exist with this email.
                        //For signle site WP install, no new user will be created. The existing user ID will be returned.
		} else {
                    //Check if the username belongs to an existing wp user account.
                    $wp_user_id = username_exists( $wp_user_data['user_login'] );
                    if ( $wp_user_id ) {
                        //A wp user account exist with this username.
                        //For signle site WP install, no new user will be created. The existing user ID will be returned.
                    }
                }

		//At this point 1) A WP User with this email or username doesn't exist. Or 2) The associated wp user doesn't have admin role
		//Lets create a new wp user record or attach the SWPM profile to an existing user accordingly.

		if ( self::is_multisite_install() ) {
			//WP Multi-Site install
			global $blog_id;
			if ( $wp_user_id ) {
				//If user exists then just add him to current blog.
				add_existing_user_to_blog(
					array(
						'user_id' => $wp_user_id,
						'role'    => 'subscriber',
					)
				);
				return $wp_user_id;
			}
                        //No existing user. Create a new one.
			$wp_user_id = wpmu_create_user( $wp_user_data['user_login'], $wp_user_data['password'], $wp_user_data['user_email'] );
			$role       = 'subscriber'; //TODO - add user as a subscriber first. The subsequent update user role function to update the role to the correct one
			add_user_to_blog( $blog_id, $wp_user_id, $role );
                        //End of WPMS
		} else {
			//This is a WP Single Site install.

                        //Lets see if an existing WP user exist from the email_exists() or username_exists() check earlier.
			if ( $wp_user_id ) {
                            return $wp_user_id;
			}

                        //No existing user. Try to create a brand new WP user entry.
			$wp_user_id = wp_create_user( $wp_user_data['user_login'], $wp_user_data['password'], $wp_user_data['user_email'] );

                        //Update that newly created user's profile with additional data.
                        $wp_user_data['ID'] = $wp_user_id;
                        wp_update_user( $wp_user_data ); //Core WP function. Updates/Syncs the user info and role.

		}

		return $wp_user_id;
	}

	public static function is_multisite_install() {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			return true;
		} else {
			return false;
		}
	}

	public static function _( $msg ) {
		return __( $msg, 'simple-membership' );
	}

	public static function e( $msg ) {
		_e( $msg, 'simple-membership' );
	}

	/*
	 * Deprecated. Instead use SwpmUtils::has_admin_management_permission()
	 */

	public static function is_admin() {
		//This function returns true if the current user has WordPress admin management permission (not to be mistaken with SWPM admin permission.
		//This function is NOT like the WordPress's is_admin() function which determins if we are on the admin end of the site.
		//TODO - rename this function to something like is_admin_user()
		return current_user_can( 'manage_options' );
	}

	public static function has_admin_management_permission() {
		if ( current_user_can( SWPM_MANAGEMENT_PERMISSION ) ) {
			return true;
		} else {
			return false;
		}
	}

        /*
         * Returns the current date timestamp value suitable for debug log file.
         */
        public static function get_current_timestamp_for_debug_log(){
            $current_wp_time = current_time('mysql');
            $dt = new DateTime($current_wp_time);
            $current_date = $dt->format('Y/m/d H:i:s');
            return $current_date;
        }

        /*
         * Returns the current date value in (Y-m-d) format in the timzeone set for this WordPress install.
         */
        public static function get_current_date_in_wp_zone(){
            $current_wp_time = current_time('mysql');
            $dt = new DateTime($current_wp_time);
            $current_date = $dt->format('Y-m-d');
            return $current_date;
        }

	/*
	 * Formats the given date value according to the WP date format settings. This function is useful for displaying a human readable date value to the user.
	 */
	public static function get_formatted_date_according_to_wp_settings( $date ) {
		$date_format = get_option( 'date_format' );
		if ( empty( $date_format ) ) {
			//WordPress's date form settings is not set. Lets set a default format.
			$date_format = 'Y-m-d';
		}

		$date_obj       = new DateTime( $date );
		$formatted_date = $date_obj->format( $date_format ); //Format the date value using date format settings
		return $formatted_date;
	}

	/*
	 * Formats and Translates the given date value according to the WP date format settings. This function is useful for displaying a human readable date value to the user.
	 * The $date argument value must be in nromal date format (2025-01-15). The function will use strtotime() function to convert it to unix time then use it.
	 */
	public static function get_formatted_and_translated_date_according_to_wp_settings( $date ) {
		$date_format = get_option( 'date_format' );
		if ( empty( $date_format ) ) {
			//WordPress's date form settings is not set. Lets set a default format.
			$date_format = 'Y-m-d';
		}

		$formatted_translated_date = date_i18n( $date_format, strtotime( $date ) );
		return $formatted_translated_date;
	}

	public static function swpm_username_exists( $user_name ) {
		global $wpdb;
		$member_table = $wpdb->prefix . 'swpm_members_tbl';
		$query        = $wpdb->prepare( 'SELECT member_id FROM ' . $member_table . ' WHERE user_name=%s', sanitize_user( $user_name ) );
		return $wpdb->get_var( $query );
	}

	public static function get_free_level() {
		$encrypted = sanitize_text_field( $_POST['level_identifier'] );
		if ( ! empty( $encrypted ) ) {
                    //We already checked using hash that the membership_level value is authentic. Now check the level_identifier against the membership_level.
                    $level_value = SwpmForm::get_membership_level_from_request();
                    $hash_val = md5( $level_value );
                    if ( $hash_val != $encrypted ) {//level_identifier validation failed.
                            $msg  = '<p>Error! Security check failed for membership level identifier validation.</p>';
                            $msg .= '<p>The submitted membership level data does not match.</p>';
                            $msg .= '<p>If you are using caching please empty the cache data and try again.</p>';
                            if ( isset ( $_POST['swpm-fb-submit'] ) ){//Form builder submission potentially
                                $msg .= '<p>If you are using the Form Builder addon, please update the addon and try again.</p>';
                            }
                            wp_die( $msg );
                    }

                    return SwpmPermission::get_instance( $encrypted )->get( 'id' );
		}

		$is_free    = SwpmSettings::get_instance()->get_value( 'enable-free-membership' );
		$free_level = absint( SwpmSettings::get_instance()->get_value( 'free-membership-id' ) );

		return ( $is_free ) ? $free_level : null;
	}

        public static function is_registration_completion_link_invalid(){
            if( self::is_paid_registration() ){
                //We are on the prompt to complete registration link URL. 

				//Check that this is not after the registration form has been submitted (we don't want to show a warning if the form has been submitted just now)
				if( isset( $_REQUEST['swpm_registration_submit'] ) || isset( $_REQUEST['swpm-fb-submit'] )){
					return false;
				}

                //Check if it points to a valid user profile or not.
                $member = SwpmUtils::get_paid_member_info();
                if ( empty($member )){
                    //A member record does not exist. So this link is invalid.
                    return true;
                }
            }
            return false;
        }
        
        public static function is_registration_completion_link_already_used(){
            if( self::is_paid_registration() ){
                //We are on the prompt to complete registration link URL. 

				//Check that this is not after the registration form has been submitted (we don't want to show a warning if the form has been submitted just now)
				if( isset( $_REQUEST['swpm_registration_submit'] ) || isset( $_REQUEST['swpm-fb-submit'] )){
					return false;
				}

                //Check if this link has already been used and the profile setup is already done.
                $member_id = filter_input( INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT );
                $member_record = SwpmMemberUtils::get_user_by_id($member_id);
                if (isset($member_record->user_name) && !empty($member_record->user_name)){
                    //A member record exists with a username value. So this link has been used already. Account is already setup.
                    return true;
                }
            }
            return false;
        }
        
	public static function is_paid_registration() {
		$member_id = filter_input( INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT );
                $code = isset( $_GET['code'] ) ? sanitize_text_field( stripslashes ( $_GET['code'] ) ) : '';
		if ( ! empty( $member_id ) && ! empty( $code ) ) {
			return true;
		}
		return false;
	}

	public static function get_paid_member_info() {
		$member_id = filter_input( INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT );
                $code = isset( $_GET['code'] ) ? sanitize_text_field( stripslashes ( $_GET['code'] ) ) : '';
		global $wpdb;
		if ( ! empty( $member_id ) && ! empty( $code ) ) {
			$query = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE member_id= %d AND reg_code=%s';
			$query = $wpdb->prepare( $query, $member_id, $code );
			return $wpdb->get_row( $query );
		}
		return null;
	}

	public static function get_incomplete_paid_member_info_by_ip() {
		global $wpdb;
		$user_ip = self::get_user_ip_address();
		if ( ! empty( $user_ip ) ) {
			//Lets check if a payment has been confirmed from this user's IP and the profile needs to be completed (where username is empty).
			$username = '';
			$query    = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE last_accessed_from_ip=%s AND user_name=%s ORDER BY member_id DESC';
			$query    = $wpdb->prepare( $query, $user_ip, $username );
			$result   = $wpdb->get_row( $query );
			return $result;
		}
		return null;
	}

	public static function account_delete_confirmation_ui( $msg = '' ) {
		ob_start();
		include SIMPLE_WP_MEMBERSHIP_PATH . 'views/account_delete_warning.php';
		ob_get_flush();
		$title = __( 'Confirm Account Deletion', 'simple-membership' );
		wp_die( '', $title, array( 'back_link' => true ) );
	}

	public static function delete_account_button() {
		$allow_account_deletion = SwpmSettings::get_instance()->get_value( 'allow-account-deletion' );
		if ( empty( $allow_account_deletion ) ) {
			return '';
		}

		$account_delete_link  = '<div class="swpm-profile-account-delete-section">';
		$account_delete_link .= '<a href="' . SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_delete_account=1"><div class="swpm-account-delete-button">' . self::_( 'Delete Account' ) . '</div></a>';
		$account_delete_link .= '</div>';
		return $account_delete_link;
	}

	public static function encrypt_password( $plain_password ) {
		//From WP 6.8 onwards, it is better to use the wp_hash_password() function to hash the password.
		$hashed_password = wp_hash_password($plain_password);

		return $hashed_password;
	}

	public static function get_restricted_image_url() {
		return SIMPLE_WP_MEMBERSHIP_URL . '/images/restricted-icon.png';
	}

	/*
	 * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
	 */

	public static function sanitize_value_by_array( $val_to_check, $valid_values ) {
		$keys = array_keys( $valid_values );
		$keys = array_map( 'strtolower', $keys );
		if ( in_array( $val_to_check, $keys ) ) {
			return $val_to_check;
		}
		return reset( $keys ); //Return he first element from the valid values
	}

        public static function swpm_sanitize_text( $text ) {
                $text = htmlspecialchars( $text );
                $text = strip_tags( $text );
                $text = sanitize_text_field( $text );
                $text = esc_attr( $text );
                return $text;
        }

	public static function get_user_ip_address() {
		$user_ip = '';

		if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$user_ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$user_ip = $_SERVER['REMOTE_ADDR'];
		}

		if ( strstr( $user_ip, ',' ) ) {
			// Return first IP if X-Forwarded-For contains multiple IPs.
			$ip_values = explode( ',', $user_ip );
			$user_ip   = $ip_values['0'];
		}

		return apply_filters( 'swpm_get_user_ip_address', $user_ip );
	}

	public static function is_first_click_free( &$content ) {
		$is_first_click                 = false;
		$args                           = array( $is_first_click, $content );
		$filtered                       = apply_filters( 'swpm_first_click_free', $args );
		list($is_first_click, $content) = $filtered;
		return $is_first_click;
	}

	private static function crypt_fallback( $string, $action = 'e' ) {
		if ( $action === 'e' ) {
			return base64_encode( $string );
		} else {
			return base64_decode( $string );
		}
	}

	public static function crypt( $string, $action = 'e' ) {
		//check if openssl module is enabled
		if ( ! extension_loaded( 'openssl' ) ) {
			// no openssl extension loaded. Can't ecnrypt
			return self::crypt_fallback( $string, $action );
		}
		//check if encrypt method is supported
		$encrypt_method    = 'aes-256-ctr';
		$available_methods = openssl_get_cipher_methods();
		if ( ! in_array( $encrypt_method, $available_methods ) ) {
			// no ecryption method supported. Can't encrypt
			return self::crypt_fallback( $string, $action );
		}

		$output     = false;
		$secret_key = wp_salt( 'auth' );
		$secret_iv  = wp_salt( 'secure_auth' );
		$key        = hash( 'sha256', $secret_key );
		$iv         = substr( hash( 'sha256', $secret_iv ), 0, 16 );

		if ( $action == 'e' ) {
			$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
		} elseif ( $action == 'd' ) {
			$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
		}

		return $output;
	}

        public static function is_rego_form_submitted(){
                if ( isset( $_POST[ 'swpm_registration_submit' ] ) ){
                    //Core plugin's registration form submitted
                    return true;
                }
                
                if ( isset( $_POST[ 'swpm-fb-submit' ] ) ){
                    //Form builder form submission.
                    return true;
                }
                
                return false;
        }
        
        public static function get_fb_rego_email_field_value(){
            if ( !isset($_POST[ 'form_id' ]) ){
                return '';
            }
            $fb_email = '';
            $form_id = absint( $_POST[ 'form_id' ] );
            if ( !empty($form_id) ){
                //This is a form builder form. Get the email address for this custom form.
                if ( !class_exists('SwpmFbForm') ){
                    return '';
                }
                $fb_form = new SwpmFbForm();
                $fb_form->init_by_id( $form_id );
                foreach ( $fb_form->formmeta->fields as $field ){
                    if ( !isset($field->key) || !is_string($field->key)){
                        continue;
                    }
                    if( $field->key == 'primary_email' ){
                        $fb_email = $fb_form->get_field_value($field);
                        break;
                    }
                }
            }
            return $fb_email;
        }
        
	public static function csv_equal_match( $needle, $haystack_csv ) {
		if($haystack_csv && strlen($haystack_csv)>0) {
                        //Converting to lowercase for better matching
			$haystack_csv = strtolower($haystack_csv); 
			$haystack_csv_array = explode(",",$haystack_csv);
	
			foreach($haystack_csv_array as $value) {
				if(trim($needle)==trim($value)) {
					return true;
				}
			}
		}
		return false;
	}

	
	public static function csv_pattern_match( $needle, $haystack_csv ) {
		if($haystack_csv && strlen($haystack_csv)>0) {
                        //For pattern match, we need to check if any of the individual pattern matches with any part/full of the entered user email address.
                        $user_email_address = $needle;//We need to search each pattern entry within this user email address value to see if there is any match.
			$haystack_csv = strtolower($haystack_csv);
			$haystack_csv_array = explode(",",$haystack_csv);
                        foreach($haystack_csv_array as $findme) {
                            $findme = trim($findme);
                            if(stripos($user_email_address, $findme)!==false) {
                                    //Found a match for the pattern.
                                    return true;
                            }
                        }
		}
		return false;
	}

    public static function email_merge_tags()
    {
        return array(
            "first_name" => __("Member's first name", "simple-membership"),
            "last_name" => __("Member's last name", "simple-membership"),
            "email" => __("Member's email address", "simple-membership"),
            "member_id" => __("Member ID", "simple-membership"),
            "user_name" => __("Member's username", "simple-membership"),
            "account_state" => __("Account status", "simple-membership"),
            "membership_level" => __("Membership level ID", "simple-membership"),
            "membership_level_name" => __("Membership level name", "simple-membership"),
            "phone" => __("Phone number (if available)", "simple-membership"),
            "member_since" => __("Member since date", "simple-membership"),
            "subscription_starts" => __("Subscription start date", "simple-membership"),
            "company_name" => __("Company name", "simple-membership"),
            "primary_address" => __("Member's address", "simple-membership"),
            "expiry_date" => __("Member's account expiry date", "simple-membership"),
        );
    }

	public static function get_formatted_payment_gateway_name($gateway){
		switch ($gateway) {
			case 'stripe':
				return 'Stripe (Legacy)';
			case 'stripe-sca':
				return 'Stripe Buy Now';
			case 'stripe-sca-subs':
				return 'Stripe Subscription';
			case 'paypal':
				return 'PayPal Standard';
			case 'paypal_std_sub_checkout':
				return 'PayPal Standard Subscription';
			case 'paypal_buy_now_checkout':
				return 'PayPal Buy Now (PPCP)';
			case 'paypal_subscription_checkout':
				return 'PayPal Subscription (PPCP)';
			case 'braintree':
				return 'Braintree';				
			default:
				return $gateway;
		}
	}

	public static function login_originated_from_swpm_login_form(){
		//Our plugin's login request (Standard login, login after registration, 2FA login, etc.) should have the 'swpm_user_name' parameter.
		//We have also added 'swpm_login_origination_flag' parameter to all our logins to identify the login request origination.
		if ( isset($_REQUEST['swpm_user_name']) ) {
			return true;
		}
		return false;
	}

	/*
	 * Get the shortcode that will be used to display in the admin UI so users can copy it easily.
	 */
	public static function get_shortcode_for_admin_ui_display( $button_id, $size = '' ){
		//Let's ensure the membership level ID for this button is valid.
		$level_id = get_post_meta($button_id, 'membership_level_id', true);
		if(!SwpmUtils::membership_level_id_exists($level_id)){
			//This membership level doesn't exist. Show an error instead of the shortcode.
			$shortcode = '<span style="color:red;">' . __('Error! The membership level you specified in this button does not exist. You may have deleted this level. Edit this button and select a valid membership level.', 'simple-membership') . '</span>';
		} else {
			//If $size is empty, add 'large-text' class and no size attribute (so it adjusts responsively).
			if( empty ($size) ){
				$shortcode = '<input type="text" onfocus="this.select();" readonly="readonly" value="[swpm_payment_button id=&quot;'.esc_attr($button_id).'&quot;]" class="large-text code">';
			} else {
				$shortcode = '<input type="text" onfocus="this.select();" readonly="readonly" value="[swpm_payment_button id=&quot;'.esc_attr($button_id).'&quot;]" class="code" size="'.esc_attr($size).'">';
			}
		}
		return $shortcode;
	}

}
