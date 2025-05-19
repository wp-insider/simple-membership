<?php

/* * ************************************************
 * Braintree Buy Now button shortcode handler
 * *********************************************** */
add_filter('swpm_payment_button_shortcode_for_braintree_buy_now', 'swpm_render_braintree_buy_now_button_sc_output', 10, 2);

function swpm_render_braintree_buy_now_button_sc_output($button_code, $args)
{

    $button_id = isset($args['id']) ? sanitize_text_field($args['id']) : '';
    if (empty($button_id)) {
        return '<p class="swpm-red-box">'.__('Error! swpm_render_braintree_buy_now_button_sc_output() function requires the button ID value to be passed to it.', 'simple-membership').'</p>';
    }

    //Get class option for button styling, set Stripe's default if none specified
    $class = isset($args['class']) ? sanitize_html_class($args['class']) : '';

    //Check new_window parameter
    $window_target = isset($args['new_window']) ? 'target="_blank"' : '';
    $button_text = (isset($args['button_text'])) ? sanitize_text_field($args['button_text']) : __('Buy Now', 'simple-membership');
    $billing_address = isset($args['billing_address']) ? '1' : '';; //By default don't show the billing address in the checkout form.
    $item_logo = ''; //Can be used to show an item logo or thumbnail in the checkout form.

    $settings = SwpmSettings::get_instance();
    $button_cpt = get_post($button_id); //Retrieve the CPT for this button
    $item_name = htmlspecialchars($button_cpt->post_title);

    $membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
    //Verify that this membership level exists (to prevent user paying for a level that has been deleted)
    if (!SwpmUtils::membership_level_id_exists($membership_level_id)) {
        return '<p class="swpm-red-box">'.__('Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.', 'simple-membership').'</p>';
    }

    //Payment amount and currency
    $payment_amount = get_post_meta($button_id, 'payment_amount', true);
    if (!is_numeric($payment_amount)) {
        return '<p class="swpm-red-box">'.__('Error! The payment amount value of the button must be a numeric number. Example: 49.50 ', 'simple-membership').'</p>';
    }
    $payment_amount = round($payment_amount, 2); //round the amount to 2 decimal place.
    $payment_currency = get_post_meta($button_id, 'currency_code', true);

    $payment_amount_formatted = SwpmMiscUtils::format_money($payment_amount, $payment_currency);

    //Return, cancel, notifiy URLs
    $notify_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_braintree_buy_now=1'; //We are going to use it to do post payment processing.
    //$button_image_url = get_post_meta($button_id, 'button_image_url', true);//Stripe doesn't currenty support button image for their standard checkout.
    //User's IP address
    $user_ip = SwpmUtils::get_user_ip_address();
    $_SESSION['swpm_payment_button_interaction'] = $user_ip;

    //Custom field data
    $custom_field_value = 'subsc_ref=' . $membership_level_id;
    $custom_field_value .= '&user_ip=' . $user_ip;
    if (SwpmMemberUtils::is_member_logged_in()) {
        $member_id = SwpmMemberUtils::get_logged_in_members_id();
        $custom_field_value .= '&swpm_id=' . $member_id;
        $member_first_name = SwpmMemberUtils::get_member_field_by_id($member_id, 'first_name');
        $member_last_name = SwpmMemberUtils::get_member_field_by_id($member_id, 'last_name');
        $member_email = SwpmMemberUtils::get_member_field_by_id($member_id, 'email');
    }
    $custom_field_value = apply_filters('swpm_custom_field_value_filter', $custom_field_value);

    //Sandbox settings
    $sandbox_enabled = $settings->get_value('enable-sandbox-testing');

    if ($sandbox_enabled) {
        $braintree_env = "sandbox";
    } else {
        $braintree_env = "production";
    }

    require_once(SIMPLE_WP_MEMBERSHIP_PATH . 'lib/braintree/lib/autoload.php');

    try {
        \Braintree\Configuration::environment($braintree_env);
        \Braintree\Configuration::merchantId(get_post_meta($button_id, 'braintree_merchant_acc_id', true));
        \Braintree\Configuration::publicKey(get_post_meta($button_id, 'braintree_public_key', true));
        \Braintree\Configuration::privateKey(get_post_meta($button_id, 'braintree_private_key', true));
        $clientToken = \Braintree\ClientToken::generate();
    } catch (Exception $e) {
        $e_class = get_class($e);
        $ret = __('Braintree Pay Now button error: ', 'simple-membership') . $e_class;
        if ($e_class == "Braintree\Exception\Authentication")
            $ret .= "<br />" . __("API keys are incorrect. Double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.", 'simple-membership');
        return $ret;
    }

    $uniqid = uniqid(); // Get unique ID to ensure several buttons can be added to one page without conflicts

    /* === Braintree Buy Now Button Form === */
    $output = '';
    $output .= '<div class="swpm-button-wrapper swpm-braintree-buy-now-wrapper">';
    $output .= "<form id='swpm-braintree-payment-form-" . $uniqid . "' action='" . $notify_url . "' METHOD='POST'> ";
    $output .= '<div id="swpm-form-cont-' . $uniqid . '" class="swpm-braintree-form-container swpm-form-container-' . esc_attr($button_id) . '" style="display:none;"></div>';
    $output .= '<div id="swpm-braintree-additional-fields-container-' . $uniqid . '" class="swpm-braintree-additional-fields-container swpm-braintree-additional-fields-container-' . esc_attr($button_id) . '" style="display:none;">';
    $output .= '<p><input type="text" name="first_name" placeholder="First Name" value="' . (isset($member_first_name) ? esc_attr($member_first_name) : '') . '" required></p>';
    $output .= '<p><input type="text" name="last_name" placeholder="Last Name" value="' . (isset($member_last_name) ? esc_attr($member_last_name) : '') . '" required></p>';
    $output .= '<p><input type="text" name="member_email" placeholder="Email" value="' . (isset($member_email) ? esc_attr($member_email) : '') . '" required></p>';
    //apply filter to output additional form fields
    $coupon_input = '';
    $coupon_input = apply_filters('swpm_payment_form_additional_fields', $coupon_input, $button_id, $uniqid);
    if (!empty($coupon_input)) {
        $output .= $coupon_input;
    }
    $output .= '<di id="swpm-braintree-amount-container-' . $uniqid . '" class="swpm-braintree-amount-container"><p>' . $payment_amount_formatted . '</p></di(v>';
    $output .= '</div>';
    $output .= '<button id="swpm-show-form-btn-' . $uniqid . '" class="swpm-braintree-pay-now-button swpm-braintree-show-form-button-' . esc_attr($button_id) . ' ' . esc_attr($class) . '" type="button" onclick="swpm_braintree_show_form_' . $uniqid . '();"><span>' . esc_attr($button_text) . '</span></button>';
    $output .= '<button id="swpm-submit-form-btn-' . $uniqid . '" class="swpm-braintree-pay-now-button swpm-braintree-submit-form-button-' . esc_attr($button_id) . ' ' . esc_attr($class) . '" type="submit" style="display: none;"><span>' . esc_attr($button_text) . '</span></button>';
    
    $output .= '<script src="https://js.braintreegateway.com/web/3.88.2/js/client.min.js"></script>';
    $output .= '<script src="https://js.braintreegateway.com/web/dropin/1.33.4/js/dropin.min.js"></script>';    


    ob_start();
?>
    <style>
        .braintree-sheet__content--form .braintree-form__flexible-fields{
            display:inherit;
        }
        .error-required{
            color:#b14747;
            display: block;
        }
        .error-required-hide{
            display: none;
        }
    </style>
    <script>
        function swpm_braintree_show_form__uniqid_() {
            document.getElementById('swpm-show-form-btn-_uniqid_').style.display = "none";
            document.getElementById('swpm-submit-form-btn-_uniqid_').style.display = "block";
            document.getElementById('swpm-form-cont-_uniqid_').style.display = "block";
            var clientToken = '_token_';
            var submitButton = document.getElementById('swpm-submit-form-btn-_uniqid_');
            var form = document.getElementById('swpm-braintree-payment-form-_uniqid_');
            var additional_fields = document.getElementById('swpm-braintree-additional-fields-container-_uniqid_');
            var amount = form.querySelector('[name="item_price"]').value;

            var first_name_parent = additional_fields.querySelector('[name="first_name"]');
            var member_email_parent = additional_fields.querySelector('[name="member_email"]');


            var errorElement_firstname = document.createElement("label");
            errorElement_firstname.innerText="First name is required";
            errorElement_firstname.classList.add("error-required");
            errorElement_firstname.classList.add("error-required-hide");
            first_name_parent.parentNode.insertBefore(errorElement_firstname, first_name_parent.nextSibling);

            var errorElement_email = document.createElement("label");
            errorElement_email.innerText="Email address is required";
            errorElement_email.classList.add("error-required");
            errorElement_email.classList.add("error-required-hide");
            member_email_parent.parentNode.insertBefore(errorElement_email, member_email_parent.nextSibling);

            //creating dropin
            braintree.dropin.create({
                authorization: clientToken,
                container: '#swpm-form-cont-_uniqid_',
                threeDSecure: true
            }, function(err, dropinInstance) {                
                if (err) {
                    // Handle any errors that might've occurred when creating Drop-in
                    console.error(err);
                    alert("Error creating payment form. Try again or check console logs");
                    return;
                }

                document.getElementById('swpm-braintree-additional-fields-container-_uniqid_').style.display = "block";

                submitButton.addEventListener('click', function(e) {
                    e.preventDefault();

                    
                    var first_name = additional_fields.querySelector('[name="first_name"]').value;

                    var last_name = additional_fields.querySelector('[name="last_name"]').value;
                    
                    var member_email = additional_fields.querySelector('[name="member_email"]').value;
                    

                    var emailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;                    
                    var isError_firstname=false;
                    var isError_email=false;

                    if(first_name.length==0)
                    {
                        errorElement_firstname.classList.remove("error-required-hide");
                        isError_firstname=true;
                    }
                    else{
                        errorElement_firstname.classList.add("error-required-hide");
                        isError_firstname=false;
                    }

                    if(!member_email.match(emailformat))
                    {
                        errorElement_email.classList.remove("error-required-hide");
                        isError_email=true;
                    }
                    else{
                        errorElement_email.classList.add("error-required-hide");
                        isError_email=false;
                    }


                    if(isError_firstname==true || isError_email==true )
                    {                    
                        return;
                    }



                    dropinInstance.requestPaymentMethod({
                        threeDSecure: {
                            amount: amount,
                            email: member_email,
                            billingAddress: {
                                givenName: first_name,
                                surname: last_name,
                            }
                        }
                    }, function(err, payload) {
                        if (err) {
                            // Handle errors in requesting payment method
                            console.log(err);
                            document.getElementById('swpm-submit-form-btn-_uniqid_').disabled = false;
                            alert("Error in requesting payment. Try again or check console logs");
                            return;
                        }
                        
                        document.getElementById('swpm-submit-form-btn-_uniqid_').disabled = true;

                        if (payload.type !== 'CreditCard') {
                            document.getElementById('swpm-braintree-nonce-field-_uniqid_').value = payload.nonce;
                            document.getElementById('swpm-braintree-payment-form-_uniqid_').submit();
                            return true;
                        }
                        
                        document.getElementById('swpm-braintree-nonce-field-_uniqid_').value = payload.nonce;
                        document.getElementById('swpm-braintree-payment-form-_uniqid_').submit();
                    });
                });
            });

        }
    </script>
<?php

    $scr = ob_get_clean();
    $scr = str_replace(array('_uniqid_', '_token_', '_amount_'), array($uniqid, $clientToken, $payment_amount), $scr);
    $output .= $scr;

    $output .= wp_nonce_field('stripe_payments', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="item_number" value="' . esc_attr($button_id) . '" />';
    $output .= '<input type="hidden" id="swpm-braintree-nonce-field-' . $uniqid . '" name="payment_method_nonce" value="" />';
    $output .= "<input type='hidden' value='".esc_attr($item_name)."' name='item_name' />";
    $output .= "<input type='hidden' value='".esc_attr($payment_amount)."' name='item_price' />";
    $output .= "<input type='hidden' value='".esc_attr($payment_currency)."' name='currency_code' />";
    $output .= "<input type='hidden' value='".esc_attr($custom_field_value)."' name='custom' />";

    //Filter to add additional payment input fields to the form.
    $output .= apply_filters('swpm_braintree_payment_form_additional_fields', '');

    $output .= "</form>";
    $output .= '</div>'; //End .swpm_button_wrapper

    return $output;
}
