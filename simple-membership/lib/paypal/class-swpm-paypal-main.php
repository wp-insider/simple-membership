<?php

//Includes
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-request-api.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-request-api-injector.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-js-button-embed.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-subsc-billing-plan.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-webhook.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-webhook-event-handler.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-onapprove-ipn-handler.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-utility-functions.php' );//Misc project specific utility functions.

/**
 * The Main class to handle the new PayPal library related tasks. 
 * It initializes when this file is inlcuded.
 */
class SWPM_PayPal_Main {

    public function __construct() {

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'swpm_paypal_webhook_event' && isset( $_GET['mode'] )) {
			//Register action (to handle webhook) only on our webhook notification URL.
			new SWPM_PayPal_Webhook_Event_Handler();
		}

		//Initialize the PayPal OnApprove IPN Handler so it can handle the 'onApprove' ajax request.
		new SWPM_PayPal_OnApprove_IPN_Handler();
    }

}

new SWPM_PayPal_Main();
