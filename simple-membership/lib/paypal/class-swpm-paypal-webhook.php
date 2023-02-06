<?php

/**
 * A Webhook class. Represents a webhook object with parameters in a given mode.
 */
class SWPM_PayPal_Webhook {
	/**
	 * The webhook API resourse ID.
	 */
	protected $id;

	/**
	 * The webhook mode.
	 */
	 protected $mode;

	 //The paypal request API object (for sending POST and GET requests to PayPal API endpoints)
	 protected $paypal_req_api;

	protected $live_client_id;
	protected $live_secret;
	protected $sandbox_client_id;
	protected $sandbox_secret;
	/**
	 * Creates a webhook for given mode.
	 *
	 * @param string $mode The transaction mode (`live` or `test`).
	 */
	public function __construct() {
		//Setup the PayPal API request object so that we can use it to make pre-made API requests easily.
		$settings = SwpmSettings::get_instance();
		$this->live_client_id = $settings->get_value('paypal-live-client-id');
		$this->live_secret = $settings->get_value('paypal-live-secret-key');    
		$this->sandbox_client_id = $settings->get_value('paypal-sandbox-client-id');
		$this->sandbox_secret = $settings->get_value('paypal-sandbox-secret-key');
		$sandbox_enabled = $settings->get_value('enable-sandbox-testing');
		$paypal_mode = $sandbox_enabled ? 'sandbox' : 'production';
		$paypal_req_api = SWPM_PayPal_Request_API::get_instance();
		$paypal_req_api->set_mode_and_api_credentials( $paypal_mode, $this->live_client_id, $this->live_secret, $this->sandbox_client_id, $this->sandbox_secret );            

		$this->paypal_req_api = $paypal_req_api;
		$this->mode = $paypal_req_api->get_api_environment_mode();
		$this->id = get_option( 'swpm_paypal_webhook_id_' . $this->mode );
	}

	/**
	 * Sets the webhook mode. Used to override/set the mode (if needed) after the object is created.
	 */
	public function set_mode_and_api_creds_for_webhook( $mode, $client_id, $secret ) {
		$this->mode = $mode;

		//The mode has been overridden. Need to update the webhook ID as well.
		$this->id = get_option( 'swpm_paypal_webhook_id_' . $this->mode );

		//Update the PayPal API request object with the new mode and credentials.
		$this->paypal_req_api->set_api_environment_mode( $this->mode );
		$this->paypal_req_api->set_api_credentials( $client_id, $secret );
	}

	/**
	 * Retrieves the webhook id
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Saves webhook ID in settings.
	 *
	 * @param string $id The Woebhook PayPal ID.
	 */
	public function set_id( $id ) {
		update_option( 'swpm_paypal_webhook_id_' . $this->mode, $id );
	}

	/**
	 * Retrieves the webhook listener URL
	 *
	 * @return string
	 */
	public function get_url() {
		return add_query_arg(
			array(
				'action' => 'swpm_paypal_webhook_event',
				'mode'   => $this->mode,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Retrieves the Webhook details via PayPal API
	 * It will retrieve the webhook based on the mode and credentials set in the $this->paypal_req_api object.
	 */
	public function get() {
		if ( ! $this->get_id() ) {
			//No webhook ID exists. Need to create new.
			return new WP_Error( 'INVALID_RESOURCE_ID' );
		}

		$params = array();
		$endpoint = '/v1/notifications/webhooks/'.$this->get_id();

		//$additional_args = array( 'return_raw_response' => '1', 'status_code' => '200' );
		$response = $this->paypal_req_api->get($endpoint, $params );
		
		//var_dump( $response );//TODO - remove this line

		if( $response !== false){
			//Response is a success!
			//Array of https://developer.paypal.com/docs/api/webhooks/v1/#definition-event_type
			//$webhook_event_types = $response;
			return $response;
		} else {
			//Error response. Convert to WP_Error object so it can be handled easily by the caller
			//SWPM_PayPal_Request_API->last_error array will have the detailed error message
			$last_error = $this->paypal_req_api->get_last_error();
			$response = new WP_Error();
			if( isset( $last_error['error_code'] ) && isset( $last_error['error_message'])){
				$response->add( $last_error['error_code'], $last_error['error_message'] );
			} else {
				$response->add( 'UNKNOWN_ERROR', 'Unknown error occurred while retrieving the webhook details.' );
			}
			return $response;
		}
	}

	/**
	 * Creates the Webhook for subscription payment events via PayPal API
	 * It will create the webhook based on the mode and credentials set in the $this->paypal_req_api object.
	 */
	public function create() {
		//Get the list of event types to be included in the webhook
		$types = self::get_event_types();
		foreach ( $types as &$type ) {
			$type = array( 'name' => $type );
		}

		//Create the params array with the webhook details
		$params = array(
			'url' => $this->get_url(),
			'event_types' => $types,
		);

		$endpoint = '/v1/notifications/webhooks';

		$response = $this->paypal_req_api->post($endpoint, $params);
		if ( $response !== false){
			//Response is a success!
			$created_webhook_id = $response->id;
			$this->set_id( $created_webhook_id );
			return $response;
		} else {
			//Error response. Convert to WP_Error object so it can be handled easily by the caller
			//SWPM_PayPal_Request_API->last_error array will have the detailed error message
			$last_error = $this->paypal_req_api->get_last_error();
			$response = new WP_Error();
			if( isset( $last_error['error_code'] ) && isset( $last_error['error_message'])){
				$response->add( $last_error['error_code'], $last_error['error_message'] );
			} else {
				$response->add( 'UNKNOWN_ERROR', 'Unknown error occurred while creating the webhook.' );
			}
			return $response;
		}
	}

	/**
	 * Deletes the Webhook via PayPal API
	 * It will delete the webhook based on the mode and credentials set in the $this->paypal_req_api object.
	 */
	public function delete() {
		$params = array();
		$endpoint = '/v1/notifications/webhooks/'.$this->get_id();

		$response = $this->paypal_req_api->delete($endpoint, $params);
		if ( $response !== false){
			//Response is a success!
			delete_option( 'swpm_paypal_webhook_id_' . $this->mode );
			return $response;
		} else {
			//Error response. Convert to WP_Error object so it can be handled easily by the caller
			//SWPM_PayPal_Request_API->last_error array will have the detailed error message
			$last_error = $this->paypal_req_api->get_last_error();
			$response = new WP_Error();
			if( isset( $last_error['error_code'] ) && isset( $last_error['error_message'])){
				$response->add( $last_error['error_code'], $last_error['error_message'] );
			} else {
				$response->add( 'UNKNOWN_ERROR', 'Unknown error occurred while deleting the webhook.' );
			}
			return $response;
		}
	}

	/**
	 * Verifies webhook event via PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api-basics/notifications/webhooks/notification-messages/
	 *
	 * @param array $event   The event data received from PayPal API.
	 * @param array $headers The request headers received from PayPal API.
	 */
	public function verify( $event, $headers ) {
		$headers = array_intersect_key(
			$headers,
			array(
				'PAYPAL-TRANSMISSION-ID'   => '',
				'PAYPAL-TRANSMISSION-TIME' => '',
				'PAYPAL-CERT-URL'          => '',
				'PAYPAL-AUTH-ALGO'         => '',
				'PAYPAL-TRANSMISSION-SIG'  => '',
			)
		);

		if ( 5 > count( $headers ) ) {
			return new WP_Error(
				'invalid_headers',
				'',
				array(
					'msg'  => 'Invalid headers',
					'data' => $headers,
				)
			);
		}

		$params = array(
			'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'],
			'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
			'cert_url'          => $headers['PAYPAL-CERT-URL'],
			'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'],
			'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'],
			'webhook_id'        => $this->get_id(),
			'webhook_event'     => $event,
		);

		//https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature_post
		$endpoint = '/v1/notifications/verify-webhook-signature';

		//A successful Verify POST should return 200 status code.
		$additional_args = array();
		$additional_args['status_code'] = 200;
		$response = $this->paypal_req_api->post($endpoint, $params, $additional_args);
		if ( $response !== false){
			//Response is a success! {"verification_status": "SUCCESS"}
			return $response;
		} else {
			//SWPM_PayPal_Request_API->last_error array will have the detailed error message
			return false;
		}
	}

	/**
	 * Returns a list of events to listen to.
	 *
	 * @return array
	 */
	public static function get_event_types() {
		return array(
			'PAYMENT.SALE.COMPLETED',              // A payment is made on a subscription.
			'BILLING.SUBSCRIPTION.CREATED',        // A subscription is created.
			'BILLING.SUBSCRIPTION.ACTIVATED',      // A subscription is activated.
			'BILLING.SUBSCRIPTION.UPDATED',        // A subscription is updated.
			'BILLING.SUBSCRIPTION.EXPIRED',        // A subscription expires.
			'BILLING.SUBSCRIPTION.CANCELLED',      // A subscription is cancelled.
			'BILLING.SUBSCRIPTION.SUSPENDED',      // A subscription is suspended.
			'BILLING.SUBSCRIPTION.PAYMENT.FAILED', // Payment failed on subscription.
		);
	}

	/**
	 * Checks whether webhooks are created for a current site. 
	 * It will check the webhook based on the mode and credentials set in the $this->paypal_req_api object.
	 *
	 * @return array
	 */
	public function check_webhook() {
		$response = $this->get();

		if( is_wp_error( $response ) ){
			//Error response
			$ret = array(
				'status'  => 'no', /* Webhook does not exist */
				'hidebtn' => false,
			);
			$error_code = $response->get_error_code();
			if ( $error_code === 'INVALID_RESOURCE_ID' ) {
				$ret['msg'] = __( 'No webhook found. Use the following Create Webhook button to create a new webhook automatically in your PayPal account.', 'simple-membership' );
			} elseif ( $error_code === 'UNAUTHORIZED' ) {
				$ret['msg']     = $response->get_error_message() . '. ' . sprintf( __( 'PayPal API Credential information is missing in settings. Please enter valid PayPal API Credentials in the General Settings tab for %s mode.', 'simple-membership' ), $this->mode );
			} elseif ( $error_code === 'invalid_client' ) {
				$ret['msg']     = sprintf( __( 'Invalid or Missing API Credentials! Check the plugin settings and enter valid API credentials in the PayPal Credentials section for %s mode.', 'simple-membership' ), $this->mode );
			} else {
				$ret['msg']     = $response->get_error_message();
			}
			return $ret;
		}

		//Successfull response
		$ret = array();
		$ret['status']  = 'yes'; //Webhook exists.
		$ret['msg']     = __( 'Webhook exists. If you still have issues with webhooks, you can delete it and create again.', 'simple-membership' );
		$ret['hidebtn'] = true;
		return $ret;
	}

	/**
	 * Checks and creates a webhook for the site.
	 * It will check and create the webhook based on the mode and credentials set in the $this->paypal_req_api object.
	 * 
	 * @return array
	 */
	public function check_and_create_webhook(){
		//First check if webhook already exists for the mode and account set in $this->paypal_req_api object.
		$ret = $this->check_webhook();
		if ( $ret['status'] == 'yes' ) {
			//Webhook already exists. No need to create a new one.
			return $ret;
		}

		// Webhook does not exist. Create a new one.

		// Check if webhook URL is using HTTPS.
		$ret = array();
		$webhook_url = $this->get_url();
		$protocol = wp_parse_url( $webhook_url, PHP_URL_SCHEME );
		if ( $protocol !== 'https' ) {
			$ret['status'] = 'no';
			$ret['msg']    = __( 'Invalid webhook URL', 'simple-membership' )  . ': ' . __( 'Note that the PayPal subscription API requires your site to use HTTPS URLs. You must use an SSL certificate with HTTPS URLs to complete the setup of the subscription addon and use it.', 'simple-membership' );
			return $ret;
		}

		$response = $this->create();
		if ( ! is_wp_error( $response ) ) {
			// webhook created.
			$ret['status']  = 'yes';
			$ret['hidebtn'] = true;
			$ret['msg'] = __( 'Webhook has been created', 'simple-membership' );
		} else {
			// Error occurred during webhook creation.
			$ret['status'] = 'no';
			$ret['msg'] = $response->get_error_message() . ': ' . json_encode( $response->get_error_data() );
		}
		return $ret;
	}

	/**
	 * Check and delete the webhook for the site.
	 * It will check and delete the webhook based on the mode and credentials set in the $this->paypal_req_api object.
	 * 
	 * @return array
	 */
	public function check_and_delete_webhook() {
		//First check if webhook exists for the mode and account set in $this->paypal_req_api object.
		$ret = $this->check_webhook();
		if ( $ret['status'] == 'yes' ) {
			//Webhook exists. Try to delete it.
			$response = $this->delete();
			if ( !is_wp_error( $response )){
				//Webhook deleted.
				$ret['success'] = true;
				$ret['msg'] = __( 'Webhook has been deleted', 'simple-membership' );
				return $ret;
			} else {
				//Error occurred during webhook deletion.
				$ret['success'] = false;
				$ret['msg'] = $response->get_error_message() . ': ' . json_encode( $response->get_error_data() );
				return $ret;
			}
			return $ret;
		}

		// Webhook does not exist. Nothing to delete.
		$ret = array();
		$ret['success'] = false;
		$ret['msg'] = __( 'No webhook found. Nothing to delete.', 'simple-membership' );
		return $ret;	
	}

	/**
	 * Check and create webhooks for both modes (live and sandbox).
	 * This function is specfic to the plugin in question and how it plans to create the webhooks.
	 */
	public function check_and_create_webhooks_for_both_modes() {
		//First, handle the live/production mode webhook.
		if( !empty($this->live_client_id) && !empty($this->live_secret) ){
			$this->set_mode_and_api_creds_for_webhook( 'production', $this->live_client_id, $this->live_secret );
			$ret = $this->check_and_create_webhook();
			if( isset( $ret['status']) && $ret['status'] == 'no' ){
				//Webhook creation failed. 
				SwpmLog::log_simple_debug( 'Webhook creation failed for live mode. Error: ' . $ret['msg'], true );
			}
		} else {
			//Live mode credentials are not set. We will show a notice to the admin using admin_notice hook.
		}

		//Next, handle the sandbox mode webhook.
		if( !empty($this->sandbox_client_id) && !empty($this->sandbox_secret) ){
			$this->set_mode_and_api_creds_for_webhook( 'sandbox', $this->sandbox_client_id, $this->sandbox_secret );
			$ret = $this->check_and_create_webhook();
			if( isset( $ret['status']) && $ret['status'] == 'no' ){
				//Webhook creation failed. 
				SwpmLog::log_simple_debug( 'Webhook creation failed for live mode. Error: ' . $ret['msg'], true );
			}			
		} else {
			//Sandbox mode credentials are not set. We will show a notice to the admin using admin_notice hook.
		}
	}

}
