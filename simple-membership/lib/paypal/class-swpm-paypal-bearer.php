<?php

class SWPM_PayPal_Bearer {
	protected static $instance;

	public function __construct() {
		//NOP
	}

	/*
	 * This needs to be a Singleton class. To make sure that the object and data is consistent throughout.
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Creates a new bearer token.
	 * @return access_token
	 */
	public function create_new_bearer_token( $environment_mode = '' ) {
        //If the environment mode is passed, then use that, otherwise use the mode from settings.
        $settings = SwpmSettings::get_instance();

        if( empty($environment_mode) ){
            //Get the environment mode from settings.
            if( $settings->get_value('enable-sandbox-testing') == '1' ){
                $environment_mode = 'sandbox';
            }else{
                $environment_mode = 'production';
            }
        }

        SwpmLog::log_simple_debug('Creating a new bearer token for environment mode: ' . $environment_mode, true);

        if( $environment_mode == 'sandbox' ){
            $client_id = $settings->get_value('paypal-sandbox-client-id');
            $secret = $settings->get_value('paypal-sandbox-secret-key');
        }else{
            $client_id = $settings->get_value('paypal-live-client-id');
            $secret = $settings->get_value('paypal-live-secret-key');
        }

        $api_base_url = SWPM_PayPal_Utility_Functions::get_api_base_url_by_environment_mode($environment_mode);
		$url = trailingslashit( $api_base_url ) . 'v1/oauth2/token?grant_type=client_credentials';

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
			),
		);

		$response = self::send_request_by_url_and_args( $url, $args );

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

		$token = self::get_token_from_json ( $response['body'] );
		//TODO save/cache the bearer token in the database.

		return $token;
	}

	public static function get_token_from_json( $json ) {
		$json = (object) json_decode( $json );
        $token = '';
		if ( isset( $json->access_token ) || isset( $json->client_token ) ) {
			$token = isset( $json->access_token ) ? $json->access_token : $json->client_token;
		}
		return $token;
	}

	public static function get_api_base_url_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return SWPM_PayPal_Main::$api_base_url_production;
		} else {
			return SWPM_PayPal_Main::$api_base_url_sandbox;
		}
	}

	/**
	 * Performs a request to the PayPal API using URL and arguments.
	 */
	public static function send_request_by_url_and_args( $url, $args ) {

		$args['timeout'] = 30;

		$args = apply_filters( 'swpm_ppcp_onboarding_request_args', $args, $url );
		if ( ! isset( $args['headers']['PayPal-Partner-Attribution-Id'] ) ) {
			$args['headers']['PayPal-Partner-Attribution-Id'] = 'TipsandTricks_SP_PPCP';
		}

		$response = wp_remote_get( $url, $args );
		return $response;
	}

}