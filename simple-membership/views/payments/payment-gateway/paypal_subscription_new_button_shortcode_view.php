<?php

/**************************************************
 * PayPal Subscription New button shortcode handler
 *************************************************/
add_filter('swpm_payment_button_shortcode_for_pp_subscription_new', 'swpm_render_pp_subscription_new_button_sc_output', 10, 2);

function swpm_render_pp_subscription_new_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? $args['id'] : '';
    if (empty($button_id)) {
        return '<p class="swpm-red-box">Error! swpm_render_pp_subscription_new_button_sc_output() function requires the button ID value to be passed to it.</p>';
    }

    /*****************************************
     * Settings and Button Specific Configuration
     *****************************************/    
    $settings = SwpmSettings::get_instance();
    $live_client_id = $settings->get_value('paypal-live-client-id');
    $sandbox_client_id = $settings->get_value('paypal-sandbox-client-id');
    $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
    $is_live_mode = $sandbox_enabled ? 0 : 1;

	$currency = get_post_meta( $button_id, 'payment_currency', true );

	$disable_funding_card = get_post_meta( $button_id, 'pp_subscription_new_disable_funding_card', true );
    $disable_funding_credit = get_post_meta( $button_id, 'pp_subscription_new_disable_funding_credit', true );
    $disable_funding_venmo = get_post_meta( $button_id, 'pp_subscription_new_disable_funding_venmo', true );
    $disable_funding = array();
    if( !empty($disable_funding_card)){
        $disable_funding[] = 'card';
    }
    if( !empty($disable_funding_credit)){
        $disable_funding[] = 'credit';
    }
    if( !empty($disable_funding_venmo)){
        $disable_funding[] = 'venmo';
    }

	$btn_type = get_post_meta($button_id, 'pp_subscription_new_btn_type', true);
    $btn_shape = get_post_meta($button_id, 'pp_subscription_new_btn_shape', true);
    $btn_layout = get_post_meta($button_id, 'pp_subscription_new_btn_layout', true);
    $btn_color = get_post_meta($button_id, 'pp_subscription_new_btn_color', true);

    $btn_width = get_post_meta($button_id, 'pp_subscription_new_btn_width', true);
    $btn_height = get_post_meta($button_id, 'pp_subscription_new_btn_height', true);
    $btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
    $btn_height = isset( $btn_sizes[ $btn_height ] ) ? $btn_sizes[ $btn_height ] : 35;

    $return_url = get_post_meta($button_id, 'return_url', true);
    $txn_success_message = __('Transaction completed successfully!', 'simple-membership');

    /**********************
     * PayPal Plan ID.
     **********************/
    //Get the plan ID (or create a new plan if needed) for the button.
	$plan_id = get_post_meta( $button_id, 'pp_subscription_plan_id', true );
    if( empty( $plan_id )){
        //Need to create a new plan
        $ret = SWPM_PayPal_Utility_Functions::create_billing_plan_for_button( $button_id );
        if( $ret['success'] === true ){
            $plan_id = $ret['plan_id'];
            SwpmLog::log_simple_debug( 'Created new PayPal subscription plan for button ID: ' . $button_id . ', Plan ID: ' . $plan_id, true );
        } else {
			$error_msg = '<p class="swpm-red-box">Error! Could not create the PayPal subscription plan for the button. Error message: ' . esc_attr( $ret['error_message'] ) . '</p>';
            return $error_msg;
        }
    } else {
        //Check if this plan exists in the PayPal account.
        if( !SWPM_PayPal_Utility_Functions::check_billing_plan_exists( $plan_id ) ){
            //The plan ID does not exist in the PayPal account. Maybe the plan was created earlier in a different mode. We need to create a new plan.
            $ret = SWPM_PayPal_Utility_Functions::create_billing_plan_for_button( $button_id );
            if( $ret['success'] === true ){
                $plan_id = $ret['plan_id'];
                SwpmLog::log_simple_debug( 'Created new PayPal subscription plan for button ID: ' . $button_id . ', Plan ID: ' . $plan_id, true );
            } else {
                $error_msg = '<p class="swpm-red-box">Error! Could not create the PayPal subscription plan for the button. Error message: ' . esc_attr( $ret['error_message'] ) . '</p>';
                return $error_msg;
            }            
        }
    }

    /**********************
     * PayPal SDK Settings
     **********************/
    //Configure the paypal SDK settings and enqueue the code for SDK loading.
    $settings_args = array(
        'is_live_mode' => $is_live_mode,
        'live_client_id' => $live_client_id,
        'sandbox_client_id' => $sandbox_client_id,
        'currency' => $currency,
        'disable-funding' => $disable_funding, /*array('card', 'credit', 'venmo'),*/
        'is_subscription' => 1,
    );

    $pp_js_button = SWPM_PayPal_JS_Button_Embed::get_instance();
    $pp_js_button->set_settings_args($settings_args);//Set the settings args that will be used to load the JS SDK.
    
    add_action( 'wp_footer', array($pp_js_button, 'load_paypal_sdk') );//Load the JS SDK on footer (so it only loads once per page)

    //The on page embed button id is used to identify the button on the page. Useful when there are multiple buttons (of the same item/product) on the same page.
    $on_page_embed_button_id = $pp_js_button->get_next_button_id();

    $output = '';
    ob_start();
    ?>
    <!-- Test using load of JS code on script loaded event trigger -->
    <div id="<?php echo esc_attr($on_page_embed_button_id); ?>" style="width: <?php echo esc_attr($btn_width); ?>px;"></div><!-- PayPal button container where the button will be rendered -->

    <script type="text/javascript">
    jQuery( function( $ ) {
        $( document ).on( "swpm_paypal_sdk_loaded", function() { 
            //Anything that goes here will only be executed after the PayPal SDK is loaded.
            //console.log( 'Rendering JS of Button ID: ' + $on_page_embed_button_id );

            const paypalButtonsComponent = paypal.Buttons({
                // optional styling for buttons
                // https://developer.paypal.com/docs/checkout/standard/customize/buttons-style-guide/
                style: {
                    color: "<?php echo esc_js($btn_color); ?>",
                    shape: "<?php echo esc_js($btn_shape); ?>",
                    height: <?php echo esc_js($btn_height); ?>,
                    label: "<?php echo esc_js($btn_type); ?>",
                    layout: "<?php echo esc_js($btn_layout); ?>",
                },
    
                // set up the recurring transaction
                createSubscription: (data, actions) => {
                    // replace with your subscription plan id
                    // https://developer.paypal.com/docs/subscriptions/#link-createplan
                    return actions.subscription.create({
                        plan_id: "<?php echo $plan_id; ?>"
                    });
                },
    
                // notify the buyer that the subscription is successful
                onApprove: (data, actions) => {
                    console.log('You have successfully created subscription');
                    console.log(data);

                    //alert(JSON.stringify(data));
                    //order_id = data.orderID;
                    //subscription_id = data.subscriptionID;
                    console.log('Order ID: ' + data.orderID);
                    console.log('Subscription ID: ' + data.subscriptionID);//Use this to retrieve subscripiton details from API


                    return_url = "<?php echo esc_url_raw($return_url); ?>";
                    if( return_url ){
                        //redirect to the Thank you page URL.
                        console.log('Redirecting to the Thank you page URL: ' + return_url);
                        window.location.href = return_url;
                    } else {
                        //No return URL is set. Just show a success message.
                        txn_success_msg = "<?php echo esc_attr($txn_success_message); ?>";
                        alert(txn_success_msg);
                    }
                },
    
                // handle unrecoverable errors
                onError: (err) => {
                    console.error('An error prevented the user from checking out with PayPal');
                    alert('An error prevented the user from checking out with PayPal');
                }
            });
    
            paypalButtonsComponent
                .render("#<?php echo $on_page_embed_button_id; ?>")
                .catch((err) => {
                    console.error('PayPal Buttons failed to render');
                });

            });
        });
    </script>

    <?php
    $output .= ob_get_clean();

    return $output;
}
