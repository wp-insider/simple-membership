<?php

//Includes
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-request-api.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-request-api-injector.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-js-button-embed.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-subsc-billing-plan.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-webhook.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-webhook-event-handler.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-utility-functions.php' );//Misc project specific utility functions.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-utility-ipn-related.php' );//Misc IPN related utility functions.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-cache.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-bearer.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-button-ajax-handler.php' );//Standard button related ajax handler.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-button-sub-ajax-handler.php' );//Subscription button related ajax handler.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-acdc-related.php' );

//Onboarding related includes
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/onboarding-related/class-swpm-paypal-onboarding.php' );//PPCP Onboarding related functions.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/onboarding-related/class-swpm-paypal-onboarding-serverside.php' );//PPCP Onboarding serverside helper.

/**
 * The Main class to handle the new PayPal library related tasks. 
 * It initializes when this file is inlcuded.
 */
class SWPM_PayPal_Main {

	public static $api_base_url_production = 'https://api-m.paypal.com';	
	public static $api_base_url_sandbox = 'https://api-m.sandbox.paypal.com';
	public static $signup_url_production = 'https://www.paypal.com/bizsignup/partner/entry';	
	public static $signup_url_sandbox = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';	
	public static $partner_id_production = '3FWGC6LFTMTUG';//Same as the partner's merchant id of the live account.
	public static $partner_id_sandbox = '47CBLN36AR4Q4';// Same as the merchant id of the platform app sandbox account.
	public static $partner_client_id_production = 'AWo6ovbrHzKZ3hHFJ7APISP4MDTjes-rJPrIgyFyKmbH-i8iaWQpmmaV5hyR21m-I6f_APG6n2rkZbmR'; //Platform app's client id.
	public static $partner_client_id_sandbox = 'AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX';

	public static $pp_api_connection_settings_menu_page = 'admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_api';


    public function __construct() {

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'swpm_paypal_webhook_event' && isset( $_GET['mode'] )) {
			//Register action (to handle webhook) only on our webhook notification URL.
			new SWPM_PayPal_Webhook_Event_Handler();
		}

		//Initialize the PayPal Ajax Create and Capture Order Class so it can handle the ajax request(s).
		new SWPM_PayPal_Button_Ajax_Hander();

		//Initialize the PayPal Subscription Button Related Ajax Class so it can handle the ajax request(s).
		new SWPM_PayPal_Button_Sub_Ajax_Hander();

		//Initialize the PayPal ACDC related class so it can handle the ajax request(s).
		new SWPM_PayPal_ACDC_Related();

		//Initialize the PayPal onboarding serverside class so it can handle the 'onboardedCallback' ajax request.
		new SWPM_PayPal_PPCP_Onboarding_Serverside();	
		
    }

}

new SWPM_PayPal_Main();
