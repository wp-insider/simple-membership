<?php

/**
 * A Webhook class. Represents a webhook object with parameters in a given mode.
 */
class SWPM_PayPal_Webhook_Event_Handler {

	public function __construct() {
		//Register to handle the webhook event.
		//Handle it at 'wp_loaded' since custom post types will also be available then
		add_action( 'wp_loaded', array(&$this, 'handle_paypal_webhook' ) );
	}

    public function handle_paypal_webhook(){
        //Handle PayPal Webhook
		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'swpm_paypal_webhook_event' || ! isset( $_GET['mode'] ) ) {
			return;
		}

		$event = file_get_contents( 'php://input' );

		if ( ! $event || substr( $event, 0, 1 ) !== '{' ) {
			SwpmLog::log_simple_debug( 'WebHook Error: Empty or non-JSON webhook data!', false );
			wp_die();
		}

		$event = json_decode( $event, true );
		SwpmLog::log_simple_debug( 'Webhook event type: ' . $event['event_type'] . '. Event summary: ' . $event['summary'], true );

		if ($_GET['mode'] == 'production') {
			$mode = 'production';
		} else {
            $mode = 'sandbox';
        }

		//If using simulator, this will need to be commented out.
		//Verify the webhook for the given mode.
		if ( ! self::verify_webhook_event_for_given_mode( $event, $mode ) ) {
			status_header(200);//Send a 200 status code to PayPal to indicate that the webhook event was received successfully.
			wp_die();
		}

		//Handle the events
		//https://developer.paypal.com/api/rest/webhooks/event-names/#link-subscriptions

		//We will handle the following webhook event types.
		$event_type = $event['event_type'];
		//The subscription is added to the payments/transactions menu/database at checkout time (from the front-end). Later these events are used to update the status of the entries. 
		switch ( $event_type ) {
			case 'BILLING.SUBSCRIPTION.ACTIVATED':
				// A subscription is activated. This has all the details (including the customer details) in the webhook event.
				$this->handle_subscription_status_update('activated', $event, $mode );
				break;
			case 'BILLING.SUBSCRIPTION.EXPIRED':
				// A subscription expires.
				$this->handle_subscription_status_update('expired', $event, $mode );
				break;
			case 'BILLING.SUBSCRIPTION.CANCELLED':
				// A subscription is cancelled.
				$this->handle_subscription_status_update('cancelled', $event, $mode );
				break;
			case 'BILLING.SUBSCRIPTION.SUSPENDED':
				// A subscription is suspended.
				$this->handle_subscription_status_update('suspended', $event, $mode );
				break;
			case 'PAYMENT.SALE.COMPLETED':
				// A payment is made on a subscription. Update access starts date (if needed).
				$this->handle_subscription_payment_received('sale_completed', $event, $mode );
				break;
			case 'PAYMENT.SALE.REFUNDED':
				// A merchant refunded a sale.
				$this->handle_payment_refunded('sale_refunded', $event, $mode );
				break;
			case 'PAYMENT.CAPTURE.REFUNDED':
				// A merchant refunded a payment capture.
				$this->handle_payment_refunded('capture_refunded', $event, $mode );
				break;				
			default:
				// Nothing to do for us. Ignore this event.
				break;
		}


		/**
		 * Trigger an action hook after webhook verification (can be used to do further customization).
		 *
		 * @param array $event The event data received from PayPal API.
		 * @see https://developer.paypal.com/docs/api-basics/notifications/webhooks/notification-messages/
		 */
		do_action( 'swpm_paypal_subscription_webhook_event', $event );

		if ( ! headers_sent() ) {
			//Send a 200 status code to PayPal to indicate that the webhook event was received successfully.
			header("HTTP/1.1 200 OK");
		}
		echo '200 OK';//Force header output.
		exit;
    }

	/**
	 * Handle subscription status update.
	 */
	public function handle_subscription_status_update( $status, $event, $mode ) {
		//Get the subscription ID from the event data.
		$subscription_id = $event['resource']['id'];
		SwpmLog::log_simple_debug( 'Handling Webhook status update. Subscription ID: ' . $subscription_id, true );

		if( $status == 'activated'){
			//Hanlded at checkout time. Nothing to do here at this time.
			return;
		}

		if( $status == 'expired' || $status == 'cancelled' || $status == 'suspended'){

            // Update subscription status of the subscription agreement record in transactions cpt table.
            SWPM_Utils_Subscriptions::update_subscription_agreement_record_status_to_cancelled($subscription_id);

			//Set the account profile to expired or inactive.

			//Retrieve the member record for this subscription
			$member_record = SwpmMemberUtils::get_user_by_subsriber_id( $subscription_id );
			if( ! $member_record ){
				// No member record found
				SwpmLog::log_simple_debug( 'Could not find an existing member record for the given subscriber ID: ' . $subscription_id . '. This user profile may have been deleted. Nothing to do', true );
				return;
			}

			// Found a member record
			$member_id = $member_record->member_id;
			//Example value: array('last_webhook_status' => 'expired' );

			if( SwpmMemberUtils::get_subscription_data_extra_info($member_id, 'last_webhook_status') == $status ){
				//Nothing to do. This webhook status has already been processed.
				SwpmLog::log_simple_debug( 'This webhook status ('.$status.') has already been processed for this member (ID: '.$member_id.'). Nothing to do.', true );
				return;
			} else {
				//Save the last webhook status.
				SwpmMemberUtils::set_subscription_data_extra_info( $member_id, 'last_webhook_status', $status );

				//Handle the account status update according to the membership level's expiry settings.
				$ipn_data = array();
				$ipn_data['subscr_id'] = $subscription_id;
				$ipn_data['custom'] = 'swpm_id=' . $member_id;
				swpm_handle_subsc_cancel_stand_alone( $ipn_data );
				return;
			}
		}

	}

	/**
	 * Handle subscription payment received.
	 */
	public function handle_subscription_payment_received( $payment_status, $event, $mode ){
		//Get the subscription ID from the event data.
		$subscription_id = isset( $event['resource']['billing_agreement_id'] ) ? $event['resource']['billing_agreement_id'] : '';
		$txn_id = isset( $event['resource']['id'] ) ? $event['resource']['id'] : '';
		SwpmLog::log_simple_debug( 'Subscription ID from resource: ' . $subscription_id . '. Transaction ID: ' . $txn_id, true );
		if( empty( $subscription_id )){
			SwpmLog::log_simple_debug( 'Subscription ID is empty. Ignoring this webhook event.', true );
			return;
		}

		if( self::is_sale_completed_webhook_already_processed( $event ) ){
			//This webhook event has already been processed. Ignoring this webhook event.
			return;
		}

		//Get the subscription details from PayPal API endpoint - v1/billing/subscriptions/{$subscription_id}
		$api_injector = new SWPM_PayPal_Request_API_Injector();
		$api_injector->set_mode_and_api_creds_based_on_mode( $mode );
		$sub_details = $api_injector->get_paypal_subscription_details( $subscription_id );
		if( $sub_details !== false ){
			$billing_info = $sub_details->billing_info;
			if(is_object($billing_info)){
				//Convert the object to an array.
				$billing_info = json_decode(json_encode($billing_info), true);
			}
			//SwpmLog::log_array_data_to_debug( $billing_info, true );//Debugging only.

			$tenure_type = isset($billing_info['cycle_executions'][0]['tenure_type']) ? $billing_info['cycle_executions'][0]['tenure_type'] : ''; //'REGULAR' or 'TRIAL'
			$sequence = isset($billing_info['cycle_executions'][0]['sequence']) ? $billing_info['cycle_executions'][0]['sequence'] : '';//1, 2, 3, etc.
			$cycles_completed = isset($billing_info['cycle_executions'][0]['cycles_completed']) ? $billing_info['cycle_executions'][0]['cycles_completed'] : '';//1, 2, 3, etc.
			$last_payment_time = isset($billing_info['last_payment']['time']) ? $billing_info['last_payment']['time'] : '';
			SwpmLog::log_simple_debug( 'Subscription tenure type: ' . $tenure_type . ', Sequence: ' . $sequence . ', Cycles Completed: '. $cycles_completed. ', Last Payment Time: ' . $last_payment_time, true );

			//Create the IPN data array from the subscription details.
			$ipn_data = self::create_ipn_data_from_paypal_api_subscription_details_data( $sub_details, $event );
			//SwpmLog::log_array_data_to_debug( $ipn_data, true );//Debugging only.

			//Update the "Access starts date" of the member account to the current date.
			swpm_update_member_subscription_start_date_if_applicable( $ipn_data );

			//Save the payment transaction details to the DB.
			SwpmTransactions::save_txn_record( $ipn_data, array() );
			SwpmLog::log_simple_debug( 'Executed save_txn_record() function.', true );
	
			// Trigger the webhook processed action hook (so other plugins can can listen for this event).
			do_action( 'swpm_paypal_subscription_sale_completed_webhook_processed', $ipn_data );		

		} else {
			//Error getting subscription details.
			SwpmLog::log_simple_debug( 'Error! Failed to get subscription details from the PayPal API.', false );
			//TODO - Show additional error details if available.
			return;
		}
	}

	public function handle_payment_refunded( $payment_status, $event, $mode ){
		//For subscription payment, the account is cancelled when the subscription is cancelled or expired.
		//For one time payment, the refund event will trigger the account cancellation.

		//TODO - Handle any other refund related webhook notifications.
		SwpmLog::log_simple_debug( 'Processing the payment refund/reversal webhook event.', true );
		SwpmLog::log_array_data_to_debug( $event, true );//Debugging only.

		if( $payment_status != 'capture_refunded'){
			//At the moment we are only processing the capture refunded event.
			SwpmLog::log_simple_debug( 'This is not a capture refund event. Ignore this event.', true );
			return;
		}

		$refund_txn_id = isset( $event['resource']['id'] ) ? $event['resource']['id'] : '';
		$rerfund_amount = isset( $event['resource']['amount']['value'] ) ? $event['resource']['amount']['value'] : 0;
		$refund_currency = isset( $event['resource']['amount']['currency_code'] ) ? $event['resource']['amount']['currency_code'] : '';

		$links = isset( $event['resource']['links'] ) ? $event['resource']['links'] : array();
		$link_refund_txn = isset( $links[0]['href'] ) ? $links[0]['href'] : '';
		//This will contain the origial capture transaction ID and the URL to query and get the details.
		$link_capture_txn = isset( $links[1]['href'] ) ? $links[1]['href'] : '';

		//Get the original capture txn ID from the refund link url.
		$uri_path_components = explode("/", parse_url($link_capture_txn, PHP_URL_PATH));
		$orig_capture_txn_id = array_pop($uri_path_components);
		SwpmLog::log_simple_debug( 'Transaction ID from resource. Transaction ID: ' . $orig_capture_txn_id . '. Original Capture Link: ' . $link_capture_txn, true );
		
		if( empty( $orig_capture_txn_id )){
			SwpmLog::log_simple_debug( 'Transaction ID value is empty. Ignoring this webhook event.', true );
			return;
		}
		
		$ipn_data = array();
		$ipn_data['parent_txn_id'] = $orig_capture_txn_id;//Important for one time transactions refund.
		$ipn_data['subscr_id'] = '';
		swpm_handle_refund_using_parent_txn_id( $ipn_data );
	}

	public static function create_ipn_data_from_paypal_api_subscription_details_data( $sub_details, $event ){
		//Creates the $ipn_data array using the data from the PayPal API endpoint - v1/billing/subscriptions/{$subscription_id}
		$ipn_data = array();
		if(!is_object($sub_details)){
			SwpmLog::log_simple_debug( 'Error! Invalid subscription details data. Cannot create ipn data.', false );
			return false;
		}

		//Get the subscriber info array
		$subscriber_info = $sub_details->subscriber;
		if(is_object($subscriber_info)){
			//Convert the object to an array.
			$subscriber_info = json_decode(json_encode($subscriber_info), true);
		}

		//Get the billing info array
		$billing_info = $sub_details->billing_info;
		if(is_object($billing_info)){
			//Convert the object to an array.
			$billing_info = json_decode(json_encode($billing_info), true);
		}

		//Get the Subscription ID and Txn ID from the event data.
		$subscription_id = isset( $event['resource']['billing_agreement_id'] ) ? $event['resource']['billing_agreement_id'] : '';
		$txn_id = isset( $event['resource']['id'] ) ? $event['resource']['id'] : '';

		//Get the custom field data of the original subscription checkout from the user profile (if available).
		$custom = SwpmTransactions::get_original_custom_value_from_transactions_cpt( $subscription_id );

		//Set the data to the $ipn_data array.
		$ipn_data['custom'] = isset($custom) ? $custom : '';
		$ipn_data['payer_email'] = $subscriber_info['email_address'];
		$ipn_data['first_name'] = $subscriber_info['name']['given_name'];
		$ipn_data['last_name'] = $subscriber_info['name']['surname'];

		$ipn_data['txn_id'] = $txn_id;
		$ipn_data['subscr_id'] = $subscription_id;
		$ipn_data['mc_gross'] = isset($billing_info['last_payment']['amount']['value']) ? $billing_info['last_payment']['amount']['value'] : '';
		$ipn_data['gateway'] = 'paypal_subscription_checkout';
		$ipn_data['txn_type'] = 'pp_subscription_sale_completed_webhook';
		$ipn_data['status'] = 'Completed';

		return $ipn_data;
	}

	public static function is_sale_completed_webhook_already_processed( $event ){
		// Query the DB to check if we have already processed this transaction or not.
		$txn_id = isset( $event['resource']['id'] ) ? $event['resource']['id'] : '';
		$subscription_id = isset( $event['resource']['billing_agreement_id'] ) ? $event['resource']['billing_agreement_id'] : '';
		$txn_row = SwpmTransactions::get_transaction_row_by_txn_id_and_subscription_id($txn_id, $subscription_id);
		if (!empty($txn_row)) {
			// And if we have already processed it, do nothing and return true
			SwpmLog::log_simple_debug( "This webhook event has already been processed (Txn ID: ".$txn_id.", Subscr ID: ".$subscription_id."). This looks to be a duplicate webhook notification. Nothing to do.", true );
			return true;
		}
		return false;
	}

	/**
	 * Gets the HTTP headers that you received from PayPal webhook.
	 */
	public static function get_paypal_webhook_headers() {
		//This method of getting the headers is more robust than using getallheaders() as this method will work on nginx servers as well.
		$headers = array();
		foreach ( $_SERVER as $key => $value ) {
			if ( substr( $key, 0, 5 ) !== 'HTTP_' ) {
				continue;
			}
			$header = str_replace( ' ', '-', str_replace( '_', ' ', strtoupper( substr( $key, 5 ) ) ) );

			$headers[ $header ] = $value;
		}
		return $headers;
	}

	public static function verify_webhook_event_for_given_mode( $event, $mode ){
		//Get HTTP headers received from the PayPal webhook.
		$headers = self::get_paypal_webhook_headers();

		//Verify the webhook event signaure.
		$pp_webhook = new SWPM_PayPal_Webhook();
		//Set the mode based on the received webhook (so we are processing according to that instead of the current mode setting in the plugin settings).
		$pp_webhook->set_mode_and_api_creds_based_on_mode( $mode );

		$response = $pp_webhook->verify_webhook_signature( $event, $headers );

		if( $response !== false && $response->verification_status === 'SUCCESS' ){
			//Webhook verification success!
			return true;
		}

		if ( isset( $response->verification_status ) && $response->verification_status !== 'SUCCESS' ) {
			SwpmLog::log_simple_debug( 'Error! Webhook verification failed! Environment mode: '. $mode . ', Verification status: ' . $response->verification_status, false );
			return false;
		}

		//If we are here then something went wrong. Log the error and return false.
		//We can check the SWPM_PayPal_Request_API->last_error to find additional details if needed.
		SwpmLog::log_simple_debug( 'Error! Webhook verification failed! Environment mode: '. $mode, false );
		return false;
	}
}
