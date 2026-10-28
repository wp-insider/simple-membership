<?php
/*
* This class handles the new Stripe (SCA) Subscription buttons related functionality. 
* 
* The following steps outline how the Stripe Subscription (SCA) code flow:
* 1) When the Subscription button is clicked, the request is intercepted by JavaScript in 'views/payments/payment-gateway/stripe_sca_button_shortcode_view.php' at line 270, which then sends an AJAX request.
* 2) The AJAX request is processed by SwpmStripeCheckoutSessionCreate::handle_session_create(), which creates the checkout session and redirects the user to the checkout page.
* 3) After the payment is completed, Stripe redirects the customer to the URL SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_sca_subscription=1&ref_id=%s', which was set as the success_url parameter when the Stripe session was created in the previous step.
* 4) Our IPN listener checks for the query arg 'swpm_process_stripe_sca_subscription'. When it detects a payload at this request, it calls SwpmStripeSCASubscriptionIpnHandler::handle_stripe_ipn() to perform post-payment processing and save the transaction record.
* 5) Webhooks (background posts) are handled separately by the 'SwpmStripeWebhookHandler' class.
*/
class SwpmStripeSCASubscriptionIpnHandler {

	public function __construct() {
		//Refer to the comments at the top of the file for code flow details.
		//This class is initialized and run when a payload is present in the 'swpm_process_stripe_sca_subscription' query arg.
		$this->handle_stripe_ipn();
	}

	public function handle_stripe_ipn() {
		//This method gets executed only for direct post (not webhooks) when payload is present in the 'swpm_process_stripe_sca_subscription' query arg. 
		//So it is executed at the time of payment in the browser (via HTTP POST). When the "hook" query arg is not present.
		//The webhooks are handled by the "SwpmStripeWebhookHandler" class.

		SwpmLog::log_simple_debug( 'Stripe SCA Subscription IPN (HTTP POST) received. Processing request...', true );
		// SwpmLog::log_simple_debug(print_r($_REQUEST, true), true);//Useful for debugging purpose

		// Read and sanitize the request parameters.
		$ref_id = isset( $_GET['ref_id'] ) ? sanitize_text_field( stripslashes ( $_GET['ref_id'] ) ) : '';

		if ( empty( $ref_id ) ) {
			//no ref id provided, cannot proceed
			SwpmLog::log_simple_debug( 'Fatal Error! No ref_id provied.', false );
			wp_die( esc_html( 'Fatal Error! No ref_id provied.' ) );

		}

		//The ref_id is sent as "swpm_<hash>|<button_id>". If the pipe separator is missing but a percent-encoded
		//pipe is present, an intermediary double-encoded the value on the way back from Stripe. Decode and retry.
		if ( strpos( $ref_id, '|' ) === false && stripos( $ref_id, '%7C' ) !== false ) {
			$ref_id = rawurldecode( $ref_id );
		}

		$trans_info = explode( '|', $ref_id );
		$button_id  = isset( $trans_info[1] ) ? absint( $trans_info[1] ) : false;

		if ( ! SwpmMiscUtils::check_if_valid_payment_btn_cpt_id( $button_id ) ) {
			SwpmLog::log_simple_debug( 'Fatal Error! Invalid payment button ID received: ' . $button_id, false );
			wp_die( esc_html( 'Fatal Error! Invalid payment button ID.' ) );
		}

		// Retrieve the CPT for this button
		$button_cpt = get_post( $button_id );
		if ( ! $button_cpt ) {
			// Fatal error. Could not find this payment button post object.
			SwpmLog::log_simple_debug( 'Fatal Error! Failed to retrieve the payment button post object for the given button ID: ' . $button_id, false );
			wp_die( esc_html( sprintf( 'Fatal Error! Payment button (ID: %d) does not exist. This request will fail.', $button_id ) ) );
		}

		$settings = SwpmSettings::get_instance();
		$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );

		//API keys
		$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $button_id, ! $sandbox_enabled );

		// Include the Stripe library.
		SwpmMiscUtils::load_stripe_lib();

		try {
			\Stripe\Stripe::setApiKey( $api_keys['secret'] );

			$events = \Stripe\Event::all(
				array(
					'type'    => 'checkout.session.completed',
					'created' => array(
						'gte' => time() - 60 * 60,
					),
				)
			);

			$sess = false;

			foreach ( $events->autoPagingIterator() as $event ) {
				$session = $event->data->object;
				if ( isset( $session->client_reference_id ) && $session->client_reference_id === $ref_id ) {
					$sess = $session;
					break;
				}
			}

			if ( false === $sess ) {
				// Can't find session.
				$error_msg = sprintf( "Fatal error! Payment with ref_id %s can't be found", $ref_id );
				SwpmLog::log_simple_debug( $error_msg, false );
				wp_die( esc_html( $error_msg ) );
				return;
			}

			$sub_id = isset( $sess->subscription ) ? $sess->subscription : '';

			$sub = \Stripe\Subscription::retrieve( $sub_id );
		} catch ( Exception $e ) {
			$error_msg = 'Error occurred: ' . $e->getMessage();
			SwpmLog::log_simple_debug( $error_msg, false );
			wp_die( esc_html( $error_msg ) );
		}

		$pm = \Stripe\PaymentMethod::retrieve( $sub->default_payment_method );

		// Grab the charge ID and set it as the transaction ID.
		$txn_id = $sub->customer;
		// The charge ID can be used to retrieve the transaction details using hte following call.
		// \Stripe\Charge::retrieve($charge->$data[0]->id);

		//check if this payment has already been processed
		$payment = get_posts(
			array(
				'meta_key'       => 'txn_id',
				'meta_value'     => $txn_id,
				'posts_per_page' => 1,
				'offset'         => 0,
				'post_type'      => 'swpm_transactions',
			)
		);
		wp_reset_postdata();

		if ( $payment ) {
			//payment has already been processed. Redirecting user to return_url
			$return_url = get_post_meta( $button_id, 'return_url', true );
			if ( empty( $return_url ) ) {
				$return_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
			}
			SwpmMiscUtils::redirect_to_url( $return_url );
			return;
		}

		$price_in_cents = $sess->amount_total;
		$currency_code  = strtoupper( $sub->plan->currency );

		$zero_cents = unserialize( SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS );
		if ( in_array( $currency_code, $zero_cents, true ) ) {
			$payment_amount = $price_in_cents;
		} else {
			$payment_amount = $price_in_cents / 100;// The amount (in cents). This value is used in Stripe API.
		}

		$payment_amount = floatval( $payment_amount );

		$payment_amount = apply_filters( 'swpm_payment_amount_filter', $payment_amount, $button_id );

		$membership_level_id = get_post_meta( $button_id, 'membership_level_id', true );

		// Everything went ahead smoothly with the charge.
		SwpmLog::log_simple_debug( 'Stripe SCA Subscription charge successful.', true );

		$customer = \Stripe\Customer::retrieve( $txn_id );

		$stripe_email = $customer->email;

		$user_ip = SwpmUtils::get_user_ip_address();

		//Custom field data
		$custom_field_value  = 'subsc_ref=' . $membership_level_id;
		$custom_field_value .= '&user_ip=' . $user_ip;
		if ( SwpmMemberUtils::is_member_logged_in() ) {
			$custom_field_value .= '&swpm_id=' . SwpmMemberUtils::get_logged_in_members_id();
		}
		$custom_field_value = apply_filters( 'swpm_custom_field_value_filter', $custom_field_value );

		$custom = $custom_field_value;

                //Override custom value if necessary (for subscription webhooks)
                if ( isset ( $sub_id )){
                    //This is a subscription payment notificataion. Lets check if the original custom field value is already saved in the CPT for this stripe subscription.
                    $orig_custom_value = SwpmTransactions::get_original_custom_value_for_subscription_payment($sub_id);
                    if ( !empty ($orig_custom_value)){
                        //Override the custom field value with the original transactions value that can contain additional cookie and session info.
                        $custom = $orig_custom_value;
                        SwpmLog::log_simple_debug( 'Custom field value overriden with original value. Custom: ' . $custom, true );
                    }
                }

		$custom_var = SwpmTransactions::parse_custom_var( $custom );
		$swpm_id    = isset( $custom_var['swpm_id'] ) ? $custom_var['swpm_id'] : '';

		// Let's try to get first_name and last_name from full name
		$name       = $pm->billing_details->name;
		$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
		$first_name = trim( preg_replace( '#' . $last_name . '#', '', $name ) );

		// Create the $ipn_data array.
		$ipn_data                     = array();
		$ipn_data['mc_gross']         = $payment_amount;
		$ipn_data['first_name']       = $first_name;
		$ipn_data['last_name']        = $last_name;
		$ipn_data['payer_email']      = $stripe_email;
		$ipn_data['membership_level'] = $membership_level_id;
		$ipn_data['txn_id']           = $txn_id;
		$ipn_data['subscr_id']        = $sub_id;
		$ipn_data['swpm_id']          = $swpm_id;
		$ipn_data['ip']               = $custom_var['user_ip'];
		$ipn_data['custom']           = $custom;
		$ipn_data['gateway']          = 'stripe-sca-subs';
		$ipn_data['txn_type']		  = 'stripe_subscription_new';//Can be used to find sub-created type transactions.
		$ipn_data['status']           = 'subscription created';

		$bd_addr = $pm->billing_details->address;

		$ipn_data['address_street']  = isset( $bd_addr->line1 ) ? $bd_addr->line1 : '';
		$ipn_data['address_city']    = isset( $bd_addr->city ) ? $bd_addr->city : '';
		$ipn_data['address_state']   = isset( $bd_addr->state ) ? $bd_addr->state : '';
		$ipn_data['address_zipcode'] = isset( $bd_addr->postal_code ) ? $bd_addr->postal_code : '';

		//Get country value from the Stripe response. It can be a country code or a country name.
		$country = isset( $bd_addr->country ) ? $bd_addr->country : '';
		if( strlen($country) == 2 ){//We have a country code. Let's convert it to country name.
			$ipn_data['address_country'] = SwpmMiscUtils::get_country_name_by_country_code($country);
		} else {
			$ipn_data['address_country'] = $country;
		}		

		$ipn_data['payment_button_id'] = $button_id;
		$ipn_data['is_live']           = ! $sandbox_enabled;

		// Handle the membership signup related tasks.
		swpm_handle_subsc_signup_stand_alone( $ipn_data, $membership_level_id, $txn_id, $swpm_id );

		// Save the transaction record
		SwpmTransactions::save_txn_record( $ipn_data );
		SwpmLog::log_simple_debug( 'Transaction data saved.', true );

		// Save the first payment (charge) of the subscription as its own transaction record (the record above is the subscription agreement).
		self::save_first_payment_record( $sub, $ipn_data );

		// Trigger the stripe IPN processed action hook (so other plugins can can listen for this event).
		do_action( 'swpm_stripe_sca_ipn_processed', $ipn_data );

		do_action( 'swpm_payment_ipn_processed', $ipn_data );

		SwpmLog::log_simple_debug( 'End of Stripe SCA Subscription IPN processing.', true, true );

		// Redirect the user to the registration URL or return URL (or to the homepage if a return URL is not specified for this payment button).
		SwpmMiscUtils::handle_after_payment_redirect( $button_id );
	}

	/*
	 * Saves the first payment (charge) of a new subscription as its own transaction record, with the charge ID as the transaction ID.
	 * This way every subscription charge has a payment record (the renewal payments are saved by the webhook handler). It also lets a refund of the first charge find its record.
	 * It is skipped (nothing is saved) when there is no charge for the first invoice (example: a free trial) or the invoice details can't be retrieved.
	 */
	public static function save_first_payment_record( $sub, $ipn_data ) {
		$invoice_id = isset( $sub->latest_invoice ) ? $sub->latest_invoice : '';
		if ( is_object( $invoice_id ) ) {
			$invoice_id = isset( $invoice_id->id ) ? $invoice_id->id : '';
		}
		if ( empty( $invoice_id ) ) {
			SwpmLog::log_simple_debug( 'Stripe subscription first payment: the subscription has no invoice ID. The first payment record will not be saved.', true );
			return;
		}

		try {
			//Use the same API version as the webhook handler, so the invoice has the same structure (the charge ID is in the payment intent of the invoice payment).
			$invoice = \Stripe\Invoice::retrieve(
				array(
					'id'     => $invoice_id,
					'expand' => array( 'payments.data.payment.payment_intent' ),
				),
				array( 'stripe_version' => '2026-07-29.dahlia' )
			);
		} catch ( \Exception $e ) {
			SwpmLog::log_simple_debug( 'Stripe subscription first payment: could not retrieve the invoice (' . $invoice_id . '). The first payment record will not be saved. ' . $e->getMessage(), false );
			return;
		}

		$amount_paid = isset( $invoice->amount_paid ) ? (int) $invoice->amount_paid : 0;
		if ( ! isset( $invoice->status ) || 'paid' !== $invoice->status || $amount_paid <= 0 ) {
			//Example: a free trial or a 100% discount. There is no charge for the first invoice.
			SwpmLog::log_simple_debug( 'Stripe subscription first payment: no charge for the first invoice (' . $invoice_id . '). The first payment record will not be saved.', true );
			return;
		}

		//Get the charge ID of the invoice payment.
		$charge_id = '';
		if ( isset( $invoice->charge ) && is_string( $invoice->charge ) ) {
			// In old api version, the charge id used to be directly available on the invoice object.
			$charge_id = $invoice->charge;
		} else if ( isset( $invoice->payments->data ) ) {
			foreach ( $invoice->payments->data as $invoice_payment ) {
				if ( ! isset( $invoice_payment->status ) || 'paid' !== $invoice_payment->status ) {
					continue;
				}
				if ( isset( $invoice_payment->payment->payment_intent->latest_charge ) ) {
					$charge_id = $invoice_payment->payment->payment_intent->latest_charge;
				} else if ( isset( $invoice_payment->payment->charge ) ) {
					$charge_id = $invoice_payment->payment->charge;
				}
				break;
			}
		}
		if ( is_object( $charge_id ) ) {
			$charge_id = isset( $charge_id->id ) ? $charge_id->id : '';
		}
		if ( empty( $charge_id ) ) {
			SwpmLog::log_simple_debug( 'Stripe subscription first payment: could not find the charge ID of the invoice (' . $invoice_id . '). The first payment record will not be saved.', false );
			return;
		}

		//Check if this charge has already been saved.
		if ( SwpmTransactions::get_transaction_row_by_txn_id( $charge_id ) ) {
			SwpmLog::log_simple_debug( 'Stripe subscription first payment: a transaction record already exists for the charge ID: ' . $charge_id, true );
			return;
		}

		//The amount that was charged.
		$currency_code = isset( $invoice->currency ) ? strtoupper( $invoice->currency ) : '';
		$zero_cents    = unserialize( SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS );
		if ( in_array( $currency_code, $zero_cents, true ) ) {
			$payment_amount = $amount_paid;
		} else {
			$payment_amount = $amount_paid / 100;
		}

		//Same data as the subscription agreement record, with the details of this payment.
		$payment_ipn_data             = $ipn_data;
		$payment_ipn_data['txn_id']   = $charge_id;
		$payment_ipn_data['mc_gross'] = floatval( $payment_amount );
		$payment_ipn_data['txn_type'] = 'stripe_subscription_first_payment';
		$payment_ipn_data['status']   = 'subscription';//Same status as the renewal payments of the subscription.

		SwpmTransactions::save_txn_record( $payment_ipn_data );
		SwpmLog::log_simple_debug( 'Stripe subscription first payment record saved. Charge ID: ' . $charge_id . ', Subscription ID: ' . $payment_ipn_data['subscr_id'], true );
	}
}

new SwpmStripeSCASubscriptionIpnHandler();
