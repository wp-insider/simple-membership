<?php

class SwpmSelfActionHandler {

    public function __construct() {
        //Register all the self action hooks the plugin needs to handle.

        //Important: The following after registration hooks are handled using a lower priority (20) so that other plugins can do their processing first.
        //This is important because if someone has added some custom code that uses this same hook, we want them to run their code before this code runs and exits (for auto login feature).
        add_action('swpm_front_end_registration_complete_fb', array(&$this, 'after_registration_callback'), 20);//For the form builder
        add_action('swpm_front_end_registration_complete_user_data', array(&$this, 'after_registration_callback'), 20);//For the core plugin

        //Handle membership level change action hook.
        add_action('swpm_membership_level_changed', array(&$this, 'handle_membership_level_changed_action'));

        add_action('swpm_payment_ipn_processed', array(&$this, 'handle_swpm_payment_ipn_processed'));

        add_filter('swpm_after_logout_redirect_url', array(&$this, 'handle_after_logout_redirection'));
        add_filter('swpm_auth_cookie_expiry_value', array(&$this, 'handle_auth_cookie_expiry_value'));

        add_action("swpm_do_init_time_tasks_front_end", array(&$this, 'handle_whitelist_blacklist_registration'));
        add_action("swpm_before_login_request_is_processed", array(&$this, 'handle_whitelist_blacklist_login'));

		add_action('swpm_after_login_authentication', array(&$this, 'handle_login_event_tracking'));
    }

    public function handle_auth_cookie_expiry_value($expire){

        $logout_member_on_browser_close = SwpmSettings::get_instance()->get_value('logout-member-on-browser-close');
        if (!empty($logout_member_on_browser_close)) {
            //This feature is enabled.
            //Setting auth cookie expiry value to 0.
            $expire = apply_filters( 'swpm_logout_on_close_auth_cookie_expiry_value', 0 );
        }

        return $expire;
    }

    public function handle_after_logout_redirection($redirect_url){
        $after_logout_url = SwpmSettings::get_instance()->get_value('after-logout-redirection-url');
        if(!empty($after_logout_url)){
            //After logout URL is being used. Override re-direct URL.
            SwpmLog::log_simple_debug("After logout redirection URL is being used: " . $after_logout_url, true);
            $redirect_url = $after_logout_url;
        }
        return $redirect_url;
    }

    public function handle_swpm_payment_ipn_processed($ipn_data){
        $ipn_forward_url = SwpmSettings::get_instance()->get_value('payment-notification-forward-url');
        if(!empty($ipn_forward_url)){
            SwpmLog::log_simple_debug("Payment Notification Forwarding is Enabled. Posting the payment data to URL: " . $ipn_forward_url, true);
            $response = wp_remote_post($ipn_forward_url, $ipn_data);
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                SwpmLog::log_simple_debug("There was an error posting the payment data. Error message: " . $error_message, true);
            }
        }
    }

    public function after_registration_callback($user_data){

        //Handle auto login after registration if enabled
        $enable_auto_login = SwpmSettings::get_instance()->get_value('auto-login-after-rego');
        if (!empty($enable_auto_login)){
            SwpmLog::log_simple_debug("Auto login after registration feature is enabled in settings. Performing auto login for user: " . $user_data['user_name'], true);
            $login_page_url = SwpmSettings::get_instance()->get_value('login-page-url');

            // Allow hooks to change the value of login_page_url
            $login_page_url = apply_filters('swpm_after_reg_callback_login_page_url', $login_page_url);

            $encoded_pass = base64_encode($user_data['plain_password']);
            $swpm_auto_login_nonce = wp_create_nonce('swpm-auto-login-nonce');
            $arr_params = array(
                'swpm_auto_login' => '1',
                'swpm_user_name' => urlencode($user_data['user_name']),
                'swpm_encoded_pw' => $encoded_pass,
                'swpm_auto_login_nonce' => $swpm_auto_login_nonce,
                'swpm_login_origination_flag' => '1',
            );
            $redirect_page = add_query_arg($arr_params, $login_page_url);
            wp_redirect($redirect_page);
            exit(0);
        }

    }

    public function handle_membership_level_changed_action($args){
        $swpm_id = $args['member_id'];
        $old_level = $args['from_level'];
        $new_level = $args['to_level'];
        SwpmLog::log_simple_debug('swpm_membership_level_changed action triggered. Member ID: '.$swpm_id.', Old Level: '.$old_level.', New Level: '.$new_level, true);

        //Check to see if the old and the new levels are the same or not.
        if(trim($old_level) == trim($new_level)){
            SwpmLog::log_simple_debug('The to (Level ID: '.$new_level.') and from (Level ID: '.$old_level.') values are the same. Nothing to do here.', true);
            return;
        }

        //Find record for this user
        SwpmLog::log_simple_debug('Retrieving user record for member ID: '.$swpm_id, true);
        $resultset = SwpmMemberUtils::get_user_by_id($swpm_id);
        if($resultset){
            //Found a record. Lets do some level update specific changes.
            //$emailaddress  = $resultset->email;
            //$account_status = $resultset->account_state;

            //Retrieve the new memberhsip level's details
            $level_row = SwpmUtils::get_membership_level_row_by_id($new_level);

            //Update the WP user role according to the new level's configuration (if applicable).
            $user_role = $level_row->role;
            $user_info = get_user_by('login', $resultset->user_name);

			if (! $user_info instanceof WP_User){
                $user_info = get_user_by('email', $resultset->email);
			}

			if (!$user_info instanceof WP_User){
				// WP User info could not be retrieved, Can't update user role.
				return;
			}

            $wp_user_id = $user_info->ID;
            SwpmLog::log_simple_debug('Calling user role update function.', true);
            SwpmMemberUtils::update_wp_user_role($wp_user_id, $user_role);
        }

    }

    public function handle_whitelist_blacklist_registration()
    {
        //Check if registration form has been submitted
        if ( !SwpmUtils::is_rego_form_submitted() ){
            return;
        }
        
        //Get the email address
        $user_email = '';
        if ( isset ($_POST["email"]) ){
            //Core plugin's rego form
            $user_email = SwpmMemberUtils::get_sanitized_email($_POST["email"]);
        } else {
            $fb_email = SwpmUtils::get_fb_rego_email_field_value();
            if ( !empty($fb_email) ){
                //Form builder's rego form
                $user_email = SwpmMemberUtils::get_sanitized_email($fb_email);
            }
        }
        if( empty($user_email) ) {
            return;
        }

        //Check whitelsting and blacklisting settings
        if(SwpmSettings::get_instance()->get_value( 'enable-whitelisting' )) {
            $is_whitelisted=false;

            $emailaddress_whitelist = SwpmSettings::get_instance()->get_value( 'whitelist-email-address' );                        
            if($emailaddress_whitelist) {
                if(SwpmUtils::csv_equal_match($user_email,$emailaddress_whitelist)) {
                    $is_whitelisted=true;
                    return;
                }
            }

            $emailaddress_pattern_whitelist = SwpmSettings::get_instance()->get_value( 'whitelist-email-address-pattern' );
            if($emailaddress_pattern_whitelist) {
                if(SwpmUtils::csv_pattern_match($user_email,$emailaddress_pattern_whitelist)) {
                    $is_whitelisted=true;
                    return;
                }
            }

            //Trigger a filter hook so it can be overriden from an addon
            $is_whitelisted = apply_filters( 'swpm_email_not_whitelisted_before_registration_block', $is_whitelisted, $user_email);

            //If whitelisting is enabled and the user email doesn't match any whitelisting rule.
            //Block the registration
            if($is_whitelisted==false) {
                $block_message_whitelist = SwpmSettings::get_instance()->get_value( 'whitelist-block-message' );
                if( !isset($block_message_whitelist) || empty($block_message_whitelist) ) {
                    $block_message_whitelist = SwpmUtils::_("The email address you used is not whitelisted on this site.");
                }

                SwpmLog::log_simple_debug( 'Registration blocked for user: '.$user_email.', as it did not match any whitelisting rule.', true );
                wp_die($block_message_whitelist);  
            }
        }

        if(SwpmSettings::get_instance()->get_value( 'enable-blacklisting' )) {
            $block_message = SwpmSettings::get_instance()->get_value( 'blacklist-block-message' );
            if(!$block_message) {                
                $block_message = SwpmUtils::_("The email address you used is blacklisted on this site.");
            }

            $emailaddress_blacklist = SwpmSettings::get_instance()->get_value( 'blacklist-email-address' );                        
            if($emailaddress_blacklist) {
                if(SwpmUtils::csv_equal_match($user_email,$emailaddress_blacklist)) {
                    SwpmLog::log_simple_debug( 'Login blocked for user: '.$user_email.' from the Blacklist Email Address List.', true );
                    wp_die($block_message);                    
                }
            }

            $emailaddress_pattern_blacklist = SwpmSettings::get_instance()->get_value( 'blacklist-email-address-pattern' );
            if($emailaddress_pattern_blacklist) {
                if(SwpmUtils::csv_pattern_match($user_email,$emailaddress_pattern_blacklist)) {
                    SwpmLog::log_simple_debug( 'Login blocked for user: '.$user_email.' from the Blacklist Email Address Pattern List.', true );
                    wp_die($block_message);                    
                }
            }
        }
        return;
    }
    
    public function handle_whitelist_blacklist_login($args)
    {
        $user_email = SwpmMemberUtils::get_sanitized_email($args["username"]);
        if(!$user_email) {
            return;
        }

        if(SwpmSettings::get_instance()->get_value( 'enable-whitelisting' )) {
            $is_whitelisted=false;

            $emailaddress_whitelist = SwpmSettings::get_instance()->get_value( 'whitelist-email-address' );                        
            if($emailaddress_whitelist) {
                if(SwpmUtils::csv_equal_match($user_email,$emailaddress_whitelist)) {
                    $is_whitelisted=true;
                    return;
                }
            }

            $emailaddress_pattern_whitelist = SwpmSettings::get_instance()->get_value( 'whitelist-email-address-pattern' );
            if($emailaddress_pattern_whitelist) {
                if(SwpmUtils::csv_pattern_match($user_email,$emailaddress_pattern_whitelist)) {
                    $is_whitelisted=true;
                    return;
                }
            }

            //Trigger a filter hook so it can be overriden from an addon
            $is_whitelisted = apply_filters( 'swpm_email_not_whitelisted_before_login_block', $is_whitelisted, $user_email);
            
            //If whitelist is enabled and user email doesn't match any whitelist rule.
            //Block the login
            if($is_whitelisted==false) {
                $block_message_whitelist = SwpmSettings::get_instance()->get_value( 'whitelist-block-message' );
                if( !isset($block_message_whitelist) || empty($block_message_whitelist) ) {
                    $block_message_whitelist = SwpmUtils::_("The email address you used is not whitelisted on this site.");
                }

                SwpmLog::log_simple_debug( 'Login blocked for user: '.$user_email.', as it did not match any whitelisting rule.', true );
                wp_die($block_message_whitelist);  
            }
        }

        if(SwpmSettings::get_instance()->get_value( 'enable-blacklisting' )) {
            $block_message = SwpmSettings::get_instance()->get_value( 'blacklist-block-message' );
            if(!$block_message) {
                $block_message = SwpmUtils::_("The email address you used is blacklisted on this site.");
            }

            $emailaddress_blacklist = SwpmSettings::get_instance()->get_value( 'blacklist-email-address' );
            if($emailaddress_blacklist) {
                if(SwpmUtils::csv_equal_match($user_email,$emailaddress_blacklist)) {
                    SwpmLog::log_simple_debug( 'Login blocked for user: '.$user_email.' from the Blacklist Email Address List.', true );
                    wp_die($block_message);                    
                }
            }

            $emailaddress_pattern_blacklist = SwpmSettings::get_instance()->get_value( 'blacklist-email-address-pattern' );
            if($emailaddress_pattern_blacklist) {
                if(SwpmUtils::csv_pattern_match($user_email,$emailaddress_pattern_blacklist)) {
                    SwpmLog::log_simple_debug( 'Login blocked for user: '.$user_email.' from the Blacklist Email Address Pattern List.', true );
                    wp_die($block_message);                    
                }
            }
        }
        return;
    }

	public function handle_login_event_tracking( $user ) {
		$settings = SwpmSettings::get_instance();
		$enable_login_event_tracking = $settings->get_value('enable_login_event_tracking');

		if ( $enable_login_event_tracking ){
			SwpmEventLogger::track_login_event( $user );
		}
	}
}