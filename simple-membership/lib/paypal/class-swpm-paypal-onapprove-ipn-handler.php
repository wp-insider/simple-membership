<?php

/**
 * This clcass handles the ajax request from the PayPal OnApprove event (the onApprove event is triggered from the Button's JS code on successful transaction). 
 * It creates the required $ipn_data array from the transaction so it can be fed into the existing IPN handler functions easily.
 */
class SWPM_PayPal_OnApprove_IPN_Handler {

	public $ipn_data  = array();

	public function __construct() {
		//Handle it at 'wp_loaded' since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the onApprove ajax request for 'Buy Now' type buttons
		// add_action( 'wp_ajax_swpm_onapprove_create_order', array(&$this, 'swpm_onapprove_create_order' ) );
		// add_action( 'wp_ajax_nopriv_swpm_onapprove_create_order', array(&$this, 'swpm_onapprove_create_order' ) );

		//Handle the onApprove ajax request for 'Subscription' type buttons
		add_action( 'wp_ajax_swpm_onapprove_create_subscription', array(&$this, 'swpm_onapprove_create_subscription' ) );
		add_action( 'wp_ajax_nopriv_swpm_onapprove_create_subscription', array(&$this, 'swpm_onapprove_create_subscription' ) );
	}

    public function swpm_onapprove_create_subscription(){

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
		//SwpmLog::log_array_data_to_debug( $data, true );//For debugging only

		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		SwpmLog::log_simple_debug( 'OnApprove ajax request received. On Page Button ID: ' . $on_page_button_id, true );

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
		$txn_data = isset( $_POST['txn_data'] ) ? stripslashes_deep( $_POST['txn_data'] ) : array();
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
		$this->create_ipn_data_array_from_txn_data( $data, $txn_data );
		SwpmLog::log_array_data_to_debug( $this->ipn_data, true );//TODO: Remove this line after testing
		
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
		$this->create_membership_and_save_txn_data( $data, $txn_data );


		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true ) );
		exit;
    }

	public function create_ipn_data_array_from_txn_data( $data, $txn_data ) {
		$ipn = array();

		//Get the custom field value from the request
		$custom = isset($data['custom_field']) ? $data['custom_field'] : '';
		$custom = urldecode( $custom );//Decode it just in case it was encoded.
		$customvariables = SwpmTransactions::parse_custom_var( $custom );

		$billing_info = isset($txn_data['billing_info']) ? $txn_data['billing_info'] : array();

		$address_street = isset($txn_data['subscriber']['shipping_address']['address']['address_line_1']) ? $txn_data['subscriber']['shipping_address']['address']['address_line_1'] : '';
		if ( isset ( $txn_data['subscriber']['shipping_address']['address']['address_line_2'] )){
			//If address line 2 is present, add it to the address.
			$address_street .= ", " . $txn_data['subscriber']['shipping_address']['address']['address_line_2'];
		}

		$ipn['custom'] = isset($data['custom_field']) ? $data['custom_field'] : '';
		$ipn['item_number'] = isset($data['button_id']) ? $data['button_id'] : '';
		$ipn['item_name'] = isset($data['item_name']) ? $data['item_name'] : '';
		$ipn['txn_id'] = isset($data['orderID']) ? $data['orderID'] : '';
		$ipn['subscr_id'] = isset($data['subscriptionID']) ? $data['subscriptionID'] : '';
		$ipn['plan_id'] = isset($txn_data['plan_id']) ? $txn_data['plan_id'] : '';
		$ipn['create_time'] = isset($txn_data['create_time']) ? $txn_data['create_time'] : '';

		$ipn['gateway'] = 'paypal_subscription_checkout';
		$ipn['txn_type'] = 'pp_subscription_new';
		$ipn['status'] = isset($txn_data['status']) ? ucfirst( $txn_data['status'] ) : '';
		$ipn['payment_status'] = isset($txn_data['status']) ? ucfirst( $txn_data['status'] ) : '';
		$ipn['subscription_status'] = isset($txn_data['status']) ? $txn_data['status'] : '';//Can be used to check if the subscription is active or not (in the webhook handler)

		//Amount and currency.
		$ipn['mc_gross'] = isset($txn_data['billing_info']['last_payment']['amount']['value']) ? $txn_data['billing_info']['last_payment']['amount']['value'] : '';
		$ipn['mc_currency'] = isset($txn_data['billing_info']['last_payment']['amount']['currency_code']) ? $txn_data['billing_info']['last_payment']['amount']['currency_code'] : '';
		if( $this->is_trial_payment( $billing_info )){
			//TODO: May need to get the trial amount from the 'cycle_executions' array
			$ipn['is_trial_txn'] = 'yes';
		}
		$ipn['quantity'] = 1;

		// customer info.
		$ipn['ip'] = isset($customvariables['user_ip']) ? $customvariables['user_ip'] : '';
		$ipn['first_name'] = isset($txn_data['subscriber']['name']['given_name']) ? $txn_data['subscriber']['name']['given_name'] : '';
		$ipn['last_name'] = isset($txn_data['subscriber']['name']['surname']) ? $txn_data['subscriber']['name']['surname'] : '';
		$ipn['payer_email'] = isset($txn_data['subscriber']['email_address']) ? $txn_data['subscriber']['email_address'] : '';
		$ipn['payer_id'] = isset($txn_data['subscriber']['payer_id']) ? $txn_data['subscriber']['payer_id'] : '';
		$ipn['address_street'] = $address_street;
		$ipn['address_city']    = isset($txn_data['subscriber']['shipping_address']['address']['admin_area_2']) ? $txn_data['subscriber']['shipping_address']['address']['admin_area_2'] : '';
		$ipn['address_state']   = isset($txn_data['subscriber']['shipping_address']['address']['admin_area_1']) ? $txn_data['subscriber']['shipping_address']['address']['admin_area_1'] : '';
		$ipn['address_zip']     = isset($txn_data['subscriber']['shipping_address']['address']['postal_code']) ? $txn_data['subscriber']['shipping_address']['address']['postal_code'] : '';
		$ipn['address_country'] = isset($txn_data['subscriber']['shipping_address']['address']['country_code']) ? $txn_data['subscriber']['shipping_address']['address']['country_code'] : '';

		//Additional variables
		//$ipn['reason_code'] = $txn_data['reason_code'];

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
			//SwpmLog::log_array_data_to_debug( $billing_info, true );	

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

	public function create_membership_and_save_txn_data( $data, $txn_data ){
		//Check if this is a duplicate notification.
		if( self::is_txn_already_processed($this->ipn_data)){
			//This transaction notification has already been processed. So we don't need to process it again.
			return true;
		}
		
		$button_id = $data['button_id'];
		$txn_id = isset($this->ipn_data['txn_id']) ? $this->ipn_data['txn_id'] : '';
		$txn_type = isset($this->ipn_data['txn_type']) ? $this->ipn_data['txn_type'] : '';
		SwpmLog::log_simple_debug( 'Transaction type: ' . $txn_type . ', Transaction ID: ' . $txn_id, true );

		// Custom variables
		$custom = isset($this->ipn_data['custom']) ? $this->ipn_data['custom'] : '';
		$customvariables = SwpmTransactions::parse_custom_var( $custom );
		
		// Membership level ID.
		$membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
		SwpmLog::log_simple_debug( 'Membership payment paid for membership level ID: ' . $membership_level_id, true );
		if ( ! empty( $membership_level_id ) ) {
			$swpm_id = '';
			if ( isset( $customvariables['swpm_id'] ) ) {
				$swpm_id = $customvariables['swpm_id'];
			}

			// Process the user profile creation/update.
			swpm_handle_subsc_signup_stand_alone( $this->ipn_data, $membership_level_id, $txn_id, $swpm_id );
			
		} else {
			SwpmLog::log_simple_debug( 'Membership level ID is missing in the button configuration! Cannot process this notification.', false );
		}

		// Save the transaction data.
		SwpmLog::log_simple_debug( 'Saving transaction data to the database table.', true );
		$this->ipn_data['status']  = $this->ipn_data['payment_status'];
		SwpmTransactions::save_txn_record( $this->ipn_data, array() );
		SwpmLog::log_simple_debug( 'Transaction data saved.', true );

		// Trigger the IPN processed action hook (so other plugins can can listen for this event).
		do_action( 'swpm_paypal_subscription_checkout_ipn_processed', $this->ipn_data );

		do_action( 'swpm_payment_ipn_processed', $this->ipn_data );

		return true;
	}

	public static function is_txn_already_processed( $ipn_data ){
		// Query the DB to check if we have already processed this transaction or not.
		global $wpdb;
		$txn_id = isset($ipn_data['txn_id']) ? $ipn_data['txn_id'] : '';
		$payer_email = isset($ipn_data['payer_email']) ? $ipn_data['payer_email'] : '';
		$txn_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_payments_tbl WHERE txn_id = %s and email = %s", $txn_id, $payer_email ), OBJECT );
		if (!empty($txn_row)) {
			// And if we have already processed it, do nothing and return true
			SwpmLog::log_simple_debug( "This transaction has already been processed (Txn ID: ".$txn_id.", Payer Email: ".$payer_email."). This looks to be a duplicate notification. Nothing to do here.", true );
			return true;
		}
		return false;
	}
}
