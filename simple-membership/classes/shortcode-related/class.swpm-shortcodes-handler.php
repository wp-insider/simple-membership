<?php

class SwpmShortcodesHandler {

	public function __construct() {
		//Register all the shortcodes here
		add_shortcode( 'swpm_payment_button', array( &$this, 'swpm_payment_button_sc' ) );
		
		add_shortcode( 'swpm_thank_you_page_registration', array( &$this, 'swpm_ty_page_rego_sc' ) );

		add_shortcode( 'swpm_show_expiry_date', array( &$this, 'swpm_show_expiry_date_sc' ) );

		add_shortcode( 'swpm_mini_login', array( &$this, 'swpm_show_mini_login_sc' ) );

		add_shortcode( 'swpm_paypal_subscription_cancel_link', array( &$this, 'swpm_pp_cancel_subs_link_sc' ) );

		add_shortcode( 'swpm_stripe_subscription_cancel_link', array( $this, 'swpm_stripe_cancel_subs_link_sc' ) );

		add_shortcode( 'swpm_show_subscriptions_and_cancel_link', array( $this, 'swpm_show_subscriptions_and_cancel_link' ) );

		//TODO - WIP (Later, this will be moved to the shortcode implementation section like the other ones)
		//include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_advanced_buy_now_button_shortcode_view.php' );

	}

	public function swpm_payment_button_sc( $args ) {
		extract(
			shortcode_atts(
				array(
					'id'          => '',
					'button_text' => '',
					'new_window'  => '',
					'class'       => '',
				),
				$args
			)
		);

		if ( empty( $id ) ) {
			return '<p class="swpm-red-box">Error! You must specify a button ID with this shortcode. Check the usage documentation.</p>';
		}

		//Add a quick escaping to the shortcode arguments.
		$args = array_map( 'esc_attr', $args );
                        
		$button_id = $id;
		//$button = get_post($button_id); //Retrieve the CPT for this button
		$button_type = get_post_meta( $button_id, 'button_type', true );
		if ( empty( $button_type ) ) {
			$error_msg  = '<p class="swpm-red-box">';
			$error_msg .= 'Error! The button ID (' . esc_attr($button_id) . ') you specified in the shortcode does not exist. You may have deleted this payment button. ';
			$error_msg .= 'Go to the Manage Payment Buttons interface then copy and paste the correct button ID in the shortcode.';
			$error_msg .= '</p>';
			return $error_msg;
		}

		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_button_shortcode_view.php' );
		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/stripe_button_shortcode_view.php' );
		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/stripe_sca_button_shortcode_view.php' );
		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/braintree_button_shortcode_view.php' );
		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_smart_checkout_button_shortcode_view.php' );
		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_buy_now_new_button_shortcode_view.php' );
		include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_subscription_new_button_shortcode_view.php' );

		$button_code = '';
		$button_code = apply_filters( 'swpm_payment_button_shortcode_for_' . $button_type, $button_code, $args );

		$output  = '';
		$output .= '<div class="swpm-payment-button">' . $button_code . '</div>';

		return $output;
	}

	public function swpm_ty_page_rego_sc( $args ) {
		$output   = '';
		$settings = SwpmSettings::get_instance();

		//If user is logged in then the purchase will be applied to the existing profile.
		if ( SwpmMemberUtils::is_member_logged_in() ) {
			$username = SwpmMemberUtils::get_logged_in_members_username();
			$output  .= '<div class="swpm-ty-page-registration-logged-in swpm-yellow-box">';
			$output  .= '<p>' . __( 'Your membership profile will be updated to reflect the payment.', 'simple-membership' ) . '</p>';
			$output  .= __( 'Your profile username: ', 'simple-membership' ) . esc_attr($username);
			$output  .= '</div>';
			$output = apply_filters( 'swpm_ty_page_registration_msg_to_logged_in_member', $output );
			return $output;
		}

		//If user is not-logged in then lets see if there is a pending registration that needs to be completed.
		$output .= '<div class="swpm-ty-page-registration">';
		$member_data = SwpmUtils::get_incomplete_paid_member_info_by_ip();
		if ( $member_data ) {
			//Found a member profile record for this IP that needs to be completed
			$reg_page_url      = $settings->get_value( 'registration-page-url' );
			$rego_complete_url = add_query_arg(
				array(
					'member_id' => $member_data->member_id,
					'code'      => $member_data->reg_code,
				),
				$reg_page_url
			);
			$output .= '<div class="swpm-ty-page-registration-link swpm-yellow-box">';
			$output .= '<p>' . __( 'Click on the following link to complete the registration.', 'simple-membership' ) . '</p>';
			$output .= '<p><a href="' . esc_url($rego_complete_url) . '">' . __( 'Click here to complete your paid registration', 'simple-membership' ) . '</a></p>';
			$output .= '</div>';
			//Allow addons to modify the output
			$output = apply_filters( 'swpm_ty_page_registration_msg_with_link', $output, $rego_complete_url );
		} else if ( SwpmMemberUtils::get_user_by_ip_address() ) {
			//Found a member profile record for this IP but it is not a pending registration
			$output .= '<div class="swpm-ty-page-registration-link swpm-found-user-by-ip swpm-yellow-box">';
			$output .= __( "It looks like you have already completed the registration process. You can now log in to the site and start enjoying your membership benefits.", "simple-membership" );
			$output .= '</div>';
			//Allow addons to modify the output
			$output = apply_filters( 'swpm_ty_page_registration_msg_found_user_by_ip', $output );
		} else {
			//Nothing found. Check again later.
			$output .= '<div class="swpm-ty-page-registration-link swpm-no-user-found swpm-yellow-box">';
			$output .= __( 'If you have just made a membership payment then your payment is yet to be processed. Please check back in a few minutes. An email will be sent to you with the details shortly.', 'simple-membership' );
			$output .= '</div>';
			//Allow addons to modify the output
			$output = apply_filters( 'swpm_ty_page_registration_msg_no_link', $output );
		}

		$output .= '</div>'; //end of .swpm-ty-page-registration

		$output = apply_filters( 'swpm_ty_page_registration_output', $output );
		return $output;
	}

	public function swpm_show_expiry_date_sc( $args ) {
		$output = '<div class="swpm-show-expiry-date">';
		if ( SwpmMemberUtils::is_member_logged_in() ) {
			$auth        = SwpmAuth::get_instance();
			$expiry_date = $auth->get_expire_date();
			$output     .= __( 'Expiry: ', 'simple-membership' ) . esc_attr($expiry_date);
		} else {
			$output .= __( 'You are not logged-in as a member', 'simple-membership' );
		}
		$output .= '</div>';
		return $output;
	}

	public function swpm_show_mini_login_sc( $args ) {

		$login_page_url   = SwpmSettings::get_instance()->get_value( 'login-page-url' );
		$join_page_url    = SwpmSettings::get_instance()->get_value( 'join-us-page-url' );
		$profile_page_url = SwpmSettings::get_instance()->get_value( 'profile-page-url' );
		$logout_url       = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '?swpm-logout=true';

		$filtered_login_url = apply_filters( 'swpm_get_login_link_url', $login_page_url ); //Addons can override the login URL value using this filter.

		//Start building the output
		$output = '<div class="swpm_mini_login_wrapper">';

		//Check if the user is logged in or not
		$auth = SwpmAuth::get_instance();
		if ( $auth->is_logged_in() ) {
			//User is logged-in.
			$username = $auth->get( 'user_name' );
			$mini_login_output_when_logged_in = '<span class="swpm_mini_login_label">' . __( 'Logged in as: ', 'simple-membership') . '</span>';
			$mini_login_output_when_logged_in .= '<span class="swpm_mini_login_username">' . esc_attr($username) . '</span>';
			$mini_login_output_when_logged_in .= '<span class="swpm_mini_login_profile"> | <a href="' . esc_url($profile_page_url) . '">' . __( 'Profile', 'simple-membership') . '</a></span>';
			$mini_login_output_when_logged_in .= '<span class="swpm_mini_login_logout"> | <a href="' . esc_url($logout_url) . '">' . __( 'Logout', 'simple-membership') . '</a></span>';
			
			//Trigger filter to allow addons to modify this output.
			$mini_login_output_when_logged_in = apply_filters( 'swpm_mini_login_output_when_logged_in', $mini_login_output_when_logged_in );
			
			//Add the logged-in output to the main output
			$output .= $mini_login_output_when_logged_in;
		} else {
			//User is NOT logged-in.
			$mini_login_output_when_not_logged = '<span class="swpm_mini_login_login_here"><a href="' . esc_url($filtered_login_url) . '">' . __( 'Login Here', 'simple-membership') . '</a></span>';

			//Check if the join us link should be hidden
			$hide_join_us_link_enabled = SwpmSettings::get_instance()->get_value('hide-join-us-link');
			if (empty($hide_join_us_link_enabled)){
				//Show the join us option.
				$mini_login_output_when_not_logged .= '<span class="swpm_mini_login_no_membership"> | ' . __( 'Not a member? ', 'simple-membership') . '</span>';
				$mini_login_output_when_not_logged .= '<span class="swpm_mini_login_join_now"><a href="' . esc_url($join_page_url) . '">' . __( 'Join Now', 'simple-membership') . '</a></span>';
			}

			//Trigger filter to allow addons to modify this output.
			$mini_login_output_when_not_logged = apply_filters( 'swpm_mini_login_output_when_not_logged_in', $mini_login_output_when_not_logged );

			//Add the not logged-in output to the main output
			$output .= $mini_login_output_when_not_logged;
		}

		$output .= '</div>';//end of .swpm_mini_login_wrapper

		//Trigger filter to allow addons to modify the final output.
		$output = apply_filters( 'swpm_mini_login_output', $output );

		return $output;
	}

	public function swpm_stripe_cancel_subs_link_sc( $args ) {
		//Shortcode parameters: ['anchor_text']

		if ( ! SwpmMemberUtils::is_member_logged_in() ) {
			//member not logged in
			$error_msg = '<div class="swpm-stripe-cancel-error-msg">' . __( 'You are not logged-in as a member', 'simple-membership' ) . '</div>';
			return $error_msg;
		}

		//Get the member ID
		$member_id = SwpmMemberUtils::get_logged_in_members_id();
		$subs = (new SWPM_Utils_Subscriptions( $member_id ))->load_stripe_subscriptions();
		if ( empty( $subs->get_active_subs_count() ) ) {
			//no active subscriptions found
			$error_msg = '<div class="swpm-stripe-cancel-error-msg">' . __( 'No active subscriptions', 'simple-membership' ) . '</div>';
			return $error_msg;
		}

        $output = $subs->get_stripe_subs_cancel_url($args, false);

		$output = '<div class="swpm-stripe-subscription-cancel-link">' . $output . '</div>';

		return $output;
	}

	public function swpm_pp_cancel_subs_link_sc( $args ) {
		//Shortcode parameters: ['anchor_text'], ['merchant_id']

		extract(
			shortcode_atts(
				array(
					'merchant_id' => '',
					'anchor_text' => '',
					'new_window' => '',
					'css_class' => '',
				),
				$args
			)
		);

		if ( empty( $merchant_id ) ) {
			return '<p class="swpm-red-box">Error! You need to specify your secure PayPal merchant ID in the shortcode using the "merchant_id" parameter.</p>';
		}

		$output   = '';
		$settings = SwpmSettings::get_instance();

		//Check if the member is logged-in
		if ( SwpmMemberUtils::is_member_logged_in() ) {
			$user_id = SwpmMemberUtils::get_logged_in_members_id();
		}

		if ( ! empty( $user_id ) ) {
			//The user is logged-in

			//Set the default window target (if it is set via the shortcode).
			if ( empty( $new_window ) ) {
				$window_target = '';
			} else {
				$window_target = ' target="_blank"';
			}

			//Set the CSS class (if it is set via the shortcode).
			if ( empty( $css_class ) ) {
				$link_css_class = '';
			} else {
				$link_css_class = ' class="' . sanitize_html_class($css_class) . '"';
			}

			//Set the default anchor text (if one is provided via the shortcode).
			if ( empty( $anchor_text ) ) {
				$anchor_text = __( 'Unsubscribe from PayPal', 'simple-membership' );
			}

			$output .= '<div class="swpm-paypal-subscription-cancel-link">';
			$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );
			if ( $sandbox_enabled ) {
				//Sandbox mode
				$output .= '<a href="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=' . esc_attr($merchant_id) . '" _fcksavedurl="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=' . esc_attr($merchant_id) . '" '. esc_js($window_target) . esc_attr($link_css_class) .'>';
				$output .= esc_attr($anchor_text);
				$output .= '</a>';
			} else {
				//Live mode
				$output .= '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=' . esc_attr($merchant_id) . '" _fcksavedurl="https://www.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=' . esc_attr($merchant_id) . '" '. esc_js($window_target) . esc_attr($link_css_class) .'>';
				$output .= esc_attr($anchor_text);
				$output .= '</a>';
			}
			$output .= '</div>';

		} else {
			//The user is NOT logged-in
			$output .= '<p>' . __( 'You are not logged-in as a member.', 'simple-membership' ) . '</p>';
		}
		return $output;
	}

	public function swpm_show_subscriptions_and_cancel_link($atts){
		$output = '';
		$atts = shortcode_atts(array(
			'show_all_status' => ''
		), $atts);

		if ( ! SwpmMemberUtils::is_member_logged_in() ) {
			//member not logged in
			return '<p>'.__( 'You are not logged-in as a member.', 'simple-membership' ).'</p>';
		}

		//Get the member ID and load subscriptions utils class.
		$member_username = SwpmMemberUtils::get_logged_in_members_username();
		$member_id = SwpmMemberUtils::get_logged_in_members_id();

		//We will use this class to load the curated subscriptions list data so we can use it in this shortcode.
		$subscriptions_utils = new SWPM_Utils_Subscriptions( $member_id );
		$subscriptions_utils->load_subs_data();

		/**
		 * Display any API key error messages (if subscription exists but api keys are not saved). 
		 * The error message is only shown when the subscription of the corresponding payment gateway is present.
		 * For example: If there are no stripe sca subscriptions, stripe api error wont be shown.
		*/
		$any_stripe_api_key_error_msg = $subscriptions_utils->get_any_stripe_sca_api_key_error();
		if ( !empty( $any_stripe_api_key_error_msg ) ) {
			$output .= '<p class="swpm-active-subs-api-key-error-msg">'. esc_attr($any_stripe_api_key_error_msg) . '</p>';
		}
		$any_paypal_api_key_error_msg = $subscriptions_utils->get_any_paypal_ppcp_api_key_error();
		if ( !empty( $any_paypal_api_key_error_msg ) ) {
			$output .= '<p class="swpm-active-subs-api-key-error-msg">'. esc_attr($any_paypal_api_key_error_msg) . '</p>';
		}

		//Check if we need to show all subscriptions or just the active ones
		$show_all_subscriptions = !empty($atts['show_all_status']) ? true : false;

		//Get the list of subscriptions
		if ($show_all_subscriptions) {
			$subscriptions_list = $subscriptions_utils->get_all_subscriptions();
		}else{
			$subscriptions_list = $subscriptions_utils->get_active_subscriptions();
		}

		//Display the list of subscriptions
		$output .= '<div class="swpm-active-subs-table-wrap">';

		if (count($subscriptions_list)) {
			$output .= '<table class="swpm-active-subs-table">';
			
			// Header section
			$output .= '<thead>';
			$output .= '<tr>';
			$output .= '<th>'. __('Subscription', 'simple-membership').'</th>';
			$output .= '<th>'. __('Action', 'simple-membership') .'</th>';
			$output .= '</tr>';
			$output .= '</thead>';

			$output .= '<tbody>';
			foreach ($subscriptions_list as $subscription) {
				$output .= '<tr>';
				$output .= '<td>';
				$output .= '<div class="swpm-sub-name">'. esc_attr($subscription['plan']).'</div>';
				if( isset ( $subscription['is_attached_to_profile'] ) && $subscription['is_attached_to_profile'] == 'yes'  ){
					//This subscription is attached to the profile currently. Show a message.
					$output .= '<div class="swpm-sub-attached-to-profile">'. __('Currently used for your membership access.', 'simple-membership').'</div>';
				}
				$output .= '</td>';

				$output .= '<td>';
				$output .= SWPM_Utils_Subscriptions::get_cancel_subscription_output($subscription);
				$output .= '</td>';
				$output .= '</tr>';
			}
			$output .= '</tbody>';

			$output .= '</table>';
		}else{
			$output .= '<p>'.__( 'Active subscription not detected for the member account with the username: ', 'simple-membership' ). esc_attr($member_username) . '</p>';
		}
		
		//This is used to refresh the page so this shortcode is reloaded after a new subscription is added. 
		//This is needed for the newly created subscription to show up in the list.
		$output .= '<script>';
		$output .= 'document.addEventListener( "swpm_paypal_subscriptions_complete", function(){ window.location = window.location.href });';
		$output .= '</script>';

		$output .= '</div>';
		
		return $output;
	}
}
