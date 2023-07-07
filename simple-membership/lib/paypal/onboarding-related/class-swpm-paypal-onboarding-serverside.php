<?php

/**
 * Handles server side tasks during PPCP onboarding.
 */
class SWPM_PayPal_PPCP_Onboarding_Serverside {

	public $environment_mode = 'production'; //sandbox or production
	public $sandbox_api_base_url = 'https://api-m.sandbox.paypal.com';
	public $production_api_base_url = 'https://api-m.paypal.com';
	public $partner_merchant_id_sandbox = 'USVAEAM3FR5E2';
	public $partner_merchant_id_production = '3FWGC6LFTMTUG';
	public $seller_nonce = '';

	public function __construct() {

		//Setup AJAX request handler for the onboarding process.
		add_action( 'wp_ajax_swpm_handle_onboarded_callback_data', array(&$this, 'handle_onboarded_callback_data' ) );
		add_action( 'wp_ajax_nopriv_handle_onboarded_callback_data', array(&$this, 'handle_onboarded_callback_data' ) );

	}

	public function handle_onboarded_callback_data(){
		//TODO - Handle the data sent by PayPal after the onboarding process.
		//The get_option('swpm_ppcp_sandbox_connect_query_args') will give you the query args that you sent to the PayPal onboarding page

		SwpmLog::log_simple_debug( 'Onboarding step: handle_onboarded_callback_data.', true );

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Empty data received.', 'simple-membership' ),
				)
			);
		}

        $data_array = json_decode($data, true);
        SwpmLog::log_array_data_to_debug( $data_array, true );//Debugging purpose

		// Check nonce.
        $nonce_string = SWPM_PayPal_PPCP_Onboarding::$account_connect_string;
		if ( ! check_ajax_referer( $nonce_string, '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'simple-membership' ),
				)
			);
			exit;
		}

		//Get the environment mode.
		$environment_mode = isset( $data_array['environment'] ) ? $data_array['environment'] : 'production';

		//Generate the access token using the shared id and auth code.
        $access_token = $this->generate_token_using_shared_id( $data_array['sharedId'], $data_array['authCode'], $environment_mode);
		if ( ! $access_token ) {
			//Failed to generate token.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Failed to generate access token. check debug log file for any error message.', 'simple-membership' ),
				)
			);
			exit;
		}

		//TODO - Use the data to do the next steps.	
		SwpmLog::log_simple_debug( 'Onboarding step: access token generated successfully. Token: ' . $access_token, true );//TODO - remove later.
		$seller_api_credentials = $this->get_seller_api_credentials_using_token( $access_token, $environment_mode );
		if ( ! $seller_api_credentials ) {
			//Failed to get seller API credentials.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Failed to get seller API credentials. check debug log file for any error message.', 'simple-membership' ),
				)
			);
		}
		SwpmLog::log_array_data_to_debug( $seller_api_credentials, true );//TODO - Debugging purpose

		//TODO - Save the data to do the next steps.


        SwpmLog::log_simple_debug( 'Succedssfully processed the handle_onboarded_callback_data.', true );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'msg' => 'Succedssfully processed the handle_onboarded_callback_data.' ) );
		exit;

	}


	/**
	 * Generates a token using the shared_id and auth_token and seller_nonce. Used during the onboarding process.
	 *
	 * @param string $shared_id The shared id.
	 * @param string $auth_code The auth code.
	 * @param string $environment_mode The environment mode. sandbox or production.
	 * 
	 * Returns the token or false otherwise.
	 */
	public function generate_token_using_shared_id( $shared_id, $auth_code, $environment_mode = 'production' ) {
		SwpmLog::log_simple_debug( 'Onboarding step: generate_token_using_shared_id. Environment mode: ' . $environment_mode, true );

		if( isset($environment_mode) && $environment_mode == 'sandbox' ){
			$query_args = get_option('swpm_ppcp_sandbox_connect_query_args');
			$seller_nonce = isset($query_args['sellerNonce']) ? $query_args['sellerNonce'] : '';
		} else {
			//TODO - test after product account is created.
			$query_args = get_option('swpm_ppcp_production_connect_query_args');
			$seller_nonce = isset($query_args['sellerNonce']) ? $query_args['sellerNonce'] : '';
		}
		SwpmLog::log_simple_debug( 'Seller nonce value: ' . $seller_nonce, true );

		$api_base_url = $this->get_api_base_url_by_environment_mode( $environment_mode );

		$url = trailingslashit( $api_base_url ) . 'v1/oauth2/token/';

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $shared_id . ':' ),
			),
			'body' => array(
				'grant_type' => 'authorization_code',
				'code' => $auth_code,
				'code_verifier' => $seller_nonce,
			),
		);

		//SwpmLog::log_array_data_to_debug( $args, true);//Debugging purpose
		$response = $this->send_request_by_url_and_args( $url, $args );
		//SwpmLog::log_array_data_to_debug( $response, true);//Debugging purpose

		if ( is_wp_error( $response ) ) {
			//WP could not post the request.
			$error_msg = $response->get_error_message();//Get the error from the WP_Error object.
			SwpmLog::log_simple_debug( 'Failed to post the request to the PayPal API. Error: ' . $error_msg, false );
			return false;
		}

		$json = json_decode( $response['body'] );
		$status_code = (int) wp_remote_retrieve_response_code( $response );//HTTP response code (ex: 400)
		if ( ! isset( $json->access_token ) ) {
			//No token found. Log error.
			if (isset( $json->error )) {
				//Try to get the error descrption (if present)
				$error_msg = isset($json->error_description) ? $json->error_description : $json->error;
			} else {
				$error_msg = 'No token found.';
			}
			SwpmLog::log_simple_debug( 'Failed to generate token. Status code: '.$status_code.', Error msg: ' . $error_msg, false );
			return false;
		}

		//Success. return the token.
		return (string) $json->access_token;
	}

	/*
	 * Gets the seller's API credentials using the access token.
	 * Returns an array with client_id and client_secret or false otherwise.
	 */
	public function get_seller_api_credentials_using_token($access_token, $environment_mode = 'production'){
		SwpmLog::log_simple_debug( 'Onboarding step: get_seller_api_credentials_using_token. Environment mode: ' . $environment_mode, true );

		$api_base_url = $this->get_api_base_url_by_environment_mode( $environment_mode );
		$partner_merchant_id = $this->get_partner_merchant_id_by_environment_mode( $environment_mode );

		$url = trailingslashit( $api_base_url ) . 'v1/customer/partners/' . $partner_merchant_id . '/merchant-integrations/credentials/';
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
		);

		$response = $this->send_request_by_url_and_args( $url, $args );

		if ( is_wp_error( $response ) ) {
			//WP could not post the request.
			$error_msg = $response->get_error_message();//Get the error from the WP_Error object.
			SwpmLog::log_simple_debug( 'Failed to post the request to the PayPal API. Error: ' . $error_msg, false );
			return false;
		}

		$json = json_decode( $response['body'] );
		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( ! isset( $json->client_id ) || ! isset( $json->client_secret ) ) {
			//Seller API credentials not found. Log error.
			if (isset( $json->error )) {
				//Try to get the error descrption (if present)
				$error_msg = isset($json->error_description)? $json->error_description : $json->error;
			} else {
				$error_msg = 'No client_id or client_secret found.';
			}
			SwpmLog::log_simple_debug( 'Failed to get seller API credentials. Status code: '.$status_code.', Error msg: ' . $error_msg, false );
			return false;
		}

		//Success. return the credentials.
		return array(
			'client_id' => $json->client_id,
			'client_secret' => $json->client_secret,
			'payer_id' => $json->payer_id,
		);

	}

	/**
	 * Performs a request to the PayPal API using URL and arguments.
	 */
	public function send_request_by_url_and_args( $url, $args ) {

		$args['timeout'] = 30;

		$args = apply_filters( 'swpm_ppcp_onboarding_request_args', $args, $url );
		if ( ! isset( $args['headers']['PayPal-Partner-Attribution-Id'] ) ) {
			$args['headers']['PayPal-Partner-Attribution-Id'] = 'TipsandTricks_SP_PPCP';
		}

		$response = wp_remote_get( $url, $args );
		return $response;
	}

	public function get_api_base_url_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return $this->production_api_base_url;
		} else {
			return $this->sandbox_api_base_url;
		}
	}

	public function get_partner_merchant_id_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return $this->partner_merchant_id_production;
		} else {
			return $this->partner_merchant_id_sandbox;
		}
	}

}