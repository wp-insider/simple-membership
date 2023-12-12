<?php

/**
 * This clcass handles the ajax requests from the PayPal button's createOrder, captureOrder functions.
 */
class SWPM_PayPal_Ajax_Create_Capture_Order {

	public function __construct() {
		//Handle it at 'wp_loaded' hook since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the create_order ajax request for 'Buy Now' type buttons.
		add_action( 'wp_ajax_swpm_pp_create_order', array(&$this, 'swpm_pp_create_order' ) );
		add_action( 'wp_ajax_nopriv_swpm_pp_create_order', array(&$this, 'swpm_pp_create_order' ) );

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
		
		$api_injector = new SWPM_PayPal_Request_API_Injector();
		$response = $api_injector->create_paypal_order_by_url_and_args( $data );
		// SwpmLog::log_simple_debug('--- Var Export Below ---', true);
		// $debug = var_export($response, true);
		// SwpmLog::log_simple_debug($debug, true);
            
		if($response !== false){
			$paypal_order_id = $response;
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
		wp_send_json( array( 'success' => true, 'order_id' => $paypal_order_id ) );
		exit;
    }

}
