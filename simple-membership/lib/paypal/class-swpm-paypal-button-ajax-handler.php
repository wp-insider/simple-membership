<?php

/**
 * This clcass handles the ajax requests from the PayPal button's createOrder, captureOrder functions.
 * On successful onApprove event, it creates the required $ipn_data array from the transaction so it can be fed into the existing IPN handler functions easily.
 */
class SWPM_PayPal_Button_Ajax_Hander {

	public function __construct() {
		//Handle it at 'wp_loaded' hook since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the create-order ajax request for 'Buy Now' type buttons.
		add_action( 'wp_ajax_swpm_pp_create_order', array(&$this, 'swpm_pp_create_order' ) );
		add_action( 'wp_ajax_nopriv_swpm_pp_create_order', array(&$this, 'swpm_pp_create_order' ) );

		//Handle the capture-order ajax request for 'Buy Now' type buttons.
		add_action( 'wp_ajax_swpm_pp_capture_order', array(&$this, 'swpm_pp_capture_order' ) );
		add_action( 'wp_ajax_nopriv_swpm_pp_capture_order', array(&$this, 'swpm_pp_capture_order' ) );		
	}

	/**
	 * Handle the swpm_pp_create_order ajax request for 'Buy Now' type buttons.
	 */
	 public function swpm_pp_create_order(){
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
		SwpmLog::log_simple_debug( 'swpm_pp_create_order ajax request received for createOrder. Button ID: '.$button_id.', On Page Button ID: ' . $on_page_button_id, true );

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
		
		//Get the Item name for this button. This will be used as the item name in the IPN.
		$button_cpt = get_post($button_id); //Retrieve the CPT for this button
		$item_name = htmlspecialchars($button_cpt->post_title);
		$item_name = substr($item_name, 0, 127);//Limit the item name to 127 characters (PayPal limit)
		//Get the payment amount for this button.
		$payment_amount = get_post_meta($button_id, 'payment_amount', true);
		//Get the currency for this button.
		$currency = get_post_meta( $button_id, 'payment_currency', true );
		$quantity = 1;
		$digital_goods_enabled = 1;

		// Create the order using the PayPal API.
		// https://developer.paypal.com/docs/api/orders/v2/#orders_create
		$data = array(
			'item_name' => $item_name,
			'payment_amount' => $payment_amount,
			'currency' => $currency,
			'quantity' => $quantity,
			'digital_goods_enabled' => $digital_goods_enabled,
		);
		
		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		//Create the order using the PayPal API.
		$api_injector = new SWPM_PayPal_Request_API_Injector();
		$response = $api_injector->create_paypal_order_by_url_and_args( $data, $additional_args );
            
		//We requested the response body to be returned, so we need to JSON decode it.
		if( $response !== false ){
			$order_data = json_decode( $response, true );
			$paypal_order_id = isset( $order_data['id'] ) ? $order_data['id'] : '';
		} else {
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'simple-membership' ),
				)
			);
			exit;
		}

        SwpmLog::log_simple_debug( 'PayPal Order ID: ' . $paypal_order_id, true );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'order_id' => $paypal_order_id, 'order_data' => $order_data ) );
		exit;
    }


	/**
	 * Handles the order capture for standard 'Buy Now' type buttons.
	 */
	public function swpm_pp_capture_order(){

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

		//Get the order_id from data
		$order_id = isset( $data['order_id'] ) ? sanitize_text_field($data['order_id']) : '';
		if ( empty( $order_id ) ) {
			SwpmLog::log_simple_debug( 'swpm_pp_capture_order - empty order ID received.', false );
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty order ID received.', 'simple-membership' ),
				)
			);
		}

		$button_id = isset( $data['button_id'] ) ? sanitize_text_field( $data['button_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		SwpmLog::log_simple_debug( 'Received request - swpm_pp_capture_order. Order ID: ' . $order_id . ', Button ID: '.$button_id.', On Page Button ID: ' . $on_page_button_id, true );

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

		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		// Capture the order using the PayPal API.
		// https://developer.paypal.com/docs/api/orders/v2/#orders_capture
		$api_injector = new SWPM_PayPal_Request_API_Injector();
		$response = $api_injector->capture_paypal_order( $order_id, $additional_args );

		//We requested the response body to be returned, so we need to JSON decode it.
		if($response !== false){
			$txn_data = json_decode( $response, true );//JSON decode the response body that we received.
			$paypal_capture_id = isset( $txn_data['id'] ) ? $txn_data['id'] : '';
		} else {
			//Failed to capture the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to capture the order. Enable the debug logging feature to get more details.', 'simple-membership' ),
				)
			);
			exit;
		}

		SwpmLog::log_simple_debug( 'PayPal Capture ID (Transaction ID): ' . $paypal_capture_id, true );

		//--
		// SwpmLog::log_array_data_to_debug($data, true);//Debugging purpose.
		// SwpmLog::log_array_data_to_debug($txn_data, true);//Debugging purpose.
		//--

		//Create the IPN data array from the transaction data.
		//Need to have the following values in the $data array.
		//['order_id']['button_id']['on_page_button_id']['item_name']['custom_field']		
		$ipn_data = SWPM_PayPal_Utility_IPN_Related::create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data );
		SwpmLog::log_array_data_to_debug( $ipn_data, true );//Debugging purpose.
		
		/* Since this capture is done from server side, the validation is not required but we are doing it anyway. */
		//Validate the buy now txn data before using it.
		$validation_response = SWPM_PayPal_Utility_IPN_Related::validate_buy_now_checkout_txn_data( $data, $txn_data );
		if( $validation_response !== true ){
			//Debug logging will reveal more details.
			wp_send_json(
				array(
					'success' => false,
					'error_detail'  => $validation_response,/* it contains the error message */
				)
			);
			exit;
		}
		
		//Process the IPN data array
		SwpmLog::log_simple_debug( 'Validation passed. Going to create/update member account and save transaction data.', true );
		SWPM_PayPal_Utility_IPN_Related::create_membership_and_save_txn_data( $data, $txn_data, $ipn_data );

		// Trigger the IPN processed action hook (so other plugins can can listen for this event).
		do_action( 'swpm_paypal_buy_now_checkout_ipn_processed', $ipn_data );
		do_action( 'swpm_payment_ipn_processed', $ipn_data );

		//Everything is processed successfully, send the success response.
		wp_send_json( array(
			'success' => true,
			'order_id' => $order_id,
			'capture_id' => $paypal_capture_id,
			'txn_data' => $txn_data,
			'redirect_url' => SwpmMiscUtils::get_after_payment_redirect_url($button_id)
			) );
		exit;
	}	

}
