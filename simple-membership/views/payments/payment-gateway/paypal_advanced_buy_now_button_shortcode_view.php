<?php

/**************************************************
 * PayPal PPCP Buy Now button shortcode handler
 *************************************************/

add_shortcode( 'swpm_payment_button_ppcp', 'swpm_render_pp_buy_now_ppcp_button_sc_output' );
//add_filter('swpm_payment_button_shortcode_for_pp_buy_now_new', 'swpm_render_pp_buy_now_new_button_sc_output', 10, 2);

function swpm_render_pp_buy_now_ppcp_button_sc_output( $button_code, $args ) {

	// $button_id = isset($args['id']) ? $args['id'] : '';
	// if (empty($button_id)) {
	//     return '<p class="swpm-red-box">Error! swpm_render_pp_buy_now_new_button_sc_output() function requires the button ID value to be passed to it.</p>';
	// }
	$button_id = '4638';

	//Membership level for this button
	$membership_level_id = get_post_meta( $button_id, 'membership_level_id', true );
	//Verify that this membership level exists (to prevent user paying for a level that has been deleted)
	if (! SwpmUtils::membership_level_id_exists( $membership_level_id )) {
		return '<p class="swpm-red-box">Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.</p>';
	}

	//Payment amount
	$payment_amount = get_post_meta( $button_id, 'payment_amount', true );

	//Get the Item name for this button. This will be used as the item name in the IPN.
	$button_cpt = get_post( $button_id ); //Retrieve the CPT for this button
	$item_name = htmlspecialchars( $button_cpt->post_title );
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
	$custom_field_value = apply_filters( 'swpm_custom_field_value_filter', $custom_field_value );

	/*****************************************
	 * Settings and Button Specific Configuration
	 *****************************************/
	$settings = SwpmSettings::get_instance();
	$live_client_id = $settings->get_value( 'paypal-live-client-id' );
	$sandbox_client_id = $settings->get_value( 'paypal-sandbox-client-id' );
	$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );
	$is_live_mode = $sandbox_enabled ? 0 : 1;
    $environment_mode = $sandbox_enabled ? 'sandbox' : 'production';

	$currency = get_post_meta( $button_id, 'payment_currency', true );

	$disable_funding_card = get_post_meta( $button_id, 'pp_buy_now_new_disable_funding_card', true );
	$disable_funding_credit = get_post_meta( $button_id, 'pp_buy_now_new_disable_funding_credit', true );
	$disable_funding_venmo = get_post_meta( $button_id, 'pp_buy_now_new_disable_funding_venmo', true );
	$disable_funding = array();
	if (! empty( $disable_funding_card )) {
		$disable_funding[] = 'card';
	}
	if (! empty( $disable_funding_credit )) {
		$disable_funding[] = 'credit';
	}
	if (! empty( $disable_funding_venmo )) {
		$disable_funding[] = 'venmo';
	}

	$btn_type = get_post_meta( $button_id, 'pp_buy_now_new_btn_type', true );
	$btn_shape = get_post_meta( $button_id, 'pp_buy_now_new_btn_shape', true );
	$btn_layout = get_post_meta( $button_id, 'pp_buy_now_new_btn_layout', true );
	$btn_color = get_post_meta( $button_id, 'pp_buy_now_new_btn_color', true );

	$btn_width = get_post_meta( $button_id, 'pp_buy_now_new_btn_width', true );
	$btn_height = get_post_meta( $button_id, 'pp_buy_now_new_btn_height', true );
	$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
	$btn_height = isset( $btn_sizes[ $btn_height ] ) ? $btn_sizes[ $btn_height ] : 35;

	$return_url = get_post_meta( $button_id, 'return_url', true );
	$txn_success_message = __( 'Transaction completed successfully!', 'simple-membership' );

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

	//Initialize and set the settings args that will be used to load the JS SDK.
	$pp_js_button = SWPM_PayPal_JS_Button_Embed::get_instance();
	$pp_js_button->set_settings_args( $settings_args );

	//Load the JS SDK on footer (so it only loads once per page)
	//Do not load this version of the JS SDK for ACDC buttons.
	//add_action( 'wp_footer', array( $pp_js_button, 'load_paypal_sdk' ) );

	//The on page embed button id is used to identify the button on the page. Useful when there are multiple buttons (of the same item/product) on the same page.
	$on_page_embed_button_id = $pp_js_button->get_next_button_id();
	//Create nonce for this button. 
	$wp_nonce = wp_create_nonce( $on_page_embed_button_id );

	//TODO - Remove this later. This is just for testing.
	//Force Cache Delete on page load. Delete the bearer token from cache to make sure it generates a new one.
	//SWPM_PayPal_Bearer::delete_cached_token();//Testing purpose only.

        
	$pp_acdc = new SWPM_PayPal_ACDC_Related();
	$client_token = $pp_acdc->generate_client_token( $environment_mode );
    $currency = isset( $currency ) ? $currency : 'USD';
	$sdk_src_url = SWPM_PayPal_ACDC_Related::get_sdk_src_url_for_acdc( $environment_mode, $currency );

    //return "";//TODO - remove later.
	//TODO - Remove this later. This is just for testing.
	//Force Cache Delete on page load. Delete the bearer token from cache to make sure it generates a new one.
	//$paypal_cache = SWPM_PayPal_Cache::get_instance();
	//$paypal_cache->delete( SWPM_PayPal_Bearer::BEARER_CACHE_KEY );//Delete to reset the cache to make sure it generates a new one.

	// $merchant_id = SWPM_PayPal_Utility_Functions::get_seller_merchant_id_by_environment_mode( $environment_mode );
	// echo '<br />------------Debug data Start------------<br />';
    // echo '<br />PayPal Button Container ID: ' . $on_page_embed_button_id;
	// echo '<br />Client ID: ' . $sandbox_client_id;
	// echo '<br />Merchant ID: ' . $merchant_id;
	// echo '<br />------------Debug data End------------<br />';
        
	//TODO - Hardcoding the SDK SRC URL for testing purpose.
    //$sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,card-fields&client-id=AWhSWfRz8trG53XGB_NojvmgFCJErbtqfyKsggUIK4N2of5c9pktXmgOksLM0pztnnmaGxXgYBg4Qatq';//Vidya's client-ID
    //$sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,card-fields&client-id=AXXepw7uhkLrtey3bJFpNZQIigy15JsCfSBbZEno-lJiNs5Fqf2-_uqxGH6i8U-1Zc6k6QjSKVm48Wrg';//New account client-ID
    //$sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=AQ1G2q1dWrcPZzrioplED3qB0forkMUhS12VPcVoEvxSCHce7iNpAGgI12nUPNVgcQY7AuGp8iL6jAQQ';
    //$sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX';
    //$sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX&currency=USD&intent=capture';
    //$sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=AQ1G2q1dWrcPZzrioplED3qB0forkMUhS12VPcVoEvxSCHce7iNpAGgI12nUPNVgcQY7AuGp8iL6jAQQ&merchant-id=6P8SX89ESHD56&currency=USD&intent=capture';
    
    //echo '<p>SDK Source URL: ' . $sdk_src_url . '</p>';
           
    //Get the bearer/access token.
    // $bearer = SWPM_PayPal_Bearer::get_instance();
    // $bearer_access_token = $bearer->get_bearer_token( $environment_mode );
    // echo '<p>Bearer Access Token: ' . $bearer_access_token . '</p>';
    //$client_token = $bearer_access_token;//Testing with bearer access token
            
    //Commented code
    /*
    * <script src="<?php echo $sdk_src_url; ?>" data-partner-attribution-id="TipsandTricks_SP_PPCP" data-client-token="<?php echo $client_token; ?>"></script>
    * $sdk_src_url = 'https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=AQ1G2q1dWrcPZzrioplED3qB0forkMUhS12VPcVoEvxSCHce7iNpAGgI12nUPNVgcQY7AuGp8iL6jAQQ&merchant-id=6P8SX89ESHD56&currency=USD&intent=capture';
    */

	$output = '';
	ob_start();
	?>
<!-- To be replaced with your own stylesheet -->
<link rel="stylesheet" type="text/css" href="https://www.paypalobjects.com/webstatic/en_US/developer/docs/css/cardfields.css"/>

<!-- Express fills in the clientId and clientToken variables -->
<script src="<?php echo $sdk_src_url; ?>" data-partner-attribution-id="TipsandTricks_SP_PPCP" data-client-token="<?php echo $client_token; ?>"></script>

<div class="swpm-button-wrapper swpm-paypal-buy-now-button-wrapper">

    <div id="paypal-button-container" class="paypal-button-container"></div>
        <div id="checkout-form">
        <div id="card-name-field-container"></div>
        <div id="card-number-field-container"></div>
        <div id="card-expiry-field-container"></div>
        <div id="card-cvv-field-container"></div>
        <button id="card-field-submit-button" type="button">Pay with Card</button>
    </div>

<script type="text/javascript">
// Render the button component
paypal
  .Buttons({
    // optional styling for buttons
    // https://developer.paypal.com/docs/checkout/standard/customize/buttons-style-guide/
    style: {
        color: '<?php echo esc_js($btn_color); ?>',
        shape: '<?php echo esc_js($btn_shape); ?>',
        height: <?php echo esc_js($btn_height); ?>,
        label: '<?php echo esc_js($btn_type); ?>',
        layout: '<?php echo esc_js($btn_layout); ?>',
    },
                
    // Sets up the transaction when a payment button is clicked
    createOrder: function (data) {
        console.log('Going to execute the create order code for PayPal button.');
        // The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.
        console.log('Setting up the order for PayPal button checkout.');
        let dataObj = {};
        dataObj.button_id = '<?php echo esc_js($button_id); ?>';
        dataObj.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
        dataObj.item_name = '<?php echo esc_js($item_name); ?>';
        dataObj.payment_source = data.paymentSource;
        console.log('dataObj: ' + JSON.stringify(dataObj));

        // Using fetch API for AJAX
        let postData = 'action=swpm_acdc_setup_order&data=' + JSON.stringify(dataObj) + '&_wpnonce=<?php echo $wp_nonce; ?>';
        console.log('Goign to send off Ajax request to the server that will create the order.');
        console.log('Post Data: ' + postData);
        //This alert will allow us to see the console log before going forward.
        alert('About to send the ajax request');
                                                
        return fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: postData
        })
        .then((response) => response.json())
        .then((order) => order.order_id);
    },
    // Finalize the transaction after payer approval
    onApprove: function (data) {
        return fetch(`myserver.com/api/orders/${data.orderID}/capture`, {
          method: "POST",
        })
        .then((response) => response.json())
        .then((orderData) => {
          // Successful capture! For dev/demo purposes:
          console.log("Capture result", orderData, JSON.stringify(orderData, null, 2));
          var transaction = orderData.purchase_units[0].payments.captures[0];
          // Show a success message within this page. For example:
          // var element = document.getElementById('paypal-button-container');
          // element.innerHTML = '<h3>Thank you for your payment!</h3>';
          // Or go to another URL: actions.redirect('thank_you.html');
        });
    },
    onError: function (error) {
        // Do something with the error from the SDK
    }
  })
  .render("#paypal-button-container");

// Create the Card Fields Component and define callbacks
const cardField = paypal.CardFields({
    createOrder: function (data) {
        // The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.
        console.log('Setting up the order for ACDC checkout.');
        let dataObj = {};
        dataObj.button_id = '<?php echo esc_js($button_id); ?>';
        dataObj.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
        dataObj.item_name = '<?php echo esc_js($item_name); ?>';
        dataObj.payment_source = data.paymentSource;
        console.log('dataObj: ' + JSON.stringify(dataObj));

        // Using fetch API for AJAX
        let postData = 'action=swpm_acdc_setup_order&data=' + JSON.stringify(dataObj) + '&_wpnonce=<?php echo $wp_nonce; ?>';
        console.log('Goign to send off Ajax request to the server that will create the order.');
        console.log('Post Data: ' + postData);
        //This alert will allow us to see the console log before going forward.
        //alert('About to send the ajax request');
                                                
        return fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "post",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: postData
        })
        .then((res) => {
            return res.json();
        })
        .then((orderData) => {
            console.log('ACDC order-create response below.');
            console.log(orderData);
            return orderData.order_id;
        });
    },
    onApprove: function (data) {
        const { orderID } = data;
        
//<-- This was not executing becasue there was no "onApprove" in the previous code example.        
console.log('Going to execute the capture order code.');
console.log(data);
console.log('Order ID: ' + orderID);
const formData = new FormData();
formData.append('action', 'swpm_acdc_capture_order');
formData.append('order_id', orderID);
formData.append('on_page_button_id', '<?php echo esc_js($on_page_embed_button_id); ?>');
formData.append('_wpnonce', '<?php echo $wp_nonce; ?>');
console.log( 'Going to do capture order AJAX. Form Data: ' +  JSON.stringify(formData) );

        return fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "post",
            body: formData
        })
        .then((res) => {
            return res.json();
        })
        .then((orderData) => {
            // Redirect to success page
            console.log('Capture response below.');
            console.log(orderData);
            console.log('Redirecting to Thank You Page. Thank you URL: ' . $return_url);
            alert('Capture successful. Redirecting to Thank You Page.');
            window.location.href = '<?php echo esc_js($return_url); ?>';
        });
    },
    onError: function (error) {
        // Do something with the error from the SDK
        console.log('ACDC onError triggered for card checkout.');
        console.log(error);
    }
});

// Render each field after checking for eligibility
if (cardField.isEligible()) {
    const nameField = cardField.NameField();
    nameField.render('#card-name-field-container');

    const numberField = cardField.NumberField();
    numberField.render('#card-number-field-container');

    const cvvField = cardField.CVVField();
    cvvField.render('#card-cvv-field-container');

    const expiryField = cardField.ExpiryField();
    expiryField.render('#card-expiry-field-container');

    // Add click listener to submit button and call the submit function on the CardField component
    document.getElementById("card-field-submit-button").addEventListener("click", () => {
        cardField
        .submit()
        .then(() => {
            // submit successful
        });
    });
};
</script>

</div><!-- end of .swpm-button-wrapper -->

    <?php
    $output .= ob_get_clean();
    return $output;

}