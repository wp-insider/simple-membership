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

	$pp_acdc = new SWPM_PayPal_ACDC_Related();
	$client_token = $pp_acdc->generarte_client_token( $environment_mode );
    $currency = isset( $currency ) ? $currency : 'USD';
	$sdk_src_url = SWPM_PayPal_ACDC_Related::get_sdk_src_url_for_acdc( $environment_mode, $currency );

	//TODO - Remove this later. This is just for testing.
	// echo '<br />------------Debug data------------<br />';
	// echo '<p>Client Token: ' . $client_token . '</p>';
	// echo '<p>SDK Source URL: ' . $sdk_src_url . '</p>';
	// echo '<br />------------Debug data------------<br />';

	$output = '';
	ob_start();
	?>
	<link rel="stylesheet" type="text/css" href="https://www.paypalobjects.com/webstatic/en_US/developer/docs/css/cardfields.css" />

	<div class="swpm-button-wrapper swpm-paypal-buy-now-button-wrapper">
		<script src="<?php echo $sdk_src_url; ?>" data-partner-attribution-id="TipsandTricks_SP_PPCP" data-client-token="<?php echo $client_token; ?>"></script>

		<!-- Some additiona hidden input fields -->
		<input type="hidden" id="<?php echo esc_attr( $on_page_embed_button_id . '-custom-field' ); ?>" name="custom" value="<?php echo esc_attr( $custom_field_value ); ?>">

		<table border="0" align="center" valign="top" bgcolor="#FFFFFF" style="width: 39%">
			<tr>
				<td colspan="2">
					<!-- PayPal button container where the button will be rendered -->
					<div id="<?php echo esc_attr( $on_page_embed_button_id ); ?>" style="width: <?php echo esc_attr( $btn_width ); ?>px;"></div>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
		</table>
		<div align="center"> or </div>
		<div class="card_container">
			<form id="card-form">
				<label for="card-number">Card Number</label>
				<div id="card-number" class="card_field"></div>
				<div>
					<label for="expiration-date">Expiration Date</label>
					<div id="expiration-date" class="card_field"></div>
				</div>
				<div>
					<label for="cvv">CVV</label>
					<div id="cvv" class="card_field"></div>
				</div>
				<label for="card-holder-name">Name on Card</label>
				<input type="text" id="card-holder-name" name="card-holder-name" autocomplete="off"
					placeholder="card holder name" />
				<div>
					<label for="card-billing-address-street">Billing Address</label>
					<input type="text" id="card-billing-address-street" name="card-billing-address-street"
						autocomplete="off" placeholder="street address" />
				</div>
				<div>
					<label for="card-billing-address-unit">&nbsp;</label>
					<input type="text" id="card-billing-address-unit" name="card-billing-address-unit" autocomplete="off"
						placeholder="unit" />
				</div>
				<div>
					<input type="text" id="card-billing-address-city" name="card-billing-address-city" autocomplete="off"
						placeholder="city" />
				</div>
				<div>
					<input type="text" id="card-billing-address-state" name="card-billing-address-state" autocomplete="off"
						placeholder="state" />
				</div>
				<div>
					<input type="text" id="card-billing-address-zip" name="card-billing-address-zip" autocomplete="off"
						placeholder="zip / postal code" />
				</div>
				<div>
					<input type="text" id="card-billing-address-country" name="card-billing-address-country"
						autocomplete="off" placeholder="country code" />
				</div>
				<br /><br />
				<button value="submit" id="submit" class="btn">Pay</button>
			</form>
		</div>
		<script type="text/javascript">
		jQuery(function ($) {
			let orderId;
			paypal.Buttons({
				style: {
					layout: 'horizontal'
				},
				createOrder: function (data, actions) {
					return actions.order.create({
						purchase_units: [{
							amount: {
								value: "1.00"
							}
						}]
					});
				},
				onApprove: function (data, actions) {
					return actions.order.capture().then(function (details) {
						window.location.href = '/success.html';
					});
				}
			//}).render("#paypal-button-container");
			}).render('#<?php echo esc_js( $on_page_embed_button_id ); ?>');
			if (paypal.HostedFields.isEligible()) {
				paypal.HostedFields.render({
					createOrder: function () {
						//Set up the transaction by creating a `paypal.Order`.
						//The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.
						console.log('Setting up the order for ACDC.');
						let data = {};
						data.button_id = '<?php echo esc_js($button_id); ?>';
						data.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
						data.item_name = '<?php echo esc_js($item_name); ?>';
						//console.log('Data: ' + JSON.stringify(data));
                        jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>', { action: 'swpm_acdc_setup_order', data: data, _wpnonce: '<?php echo $wp_nonce; ?>'}, function( response ) {
                            console.log( 'Response from the server: ' + JSON.stringify( response ) );
                            if ( response.success ) {
                                //Success response.
								//TODO - Store the order ID in a variable then return it for the SDK to use.
								orderId = response.order_id;
								console.log('Order ID: ' + orderId);
								return response.order_id;
                            } else {
                                //Error response from the AJAX hanler. Show the error message.
                                console.log( 'Error response: ' + JSON.stringify( response.err_msg ) );
                                alert( JSON.stringify( response ) );
								return;
                            }

                            //Return the button and the spinner back to their orignal display state.
                            //pp_button_container.show();//Show the buttons
                            //pp_button_spinner_conainer.hide();//Hide the spinner.

                        });
					},
					styles: {
						'.valid': {
							'color': 'green'
						},
						'.invalid': {
							'color': 'red'
						}
					},
					fields: {
						number: {
							selector: "#card-number",
							placeholder: "4111 1111 1111 1111"
						},
						cvv: {
							selector: "#cvv",
							placeholder: "123"
						},
						expirationDate: {
							selector: "#expiration-date",
							placeholder: "MM/YY"
						}
					}
				}).then(function (cardFields) {
					document.querySelector("#card-form").addEventListener('submit', (event) => {
console.log('Card fields submitted.');
						event.preventDefault();
						cardFields.submit({
							cardholderName: document.getElementById('card-holder-name').value,
							billingAddress: {
								streetAddress: document.getElementById('card-billing-address-street').value,
								extendedAddress: document.getElementById('card-billing-address-unit').value,
								region: document.getElementById('card-billing-address-state').value,
								locality: document.getElementById('card-billing-address-city').value,
								postalCode: document.getElementById('card-billing-address-zip').value,
								countryCodeAlpha2: document.getElementById('card-billing-address-country').value
							}
						}).then(function () {
console.log('Going to execute the capture order code.');//<-- This is not executing.

							const formData = new FormData();
formData.append('action', 'swpm_acdc_capture_order');
formData.append('order_id', orderId);
formData.append('on_page_button_id', '<?php echo esc_js($on_page_embed_button_id); ?>');
formData.append('_wpnonce', '<?php echo $wp_nonce; ?>');
console.log( 'Going to do capture order AJAX. Form Data: ' +  JSON.stringify(formData) );
							fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
								method: 'post',
								body: formData,
							}).then(function (res) {
								console.log( 'Capture response: ' +  JSON.stringify(res) );
								return res.json();
							}).then(function (orderData) {
								console.log( 'Capture result from server: ', JSON.stringify(orderData) );

								var errorDetail = Array.isArray(orderData.details) && orderData.details[0];
								if (errorDetail) {
									var msg = 'Sorry, your transaction could not be processed.';
									if (errorDetail.description) msg += '' + errorDetail.description;
									if (orderData.debug_id) msg += ' (' + orderData.debug_id + ')';
									return alert(msg); // Show a failure message
								}
								alert('Transaction completed!');
							})
						}).catch(function (err) {
							alert('Payment could not be captured! ' + JSON.stringify(err))
						});
					});
				});
			} else {
				document.querySelector("#card-form").style = 'display: none';
			}
		});
		</script>
	</div><!-- end of .swpm-button-wrapper -->
	<?php
	$output .= ob_get_clean();

	return $output;

}