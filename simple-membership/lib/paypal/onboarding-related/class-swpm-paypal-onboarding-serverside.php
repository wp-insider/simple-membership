<?php

/**
 * Handles server side tasks during PPCP onboarding.
 */
class SWPM_PayPal_PPCP_Onboarding_Serverside {

	public function __construct() {

		//Setup AJAX request handler for the onboarding process.
		add_action( 'wp_ajax_swpm_handle_onboarded_callback_data', array(&$this, 'handle_onboarded_callback_data' ) );
		add_action( 'wp_ajax_nopriv_handle_onboarded_callback_data', array(&$this, 'handle_onboarded_callback_data' ) );

	}

	public function handle_onboarded_callback_data(){
		//TODO - Handle the data sent by PayPal after the onboarding process.
		//The get_option('swpm_ppcp_sandbox_connect_query_args') will give you the query args that you sent to the PayPal onboarding page

		SwpmLog::log_simple_debug( 'Received request: handle_onboarded_callback_data.', true );

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

        //TODO - use the data to do the next steps.
        

        SwpmLog::log_simple_debug( 'Succedssfully processed the handle_onboarded_callback_data.', true );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'msg' => 'Succedssfully processed the handle_onboarded_callback_data.' ) );
		exit;

	}


}