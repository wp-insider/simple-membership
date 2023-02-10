<?php

/**
 * This clcass handles the ajax request from the PayPal OnApprove event (the onApprove event is triggered from the Button's JS code on successful transaction). 
 * It creates the required $ipn_data array from the transaction so it can be fed into the existing IPN handler functions easily.
 */
class SWPM_PayPal_OnApprove_IPN_Handler {

	public function __construct() {
		//Handle it at 'wp_loaded' since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the onApprove ajax request for 'Buy Now' type buttons
		// add_action( 'wp_ajax_swpm_onapprove_create_order', array(&$this, 'swpm_onapprove_create_order' ) );
		// add_action( 'wp_ajax_nopriv_swpm_onapprove_create_order', array(&$this, 'swpm_onapprove_create_order' ) );

		//Handle the onApprove ajax request for 'Subscription' type buttons
		add_action( 'wp_ajax_swpm_onapprove_create_subscription', array(&$this, 'swpm_onapprove_create_subscription' ) );
		add_action( 'wp_ajax_nopriv_swpm_onapprove_create_subscription', array(&$this, 'swpm_onapprove_create_subscription' ) );
	}

    public function swpm_onapprove_create_subscription(){

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
		SwpmLog::log_array_data_to_debug( $data, true );//TODO: Remove this line after testing

		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		SwpmLog::log_simple_debug( 'OnApprove ajax request received. On Page Button ID: ' . $on_page_button_id, true );

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

		//Get the transaction data from the request
		$txn_data = isset( $_POST['txn_data'] ) ? stripslashes_deep( $_POST['txn_data'] ) : array();
		if ( empty( $txn_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty transaction data received.', 'simple-membership' ),
				)
			);
		}
		SwpmLog::log_array_data_to_debug( $txn_data, true );//TODO: Remove this line after testing


		//Create the IPN data array from the transaction data.
		$ipn_data = $this->create_ipn_data_array_from_txn_data( $data );
		
		//TODO: Validate the IPN data array
		//TODO: Process the IPN data array


		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true ) );
		exit;
    }

	public function create_ipn_data_array_from_txn_data( $data ) {

	}

}
