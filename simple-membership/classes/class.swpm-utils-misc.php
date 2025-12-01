<?php

class SwpmMiscUtils {

	public static function create_mandatory_wp_pages() {
		$settings = SwpmSettings::get_instance();

		//Create join us page
		$swpm_join_page_content  = '<p style="color:red;font-weight:bold;">This page and the content has been automatically generated for you to give you a basic idea of how a "Join Us" page should look like. You can customize this page however you like it by editing this page from your WordPress page editor.</p>';
		$swpm_join_page_content .= '<p style="font-weight:bold;">If you change the URL of this page, make sure to update the URL value in the settings menu of the plugin.</p>';
		$swpm_join_page_content .= '<p style="font-weight:bold;">If you delete any of the essential pages required by the plugin, <a href="https://simple-membership-plugin.com/recreating-required-pages-simple-membership-plugin/" target="_blank">this documentation</a> will guide you in recreating them.</p>';
		$swpm_join_page_content .= '<p style="border-top:1px solid #ccc;padding-top:10px;margin-top:10px;"></p>
			<strong>Free Membership</strong>
			<br />
			You get unlimited access to free membership content
			<br />
			<em><strong>Price: Free!</strong></em>
			<br /><br />Link the following image to go to the Registration Page if you want your visitors to be able to create a free membership account<br /><br />
			<img title="Join Now" src="' . SIMPLE_WP_MEMBERSHIP_URL . '/images/join-now-button-image.gif" alt="Join Now Button" width="277" height="82" />
			<p style="border-bottom:1px solid #ccc;padding-bottom:10px;margin-bottom:10px;"></p>';
		$swpm_join_page_content .= '<p><strong>You can register for a Free Membership or pay for one of the following membership options</strong></p>';
		$swpm_join_page_content .= '<p style="border-top:1px solid #ccc;padding-top:10px;margin-top:10px;"></p>
			[ ==> Insert Payment Button For Your Paid Membership Levels Here <== ]
			<p style="border-bottom:1px solid #ccc;padding-bottom:10px;margin-bottom:10px;"></p>';

		$swpm_join_page = array(
			'post_title'     => 'Join Us',
			'post_name'      => 'membership-join',
			'post_content'   => $swpm_join_page_content,
			'post_parent'    => 0,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);

		$join_page_obj = get_page_by_path( 'membership-join' );
		if ( ! $join_page_obj ) {
			$join_page_id = wp_insert_post( $swpm_join_page );
		} else {
			$join_page_id = $join_page_obj->ID;
			if ( $join_page_obj->post_status == 'trash' ) { //For cases where page may be in trash, bring it out of trash
				wp_update_post(
					array(
						'ID'          => $join_page_obj->ID,
						'post_status' => 'publish',
					)
				);
			}
		}
		$swpm_join_page_permalink = get_permalink( $join_page_id );
		$settings->set_value( 'join-us-page-url', $swpm_join_page_permalink );

		//Create registration page
		$swpm_rego_page = array(
			'post_title'     => SwpmUtils::_( 'Registration' ),
			'post_name'      => 'membership-registration',
			'post_content'   => '[swpm_registration_form]',
			'post_parent'    => $join_page_id,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$rego_page_obj  = get_page_by_path( 'membership-registration' );
		if ( ! $rego_page_obj ) {
			$rego_page_id = wp_insert_post( $swpm_rego_page );
		} else {
			$rego_page_id = $rego_page_obj->ID;
			if ( $rego_page_obj->post_status == 'trash' ) { //For cases where page may be in trash, bring it out of trash
				wp_update_post(
					array(
						'ID'          => $rego_page_obj->ID,
						'post_status' => 'publish',
					)
				);
			}
		}
		$swpm_rego_page_permalink = get_permalink( $rego_page_id );
		$settings->set_value( 'registration-page-url', $swpm_rego_page_permalink );

		//Create login page
		$swpm_login_page = array(
			'post_title'     => SwpmUtils::_( 'Member Login' ),
			'post_name'      => 'membership-login',
			'post_content'   => '[swpm_login_form]',
			'post_parent'    => 0,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$login_page_obj  = get_page_by_path( 'membership-login' );
		if ( ! $login_page_obj ) {
			$login_page_id = wp_insert_post( $swpm_login_page );
		} else {
			$login_page_id = $login_page_obj->ID;
			if ( $login_page_obj->post_status == 'trash' ) { //For cases where page may be in trash, bring it out of trash
				wp_update_post(
					array(
						'ID'          => $login_page_obj->ID,
						'post_status' => 'publish',
					)
				);
			}
		}
		$swpm_login_page_permalink = get_permalink( $login_page_id );
		$settings->set_value( 'login-page-url', $swpm_login_page_permalink );

		//Create profile page
		$swpm_profile_page = array(
			'post_title'     => SwpmUtils::_( 'Profile' ),
			'post_name'      => 'membership-profile',
			'post_content'   => '[swpm_profile_form]',
			'post_parent'    => $login_page_id,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$profile_page_obj  = get_page_by_path( 'membership-profile' );
		if ( ! $profile_page_obj ) {
			$profile_page_id = wp_insert_post( $swpm_profile_page );
		} else {
			$profile_page_id = $profile_page_obj->ID;
			if ( $profile_page_obj->post_status == 'trash' ) { //For cases where page may be in trash, bring it out of trash
				wp_update_post(
					array(
						'ID'          => $profile_page_obj->ID,
						'post_status' => 'publish',
					)
				);
			}
		}
		$swpm_profile_page_permalink = get_permalink( $profile_page_id );
		$settings->set_value( 'profile-page-url', $swpm_profile_page_permalink );

		//Create password reset page
		$swpm_reset_page = array(
			'post_title'     => SwpmUtils::_( 'Password Reset' ),
			'post_name'      => 'password-reset',
			'post_content'   => '[swpm_reset_form]',
			'post_parent'    => $login_page_id,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$reset_page_obj  = get_page_by_path( 'password-reset' );
		if ( ! $reset_page_obj ) {
			$reset_page_id = wp_insert_post( $swpm_reset_page );
		} else {
			$reset_page_id = $reset_page_obj->ID;
			if ( $reset_page_obj->post_status == 'trash' ) { //For cases where page may be in trash, bring it out of trash
				wp_update_post(
					array(
						'ID'          => $reset_page_obj->ID,
						'post_status' => 'publish',
					)
				);
			}
		}
		$swpm_reset_page_permalink = get_permalink( $reset_page_id );
		$settings->set_value( 'reset-page-url', $swpm_reset_page_permalink );

		//Create a default Thank You page
		$swpm_thank_you_page = array(
			'post_title'     => SwpmUtils::_( 'Thank You' ),
			'post_name'      => 'thank-you',
			'post_content'   => '[swpm_thank_you_page_registration]',
			'post_parent'    => 0,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$thank_you_page_obj  = get_page_by_path( 'thank-you' );
		if ( ! $thank_you_page_obj ) {
			$thank_you_page_id = wp_insert_post( $swpm_thank_you_page );
		} else {
			$thank_you_page_id = $thank_you_page_obj->ID;
			if ( $thank_you_page_obj->post_status == 'trash' ) { //For cases where page may be in trash, bring it out of trash
				wp_update_post(
					array(
						'ID'          => $thank_you_page_obj->ID,
						'post_status' => 'publish',
					)
				);
			}
		}
		$swpm_thank_you_page_permalink = get_permalink( $thank_you_page_id );
		$settings->set_value( 'thank-you-page-url', $swpm_thank_you_page_permalink );

		//Save all settings object changes
		$settings->save(); 
	}

	public static function redirect_to_url( $url ) {
		if ( empty( $url ) ) {
			return;
		}
		$url = apply_filters( 'swpm_redirect_to_url', $url );

		if ( ! preg_match( '/http/', $url ) ) {//URL value is incorrect
			echo '<p>Error! The URL value you entered in the plugin configuration is incorrect.</p>';
			echo '<p>A URL must always have the "http" keyword in it.</p>';
			echo '<p style="font-weight: bold;">The URL value you currently configured is: <br />' . $url . '</p>';
			echo '<p>Here are some examples of correctly formatted URL values for your reference: <br />http://www.example.com<br/>http://example.com<br />https://www.example.com</p>';
			echo '<p>Find the field where you entered this incorrect URL value and correct the mistake then try again.</p>';
			exit;
		}
		if ( ! headers_sent() ) {
			header( 'Location: ' . $url );
		} else {
			echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
		}
		exit;
	}

	public static function show_temporary_message_then_redirect( $msg, $redirect_url, $timeout = 5 ) {
		$timeout       = absint( $timeout );
		$redirect_html = sprintf( '<meta http-equiv="refresh" content="%d; url=\'%s\'" />', $timeout, $redirect_url );
		$redir_msg     = SwpmUtils::_( 'You will be automatically redirected in a few seconds. If not, please %s.' );
		$redir_msg     = sprintf( $redir_msg, '<a href="' . $redirect_url . '">' . SwpmUtils::_( 'click here' ) . '</a>' );

		$msg   = $msg . '<br/><br/>' . $redir_msg . $redirect_html;
		$title = SwpmUtils::_( 'Action Status' );
		wp_die( $msg, $title );
	}

	public static function get_current_page_url() {
		$pageURL = 'http';

        if ( isset( $_SERVER['SCRIPT_URI'] ) && ! empty( $_SERVER['SCRIPT_URI'] ) ) {
			$pageURL = $_SERVER['SCRIPT_URI'];
            $pageURL = str_replace(':443', '', $pageURL);//remove any port number from the URL value (some hosts include the port number with this).
			$pageURL = apply_filters( 'swpm_get_current_page_url_filter', $pageURL );
			return $pageURL;
		}

		//Check if 'SERVER_NAME' is set. If not, try get the URL from WP.
		if( !isset( $_SERVER['SERVER_NAME'] ) ) {
			global $wp;
			if( is_object( $wp ) && isset( $wp->request ) ){
				//Try to get the URL from WP
				$pageURL = home_url( add_query_arg( array(), $wp->request ) );
				$pageURL = apply_filters( 'swpm_get_current_page_url_filter', $pageURL );
				return $pageURL;				
			}
		}

		//Construct the URL value from the $_SERVER array values.
		if ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' ) ) {
			$pageURL .= 's';
		}
		$pageURL .= '://';
		if ( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) && ( $_SERVER['SERVER_PORT'] != '443' ) ) {
			$pageURL .= ltrim( $_SERVER['SERVER_NAME'], '.*' ) . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= ltrim( $_SERVER['SERVER_NAME'], '.*' ) . $_SERVER['REQUEST_URI'];
		}

		//Clean any known port numbers from the URL (some hosts may include these port numbers).
		$pageURL = str_replace(':8080', '', $pageURL);
		
		//Trigger filter 
		$pageURL = apply_filters( 'swpm_get_current_page_url_filter', $pageURL );

		return $pageURL;
	}

	/**
	 * This function will return the URL to redirect the user to after a payment is completed.
	 * It will check the button configuration and the user's registration status to determine the appropriate return URL.
	 */
	public static function get_after_payment_redirect_url( $button_id ) {
		//Ensure the button ID is valid.
		if ( empty( $button_id ) ) {
			SwpmLog::log_simple_debug( 'Payment button id not provided. Redirecting to home url.', false );
			return SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
		}

		$settings = SwpmSettings::get_instance();
		$incomplete_member_data = SwpmUtils::get_incomplete_paid_member_info_by_ip();

		//Check if the regdirect to paid registration link option is enabled for this button.
		$redirect_to_paid_reg_link_after_payment = get_post_meta($button_id, 'redirect_to_paid_reg_link_after_payment', true);

		//Get the thank you page URL from the button settings or the default thank you page URL (from general settings).
		$thank_you_page_url = get_post_meta( $button_id, 'return_url', true );
		if( empty( $thank_you_page_url ) ){
			$thank_you_page_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
		}
		//Note: in the future when we support only the PPCP and Stripe payment methods, we will be able to fallback to the default thank you page URL (of general settings) if the button does not have a thank you page URL configured.

		if( SwpmMemberUtils::is_member_logged_in() ){
			//User is already logged in. The appropriate return URL is the thank you page URL.
			SwpmLog::log_simple_debug( 'User is already logged in. The appropriate return URL is the thank you page URL.', true );
			$return_url = $thank_you_page_url;
		} else if ( !empty( $redirect_to_paid_reg_link_after_payment ) && $incomplete_member_data ){
			//Found a member profile record for this IP that needs to be completed & the redirect to paid registration link option is enabled for this button.
			//The appropriate return URL is the unique registration completion URL.
			$reg_page_url = $settings->get_value( 'registration-page-url' );
			$unique_rego_complete_url = add_query_arg(
				array(
					'member_id' => $incomplete_member_data->member_id,
					'code' => $incomplete_member_data->reg_code,
				),
				$reg_page_url
			);
			SwpmLog::log_simple_debug( 'The condition to redirect to the paid registration link has been met. Setting the return URL to the paid registration link.', true );
			$return_url = $unique_rego_complete_url;
		} else {
			//The user is not logged in and the above conditions are not met. 
			//The return URL is the thank you page URL.
			$return_url = $thank_you_page_url;
		}

		return $return_url;
	}

	public static function handle_after_payment_redirect( $button_id ){
		//This function is called after a payment is completed. It will redirect the user to the thank you page or the paid registration link (based on the settings).

		$redirect_url = self::get_after_payment_redirect_url($button_id);

		SwpmLog::log_simple_debug( 'Redirecting the user to: ' . $redirect_url, true );

		self::redirect_to_url($redirect_url);
	}

	/*
	 * This is an alternative to the get_current_page_url() function. It needs to be tested on many different server conditions before it can be utilized
	 */
	public static function get_current_page_url_alt() {
		$url_parts          = array();
		$url_parts['proto'] = 'http';

		if ( isset( $_SERVER['SCRIPT_URI'] ) && ! empty( $_SERVER['SCRIPT_URI'] ) ) {
			return $_SERVER['SCRIPT_URI'];
		}

		if ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' ) ) {
			$url_parts['proto'] = 'https';
		}

		$url_parts['port'] = '';
		if ( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) && ( $_SERVER['SERVER_PORT'] != '443' ) ) {
			$url_parts['port'] = $_SERVER['SERVER_PORT'];
		}

		$url_parts['domain'] = ltrim( $_SERVER['SERVER_NAME'], '.*' );
		$url_parts['uri']    = $_SERVER['REQUEST_URI'];

		$url_parts = apply_filters( 'swpm_get_current_page_url_alt_filter', $url_parts );

		$pageURL = sprintf( '%s://%s%s%s', $url_parts['proto'], $url_parts['domain'], ! empty( $url_parts['port'] ) ? ':' . $url_parts['port'] : '', $url_parts['uri'] );

		return $pageURL;
	}

	/*
	 * Returns just the domain name. Something like example.com
	 */

	public static function get_home_url_without_http_and_www() {
		$site_url = get_site_url();
		$parse    = parse_url( $site_url );
		$site_url = $parse['host'];
		$site_url = str_replace( 'https://', '', $site_url );
		$site_url = str_replace( 'http://', '', $site_url );
		if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $site_url, $regs ) ) {
			$site_url = $regs['domain'];
		}
		return $site_url;
	}

	public static function replace_dynamic_tags( $msg_body, $member_id, $additional_args = '' ) {
		$settings    = SwpmSettings::get_instance();
		$user_record = SwpmMemberUtils::get_user_by_id( $member_id );

		$password = '';
		$password_reset_link = '';
		$reg_link = '';
		$subscription_id = '';
		if ( ! empty( $additional_args ) ) {
			$password = isset( $additional_args['password'] ) ? $additional_args['password'] : $password;
			$reg_link = isset( $additional_args['reg_link'] ) ? $additional_args['reg_link'] : $reg_link;
			$password_reset_link = isset( $additional_args['password_reset_link'] ) ? $additional_args['password_reset_link'] : $password_reset_link;
			$subscription_id = isset($additional_args['subscription_id']) ? $additional_args['subscription_id'] : '';
		}
		
		$login_link = $settings->get_value( 'login-page-url' );

		//Construct the primary address value
		$primary_address = '';
		if ( ! empty( $user_record->address_street ) && ! empty( $user_record->address_city ) ) {
			//An address value is present.
			$primary_address .= $user_record->address_street;
			$primary_address .= "\n" . $user_record->address_city;
			if ( ! empty( $user_record->address_state ) ) {
				$primary_address .= ' ' . $user_record->address_state;
			}
			if ( ! empty( $user_record->address_zipcode ) ) {
				$primary_address .= ' ' . $user_record->address_zipcode;
			}
			if ( ! empty( $user_record->country ) ) {
				$primary_address .= "\n" . $user_record->country;
			}
		}

		$membership_level_name = SwpmMembershipLevelUtils::get_membership_level_name_of_a_member( $member_id );
		//Format some field values
		$member_since_formatted = SwpmUtils::get_formatted_date_according_to_wp_settings( $user_record->member_since );
		$subsc_starts_formatted = SwpmUtils::get_formatted_date_according_to_wp_settings( $user_record->subscription_starts );

		$expiry_date = SwpmMemberUtils::get_formatted_expiry_date_by_user_id( $member_id );

		//Define the replacable tags
		$tags = array(
			'{member_id}',
			'{user_name}',
			'{first_name}',
			'{last_name}',
			'{membership_level}',
			'{membership_level_name}',
			'{account_state}',
			'{email}',
			'{phone}',
			'{member_since}',
			'{subscription_starts}',
			'{company_name}',
			'{password}',
			'{login_link}',
			'{reg_link}',
			'{primary_address}',
			'{password_reset_link}',
			'{subscription_id}',
			'{expiry_date}',
		);

		//Define the values
		$vals = array(
			$member_id,
			$user_record->user_name,
			$user_record->first_name,
			$user_record->last_name,
			$user_record->membership_level,
			$membership_level_name,
			$user_record->account_state,
			$user_record->email,
			$user_record->phone,
			$member_since_formatted,
			$subsc_starts_formatted,
			$user_record->company_name,
			$password,			
			$login_link,
			$reg_link,
			$primary_address,
			$password_reset_link,
			$subscription_id,
			$expiry_date,
		);
		
		$msg_body = str_replace( $tags, $vals, $msg_body );

		//Allow any addons to add their own custom tags.
		$msg_body = apply_filters( 'swpm_replace_dynamic_tags', $msg_body, $member_id, $additional_args );
		
		return $msg_body;
	}

	public static function get_login_link() {
		$swpm_settings = SwpmSettings::get_instance();
		$login_url = $swpm_settings->get_value( 'login-page-url' );
		$joinus_url = $swpm_settings->get_value( 'join-us-page-url' );
		$hide_join_us_link_enabled = $swpm_settings->get_value('hide-join-us-link');

		if ( empty( $login_url ) || empty( $joinus_url ) ) {
			return '<span style="color:red;">Simple Membership is not configured correctly. The login page or the join us page URL is missing in the settings configuration. '
					. 'Please contact <a href="mailto:' . get_option( 'admin_email' ) . '">Admin</a>';
		}

		//Create the login/protection message
		$filtered_login_url = apply_filters( 'swpm_get_login_link_url', $login_url ); //Addons can override the login URL value using this filter.
		$login_msg = '';
		$login_msg .= SwpmUtils::_( 'Please' ) . ' <a class="swpm-login-link" href="' . $filtered_login_url . '">' . SwpmUtils::_( 'Log In' ) . '</a>. ';

		if (empty($hide_join_us_link_enabled)){
			//Show the join us option
			$login_msg .= SwpmUtils::_( 'Not a Member?' ) . ' <a href="' . $joinus_url . '">' . SwpmUtils::_( 'Join Us' ) . '</a>';
		}
		return $login_msg;
	}

	public static function get_renewal_link() {
		$renewal = SwpmSettings::get_instance()->get_value( 'renewal-page-url' );
		if ( empty( $renewal ) ) {
			//No renewal page is configured so don't show any renewal page link. It is okay to have no renewal page configured.
			return '';
		}
		return SwpmUtils::_( 'Please' ) . ' <a class="swpm-renewal-link" href="' . $renewal . '">' . SwpmUtils::_( 'renew' ) . '</a> ' . SwpmUtils::_( ' your account to gain access to this content.' );
	}

	public static function compare_url_without_http( $url1, $url2 ) {
		$url1 = str_replace( 'http://', '', $url1 );
		$url1 = str_replace( 'https://', '', $url1 );
		$url2 = str_replace( 'http://', '', $url2 );
		$url2 = str_replace( 'https://', '', $url2 );
		return self::compare_url( $url1, $url2 );
	}

	public static function compare_url( $url1, $url2 ) {
		//See also compare_url_without_http() method above.
		
		$url1 = trailingslashit( strtolower( $url1 ) );
		$url2 = trailingslashit( strtolower( $url2 ) );
		if ( $url1 == $url2 ) {
			return true;
		}

		$url1 = parse_url( $url1 );
		$url2 = parse_url( $url2 );

		$components = array( 'scheme', 'host', 'port', 'path' );

		foreach ( $components as $key => $value ) {
			if ( ! isset( $url1[ $value ] ) && ! isset( $url2[ $value ] ) ) {
				continue;
			}

			if ( ! isset( $url2[ $value ] ) ) {
				return false;
			}
			if ( ! isset( $url1[ $value ] ) ) {
				return false;
			}

			if ( $url1[ $value ] != $url2[ $value ] ) {
				return false;
			}
		}

		if ( ! isset( $url1['query'] ) && ! isset( $url2['query'] ) ) {
			return true;
		}

		if ( ! isset( $url2['query'] ) ) {
			return false;
		}
		if ( ! isset( $url1['query'] ) ) {
			return false;
		}

		return strpos( $url1['query'], $url2['query'] ) || strpos( $url2['query'], $url1['query'] );
	}

	public static function is_swpm_admin_page() {
		if ( isset( $_GET['page'] ) && ( stripos( $_GET['page'], 'simple_wp_membership' ) !== false ) ) {
			//This is an admin page of the SWPM plugin
			return true;
		}
		return false;
	}

	public static function check_user_permission_and_is_admin( $action_name ) {
		//Check we are on the admin end
		if ( ! is_admin() ) {
			//Error! This is not on the admin end. This can only be done from the admin side
			wp_die( SwpmUtils::_( 'Error! This action (' . $action_name . ') can only be done from admin end.' ) );
		}

		//Check user has management permission
		if ( ! current_user_can( SWPM_MANAGEMENT_PERMISSION ) ) {
			//Error! Only management users can do this
			wp_die( SwpmUtils::_( 'Error! This action (' . $action_name . ') can only be done by an user with management permission.' ) );
		}
	}

	public static function format_raw_content_for_front_end_display( $raw_content ) {
		$formatted_content = wptexturize( $raw_content );
		$formatted_content = convert_smilies( $formatted_content );
		$formatted_content = convert_chars( $formatted_content );
		$formatted_content = wpautop( $formatted_content );
		$formatted_content = shortcode_unautop( $formatted_content );
		$formatted_content = prepend_attachment( $formatted_content );
		$formatted_content = capital_P_dangit( $formatted_content );
		$formatted_content = do_shortcode( $formatted_content );
                $formatted_content = do_blocks( $formatted_content );

                $formatted_content = apply_filters('swpm_format_raw_content_for_front_end_display', $formatted_content);

		return $formatted_content;
	}

	public static function get_country_name_by_country_code( $country_code ) {
		$countries = array (
			'AW' => 'Aruba',
			'AF' => 'Afghanistan',
			'AO' => 'Angola',
			'AL' => 'Albania',
			'AD' => 'Andorra',
			'AE' => 'United Arab Emirates',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AS' => 'American Samoa',
			'AG' => 'Antigua and Barbuda',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BI' => 'Burundi',
			'BE' => 'Belgium',
			'BJ' => 'Benin',
			'BF' => 'Burkina Faso',
			'BD' => 'Bangladesh',
			'BG' => 'Bulgaria',
			'BH' => 'Bahrain',
			'BS' => 'Bahamas, The',
			'BA' => 'Bosnia and Herzegovina',
			'BY' => 'Belarus',
			'BZ' => 'Belize',
			'BM' => 'Bermuda',
			'BO' => 'Bolivia',
			'BR' => 'Brazil',
			'BB' => 'Barbados',
			'BN' => 'Brunei Darussalam',
			'BT' => 'Bhutan',
			'BW' => 'Botswana',
			'CF' => 'Central African Republic',
			'CA' => 'Canada',
			'CH' => 'Switzerland',
			'JG' => 'Channel Islands',
			'CL' => 'Chile',
			'CN' => 'China',
			'CI' => 'Cote d\'Ivoire',
			'CM' => 'Cameroon',
			'CD' => 'Congo, Dem. Rep.',
			'CG' => 'Congo, Rep.',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CV' => 'Cabo Verde',
			'CR' => 'Costa Rica',
			'CU' => 'Cuba',
			'CW' => 'Curacao',
			'KY' => 'Cayman Islands',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DE' => 'Germany',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DK' => 'Denmark',
			'DO' => 'Dominican Republic',
			'DZ' => 'Algeria',
			'EC' => 'Ecuador',
			'EG' => 'Egypt, Arab Rep.',
			'ER' => 'Eritrea',
			'ES' => 'Spain',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FI' => 'Finland',
			'FJ' => 'Fiji',
			'FR' => 'France',
			'FO' => 'Faroe Islands',
			'FM' => 'Micronesia, Fed. Sts.',
			'GA' => 'Gabon',
			'GB' => 'United Kingdom',
			'GE' => 'Georgia',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GN' => 'Guinea',
			'GM' => 'Gambia, The',
			'GW' => 'Guinea-Bissau',
			'GQ' => 'Equatorial Guinea',
			'GR' => 'Greece',
			'GD' => 'Grenada',
			'GL' => 'Greenland',
			'GT' => 'Guatemala',
			'GU' => 'Guam',
			'GY' => 'Guyana',
			'HK' => 'Hong Kong SAR, China',
			'HN' => 'Honduras',
			'HR' => 'Croatia',
			'HT' => 'Haiti',
			'HU' => 'Hungary',
			'ID' => 'Indonesia',
			'IM' => 'Isle of Man',
			'IN' => 'India',
			'IE' => 'Ireland',
			'IR' => 'Iran, Islamic Rep.',
			'IQ' => 'Iraq',
			'IS' => 'Iceland',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JO' => 'Jordan',
			'JP' => 'Japan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KG' => 'Kyrgyz Republic',
			'KH' => 'Cambodia',
			'KI' => 'Kiribati',
			'KN' => 'St. Kitts and Nevis',
			'KR' => 'Korea, Rep.',
			'KW' => 'Kuwait',
			'LA' => 'Lao PDR',
			'LB' => 'Lebanon',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LC' => 'St. Lucia',
			'LI' => 'Liechtenstein',
			'LK' => 'Sri Lanka',
			'LS' => 'Lesotho',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'LV' => 'Latvia',
			'MO' => 'Macao SAR, China',
			'MF' => 'St. Martin (French part)',
			'MA' => 'Morocco',
			'MC' => 'Monaco',
			'MD' => 'Moldova',
			'MG' => 'Madagascar',
			'MV' => 'Maldives',
			'MX' => 'Mexico',
			'MH' => 'Marshall Islands',
			'MK' => 'Macedonia, FYR',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MM' => 'Myanmar',
			'ME' => 'Montenegro',
			'MN' => 'Mongolia',
			'MP' => 'Northern Mariana Islands',
			'MZ' => 'Mozambique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'NA' => 'Namibia',
			'NC' => 'New Caledonia',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NI' => 'Nicaragua',
			'NL' => 'Netherlands',
			'NO' => 'Norway',
			'NP' => 'Nepal',
			'NR' => 'Nauru',
			'NZ' => 'New Zealand',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PA' => 'Panama',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PW' => 'Palau',
			'PG' => 'Papua New Guinea',
			'PL' => 'Poland',
			'PR' => 'Puerto Rico',
			'KP' => 'Korea, Dem. People’s Rep.',
			'PT' => 'Portugal',
			'PY' => 'Paraguay',
			'PS' => 'West Bank and Gaza',
			'PF' => 'French Polynesia',
			'QA' => 'Qatar',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'SA' => 'Saudi Arabia',
			'SD' => 'Sudan',
			'SN' => 'Senegal',
			'SG' => 'Singapore',
			'SB' => 'Solomon Islands',
			'SL' => 'Sierra Leone',
			'SV' => 'El Salvador',
			'SM' => 'San Marino',
			'SO' => 'Somalia',
			'RS' => 'Serbia',
			'SS' => 'South Sudan',
			'ST' => 'Sao Tome and Principe',
			'SR' => 'Suriname',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SE' => 'Sweden',
			'SZ' => 'Swaziland',
			'SX' => 'Sint Maarten (Dutch part)',
			'SC' => 'Seychelles',
			'SY' => 'Syrian Arab Republic',
			'TC' => 'Turks and Caicos Islands',
			'TD' => 'Chad',
			'TG' => 'Togo',
			'TH' => 'Thailand',
			'TJ' => 'Tajikistan',
			'TM' => 'Turkmenistan',
			'TL' => 'Timor-Leste',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TV' => 'Tuvalu',
			'TW' => 'Taiwan, China',
			'TZ' => 'Tanzania',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'UY' => 'Uruguay',
			'US' => 'United States',
			'UZ' => 'Uzbekistan',
			'VC' => 'St. Vincent and the Grenadines',
			'VE' => 'Venezuela, RB',
			'VG' => 'British Virgin Islands',
			'VI' => 'Virgin Islands (U.S.)',
			'VN' => 'Vietnam',
			'VU' => 'Vanuatu',
			'WS' => 'Samoa',
			'XK' => 'Kosovo',
			'YE' => 'Yemen, Rep.',
			'ZA' => 'South Africa',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);

		$country_code = isset( $country_code ) ? strtoupper( $country_code ) : '';
		$country = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
		return $country;
	}

	public static function get_countries_dropdown( $country = '' ) {
		//Note: the country names are output using the __() function below so that they can be translated. The POT file just needs to have the country names in it.
		$countries = array(
			'Afghanistan',
			'Albania',
			'Algeria',
			'Andorra',
			'Angola',
			'Antigua and Barbuda',
			'Argentina',
			'Armenia',
			'Aruba',
			'Australia',
			'Austria',
			'Azerbaijan',
			'Bahamas',
			'Bahrain',
			'Bangladesh',
			'Barbados',
			'Belarus',
			'Belgium',
			'Belize',
			'Benin',
			'Bhutan',
			'Bolivia',
			'Bonaire',
			'Bosnia and Herzegovina',
			'Botswana',
			'Brazil',
			'Brunei',
			'Bulgaria',
			'Burkina Faso',
			'Burundi',
			'Cambodia',
			'Cameroon',
			'Canada',
			'Cape Verde',
			'Cayman Islands',
			'Central African Republic',
			'Chad',
			'Chile',
			'China',
			'Colombia',
			'Comoros',
			'Congo (Brazzaville)',
			'Congo',
			'Costa Rica',
			'Cote d\'Ivoire',
			'Croatia',
			'Cuba',
			'Curacao',
			'Cyprus',
			'Czech Republic',
			'Denmark',
			'Djibouti',
			'Dominica',
			'Dominican Republic',
			'East Timor (Timor Timur)',
			'Ecuador',
			'Egypt',
			'El Salvador',
			'Equatorial Guinea',
			'Eritrea',
			'Estonia',
            'Eswatini',
			'Ethiopia',
			'Fiji',
			'Finland',
			'France',
            'French Polynesia',
			'Gabon',
			'Gambia, The',
			'Georgia',
			'Germany',
			'Ghana',
			'Greece',
			'Grenada',
			'Guatemala',
			'Guinea',
			'Guinea-Bissau',
			'Guyana',
			'Haiti',
			'Honduras',
			'Hong Kong',
			'Hungary',
			'Iceland',
			'India',
			'Indonesia',
			'Iran',
			'Iraq',
			'Ireland',
			'Israel',
			'Italy',
			'Jamaica',
			'Japan',
			'Jordan',
			'Kazakhstan',
			'Kenya',
			'Kiribati',
			'Korea, North',
			'Korea, South',
			'Kuwait',
			'Kyrgyzstan',
			'Laos',
			'Latvia',
			'Lebanon',
			'Lesotho',
			'Liberia',
			'Libya',
			'Liechtenstein',
			'Lithuania',
			'Luxembourg',
			'Macedonia',
			'Madagascar',
			'Malawi',
			'Malaysia',
			'Maldives',
			'Mali',
			'Malta',
			'Marshall Islands',
			'Mauritania',
			'Mauritius',
			'Mexico',
			'Micronesia',
			'Moldova',
			'Monaco',
			'Mongolia',
			'Montenegro',
			'Morocco',
			'Mozambique',
			'Myanmar',
			'Namibia',
			'Nauru',
			'Nepa',
			'Netherlands',
			'New Zealand',
			'Nicaragua',
			'Niger',
			'Nigeria',
			'Norway',
			'Oman',
			'Pakistan',
			'Palau',
            'Palestine',
			'Panama',
			'Papua New Guinea',
			'Paraguay',
			'Peru',
			'Philippines',
			'Poland',
			'Portugal',
			'Qatar',
			'Romania',
			'Russia',
			'Rwanda',
			'Saint Kitts and Nevis',
			'Saint Lucia',
			'Saint Vincent',
			'Samoa',
			'San Marino',
			'Sao Tome and Principe',
			'Saudi Arabia',
			'Senegal',
			'Serbia',
			'Seychelles',
			'Sierra Leone',
			'Singapore',
			'Slovakia',
			'Slovenia',
			'Solomon Islands',
			'Somalia',
			'South Africa',
			'Spain',
			'Sri Lanka',
			'Sudan',
			'Suriname',
			'Swaziland',
			'Sweden',
			'Switzerland',
			'Syria',
			'Taiwan',
			'Tajikistan',
			'Tanzania',
			'Thailand',
			'Togo',
			'Tonga',
			'Trinidad and Tobago',
			'Tunisia',
			'Turkey',
			'Turkmenistan',
			'Tuvalu',
			'Uganda',
			'Ukraine',
			'United Arab Emirates',
			'United Kingdom',
			'United States of America',
			'Uruguay',
			'Uzbekistan',
			'Vanuatu',
			'Vatican City',
			'Venezuela',
			'Vietnam',
			'Yemen',
			'Zambia',
			'Zimbabwe',
		);
		//let's try to "guess" country name
		$curr_lev      = -1;
		$guess_country = '';
		foreach ( $countries as $country_name ) {
			similar_text( strtolower( $country ), strtolower( $country_name ), $lev );
			if ( $lev >= $curr_lev ) {
				//this is closest match so far
				$curr_lev      = $lev;
				$guess_country = $country_name;
			}
			if ( $curr_lev == 100 ) {
				//exact match
				break;
			}
		}
		if ( $curr_lev <= 80 ) {
			// probably bad guess
			$guess_country = '';
		}
		$countries_dropdown = '';
		//let's add "(Please select)" option
		$countries_dropdown .= "\r\n" . '<option value=""' . ( $country == '' ? ' selected' : '' ) . '>' . __( '(Please Select)', 'simple-membership' ) . '</option>';
		if ( $guess_country == '' && $country != '' ) {
			//since we haven't guessed the country name, let's add current value to the options
			$countries_dropdown .= "\r\n" . '<option value="' . $country . '" selected>' . $country . '</option>';
		}
		if ( $guess_country != '' ) {
			$country = $guess_country;
		}
		foreach ( $countries as $country_name ) {
			//The country name strings are already in the POT file from the swpm_dummy_country_names_for_translation() function, so we can use __() function to output the country names.
			$countries_dropdown .= "\r\n" . '<option value="' . $country_name . '"' . ( strtolower( $country_name ) == strtolower( $country ) ? ' selected' : '' ) . '>' . __($country_name, 'simple-membership') . '</option>';
		}
		return $countries_dropdown;
	}

	/**
	 * This function is used to force the translation tools to include the country names in the translation files (POT file).
	 * It does not return anything and is not meant to be called in the code.
	 */
	function swpm_dummy_country_names_for_translation() {
		// Dummy country names for translation purpose only.
		// This will be helpful for all the addons to also output country names using the __() function and have translation support.
		__('Afghanistan', 'simple-membership');
		__('Albania', 'simple-membership');
		__('Algeria', 'simple-membership');
		__('Andorra', 'simple-membership');
		__('Angola', 'simple-membership');
		__('Antigua and Barbuda', 'simple-membership');
		__('Argentina', 'simple-membership');
		__('Armenia', 'simple-membership');
		__('Aruba', 'simple-membership');
		__('Australia', 'simple-membership');
		__('Austria', 'simple-membership');
		__('Azerbaijan', 'simple-membership');
		__('Bahamas', 'simple-membership');
		__('Bahrain', 'simple-membership');
		__('Bangladesh', 'simple-membership');
		__('Barbados', 'simple-membership');
		__('Belarus', 'simple-membership');
		__('Belgium', 'simple-membership');
		__('Belize', 'simple-membership');
		__('Benin', 'simple-membership');
		__('Bhutan', 'simple-membership');
		__('Bolivia', 'simple-membership');
		__('Bonaire', 'simple-membership');
		__('Bosnia and Herzegovina', 'simple-membership');
		__('Botswana', 'simple-membership');
		__('Brazil', 'simple-membership');
		__('Brunei', 'simple-membership');
		__('Bulgaria', 'simple-membership');
		__('Burkina Faso', 'simple-membership');
		__('Burundi', 'simple-membership');
		__('Cambodia', 'simple-membership');
		__('Cameroon', 'simple-membership');
		__('Canada', 'simple-membership');
		__('Cape Verde', 'simple-membership');
		__('Cayman Islands', 'simple-membership');
		__('Central African Republic', 'simple-membership');
		__('Chad', 'simple-membership');
		__('Chile', 'simple-membership');
		__('China', 'simple-membership');
		__('Colombia', 'simple-membership');
		__('Comoros', 'simple-membership');
		__('Congo (Brazzaville)', 'simple-membership');
		__('Congo', 'simple-membership');
		__('Costa Rica', 'simple-membership');
		__('Cote d\'Ivoire', 'simple-membership');
		__('Croatia', 'simple-membership');
		__('Cuba', 'simple-membership');
		__('Curacao', 'simple-membership');
		__('Cyprus', 'simple-membership');
		__('Czech Republic', 'simple-membership');
		__('Denmark', 'simple-membership');
		__('Djibouti', 'simple-membership');
		__('Dominica', 'simple-membership');
		__('Dominican Republic', 'simple-membership');
		__('East Timor (Timor Timur)', 'simple-membership');
		__('Ecuador', 'simple-membership');
		__('Egypt', 'simple-membership');
		__('El Salvador', 'simple-membership');
		__('Equatorial Guinea', 'simple-membership');
		__('Eritrea', 'simple-membership');
		__('Estonia', 'simple-membership');
		__('Eswatini', 'simple-membership');
		__('Ethiopia', 'simple-membership');
		__('Fiji', 'simple-membership');
		__('Finland', 'simple-membership');
		__('France', 'simple-membership');
		__('French Polynesia', 'simple-membership');
		__('Gabon', 'simple-membership');
		__('Gambia, The', 'simple-membership');
		__('Georgia', 'simple-membership');
		__('Germany', 'simple-membership');
		__('Ghana', 'simple-membership');
		__('Greece', 'simple-membership');
		__('Grenada', 'simple-membership');
		__('Guatemala', 'simple-membership');
		__('Guinea', 'simple-membership');
		__('Guinea-Bissau', 'simple-membership');
		__('Guyana', 'simple-membership');
		__('Haiti', 'simple-membership');
		__('Honduras', 'simple-membership');
		__('Hong Kong', 'simple-membership');
		__('Hungary', 'simple-membership');
		__('Iceland', 'simple-membership');
		__('India', 'simple-membership');
		__('Indonesia', 'simple-membership');
		__('Iran', 'simple-membership');
		__('Iraq', 'simple-membership');
		__('Ireland', 'simple-membership');
		__('Israel', 'simple-membership');
		__('Italy', 'simple-membership');
		__('Jamaica', 'simple-membership');
		__('Japan', 'simple-membership');
		__('Jordan', 'simple-membership');
		__('Kazakhstan', 'simple-membership');
		__('Kenya', 'simple-membership');
		__('Kiribati', 'simple-membership');
		__('Korea, North', 'simple-membership');
		__('Korea, South', 'simple-membership');
		__('Kuwait', 'simple-membership');
		__('Kyrgyzstan', 'simple-membership');
		__('Laos', 'simple-membership');
		__('Latvia', 'simple-membership');
		__('Lebanon', 'simple-membership');
		__('Lesotho', 'simple-membership');
		__('Liberia', 'simple-membership');
		__('Libya', 'simple-membership');
		__('Liechtenstein', 'simple-membership');
		__('Lithuania', 'simple-membership');
		__('Luxembourg', 'simple-membership');
		__('Macedonia', 'simple-membership');
		__('Madagascar', 'simple-membership');
		__('Malawi', 'simple-membership');
		__('Malaysia', 'simple-membership');
		__('Maldives', 'simple-membership');
		__('Mali', 'simple-membership');
		__('Malta', 'simple-membership');
		__('Marshall Islands', 'simple-membership');
		__('Mauritania', 'simple-membership');
		__('Mauritius', 'simple-membership');
		__('Mexico', 'simple-membership');
		__('Micronesia', 'simple-membership');
		__('Moldova', 'simple-membership');
		__('Monaco', 'simple-membership');
		__('Mongolia', 'simple-membership');
		__('Montenegro', 'simple-membership');
		__('Morocco', 'simple-membership');
		__('Mozambique', 'simple-membership');
		__('Myanmar', 'simple-membership');
		__('Namibia', 'simple-membership');
		__('Nauru', 'simple-membership');
		__('Nepa', 'simple-membership');
		__('Netherlands', 'simple-membership');
		__('New Zealand', 'simple-membership');
		__('Nicaragua', 'simple-membership');
		__('Niger', 'simple-membership');
		__('Nigeria', 'simple-membership');
		__('Norway', 'simple-membership');
		__('Oman', 'simple-membership');
		__('Pakistan', 'simple-membership');
		__('Palau', 'simple-membership');
		__('Palestine', 'simple-membership');
		__('Panama', 'simple-membership');
		__('Papua New Guinea', 'simple-membership');
		__('Paraguay', 'simple-membership');
		__('Peru', 'simple-membership');
		__('Philippines', 'simple-membership');
		__('Poland', 'simple-membership');
		__('Portugal', 'simple-membership');
		__('Qatar', 'simple-membership');
		__('Romania', 'simple-membership');
		__('Russia', 'simple-membership');
		__('Rwanda', 'simple-membership');
		__('Saint Kitts and Nevis', 'simple-membership');
		__('Saint Lucia', 'simple-membership');
		__('Saint Vincent', 'simple-membership');
		__('Samoa', 'simple-membership');
		__('San Marino', 'simple-membership');
		__('Sao Tome and Principe', 'simple-membership');
		__('Saudi Arabia', 'simple-membership');
		__('Senegal', 'simple-membership');
		__('Serbia', 'simple-membership');
		__('Seychelles', 'simple-membership');
		__('Sierra Leone', 'simple-membership');
		__('Singapore', 'simple-membership');
		__('Slovakia', 'simple-membership');
		__('Slovenia', 'simple-membership');
		__('Solomon Islands', 'simple-membership');
		__('Somalia', 'simple-membership');
		__('South Africa', 'simple-membership');
		__('Spain', 'simple-membership');
		__('Sri Lanka', 'simple-membership');
		__('Sudan', 'simple-membership');
		__('Suriname', 'simple-membership');
		__('Swaziland', 'simple-membership');
		__('Sweden', 'simple-membership');
		__('Switzerland', 'simple-membership');
		__('Syria', 'simple-membership');
		__('Taiwan', 'simple-membership');
		__('Tajikistan', 'simple-membership');
		__('Tanzania', 'simple-membership');
		__('Thailand', 'simple-membership');
		__('Togo', 'simple-membership');
		__('Tonga', 'simple-membership');
		__('Trinidad and Tobago', 'simple-membership');
		__('Tunisia', 'simple-membership');
		__('Turkey', 'simple-membership');
		__('Turkmenistan', 'simple-membership');
		__('Tuvalu', 'simple-membership');
		__('Uganda', 'simple-membership');
		__('Ukraine', 'simple-membership');
		__('United Arab Emirates', 'simple-membership');
		__('United Kingdom', 'simple-membership');
		__('United States of America', 'simple-membership');
		__('Uruguay', 'simple-membership');
		__('Uzbekistan', 'simple-membership');
		__('Vanuatu', 'simple-membership');
		__('Vatican City', 'simple-membership');
		__('Venezuela', 'simple-membership');
		__('Vietnam', 'simple-membership');
		__('Yemen', 'simple-membership');
		__('Zambia', 'simple-membership');
		__('Zimbabwe', 'simple-membership');
	}


	/**
	 * This function returns the human readable name of a button type.
	 */
	public static function get_button_type_name( $button_type ) {
		//It is used in the 'manage payment buttons' page to display the button type.
		$btnTypesNames = array(
			'pp_buy_now'              => SwpmUtils::_( 'PayPal Buy Now' ),
			'pp_subscription'         => SwpmUtils::_( 'PayPal Subscription' ),
			'pp_buy_now_new'     	  => SwpmUtils::_( 'PayPal Buy Now (New API)' ),
			'pp_subscription_new'     => SwpmUtils::_( 'PayPal Subscription (New API)' ),
			'pp_smart_checkout'       => SwpmUtils::_( 'PayPal Smart Checkout (Deprecated)' ),
			'stripe_buy_now'          => SwpmUtils::_( 'Stripe Legacy Buy Now (Deprecated)' ),
			'stripe_subscription'     => SwpmUtils::_( 'Stripe Legacy Subscription (Deprecated)' ),
			'stripe_sca_buy_now'      => SwpmUtils::_( 'Stripe Buy Now' ),
			'stripe_sca_subscription' => SwpmUtils::_( 'Stripe Subscription' ),
			'braintree_buy_now'       => SwpmUtils::_( 'Braintree Buy Now' ),
		);

		$button_type_name = $button_type;

		if ( array_key_exists( $button_type, $btnTypesNames ) ) {
			$button_type_name = $btnTypesNames[ $button_type ];
		}

		return $button_type_name;
	}

	public static function format_money( $amount, $currency = false ) {
		$amount = floatval($amount);
		$formatted = number_format( $amount, 2 );
		if ( $currency ) {
			$formatted .= ' ' . $currency;
		}
		return $formatted;
	}

	public static function load_stripe_lib() {
		//this function loads Stripe PHP SDK and ensures only once instance is loaded
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once SIMPLE_WP_MEMBERSHIP_PATH . 'lib/stripe-gateway/init.php';
			\Stripe\Stripe::setAppInfo( 'Simple Membership', SIMPLE_WP_MEMBERSHIP_VER, 'https://simple-membership-plugin.com/', 'pp_partner_Fvas9OJ0jQ2oNQ' );
		}
	}

	public static function get_stripe_api_keys_from_payment_button( $button_id, $live = false ) {
		$keys   = array(
			'public' => '',
			'secret' => '',
		);
		$button = get_post( $button_id );
		if ( $button ) {
			$opts            = get_option( 'swpm-settings' );
			$use_global_keys = get_post_meta( $button_id, 'stripe_use_global_keys', true );

			if ( $use_global_keys ) {
				if ( $live ) {
					$keys['public'] = isset( $opts['stripe-live-public-key'] ) ? $opts['stripe-live-public-key'] : '';
					$keys['secret'] = isset( $opts['stripe-live-secret-key'] ) ? $opts['stripe-live-secret-key'] : '';
				} else {
					$keys['public'] = isset( $opts['stripe-test-public-key'] ) ? $opts['stripe-test-public-key'] : '';
					$keys['secret'] = isset( $opts['stripe-test-secret-key'] ) ? $opts['stripe-test-secret-key'] : '';
				}
			} else {
				if ( $live ) {
					$stripe_live_secret_key      = get_post_meta( $button_id, 'stripe_live_secret_key', true );
					$stripe_live_publishable_key = get_post_meta( $button_id, 'stripe_live_publishable_key', true );

					$keys['public'] = $stripe_live_publishable_key;
					$keys['secret'] = $stripe_live_secret_key;
				} else {
					$stripe_test_secret_key      = get_post_meta( $button_id, 'stripe_test_secret_key', true );
					$stripe_test_publishable_key = get_post_meta( $button_id, 'stripe_test_publishable_key', true );

					$keys['public'] = $stripe_test_publishable_key;
					$keys['secret'] = $stripe_test_secret_key;
				}
			}
		}
		return $keys;
	}

	public static function mail( $email, $subject, $email_body, $headers ) {
		$settings     = SwpmSettings::get_instance();
		$html_enabled = $settings->get_value( 'email-enable-html' );
		if ( ! empty( $html_enabled ) ) {
			$headers   .= "Content-Type: text/html; charset=UTF-8\r\n";
			$email_body = nl2br( $email_body );
		}
		wp_mail( $email, $subject, $email_body, $headers );
	}

	public static function has_email_merge_tag( $body, $tag ) {
		if( strpos( $body, $tag ) !== false ) {
			return true;
		}
		return false;
	}

	public static function resend_activation_email_by_member_id( $member_id ) {
		$member = SwpmMemberUtils::get_user_by_id( $member_id );
		if ( empty( $member ) ) {
			//can't find member
			SwpmLog::log_simple_debug( 'Account activation email for member ID: '.$member_id.' could not be sent. Member account does not exists.', false );
			SwpmTransfer::get_instance()->set('resend_activation_email_error', sprintf(__('Cannot find member account of ID: %d.', 'simple-membership'), $member_id));
			return;
		}
		if ( isset($member->account_state) && $member->account_state !== 'activation_required' ) {
			//account already active
			SwpmLog::log_simple_debug( 'Account activation email for member ID: '.$member_id.' could not be sent. Account activation already done.', false );
			SwpmTransfer::get_instance()->set('resend_activation_email_error', sprintf(__('Account activation for member ID: %d already done.', 'simple-membership'), $member_id));
			return;
		}
		$act_data = get_option( 'swpm_email_activation_data_usr_' . $member_id, array() );
		if ( empty( $act_data ) ) {
			//looks like activation data has been removed for some reason. We won't be able to have member's plain password in this case
			$act_data['plain_password'] = '';
		}

		delete_option( 'swpm_email_activation_data_usr_' . $member_id );

		$member_info_array =  (array) $member;
		$member_info_array['plain_password'] = isset($act_data['plain_password']) && !empty($act_data['plain_password']) ?  SwpmUtils::crypt( $act_data['plain_password'], 'd' ) : '';

		$settings = SwpmSettings::get_instance();

		//Generate the activation code and store it in the DB
		$act_code  = md5( uniqid() . $member_id );
		$user_data = array(
			'timestamp'      => time(),
			'act_code'       => $act_code,
			'plain_password' => $member_info_array['plain_password'],
		);

		$user_data = apply_filters( 'swpm_email_activation_data', $user_data );

		update_option( 'swpm_email_activation_data_usr_' . $member_id, $user_data, false );

		$activation_link = add_query_arg(
			array(
				'swpm_email_activation' => '1',
				'swpm_member_id'        => $member_id,
				'swpm_token'            => $act_code,
			),
			get_home_url()
		);

		// Allow hooks to change the value of activation_link
		$activation_link = apply_filters( 'swpm_send_reg_email_activation_link', $activation_link );

		$from_address = $settings->get_value( 'email-from' );
		$to_email     = trim( $member_info_array['email'] );
		$login_link   = $settings->get_value( 'login-page-url' );
		$headers      = 'From: ' . $from_address . "\r\n";

		$member_info_array['activation_link']       = $activation_link;
		$member_info_array['membership_level_name'] = SwpmPermission::get_instance( $member_info_array['membership_level'] )->get( 'alias' );
		$member_info_array['password']              = $member_info_array['plain_password'];
		$member_info_array['login_link']            = $login_link;

		$values = array_values( $member_info_array );
		$keys   = array_map( 'swpm_enclose_var', array_keys( $member_info_array ) );

		$body = $settings->get_value( 'email-activation-mail-body' );
		$body = html_entity_decode( $body );
		$body = str_replace( $keys, $values, $body );
		$body = SwpmMiscUtils::replace_dynamic_tags( $body, $member_id ); //Do the standard merge var replacement.

		$subject = $settings->get_value( 'email-activation-mail-subject' );

		SwpmMiscUtils::mail( $to_email, $subject, $body, $headers );
		SwpmLog::log_simple_debug( 'Account activation email for member ID: '.$member_id.' successfully sent to: ' . $to_email . '. From email address value used: ' . $from_address, true );
	}
}
