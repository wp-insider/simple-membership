<?php

/*
It uses the following action hooks to render the button's form then process the form submission.
do_action( 'swpm_create_new_button_process_submission' );//To handle the save/create new operation
do_action( 'swpm_edit_payment_button_process_submission' );//To handle the edit operation
do_action( 'swpm_create_new_button_for_' . $button_type );//To render the HTML form for the create/edit button
*/

/*
The functions has been optimized to avoid code duplication.
This function is responsible for rendering either Save or Edit button interface depending on the parameters.
It's much easier to modify it as the changes (descriptions update etc) are reflected in both forms at once.
 */

/*************************************************************************************
 * Start of process submission: save or edit PayPal subscription (New) payment button data
 *************************************************************************************/
function swpm_save_edit_pp_subscription_new_button_data() {

    $button_name = isset($_REQUEST['button_name']) ? sanitize_text_field($_REQUEST['button_name']) : '';

    $btn_type = isset($_POST['pp_subscription_new_btn_type']) ? sanitize_text_field($_POST['pp_subscription_new_btn_type']) : '';
    $btn_shape = isset($_POST['pp_subscription_new_btn_shape']) ? sanitize_text_field($_POST['pp_subscription_new_btn_shape']) : '';
    $btn_layout = isset($_POST['pp_subscription_new_btn_layout']) ? sanitize_text_field($_POST['pp_subscription_new_btn_layout']) : '';
    $btn_height = isset($_POST['pp_subscription_new_btn_height']) ? sanitize_text_field($_POST['pp_subscription_new_btn_height']) : '';
    $btn_width = isset($_POST['pp_subscription_new_btn_width']) ? sanitize_text_field($_POST['pp_subscription_new_btn_width']) : '';
    $btn_color = isset($_POST['pp_subscription_new_btn_color']) ? sanitize_text_field($_POST['pp_subscription_new_btn_color']) : '';

    $disable_funding_card = isset($_POST['pp_subscription_new_disable_funding_card']) ? sanitize_text_field($_POST['pp_subscription_new_disable_funding_card']) : '';
    $disable_funding_credit = isset($_POST['pp_subscription_new_disable_funding_credit']) ? sanitize_text_field($_POST['pp_subscription_new_disable_funding_credit']) : '';
    $disable_funding_venmo = isset($_POST['pp_subscription_new_disable_funding_venmo']) ? sanitize_text_field($_POST['pp_subscription_new_disable_funding_venmo']) : '';
    $redirect_to_paid_reg_link_after_payment = isset($_POST['redirect_to_paid_reg_link_after_payment']) ? sanitize_text_field($_POST['redirect_to_paid_reg_link_after_payment']) : '';

    //Process form submission
    if (isset($_REQUEST['swpm_pp_subscription_new_save_submit'])) {
        //This is a PayPal Subscription (New) button creation/save event.

        check_admin_referer( 'swpm_admin_add_edit_pp_subscription_new_btn', 'swpm_admin_add_edit_pp_subscription_new_btn' );

        $button_id = wp_insert_post(
                array(
                    'post_title' => $button_name,
                    'post_type' => 'swpm_payment_button',
                    'post_content' => '',
                    'post_status' => 'publish'
                )
        );

        add_post_meta($button_id, 'button_type', sanitize_text_field($_REQUEST['button_type']));
        add_post_meta($button_id, 'membership_level_id', sanitize_text_field($_REQUEST['membership_level_id']));

        add_post_meta($button_id, 'payment_currency', trim(sanitize_text_field($_REQUEST['payment_currency']))); 
        add_post_meta($button_id, 'recurring_billing_amount', trim(sanitize_text_field($_REQUEST['recurring_billing_amount'])));
        add_post_meta($button_id, 'recurring_billing_cycle', trim(sanitize_text_field($_REQUEST['recurring_billing_cycle'])));
        add_post_meta($button_id, 'recurring_billing_cycle_term', trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_term'])));
        add_post_meta($button_id, 'recurring_billing_cycle_count', trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_count'])));
        add_post_meta($button_id, 'recurring_billing_reattempt', isset($_REQUEST['recurring_billing_reattempt']) ? '1' : '');

        add_post_meta($button_id, 'trial_billing_amount', trim(sanitize_text_field($_REQUEST['trial_billing_amount'])));
        add_post_meta($button_id, 'trial_billing_cycle', trim(sanitize_text_field($_REQUEST['trial_billing_cycle'])));
        add_post_meta($button_id, 'trial_billing_cycle_term', trim(sanitize_text_field($_REQUEST['trial_billing_cycle_term'])));

        add_post_meta($button_id, 'pp_subscription_new_btn_type', $btn_type);
        add_post_meta($button_id, 'pp_subscription_new_btn_shape', $btn_shape);
        add_post_meta($button_id, 'pp_subscription_new_btn_layout', $btn_layout);        
        add_post_meta($button_id, 'pp_subscription_new_btn_height', $btn_height);
        add_post_meta($button_id, 'pp_subscription_new_btn_width', $btn_width);
        add_post_meta($button_id, 'pp_subscription_new_btn_color', $btn_color);

        add_post_meta($button_id, 'pp_subscription_new_disable_funding_card', $disable_funding_card);
        add_post_meta($button_id, 'pp_subscription_new_disable_funding_credit', $disable_funding_credit);
        add_post_meta($button_id, 'pp_subscription_new_disable_funding_venmo', $disable_funding_venmo);

        add_post_meta($button_id, 'redirect_to_paid_reg_link_after_payment', $redirect_to_paid_reg_link_after_payment );

        add_post_meta($button_id, 'return_url', trim(sanitize_text_field($_REQUEST['return_url'])));

	    // Active subscription check settings.
        update_post_meta( $button_id, 'show_warning_if_any_active_sub', isset( $_REQUEST['show_warning_if_any_active_sub'] ) ? $_REQUEST['show_warning_if_any_active_sub'] : '' );
	    update_post_meta( $button_id, 'hide_btn_if_any_active_sub', isset( $_REQUEST['hide_btn_if_any_active_sub'] ) ? $_REQUEST['hide_btn_if_any_active_sub'] : '' );
	    update_post_meta( $button_id, 'warning_msg_for_existing_sub', isset( $_REQUEST['warning_msg_for_existing_sub'] ) ? wp_kses_post($_REQUEST['warning_msg_for_existing_sub']) : '' );

	    //Check if webhooks already configured for this site. If not, create necessary webhooks.
		SWPM_PayPal_Utility_Functions::check_and_create_webhooks_for_this_site();

        //Redirect to the manage payment buttons interface
        $url = admin_url() . 'admin.php?page=simple_wp_membership_payments&tab=payment_buttons';
        SwpmMiscUtils::redirect_to_url($url);
    }

    if (isset($_REQUEST['swpm_pp_subscription_new_edit_submit'])) {
        //This is a PayPal Subscription (New) button edit event.

        check_admin_referer( 'swpm_admin_add_edit_pp_subscription_new_btn', 'swpm_admin_add_edit_pp_subscription_new_btn' );

        $button_id = sanitize_text_field($_REQUEST['button_id']);
        $button_id = absint($button_id);

        $button_post = array(
            'ID' => $button_id,
            'post_title' => $button_name,
            'post_type' => 'swpm_payment_button',
        );
        wp_update_post($button_post);

        update_post_meta($button_id, 'button_type', sanitize_text_field($_REQUEST['button_type']));
        update_post_meta($button_id, 'membership_level_id', sanitize_text_field($_REQUEST['membership_level_id']));

        if( SWPM_PayPal_Utility_Functions::has_plan_details_changed_for_button($button_id) ){
            //Plan details have changed. Delete any existing plan (so a new one can be created next time this button is used).
            SwpmLog::log_simple_debug("PayPal Subscription button edit - billing plan details have been updated.", true);
            delete_post_meta( $button_id, 'pp_subscription_plan_id' );
        }

        update_post_meta($button_id, 'payment_currency', trim(sanitize_text_field($_REQUEST['payment_currency'])));
        update_post_meta($button_id, 'recurring_billing_amount', trim(sanitize_text_field($_REQUEST['recurring_billing_amount'])));
        update_post_meta($button_id, 'recurring_billing_cycle', trim(sanitize_text_field($_REQUEST['recurring_billing_cycle'])));
        update_post_meta($button_id, 'recurring_billing_cycle_term', trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_term'])));
        update_post_meta($button_id, 'recurring_billing_cycle_count', trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_count'])));
        update_post_meta($button_id, 'recurring_billing_reattempt', isset($_REQUEST['recurring_billing_reattempt']) ? '1' : '');

        update_post_meta($button_id, 'trial_billing_amount', trim(sanitize_text_field($_REQUEST['trial_billing_amount'])));
        update_post_meta($button_id, 'trial_billing_cycle', trim(sanitize_text_field($_REQUEST['trial_billing_cycle'])));
        update_post_meta($button_id, 'trial_billing_cycle_term', trim(sanitize_text_field($_REQUEST['trial_billing_cycle_term'])));

        update_post_meta($button_id, 'pp_subscription_new_btn_type', $btn_type);
        update_post_meta($button_id, 'pp_subscription_new_btn_shape', $btn_shape);
        update_post_meta($button_id, 'pp_subscription_new_btn_layout', $btn_layout);        
        update_post_meta($button_id, 'pp_subscription_new_btn_height', $btn_height);
        update_post_meta($button_id, 'pp_subscription_new_btn_width', $btn_width);
        update_post_meta($button_id, 'pp_subscription_new_btn_color', $btn_color);

        update_post_meta($button_id, 'pp_subscription_new_disable_funding_card', $disable_funding_card);
        update_post_meta($button_id, 'pp_subscription_new_disable_funding_credit', $disable_funding_credit);
        update_post_meta($button_id, 'pp_subscription_new_disable_funding_venmo', $disable_funding_venmo);

        update_post_meta($button_id, 'redirect_to_paid_reg_link_after_payment', $redirect_to_paid_reg_link_after_payment);

        update_post_meta($button_id, 'return_url', trim(sanitize_text_field($_REQUEST['return_url'])));

	    // Active subscription check settings.
	    update_post_meta( $button_id, 'show_warning_if_any_active_sub', isset( $_REQUEST['show_warning_if_any_active_sub'] ) ? $_REQUEST['show_warning_if_any_active_sub'] : '' );
	    update_post_meta( $button_id, 'hide_btn_if_any_active_sub', isset( $_REQUEST['hide_btn_if_any_active_sub'] ) ? $_REQUEST['hide_btn_if_any_active_sub'] : '' );
	    update_post_meta( $button_id, 'warning_msg_for_existing_sub', isset( $_REQUEST['warning_msg_for_existing_sub'] ) ? wp_kses_post($_REQUEST['warning_msg_for_existing_sub']) : '' );

        echo '<div id="message" class="updated fade"><p>Payment button data successfully updated!</p></div>';
    }
}

//I've merged two (save and edit events) into one
add_action('swpm_create_new_button_process_submission', 'swpm_save_edit_pp_subscription_new_button_data');
add_action('swpm_edit_payment_button_process_submission', 'swpm_save_edit_pp_subscription_new_button_data');

/*************************************************************************************
 * END of Process submission
 *************************************************************************************/

/*****************************************************************************
 * Start of render the PayPal Subscription (New) payment button creation interface
 *****************************************************************************/
function render_save_edit_pp_subscription_new_button_interface($bt_opts, $is_edit_mode = false) {

    $settings = SwpmSettings::get_instance();
    $live_client_id = $settings->get_value('paypal-live-client-id');  
    $sandbox_client_id = $settings->get_value('paypal-sandbox-client-id');

    if ( empty($live_client_id) && empty($sandbox_client_id) ) {
        //API credentials are not configured. Show a warning message and return.
        echo '<div class="swpm-orange-box">';
        echo __('You need to configure your PayPal API credentials first. ', 'simple-membership');
        echo '<a href="admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_api" target="_blank">'.__('Click here', 'simple-membership').'</a> ' . __('to configure your PayPal API credentials in the payment settings menu.', 'simple-membership');
        echo '</div>';
        return;
    }

    ?>

    <div class="swpm-orange-box">
        <?php _e('View the', 'simple-membership') ?> <a target="_blank" href="https://simple-membership-plugin.com/create-paypal-subscription-buttons-paypal-api/"><?php _e('documentation', 'simple-membership') ?></a>&nbsp;
        <?php _e('to learn how to create and use a PayPal Subscription (New API) payment button.', 'simple-membership') ?>
    </div>

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php _e('PayPal Subscription (New API) Button Configuration', 'simple-membership'); ?></label></h3>
        <div class="inside">

            <form id="pp_subscription_new_button_config_form" method="post">
                <input type="hidden" name="button_type" value="<?php echo esc_attr($bt_opts['button_type']); ?>">
                <?php if (!$is_edit_mode) { ?>
                    <input type="hidden" name="swpm_button_type_selected" value="1">
                <?php } ?>

                <table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
                    <?php if ($is_edit_mode) { ?>
                        <tr valign="top">
                            <th scope="row"><?php _e('Button ID', 'simple-membership'); ?></th>
                            <td>
                                <input type="text" size="10" name="button_id" value="<?php echo esc_attr($bt_opts['button_id']); ?>" readonly required />
                                <p class="description"><?php _e('This is the ID of this payment button. It is automatically generated for you and it cannot be changed.', 'simple-membership') ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Button Shortcode', 'simple-membership'); ?></th>
                            <td>
                                <?php
                                $shortcode = SwpmUtils::get_shortcode_for_admin_ui_display($bt_opts['button_id'], 50);
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
                        <th scope="row"><?php _e('Button Title', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="50" name="button_name" value="<?php echo ($is_edit_mode ? esc_attr($bt_opts['button_name']) : ''); ?>" required />
                            <p class="description"><?php _e('Give this membership payment button a name. Example: Gold membership payment', 'simple-membership') ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Membership Level', 'simple-membership'); ?></th>
                        <td>
                            <select id="membership_level_id" name="membership_level_id">
                                <?php echo ($is_edit_mode ? SwpmUtils::membership_level_dropdown($bt_opts['membership_level_id']) : SwpmUtils::membership_level_dropdown()); ?>
                            </select>
                            <p class="description"><?php _e('Select the membership level this payment button is for.', 'simple-membership') ?></p>
                        </td>
                    </tr>

                    <?php
                    //If the button is being edited and there is a plan ID, then show PayPal billing plan ID and the environment mode this subscritpiton button is configured for.
					if ( $is_edit_mode && !empty($bt_opts['pp_subscription_plan_id'])) {
                        ?>
                        <tr valign="top">
                            <th colspan="2"><div class="swpm-grey-box"><?php _e('PayPal Billing Plan Details for This Button', 'simple-membership'); ?></div></th>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php _e('Subscription Plan Mode', 'simple-membership'); ?></th>
                            <td>
                                <input type="text" size="20" name="pp_subscription_plan_mode" value="<?php echo esc_attr($bt_opts['pp_subscription_plan_mode']); ?>" readonly />
                                <p class="description"><?php _e('This is the paypal mode this subscription button was created in.', 'simple-membership') ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Subscription Plan ID', 'simple-membership'); ?></th>
                            <td>
                                <input type="text" size="20" name="pp_subscription_plan_id" value="<?php echo esc_attr($bt_opts['pp_subscription_plan_id']); ?>" readonly />
                                <p class="description"><?php _e('This is the paypal subscription plan ID.', 'simple-membership') ?></p>
                            </td>
                        <?php                    
					}
                    ?>

                    <tr valign="top">
                        <th colspan="2"><div class="swpm-grey-box"><?php _e('Subscription/Recurring Billing Details', 'simple-membership'); ?></div></th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Payment Currency', 'simple-membership'); ?></th>
                        <td>
                            <select id="payment_currency" name="payment_currency">
                                <option value="USD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'USD') ? 'selected="selected"' : ''; ?>>US Dollars ($)</option>
                                <option value="EUR" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'EUR') ? 'selected="selected"' : ''; ?>>Euros (€)</option>
                                <option value="GBP" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'GBP') ? 'selected="selected"' : ''; ?>>Pounds Sterling (£)</option>
                                <option value="AUD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'AUD') ? 'selected="selected"' : ''; ?>>Australian Dollars ($)</option>
                                <option value="BRL" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'BRL') ? 'selected="selected"' : ''; ?>>Brazilian Real (R$)</option>
                                <option value="CAD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'CAD') ? 'selected="selected"' : ''; ?>>Canadian Dollars ($)</option>
                                <option value="CNY" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'CNY') ? 'selected="selected"' : ''; ?>>Chinese Yuan</option>
                                <option value="CZK" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'CZK') ? 'selected="selected"' : ''; ?>>Czech Koruna</option>
                                <option value="DKK" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'DKK') ? 'selected="selected"' : ''; ?>>Danish Krone</option>
                                <option value="HKD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'HKD') ? 'selected="selected"' : ''; ?>>Hong Kong Dollar ($)</option>
                                <option value="HUF" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'HUF') ? 'selected="selected"' : ''; ?>>Hungarian Forint</option>
                                <option value="INR" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'INR') ? 'selected="selected"' : ''; ?>>Indian Rupee</option>
                                <option value="IDR" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'IDR') ? 'selected="selected"' : ''; ?>>Indonesia Rupiah</option>
                                <option value="ILS" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'ILS') ? 'selected="selected"' : ''; ?>>Israeli Shekel</option>
                                <option value="JPY" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'JPY') ? 'selected="selected"' : ''; ?>>Japanese Yen (¥)</option>
                                <option value="MYR" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'MYR') ? 'selected="selected"' : ''; ?>>Malaysian Ringgits</option>
                                <option value="MXN" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'MXN') ? 'selected="selected"' : ''; ?>>Mexican Peso ($)</option>
                                <option value="NZD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'NZD') ? 'selected="selected"' : ''; ?>>New Zealand Dollar ($)</option>
                                <option value="NOK" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'NOK') ? 'selected="selected"' : ''; ?>>Norwegian Krone</option>
                                <option value="PHP" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'PHP') ? 'selected="selected"' : ''; ?>>Philippine Pesos</option>
                                <option value="PLN" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'PLN') ? 'selected="selected"' : ''; ?>>Polish Zloty</option>
                                <option value="RUB" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'RUB') ? 'selected="selected"' : ''; ?>>Russian Ruble</option>
                                <option value="SGD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'SGD') ? 'selected="selected"' : ''; ?>>Singapore Dollar ($)</option>
                                <option value="ZAR" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'ZAR') ? 'selected="selected"' : ''; ?>>South African Rand (R)</option>
                                <option value="KRW" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'KRW') ? 'selected="selected"' : ''; ?>>South Korean Won</option>
                                <option value="SEK" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'SEK') ? 'selected="selected"' : ''; ?>>Swedish Krona</option>
                                <option value="CHF" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'CHF') ? 'selected="selected"' : ''; ?>>Swiss Franc</option>
                                <option value="TWD" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'TWD') ? 'selected="selected"' : ''; ?>>Taiwan New Dollars</option>
                                <option value="THB" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'THB') ? 'selected="selected"' : ''; ?>>Thai Baht</option>
                                <option value="TRY" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'TRY') ? 'selected="selected"' : ''; ?>>Turkish Lira</option>
                                <option value="VND" <?php echo (isset($bt_opts['payment_currency']) && $bt_opts['payment_currency'] == 'VND') ? 'selected="selected"' : ''; ?>>Vietnamese Dong</option>
                            </select>
                            <p class="description"><?php _e('Select the currency for this payment button.', 'simple-membership') ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Recurring Billing Amount', 'simple-membership'); ?></th>
                        <td>
                            <input type="number" min="0" step="0.01" size="10" name="recurring_billing_amount" value="<?php echo ($is_edit_mode ? esc_attr($bt_opts['recurring_billing_amount']) : ''); ?>" required />
                            <p class="description"><?php _e('Amount to be charged on every billing cycle. If used with a trial period then this amount will be charged after the trial period is over. Example values: 9.90 or 25.00 or 299.90 etc (do not enter currency symbol).', 'simple-membership'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Recurring Billing Cycle', 'simple-membership'); ?></th>
                        <td>
                            <input type="number" min="0" step="1" size="10" name="recurring_billing_cycle" value="<?php echo ($is_edit_mode ? esc_attr($bt_opts['recurring_billing_cycle']) : ''); ?>" required />
                            <select id="recurring_billing_cycle_term" name="recurring_billing_cycle_term">
                                <option value="D"<?php echo (isset($bt_opts['recurring_billing_cycle_term']) && $bt_opts['recurring_billing_cycle_term'] === 'D') ? ' selected' : ''; ?>>Day(s)</option>
                                <option value="M"<?php echo (isset($bt_opts['recurring_billing_cycle_term']) && $bt_opts['recurring_billing_cycle_term'] === 'M') ? ' selected' : ''; ?>>Month(s)</option>
                                <option value="Y"<?php echo (isset($bt_opts['recurring_billing_cycle_term']) && $bt_opts['recurring_billing_cycle_term'] === 'Y') ? ' selected' : ''; ?>>Year(s)</option>
                            </select>
                            <p class="description"><?php _e('Set the interval of the recurring payment. Example value: 1 Month (if you want to charge every month)', 'simple-membership'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Recurring Billing Cycle Count', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="10" name="recurring_billing_cycle_count" value="<?php echo ($is_edit_mode ? esc_attr($bt_opts['recurring_billing_cycle_count']) : ''); ?>" />
                            <p class="description"><?php _e('After how many cycles should billing stop. Leave this field empty (or enter 0) if you want the payment to continue until the subscription is canceled.', 'simple-membership'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Re-attempt on Failure', 'simple-membership'); ?></th>
                        <td>
                            <input type="checkbox" name="recurring_billing_reattempt" value="1" <?php echo (isset($bt_opts['recurring_billing_reattempt']) && !empty($bt_opts['recurring_billing_reattempt']) ) ? ' checked' : ''; ?> />
                            <p class="description"><?php _e('When checked, the payment will be re-attempted two more times if the payment fails. After the third failure, the subscription will be canceled.', 'simple-membership'); ?></p>
                        </td>
                    </tr>                    

                    <tr valign="top">
                        <th colspan="2"><div class="swpm-grey-box"><?php _e('Trial Billing Details (Leave empty if you are not offering a trial period)', 'simple-membership'); ?></div></th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Trial Billing Amount', 'simple-membership'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" size="10" name="trial_billing_amount" value="<?php echo ($is_edit_mode ? esc_attr($bt_opts['trial_billing_amount']) : ''); ?>" />
                            <p class="description"><?php _e('Amount to be charged for the trial period. Enter 0 if you want to offer a free trial period.', 'simple-membership') ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Trial Billing Period', 'simple-membership'); ?></th>
                        <td>
                            <input type="number" min="0" step="1" size="10" name="trial_billing_cycle" value="<?php echo ($is_edit_mode ? esc_attr($bt_opts['trial_billing_cycle']) : ''); ?>" />
                            <select id="trial_billing_cycle_term" name="trial_billing_cycle_term">
                                <option value="D"<?php echo (isset($bt_opts['trial_billing_cycle_term']) && $bt_opts['trial_billing_cycle_term'] === 'D') ? ' selected' : ''; ?>>Day(s)</option>
                                <option value="M"<?php echo (isset($bt_opts['trial_billing_cycle_term']) && $bt_opts['trial_billing_cycle_term'] === 'M') ? ' selected' : ''; ?>>Month(s)</option>
                                <option value="Y"<?php echo (isset($bt_opts['trial_billing_cycle_term']) && $bt_opts['trial_billing_cycle_term'] === 'Y') ? ' selected' : ''; ?>>Year(s)</option>
                            </select>
                            <p class="description"><?php _e('Length of the trial period', 'simple-membership') ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th colspan="2"><div class="swpm-grey-box"><?php _e('Button Style Settings (Optional)', 'simple-membership'); ?></div></th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e("Button Type/Label", "simple-membership"); ?></th>
                        <td>
                            <select name="pp_subscription_new_btn_type" style="min-width: 150px;">
                                <option value="checkout"<?php echo (isset($bt_opts['pp_subscription_new_btn_type']) && $bt_opts['pp_subscription_new_btn_type'] === 'checkout') ? ' selected' : ''; ?>><?php _e("Checkout", "simple-membership"); ?></option>                                
                                <option value="pay"<?php echo (isset($bt_opts['pp_subscription_new_btn_type']) && $bt_opts['pp_subscription_new_btn_type'] === 'pay') ? ' selected' : ''; ?>><?php _e("Pay", "simple-membership"); ?></option>
                                <option value="paypal"<?php echo (isset($bt_opts['pp_subscription_new_btn_type']) && $bt_opts['pp_subscription_new_btn_type'] === 'paypal') ? ' selected' : ''; ?>><?php _e("PayPal", "simple-membership"); ?></option>
                                <option value="buynow"<?php echo (isset($bt_opts['pp_subscription_new_btn_type']) && $bt_opts['pp_subscription_new_btn_type'] === 'buynow') ? ' selected' : ''; ?>><?php _e("Buy Now", "simple-membership"); ?></option>
                                <option value="subscribe"<?php echo (isset($bt_opts['pp_subscription_new_btn_type']) && $bt_opts['pp_subscription_new_btn_type'] === 'subscribe') ? ' selected' : ''; ?>><?php _e("Subscribe", "simple-membership"); ?></option>
                            </select>
                            <p class="description"><?php _e("Select button type/label.", "simple-membership"); ?></p>
                        </td>
                    </tr>                    
                    <tr valign="top">
                        <th scope="row"><?php _e("Button Shape", "simple-membership"); ?></th>
                        <td>
                            <p><label><input type="radio" name="pp_subscription_new_btn_shape" value="rect"<?php echo (isset($bt_opts['pp_subscription_new_btn_shape']) && $bt_opts['pp_subscription_new_btn_shape'] === 'rect' || empty($bt_opts['pp_subscription_new_btn_shape'])) ? ' checked' : ''; ?>> <?php _e("Rectangular", "simple-membership"); ?></label></p>
                            <p><label><input type="radio" name="pp_subscription_new_btn_shape" value="pill"<?php echo (isset($bt_opts['pp_subscription_new_btn_shape']) && $bt_opts['pp_subscription_new_btn_shape'] === 'pill') ? ' checked' : ''; ?>> <?php _e("Pill", "simple-membership"); ?></label></p>
                            <p class="description"><?php _e("Select button shape.", "simple-membership"); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Button Layout", "simple-membership"); ?></th>
                        <td>
                            <p><label><input type="radio" name="pp_subscription_new_btn_layout" value="vertical"<?php echo (isset($bt_opts['pp_subscription_new_btn_layout']) && $bt_opts['pp_subscription_new_btn_layout'] === 'vertical' || empty($bt_opts['pp_subscription_new_btn_layout'])) ? ' checked' : ''; ?>> <?php _e("Vertical", "simple-membership"); ?></label></p>
                            <p><label><input type="radio" name="pp_subscription_new_btn_layout" value="horizontal"<?php echo (isset($bt_opts['pp_subscription_new_btn_layout']) && $bt_opts['pp_subscription_new_btn_layout'] === 'horizontal') ? ' checked' : ''; ?>> <?php _e("Horizontal", "simple-membership"); ?></label></p>
                            <p class="description"><?php _e("Select button layout.", "simple-membership"); ?></p>
                        </td>
                    </tr>               
                    <tr valign="top">
                        <th scope="row"><?php _e("Button Height", "simple-membership"); ?></th>
                        <td>
                            <select name="pp_subscription_new_btn_height" style="min-width: 150px;">
                                <option value="small"<?php echo (isset($bt_opts['pp_subscription_new_btn_height']) && $bt_opts['pp_subscription_new_btn_height'] === 'small') ? ' selected' : ''; ?>><?php _e("Small", "simple-membership"); ?></option>                                
                                <option value="medium"<?php echo (isset($bt_opts['pp_subscription_new_btn_height']) && $bt_opts['pp_subscription_new_btn_height'] === 'medium') ? ' selected' : ''; ?>><?php _e("Medium", "simple-membership"); ?></option>
                                <option value="large"<?php echo (isset($bt_opts['pp_subscription_new_btn_height']) && $bt_opts['pp_subscription_new_btn_height'] === 'large') ? ' selected' : ''; ?>><?php _e("Large", "simple-membership"); ?></option>
                                <option value="extra-large"<?php echo (isset($bt_opts['pp_subscription_new_btn_height']) && $bt_opts['pp_subscription_new_btn_height'] === 'extra-large') ? ' selected' : ''; ?>><?php _e("Extra Large", "simple-membership"); ?></option>
                            </select>
                            <p class="description"><?php _e("Select button height.", "simple-membership"); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Button Width', 'simple-membership'); ?></th>
                        <td>
                            <input type="number" step="1" min="0" size="10" name="pp_subscription_new_btn_width" value="300" style="min-width: 150px;" />
                            <p class="description"><?php _e("Select button width.", "simple-membership"); ?></p>
                        </td>
                    </tr>                    
                    <tr valign="top">
                        <th scope="row"><?php _e("Button Color", "simple-membership"); ?></th>
                        <td>
                            <select name="pp_subscription_new_btn_color" style="min-width: 150px;">
                                <option value="gold"<?php echo (isset($bt_opts['pp_subscription_new_btn_color']) && $bt_opts['pp_subscription_new_btn_color'] === 'gold') ? ' selected' : ''; ?>><?php _e("Gold", "simple-membership"); ?></option>
                                <option value="blue"<?php echo (isset($bt_opts['pp_subscription_new_btn_color']) && $bt_opts['pp_subscription_new_btn_color'] === 'blue') ? ' selected' : ''; ?>><?php _e("Blue", "simple-membership"); ?></option>
                                <option value="silver"<?php echo (isset($bt_opts['pp_subscription_new_btn_color']) && $bt_opts['pp_subscription_new_btn_color'] === 'silver') ? ' selected' : ''; ?>><?php _e("Silver", "simple-membership"); ?></option>
                                <option value="white"<?php echo (isset($bt_opts['pp_subscription_new_btn_color']) && $bt_opts['pp_subscription_new_btn_color'] === 'white') ? ' selected' : ''; ?>><?php _e("White", "simple-membership"); ?></option>
                                <option value="black"<?php echo (isset($bt_opts['pp_subscription_new_btn_color']) && $bt_opts['pp_subscription_new_btn_color'] === 'black') ? ' selected' : ''; ?>><?php _e("Black", "simple-membership"); ?></option>
                            </select>
                            <p class="description"><?php _e("Select button color.", "simple-membership"); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th colspan="2"><div class="swpm-grey-box"><?php _e('Additional Settings (Optional)', 'simple-membership'); ?></div></th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e("Disable Funding", "simple-membership"); ?></th>
                        <td>
                        <p><label><input type="checkbox" name="pp_subscription_new_disable_funding_card" value="1"<?php echo (!empty($bt_opts['pp_subscription_new_disable_funding_card']) ) ? ' checked' : ''; ?>> <?php _e("Credit or debit cards", "simple-membership"); ?></label></p>
                            <p><label><input type="checkbox" name="pp_subscription_new_disable_funding_credit" value="1"<?php echo (!empty($bt_opts['pp_subscription_new_disable_funding_credit']) ) ? ' checked' : ''; ?>> <?php _e("PayPal Credit", "simple-membership"); ?></label></p>
                            <p><label><input type="checkbox" name="pp_subscription_new_disable_funding_venmo" value="1"<?php echo (!empty($bt_opts['pp_subscription_new_disable_funding_venmo']) ) ? ' checked' : ''; ?>> <?php _e("Venmo", "simple-membership"); ?></label></p>
                            <p class="description"><?php _e("By default, funding source eligibility is smartly decided based on a variety of factors. You can force disable funding options by selecting them here.", "simple-membership"); ?></p>
                        </td>
                    </tr>

	                <?php
	                $redirect_to_paid_reg_link_after_payment = isset($bt_opts['redirect_to_paid_reg_link_after_payment']) && !empty($bt_opts['redirect_to_paid_reg_link_after_payment']) ? 'checked' : '';
	                ?>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Redirect to Paid Registration Link', 'simple-membership'); ?></th>
                        <td>
                            <input type="checkbox" name="redirect_to_paid_reg_link_after_payment" value="1" <?php echo esc_attr($redirect_to_paid_reg_link_after_payment) ?> />
                            <p class="description">
				                <?php _e('Enable this option to automatically redirect the user to the unique paid registration link after a successful payment. ', 'simple-membership') ?>
                                <a href="https://simple-membership-plugin.com/automatically-redirect-users-to-the-paid-registration-link-after-payment/" target="_blank"><?php _e('Read this documentation', 'simple-membership') ?></a>
                                <?php _e(' to learn how it works.', 'simple-membership') ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Return URL', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="100" name="return_url" value="<?php echo ($is_edit_mode ? esc_url_raw($bt_opts['return_url']) : ''); ?>" />
                            <p class="description"><?php _e('This is the URL the user will be redirected to after a successful payment. Enter the URL of your Thank You page here.', 'simple-membership') ?></p>

                            <?php if ($redirect_to_paid_reg_link_after_payment) { ?>
                                <p class="description">
                                    <strong><?php esc_attr_e('Note: ', 'simple-membership'); ?></strong> <?php esc_attr_e("The 'Redirect to Paid Registration Link' option is enabled for this button. Unregistered users will be redirected to the paid registration page after completing payment.", 'simple-membership'); ?>
                                </p>
                            <?php } ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th colspan="2">
                            <div class="swpm-grey-box"><?php _e('Active Subscription Check', 'simple-membership'); ?></div>
                                <p class="description">
		                        <?php _e('The options in this section allow you to check if the logged-in member already has an active subscription and display a warning message if needed.', 'simple-membership') ?>
                            </p>
                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Show Warning for Existing Subscription' , 'simple-membership'); ?></th>
                        <td>
                            <input type="checkbox" name="show_warning_if_any_active_sub" value="1" <?php echo ( $is_edit_mode ? ( ( isset( $bt_opts['show_warning_if_any_active_sub'] ) && $bt_opts['show_warning_if_any_active_sub'] === '1' ) ? ' checked' : '' ) : '' ); ?> />
                            <p class="description"><?php _e('Enable this option to display a warning message next to the subscription button if the logged-in member already has an active subscription.', 'simple-membership') ?></p>

                            <br />
                            <p><strong><?php _e( 'Message to Display for Existing Subscription' , 'simple-membership'); ?></strong></p>
                            <input type="text" size="100" name="warning_msg_for_existing_sub" value="<?php echo ( $is_edit_mode && isset( $bt_opts['warning_msg_for_existing_sub'] ) ? esc_attr($bt_opts['warning_msg_for_existing_sub']) : '' ); ?>" />
                            <p class="description">
				                <?php
				                _e('Enter the custom message you want to show to logged-in members who already have an active subscription.', 'simple-membership');
				                echo ' ' . __('You may include a link to a page with ', 'simple-membership');
				                echo '<a href="https://simple-membership-plugin.com/show-active-subscriptions-and-providing-a-cancellation-option/" target="_blank">' . __('self-cancellation instructions', 'simple-membership') . '</a>.';
				                ?>
                            </p>

                            <br />
                            <input type="checkbox" name="hide_btn_if_any_active_sub" value="1" <?php echo ( $is_edit_mode ? ( ( isset( $bt_opts['hide_btn_if_any_active_sub'] ) && $bt_opts['hide_btn_if_any_active_sub'] === '1' ) ? ' checked' : '' ) : '' ); ?> />
                            <strong><?php echo ' ' . __( 'Hide Payment Button if Another Subscription is Active' , 'simple-membership'); ?></strong>
                            <p class="description">
				                <?php _e('Check this option to hide the subscription button when the warning message is displayed for an existing active subscription.', 'simple-membership') ?>
                            </p>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <?php wp_nonce_field('swpm_admin_add_edit_pp_subscription_new_btn','swpm_admin_add_edit_pp_subscription_new_btn') ?>
                    <input type="submit" name="swpm_pp_subscription_new_<?php echo ($is_edit_mode ? 'edit' : 'save'); ?>_submit" class="button-primary" value="<?php _e('Save Payment Data'); ?>" >
                </p>

            </form>

        </div>
    </div>
	<?php
}

/*****************************************************************
 * Render the create new PayPal subscription payment button interface
 * ************************************************************** */
function swpm_create_new_pp_subscription_new_button() {

    $bt_opts = array(
        'button_type' => sanitize_text_field($_REQUEST['button_type']),
        'pp_subscription_new_btn_height' => 'medium', /* set default button height */
        'pp_subscription_new_btn_color' => 'blue', /* set default button color */
        'pp_subscription_new_disable_funding_credit' => '1', /* set credit option to be disabled by default */
    );

    render_save_edit_pp_subscription_new_button_interface($bt_opts);
}
add_action('swpm_create_new_button_for_pp_subscription_new', 'swpm_create_new_pp_subscription_new_button');

/*****************************************************************
 * Render the edit new PayPal subscription payment button interface
 *************************************************************** */
function swpm_edit_pp_subscription_new_button() {

    //Retrieve the payment button data and present it for editing.

    $button_id = sanitize_text_field($_REQUEST['button_id']);
    $button_id = absint($button_id);

    $button = get_post($button_id); //Retrieve the CPT for this button
    //$button_image_url = get_post_meta($button_id, 'button_image_url', true);

    $bt_opts = array(
        'button_id' => $button_id,
        'button_type' => sanitize_text_field($_REQUEST['button_type']),
        'button_name' => $button->post_title,
        'membership_level_id' => get_post_meta($button_id, 'membership_level_id', true),
        'payment_currency' => get_post_meta($button_id, 'payment_currency', true),
        'recurring_billing_amount' => get_post_meta($button_id, 'recurring_billing_amount', true),
        'recurring_billing_cycle' => get_post_meta($button_id, 'recurring_billing_cycle', true),
        'recurring_billing_cycle_term' => get_post_meta($button_id, 'recurring_billing_cycle_term', true),
        'recurring_billing_cycle_count' => get_post_meta($button_id, 'recurring_billing_cycle_count', true),
        'recurring_billing_reattempt' => get_post_meta($button_id, 'recurring_billing_reattempt', true),
        'trial_billing_amount' => get_post_meta($button_id, 'trial_billing_amount', true),
        'trial_billing_cycle' => get_post_meta($button_id, 'trial_billing_cycle', true),
        'trial_billing_cycle_term' => get_post_meta($button_id, 'trial_billing_cycle_term', true),
        'pp_subscription_new_btn_type' => get_post_meta($button_id, 'pp_subscription_new_btn_type', true),
        'pp_subscription_new_btn_shape' => get_post_meta($button_id, 'pp_subscription_new_btn_shape', true),
        'pp_subscription_new_btn_layout' => get_post_meta($button_id, 'pp_subscription_new_btn_layout', true),        
        'pp_subscription_new_btn_height' => get_post_meta($button_id, 'pp_subscription_new_btn_height', true),
        'pp_subscription_new_btn_width' => get_post_meta($button_id, 'pp_subscription_new_btn_width', true),
        'pp_subscription_new_btn_color' => get_post_meta($button_id, 'pp_subscription_new_btn_color', true),
        'pp_subscription_new_disable_funding_card' => get_post_meta($button_id, 'pp_subscription_new_disable_funding_card', true),
        'pp_subscription_new_disable_funding_credit' => get_post_meta($button_id, 'pp_subscription_new_disable_funding_credit', true),
        'pp_subscription_new_disable_funding_venmo' => get_post_meta($button_id, 'pp_subscription_new_disable_funding_venmo', true),
        'redirect_to_paid_reg_link_after_payment' => get_post_meta($button_id, 'redirect_to_paid_reg_link_after_payment', true),
        'return_url' => get_post_meta($button_id, 'return_url', true),
        'pp_subscription_plan_id' => get_post_meta($button_id, 'pp_subscription_plan_id', true),
        'pp_subscription_plan_mode' => get_post_meta($button_id, 'pp_subscription_plan_mode', true),

	    'show_warning_if_any_active_sub' => sanitize_text_field(get_post_meta( $button_id, 'show_warning_if_any_active_sub', true )),
	    'hide_btn_if_any_active_sub' => sanitize_text_field(get_post_meta( $button_id, 'hide_btn_if_any_active_sub', true )),
	    'warning_msg_for_existing_sub' => wp_kses_post(get_post_meta( $button_id, 'warning_msg_for_existing_sub', true )),
);

    render_save_edit_pp_subscription_new_button_interface($bt_opts, true);
}
add_action('swpm_edit_payment_button_for_pp_subscription_new', 'swpm_edit_pp_subscription_new_button');
/*************************************************************************************
 * END of render button configuration HTML
 *************************************************************************************/