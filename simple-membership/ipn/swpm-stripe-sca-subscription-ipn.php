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

		require_once SIMPLE_WP_MEMBERSHIP_PATH . 'lib/stripe-util-functions.php';

		// The customer ID is the transaction ID of the "subscription created" record (the subscription agreement).
		// The first payment gets its own record with its charge ID (see save_first_payment_record()).
		$customer_id = StripeUtilFunctions::get_object_id( $sub->customer );
		$txn_id      = $customer_id;

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

		// The charge of the first payment. False: the customer ID is used as the payment's transaction ID.
		$first_payment = self::get_first_payment_charge( $sess, $ref_id, $sub_id, $customer_id, $api_keys['secret'], ! $sandbox_enabled, $price_in_cents, $payment_amount, $currency_code );
		if ( $first_payment && SwpmTransactions::get_transaction_row_by_txn_id( $first_payment['charge_id'] ) ) {
			//This first payment has already been processed. Redirecting user to return_url
			$return_url = get_post_meta( $button_id, 'return_url', true );
			if ( empty( $return_url ) ) {
				$return_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
			}
			SwpmMiscUtils::redirect_to_url( $return_url );
			return;
		}

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

		// Save the transaction record (the "subscription created" record of the agreement)
		SwpmTransactions::save_txn_record( $ipn_data );
		$agreement_id = self::get_saved_record_id( $ipn_data, 'subscription created', 'stripe_subscription_new' );

		// Then the first payment's own record. The hooks get the agreement's data; when the first payment record is saved,
		// with the charge ID as the transaction ID (so a refund of the charge finds the payment and the commission).
		$hook_ipn_data = $ipn_data;
		if ( ! $agreement_id ) {
			$first_payment_saved = 'uncertain';
		} else {
			SwpmLog::log_simple_debug( 'Transaction data saved.', true );
			$first_payment_saved = $first_payment ? self::save_first_payment_record( $agreement_id, $ipn_data, $first_payment ) : 'not_saved';
		}
		if ( 'saved' === $first_payment_saved ) {
			$hook_ipn_data['txn_id']             = $first_payment['charge_id'];
			$hook_ipn_data['stripe_customer_id'] = $customer_id;
		}

		if ( 'uncertain' === $first_payment_saved ) {
			// No hook when a record may be incomplete (a commission would be given with a reference a refund can't
			// find, or twice when the page is opened again): the payment needs a manual check.
			SwpmLog::log_simple_debug( 'Error! The payment of subscription ' . $sub_id . ' (customer ' . $customer_id . ( $first_payment ? ', charge ' . $first_payment['charge_id'] : '' ) . ') may not be saved correctly (the agreement record' . ( $first_payment ? ', its markers or the first payment record' : '' ) . '). The payment hooks were not triggered. Check this payment manually.', false );
		} else {
			// Trigger the stripe IPN processed action hook (so other plugins can can listen for this event).
			do_action( 'swpm_stripe_sca_ipn_processed', $hook_ipn_data );

			do_action( 'swpm_payment_ipn_processed', $hook_ipn_data );
		}

		SwpmLog::log_simple_debug( 'End of Stripe SCA Subscription IPN processing.', true, true );

		// Redirect the user to the registration URL or return URL (or to the homepage if a return URL is not specified for this payment button).
		SwpmMiscUtils::handle_after_payment_redirect( $button_id );
	}

	/**
	 * Finds the charge of the subscription's first payment (the first invoice of the checkout session).
	 * Returns the result of StripeUtilFunctions::get_invoice_payment_charge() plus 'invoice_id', or false when the charge is
	 * not certain (no amount due, payment not finished, API error...) or its amount is not the amount of this checkout.
	 */
	private static function get_first_payment_charge( $sess, $ref_id, $sub_id, $customer_id, $secret_key, $livemode, $price_in_cents, $payment_amount, $currency_code ) {
		try {
			if ( ! StripeUtilFunctions::has_methods( array( '\Stripe\Checkout\Session' => 'retrieve' ) ) ) {
				SwpmLog::log_simple_debug( 'Stripe first payment: the loaded Stripe library cannot retrieve a checkout session. The customer ID is used as the transaction ID.', true );
				return false;
			}
			// The session in the event has the API version of the event: retrieve it with StripeUtilFunctions::API_VERSION.
			$session = \Stripe\Checkout\Session::retrieve( $sess->id, StripeUtilFunctions::get_request_options( $secret_key ) );
		} catch ( \Throwable $e ) {
			SwpmLog::log_simple_debug( 'Stripe first payment: could not retrieve the checkout session. The customer ID is used as the transaction ID. ' . $e->getMessage(), false );
			return false;
		}
		$session_checks = array(
			'reference'    => isset( $session->client_reference_id ) && $session->client_reference_id === $ref_id,
			'subscription' => StripeUtilFunctions::get_object_id( isset( $session->subscription ) ? $session->subscription : '' ) === $sub_id,
			'customer'     => StripeUtilFunctions::get_object_id( isset( $session->customer ) ? $session->customer : '' ) === $customer_id,
			'mode'         => isset( $session->livemode ) && (bool) $session->livemode === (bool) $livemode,
			'amount'       => isset( $session->amount_total ) && is_int( $session->amount_total ) && $session->amount_total === (int) $price_in_cents,
			'currency'     => isset( $session->currency ) && strtoupper( $session->currency ) === $currency_code,
		);
		$failed = array_keys( array_filter( $session_checks, function ( $ok ) { return ! $ok; } ) );
		if ( ! empty( $failed ) ) {
			SwpmLog::log_simple_debug( 'Stripe first payment: the checkout session does not match this subscription (' . implode( ', ', $failed ) . '). The customer ID is used as the transaction ID.', false );
			return false;
		}

		$invoice_id = StripeUtilFunctions::get_object_id( isset( $session->invoice ) ? $session->invoice : '' );
		$result     = StripeUtilFunctions::get_invoice_payment_charge( $invoice_id, $secret_key, $customer_id, $livemode, $sub_id );
		if ( 'paid' !== $result['state'] ) {
			SwpmLog::log_simple_debug( 'Stripe first payment of subscription ' . $sub_id . ': ' . $result['state'] . ' (' . $result['reason'] . '). The customer ID is used as the transaction ID.', true );
			return false;
		}
		// The charge must be the amount of this checkout, in its currency, before and after the swpm_payment_amount_filter
		// (a refund is a part of what was really paid, so the saved amount must be that).
		if ( $result['currency'] !== $currency_code || $result['amount'] !== (int) $price_in_cents || StripeUtilFunctions::get_amount_in_stripe_units( $payment_amount, $currency_code ) !== $result['amount'] ) {
			SwpmLog::log_simple_debug( 'Stripe first payment of subscription ' . $sub_id . ': the charge amount (' . $result['amount'] . ') is not the checkout amount. The customer ID is used as the transaction ID.', true );
			return false;
		}
		$result['invoice_id'] = $invoice_id;
		return $result;
	}

	/**
	 * Saves the first payment of the subscription as its own record (charge ID as the transaction ID), next to the
	 * "subscription created" record of the agreement, which gets the markers stripe_payment_model = 2,
	 * stripe_first_charge_id and stripe_first_invoice_id (they mark an agreement whose first payment has its own record).
	 *
	 * Returns 'saved', 'not_saved' (nothing written: the hooks get the customer ID as the transaction ID) or 'uncertain'
	 * (a record may be incomplete: no hook).
	 */
	private static function save_first_payment_record( $agreement_id, $ipn_data, $first_payment ) {
		$markers = array(
			'stripe_payment_model'    => '2',
			'stripe_first_charge_id'  => $first_payment['charge_id'],
			'stripe_first_invoice_id' => $first_payment['invoice_id'],
		);
		foreach ( $markers as $key => $value ) {
			update_post_meta( $agreement_id, $key, $value );
		}
		foreach ( $markers as $key => $value ) {
			if ( get_post_meta( $agreement_id, $key, true ) !== $value ) {
				foreach ( array_keys( $markers ) as $marker ) {
					delete_post_meta( $agreement_id, $marker );
				}
				if ( '' === get_post_meta( $agreement_id, 'stripe_payment_model', true ) ) {
					SwpmLog::log_simple_debug( 'Stripe first payment: the markers could not be saved on the agreement record ' . $agreement_id . '. The customer ID is used as the transaction ID.', false );
					return 'not_saved';
				}
				return 'uncertain';
			}
		}

		$payment_ipn_data             = $ipn_data;
		$payment_ipn_data['txn_id']   = $first_payment['charge_id'];
		$payment_ipn_data['status']   = 'subscription';// Like the Stripe renewal payments (not a "subscription created" record).
		$payment_ipn_data['txn_type'] = 'stripe_subscription_first_payment';
		SwpmTransactions::save_txn_record( $payment_ipn_data );

		$payment_id = self::get_saved_record_id( $payment_ipn_data, 'subscription', 'stripe_subscription_first_payment' );
		if ( ! $payment_id ) {
			return 'uncertain';
		}
		SwpmLog::log_simple_debug( 'Stripe first payment saved with the charge ID: ' . $first_payment['charge_id'] . '. Agreement record: ' . $agreement_id . ', payment record: ' . $payment_id, true );
		return 'saved';
	}

	/**
	 * Checks that a record saved by SwpmTransactions::save_txn_record() is complete, in both places it is saved (the
	 * swpm_transactions post and its row of the payments table): exactly one post with this transaction ID, subscription
	 * and gateway, the expected status and txn_type, the amount, and its payments table row. Returns the post ID, or 0.
	 */
	private static function get_saved_record_id( $ipn_data, $status, $txn_type ) {
		global $wpdb;
		$ids = get_posts(
			array(
				'post_type'      => 'swpm_transactions',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => 'txn_id', 'value' => $ipn_data['txn_id'] ),
					array( 'key' => 'subscr_id', 'value' => $ipn_data['subscr_id'] ),
					array( 'key' => 'gateway', 'value' => $ipn_data['gateway'] ),
				),
			)
		);
		if ( count( $ids ) !== 1 ) {
			return 0;
		}
		$id     = (int) $ids[0];
		$amount = round( (float) $ipn_data['mc_gross'], 2 );
		if ( get_post_meta( $id, 'status', true ) !== $status || get_post_meta( $id, 'txn_type', true ) !== $txn_type || round( (float) get_post_meta( $id, 'payment_amount', true ), 2 ) !== $amount ) {
			return 0;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT txn_id, subscr_id, payment_amount FROM {$wpdb->prefix}swpm_payments_tbl WHERE id = %d", (int) get_post_meta( $id, 'db_row_id', true ) ) );
		if ( ! $row || $row->txn_id !== $ipn_data['txn_id'] || $row->subscr_id !== $ipn_data['subscr_id'] || round( (float) $row->payment_amount, 2 ) !== $amount ) {
			return 0;
		}
		return $id;
	}
}

new SwpmStripeSCASubscriptionIpnHandler();
