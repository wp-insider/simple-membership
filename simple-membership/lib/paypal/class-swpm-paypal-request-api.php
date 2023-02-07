<?php
/**
 * PayPal REST API request class.
 */
class SWPM_PayPal_Request_API {

	protected static $instance;

	protected $client_id;
	protected $secret;
	protected $basic_auth_string;

	public $environment_mode = 'production'; //sandbox or production
	public $sandbox_api_base_url = 'https://api-m.sandbox.paypal.com';
	public $production_api_base_url = 'https://api.paypal.com';

	public $last_error;

	public $app_info = array(
		'name' => 'Simple Membership',
		'url' => 'https://wordpress.org/plugins/simple-membership/',
	);

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function set_mode_and_api_credentials( $mode, $live_client_id, $live_secret, $sandbox_client_id, $sandbox_secret ) {
		$this->set_api_environment_mode( $mode );
		if ( $mode == 'production' ) {
			$client_id = $live_client_id;
			$secret = $live_secret;
		} else {
			$client_id = $sandbox_client_id;
			$secret = $sandbox_secret;
		}
		$this->set_api_credentials( $client_id, $secret );
	}

	public function set_api_credentials( $client_id, $secret ) {
		if( empty( $client_id ) || empty( $secret ) ){
			wp_die( "PayPal API credentials are not set. Missing Client ID or Secret Key. Please set them in the plugin's payment settings page." );
		}
		$this->client_id = $client_id;
		$this->secret = $secret;
		$this->basic_auth_string = base64_encode( $this->client_id . ":" . $this->secret );
	}

	public function set_api_environment_mode( $mode = 'production' ) {
		$this->environment_mode = $mode;
	}

	public function get_api_environment_mode() {
		return $this->environment_mode;
	}

	public function get_api_base_url() {
		if ($this->environment_mode == 'production') {
			return $this->production_api_base_url;
		} else {
			return $this->sandbox_api_base_url;
		}
	}

	/*
	 * Useful to encode params for HTTP GET request where an array can be in the URL.
	 */
	private function encode_params( $d ) {
		if (true === $d) {
			return 'true';
		}
		if (false === $d) {
			return 'false';
		}
		if (is_array( $d )) {
			$res = array();
			foreach ( $d as $k => $v ) {
				$res[ $k ] = $this->encode_params( $v );
			}
			return $res;
		}
		return $d;
	}

	private function before_request() {
		//Reset the last_error variable before making a request.
		$this->last_error = array();
	}

	public function get_last_error() {
		return $this->last_error;
	}

	private function get_headers() {
		$ua_string = $this->format_app_info_to_string( $this->app_info );

		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Basic ' . $this->basic_auth_string,
			'User-Agent' => $ua_string,
			'PayPal-Partner-Attribution-Id' => 'TipsandTricks_SP',
		);
		return $headers;
	}

	/**
	 * Make GET API request
	 *
	 * @param  string $endpoint
	 * Endpoint to make request to. Example: '/v1/billing/plans'
	 * @param  array $params
	 * @return mixed
	 * `object` on success, `false` on error
	 */
	public function get( $endpoint, $params = array(), $additional_args = array() ) {

		$this->before_request();

		$headers = $this->get_headers();

		$api_base_url = $this->get_api_base_url();
		$request_url = $api_base_url . $endpoint; //Example: https://api-m.sandbox.paypal.com/v1/billing/plans

		$res = wp_remote_get(
			$request_url,
			array(
				'headers' => $headers,
				'body' => $this->encode_params( $params ),
			)
		);

		if( isset( $additional_args['return_raw_response'] ) && $additional_args['return_raw_response'] ){
			return $res;
		}
		$status_code = isset( $additional_args['status_code'] ) ? $additional_args['status_code'] : 200;
		$return = $this->process_request_result( $res, $status_code );

		return $return;
	}

	/**
	 * Make POST API request
	 *
	 * @param  string $endpoint
	 * Endpoint to make request to. Example: '/v1/catalogs/products'
	 * @param  array $params
	 * Parameters to send
	 * @param string $method
	 * Request method. Default is 'POST'
	 * @return mixed
	 * `object` on success, `false` on error
	 */
	public function post( $endpoint, $params = array(), $additional_args = array() ) {

		$this->before_request();

		$headers = $this->get_headers();

		$api_base_url = $this->get_api_base_url();
		$request_url = $api_base_url . $endpoint; //Example: https://api-m.sandbox.paypal.com/v1/catalogs/products

		$res = wp_remote_post(
			$request_url,
			array(
				'headers' => $headers,
				'body' => json_encode( $params ),
			)
		);

		if( isset( $additional_args['return_raw_response'] ) && $additional_args['return_raw_response'] ){
			return $res;
		}
		//POST success response status code is 201 by default
		$status_code = isset( $additional_args['status_code'] ) ? $additional_args['status_code'] : 201;
		$return = $this->process_request_result( $res, $status_code );

		return $return;
	}

	/**
	 * Make DELETE API request
	 * @param mixed $endpoint
	 * @param mixed $params
	 * @return mixed
	 */
	public function delete( $endpoint, $params = array(), $additional_args = array() ) {

		$this->before_request();

		$headers = $this->get_headers();

		$api_base_url = $this->get_api_base_url();
		$request_url = $api_base_url . $endpoint; //Example: https://api-m.sandbox.paypal.com/v1/catalogs/products

		$res = wp_remote_request(
			$request_url,
			array(
				'method' => 'DELETE',
				'headers' => $headers,
				'body' => json_encode( $params ),
			)
		);

		if( isset( $additional_args['return_raw_response'] ) && $additional_args['return_raw_response'] ){
			return $res;
		}
		//DELETE success response status code is 204 by default
		$status_code = isset( $additional_args['status_code'] ) ? $additional_args['status_code'] : 204;
		$return = $this->process_request_result( $res, $status_code );

		return $return;
	}
	/*
	 * Checks the response and if it finds any error, it stores the error details in the last_error var then returns false.
	 * Minimizes the amount of response code check the source code has to do.
	 */
	private function process_request_result( $res, $status_code = 200 ) {
		if (is_wp_error( $res )) {
			$this->last_error['error_message'] = $res->get_error_message();
			$this->last_error['error_code'] = $res->get_error_code();
			return false;
		}

		if ($status_code !== $res['response']['code']) {
			if (! empty( $res['body'] )) {
				$body = json_decode( $res['body'], true );
				if (isset( $body['error'] )) {
					$this->last_error['error_message'] = $body['error_description'];
					$this->last_error['error_code'] = $body['error'];//String error code (ex: "invalid_client")
					$this->last_error['http_code'] = $res['response']['code'];//HTTP error code (ex: 400)
				}
			} else {
				//Empty body response.
				$this->last_error['error_message'] = 'Error! The body of the response is empty. Check that the expected response status code is correct.';
			}
			return false;
		}

		$return = json_decode( $res['body'] );

		return $return;
	}

	private function format_app_info_to_string( $app_info ) {
		if (null !== $app_info) {
			$string = $app_info['name'];
			if (null !== $app_info['url']) {
				$string .= ' (' . $app_info['url'] . ')';
			}
			return $string;
		}
		return "";
	}
}