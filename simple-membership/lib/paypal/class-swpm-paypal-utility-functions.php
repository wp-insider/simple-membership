<?php

class SWPM_PayPal_Utility_Functions{
    
    public static function create_product_params_from_button( $button_id ){
        $button_name = get_the_title( $button_id );
        $product_params = array(
            'name' => $button_name,
            'type' => 'DIGITAL',
        );
        return $product_params;
    }

    public static function create_subscription_args_from_button( $button_id ){
        $subsc_args = array(
            'currency' => get_post_meta($button_id, 'payment_currency', true),
            'sub_trial_price' => get_post_meta($button_id, 'trial_billing_amount', true),            
            'sub_trial_period' => get_post_meta($button_id, 'trial_billing_cycle', true),
            'sub_trial_period_type' => get_post_meta($button_id, 'trial_billing_cycle_term', true),
            'sub_recur_price' => get_post_meta($button_id, 'recurring_billing_amount', true),            
            'sub_recur_period' => get_post_meta($button_id, 'recurring_billing_cycle', true),
            'sub_recur_period_type' => get_post_meta($button_id, 'recurring_billing_cycle_term', true),
            'sub_recur_count' => get_post_meta($button_id, 'recurring_billing_cycle_count', true),
            'sub_recur_reattemp' => get_post_meta($button_id, 'recurring_billing_reattempt', true),
        );
        return $subsc_args;
    }

    /**
     * Checks if the plan details (core subscription plan values) in new form submission have changed for the given button ID.
     */
    public static function has_plan_details_changed_for_button( $button_id ){
		$plan_details_changed = false;
        $core_plan_fields = array(
            'payment_currency' => trim(sanitize_text_field($_REQUEST['payment_currency'])),
            'recurring_billing_amount' => trim(sanitize_text_field($_REQUEST['recurring_billing_amount'])),
            'recurring_billing_cycle' => trim(sanitize_text_field($_REQUEST['recurring_billing_cycle'])),
            'recurring_billing_cycle_term' => trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_term'])),
            'recurring_billing_cycle_count' => trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_count'])),
            'trial_billing_amount' => trim(sanitize_text_field($_REQUEST['trial_billing_amount'])),
            'trial_billing_cycle' => trim(sanitize_text_field($_REQUEST['trial_billing_cycle'])),
            'trial_billing_cycle_term' => trim(sanitize_text_field($_REQUEST['trial_billing_cycle_term'])),
        );
		foreach ( $core_plan_fields as $meta_name => $value ) {
            $old_value = get_post_meta( $button_id, $meta_name, true );
            if ( $old_value !== $value ) {
                $plan_details_changed = true;
            }
		}
		return $plan_details_changed;
    }

    /**
     * Checks if a billling plan exists for the given button ID. If not, it creates a new billing plan in PayPal. 
     * Returns the billing plan ID in an array.
     * @param mixed $button_id
     * @return array
     */
    public static function create_billing_plan_for_button( $button_id ){
        $output = "";
		$ret = array();
        $plan_id = get_post_meta($button_id, 'pp_subscription_plan_id', true);
        if ( empty ( $plan_id )){
            //Billing plan doesn't exist. Need to create a new billing plan in PayPal.
            $product_params = self::create_product_params_from_button( $button_id );         
            $subsc_args = self::create_subscription_args_from_button( $button_id );

            //Setup the PayPal API Injector class. This class is used to do certain premade API queries.
            $pp_api_injector = new SWPM_PayPal_Request_API_Injector();
			$paypal_req_api = $pp_api_injector->get_paypal_req_api();
            $paypal_mode = $paypal_req_api->get_api_environment_mode();
            // Debugging
            // echo '<pre>';
            // var_dump($paypal_req_api);
            // echo '</pre>';

            $plan_id = $pp_api_injector->create_product_and_billing_plan($product_params, $subsc_args);
            if ( $plan_id !== false ) {
                //Plan created successfully. Save the plan ID for future reference.
                update_post_meta($button_id, 'pp_subscription_plan_id', $plan_id);
                update_post_meta($button_id, 'pp_subscription_plan_mode', $paypal_mode);

                $ret['success'] = true;
                $ret['plan_id'] = $plan_id;
				$ret['output'] = $output;
				return $ret;
            } else {
                //Plan creation failed. Show an error message.
                $last_error = $paypal_req_api->get_last_error();
                $error_message = isset($last_error['error_message']) ? $last_error['error_message'] : '';
                $output .= '<div class="swpm-paypal-api-error-msg">';
                $output .= '<p>Error! Failed to create a subscription billing plan in your PayPal account. The following error message was returned from the PayPal API.</p>';
                $output .= '<p>Error Message: ' . esc_attr($error_message) . '</p>';
                $output .= '</div>';

                $ret['success'] = false;
                $ret['plan_id'] = '';
                $ret['error_message'] = $error_message;
                $ret['output'] = $output;
                return $ret;
            }
        }
        $ret['success'] = true;
        $ret['plan_id'] = $plan_id;
        $ret['output'] = $output;
		return $ret;
    }

    public static function check_billing_plan_exists( $plan_id ){
        //Setup the PayPal API Injector class. This class is used to do certain premade API queries.
        $pp_api_injector = new SWPM_PayPal_Request_API_Injector();

        //Use the "Show plan details" API call to verify that the plan exists for the given account and mode.
        https://developer.paypal.com/docs/api/subscriptions/v1/#plans_get
        $plan_details = $pp_api_injector->get_paypal_billing_plan_details( $plan_id );
        if( $plan_details !== false ){
            //Plan exists. Return true.
            return true;
        }
        
        $paypal_req_api = $pp_api_injector->get_paypal_req_api();
        $paypal_mode = $paypal_req_api->get_api_environment_mode();

        Swpmlog::log_simple_debug( "Billing plan with ID: ". $plan_id . " does not exist in PayPal. The check was done in mode: ".$paypal_mode.". Maybe the plan was originally created in a different environment mode.", true );
		return false;
    }

    /**
     * Checks if a webhook already exists for this site for BOTH sandbox and production modes. If one doesn't exist, create one.
     */
	public static function check_and_create_webhooks_for_this_site() {
		$pp_webhook = new SWPM_PayPal_Webhook();
		$pp_webhook->check_and_create_webhooks_for_both_modes();    
	}

    public static function check_current_mode_and_set_notice_if_webhook_not_set() {
        //Check if the current mode is sandbox or production. Then check if a webhook is set for that mode. 
        //If not, show a notice to the admin user by using the admin_notice hook.

        //TODO - need to finilaize this.
        //update_option( "swpm_show_{$mode}_webhook_notice", 'no' === $ret[ $mode ]['status'] );
        //Check the following code for example:
        //add_action( 'admin_notices', array( $this, 'show_webhooks_admin_notice' ) );
    }

}