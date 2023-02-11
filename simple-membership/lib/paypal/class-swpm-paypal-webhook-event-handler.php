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

		status_header(200);//Send a 200 status code to PayPal to indicate that the webhook event was received successfully.

		if ($_GET['mode'] == 'production') {
			$mode = 'production';
		} else {
            $mode = 'sandbox';
        }

		//TODO - re-enable it when finished with simulator.
		//Verify the webhook for the given mode.
		if ( ! self::verify_webhook_event_for_given_mode( $event, $mode ) ) {
			status_header(200);//Send a 200 status code to PayPal to indicate that the webhook event was received successfully.
			exit;
		}

		//Handle the events
		//https://developer.paypal.com/api/rest/webhooks/event-names/#link-subscriptions

		//TODO - remove later.
// ob_start();
// echo '<pre>';
// var_dump($event);
// echo '</pre>';
// $contents = ob_get_contents();
// ob_end_clean();
// SwpmLog::log_simple_debug( 'Webhook event: ' . $contents, true );
		//SwpmLog::log_simple_debug( 'Webhook event received: ' . $event, true );

		//Save the event type as a status (a short string) and the subscription ID in the payments table. This can be used to see if a notification is a duplicate or not.
		//We will handle the following event types.
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

		wp_die();
    }

	/**
	 * Handle subscription status update.
	 */
	public function handle_subscription_status_update( $status, $event, $mode ) {
		//Get the subscription ID from the event data.
		$subscription_id = $event['resource']['id'];
		SwpmLog::log_simple_debug( 'Subscription ID from resource: ' . $subscription_id, true );


		//TODO - May NEED TO make an API call to get the subscription details.

		$subscription_id = $event['resource']['id'];
		$create_time = $event['resource']['create_time'];
		$subscription_status = $event['resource']['status'];
		$subscription_plan_id = $event['resource']['plan_id'];
		$customer_id = $event['resource']['subscriber']['payer_id'];
		$first_name = $event['resource']['subscriber']['name']['given_name'];
		$last_name = $event['resource']['subscriber']['name']['surname'];
		$email_address = $event['resource']['subscriber']['email_address'];

		$billing_info = $event['resource']['billing_info'];
		$address_line_1 = $event['resource']['subscriber']['shipping_address']['address']['address_line_1'];
		$address_line_2 = $event['resource']['subscriber']['shipping_address']['address']['address_line_2'];
		$admin_area_1 = $event['resource']['subscriber']['shipping_address']['address']['admin_area_1'];
		$admin_area_2 = $event['resource']['subscriber']['shipping_address']['address']['admin_area_2'];
		$postal_code = $event['resource']['subscriber']['shipping_address']['address']['postal_code'];
		$country_code = $event['resource']['subscriber']['shipping_address']['address']['country_code'];

		SwpmLog::log_simple_debug( 'Info 1: ' . $subscription_id . '|' . $subscription_status .'|'. $email_address . '|' . $first_name .'|'.$last_name, true );
		SwpmLog::log_simple_debug( 'Info 2: ' . $create_time . '|' . $subscription_plan_id .'|'. $customer_id, true );
		SwpmLog::log_simple_debug( $address_line_1 . $address_line_2 . '|' . $admin_area_1 . $admin_area_2 .'|'. $postal_code . $country_code, true);
		//SwpmLog::log_array_data_to_debug( $billing_info, true );
		

		//[2023/02/09 22:20:07] - SUCCESS: Info 3: I-BW452GLLEP1G|ACTIVE|customer@example.com|John|Doe
		//[2023/02/09 22:20:07] - SUCCESS: Info 4: 2018-12-10T21:20:49Z|P-5ML4271244454362WXNWU5NQ|

		//update_time or status_update_time may be used to determine the last time the subscription status was updated. And if a notification is duplicate.

		// if ( ! $subscription || $this->status === $subscription->get_status() ) {
		// 	return;
		// }

		//For cancelled
		//"/v1/billing/subscriptions/{$id}/cancel", 'POST', $mode

	}

	/**
	 * Handle subscription payment received.
	 */
	public function handle_subscription_payment_received( $payment_status, $event, $mode ){
		//Get the subscription ID from the event data.
		$subscription_id = isset( $event['resource']['billing_agreement_id'] ) ? $event['resource']['billing_agreement_id'] : '';
		$txn_id = isset( $event['resource']['id'] ) ? $event['resource']['id'] : '';
		//$subscription_id = 'I-02JKMA5PA1H4';//Testing
		SwpmLog::log_simple_debug( 'Subscription ID from resource: ' . $subscription_id . '. Transaction ID: ' . $txn_id, true );
		if( empty( $subscription_id )){
			SwpmLog::log_simple_debug( 'Subscription ID is empty. Ignoring this webhook event.', true );
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
			$tenure_type = $billing_info['cycle_executions'][0]['tenure_type'];//'REGULAR' or 'TRIAL'
			SwpmLog::log_simple_debug( 'Subscription tenure type: ' . $tenure_type, true );

			//TODO - update the "Access starts date" of the member account to the current date.

			// ob_start();
			// echo '<pre>';
			// var_dump($sub_details);
			// echo '</pre>';
			// $contents = ob_get_contents();
			// ob_end_clean();
			// SwpmLog::log_simple_debug( 'Sub details Info: ' . $contents, true );			

		} else {
			//Error getting subscription details.
			SwpmLog::log_simple_debug( 'Error! Failed to get subscription details from the PayPal API.', false );
			//TODO - Show additional error details if available.
			return;
		}

		// if ( ! $subscription || $this->status === $subscription->get_status() ) {
		// 	return;
		// }

		//TODO - update the "Access starts date" of the member account to the current date.


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
		//SwpmLog::log_array_data_to_debug( $headers, true );
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
			//SwpmLog::log_simple_debug( 'Webhook verification success! Verification status: ' . $response->verification_status, true );//TODO - remove later.
			return true;
		}

		if ( isset( $response->verification_status ) && $response->verification_status !== 'SUCCESS' ) {
			SwpmLog::log_simple_debug( 'Error! Webhook verification failed! Verification status: ' . $response->verification_status, false );
			return false;
		}

		//If we are here then something went wrong. Log the error and return false.
		//We can check the SWPM_PayPal_Request_API->last_error to find additional details if needed.
		SwpmLog::log_simple_debug( 'Error! Webhook verification failed!', false );
		return false;
	}
}
