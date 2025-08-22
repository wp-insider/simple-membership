<?php

class SwpmWpLoadedTasks {

	public function __construct() {

	}

	/*
	 * This is triggered after all plugins, themes and WP has loaded.
	 * It is triggered after init, plugins_loaded etc.
	 */
	public function do_wp_loaded_tasks() {
		$this->synchronise_swpm_logout_for_wp_users();

		//IPN listener
		$this->swpm_ipn_listener();

		//Cancel stripe subscription action listener (Used by the old Stripe subscription cancel shortcode). It will be removed in future.
		$cancel_stripe_sub_action = filter_input( INPUT_POST, 'swpm_do_cancel_stripe_sub', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $cancel_stripe_sub_action ) ) {
			if ( ! SwpmMemberUtils::is_member_logged_in() ) {
				//member not logged in
				SWPM_Utils_Subscriptions::cancel_msg( __( 'You are not logged in.', 'simple-membership' ) );
			}
			$member_id = SwpmMemberUtils::get_logged_in_members_id();
			(new SWPM_Utils_Subscriptions( $member_id ))->load_stripe_subscriptions()->handle_cancel_sub();
		}

		//Cancel subscription action listener (Used by the 'swpm_show_subscriptions_and_cancel_link' shortcode)
		if ( isset($_POST['swpm_do_cancel_sub']) ) {
			if ( ! SwpmMemberUtils::is_member_logged_in() ) {
				//member not logged in
				SWPM_Utils_Subscriptions::cancel_msg( __( 'You are not logged in.', 'simple-membership' ) );
			}
			$member_id = SwpmMemberUtils::get_logged_in_members_id();
            $subscriptions_list = new SWPM_Utils_Subscriptions( $member_id );
            $subscriptions_list->load_subs_data();
            $subscriptions_list->handle_cancel_sub();
		}
	}

	/*
	 * Logs out the user from the swpm session if they are logged out of the WP user session
	 */
	public function synchronise_swpm_logout_for_wp_users() {
		if ( function_exists('is_user_logged_in') && ! is_user_logged_in() ) {
			/* WP user is logged out. So logout the SWPM user (if applicable) */
			if ( SwpmMemberUtils::is_member_logged_in() ) {

				//Check if force WP user login sync is enabled or not
				$force_wp_user_sync = SwpmSettings::get_instance()->get_value( 'force-wp-user-sync' );
				if ( empty( $force_wp_user_sync ) ) {
					return '';
				}
				/* Force WP user login sync is enabled. */
				/* SWPM user is logged in the system. Log him out. */
				SwpmLog::log_auth_debug( 'synchronise_swpm_logout_for_wp_users() - Force wp user login sync is enabled. ', true );
				SwpmLog::log_auth_debug( 'WP user session is logged out for this user. So logging out of the swpm session also.', true );
				SwpmAuth::get_instance()->logout_silent_and_redirect();
			}
		}
	}

	/* Payment Gateway IPN listener */

	public function swpm_ipn_listener() {

		//Listen and handle PayPal IPN
		$swpm_process_ipn = filter_input( INPUT_GET, 'swpm_process_ipn' );
		if ( $swpm_process_ipn == '1' ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_pp_ipn.php';
			exit;
		}

		//Listen and handle Stripe (Legacy) Buy Now IPN
		$swpm_process_stripe_buy_now = filter_input( INPUT_GET, 'swpm_process_stripe_buy_now' );
		if ( $swpm_process_stripe_buy_now == '1' ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-stripe-buy-now-ipn.php';
			exit;
		}

		//Listen and handle Stripe (SCA) Buy Now IPN
		$swpm_process_stripe_sca_buy_now = filter_input( INPUT_GET, 'swpm_process_stripe_sca_buy_now' );
		if ( $swpm_process_stripe_sca_buy_now == '1' ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-stripe-sca-buy-now-ipn.php';
			exit;
		}

		//Handles all the Stripe Webhooks (Both SCA and Legacy)
		$hook = isset($_REQUEST['hook']) ? $_REQUEST['hook'] : '';
		if ( $hook == '1' ) {
			//The normal webhook URL is: SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL/?swpm_process_stripe_subscription=1&hook=1
			//Lets check for the 'swpm_process_stripe_subscription' query parameter to ensure we are on the correct endpoint
			$swpm_process_stripe_subscription = isset( $_REQUEST['swpm_process_stripe_subscription'] ) ? $_REQUEST['swpm_process_stripe_subscription'] : '';
			if( $swpm_process_stripe_subscription == '1'){
				include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-stripe-webhook-handler.php';
				exit;
			}
		}

		//Listen and handle Stripe (Legacy) Subscription IPN
		//This legacy button has been deprecated. Use the new option instead.
		$swpm_process_stripe_subscription = filter_input( INPUT_GET, 'swpm_process_stripe_subscription' );
		if ( $swpm_process_stripe_subscription == '1' ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-stripe-subscription-ipn.php';
			exit;
		}

		//Listen and handle Stripe (SCA) Subscription IPN
		$swpm_process_stripe_sca_subscription = filter_input( INPUT_GET, 'swpm_process_stripe_sca_subscription' );
		if ( $swpm_process_stripe_sca_subscription == '1' ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-stripe-sca-subscription-ipn.php';
			exit;
		}

		//Listen and handle Braintree Buy Now IPN
		$swpm_process_braintree_buy_now = filter_input( INPUT_GET, 'swpm_process_braintree_buy_now' );
		if ( $swpm_process_braintree_buy_now == '1' ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-braintree-buy-now-ipn.php';
			exit;
		}

		if ( wp_doing_ajax() ) {
			//Listen and handle smart paypal checkout IPN
			include SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-smart-checkout-ipn.php';
			add_action( 'wp_ajax_swpm_process_pp_smart_checkout', 'swpm_pp_smart_checkout_ajax_hanlder' );
			add_action( 'wp_ajax_nopriv_swpm_process_pp_smart_checkout', 'swpm_pp_smart_checkout_ajax_hanlder' );

			//Listen and handle Stripe SCA checkout session create requests.
			//Our Stripe session create request comes via ajax.
			require_once SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm-stripe-sca-checkout-session-create.php';
		}
	}

}
