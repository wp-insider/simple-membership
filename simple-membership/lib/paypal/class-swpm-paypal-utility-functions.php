<?php

class SWPM_PayPal_Utility_Functions{
    
    public static function get_api_environment_mode_from_settings(){
        $settings = SwpmSettings::get_instance();
        $sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );//The value will be checked="checked" or empty string.
        if( !empty( $sandbox_enabled ) ){
            $environment_mode = 'sandbox';
        }else{
            $environment_mode = 'production';
        }
        return $environment_mode;
    }

    public static function validate_paypal_client_id_settings( $settings_args ) {
        //Validates PayPal client ID settings based on the selected mode (live or sandbox).
        $is_live_mode = !empty($settings_args['is_live_mode']);
        $live_client_id = isset($settings_args['live_client_id']) ? trim($settings_args['live_client_id']) : '';
        $sandbox_client_id = isset($settings_args['sandbox_client_id']) ? trim($settings_args['sandbox_client_id']) : '';

        if ( $is_live_mode && empty($live_client_id) ) {
            return 'PayPal Live Client ID is missing in the settings while operating in live mode.';
        }

        if ( !$is_live_mode && empty($sandbox_client_id) ) {
            return 'PayPal Sandbox Client ID is missing in the settings while operating in sandbox mode.';
        }

        // No error
        return ''; 
    }

	public static function get_api_base_url_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return SWPM_PayPal_Main::$api_base_url_production;
		} else {
			return SWPM_PayPal_Main::$api_base_url_sandbox;
		}
	}

	public static function get_signup_url_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return SWPM_PayPal_Main::$signup_url_production;
		} else {
			return SWPM_PayPal_Main::$signup_url_sandbox;
		}
	}    
	public static function get_partner_id_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return SWPM_PayPal_Main::$partner_id_production;
		} else {
			return SWPM_PayPal_Main::$partner_id_sandbox;
		}
	}

    public static function get_partner_client_id_by_environment_mode( $environment_mode = 'production' ) {
        if ($environment_mode == 'production') {
            return SWPM_PayPal_Main::$partner_client_id_production;
        } else {
            return SWPM_PayPal_Main::$partner_client_id_sandbox;
        }
    }

    /**
     * Gets the seller merchant ID (payer ID) by environment mode. 
     * Used in the PayPal API calls (for setting PayPal-Auth-Assertion header value)
     */
    public static function get_seller_merchant_id_by_environment_mode( $environment_mode = 'production' ) {
        $settings = SwpmSettings::get_instance();
        $seller_merchant_id = '';

        if ($environment_mode == 'production') {
            $seller_merchant_id = $settings->get_value('paypal-live-seller-merchant-id');
        } else {
            $seller_merchant_id = $settings->get_value('paypal-sandbox-seller-merchant-id');
        }
        return $seller_merchant_id;
    }

    public static function get_seller_client_id_by_environment_mode( $environment_mode = 'production' ) {
        $settings = SwpmSettings::get_instance();
        $seller_client_id = '';

        if ($environment_mode == 'production') {
            $seller_client_id = $settings->get_value('paypal-live-client-id');
        } else {
            $seller_client_id = $settings->get_value('paypal-sandbox-client-id');
        }
        return $seller_client_id;
    }

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
     * Force creates a new billing plan for the button (the paypal account connection or the mode may have changed)
     */
    public static function create_billing_plan_fresh_new( $button_id ){
        //Reset any plan ID that may be saved for this button. 
        //We need to create completely new plan (using the current PayPal account and mode)
        update_post_meta($button_id, 'pp_subscription_plan_id', '');
        update_post_meta($button_id, 'pp_subscription_plan_mode', '');

        $ret = array();
        $ret = self::create_billing_plan_for_button( $button_id );
        return $ret;
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

        Swpmlog::log_simple_debug( "Billing plan with ID: ". $plan_id . " does not exist in PayPal. The check was done in mode: ".$paypal_mode.". Maybe the plan was originally created in a different environment mode or account.", true );
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

	/**
	 * Normalizes a PayPal amount (example: '10.0' and '10.00' both give '10'), so two amounts can be compared exactly.
	 * Returns false when the value is not a decimal number of zero or more.
	 */
	public static function normalize_amount( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/\A[0-9]+(?:\.[0-9]+)?\z/', $value ) ) {
			return false;
		}
		if ( strpos( $value, '.' ) !== false ) {
			$value = rtrim( rtrim( $value, '0' ), '.' );
		}
		$value = ltrim( $value, '0' );
		if ( $value === '' || $value[0] === '.' ) {
			$value = '0' . $value;
		}
		return $value;
	}

	/**
	 * Converts a PayPal time (example: '2026-11-02T03:01:53Z') to a Unix timestamp, so two times can be compared exactly.
	 * Fractions of a second are only accepted when they are zero (example: '.000Z'). Returns false for any other value.
	 */
	public static function get_timestamp_from_paypal_time( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/\A(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.(\d+))?(?:Z|([+-])(\d{2}):(\d{2}))\z/', $value, $m ) ) {
			return false;
		}
		if ( isset( $m[7] ) && $m[7] !== '' && trim( $m[7], '0' ) !== '' ) {
			return false;
		}
		$year = (int) $m[1];
		$month = (int) $m[2];
		$day = (int) $m[3];
		$hour = (int) $m[4];
		$minute = (int) $m[5];
		$second = (int) $m[6];
		if ( ! checkdate( $month, $day, $year ) || $hour > 23 || $minute > 59 || $second > 59 ) {
			return false;
		}
		$offset = 0;
		if ( isset( $m[8] ) && $m[8] !== '' ) {
			$offset_hours = (int) $m[9];
			$offset_minutes = (int) $m[10];
			if ( $offset_hours > 23 || $offset_minutes > 59 ) {
				return false;
			}
			$offset = ( $offset_hours * 3600 + $offset_minutes * 60 ) * ( $m[8] === '-' ? -1 : 1 );
		}
		return gmmktime( $hour, $minute, $second, $month, $day, $year ) - $offset;
	}

	/**
	 * Gets the subscription agreement record (txn_type 'pp_subscription_new') of a PayPal subscription checkout.
	 * Found by the transaction type, not by the status (the status is saved translated).
	 * $mode: 'production' or 'sandbox' (the environment of the verified webhook). The record must be for that environment.
	 * Returns the record's post ID, or 0 when there is no record, more than one, it is for the other environment, or the mode is not valid.
	 */
	public static function get_subscription_checkout_agreement_record_id( $subscr_id, $mode ) {
		if ( empty( $subscr_id ) || ! in_array( $mode, array( 'production', 'sandbox' ), true ) ) {
			return 0;
		}
		$record_ids = get_posts( array(
			'post_type' => 'swpm_transactions',
			'posts_per_page' => 2,
			'fields' => 'ids',
			'meta_query' => array(
				array( 'key' => 'subscr_id', 'value' => $subscr_id, 'compare' => '=' ),
				array( 'key' => 'txn_type', 'value' => 'pp_subscription_new', 'compare' => '=' ),
			),
		) );
		if ( count( $record_ids ) !== 1 ) {
			return 0;
		}
		$is_live = get_post_meta( $record_ids[0], 'is_live', true );
		if ( $is_live !== ( $mode === 'production' ? 'yes' : 'no' ) ) {
			return 0;
		}
		return (int) $record_ids[0];
	}

	/**
	 * Gets the sale IDs matched to the payment of a PayPal subscription checkout (the 'checkout_sale_id' meta of the agreement record).
	 * Normally zero or one. More than one means a conflict: then none of them can be used.
	 */
	public static function get_checkout_sale_ids( $agreement_record_id ) {
		$sale_ids = get_post_meta( $agreement_record_id, 'checkout_sale_id', false );
		return array_values( array_unique( array_map( 'strval', (array) $sale_ids ) ) );
	}

}