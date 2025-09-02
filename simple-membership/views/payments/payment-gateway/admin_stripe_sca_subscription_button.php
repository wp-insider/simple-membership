<?php
/* * ***************************************************************
 * Render the new Stripe Subscription payment button creation interface
 * ************************************************************** */

function swpm_render_new_edit_stripe_sca_subscription_button_interface( $opts, $edit = false ) {

	//Test for PHP v5.6.0 or show error and don't show the remaining interface.
	if ( version_compare( PHP_VERSION, '5.6.0' ) < 0 ) {
		//This server can't handle Stripe library
		echo '<div class="swpm-red-box">';
		echo '<p>'.__('The Stripe payment gateway libary requires at least PHP 5.6.0. Your server is using a very old version of PHP that Stripe does not support.', 'simple-membership').'</p>';
		echo '<p>'.__('Request your hosting provider to upgrade your PHP to a more recent version then you will be able to use the Stripe gateway.', 'simple-membership').'<p>';
		echo '</div>';
		return;
	}

	if ( isset( $opts['stripe_use_global_keys'][0] ) ) {
		$use_global_keys = $opts['stripe_use_global_keys'][0];
	} else {
		$use_global_keys = $edit ? false : true;
	}

	$stripe_test_publishable_key = isset( $opts['stripe_test_publishable_key'][0] ) ? $opts['stripe_test_publishable_key'][0] : '';
	$stripe_test_secret_key      = isset( $opts['stripe_test_secret_key'][0] ) ? $opts['stripe_test_secret_key'][0] : '';

	$stripe_live_publishable_key = isset( $opts['stripe_live_publishable_key'][0] ) ? $opts['stripe_live_publishable_key'][0] : '';
	$stripe_live_secret_key      = isset( $opts['stripe_live_secret_key'][0] ) ? $opts['stripe_live_secret_key'][0] : '';

	function swpm_stripe_sca_subscr_gen_curr_opts( $selected = false ) {
		$curr_arr = array(
			'USD' => 'US Dollars ($)',
			'EUR' => 'Euros (€)',
			'GBP' => 'Pounds Sterling (£)',
			'AUD' => 'Australian Dollars ($)',
			'BRL' => 'Brazilian Real (R$)',
			'CAD' => 'Canadian Dollars ($)',
			'CNY' => 'Chinese Yuan',
			'CZK' => 'Czech Koruna',
			'DKK' => 'Danish Krone',
			'HKD' => 'Hong Kong Dollar ($)',
			'HUF' => 'Hungarian Forint',
			'INR' => 'Indian Rupee',
			'IDR' => 'Indonesia Rupiah',
			'ILS' => 'Israeli Shekel',
			'JPY' => 'Japanese Yen (¥)',
			'MYR' => 'Malaysian Ringgits',
			'MXN' => 'Mexican Peso ($)',
			'NZD' => 'New Zealand Dollar ($)',
			'NOK' => 'Norwegian Krone',
			'PHP' => 'Philippine Pesos',
			'PLN' => 'Polish Zloty',
			'SGD' => 'Singapore Dollar ($)',
			'ZAR' => 'South African Rand (R)',
			'KRW' => 'South Korean Won',
			'SEK' => 'Swedish Krona',
			'CHF' => 'Swiss Franc',
			'TWD' => 'Taiwan New Dollars',
			'THB' => 'Thai Baht',
			'TRY' => 'Turkish Lira',
			'VND' => 'Vietnamese Dong',
		);
		$out      = '';
		foreach ( $curr_arr as $key => $value ) {
			if ( $selected !== false && $selected == $key ) {
				$sel = ' selected';
			} else {
				$sel = '';
			}
			$out .= '<option value="' . $key . '"' . $sel . '>' . $value . '</option>';
		}
		return $out;
	}

	$button_type = isset($_REQUEST['button_type']) ? sanitize_text_field($_REQUEST['button_type']) : '';

	?>

<div class="swpm-orange-box">
	<?php _e('View the', 'simple-membership') ?> <a target="_blank" href="https://simple-membership-plugin.com/sca-compliant-stripe-subscription-button/"><?php _e('documentation', 'simple-membership') ?></a>&nbsp;
	<?php _e('to learn how to create a Stripe Subscription payment button and use it.', 'simple-membership') ?>
</div>

<form id="stripe_sca_subsciption_button_config_form" method="post">

	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e( 'Stripe Subscription Button Configuration' , 'simple-membership'); ?></label></h3>
		<div class="inside">
			<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
				<?php if ( ! $edit ) { ?>
				<input type="hidden" name="button_type" value="<?php echo esc_attr( $button_type ); ?>">
				<input type="hidden" name="swpm_button_type_selected" value="1">
				<?php } else { ?>
				<tr valign="top">
					<th scope="row"><?php _e( 'Button ID' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="10" name="button_id" value="<?php echo esc_attr($opts['button_id']); ?>" readonly required />
						<p class="description"><?php _e('This is the ID of this payment button. It is automatically generated for you and it cannot be changed.', 'simple-membership') ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Button Shortcode', 'simple-membership'); ?></th>
					<td>
						<?php
						$shortcode = SwpmUtils::get_shortcode_for_admin_ui_display($opts['button_id'], 50);
						echo $shortcode;
						?>
						<p class="description">
							<?php _e('Use this shortcode to embed the payment button in your posts, pages, or on your ', 'simple-membership') ?>
							<?php echo '<a href="https://simple-membership-plugin.com/membership-join-us-page/" target="_blank">' . __('join-us landing page', 'simple-membership') . '</a>.' ?>
						</p>
					</td>
				</tr>				
				<?php } ?>
				<tr valign="top">
					<th scope="row"><?php _e( 'Button Title' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="50" name="button_name" value="<?php echo ( $edit ? esc_attr($opts['button_title']) : '' ); ?>" required />
						<p class="description"><?php _e('Give this membership payment button a name. Example: Gold membership payment', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Membership Level' , 'simple-membership'); ?></th>
					<td>
						<select id="membership_level_id" name="membership_level_id">
							<?php echo ( $edit ? SwpmUtils::membership_level_dropdown( $opts['membership_level_id'][0] ) : SwpmUtils::membership_level_dropdown() ); ?>
						</select>
						<p class="description"><?php _e('Select the membership level this payment button is for.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Stripe Price/API ID' , 'simple-membership'); ?></th>
					<td>
						<input type="text" name="stripe_plan_id" value="<?php echo ( $edit ? esc_attr($opts['stripe_plan_id'][0]) : '' ); ?>" required />
						<p class="description">
							<?php _e('ID of the plan that you want subscribers to be assigned to. You can get more details in the', 'simple-membership') ?>
							<a href="https://simple-membership-plugin.com/sca-compliant-stripe-subscription-button/" target="_blank"><?php _e('documentation', 'simple-membership') ?></a>.
						</p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Trial Period' , 'simple-membership'); ?></th>
					<td>
						<input type="number" min="0" name="stripe_trial_period" value="<?php echo $edit ? esc_attr( $opts['stripe_trial_period'][0] ) : ''; ?>" /> <?php _e('days', 'simple-membership') ?>
						<p class="description"><?php _e('If you want to use a trial period then enter the number of days in this field. Subscriptions to this plan will automatically start after that. If left blank or 0, trial period is disabled.', 'simple-membership') ?></p>
					</td>
				</tr>

			</table>

		</div>
	</div><!-- end of main button configuration box -->

	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e( 'Stripe API Settings' , 'simple-membership'); ?></label></h3>
		<div class="inside">

			<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">

				<tr valign="top">
					<th scope="row"><?php _e( 'Use Global API Keys Settings' , 'simple-membership'); ?></th>
					<td>
						<input type="checkbox" name="stripe_use_global_keys" value="1" <?php echo $edit ? ( $use_global_keys ? ' checked' : '' ) : ' checked'; ?> />
						<p class="description"><?php _e( 'Use API keys from <a href="admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_stripe" target="_blank">Payment Settings</a> tab.', 'simple-membership' ); ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Test Publishable Key' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="stripe_test_publishable_key" value="<?php echo esc_attr( $edit ? $stripe_test_publishable_key : '' ); ?>" />
						<p class="description"><?php _e('Enter your Stripe test publishable key.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Test Secret Key' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="stripe_test_secret_key" value="<?php echo esc_attr( $edit ? $stripe_test_secret_key : '' ); ?>" />
						<p class="description"><?php _e('Enter your Stripe test secret key.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Live Publishable Key' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="stripe_live_publishable_key" value="<?php echo esc_attr( $edit ? $stripe_live_publishable_key : '' ); ?>" />
						<p class="description"><?php _e('Enter your Stripe live publishable key.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Live Secret Key' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="stripe_live_secret_key" value="<?php echo esc_attr( $edit ? $stripe_live_secret_key : '' ); ?>" />
						<p class="description"><?php _e('Enter your Stripe live secret key.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Webhook Endpoint URL' , 'simple-membership'); ?></th>
					<td>
						<kbd><?php echo SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_stripe_subscription=1&hook=1'; ?></kbd>
						<p class="description">
							<?php _e('You should create a new Webhook in your Stripe account and put this URL there. You can get more info in the', 'simple-membership') ?> <a href="https://simple-membership-plugin.com/sca-compliant-stripe-subscription-button/" target="_blank"><?php _e('documentation', 'simple-membership') ?></a>.
						</p>
					</td>
				</tr>

				<script>
				var swpmInputsArr = ['stripe_test_publishable_key', 'stripe_test_secret_key', 'stripe_live_publishable_key', 'stripe_live_secret_key'];
				jQuery('input[name="stripe_use_global_keys"').change(function() {
					var checked = jQuery(this).prop('checked');
					jQuery.each(swpmInputsArr, function(index, el) {
						jQuery('input[name="' + el + '"]').prop('disabled', checked);
					});
				});
				jQuery('input[name="stripe_use_global_keys"').trigger('change');
				</script>

			</table>
		</div>
	</div><!-- end of Stripe API Keys box -->

	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e( 'Optional Details' , 'simple-membership'); ?></label></h3>
		<div class="inside">

			<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">

				<tr valign="top">
					<th scope="row"><?php _e( 'Collect Customer Address' , 'simple-membership'); ?></th>
					<td>
						<input type="checkbox" name="collect_address" value="1" <?php echo ( $edit ? ( ( isset( $opts['stripe_collect_address'][0] ) && $opts['stripe_collect_address'][0] === '1' ) ? ' checked' : '' ) : '' ); ?> />
						<p class="description"><?php _e('Enable this option if you want to collect customer address during Stripe checkout.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Enable Automatic Tax' , 'simple-membership'); ?></th>
					<td>
					<input type="checkbox" name="automatic_tax" value="1" <?php echo ( $edit ? ( ( isset( $opts['stripe_automatic_tax'][0] ) && $opts['stripe_automatic_tax'][0] === '1' ) ? ' checked' : '' ) : '' ); ?> />
						<p class="description"><?php _e('Enable this option if you want to enable automatic tax feature of Stripe. You will need to enable this feature in your Stripe account before using it.', 'simple-membership') ?></p>
					</td>
				</tr>

                <tr valign="top">
                    <th scope="row"><?php _e( 'Allow Promotion Codes', 'simple-membership'); ?></th>
                    <td>
                        <input type="checkbox" name="allow_promotion_codes" value="1" <?php echo ( $edit ? ( ( isset( $opts['allow_promotion_codes'][0] ) && $opts['allow_promotion_codes'][0] === '1' ) ? ' checked' : '' ) : '' ); ?> />
						<p class="description">
							<?php _e('Enable this option if you want to use the promotion codes feature of Stripe. You will need to enable this feature in your Stripe account before using it.', 'simple-membership') ?>
							<?php echo '<a href="https://simple-membership-plugin.com/applying-discount-coupon-or-promotion-codes-to-stripe-payment-buttons/" target="_blank">' . __('Learn more', 'simple-membership') . '</a>.'; ?>
						</p>
                    </td>
                </tr>

				<?php
				    $redirect_to_paid_reg_link_after_payment = isset( $opts['redirect_to_paid_reg_link_after_payment'][0] ) && !empty($opts['redirect_to_paid_reg_link_after_payment'][0]) ? true : false;
				?>

                <tr valign="top">
                    <th scope="row"><?php _e( 'Redirect to Paid Registration Link', 'simple-membership'); ?></th>
                    <td>
                        <input type="checkbox" name="redirect_to_paid_reg_link_after_payment" value="1" <?php echo esc_attr( $edit && $redirect_to_paid_reg_link_after_payment ? 'checked' : '' ); ?> />
						<p class="description">
							<?php _e('Enable this option to automatically redirect the user to the unique paid registration link after a successful payment. ', 'simple-membership') ?>
							<a href="https://simple-membership-plugin.com/automatically-redirect-users-to-the-paid-registration-link-after-payment/" target="_blank"><?php _e('Read this documentation', 'simple-membership') ?></a>
							<?php _e(' to learn how it works.', 'simple-membership') ?>
						</p>
                    </td>
                </tr>

                <tr valign="top">
					<th scope="row"><?php _e( 'Return URL' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="return_url" value="<?php echo ( $edit ? esc_url_raw($opts['return_url'][0]) : '' ); ?>" />
                        <p class="description"><?php _e('This is the URL the user will be redirected to after a successful payment. Enter the URL of your Thank You page here.', 'simple-membership') ?></p>

						<?php if ($redirect_to_paid_reg_link_after_payment) { ?>
							<p class="description">
								<strong><?php esc_attr_e('Note: ', 'simple-membership'); ?></strong> <?php esc_attr_e("The 'Redirect to Paid Registration Link' option is enabled for this button. Unregistered users will be redirected to the paid registration page after completing payment.", 'simple-membership'); ?>
							</p>
						<?php } ?>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Cancel URL' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="cancel_url" value="<?php echo $edit && isset($opts['cancel_url'][0]) && !empty($opts['cancel_url'][0]) ? esc_url_raw($opts['cancel_url'][0] ) : '' ?>" />
						<p class="description"><?php _e('This is the URL the user will be redirected to when a payment is canceled. Enter the URL of your preferred page here.', 'simple-membership') ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Button Image URL' , 'simple-membership'); ?></th>
					<td>
						<input type="text" size="100" name="button_image_url" value="<?php echo ( $edit ? esc_url_raw($opts['button_image_url'][0]) : '' ); ?>" />
						<p class="description"><?php _e('If you want to customize the look of the button using an image then enter the URL of the image.', 'simple-membership') ?></p>
					</td>
				</tr>

			</table>
		</div>
	</div><!-- end of optional details box -->

	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e( 'Active Subscription Check' , 'simple-membership'); ?></label></h3>
		<div class="inside">

			<p class="description">
				<?php _e('The options in this section allow you to check if the logged-in member already has an active subscription and display a warning message if needed.', 'simple-membership') ?>
			</p>

			<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">

                <tr valign="top">
                    <th scope="row"><?php _e( 'Show Warning for Existing Subscription' , 'simple-membership'); ?></th>
                    <td>
                        <input type="checkbox" name="show_warning_if_any_active_sub" value="1" <?php echo ( $edit ? ( ( isset( $opts['show_warning_if_any_active_sub'][0] ) && $opts['show_warning_if_any_active_sub'][0] === '1' ) ? ' checked' : '' ) : '' ); ?> />
                        <p class="description"><?php _e('Enable this option to display a warning message next to the subscription button if the logged-in member already has an active subscription.', 'simple-membership') ?></p>

						<br />
						<p><strong><?php _e( 'Message to Display for Existing Subscription' , 'simple-membership'); ?></strong></p>
                        <input type="text" size="100" name="warning_msg_for_existing_sub" value="<?php echo ( $edit && isset($opts['warning_msg_for_existing_sub'][0]) ? esc_attr($opts['warning_msg_for_existing_sub'][0]) : '' ); ?>" />
                        <p class="description">
							<?php 
							_e('Enter the custom message you want to show to logged-in members who already have an active subscription.', 'simple-membership');
							echo ' ' . __('You may include a link to a page with ', 'simple-membership');
							echo '<a href="https://simple-membership-plugin.com/show-active-subscriptions-and-providing-a-cancellation-option/" target="_blank">' . __('self-cancellation instructions', 'simple-membership') . '</a>.';
							?>
						</p>

						<br />
						<input type="checkbox" name="hide_btn_if_any_active_sub" value="1" <?php echo ( $edit ? ( ( isset( $opts['hide_btn_if_any_active_sub'][0] ) && $opts['hide_btn_if_any_active_sub'][0] === '1' ) ? ' checked' : '' ) : '' ); ?> />
						<strong><?php echo ' ' . __( 'Hide Payment Button if Another Subscription is Active' , 'simple-membership'); ?></strong>
						<p class="description">
							<?php _e('Check this option to hide the subscription button when the warning message is displayed for an existing active subscription.', 'simple-membership') ?>
						</p>
                    </td>
                </tr>

			</table>
		</div>
	</div><!-- end of Active Subscription Check box -->

	<p class="submit">
		<?php wp_nonce_field( 'swpm_admin_add_edit_stripe_sca_subs_btn', 'swpm_admin_add_edit_stripe_sca_subs_btn' ); ?>
		<input type="submit" name="swpm_stripe_sca_subscription_<?php echo ( $edit ? 'edit' : 'save' ); ?>_submit" class="button-primary" value="<?php _e( 'Save Payment Data' , 'simple-membership'); ?>">
	</p>

</form>

	<?php
}

add_action( 'swpm_create_new_button_for_stripe_sca_subscription', 'swpm_create_new_stripe_sca_subscription_button' );

function swpm_create_new_stripe_sca_subscription_button() {
	swpm_render_new_edit_stripe_sca_subscription_button_interface( '' );
}

add_action( 'swpm_edit_payment_button_for_stripe_sca_subscription', 'swpm_edit_stripe_sca_subscription_button' );

function swpm_edit_stripe_sca_subscription_button() {
	//Retrieve the payment button data and present it for editing.

	$button_id = sanitize_text_field( $_REQUEST['button_id'] );
	$button_id = absint( $button_id );

	$button = get_post( $button_id ); //Retrieve the CPT for this button

	$post_meta                 = get_post_meta( $button_id );
	$post_meta['button_title'] = $button->post_title;
	$post_meta['button_id']    = $button_id;

	swpm_render_new_edit_stripe_sca_subscription_button_interface( $post_meta, true );
}

/*
 * Process submission and save the new PayPal Subscription payment button data
 */
add_action( 'swpm_create_new_button_process_submission', 'swpm_save_edit_stripe_sca_subscription_button_data' );
add_action( 'swpm_edit_payment_button_process_submission', 'swpm_save_edit_stripe_sca_subscription_button_data' );

function swpm_save_edit_stripe_sca_subscription_button_data() {

	if ( isset( $_REQUEST['swpm_stripe_sca_subscription_save_submit'] ) ) {
		$edit = false;
	}
	if ( isset( $_REQUEST['swpm_stripe_sca_subscription_edit_submit'] ) ) {
		$edit = true;
	}
	if ( isset( $edit ) ) {
		//This is a Stripe subscription button save or edit event. Process the submission.
		check_admin_referer( 'swpm_admin_add_edit_stripe_sca_subs_btn', 'swpm_admin_add_edit_stripe_sca_subs_btn' );
		if ( $edit ) {
			$button_id   = sanitize_text_field( $_REQUEST['button_id'] );
			$button_id   = absint( $button_id );
			$button_type = sanitize_text_field( $_REQUEST['button_type'] );
			$button_name = sanitize_text_field( $_REQUEST['button_name'] );

			$button_post = array(
				'ID'         => $button_id,
				'post_title' => $button_name,
				'post_type'  => 'swpm_payment_button',
			);
			wp_update_post( $button_post );
		} else {
			$button_id   = wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $_REQUEST['button_name'] ),
					'post_type'    => 'swpm_payment_button',
					'post_content' => '',
					'post_status'  => 'publish',
				)
			);
			$button_type = sanitize_text_field( $_REQUEST['button_type'] );
		}

		update_post_meta( $button_id, 'button_type', $button_type );
		update_post_meta( $button_id, 'membership_level_id', sanitize_text_field( $_REQUEST['membership_level_id'] ) );
		update_post_meta( $button_id, 'return_url', trim( sanitize_text_field( $_REQUEST['return_url'] ) ) );

		$cancel_url = isset($_POST['cancel_url']) && !empty($_POST['cancel_url']) ? trim(sanitize_text_field($_POST['cancel_url'])) : '';
		update_post_meta( $button_id, 'cancel_url',  $cancel_url );

		update_post_meta( $button_id, 'button_image_url', trim( sanitize_text_field( $_REQUEST['button_image_url'] ) ) );
		update_post_meta( $button_id, 'stripe_collect_address', isset( $_POST['collect_address'] ) ? '1' : '' );
		update_post_meta( $button_id, 'stripe_automatic_tax', isset( $_POST['automatic_tax'] ) ? '1' : '' );
		update_post_meta( $button_id, 'allow_promotion_codes', isset( $_POST['allow_promotion_codes'] ) ? '1' : '' );
		update_post_meta( $button_id, 'redirect_to_paid_reg_link_after_payment', isset( $_POST['redirect_to_paid_reg_link_after_payment'] ) ? '1' : '' );

		update_post_meta( $button_id, 'show_warning_if_any_active_sub', isset( $_POST['show_warning_if_any_active_sub'] ) ? $_POST['show_warning_if_any_active_sub'] : '' );
		update_post_meta( $button_id, 'hide_btn_if_any_active_sub', isset( $_POST['hide_btn_if_any_active_sub'] ) ? $_POST['hide_btn_if_any_active_sub'] : '' );
		update_post_meta( $button_id, 'warning_msg_for_existing_sub', isset( $_POST['warning_msg_for_existing_sub'] ) ? wp_kses_post($_POST['warning_msg_for_existing_sub']) : '' );

		//API details
		$stripe_test_secret_key = isset( $_POST['stripe_test_secret_key'] ) ? sanitize_text_field( stripslashes ( $_POST['stripe_test_secret_key'] ) ) : '';
		$stripe_test_publishable_key = isset( $_POST['stripe_test_publishable_key'] ) ? sanitize_text_field( stripslashes ( $_POST['stripe_test_publishable_key'] ) ) : '';
		$stripe_live_secret_key = isset( $_POST['stripe_live_secret_key'] ) ? sanitize_text_field( stripslashes ( $_POST['stripe_live_secret_key'] ) ) : '';
		$stripe_live_publishable_key = isset( $_POST['stripe_live_publishable_key'] ) ? sanitize_text_field( stripslashes ( $_POST['stripe_live_publishable_key'] ) ) : '';

		if ( isset( $stripe_test_secret_key ) ) {
			update_post_meta( $button_id, 'stripe_test_secret_key', sanitize_text_field( $stripe_test_secret_key ) );
		}
		if ( isset( $stripe_test_publishable_key ) ) {
			update_post_meta( $button_id, 'stripe_test_publishable_key', sanitize_text_field( $stripe_test_publishable_key ) );
		}
		if ( isset( $stripe_live_secret_key ) ) {
			update_post_meta( $button_id, 'stripe_live_secret_key', sanitize_text_field( $stripe_live_secret_key ) );
		}
		if ( isset( $stripe_live_publishable_key ) ) {
			update_post_meta( $button_id, 'stripe_live_publishable_key', sanitize_text_field( $stripe_live_publishable_key ) );
		}

		$stripe_use_global_keys = filter_input( INPUT_POST, 'stripe_use_global_keys', FILTER_SANITIZE_NUMBER_INT );
		$stripe_use_global_keys = $stripe_use_global_keys ? true : false;

		update_post_meta( $button_id, 'stripe_use_global_keys', $stripe_use_global_keys );

		if ( $edit ) {
			// let's see if Stripe details (plan ID and Secret Key) are valid
			$stripe_error_msg = '';
			$settings         = SwpmSettings::get_instance();
			$sandbox_enabled  = $settings->get_value( 'enable-sandbox-testing' );
			if ( $sandbox_enabled ) {
				$secret_key = $stripe_test_secret_key ? $stripe_test_secret_key : $settings->get_value( 'stripe-test-secret-key' );
			} else {
				$secret_key = $stripe_live_secret_key ? $stripe_live_secret_key : $settings->get_value( 'stripe-live-secret-key' );
			}

			require_once SIMPLE_WP_MEMBERSHIP_PATH . 'lib/stripe-util-functions.php';
			$result = StripeUtilFunctions::get_stripe_plan_info( $secret_key, sanitize_text_field( $_REQUEST['stripe_plan_id'] ) );
			if ( $result['success'] ) {
				$plan_data = $result['plan_data'];
			} else {
				$stripe_error_msg = $result['error_msg'];
			}
		}

		//Subscription billing details
		update_post_meta( $button_id, 'stripe_plan_id', sanitize_text_field( $_REQUEST['stripe_plan_id'] ) );
		update_post_meta( $button_id, 'stripe_trial_period', sanitize_text_field( $_REQUEST['stripe_trial_period'] ) );
		update_post_meta( $button_id, 'stripe_plan_data', ( isset( $plan_data ) ? $plan_data : false ) );

		if ( $edit ) {
			if ( empty( $stripe_error_msg ) ) {
				echo '<div id="message" class="updated fade"><p>'.__('Payment button data successfully updated!', 'simple-membership').'</p></div>';
			} else {
				echo '<div id="message" class="error"><p>' . $stripe_error_msg . '</p></div>';
			}
		} else {
			//Redirect to the manage payment buttons interface
                        $url = admin_url() . 'admin.php?page=simple_wp_membership_payments&tab=payment_buttons';
                        SwpmMiscUtils::redirect_to_url( $url );
		}
	}
}
