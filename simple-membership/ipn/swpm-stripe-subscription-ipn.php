<?php

require SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_subsc_ipn.php';

class SwpmStripeSubscriptionIpnHandler {

	public function __construct() {

		$this->handle_stripe_ipn();
	}

	public function handle_stripe_ipn() {
		if ( isset( $_GET['hook'] ) ) {
			// this is Webhook notify from Stripe
			// TODO: add Webhook Signing Secret verification
			// To do this, we need to get customer ID, retreive its details from Stripe, get button_id from metadata
			// and see if the button has Signing Secret option set. If it is - we need to check signatures
			// More details here: https://stripe.com/docs/webhooks#signatures

			$input = @file_get_contents( 'php://input' );
			if ( empty( $input ) ) {
				SwpmLog::log_simple_debug( 'Stripe Subscription Webhook sent empty data or page was accessed directly. Aborting.', false );
				echo 'Empty Webhook data received.';
				die;
			}
			// SwpmLog::log_simple_debug($input, true);
			$event_json = json_decode( $input );

			$type = $event_json->type;

			if ( 'customer.subscription.deleted' === $type || 'charge.refunded' === $type ) {
				// Subscription expired or refunded event
				SwpmLog::log_simple_debug( 'Stripe Subscription Webhook received. Processing request...', true );
				// Let's form minimal ipn_data array for swpm_handle_subsc_cancel_stand_alone
				$customer                  = $event_json->data->object->customer;
				$subscr_id                 = $event_json->data->object->id;
				$ipn_data                  = array();
				$ipn_data['subscr_id']     = $subscr_id;
				$ipn_data['parent_txn_id'] = $customer;

				swpm_handle_subsc_cancel_stand_alone( $ipn_data );
			}
			http_response_code( 200 ); // tells Stripe we received this notify
			return;
		}

		SwpmLog::log_simple_debug( 'Stripe Subscription IPN received. Processing request...', true );
		// SwpmLog::log_simple_debug(print_r($_REQUEST, true), true);//Useful for debugging purpose
		// Include the Stripe library.
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			include SIMPLE_WP_MEMBERSHIP_PATH . 'lib/stripe-gateway/init.php';
		}
		// Read and sanitize the request parameters.
		$button_id    = sanitize_text_field( $_REQUEST['item_number'] );
		$button_id    = absint( $button_id );
		$button_title = sanitize_text_field( $_REQUEST['item_name'] );

		$stripe_token      = filter_input( INPUT_POST, 'stripeToken', FILTER_SANITIZE_STRING );
		$stripe_token_type = filter_input( INPUT_POST, 'stripeTokenType', FILTER_SANITIZE_STRING );
		$stripe_email      = filter_input( INPUT_POST, 'stripeEmail', FILTER_SANITIZE_EMAIL );

		// Retrieve the CPT for this button
		$button_cpt = get_post( $button_id );
		if ( ! $button_cpt ) {
			// Fatal error. Could not find this payment button post object.
			SwpmLog::log_simple_debug( 'Fatal Error! Failed to retrieve the payment button post object for the given button ID: ' . $button_id, false );
			wp_die( esc_html( sprintf( 'Fatal Error! Payment button (ID: %d) does not exist. This request will fail.', $button_id ) ) );
		}

		$plan_id = get_post_meta( $button_id, 'stripe_plan_id', true );
		$descr   = 'Subscription to "' . $plan_id . '" plan';

		$membership_level_id = get_post_meta( $button_id, 'membership_level_id', true );

		// Validate and verify some of the main values.
		// Validation passed. Go ahead with the charge.
		// Sandbox and other settings
		$settings        = SwpmSettings::get_instance();
		$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );
		if ( $sandbox_enabled ) {
			SwpmLog::log_simple_debug( 'Sandbox payment mode is enabled. Using test API key details.', true );
			$secret_key = get_post_meta( $button_id, 'stripe_test_secret_key', true ); // Use sandbox API key
		} else {
			$secret_key = get_post_meta( $button_id, 'stripe_live_secret_key', true ); // Use live API key
		}

		// Set secret API key in the Stripe library
		\Stripe\Stripe::setApiKey( $secret_key );

		// Get the credit card details submitted by the form
		$token = $stripe_token;

		// Create the charge on Stripe's servers - this will charge the user's card
		try {
			$customer = \Stripe\Customer::create(
				array(
					'description'     => $descr,
					'email'           => $stripe_email,
					'source'          => $token,
					'plan'            => $plan_id,
					'trial_from_plan' => 'true',
				)
			);
		} catch ( Exception $e ) {
			SwpmLog::log_simple_debug( 'Error occurred during Stripe Subscribe. ' . $e->getMessage(), false );
			$body         = $e->getJsonBody();
			$error        = $body['error'];
			$error_string = print_r( $error, true );
			SwpmLog::log_simple_debug( 'Error details: ' . $error_string, false );
			wp_die( esc_html( 'Stripe Subscription Error! ' . $e->getMessage() . $error_string ) );
		}

		// Everything went ahead smoothly with the charge.
		SwpmLog::log_simple_debug( 'Stripe Subscription successful.', true );

		// let's add button_id to metadata
		$customer->metadata = array( 'button_id' => $button_id );
		try {
			$customer->save();
		} catch ( Exception $e ) {
			SwpmLog::log_simple_debug( 'Error occurred during Stripe customer metadata update. ' . $e->getMessage(), false );
			$body = $e->getJsonBody();
			SwpmLog::log_simple_debug( 'Error details: ' . $error_string, false );
		}

		// Grab customer ID and set it as the transaction ID.
		$txn_id = $customer->id; // $charge->balance_transaction;
		// Grab subscription ID
		$subscr_id  = $customer->subscriptions->data[0]->id;
		$custom     = sanitize_text_field( $_REQUEST['custom'] );
		$custom_var = SwpmTransactions::parse_custom_var( $custom );
		$swpm_id    = isset( $custom_var['swpm_id'] ) ? $custom_var['swpm_id'] : '';

		$payment_amount = $customer->subscriptions->data[0]->plan->amount / 100;

		// Create the $ipn_data array.
		$ipn_data                     = array();
		$ipn_data['mc_gross']         = $payment_amount;
		$ipn_data['first_name']       = '';
		$ipn_data['last_name']        = '';
		$ipn_data['payer_email']      = $stripe_email;
		$ipn_data['membership_level'] = $membership_level_id;
		$ipn_data['txn_id']           = $txn_id;
		$ipn_data['subscr_id']        = $subscr_id . '|' . $button_id;
		$ipn_data['swpm_id']          = $swpm_id;
		$ipn_data['ip']               = $custom_var['user_ip'];
		$ipn_data['custom']           = $custom;
		$ipn_data['gateway']          = 'stripe';
		$ipn_data['status']           = 'completed';

		$ipn_data['address_street']  = '';
		$ipn_data['address_city']    = '';
		$ipn_data['address_state']   = '';
		$ipn_data['address_zipcode'] = '';
		$ipn_data['country']         = '';

		// Handle the membership signup related tasks.
		swpm_handle_subsc_signup_stand_alone( $ipn_data, $membership_level_id, $txn_id, $swpm_id );

		// Save the transaction record
		SwpmTransactions::save_txn_record( $ipn_data );
		SwpmLog::log_simple_debug( 'Transaction data saved.', true );

		// Trigger the stripe IPN processed action hook (so other plugins can can listen for this event).
		do_action( 'swpm_stripe_ipn_processed', $ipn_data );

		do_action( 'swpm_payment_ipn_processed', $ipn_data );

		// Redirect the user to the return URL (or to the homepage if a return URL is not specified for this payment button).
		$return_url = get_post_meta( $button_id, 'return_url', true );
		if ( empty( $return_url ) ) {
			$return_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
		}
		SwpmLog::log_simple_debug( 'Redirecting customer to: ' . $return_url, true );
		SwpmLog::log_simple_debug( 'End of Stripe Subscription IPN processing.', true, true );
		SwpmMiscUtils::redirect_to_url( $return_url );
	}

}

$swpm_stripe_subscription_ipn = new SwpmStripeSubscriptionIpnHandler();
