<?php
/**
 * This class handles the webhook notifications from Stripe for all Stripe subscription payments.
 */
class SwpmStripeWebhookHandler {

	public function __construct() {
		$this->handle_stripe_webhook();
	}

	public function handle_stripe_webhook() {
		// This is Webhook notification from Stripe.
		// This webhook is used for all recurring payment notification (Legacy and SCA ones).

		$input = @file_get_contents( 'php://input' );
		if ( empty( $input ) ) {
			SwpmLog::log_simple_debug( 'Stripe subscription webhook sent empty data or page was accessed directly. Aborting.', false );
			echo 'Empty Webhook data received.';
			die;
		}
		// SwpmLog::log_simple_debug($input, true);
		$event_json = json_decode( $input );

		// Check if webhook event data needs to be validated.
		//More details here: https://stripe.com/docs/webhooks#signatures
		$webhook_signing_secret = SwpmSettings::get_instance()->get_value( 'stripe-webhook-signing-secret' );
		if ( ! empty( $webhook_signing_secret ) ) {
			SwpmLog::log_simple_debug( 'Stripe webhook signing secret is configured. Validating this webhook event...', true );
			$event_json = self::validate_webhook_data( $input );
			if ( empty( $event_json ) ) {
				//Invalid webhook data received. Don't process this request.
				http_response_code( 400 );
				echo 'Error: Invalid webhook data received.';
				exit();
			} else {
				SwpmLog::log_simple_debug( 'Stripe webhook event data validated successfully!', true );
			}
		}

		$type = isset($event_json->type) ? $event_json->type : '';
		$stripe_api_version = isset($event_json->api_version) ? $event_json->api_version : '';
		SwpmLog::log_simple_debug( sprintf( 'Stripe subscription webhook received: %s and api version: %s. Checking if we need to handle this webhook.', $type, $stripe_api_version ), true );

		if ( 'customer.subscription.deleted' === $type || 'charge.refunded' === $type ) {
			// Subscription expired or refunded event
			//SwpmLog::log_simple_debug( sprintf( 'Stripe Subscription Webhook %s received. Processing request...', $type ), true );

			// Let's form minimal ipn_data array for swpm_handle_subsc_cancel_stand_alone
			$customer                  = $event_json->data->object->customer;
			$subscr_id                 = $event_json->data->object->id;
			$ipn_data                  = array();
			$ipn_data['subscr_id']     = $subscr_id;
			$ipn_data['parent_txn_id'] = $customer;

			// Update subscription status of the subscription agreement record in transactions cpt table.
			SWPM_Utils_Subscriptions::update_subscription_agreement_record_status_to_cancelled( $subscr_id );

			swpm_handle_subsc_cancel_stand_alone( $ipn_data );
		}

		if ( $type === 'invoice.payment_succeeded' ) {
			$billing_reason = isset( $event_json->data->object->billing_reason ) ? $event_json->data->object->billing_reason : '';
			if ( $billing_reason == 'subscription_cycle' ) {
				//This is recurring/subscription payment invoice
				SwpmLog::log_simple_debug( sprintf( 'Stripe invoice.payment_succeeded webhook for subscription_cycle. This is a successful subscription charge. Capturing payment data.' ), true );

				// Get the subscription ID
				$sub_id = isset($event_json->data->object->subscription) ? $event_json->data->object->subscription : '';
				if (empty($sub_id)){
					//The subscription ID value is empty. Let's try to get it from the parent object.
					if( isset( $event_json->data->object->parent->subscription_details->subscription ) ){
						$sub_id = $event_json->data->object->parent->subscription_details->subscription;
					}
				}

				//$cust_id = $event_json->data->object->billing_reason;
				//$date = $event_json->data->object->date;
				$price_in_cents = $event_json->data->object->amount_paid; //amount in cents
				$currency_code  = $event_json->data->object->currency;

				$zero_cents = unserialize( SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS );
				if ( in_array( $currency_code, $zero_cents, true ) ) {
					$payment_amount = $price_in_cents;
				} else {
					$payment_amount = $price_in_cents / 100;// The amount (in cents). This value is used in Stripe API.
				}
				$payment_amount = floatval( $payment_amount );

				// Let's try to get first_name and last_name from full name
				$full_name   = $event_json->data->object->customer_name;
				$name_pieces = explode( ' ', $full_name, 2 );
				$first_name  = $name_pieces[0];
				if ( ! empty( $name_pieces[1] ) ) {
					$last_name = $name_pieces[1];
				}

				//Retrieve the member record for this subscription
				$member_record = SwpmMemberUtils::get_user_by_subsriber_id( $sub_id );
				if ( $member_record ) {
					// Found a member record
					$member_id           = $member_record->member_id;
					$membership_level_id = $member_record->membership_level;
					if ( empty( $first_name ) ) {
						$first_name = $member_record->first_name;
					}
					if ( empty( $last_name ) ) {
						$last_name = $member_record->last_name;
					}
				} else {
					SwpmLog::log_simple_debug( 'Could not find an existing member record for the given subscriber ID: ' . $sub_id . '. This user profile may have been deleted.', false );
					$member_id           = '';
					$membership_level_id = '';
				}

				// Retrieve the customer's email address.
				$customer_email = isset( $event_json->data->object->customer_email ) ? $event_json->data->object->customer_email : '';

				// Retrieve the transaction ID (Charge ID) for this payment.
				$txn_id = isset( $event_json->data->object->charge ) ? $event_json->data->object->charge : '';

				if ( empty( $txn_id ) ) {
					// This the newer version of stripe api (i.e. xxxx-xx-xx.basil) the event object does not contain charge id.
					$txn_id = self::get_charge_id_from_event_data( $event_json );
				}

				// Handle if it's a 100% discount. Charge id is not available for this case.
				if ( empty( $txn_id ) ) {
					if ( isset( $event_json->data->object->discount->coupon->percent_off ) && ( $event_json->data->object->discount->coupon->percent_off == 100 ) ) {
						// create dummy txn id.
						$txn_id = "free_sub_" . hash( "md5", $sub_id );
					}
				}

				//Create the custom field
				$custom_field_value = 'subsc_ref=' . $membership_level_id;
				$custom_field_value .= '&swpm_id=' . $member_id;

				// Create the $ipn_data array.
				$ipn_data                     = array();
				$ipn_data['mc_gross']         = $payment_amount;
				$ipn_data['first_name']       = $first_name;
				$ipn_data['last_name']        = $last_name;
				$ipn_data['payer_email']      = $customer_email;
				$ipn_data['membership_level'] = $membership_level_id;
				$ipn_data['txn_id']           = $txn_id;
				$ipn_data['subscr_id']        = $sub_id;
				$ipn_data['swpm_id']          = $member_id;
				$ipn_data['ip']               = '';
				$ipn_data['custom']           = $custom_field_value;
				$ipn_data['gateway']          = 'stripe-sca-subs';
				$ipn_data['txn_type']         = 'recurring_payment';
				$ipn_data['status']           = 'subscription';

				// Update the member's access start date if applicable.
				// It will update the access start date based on the membership level of the member (if needed)
				swpm_update_member_subscription_start_date_if_applicable( $ipn_data );

				// Save the transaction record
				SwpmTransactions::save_txn_record( $ipn_data );
				SwpmLog::log_simple_debug( 'Transaction data saved for Stripe subscription notification.', true );
			}
		}

		//End of the webhook notification execution.
		//Give 200 status then exit out.
		SwpmLog::log_simple_debug( 'End of Stripe subscription webhook processing. Webhook type: ' . $type, true );
		http_response_code( 200 ); // Tells Stripe we received this notification
	}

	public static function get_charge_id_from_event_data( $event_json ) {
		$invoice_id = isset($event_json->data->object->id) ? $event_json->data->object->id : null;
		if (empty($invoice_id)){
			SwpmLog::log_simple_debug( 'Invoice id could not be retrieved.', false );
			return null;
		}

		SwpmLog::log_simple_debug( 'Using invoice id: ' . $invoice_id . ' to retrieve charge_id.', true );

		$settings = SwpmSettings::get_instance();
		$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );

		$secret_key = null;

		// Try to get api secret key
		if (isset($event_json->data->object->object) && $event_json->data->object->object == 'subscription' ){
			$sub_id = $event_json->data->object->id;
			$sub_agreement_cpt_id = SWPM_Utils_Subscriptions::get_subscription_agreement_cpt_id_by_subs_id($sub_id);
			$payment_button_id = get_post_meta($sub_agreement_cpt_id, 'payment_button_id', true);
			$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $payment_button_id, !$sandbox_enabled );
			$secret_key = isset($api_keys['secret']) ? $api_keys['secret'] : '';
		}

		if (empty($secret_key)){
			SwpmLog::log_simple_debug( 'Using stripe secret key from global settings.', true );
			if ( $sandbox_enabled ) {
				$secret_key = $settings->get_value( 'stripe-test-secret-key' );
			} else {
				$secret_key = $settings->get_value( 'stripe-live-secret-key' );
			}
		}


		$charge_id = null;

		try {
			SwpmMiscUtils::load_stripe_lib();
			\Stripe\Stripe::setApiKey( $secret_key );

			$invoice = \Stripe\Invoice::retrieve( $invoice_id );

			if ( isset($invoice->charge) ){
				$charge_id = $invoice->charge;
			} else {
				SwpmLog::log_simple_debug( 'Error: charge id could not be retrieved from invoice object.', false );
			}

		} catch ( \Exception $e ) {
			SwpmLog::log_simple_debug( 'Error During invoice retrieval from invoice id. ' . $e->getMessage(), false );
		}

		return $charge_id;
	}

	public static function validate_webhook_data( $event_data_raw ){
		$event_json = json_decode( $event_data_raw );

		$sub_id = '';
		if ( isset($event_json->data->object->subscription) ){
			$sub_id = $event_json->data->object->subscription;
		} else if (isset( $event_json->data->object->id ) && $event_json->data->object->object == 'subscription' ){
			$sub_id = $event_json->data->object->id;
		}

		$sub_agreement_cpt_id = SWPM_Utils_Subscriptions::get_subscription_agreement_cpt_id_by_subs_id($sub_id);;

		// Check if the sandbox mode is enabled
		$sandbox_enabled = SwpmSettings::get_instance()->get_value( 'enable-sandbox-testing' );
		$webhook_signing_secret = SwpmSettings::get_instance()->get_value( 'stripe-webhook-signing-secret' );

		$payment_button_id = get_post_meta($sub_agreement_cpt_id, 'payment_button_id', true);

		$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $payment_button_id, !$sandbox_enabled );

		if (!isset($api_keys['secret'])){
			SwpmLog::log_simple_debug('Stripe API secret key could not be retrieved. Could not validate this webhook.', false);
			return false;
		}

		// Include the Stripe library.
		SwpmMiscUtils::load_stripe_lib();

		\Stripe\Stripe::setApiKey( $api_keys['secret'] );

		$stripe_signature_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

		try {
			$event_json = \Stripe\Webhook::constructEvent($event_data_raw, $stripe_signature_header, $webhook_signing_secret);
		} catch(\UnexpectedValueException $e) {
			// Invalid payload. Don't Process this request.
			SwpmLog::log_simple_debug('Error parsing payload: ' . $e->getMessage() , false);
			return false;
		} catch(\Stripe\Exception\SignatureVerificationException $e) {
			// Invalid signature. Don't Process this request.
			SwpmLog::log_simple_debug('Error verifying webhook signature: ' . $e->getMessage() , false);
			return false;
		}

		return $event_json;
	}

}

new SwpmStripeWebhookHandler();
