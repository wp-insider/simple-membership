<?php 

/**
 * PayPal ACDC Related Functions
 * Documentation reference: https://developer.paypal.com/docs/multiparty/checkout/advanced/integrate/
 */
class SWPM_PayPal_ACDC_Related {

	public function __construct() {
		//Handle it at 'wp_loaded' since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_acdc_related_ajax_request_actions' ) );
    }

	public function setup_acdc_related_ajax_request_actions() {
		//Handle the onApprove ajax request for ACDC 'Buy Now' type buttons order setup.
		add_action( 'wp_ajax_swpm_acdc_setup_order', array(&$this, 'swpm_acdc_setup_order' ) );
		add_action( 'wp_ajax_nopriv_swpm_acdc_setup_order', array(&$this, 'swpm_acdc_setup_order' ) );

	}

	public static function get_sdk_src_url_for_acdc( $environment_mode = 'production', $currency = 'USD' ){

        $client_id = SWPM_PayPal_Utility_Functions::get_partner_client_id_by_environment_mode( $environment_mode );
        $merchant_id = SWPM_PayPal_Utility_Functions::get_seller_merchant_id_by_environment_mode( $environment_mode );

		$query_args = array();
		$query_args['components'] = 'buttons,hosted-fields';
		$query_args['client-id'] = $client_id;
		$query_args['merchant-id'] = $merchant_id;
		$query_args['currency'] = $currency;
		$query_args['intent'] = 'capture';

		$base_url = 'https://www.paypal.com/sdk/js';
		$sdk_src_url = add_query_arg( $query_args, $base_url );
		//Example URL = "https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=".$client_id."&merchant-id=".$merchant_id."&currency=USD&intent=capture";

        //Encode the URL to prevent &currency=USD or other parameters from being converted to special symbol.
        $sdk_src_url = htmlspecialchars( $sdk_src_url, ENT_QUOTES, 'UTF-8' );
		return $sdk_src_url;
	}

    /**
     * Generates a customer ID that is used in the generate token API call.
     * PayPal's requirement is that it needs to be between 1-22 characters.
     */
	public static function generate_customer_id($length = 20) {
		//We will generate a random string of 20 characters by default and use that as the customer_id.
        //If the user is logged into the site, we can use potentially the user's ID as the customer_id.

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        $customer_id = $random_string;
        return $customer_id;
	}

    /**
     * Generates a client token that is used in ACDC (Advanced Credit and Debit Card) flow.
     * PayPal requirement: A client token needs to be generated for each time the card fields render on the page.
     */
    public function generarte_client_token( $environment_mode = 'production' ){
        //Generate a customer ID.
        $customer_id = self::generate_customer_id();

        //Get the API base URL.
        $api_base_url = SWPM_PayPal_Utility_Functions::get_api_base_url_by_environment_mode( $environment_mode );

		//Get the bearer/access token.
		$bearer = SWPM_PayPal_Bearer::get_instance();
		$bearer_token = $bearer->get_bearer_token( $environment_mode );

		$url = trailingslashit( $api_base_url ) . 'v1/identity/generate-token';
		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Bearer ' . $bearer_token,
				'Content-Type'  => 'application/json',
                'PayPal-Partner-Attribution-Id' => 'TipsandTricks_SP_PPCP',
			),
		);

        $args['body'] = wp_json_encode(
            array(
                'customer_id' => $customer_id,
            )
        );

        //Send the request to the PayPal API.
        $response = SWPM_PayPal_Request_API::send_request_by_url_and_args( $url, $args );

		if ( is_wp_error( $response ) ) {
			//WP could not post the request.
			$error_msg = $response->get_error_message();//Get the error from the WP_Error object.
			SwpmLog::log_simple_debug( 'Failed to post the request to the PayPal API. Error: ' . $error_msg, false );
			return false;
		}
        
		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			//PayPal API returned an error.
			$response_body = wp_remote_retrieve_body( $response );
			SwpmLog::log_simple_debug( 'PayPal API returned an error. Status Code: ' . $status_code . ' Response Body: ' . $response_body, false );
			return false;
		}
        
        //Get the client_token string value from the response.
		$json = json_decode( wp_remote_retrieve_body( $response ) );
        $client_token = isset( $json->client_token) ? $json->client_token : '';
		return $client_token;
    }

	/**
	 * Handles the order setup for ACDC 'Buy Now' type buttons.
	 */
	public function swpm_acdc_setup_order(){
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
		//TODO - Debugging purpose
		SwpmLog::log_array_data_to_debug( $data, true );

		$button_id = isset( $data['button_id'] ) ? sanitize_text_field( $data['button_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		SwpmLog::log_simple_debug( 'acdc_setup_order ajax request received for createOrder. Button ID: '.$button_id.', On Page Button ID: ' . $on_page_button_id, true );

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

		$order_data = [
			"intent" => "CAPTURE",
			"purchase_units" => [
				[
					"amount" => [
						"value" => $payment_amount,
						"currency_code" => $currency,
						"breakdown" => [
							"item_total" => [
								"currency_code" => $currency,
								"value" => $payment_amount * $quantity,
							]
						]
					],
					"items" => [
						[
							"name" => $item_name,
							"quantity" => $quantity,
							// "category" => $js_digital_goods_enabled ? "PHYSICAL_GOODS" : "DIGITAL_GOODS", // Uncomment if necessary
							"unit_amount" => [
								"value" => $payment_amount,
								"currency_code" => $currency,
							]
						]
					],
					"description" => $item_name,
				]
			]
		];

		//TODO - Create the order using the PayPal API.
		//https://developer.paypal.com/docs/api/orders/v2/#orders_create
		//TODO - we have the $order_data array. Use that to create the order.
		$paypal_order_id = '1234567890';


		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'order_id' => $paypal_order_id ) );
		exit;
	} 
}