<?php

class SwpmStripeBuyNowIpnHandler {
    
    public function __construct() {
        
        $this->handle_stripe_ipn();
    }
    
    public function handle_stripe_ipn(){
        SwpmLog::log_simple_debug("Stripe Buy Now IPN received. Processing request...", true);
        //SwpmLog::log_simple_debug(print_r($_REQUEST, true), true);//Useful for debugging purpose
        
        //Include the Stripe library.
        include(SIMPLE_WP_MEMBERSHIP_PATH . 'lib/stripe-gateway/init.php');
        
        //Read and sanitize the request parameters.
        $button_id = sanitize_text_field($_REQUEST['item_number']);
        $button_title = sanitize_text_field($_REQUEST['item_name']);
        $payment_amount = sanitize_text_field($_REQUEST['item_price']);
        $price_in_cents = $payment_amount * 100 ;//The amount (in cents). This value is used in Stripe API.
        $currency_code = sanitize_text_field($_REQUEST['currency_code']);
        
        $stripe_token = sanitize_text_field($_POST['stripeToken']);
        $stripe_token_type = sanitize_text_field($_POST['stripeTokenType']);
        $stripe_email = sanitize_email($_POST['stripeEmail']);
        
        //Retrieve the CPT for this button
        $button_cpt = get_post($button_id); 
        if(!$button_cpt){
            //Fatal error. Could not find this payment button post object.
            SwpmLog::log_simple_debug("Fatal Error! Failed to retrieve the payment button post object for the given button ID: ". $button_id, false);
            wp_die("Fatal Error! Payment button (ID: ".$button_id.") does not exist. This request will fail.");
        }
        
        //Validate and verify some of the main values.
        $true_payment_amount = get_post_meta($button_id, 'payment_amount', true);
        if( $payment_amount != $true_payment_amount ) {
            //Fatal error. Payment amount may have been tampered with.
            $error_msg = 'Fatal Error! Received payment amount ('.$payment_amount.') does not match with the original amount ('.$true_payment_amount.')';
            SwpmLog::log_simple_debug($error_msg, false);
            wp_die($error_msg);
        }
        $true_currency_code = get_post_meta($button_id, 'payment_currency', true);
        if( $currency_code != $true_currency_code ) {
            //Fatal error. Currency code may have been tampered with.
            $error_msg = 'Fatal Error! Received currency code ('.$currency_code.') does not match with the original code ('.$true_currency_code.')';
            SwpmLog::log_simple_debug($error_msg, false);
            wp_die($error_msg);
        }
        
        //Validation passed. Go ahead with the charge.
        
        //Sandbox and other settings
        $settings = SwpmSettings::get_instance();
        $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
        if($sandbox_enabled){
            SwpmLog::log_simple_debug("Sandbox payment mode is enabled. Using test API key details.", true);
            $secret_key = get_post_meta($button_id, 'stripe_test_secret_key', true);;//Use sandbox API key
        } else {
            $secret_key = get_post_meta($button_id, 'stripe_live_secret_key', true);;//Use live API key
        }

        //Set secret API key in the Stripe library
        \Stripe\Stripe::setApiKey($secret_key);
        
        // Get the credit card details submitted by the form
        $token = $stripe_token;
        
        // Create the charge on Stripe's servers - this will charge the user's card
        try {
            $charge = \Stripe\Charge::create(array(
            "amount" => $price_in_cents, //Amount in cents
            "currency" => strtolower($currency_code),
            "source" => $token,
            "description" => $button_title,
        ));
        } catch(\Stripe\Error\Card $e) {
            // The card has been declined
            SwpmLog::log_simple_debug("Stripe Charge Error! The card has been declined. ".$e->getMessage(), false);
            $body = $e->getJsonBody();
            $error  = $body['error'];
            $error_string = print_r($error,true);
            SwpmLog::log_simple_debug("Error details: ".$error_string, false);
            wp_die("Stripe Charge Error! Card charge has been declined. " . $e->getMessage() . $error_string);
        }

        //Everything went ahead smoothly with the charge.
        SwpmLog::log_simple_debug("Stripe Buy Now charge successful.", true);
        
        
        //********************************************************************
        //TODO Create the $ipn_data array then call the following function
        //swpm_handle_subsc_signup_stand_alone($ipn_data, $subsc_ref, $unique_ref, $swpm_id = '')
        
        SwpmLog::log_simple_debug("End of Stripe Buy Now IPN processing.", true, true);
        exit;//TODO - remove me with a redirection here.
    }
}

$swpm_stripe_buy_ipn = new SwpmStripeBuyNowIpnHandler();
