<?php

/**************************************************
 * PayPal Buy Now New button shortcode handler
 *************************************************/
add_filter('swpm_payment_button_shortcode_for_pp_buy_now_new', 'swpm_render_pp_buy_now_new_button_sc_output', 10, 2);

function swpm_render_pp_buy_now_new_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? sanitize_text_field($args['id']) : '';
    if (empty($button_id)) {
        return '<p class="swpm-red-box">'.__('Error! swpm_render_pp_buy_now_new_button_sc_output() function requires the button ID value to be passed to it.', 'simple-membership').'</p>';
    }

    //Membership level for this button
    $membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
    //Verify that this membership level exists (to prevent user paying for a level that has been deleted)
    if (!SwpmUtils::membership_level_id_exists($membership_level_id)) {
        return '<p class="swpm-red-box">'.__('Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.', 'simple-membership').'</p>';
    }

    //Payment amount
    $payment_amount = get_post_meta($button_id, 'payment_amount', true);

    //Get the Item name for this button. This will be used as the item name in the IPN.
    $button_cpt = get_post($button_id); //Retrieve the CPT for this button
    $item_name = htmlspecialchars($button_cpt->post_title);
    $item_name = substr($item_name, 0, 127);//Limit the item name to 127 characters (PayPal limit)

    //User's IP address
    $user_ip = SwpmUtils::get_user_ip_address();

    //Custom field data
    $custom_field_value = 'subsc_ref=' . $membership_level_id;
    $custom_field_value .= '&user_ip=' . $user_ip;
    if (SwpmMemberUtils::is_member_logged_in()) {
        $member_id = SwpmMemberUtils::get_logged_in_members_id();
        $custom_field_value .= '&swpm_id=' . $member_id;
        //$member_first_name = SwpmMemberUtils::get_member_field_by_id($member_id, 'first_name');
        //$member_last_name = SwpmMemberUtils::get_member_field_by_id($member_id, 'last_name');
        //$member_email = SwpmMemberUtils::get_member_field_by_id($member_id, 'email');
    }
    $custom_field_value = apply_filters('swpm_custom_field_value_filter', $custom_field_value);

    /*****************************************
     * Settings and Button Specific Configuration
     *****************************************/    
    $settings = SwpmSettings::get_instance();
    $live_client_id = $settings->get_value('paypal-live-client-id');
    $sandbox_client_id = $settings->get_value('paypal-sandbox-client-id');
    $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
    $is_live_mode = $sandbox_enabled ? 0 : 1;

	$currency = get_post_meta( $button_id, 'payment_currency', true );

	$disable_funding_card = get_post_meta( $button_id, 'pp_buy_now_new_disable_funding_card', true );
    $disable_funding_credit = get_post_meta( $button_id, 'pp_buy_now_new_disable_funding_credit', true );
    $disable_funding_venmo = get_post_meta( $button_id, 'pp_buy_now_new_disable_funding_venmo', true );
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

	$btn_type = get_post_meta($button_id, 'pp_buy_now_new_btn_type', true);
    $btn_shape = get_post_meta($button_id, 'pp_buy_now_new_btn_shape', true);
    $btn_layout = get_post_meta($button_id, 'pp_buy_now_new_btn_layout', true);
    $btn_color = get_post_meta($button_id, 'pp_buy_now_new_btn_color', true);

    $btn_width = get_post_meta($button_id, 'pp_buy_now_new_btn_width', true);
    $btn_height = get_post_meta($button_id, 'pp_buy_now_new_btn_height', true);
    $btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
    $btn_height = isset( $btn_sizes[ $btn_height ] ) ? $btn_sizes[ $btn_height ] : 35;

    $txn_success_message = __('Transaction completed successfully!', 'simple-membership');

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
        'intent' => 'capture', /* It is used to set the "intent" parameter in the JS SDK */
        'is_subscription' => 0, /* It is used to set the "vault" parameter in the JS SDK */
    );

    //Validate the PayPal client ID to ensure the settings are correct.
    $any_validation_error = SWPM_PayPal_Utility_Functions::validate_paypal_client_id_settings( $settings_args );
    if ( !empty($any_validation_error) ) {
        //If there is any validation error, return the error message.
        return '<p class="swpm-red-box">'.$any_validation_error.'</p>';
    }

    //Initialize and set the settings args that will be used to load the JS SDK.
    $pp_js_button = SWPM_PayPal_JS_Button_Embed::get_instance();
    $pp_js_button->set_settings_args( $settings_args );

    //Load the JS SDK on footer (so it only loads once per page)
    add_action( 'wp_footer', array($pp_js_button, 'load_paypal_sdk') );

    //The on page embed button id is used to identify the button on the page. Useful when there are multiple buttons (of the same item/product) on the same page.
    $on_page_embed_button_id = $pp_js_button->get_next_button_id();
    //Create nonce for this button. 
    $wp_nonce = wp_create_nonce($on_page_embed_button_id);

    $swpm_button_wrapper_id = 'swpm-button-wrapper-'.$button_id;

    $output = '';
    ob_start();
    ?>
    <div id="<?php echo esc_attr($swpm_button_wrapper_id); ?>" class="swpm-button-wrapper swpm-paypal-buy-now-button-wrapper">

    <!-- PayPal button container where the button will be rendered -->
    <div id="<?php echo esc_attr($on_page_embed_button_id); ?>" style="width: <?php echo esc_attr($btn_width); ?>px;"></div>
    <!-- Some additiona hidden input fields -->
    <input type="hidden" id="<?php echo esc_attr($on_page_embed_button_id.'-custom-field'); ?>" name="custom" value="<?php echo esc_attr($custom_field_value); ?>">

    <script type="text/javascript">
        document.addEventListener( "swpm_paypal_sdk_loaded", function() { 
            //Anything that goes here will only be executed after the PayPal SDK is loaded.
            console.log('PayPal JS SDK is loaded.');

            var js_currency_code = '<?php echo esc_js($currency); ?>';
            var js_payment_amount = <?php echo esc_js($payment_amount); ?>;
            var js_quantity = 1;
            var js_digital_goods_enabled = 1;

            const paypalButtonsComponent = paypal.Buttons({
                // optional styling for buttons
                // https://developer.paypal.com/docs/checkout/standard/customize/buttons-style-guide/
                style: {
                    color: '<?php echo esc_js($btn_color); ?>',
                    shape: '<?php echo esc_js($btn_shape); ?>',
                    height: <?php echo esc_js($btn_height); ?>,
                    label: '<?php echo esc_js($btn_type); ?>',
                    layout: '<?php echo esc_js($btn_layout); ?>',
                },

                // Setup the transaction.
                createOrder: async function() {
                    // Create the order in PayPal using the PayPal API.
                    // https://developer.paypal.com/docs/checkout/standard/integrate/
                    // The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.                    
                    console.log('Setting up the AJAX request for create-order call.');
                    let pp_bn_data = {};
                    pp_bn_data.button_id = '<?php echo esc_js($button_id); ?>';
                    pp_bn_data.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
                    pp_bn_data.item_name = '<?php echo esc_js($item_name); ?>';
                    let post_data = 'action=swpm_pp_create_order&data=' + JSON.stringify(pp_bn_data) + '&_wpnonce=<?php echo $wp_nonce; ?>';
                    try {
                        // Using fetch for AJAX request. This is supported in all modern browsers.
                        const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                            method: "post",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: post_data
                        });

                        const response_data = await response.json();

                        if (response_data.order_id) {
                            console.log('Create-order API call to PayPal completed successfully.');
                            //If we need to see the order details, uncomment the following line.
                            //const order_data = response_data.order_data;
                            //console.log('Order data: ' + JSON.stringify(order_data));
                            return response_data.order_id;
                        } else {
                            const error_message = JSON.stringify(response_data);
                            console.error('Error occurred during the create-order API call to PayPal. ' + error_message);
                            throw new Error(error_message);
                        }
                    } catch (error) {
                        console.error(error);
                        alert('Could not initiate PayPal Checkout...\n\n' + JSON.stringify(error));
                    }
                },
    
                // handle the onApprove event
                onApprove: async function(data, actions) {
                    console.log('Successfully created a transaction.');

                    //Show the spinner while we process this transaction.
                    const pp_button_container = document.getElementById('<?php echo esc_js($on_page_embed_button_id); ?>');
                    const pp_button_container_wrapper = document.getElementById('<?php echo esc_js($swpm_button_wrapper_id); ?>');
                    const pp_button_spinner_container = pp_button_container_wrapper.querySelector('.swpm-pp-button-spinner-container');
                    pp_button_container.style.display = 'none'; //Hide the buttons
                    pp_button_spinner_container.style.display = 'inline-block'; //Show the spinner.

                    // Capture the order in PayPal using the PayPal API.
                    // https://developer.paypal.com/docs/checkout/standard/integrate/
                    // The server-side capture-order API is used. Then the Capture-ID is returned.
                    console.log('Setting up the AJAX request for capture-order call.');
                    let pp_bn_data = {};
                    pp_bn_data.order_id = data.orderID;
                    pp_bn_data.button_id = '<?php echo esc_js($button_id); ?>';
                    pp_bn_data.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
                    pp_bn_data.item_name = '<?php echo esc_js($item_name); ?>';

                    //Add custom_field data. It is important to encode the custom_field data so it doesn't mess up the data with & character.
                    const custom_data = document.getElementById('<?php echo esc_attr($on_page_embed_button_id."-custom-field"); ?>').value;
                    pp_bn_data.custom_field = encodeURIComponent(custom_data);
                    
                    const post_data = new URLSearchParams({
                        action: 'swpm_pp_capture_order',
                        data: JSON.stringify(pp_bn_data),
                        _wpnonce: '<?php echo $wp_nonce; ?>',
                    }).toString();
                    
                    try {
                        const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                            method: "post",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: post_data
                        });

                        const response_data = await response.json();
                        const txn_data = response_data.txn_data;
                        const error_detail = txn_data?.details?.[0];
                        const error_msg = response_data.error_msg;//Our custom error message.
                        // Three cases to handle:
                        // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                        // (2) Other non-recoverable errors -> Show a failure message
                        // (3) Successful transaction -> Show confirmation or thank you message

                        if (response_data.capture_id) {
                            // Successful transaction -> Show confirmation or thank you message
                            console.log('Capture-order API call to PayPal completed successfully.');

                            //Redirect to the Thank you page or Registration page URL if it is set.
                            const return_url = response_data.redirect_url || '';
                            if( return_url ){
                                //redirect to the URL.
                                console.log('Redirecting to the Thank you page URL: ' + return_url);
                                window.location.href = return_url;
                                return;
                            } else {
                                //No return URL is set. Just show a success message.
                                //Important Note: any alert message will block the normal PayPal popup window flow. So we want to show the message on the page instead of using alert.
                                txn_success_msg = '<?php echo esc_attr($txn_success_message); ?>';
                                const swpm_btn_wrapper_div = document.getElementById('<?php echo esc_js($swpm_button_wrapper_id); ?>');
                                if (swpm_btn_wrapper_div) {
                                    // Remove any previous message if it exists
                                    const old_msg_div = swpm_btn_wrapper_div.querySelector('.swpm-ppcp-txn-success-message');
                                    if (old_msg_div) old_msg_div.remove();

                                    // Create new message div
                                    const new_msg_div = document.createElement('div');
                                    new_msg_div.className = 'swpm-ppcp-txn-success-message';
                                    new_msg_div.textContent = txn_success_msg;

                                    //Insert the message div before the button.
                                    const firstChild = swpm_btn_wrapper_div.firstChild;
                                    swpm_btn_wrapper_div.insertBefore(new_msg_div, firstChild);
                                }
                            }

                        } else if (error_detail?.issue === "INSTRUMENT_DECLINED") {
                            // Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                            console.log('Recoverable INSTRUMENT_DECLINED error. Calling actions.restart()');
                            return actions.restart();
                        } else if ( error_msg && error_msg.trim() !== '' ) {
                            //Our custom error message from the server.
                            console.error('Error occurred during PayPal checkout process.');
                            console.error( error_msg );
                            alert( error_msg );
                        } else {
                            // Other non-recoverable errors -> Show a failure message
                            console.error('Non-recoverable error occurred during PayPal checkout process.');
                            console.error( error_detail );
                            //alert('Error occurred with the transaction. Enable debug logging to get more details.\n\n' + JSON.stringify(error_detail));
                        }

                        //Return the button and the spinner back to their orignal display state.
                        pp_button_container.style.display = 'block'; // Show the buttons
                        pp_button_spinner_container.style.display = 'none'; // Hide the spinner

                    } catch (error) {
                        console.error(error);
                        alert('PayPal returned an error! Transaction could not be processed. Enable the debug logging feature to get more details...\n\n' + JSON.stringify(error));
                    }
                },
    
                // handle unrecoverable errors
                onError: function(err) {
                    console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                    alert( '<?php echo esc_js(__("Error occurred during PayPal checkout process.", "simple-membership")); ?>\n\n' + JSON.stringify(err) );
                },

                // handle onCancel event
                onCancel: function(data) {
                    console.log('Checkout operation cancelled by the customer.');
                    //Return to the parent page which the button does by default.
                }
            });
    
            paypalButtonsComponent
                .render('#<?php echo esc_js($on_page_embed_button_id); ?>')
                .catch((err) => {
                    console.error('PayPal Buttons failed to render');
                });

        });
    </script>
    <style>
        @keyframes swpm-pp-button-spinner {
            to {transform: rotate(360deg);}
        }
        .swpm-pp-button-spinner {
            margin: 0 auto;
            text-indent: -9999px;
            vertical-align: middle;
            box-sizing: border-box;
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 5px solid #ccc;
            border-top-color: #0070ba;
            animation: swpm-pp-button-spinner .6s linear infinite;
        }
        .swpm-pp-button-spinner-container {
            width: 100%;
            text-align: center;
            margin-top:10px;
            display: none;
        }
    </style>
    <div class="swpm-pp-button-spinner-container">
        <div class="swpm-pp-button-spinner"></div>
    </div>
    </div><!-- end of .swpm-button-wrapper -->
    <?php
    $output .= ob_get_clean();

    return $output;
}
