<?php

/* * ************************************************
 * Stripe Buy Now button shortcode handler
 * *********************************************** */
add_filter('swpm_payment_button_shortcode_for_stripe_buy_now', 'swpm_render_stripe_buy_now_button_sc_output', 10, 2);

function swpm_render_stripe_buy_now_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? $args['id'] : '';
    if (empty($button_id)) {
        return '<p class="swpm-red-box">Error! swpm_render_stripe_buy_now_button_sc_output() function requires the button ID value to be passed to it.</p>';
    }

    //Check new_window parameter
    $window_target = isset($args['new_window']) ? 'target="_blank"' : '';
    $button_text = (isset($args['button_text'])) ? $args['button_text'] : SwpmUtils::_('Buy Now');
    $billing_address = isset($args['billing_address']) ? '1' : '';;//By default don't show the billing address in the checkout form.
    $item_logo = '';//Can be used to show an item logo or thumbnail in the checkout form.

    $settings = SwpmSettings::get_instance();
    $button_cpt = get_post($button_id); //Retrieve the CPT for this button
    $item_name = htmlspecialchars($button_cpt->post_title);

    $membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
    //Verify that this membership level exists (to prevent user paying for a level that has been deleted)
    if(!SwpmUtils::membership_level_id_exists($membership_level_id)){
        return '<p class="swpm-red-box">Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.</p>';
    }
    
    //Payment amount and currency
    $payment_amount = get_post_meta($button_id, 'payment_amount', true);
    if (!is_numeric($payment_amount)) {
        return '<p class="swpm-red-box">Error! The payment amount value of the button must be a numeric number. Example: 49.50 </p>';
    }
    $payment_amount = round($payment_amount, 2); //round the amount to 2 decimal place.
    $price_in_cents = $payment_amount * 100 ;//The amount (in cents). This value is passed to Stripe API.
    $payment_currency = get_post_meta($button_id, 'payment_currency', true);
   
    //Return, cancel, notifiy URLs
    $return_url = get_post_meta($button_id, 'return_url', true);
    if (empty($return_url)) {
        $return_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
    }
    $notify_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_buy_now=1';//We are going to use it to do post payment processing.
    //$button_image_url = get_post_meta($button_id, 'button_image_url', true);//Stripe doesn't currenty support button image for their standard checkout.

    //User's IP address
    $user_ip = SwpmUtils::get_user_ip_address();
    $_SESSION['swpm_payment_button_interaction'] = $user_ip;
    
    //Custom field data
    $custom_field_value = 'subsc_ref=' . $membership_level_id;    
    $custom_field_value .= '&user_ip=' . $user_ip;
    if (SwpmMemberUtils::is_member_logged_in()) {
        $custom_field_value .= '&swpm_id=' . SwpmMemberUtils::get_logged_in_members_id();
    }
    $custom_field_value = apply_filters('swpm_custom_field_value_filter', $custom_field_value);
    
    //Sandbox settings
    $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
    
    //API keys
    $stripe_test_secret_key = get_post_meta($button_id, 'stripe_test_secret_key', true);
    $stripe_test_publishable_key = get_post_meta($button_id, 'stripe_test_publishable_key', true);
    $stripe_live_secret_key = get_post_meta($button_id, 'stripe_live_secret_key', true);
    $stripe_live_publishable_key = get_post_meta($button_id, 'stripe_live_publishable_key', true);
    if($sandbox_enabled){
        $publishable_key = $stripe_test_publishable_key;//Use sandbox API key
    } else {
        $publishable_key = $stripe_live_publishable_key;//Use live API key
    }
    
    /* === Stripe Buy Now Button Form === */
    $output = '';
    $output .= '<div class="swpm-button-wrapper swpm-stripe-buy-now-wrapper">';
    $output .= "<form action='" . $notify_url . "' METHOD='POST'> ";
    $output .= "<script src='https://checkout.stripe.com/checkout.js' class='stripe-button'
        data-key='".$publishable_key."'
        data-panel-label='Pay'
        data-amount='{$price_in_cents}'
        data-name='{$item_name}'";
    $output .= "data-description='{$payment_amount} {$payment_currency}'";
    $output .= "data-label='{$button_text}'";//Stripe doesn't currenty support button image for their standard checkout.
    $output .= "data-currency='{$payment_currency}'";
    if(!empty($item_logo)){//Show item logo/thumbnail in the stripe payment window
        $output .= "data-image='{$item_logo}'";
    }        
    if(!empty($billing_address)){//Show billing address in the stipe payment window
        $output .= "data-billingAddress='true'";
    }
    $output .= apply_filters('swpm_stripe_additional_checkout_data_parameters', '');//Filter to allow the addition of extra data parameters for stripe checkout.
    $output .="></script>";

    $output .= wp_nonce_field('stripe_payments', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="item_number" value="' . $button_id . '" />';
    $output .= "<input type='hidden' value='{$item_name}' name='item_name' />";
    $output .= "<input type='hidden' value='{$payment_amount}' name='item_price' />";
    $output .= "<input type='hidden' value='{$payment_currency}' name='currency_code' />";
    $output .= "<input type='hidden' value='{$custom_field_value}' name='custom' />";
    
    //Filter to add additional payment input fields to the form.
    $output .= apply_filters('swpm_stripe_payment_form_additional_fields', '');
    
    $output .= "</form>";
    $output .= '</div>'; //End .swpm_button_wrapper

    return $output;
}
