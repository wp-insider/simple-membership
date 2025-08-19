<?php

class SwpmShortcodesHandler {

	public $execution_success_notice = false;

	public $success_notice_pw_reset = false;

	public function __construct() {
		//Shortcode for the registration, login and profile forms
		add_shortcode("swpm_registration_form", array(&$this, 'registration_form'));
		add_shortcode('swpm_profile_form', array(&$this, 'profile_form'));
		add_shortcode('swpm_login_form', array(&$this, 'login_form_shortcode_output'));
		add_shortcode('swpm_reset_form', array(&$this, 'reset_password_shortcode_output'));

		//Register all the other shortcodes here
		add_shortcode( 'swpm_payment_button', array( &$this, 'swpm_payment_button_sc' ) );
		add_shortcode( 'swpm_thank_you_page_registration', array( &$this, 'swpm_ty_page_rego_sc' ) );
		add_shortcode( 'swpm_show_expiry_date', array( &$this, 'swpm_show_expiry_date_sc' ) );
		add_shortcode( 'swpm_mini_login', array( &$this, 'swpm_show_mini_login_sc' ) );
		add_shortcode( 'swpm_paypal_subscription_cancel_link', array( &$this, 'swpm_pp_cancel_subs_link_sc' ) );
		add_shortcode( 'swpm_stripe_subscription_cancel_link', array( $this, 'swpm_stripe_cancel_subs_link_sc' ) );
		add_shortcode( 'swpm_show_subscriptions_and_cancel_link', array( $this, 'swpm_show_subscriptions_and_cancel_link' ) );
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

		//Initialize the output variable.
		$output = '';
		$any_note_or_msg_output = '';
		$hide_payment_btn = false;

		//Check if the active subscription warning option is enabled for this button.
		if ( in_array($button_type, array('stripe_sca_subscription', 'pp_subscription_new')) ){
			$is_visitor_logged_in = SwpmAuth::get_instance()->is_logged_in();
			$show_warning_if_any_active_sub = get_post_meta( $button_id, 'show_warning_if_any_active_sub', true );

			if ( $is_visitor_logged_in && !empty($show_warning_if_any_active_sub) ){
				// The visitor is logged in and the active subs warning option is enabled.

				// Check if the user has any active subscription(s)
				$logged_in_member_id = SwpmMemberUtils::get_logged_in_members_id();
				$sub_utils = new SWPM_Utils_Subscriptions($logged_in_member_id);
				$last_active_sub = $sub_utils->get_last_active_sub_if_any();

				if (!empty($last_active_sub)){
					// Active subscription detected.

					// Check if the hide subscription button option is enabled for this button.
					$hide_btn_if_any_active_sub = get_post_meta( $button_id, 'hide_btn_if_any_active_sub', true );
					if( !empty($hide_btn_if_any_active_sub) && ($hide_btn_if_any_active_sub == 1) ){
						//Set the hide payment button flag to true.
						$hide_payment_btn = true;
					}

					// Warning message for the existing active subscription.
					$any_note_or_msg_output .= '<div class="swpm-warning-msg-for-existing-sub swpm-yellow-box">';
					$warning_msg_for_existing_sub = get_post_meta( $button_id, 'warning_msg_for_existing_sub', true );
					if (!empty($warning_msg_for_existing_sub)){
						//Use the custom warning message set for this button.
						$any_note_or_msg_output .= $warning_msg_for_existing_sub;
					} else {
						//Add a default warning message.
						$any_note_or_msg_output .= __('Note: You have an active subscription already.', 'simple-membership');
					}
					$any_note_or_msg_output .= '</div>';
				}
			}
		}

		/* Any notes or message section */
		//Trigger a filter to allow addons to modify the output of the note/message before the payment button.
		$any_note_or_msg_output = apply_filters( 'swpm_payment_button_note_msg_output', $any_note_or_msg_output, $button_id, $button_type );
		if ( !empty($any_note_or_msg_output) ){
			//If the active subscription warning message is set, we will show it before the payment button.
			$output = '<div class="swpm-payment-button-note-msg">' . $any_note_or_msg_output . $output . '</div>';
		}

		/* The payment button section */
		//Trigger a filter hook to allow custom code to modify the hide payment button flag. It can be used by addons to hide the payment button based on custom logic.
		$hide_payment_btn = apply_filters( 'swpm_hide_payment_button', $hide_payment_btn, $button_id, $button_type );
		if ( !$hide_payment_btn ){
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_button_shortcode_view.php' );
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/stripe_button_shortcode_view.php' );
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/stripe_sca_button_shortcode_view.php' );
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/braintree_button_shortcode_view.php' );
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_smart_checkout_button_shortcode_view.php' );
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_buy_now_new_button_shortcode_view.php' );
			include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/paypal_subscription_new_button_shortcode_view.php' );

			$button_code = '';
			$button_code = apply_filters( 'swpm_payment_button_shortcode_for_' . $button_type, $button_code, $args );

			$output .= '<div class="swpm-payment-button">' . $button_code . '</div>';
		}

		//Return the final output.
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
			'show_all_status' => '',
			'redirect_to_after_cancel' => '',
		), $atts);

		if ( ! SwpmMemberUtils::is_member_logged_in() ) {
			//member not logged in
			return '<p>'.__( 'You are not logged-in as a member.', 'simple-membership' ).'</p>';
		}

		//Get the member ID and load subscriptions utils class.
		$member_username = SwpmMemberUtils::get_logged_in_members_username();
		$member_id = SwpmMemberUtils::get_logged_in_members_id();

		$redirect_to_url = isset($atts['redirect_to_after_cancel']) ? sanitize_url($atts['redirect_to_after_cancel']) : '';

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
				$output .= SWPM_Utils_Subscriptions::get_cancel_subscription_output($subscription, $redirect_to_url);
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

	public function registration_form($atts) {
		//Trigger action hook
		do_action( 'swpm_shortcode_registration_form_start', $atts );

		$output = "";

		//Check if the form has been submitted and there is a success message.
		$any_notice_output = $this->capture_any_notice_output();
		if( !empty( $any_notice_output ) && $this->execution_success_notice ){
			//The registration form execution was a success. Return the success notice output string (it will be used with the shortcode output).
			return $any_notice_output;
		}

		//Check if free membership is enabled on the site.
		$is_free_enabled = SwpmSettings::get_instance()->get_value('enable-free-membership');
		$free_level_id = absint(SwpmSettings::get_instance()->get_value('free-membership-id'));
		$is_valid_free_level = SwpmMembershipLevelUtils::check_if_membership_level_exists($free_level_id);
		if( $is_free_enabled && !$is_valid_free_level ){
			//Free membership is enabled but the free level ID is invalid.
			//This is a critical configuration error. Show an error message and return.
			$output .= '<div class="swpm_error swpm-red-error-text">';
			$output .= __('Error! You have enabled free membership on this site but you did not enter a valid membership level ID in the "Free Membership Level ID" field of the settings menu.', 'simple-membership');
			$output .= '</div>';
			return $output;
		}

		//Get the level ID from the shortcode or use the free membership level ID if free membership is enabled.
		$level = isset($atts['level']) ? absint($atts['level']) : ($is_free_enabled ? $free_level_id : null);

		$output .= $any_notice_output;
		$output .= SwpmFrontRegistration::get_instance()->regigstration_ui($level);

		//Trigger action hook
		do_action( 'swpm_shortcode_registration_form_end', $atts );
		return $output;
	}

	public function profile_form() {
		$output = '';
		$auth = SwpmAuth::get_instance();
		$any_notice_output = $this->capture_any_notice_output();
		if ($auth->is_logged_in()) {
			$override_out = apply_filters('swpm_profile_form_override', '');
			if (!empty($override_out)) {
				return $override_out;
			}
			ob_start();
			echo $any_notice_output;//Include any output from the execution (for showing output inside the shortcode)
			//Load the edit profile template
			// SwpmUtilsTemplate::swpm_load_template('edit.php', false);
			$render_new_form_ui = SwpmSettings::get_instance()->get_value('use-new-form-ui');
			if (!empty($render_new_form_ui)) {
				SwpmUtilsTemplate::swpm_load_template('edit-v2.php', false);
			}else{
				SwpmUtilsTemplate::swpm_load_template('edit.php', false);
			}
			return ob_get_clean();
		}
		//User is not logged into the site. Show appropriate message.
		$output .= '<div class="swpm_profile_not_logged_in_msg">';
		$output .= SwpmUtils::_('You are not logged in.');
		$output .= '</div>';
		return $output;
	}

	public function login_form_shortcode_output() {
		ob_start();
		$auth = SwpmAuth::get_instance();
		if ($auth->is_logged_in()) {
			//Load the template for logged-in member
			SwpmUtilsTemplate::swpm_load_template('loggedin.php', false);
		} else {
			//Load JS only if option is set
			$display_password_toggle = SwpmSettings::get_instance()->get_value('password-visibility-login-form');
			if ( !empty( $display_password_toggle ) ){
				wp_enqueue_script('swpm.password-toggle');
			}
			//Load the login widget template
			SwpmUtilsTemplate::swpm_load_template('login.php', false);
		}
		return ob_get_clean();
	}

	public function reset_password_shortcode_output() {
		//Check if the form has been submitted and there is a success message.
		$any_notice_output = $this->capture_any_notice_output();
		if( !empty( $any_notice_output ) && $this->success_notice_pw_reset ){
			//The password reset form execution was a success. Return the success notice output string (it will be used with the shortcode output).
			return $any_notice_output;
		}

		if( isset( $_GET["action"]) && $_GET["action"] == "swpm-reset-using-link" ) {
			ob_start();
			echo $any_notice_output;//Include any output from the execution (for showing output inside the shortcode)
			//Load the reset password template
			SwpmUtilsTemplate::swpm_load_template('reset_password_using_link.php', false);
			return ob_get_clean();
		}
		else {
			ob_start();
			echo $any_notice_output;//Include any output from the execution (for showing output inside the shortcode)
			//Load the forgot password template
			SwpmUtilsTemplate::swpm_load_template('forgot_password.php', false);
			return ob_get_clean();
		}
	}

	/*
	 * Similar to $this->notices() function but instead of echoing the message, it returns the message (if any).
	 * Useful for using inside a shortcode output.
	 */
	public function capture_any_notice_output() {
		$output = '';
		$message = SwpmTransfer::get_instance()->get('status');
		if ( empty( $message ) ) {
			return $output;
		}

		if ( $message['succeeded'] ) {
			$output .= "<div id='swpm_message' class='swpm_success'>";
			$this->execution_success_notice = true;
		} else {
			$output .= "<div id='swpm_message' class='swpm_error'>";
		}
		$output .= $message['message'];
		$extra = isset( $message['extra'] ) ? $message['extra'] : array();
		if ( is_string($extra) ) {
			$output .= $extra;
		} else if ( is_array($extra) && !empty($extra) ) {
			$output .= '<ul>';
			foreach ($extra as $key => $value) {
				$output .= '<li class="' . esc_attr( $key ) . '">' . $value . '</li>';
			}
			$output .= '</ul>';
		}
		$output .= "</div>";
		//If password reset notice was sent, set the flag.
		if (isset($message['pass_reset_sent'])) {
			$this->success_notice_pw_reset = true;
		}
		return $output;
	}
}
