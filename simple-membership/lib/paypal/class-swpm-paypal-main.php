<?php

//Includes
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-request-api.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-request-api-injector.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-js-button-embed.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-subsc-billing-plan.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-webhook.php' );

/**
 * The Main class to handle the new PayPal library related tasks. 
 * It initializes when this file is inlcuded.
 */
class SWPM_PayPal_Main {

    public function __construct() {
        add_action( 'wp_loaded', array(&$this, 'handle_paypal_webhook') );
    }

    function handle_paypal_webhook(){
        //TODO 
        //Handle PayPal Webhook
    }
}

new SWPM_PayPal_Main();
