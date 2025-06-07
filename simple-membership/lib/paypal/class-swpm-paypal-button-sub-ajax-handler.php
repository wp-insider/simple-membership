<?php

/**
 * This clcass handles the ajax request from the PayPal OnApprove event (the onApprove event is triggered from the Button's JS code on successful transaction). 
 * It creates the required $ipn_data array from the transaction so it can be fed into the existing IPN handler functions easily.
 */
class SWPM_PayPal_Button_Sub_Ajax_Hander {

	public $ipn_data  = array();

	public function __construct() {
		//Handle it at 'wp_loaded' since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the create subscription via API ajax request
		add_action( 'wp_ajax_swpm_pp_create_subscription', array(&$this, 'swpm_pp_create_subscription' ) );
		add_action( 'wp_ajax_nopriv_swpm_pp_create_subscription', array(&$this, 'swpm_pp_create_subscription' ) );

		//Handle the onApprove ajax request for 'Subscription' type buttons
		add_action( 'wp_ajax_swpm_onapprove_process_subscription', array(&$this, 'swpm_onapprove_process_subscription' ) );
		add_action( 'wp_ajax_nopriv_swpm_onapprove_process_subscription', array(&$this, 'swpm_onapprove_process_subscription' ) );		
	}


	/**
	 * Handle the create-subscription ajax request for 'Subscription' type buttons.
	 */
    public function swpm_pp_create_subscription(){
		//We will create a plan for the button (if needed). Then create a subscription for the user and return the subscription ID.
		//https://developer.paypal.com/docs/api/subscriptions/v1/#plans_create

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'simple-membership' ),
				)
			);
		}
		
		if( !is_array( $data ) ){
			//Convert the JSON string to an array (Vanilla JS AJAX data will be in JSON format).
			$data = json_decode( $data, true);		
		}

		$button_id = isset( $data['button_id'] ) ? sanitize_text_field( $data['button_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		SwpmLog::log_simple_debug( 'swpm_pp_create_subscription ajax request received for createSubscription. Button ID: '.$button_id.', On Page Button ID: ' . $on_page_button_id, true );

		// Check nonce.
		if ( ! check_ajax_referer( $on_page_button_id, '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'simple-membership' ),
				)
			);
			exit;
		}

		/**************************
		 * Get the PayPal Plan ID *
		 **************************/
		//Get the plan ID (or create a new plan if needed) for the button.
		$plan_id = get_post_meta( $button_id, 'pp_subscription_plan_id', true );
		$plan_create_error_msg = '';
		if( empty( $plan_id )){
			//Need to create a new plan
			$ret = SWPM_PayPal_Utility_Functions::create_billing_plan_for_button( $button_id );
			if( $ret['success'] === true ){
				$plan_id = $ret['plan_id'];
				SwpmLog::log_simple_debug( 'Created new PayPal subscription plan for button ID: ' . $button_id . ', Plan ID: ' . $plan_id, true );
			} else {
				$plan_create_error_msg = 'Error! Could not create the PayPal subscription plan for the button. Error message: ' . esc_attr( $ret['error_message'] );
			}
		} else {
			//Check if this plan exists in the PayPal account.
			if( !SWPM_PayPal_Utility_Functions::check_billing_plan_exists( $plan_id ) ){
				//The plan ID does not exist in the PayPal account. Maybe the plan was created earlier in a different mode or using a different paypal account. 
				//We need to create a fresh new plan for this button.
				$ret = SWPM_PayPal_Utility_Functions::create_billing_plan_fresh_new( $button_id );
				if( $ret['success'] === true ){
					$plan_id = $ret['plan_id'];
					SwpmLog::log_simple_debug( 'Created new PayPal subscription plan for button ID: ' . $button_id . ', Plan ID: ' . $plan_id, true );
				} else {
					$plan_create_error_msg = 'Error! Could not create the PayPal subscription plan for the button. Error message: ' . esc_attr( $ret['error_message'] );
				}            
			}
		}

		//Check if any error occurred while creating the plan.
		if( !empty( $plan_create_error_msg ) ){
			SwpmLog::log_simple_debug( $plan_create_error_msg, false );
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => $plan_create_error_msg,
				)
			);
			exit;
		}

		/*************************************
		 * Create the subscription on PayPal *
		 ************************************/
		//Going to create the subscription by making the PayPal API call.
		$api_injector = new SWPM_PayPal_Request_API_Injector();

		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		$response = $api_injector->create_paypal_subscription_for_billing_plan( $plan_id, $data, $additional_args );

		//We requested the full response body to be returned, so we need to JSON decode it.
		if( $response !== false ){
			//JSON decode the response body to an array.
			$sub_data = json_decode( $response, true );
			$paypal_sub_id = isset( $sub_data['id'] ) ? $sub_data['id'] : '';
		} else {
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to create the subscription using PayPal API. Enable the debug logging feature to get more details.', 'simple-membership' ),
				)
			);
			exit;
		}

		//Uncomment the following line to see more details of the subscription data.
		//SwpmLog::log_array_data_to_debug( $sub_data, true );

		SwpmLog::log_simple_debug( 'PayPal Subscription ID: ' . $paypal_sub_id, true );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'subscription_id' => $paypal_sub_id, 'sub_data' => $sub_data ) );
		exit;
    }


	/**
	 * Handle the onApprove ajax request for 'Subscription' type buttons
	 */
    public function swpm_onapprove_process_subscription(){

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? json_decode( stripslashes_deep( $_POST['data'] ), true ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'simple-membership' ),
				)
			);
		}
		//SwpmLog::log_array_data_to_debug( $data, true );//Debugging only

		$button_id = isset( $data['button_id'] ) ? sanitize_text_field( $data['button_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		SwpmLog::log_simple_debug( 'OnApprove ajax request received for createSubscription. On Page Button ID: ' . $on_page_button_id, true );

		// Check nonce.
		if ( ! check_ajax_referer( $on_page_button_id, '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'simple-membership' ),
				)
			);
			exit;
		}

		//Get the transaction data from the request
		$txn_data = isset( $_POST['txn_data'] ) ? json_decode( stripslashes_deep( $_POST['txn_data'] ), true ) : array();

		if ( empty( $txn_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty transaction data received.', 'simple-membership' ),
				)
			);
		}
		//SwpmLog::log_array_data_to_debug( $txn_data, true );//Debugging only.

		//Create the IPN data array from the transaction data.
		$this->create_ipn_data_array_from_create_subscription_txn_data( $data, $txn_data );
		//SwpmLog::log_array_data_to_debug( $this->ipn_data, true );//Debugging only.
		
		//Validate the subscription txn data before using it.
		$validation_response = $this->validate_subscription_checkout_txn_data( $data, $txn_data );
		if( $validation_response !== true ){
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => $validation_response,
				)
			);
			exit;
		}

		//Process the IPN data array
		SwpmLog::log_simple_debug( 'Validation passed. Going to create/update member account and save transaction data.', true );
		SWPM_PayPal_Utility_IPN_Related::create_membership_and_save_txn_data( $data, $txn_data, $this->ipn_data );

		// Trigger the IPN processed action hook (so other plugins can can listen for this event).
		do_action( 'swpm_paypal_subscription_checkout_ipn_processed', $this->ipn_data );
		do_action( 'swpm_payment_ipn_processed', $this->ipn_data );

		//If everything is processed successfully, send the success response.
		wp_send_json( array(
			'success' => true,
			'redirect_url' => SwpmMiscUtils::get_after_payment_redirect_url($button_id),
		) );
		exit;
    }

	public function create_ipn_data_array_from_create_subscription_txn_data( $data, $txn_data ) {
		$ipn = array();

		//Get the custom field value from the request
		$custom = isset($data['custom_field']) ? $data['custom_field'] : '';
		$custom = urldecode( $custom );//Decode it just in case it was encoded.

		if(isset($data['orderID'])){
			//Add the PayPal API orderID value to the reference parameter. So it gets saved with custom field data. This will be used to also save it to the reference DB column field when saving the transaction.
			$data['custom_field'] = $custom . '&reference=' . $data['orderID'];
		}

		$customvariables = SwpmTransactions::parse_custom_var( $custom );

		$billing_info = isset($txn_data['billing_info']) ? $txn_data['billing_info'] : array();

		$address_street = isset($txn_data['subscriber']['shipping_address']['address']['address_line_1']) ? $txn_data['subscriber']['shipping_address']['address']['address_line_1'] : '';
		if ( isset ( $txn_data['subscriber']['shipping_address']['address']['address_line_2'] )){
			//If address line 2 is present, add it to the address.
			$address_street .= ", " . $txn_data['subscriber']['shipping_address']['address']['address_line_2'];
		}

		//Set the gateway and txn_type values.
		$ipn['gateway'] = 'paypal_subscription_checkout';
		$ipn['txn_type'] = 'pp_subscription_new';//Can be used to find sub-created type transactions.

		//The custom field value.
		$ipn['custom'] = isset($data['custom_field']) ? $data['custom_field'] : '';

		//This will save the button ID (in the save_txn_record function) in the swpm_transactions CPT (for a reference to the button used for the payment)
		$ipn['payment_button_id'] = isset($data['button_id']) ? $data['button_id'] : '';
		
		//If the subscription is for live mode or sandbox mode. We will use this to set the 'is_live' flag in the swpm_transactions CPT.
		$settings = SwpmSettings::get_instance();
		$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );
		$ipn['is_live'] = empty($sandbox_enabled) ? 'yes' : 'no';//We need to save the environment (live or sandbox) of the subscription.

		$ipn['item_number'] = isset($data['button_id']) ? $data['button_id'] : '';
		$ipn['item_name'] = isset($data['item_name']) ? $data['item_name'] : '';		

		$ipn['plan_id'] = isset($txn_data['plan_id']) ? $txn_data['plan_id'] : '';//The plan ID of the subscription
		$ipn['subscr_id'] = isset($data['subscriptionID']) ? $data['subscriptionID'] : '';//The subscription ID
		$ipn['create_time'] = isset($txn_data['create_time']) ? $txn_data['create_time'] : '';

		//The transaction ID is not available in the create/activate subscription response. So we will just use the order ID here.
		//The subscription capture happens in the background. So if we want to use the get transactions list API to get the transaction ID of the first transaction, we will need to do that later using cronjob maybe.
		$ipn['txn_id'] = isset($data['orderID']) ? $data['orderID'] : '';

		$ipn['status'] = __('subscription created', 'simple-membership');
		$ipn['payment_status'] = __('subscription created', 'simple-membership');
		$ipn['subscription_status'] = isset($txn_data['status']) ? $txn_data['status'] : '';//Can be used to check if the subscription is active or not (in the webhook handler)

		//Amount and currency.
		$ipn['mc_gross'] = isset($txn_data['billing_info']['last_payment']['amount']['value']) ? $txn_data['billing_info']['last_payment']['amount']['value'] : 0;
		$ipn['mc_currency'] = isset($txn_data['billing_info']['last_payment']['amount']['currency_code']) ? $txn_data['billing_info']['last_payment']['amount']['currency_code'] : '';
		if( $this->is_trial_payment( $billing_info )){
			//TODO: May need to get the trial amount from the 'cycle_executions' array
			$ipn['is_trial_txn'] = 'yes';
		}
		$ipn['quantity'] = 1;

		//Customer info.
		$ipn['ip'] = isset($customvariables['user_ip']) ? $customvariables['user_ip'] : '';
		$ipn['first_name'] = isset($txn_data['subscriber']['name']['given_name']) ? $txn_data['subscriber']['name']['given_name'] : '';
		$ipn['last_name'] = isset($txn_data['subscriber']['name']['surname']) ? $txn_data['subscriber']['name']['surname'] : '';
		$ipn['payer_email'] = isset($txn_data['subscriber']['email_address']) ? $txn_data['subscriber']['email_address'] : '';
		$ipn['payer_id'] = isset($txn_data['subscriber']['payer_id']) ? $txn_data['subscriber']['payer_id'] : '';
		$ipn['address_street'] = $address_street;
		$ipn['address_city']    = isset($txn_data['subscriber']['shipping_address']['address']['admin_area_2']) ? $txn_data['subscriber']['shipping_address']['address']['admin_area_2'] : '';
		$ipn['address_state']   = isset($txn_data['subscriber']['shipping_address']['address']['admin_area_1']) ? $txn_data['subscriber']['shipping_address']['address']['admin_area_1'] : '';
		$ipn['address_zip']     = isset($txn_data['subscriber']['shipping_address']['address']['postal_code']) ? $txn_data['subscriber']['shipping_address']['address']['postal_code'] : '';
		$country_code = isset($txn_data['subscriber']['shipping_address']['address']['country_code']) ? $txn_data['subscriber']['shipping_address']['address']['country_code'] : '';
		$ipn['address_country'] = SwpmMiscUtils::get_country_name_by_country_code($country_code);


		/**********************************/
		//Ensure the customer's email and name are set. For guest checkout, the email and name may not be set in the standard onApprove data.
		//So we will query the subscrition details from the PayPal API to get the subscriber's email and name (if needed).
		/**********************************/
		if( empty($ipn['payer_email']) || empty($ipn['first_name']) || empty($ipn['last_name']) ){
			//Use the subscription ID to get the subscriber's email and name from the PayPal API.
			$subscription_id = isset($ipn['subscr_id']) ? $ipn['subscr_id'] : '';
			SwpmLog::log_simple_debug( 'Subscriber Email or Name not set in the onApprove data. Going to query the PayPal API for subscription details. Subscription ID: ' . $subscription_id, true );

			//This is for on-site checkout only. So the 'mode' and API creds will be whatever is currently set in the settings.
			$api_injector = new SWPM_PayPal_Request_API_Injector();
			$sub_details = $api_injector->get_paypal_subscription_details( $subscription_id );
			if( $sub_details !== false ){
				$subscriber = isset($sub_details->subscriber) ? $sub_details->subscriber : array();
				if(is_object($subscriber)){
					//Convert the object to an array.
					$subscriber_data_array = json_decode(json_encode($subscriber), true);
				}
				//Debugging only.
				SwpmLog::log_array_data_to_debug( $subscriber_data_array, true );
				
				if( empty($ipn['payer_email']) && isset($subscriber_data_array['email_address']) ){
					//Set the payer email from the subscriber data.
					$ipn['payer_email'] = $subscriber_data_array['email_address'];
				}
				if( empty($ipn['first_name']) && isset($subscriber_data_array['name']['given_name']) ){
					//Set the payer first name from the subscriber data.
					$ipn['first_name'] = $subscriber_data_array['name']['given_name'];
				}
				if( empty($ipn['last_name']) && isset($subscriber_data_array['name']['surname']) ){
					//Set the payer last name from the subscriber data.
					$ipn['last_name'] = $subscriber_data_array['name']['surname'];
				}
				SwpmLog::log_simple_debug( 'Subscriber Email: ' . $ipn['payer_email'] . ', First Name: ' . $ipn['first_name'] . ', Last Name: ' . $ipn['last_name'], true );
			} else {
				//Error getting subscription details.
				$validation_error_msg = 'Validation Error! Failed to get subscription details from the PayPal API. Subscription ID: ' . $subscription_id;
				SwpmLog::log_simple_debug( $validation_error_msg, false );
			}
		}		

		//Return the IPN data array. This will be used to create/update the member account and save the transaction data.
		$this->ipn_data = $ipn;
	}

	public function is_trial_payment( $billing_info ) {
		if( isset( $billing_info['cycle_executions'][0]['tenure_type'] ) && ($billing_info['cycle_executions'][0]['tenure_type'] === 'TRIAL')){
			return true;
		}
		return false;
	}

	/**
	 * Validate that the subscription exists in PayPal and the price matches the price in the DB.
	 */
	public function validate_subscription_checkout_txn_data( $data, $txn_data ) {
		//Get the subscription details from PayPal API endpoint - v1/billing/subscriptions/{$subscription_id}
		$subscription_id = $data['subscriptionID'];
		$button_id = $data['button_id'];

		$validation_error_msg = '';

		//This is for on-site checkout only. So the 'mode' and API creds will be whatever is currently set in the settings.
		$api_injector = new SWPM_PayPal_Request_API_Injector();
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
			SwpmLog::log_simple_debug( 'Subscription tenure type: ' . $tenure_type . ', Sequence: ' . $sequence . ', Cycles Completed: '. $cycles_completed, true );			

			//Tenure type - 'REGULAR' or 'TRIAL'
			$tenure_type = isset($billing_info['cycle_executions'][0]['tenure_type']) ? $billing_info['cycle_executions'][0]['tenure_type'] : 'REGULAR';
			//If tenure type is 'TRIAL', check that this button has a trial period.
			if( $tenure_type === 'TRIAL' ){
				SwpmLog::log_simple_debug('Trial payment detected.', true);//TODO - remove later.

				//Check that the button has a trial period.
				$trial_billing_cycle = get_post_meta( $button_id, 'trial_billing_cycle', true );
				if( empty($trial_billing_cycle) ){
					//This button does not have a trial period. So this is not a valid trial payment.
					$validation_error_msg = 'Validation Error! This is a trial payment but the button does not have a trial period configured. Button ID: ' . $button_id . ', Subscription ID: ' . $subscription_id;
					SwpmLog::log_simple_debug( $validation_error_msg, false );
					return $validation_error_msg;
				}
			} else {
				//This is a regular subscription checkout (without trial). Check that the price matches.
				$amount = isset($billing_info['last_payment']['amount']['value']) ? $billing_info['last_payment']['amount']['value'] : 0;
				$recurring_billing_amount = get_post_meta( $button_id, 'recurring_billing_amount', true );
				if( $amount < $recurring_billing_amount ){
					//The amount does not match.
					$validation_error_msg = 'Validation Error! The subscription amount does not match. Button ID: ' . $button_id . ', Subscription ID: ' . $subscription_id . ', Amount Received: ' . $amount . ', Amount Expected: ' . $recurring_billing_amount;
					SwpmLog::log_simple_debug( $validation_error_msg, false );
					return $validation_error_msg;
				}
				//Check that the Currency code matches
				$currency = isset($billing_info['last_payment']['amount']['currency_code']) ? $billing_info['last_payment']['amount']['currency_code'] : '';
				$currency_expected = get_post_meta( $button_id, 'payment_currency', true );
				if( $currency !== $currency_expected ){
					//The currency does not match.
					$validation_error_msg = 'Validation Error! The subscription currency does not match. Button ID: ' . $button_id . ', Subscription ID: ' . $subscription_id . ', Currency Received: ' . $currency . ', Currency Expected: ' . $currency_expected;
					SwpmLog::log_simple_debug( $validation_error_msg, false );
					return $validation_error_msg;
				}
			}

		} else {
			//Error getting subscription details.
			$validation_error_msg = 'Validation Error! Failed to get subscription details from the PayPal API. Subscription ID: ' . $subscription_id;
			//TODO - Show additional error details if available.
			SwpmLog::log_simple_debug( $validation_error_msg, false );
			return $validation_error_msg;
		}

		//All good. The data is valid.
		return true;
	}

}
