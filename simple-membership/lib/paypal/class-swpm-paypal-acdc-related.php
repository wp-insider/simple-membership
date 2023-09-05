<?php 

/**
 * PayPal ACDC Related Functions
 */
class SWPM_PayPal_ACDC_Related {

	public function __construct() {

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

}