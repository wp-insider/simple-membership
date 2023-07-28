<?php

class SWPM_PayPal_Bearer {

	const BEARER_CACHE_KEY = 'swpm-ppcp-bearer-cache-key';
	const BEARER_CACHE_EXPIRATION = (8 * HOUR_IN_SECONDS);//Cache for 8 hours.
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
	 * Check if a bearer token exists in the cache and if it is expired or not. Create a new one if needed.
	 */
	public function get_bearer_token( $environment_mode = '' ) {
		$paypal_cache = SWPM_PayPal_Cache::get_instance();
		
		//Check if a cached token exists
		$token_exists = $paypal_cache->has( self::BEARER_CACHE_KEY );
		if ( $token_exists ) {
			//A cached token exists. Check if it is expired.
			//SwpmLog::log_simple_debug('Cached bearer token exists. Checking if it is valid.', true);
			$token = $paypal_cache->get( self::BEARER_CACHE_KEY );
			$is_valid_token = $this->is_valid_token( $token, $environment_mode );
			if ( $is_valid_token ) {
				//The cached token is valid. Return it.
				$token_string = $token['token_value'];
				SwpmLog::log_simple_debug('Using the cached bearer token (since it is still valid). Environment mode: ' . $environment_mode, true);
				return $token_string;
			}
		}

		//A token doesn't exist or it is expired. Create a new one. 
		//It will save/cache the newly created token also.
		$token_string = $this->create_new_bearer_token( $environment_mode );
		return $token_string;
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

		//Check if the client id and secret are set before trying to create a bearer token using those values.
		if( empty( $client_id ) || empty( $secret ) ){
			SwpmLog::log_simple_debug('PayPal API credentials are not set. Missing Client ID or Secret Key. Please set them in the plugin\'s payment settings page.', false);
			return false;
		}

		//Get the API base URL based on the environment mode.
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

		//Get the token string value from the response.
		$token_string = self::get_token_from_json ( $response['body'] );
		
		//Cache/save the bearer token in the database.
		self::cache_token( $token_string, $environment_mode );

		return $token_string;
	}

	public static function cache_token( $token_string, $environment_mode = 'prouction' ) {
		$token = array(
			'token_value' => $token_string,
			'created_at' => time(),
			'environment_mode' => $environment_mode,
		);
		$paypal_cache = SWPM_PayPal_Cache::get_instance();
		$paypal_cache->set( self::BEARER_CACHE_KEY, $token, self::BEARER_CACHE_EXPIRATION );//Cache for 8 hours.
	}

	public static function delete_cached_token() {
		$paypal_cache = SWPM_PayPal_Cache::get_instance();
		$paypal_cache->delete( self::BEARER_CACHE_KEY );
	}

	/**
	 * Checks if token is expired or not
	 * @return bool
	 */
	public function is_valid_token( $token, $environment_mode = 'production' ) {
		$token_string = $token['token_value'];
		$created_at = $token['created_at'];
		$token_env_mode = $token['environment_mode'];

		if( $token_env_mode != $environment_mode ){
			//The token is not for the current environment mode. So it is not valid.
			return false;
		}

		$expiry_timestamp = $created_at + self::BEARER_CACHE_EXPIRATION;
		if ( time() > $expiry_timestamp ) {
			//The token is expired.
			return false;
		}
		
		return true;
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