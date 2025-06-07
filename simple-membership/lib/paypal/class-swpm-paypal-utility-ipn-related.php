<?php

class SWPM_PayPal_Utility_IPN_Related {

	public static function create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data ) {
		$ipn_data = array();

		//Get the custom field value from the request
		$custom = isset($data['custom_field']) ? $data['custom_field'] : '';
		$custom = urldecode( $custom );//Decode it just in case it was encoded.

		//Add the PayPal API order_id value to the reference parameter. So it gets saved with custom field data. This will be used to also save it to the reference DB column field when saving the transaction.
		if(isset($data['order_id'])){
			$data['custom_field'] = $custom . '&reference=' . $data['order_id'];
		} else if(isset($data['orderID'])){
			$data['custom_field'] = $custom . '&reference=' . $data['orderID'];
		}

		//Parse the custom field to read the IP address.
		$customvariables = SwpmTransactions::parse_custom_var( $custom );

		$purchase_units = isset($txn_data['purchase_units']) ? $txn_data['purchase_units'] : array();

		//The $data['orderID'] is the ID for the order created using createOrder API call. The Transaction ID is the ID for the captured payment.
		$txn_id = isset($txn_data['purchase_units'][0]['payments']['captures'][0]['id']) ? $txn_data['purchase_units'][0]['payments']['captures'][0]['id'] : '';
		
		$address_street = isset($txn_data['purchase_units'][0]['shipping']['address']['address_line_1']) ? $txn_data['purchase_units'][0]['shipping']['address']['address_line_1'] : '';
		if ( isset ( $txn_data['purchase_units'][0]['shipping']['address']['address_line_2'] )){
			//If address line 2 is present, add it to the address.
			$address_street .= ", " . $txn_data['purchase_units'][0]['shipping']['address']['address_line_2'];
		}

		$ipn_data['gateway'] = 'paypal_buy_now_checkout';
		$ipn_data['txn_type'] = 'pp_buy_now_new';
		$ipn_data['custom'] = isset($data['custom_field']) ? $data['custom_field'] : '';
		$ipn_data['txn_id'] = $txn_id;
		$ipn_data['subscr_id'] = $txn_id;//Same as txn_id for one-time payments.

		//This will save the button ID in the swpm_transactions CPT (for a reference to the button used for the payment)
		$ipn_data['payment_button_id'] = isset($data['button_id']) ? $data['button_id'] : '';

		$ipn_data['item_number'] = isset($data['button_id']) ? $data['button_id'] : '';
		$ipn_data['item_name'] = isset($data['item_name']) ? $data['item_name'] : '';

		$ipn_data['status'] = isset($txn_data['status']) ? ucfirst( strtolower($txn_data['status']) ) : '';
		$ipn_data['payment_status'] = isset($txn_data['status']) ? ucfirst( strtolower($txn_data['status']) ) : '';

		//Amount
		if ( isset($txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['value']) ){
			//This is for PayPal checkout serverside capture.
			$ipn_data['mc_gross'] = $txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
		} else if ( isset($txn_data['purchase_units'][0]['amount']['value']) ){
			//This is for PayPal Checkout client-side capture (deprecated)
			$ipn_data['mc_gross'] = $txn_data['purchase_units'][0]['amount']['value'];
		} else {
			$ipn_data['mc_gross'] = 0;
		}

		//Currency
		if ( isset($txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code']) ){
			//This is for PayPal checkout serverside capture.
			$ipn_data['mc_currency'] = $txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
		} else if ( isset( $txn_data['purchase_units'][0]['amount']['currency_code']) ){
			//This is for PayPal Checkout client-side capture (deprecated)
			$ipn_data['mc_currency'] = $txn_data['purchase_units'][0]['amount']['currency_code'];
		} else {
			$ipn_data['mc_currency'] = 0;
		}

		//Default to 1 for quantity.
		$ipn_data['quantity'] = 1;

		// customer info.
		$ipn_data['ip'] = isset($customvariables['user_ip']) ? $customvariables['user_ip'] : '';
		$ipn_data['first_name'] = isset($txn_data['payer']['name']['given_name']) ? $txn_data['payer']['name']['given_name'] : '';
		$ipn_data['last_name'] = isset($txn_data['payer']['name']['surname']) ? $txn_data['payer']['name']['surname'] : '';
		$ipn_data['payer_email'] = isset($txn_data['payer']['email_address']) ? $txn_data['payer']['email_address'] : '';
		$ipn_data['payer_id'] = isset($txn_data['payer']['payer_id']) ? $txn_data['payer']['payer_id'] : '';
		$ipn_data['address_street'] = $address_street;
		$ipn_data['address_city']    = isset($txn_data['purchase_units'][0]['shipping']['address']['admin_area_2']) ? $txn_data['purchase_units'][0]['shipping']['address']['admin_area_2'] : '';
		$ipn_data['address_state']   = isset($txn_data['purchase_units'][0]['shipping']['address']['admin_area_1']) ? $txn_data['purchase_units'][0]['shipping']['address']['admin_area_1'] : '';
		$ipn_data['address_zip']     = isset($txn_data['purchase_units'][0]['shipping']['address']['postal_code']) ? $txn_data['purchase_units'][0]['shipping']['address']['postal_code'] : '';
		$country_code = isset($txn_data['purchase_units'][0]['shipping']['address']['country_code']) ? $txn_data['purchase_units'][0]['shipping']['address']['country_code'] : '';
		$ipn_data['address_country'] = SwpmMiscUtils::get_country_name_by_country_code($country_code);
	

		/**********************************/
		//Ensure the customer's email and name are set. For guest checkout, the email and name may not be set in the standard onApprove data (due to privacy reasons).
		//So we will query the Order details from the PayPal API to get the customer's email and name (if needed).
		/**********************************/
		if( empty($ipn_data['payer_email']) || empty($ipn_data['first_name']) || empty($ipn_data['last_name']) ){
			//Use the order ID to get the customer's email and name from the PayPal API.
			$pp_order_id = isset($data['order_id']) ? $data['order_id'] : '';
			SwpmLog::log_simple_debug( 'Customer Email or Name not set in the onApprove data. Going to query the PayPal API for order details. Order ID: ' . $pp_order_id, true );

			//This is for on-site checkout only. So the 'mode' and API creds will be whatever is currently set in the settings.
			$api_injector = new SWPM_PayPal_Request_API_Injector();
			$order_details = $api_injector->get_paypal_order_details( $pp_order_id );
			if( $order_details !== false ){
				//The order details were retrieved successfully.
				$payer = isset($order_details->payer) ? $order_details->payer : array();
				if(is_object($payer)){
					//Convert the object to an array.
					$customer_data_array = json_decode(json_encode($payer), true);
				}
				//Debugging only.
				SwpmLog::log_array_data_to_debug( $customer_data_array, true );
				
				if( empty($ipn_data['payer_email']) && isset($customer_data_array['email_address']) ){
					//Set the payer email from the subscriber data.
					$ipn_data['payer_email'] = $customer_data_array['email_address'];
				}
				if( empty($ipn_data['first_name']) && isset($customer_data_array['name']['given_name']) ){
					//Set the payer first name from the subscriber data.
					$ipn_data['first_name'] = $customer_data_array['name']['given_name'];
				}
				if( empty($ipn_data['last_name']) && isset($customer_data_array['name']['surname']) ){
					//Set the payer last name from the subscriber data.
					$ipn_data['last_name'] = $customer_data_array['name']['surname'];
				}
				SwpmLog::log_simple_debug( 'Customer Email: ' . $ipn_data['payer_email'] . ', First Name: ' . $ipn_data['first_name'] . ', Last Name: ' . $ipn_data['last_name'], true );

			} else {
				//Error getting order details.
				$validation_error_msg = 'Validation Error! Failed to get transaction/order details from the PayPal API. PayPal Order ID: ' . $pp_order_id;
				SwpmLog::log_simple_debug( $validation_error_msg, false );
			}
		}

		//Return the IPN data array.
		return $ipn_data;
	}


	/**
	 * Validate that the transaction/order exists in PayPal and the price matches the price in the DB.
	 */
	public static function validate_buy_now_checkout_txn_data( $data, $txn_data ) {
		//Get the transaction/order details from PayPal API endpoint - /v2/checkout/orders/{$order_id}
		$pp_orderID = isset($data['order_id']) ? $data['order_id'] : $data['orderID'];//backward compatibility.
		$button_id = $data['button_id'];

		$validation_error_msg = '';

		//This is for on-site checkout only. So the 'mode' and API creds will be whatever is currently set in the settings.
		$api_injector = new SWPM_PayPal_Request_API_Injector();
		$order_details = $api_injector->get_paypal_order_details( $pp_orderID );
		if( $order_details !== false ){
			//The order details were retrieved successfully.
			if(is_object($order_details)){
				//Convert the object to an array.
				$order_details = json_decode(json_encode($order_details), true);
			}

			// Debug purpose only.
			// SwpmLog::log_simple_debug( 'PayPal Order Details: ', true );
			// SwpmLog::log_array_data_to_debug( $order_details, true );

			// Check that the order's capture status is COMPLETED.
			$status = '';
			// Check if the necessary keys and arrays exist and are not empty
			if (!empty($order_details['purchase_units']) && !empty($order_details['purchase_units'][0]['payments']) && !empty($order_details['purchase_units'][0]['payments']['captures'])) {
				// Access the first item in the 'captures' array
				$capture = $order_details['purchase_units'][0]['payments']['captures'][0];
				$capture_id = isset($capture['id']) ? $capture['id'] : '';
				// Check if 'status' is set for the capture
				if (isset($capture['status'])) {
					// Extract the 'status' value
					$status = $capture['status'];
				}
			}
			if ( strtolower($status) != strtolower('COMPLETED') ) {
				//The order is not completed yet.
				$validation_error_msg = 'Validation Error! The transaction status is not completed yet. Button ID: ' . $button_id . ', PayPal Capture ID: ' . $capture_id . ', Capture Status: ' . $status;
				SwpmLog::log_simple_debug( $validation_error_msg, false );
				return $validation_error_msg;
			}

			//Check that the amount matches with what we expect.
			$amount = isset($order_details['purchase_units'][0]['amount']['value']) ? $order_details['purchase_units'][0]['amount']['value'] : 0;

			$payment_amount_expected = get_post_meta( $button_id, 'payment_amount', true );
			if( floatval($amount) < floatval($payment_amount_expected) ){
				//The amount does not match.
				$validation_error_msg = 'Validation Error! The payment amount does not match. Button ID: ' . $button_id . ', PayPal Order ID: ' . $pp_orderID . ', Amount Received: ' . $amount . ', Amount Expected: ' . $payment_amount_expected;
				SwpmLog::log_simple_debug( $validation_error_msg, false );
				return $validation_error_msg;
			}

			//Check that the currency matches with what we expect.
			$currency = isset($order_details['purchase_units'][0]['amount']['currency_code']) ? $order_details['purchase_units'][0]['amount']['currency_code'] : '';
			$currency_expected = get_post_meta( $button_id, 'payment_currency', true );
			if( $currency != $currency_expected ){
				//The currency does not match.
				$validation_error_msg = 'Validation Error! The payment currency does not match. Button ID: ' . $button_id . ', PayPal Order ID: ' . $pp_orderID . ', Currency Received: ' . $currency . ', Currency Expected: ' . $currency_expected;
				SwpmLog::log_simple_debug( $validation_error_msg, false );
				return $validation_error_msg;
			}

		} else {
			//Error getting order details.
			$validation_error_msg = 'Validation Error! Failed to get transaction/order details from the PayPal API. PayPal Order ID: ' . $pp_orderID;
			//TODO - Show additional error details if available.
			SwpmLog::log_simple_debug( $validation_error_msg, false );
			return $validation_error_msg;
		}

		//All good. The data is valid.
		return true;
	}

	public static function create_membership_and_save_txn_data( $data, $txn_data, $ipn_data){
		//Check if this is a duplicate notification.
		if( SWPM_PayPal_Utility_IPN_Related::is_txn_already_processed($ipn_data)){
			//This transaction notification has already been processed. So we don't need to process it again.
			return true;
		}
		
		$button_id = $data['button_id'];
		$txn_id = isset($ipn_data['txn_id']) ? $ipn_data['txn_id'] : '';
		$txn_type = isset($ipn_data['txn_type']) ? $ipn_data['txn_type'] : '';
		SwpmLog::log_simple_debug( 'Transaction type: ' . $txn_type . ', Transaction ID: ' . $txn_id, true );

		// Custom variables
		$custom = isset($ipn_data['custom']) ? $ipn_data['custom'] : '';
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
			swpm_handle_subsc_signup_stand_alone( $ipn_data, $membership_level_id, $txn_id, $swpm_id );
			
		} else {
			SwpmLog::log_simple_debug( 'Membership level ID is missing in the button configuration! Cannot process this notification.', false );
		}

		// Save the transaction data.
		SwpmLog::log_simple_debug( 'Saving transaction data to the database table.', true );
		SwpmTransactions::save_txn_record( $ipn_data, array() );
		SwpmLog::log_simple_debug( 'Transaction data saved.', true );

		return true;
	}

	public static function is_txn_already_processed( $ipn_data ){
		// Query the DB to check if we have already processed this transaction or not.
		$txn_id = isset($ipn_data['txn_id']) ? $ipn_data['txn_id'] : '';
		$payer_email = isset($ipn_data['payer_email']) ? $ipn_data['payer_email'] : '';
        $txn_row = SwpmTransactions::get_transaction_row_by_txn_id_and_email($txn_id, $payer_email);
        if (!empty($txn_row)) {
			// And if we have already processed it, do nothing and return true
			SwpmLog::log_simple_debug( "This transaction has already been processed (Txn ID: ".$txn_id.", Payer Email: ".$payer_email."). This looks to be a duplicate notification. Nothing to do here.", true );
			return true;
		}
		return false;
	}
        
}