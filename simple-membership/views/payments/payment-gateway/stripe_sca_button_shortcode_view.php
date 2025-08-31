<?php

/* * ************************************************
 * Stripe Buy Now button shortcode handler
 * *********************************************** */
add_filter( 'swpm_payment_button_shortcode_for_stripe_sca_buy_now', 'swpm_render_stripe_sca_buy_now_button_sc_output', 10, 2 );

function swpm_render_stripe_sca_buy_now_button_sc_output( $button_code, $args ) {

	$button_id = isset( $args['id'] ) ? sanitize_text_field($args['id']) : '';
	if ( empty( $button_id ) ) {
		return '<p class="swpm-red-box">Error! swpm_render_stripe_sca_buy_now_button_sc_output() function requires the button ID value to be passed to it.</p>';
	}

	//Get class option for button styling, set Stripe's default if none specified
	$class = isset( $args['class'] ) ? sanitize_html_class($args['class']) : 'stripe-button-el';

	//Check new_window parameter
	$window_target = isset( $args['new_window'] ) ? 'target="_blank"' : '';
	$button_text   = ( isset( $args['button_text'] ) ) ? sanitize_text_field( $args['button_text'] ) : __( 'Buy Now', 'simple-membership' );

	//Check the optional 'payment_method_types' paramter to see if it is set. Example value: payment_method_types="card,us_bank_account".
	//It can be used to enable ACH payment option.
	$payment_method_types = isset( $args['payment_method_types'] ) ? $args['payment_method_types'] : '';

	$item_logo = ''; //Can be used to show an item logo or thumbnail in the checkout form.

	$settings   = SwpmSettings::get_instance();
	$button_cpt = get_post( $button_id ); //Retrieve the CPT for this button
	$item_name  = htmlspecialchars( $button_cpt->post_title );

	$membership_level_id = get_post_meta( $button_id, 'membership_level_id', true );
	//Verify that this membership level exists (to prevent user paying for a level that has been deleted)
	if ( ! \SwpmUtils::membership_level_id_exists( $membership_level_id ) ) {
		return '<p class="swpm-red-box">'.__('Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.', 'simple-membership').'</p>';
	}

	//Payment amount and currency
	$payment_amount = get_post_meta( $button_id, 'payment_amount', true );
	if ( ! is_numeric( $payment_amount ) ) {
		return '<p class="swpm-red-box">'.__('Error! The payment amount value of the button must be a numeric number. Example: 49.50 ', 'simple-membership').'</p>';
	}
	$payment_currency = get_post_meta( $button_id, 'payment_currency', true );
	$payment_amount   = round( $payment_amount, 2 ); //round the amount to 2 decimal place.
	$zero_cents       = unserialize( SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS );
	if ( in_array( $payment_currency, $zero_cents ) ) {
		//this is zero-cents currency, amount shouldn't be multiplied by 100
		$price_in_cents = $payment_amount;
	} else {
		$price_in_cents = $payment_amount * 100; //The amount (in cents). This value is passed to Stripe API.
	}
	$payment_amount_formatted = SwpmMiscUtils::format_money( $payment_amount, $payment_currency );

	//$button_image_url = get_post_meta($button_id, 'button_image_url', true);//Stripe doesn't currenty support button image for their standard checkout.
	//User's IP address
	$user_ip                                     = SwpmUtils::get_user_ip_address();
	$_SESSION['swpm_payment_button_interaction'] = $user_ip;

	//Sandbox settings
	$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );

	//API keys
	$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $button_id, ! $sandbox_enabled );

	$uniqid = md5( uniqid() );
	$ref_id = 'swpm_' . $uniqid . '|' . $button_id;

	//Return, cancel, notifiy URLs
	$notify_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_sca_buy_now=1&ref_id=' . $ref_id; //We are going to use it to do post payment processing.

        $current_url = SwpmMiscUtils::get_current_page_url();

	/* === Stripe Buy Now Button Form === */
	$output  = '';
	$output .= '<div class="swpm-button-wrapper swpm-stripe-buy-now-wrapper">';
	$output .= "<form id='swpm-stripe-payment-form-" . $uniqid . "' action='" . $notify_url . "' METHOD='POST'> ";
	$output .= "<div style='display: none !important'>";

	//Handle script and style loading for the button
	if ( ! wp_script_is( 'swpm.stripe', 'registered' ) ) {
            //In some themes (block themes) this may not have been registered yet since that process can be delayed. So register it now before doing inline script to it.
            wp_register_script("swpm.stripe", "https://js.stripe.com/v3/", array("jquery"), SIMPLE_WP_MEMBERSHIP_VER);
	}
	wp_enqueue_script("swpm.stripe");
	wp_enqueue_style("swpm.stripe.style");

	//initializing stripe for each button, right after loading stripe script
	$stripe_js_obj="stripe_".$button_id;
	wp_add_inline_script("swpm.stripe","var ".$stripe_js_obj." = Stripe('".esc_js( $api_keys['public'] )."');");

	ob_start();
	?>
	<script>
        document.addEventListener('DOMContentLoaded', function (){
            const swpmStripeSCAPaymentFrom = document.getElementById('swpm-stripe-payment-form-<?php echo esc_js( $uniqid ); ?>');
            swpmStripeSCAPaymentFrom?.addEventListener('submit', async function (e){
                e.preventDefault();

                let submitBUtton = this.querySelector('button');
                if ( ! submitBUtton ){
                    // Using image type button
                    submitBUtton = this.querySelector('input[type="image"]');
                }

                submitBUtton?.setAttribute('disabled', true);

                const stripe_js_obj = <?php echo $stripe_js_obj;?>;
                const request_url = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
                const payload = new URLSearchParams({
                    action: 'swpm_stripe_sca_create_checkout_session',
                    swpm_button_id: <?php echo esc_js( $button_id ); ?>,
                    payment_method_types: '<?php echo esc_js( $payment_method_types ); ?>',
                    swpm_page_url: '<?php echo esc_js( $current_url ); ?>',
                    swpm_uniqid: '<?php echo esc_js( $uniqid ); ?>'
                });
                try {
                    let response = await fetch(request_url ,{
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: payload
                    })

                    if (!response.ok) {
                        // HTTP error codes (e.g. 404, 500)
                        throw new Error("Error code: " + response.status);
                    }

                    response = await response.json();

                    if (!response.error) {
                        stripe_js_obj.redirectToCheckout({
                            sessionId: response.session_id
                        }).then(function (result) {
                            //
                        });
                    } else {
                        alert(response.error);
                        submitBUtton?.removeAttribute('disabled');
                        return false;
                    }

                } catch (e) {
                    alert("HTTP error occurred during AJAX request. Error: "+ e.message);
                    submitBUtton?.removeAttribute('disabled');
                    return false;
                }
            })
		});
	</script>
	<?php
	$output .= ob_get_clean();
	$output .= '</div>';

	//apply filter to output additional form fields
	$coupon_input = '';
	$coupon_input = apply_filters( 'swpm_payment_form_additional_fields', $coupon_input, $button_id, $uniqid );
	if ( ! empty( $coupon_input ) ) {
		$output .= $coupon_input;
	}

	$button_image_url = get_post_meta( $button_id, 'button_image_url', true );
	if ( ! empty( $button_image_url ) ) {
		$output .= '<input type="image" src="' . esc_url($button_image_url) . '" class="' . esc_attr($class) . '" alt="' . esc_attr($button_text) . '" title="' . esc_attr($button_text) . '" />';
	} else {
		$output .= "<button id='".esc_attr($button_id)."' type='submit' class='".esc_attr($class)."'><span>". esc_attr($button_text)."</span></button>";
	}

	//Filter to add additional payment input fields to the form.
	$output .= apply_filters( 'swpm_stripe_payment_form_additional_fields', '' );

	$output .= '</form>';
	$output .= '</div>'; //End .swpm_button_wrapper

	return $output;
}

add_filter( 'swpm_payment_button_shortcode_for_stripe_sca_subscription', 'swpm_render_stripe_sca_subscription_button_sc_output', 10, 2 );

function swpm_render_stripe_sca_subscription_button_sc_output( $button_code, $args ) {

	$button_id = isset( $args['id'] ) ? sanitize_html_class($args['id']) : '';
	if ( empty( $button_id ) ) {
		return '<p class="swpm-red-box">'.__('Error! swpm_render_stripe_sca_buy_now_button_sc_output() function requires the button ID value to be passed to it.', 'simple-membership').'</p>';
	}

	//Get class option for button styling, set Stripe's default if none specified
	$class = isset( $args['class'] ) ? sanitize_html_class($args['class']) : 'stripe-button-el';

	//Check new_window parameter
	$window_target = isset( $args['new_window'] ) ? 'target="_blank"' : '';
	$button_text   = ( isset( $args['button_text'] ) ) ? esc_attr( $args['button_text'] ) : __( 'Buy Now' , 'simple-membership');

	//Check the optional 'payment_method_types' paramter to see if it is set. Example value: payment_method_types="card,us_bank_account".
	//It can be used to enable ACH payment option.
	$payment_method_types = isset( $args['payment_method_types'] ) ? $args['payment_method_types'] : '';

	$item_logo = ''; //Can be used to show an item logo or thumbnail in the checkout form.

	$settings   = SwpmSettings::get_instance();
	$button_cpt = get_post( $button_id ); //Retrieve the CPT for this button
	$item_name  = htmlspecialchars( $button_cpt->post_title );

	$membership_level_id = get_post_meta( $button_id, 'membership_level_id', true );
	//Verify that this membership level exists (to prevent user paying for a level that has been deleted)
	if ( ! \SwpmUtils::membership_level_id_exists( $membership_level_id ) ) {
		return '<p class="swpm-red-box">'.__('Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.', 'simple-membership').'</p>';
	}

	//$button_image_url = get_post_meta($button_id, 'button_image_url', true);//Stripe doesn't currenty support button image for their standard checkout.
	//User's IP address
	$user_ip                                     = SwpmUtils::get_user_ip_address();
	$_SESSION['swpm_payment_button_interaction'] = $user_ip;

	//Custom field data
	$custom_field_value  = 'subsc_ref=' . $membership_level_id;
	$custom_field_value .= '&user_ip=' . $user_ip;
	if ( SwpmMemberUtils::is_member_logged_in() ) {
		$custom_field_value .= '&swpm_id=' . SwpmMemberUtils::get_logged_in_members_id();
	}
	$custom_field_value = apply_filters( 'swpm_custom_field_value_filter', $custom_field_value );

	//Sandbox settings
	$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );

	//API keys
	$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $button_id, ! $sandbox_enabled );

	//Billing address
	$billing_address = isset( $args['billing_address'] ) ? '1' : '';
	//By default don't show the billing address in the checkout form.
	//if billing_address parameter is not present in the shortcode, let's check button option
	if ( $billing_address === '' ) {
		$collect_address = get_post_meta( $button_id, 'stripe_collect_address', true );
		if ( $collect_address === '1' ) {
			//Collect Address enabled in button settings
			$billing_address = 1;
		}
	}

	$uniqid = md5( uniqid() );
	$ref_id = 'swpm_' . $uniqid . '|' . $button_id;

	//Return, cancel, notifiy URLs
	$notify_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_sca_subscription=1&ref_id=' . $ref_id; //We are going to use it to do post payment processing.

	$current_url = SwpmMiscUtils::get_current_page_url();

	$plan_id = get_post_meta( $button_id, 'stripe_plan_id', true );

	/* === Stripe SCA Subscription Button Form === */
	$output  = '';
	$output .= '<div class="swpm-button-wrapper swpm-stripe-buy-now-wrapper">';
	$output .= "<form id='swpm-stripe-payment-form-" . $uniqid . "' action='" . $notify_url . "' METHOD='POST'> ";
	$output .= "<div style='display: none !important'>";

        //Handle script and style loading for the button
	if ( ! wp_script_is( 'swpm.stripe', 'registered' ) ) {
            //In some themes (block themes) this may not have been registered yet since that process can be delayed. So register it now before doing inline script to it.
            wp_register_script("swpm.stripe", "https://js.stripe.com/v3/", array("jquery"), SIMPLE_WP_MEMBERSHIP_VER);
	}
	wp_enqueue_script("swpm.stripe");
	wp_enqueue_style("swpm.stripe.style");

	//initializing stripe for each button, right after loading stripe script
	$stripe_js_obj="stripe_".$button_id;
	wp_add_inline_script("swpm.stripe","var ".$stripe_js_obj." = Stripe('".esc_js( $api_keys['public'] )."');");

	ob_start();
	?>
	<script>
        document.addEventListener('DOMContentLoaded', function (){
            const swpmStripeScaSubsPaymentFrom = document.getElementById('swpm-stripe-payment-form-<?php echo esc_js( $uniqid ); ?>');
            swpmStripeScaSubsPaymentFrom?.addEventListener('submit', async function (e){
                e.preventDefault();

                let submitBUtton = this.querySelector('button');
                if ( ! submitBUtton ){
                    // Using image type button
                    submitBUtton = this.querySelector('input[type="image"]');
                }

                submitBUtton?.setAttribute('disabled', true);

	            const stripe_js_obj = <?php echo $stripe_js_obj;?>;
                const request_url = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
                const payload = new URLSearchParams({
                    action: 'swpm_stripe_sca_create_checkout_session',
                    swpm_button_id: <?php echo esc_js( $button_id ); ?>,
                    payment_method_types: '<?php echo esc_js( $payment_method_types ); ?>',
                    swpm_page_url: '<?php echo esc_js( $current_url ); ?>',
                    swpm_uniqid: '<?php echo esc_js( $uniqid ); ?>'
                });

                try {
                    let response = await fetch(request_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: payload
                    })

                    if (!response.ok) {
                        // HTTP error codes (e.g. 404, 500)
                        throw new Error("Error code: " + response.status);
                    }

                    response = await response.json();

                    if (!response.error) {
                        stripe_js_obj.redirectToCheckout({
                            sessionId: response.session_id
                        }).then(function (result) {
                            //
                        });
                    } else {
                        alert(response.error);
                        submitBUtton?.removeAttribute('disabled');
                        return false;
                    }

                } catch (e) {
                    alert("HTTP error occurred during AJAX request. Error: "+ e.message);
                    submitBUtton?.removeAttribute('disabled');
                    return false;
                }
            })
        })
	</script>
	<?php
	$output .= ob_get_clean();
	$output .= '</div>';

	//apply filter to output additional form fields
	$coupon_input = '';
	$coupon_input = apply_filters( 'swpm_payment_form_additional_fields', $coupon_input, $button_id, $uniqid );
	if ( ! empty( $coupon_input ) ) {
		$output .= $coupon_input;
	}

	$button_image_url = get_post_meta( $button_id, 'button_image_url', true );
	if ( ! empty( $button_image_url ) ) {
		$output .= '<input type="image" src="' . esc_url($button_image_url) . '" class="' .esc_attr($class). '" alt="' . esc_attr($button_text) . '" title="' . esc_attr($button_text) . '" />';
	} else {
		$output .= "<button id='".esc_attr($button_id)."' type='submit' class='".esc_attr($class)."'><span>".esc_attr($button_text)."</span></button>";
	}

	//Filter to add additional payment input fields to the form.
	$output .= apply_filters( 'swpm_stripe_payment_form_additional_fields', '' );

	$output .= '</form>';
	$output .= '</div>'; //End .swpm_button_wrapper

	return $output;}
