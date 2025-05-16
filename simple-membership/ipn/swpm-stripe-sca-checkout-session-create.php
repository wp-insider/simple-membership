<?php

class SwpmStripeCheckoutSessionCreate{

	public function __construct() {
		//Our Stripe session create request comes via ajax.
		if ( wp_doing_ajax() ) {
            //check if this is session create request
			$action = isset( $_POST['action'] ) ? sanitize_text_field( stripslashes ( $_POST['action'] ) ) : '';
			if ( 'swpm_stripe_sca_create_checkout_session' === $action ) {
                //handle session create request
				add_action( 'wp_ajax_swpm_stripe_sca_create_checkout_session', array( $this, 'handle_session_create' ) );
				add_action( 'wp_ajax_nopriv_swpm_stripe_sca_create_checkout_session', array( $this, 'handle_session_create' ) );
			}
		}
    }

	public function handle_session_create() {
		$button_id = filter_input( INPUT_POST, 'swpm_button_id', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $button_id ) ) {
			wp_send_json( array( 'error' => 'No button ID provided' ) );
		}

        SwpmLog::log_simple_debug( 'Stripe SCA checkout session create request received. Processing request...', true );

		//Check if payment_method_types is being used in the shortcode.
		$payment_method_types = isset( $_POST['payment_method_types'] ) ? sanitize_text_field( stripslashes ( $_POST['payment_method_types'] ) ) : '';
		if ( empty( $payment_method_types ) ) {
			//Use the empty value so it can be managed from the seller's Stripe account settings.
			$payment_method_types_array = array();
			//$payment_method_types_array = array( 'card' );//Legacy value
		} else {
			//Use the payment_method_types specified in the shortcode (example value: card,us_bank_account
			$payment_method_types_array = array_map( 'trim', explode (",", $payment_method_types) );
        }

		//Get the settings instance.
		$settings = SwpmSettings::get_instance();

		//Retrieve the CPT for this button
		$button_cpt = get_post( $button_id ); 
		$item_name  = htmlspecialchars( $button_cpt->post_title );

		$plan_id = get_post_meta( $button_id, 'stripe_plan_id', true );

		if ( empty( $plan_id ) ) {
			//This is a one-off payment button.
			//Get payment amount and currency
			$payment_amount = get_post_meta( $button_id, 'payment_amount', true );
			if ( ! is_numeric( $payment_amount ) ) {
				wp_send_json( array( 'error' => 'Error! The payment amount value of the button must be a numeric number. Example: 49.50' ) );
			}

			$payment_currency = get_post_meta( $button_id, 'payment_currency', true );
			$payment_amount   = round( $payment_amount, 2 ); //round the amount to 2 decimal place.

			$payment_amount = apply_filters( 'swpm_payment_amount_filter', $payment_amount, $button_id );

			$zero_cents = unserialize( SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS );
			if ( in_array( $payment_currency, $zero_cents ) ) {
				//this is zero-cents currency, amount shouldn't be multiplied by 100
				$price_in_cents = $payment_amount;
			} else {
				$price_in_cents = $payment_amount * 100; //The amount (in cents). This value is passed to Stripe API.
			}
			$payment_amount_formatted = SwpmMiscUtils::format_money( $payment_amount, $payment_currency );
		}

		//$button_image_url = get_post_meta($button_id, 'button_image_url', true);//Stripe doesn't currenty support button image for their standard checkout.
		
		//Get user's IP address.
		$user_ip = SwpmUtils::get_user_ip_address();
		if (empty($user_ip)){
			//We use the IP address for reference so this is a required field.
			//If we can't get the IP address, we can't proceed with the payment.
			//Log the error and send a JSON response.
			$error_msg = __("Unable to detect the visitor's IP address. Checkout request cannot proceed.", 'simple-membership');
			SwpmLog::log_simple_debug($error_msg, false);
			wp_send_json( array( 'error' => 'Error occurred: ' . $error_msg ) );
		}

		//Save the IP address in the session for later use.
		$_SESSION['swpm_payment_button_interaction'] = $user_ip;

		//Get the button's level ID
		$membership_level_id = get_post_meta( $button_id, 'membership_level_id', true );

		//Custom field data
		$custom_field_value  = 'subsc_ref=' . $membership_level_id;
		$custom_field_value .= '&user_ip=' . $user_ip;
		if ( SwpmMemberUtils::is_member_logged_in() ) {
			$custom_field_value .= '&swpm_id=' . SwpmMemberUtils::get_logged_in_members_id();
		}
		$custom_field_value = apply_filters( 'swpm_custom_field_value_filter', $custom_field_value );

		//Sandbox settings
		$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );

		//API keys
		$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $button_id, ! $sandbox_enabled );

		//Billing address
		$billing_address = isset( $args['billing_address'] ) ? '1' : '';
		//By default don't show the billing address in the checkout form.
		//If billing_address parameter is not present in the shortcode, let's check button option
		if ( $billing_address === '' ) {
			$collect_address = get_post_meta( $button_id, 'stripe_collect_address', true );
			if ( $collect_address === '1' ) {
				//Collect Address enabled in button settings
				$billing_address = 1;
			}
		}

		//Check automatic tax setting.
		$automatic_tax = false;
		$automatic_tax_opt = get_post_meta( $button_id, 'stripe_automatic_tax', true );
		if ( $automatic_tax_opt === '1' ) {
				$automatic_tax = true;
		}

		//Generate a reference ID for this Stripe transaction
		$hashed_ip = md5($user_ip);
		$ref_id = 'swpm_' . $hashed_ip . '|' . $button_id;

		//Return, cancel, notifiy URLs.
		if ( empty( $plan_id ) ) {
			$notify_url = sprintf( SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_sca_buy_now=1&ref_id=%s', $ref_id );
		} else {
			$notify_url = sprintf( SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_sca_subscription=1&ref_id=%s', $ref_id );
		}

		//The url to redirect to when user clicks on the back button in the stripe sca buy now button checkout page. If no url set, there will be no back button.
		$cancel_url = get_post_meta( $button_id, 'cancel_url', true );
		$cancel_url = ! empty( $cancel_url ) ? sanitize_text_field($cancel_url) : SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;

		//prefill member email
		$prefill_member_email = $settings->get_value( 'stripe-prefill-member-email' );
		if ( $prefill_member_email ) {
			$auth = SwpmAuth::get_instance();
			$member_email = $auth->get( 'email' );
		}

		//Load the Stripe library.
		SwpmMiscUtils::load_stripe_lib();

		try {
			\Stripe\Stripe::setApiKey( $api_keys['secret'] );
			\Stripe\Stripe::setApiVersion("2025-02-24.acacia");
			if ( empty( $plan_id ) ) {
				//This is one-off payment
				$opts = array(
					'client_reference_id'        => $ref_id,
					'billing_address_collection' => $billing_address ? 'required' : 'auto',					
					'line_items' => array(
						array(
							'price_data'  => array(
								'currency' => $payment_currency,
								'unit_amount'      => $price_in_cents,
								'product_data' => array(
									'name'        => $item_name,
									'description' => $payment_amount_formatted,
								),
							),
							'quantity'    => 1
						)
					),					
					'mode' => 'payment',
					'success_url'                => $notify_url,
					'cancel_url'                 => $cancel_url,
				);
			} else {
				//this is subscription payment
				$opts = array(
					'client_reference_id'        => $ref_id,
					'billing_address_collection' => $billing_address ? 'required' : 'auto',					
					'line_items' => array(
						array(
						'price' => $plan_id,
						'quantity'    => 1
						),
						
				),
					'mode' => 'subscription',
					'success_url'                => $notify_url,
					'cancel_url'                 => $cancel_url,
				);

				$trial_period = get_post_meta( $button_id, 'stripe_trial_period', true );
				$trial_period = absint( $trial_period );
				if ( $trial_period ) {
					$opts['subscription_data']['trial_period_days'] = $trial_period;
				}
			}

			//Set payment method types (if used in the shortcode). Otherwise, let Stripe use the default value.
			if( !empty( $payment_method_types_array ) ) {
				$opts['payment_method_types'] = $payment_method_types_array;
			}

			//Set the logo for the line item.
			if ( ! empty( $item_logo ) ) {
				$opts['line_items'][0]["product_data"]['images'] = array( $item_logo );
			}

			//Set the customer email.
			if ( ! empty( $member_email ) ) {
				$opts['customer_email'] = $member_email;
			}

			//Set the automatic tax feature.
			if( $automatic_tax == true ) {
				$opts["automatic_tax"] = array( "enabled" => true );
			}

			$allow_promotion_codes = get_post_meta( $button_id, 'allow_promotion_codes', true );
			if ( !empty($allow_promotion_codes) && $allow_promotion_codes == '1' ) {
				$opts["allow_promotion_codes"] = true;
			}
			
			$opts = apply_filters( 'swpm_stripe_sca_session_opts', $opts, $button_id );

			$session = \Stripe\Checkout\Session::create( $opts );
		} catch ( Exception $e ) {
			$err = $e->getMessage();
			wp_send_json( array( 'error' => 'Error occurred: ' . $err ) );
		}

        SwpmLog::log_simple_debug( 'Stripe SCA checkout session created successfully.', true );
		wp_send_json( array( 'session_id' => $session->id ) );
	}

}

new SwpmStripeCheckoutSessionCreate();
