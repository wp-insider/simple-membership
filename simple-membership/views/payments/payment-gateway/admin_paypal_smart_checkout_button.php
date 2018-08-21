<?php
/* * ***************************************************************
 * Render the new PayPal Smart Checkout payment button creation interface
 * ************************************************************** */

/*
  I've optimized render function in order to avoid code duplication.
  This function is responsible for rendering either Save or Edit button interface depending on the parameters.
  It's much easier to modify it as the changes (descriptions update etc) are reflected in both forms at once.
 */

function render_save_edit_pp_smart_checkout_button_interface($bt_opts, $is_edit_mode = false) {
    ?>

    <div class="swpm-orange-box">
        View the <a target="_blank" href="https://simple-membership-plugin.com/create-braintree-buy-now-button-for-membership-payment/">documentation</a>&nbsp;
        to learn how to create and use a PayPal Smart Checkout payment button.
    </div>

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php echo SwpmUtils::_('PayPal Smart Checkout Button Configuration'); ?></label></h3>
        <div class="inside">

            <form id="smart_checkout_button_config_form" method="post">
                <input type="hidden" name="button_type" value="<?php echo $bt_opts['button_type']; ?>">
                <?php if (!$is_edit_mode) { ?>
                    <input type="hidden" name="swpm_button_type_selected" value="1">
                <?php } ?>

                <table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
                    <?php if ($is_edit_mode) { ?>
                        <tr valign="top">
                            <th scope="row"><?php echo SwpmUtils::_('Button ID'); ?></th>
                            <td>
                                <input type="text" size="10" name="button_id" value="<?php echo $bt_opts['button_id']; ?>" readonly required />
                                <p class="description">This is the ID of this payment button. It is automatically generated for you and it cannot be changed.</p>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Button Title'); ?></th>
                        <td>
                            <input type="text" size="50" name="button_name" value="<?php echo ($is_edit_mode ? $bt_opts['button_name'] : ''); ?>" required />
                            <p class="description">Give this membership payment button a name. Example: Gold membership payment</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Membership Level'); ?></th>
                        <td>
                            <select id="membership_level_id" name="membership_level_id">
                                <?php echo ($is_edit_mode ? SwpmUtils::membership_level_dropdown($bt_opts['membership_level_id']) : SwpmUtils::membership_level_dropdown()); ?>
                            </select>
                            <p class="description">Select the membership level this payment button is for.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Payment Amount'); ?></th>
                        <td>
                            <input type="text" size="6" name="payment_amount" value="<?php echo ($is_edit_mode ? $bt_opts['payment_amount'] : ''); ?>" required />
                            <p class="description">Enter payment amount. Example values: 10.00 or 19.50 or 299.95 etc (do not put currency symbol).</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th colspan="2"><div class="swpm-grey-box"><?php echo SwpmUtils::_('PayPal Smart Checkout and account details. You can get this from your PayPal account.'); ?></div></th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Live Client ID'); ?></th>
                        <td>
                            <input type="text" size="50" name="pp_smart_checkout_live_id" value="<?php echo ($is_edit_mode ? $bt_opts['pp_smart_checkout_live_id'] : ''); ?>" required/>
                            <p class="description">Enter your live Client ID.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Live Secret'); ?></th>
                        <td>
                            <input type="text" size="50" name="pp_smart_checkout_live_sec" value="<?php echo ($is_edit_mode ? $bt_opts['pp_smart_checkout_live_sec'] : ''); ?>" required/>
                            <p class="description">Enter your live Secret.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Sandbox Client ID'); ?></th>
                        <td>
                            <input type="text" size="50" name="pp_smart_checkout_test_id" value="<?php echo ($is_edit_mode ? $bt_opts['pp_smart_checkout_test_id'] : ''); ?>" required/>
                            <p class="description">Enter your sandbox Client ID.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Sandbox Secret'); ?></th>
                        <td>
                            <input type="text" size="50" name="pp_smart_checkout_test_sec" value="<?php echo ($is_edit_mode ? $bt_opts['pp_smart_checkout_test_sec'] : ''); ?>" required/>
                            <p class="description">Enter your sandbox Secret.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th colspan="2"><div class="swpm-grey-box"><?php echo SwpmUtils::_('The following details are optional.'); ?></div></th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php echo SwpmUtils::_('Return URL'); ?></th>
                        <td>
                            <input type="text" size="100" name="return_url" value="<?php echo ($is_edit_mode ? $bt_opts['return_url'] : ''); ?>" />
                            <p class="description">This is the URL the user will be redirected to after a successful payment. Enter the URL of your Thank You page here.</p>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" name="swpm_pp_smart_checkout_<?php echo ($is_edit_mode ? 'edit' : 'save'); ?>_submit" class="button-primary" value="<?php echo SwpmUtils::_('Save Payment Data'); ?>" >
                </p>

            </form>

        </div>
    </div>
    <?php
}

/* * ***************************************************************
 * Render save PayPal Smart Checkout payment button interface
 * ************************************************************** */
add_action('swpm_create_new_button_for_pp_smart_checkout', 'swpm_create_new_pp_smart_checkout_button');

function swpm_create_new_pp_smart_checkout_button() {

    $bt_opts = array(
        'button_type' => sanitize_text_field($_REQUEST['button_type']),
    );

    render_save_edit_pp_smart_checkout_button_interface($bt_opts);
}

/* * ***************************************************************
 * Render edit PayPal Smart Checkout payment button interface
 * ************************************************************** */
add_action('swpm_edit_payment_button_for_pp_smart_checkout', 'swpm_edit_pp_smart_checkout_button');

function swpm_edit_pp_smart_checkout_button() {

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
        'payment_amount' => get_post_meta($button_id, 'payment_amount', true),
        'pp_smart_checkout_live_id' => get_post_meta($button_id, 'pp_smart_checkout_live_id', true),
        'pp_smart_checkout_live_sec' => get_post_meta($button_id, 'pp_smart_checkout_live_sec', true),
        'pp_smart_checkout_test_id' => get_post_meta($button_id, 'pp_smart_checkout_test_id', true),
        'pp_smart_checkout_test_sec' => get_post_meta($button_id, 'pp_smart_checkout_test_sec', true),
        'return_url' => get_post_meta($button_id, 'return_url', true),
    );

    render_save_edit_pp_smart_checkout_button_interface($bt_opts, true);
}

/*
 * Process submission and save the new or edit PayPal Smart Checkout payment button data
 */

add_action('swpm_create_new_button_process_submission', 'swpm_save_edit_pp_smart_checkout_button_data');
add_action('swpm_edit_payment_button_process_submission', 'swpm_save_edit_pp_smart_checkout_button_data');

//I've merged two (save and edit events) into one

function swpm_save_edit_pp_smart_checkout_button_data() {
    if (isset($_REQUEST['swpm_pp_smart_checkout_save_submit'])) {
        //This is a PayPal Smart Checkout button save event.

        $button_id = wp_insert_post(
                array(
                    'post_title' => sanitize_text_field($_REQUEST['button_name']),
                    'post_type' => 'swpm_payment_button',
                    'post_content' => '',
                    'post_status' => 'publish'
                )
        );

        $button_type = sanitize_text_field($_REQUEST['button_type']);
        add_post_meta($button_id, 'button_type', $button_type);
        add_post_meta($button_id, 'membership_level_id', sanitize_text_field($_REQUEST['membership_level_id']));
        add_post_meta($button_id, 'payment_amount', trim(sanitize_text_field($_REQUEST['payment_amount'])));

        add_post_meta($button_id, 'pp_smart_checkout_live_id', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_live_id'])));
        add_post_meta($button_id, 'pp_smart_checkout_live_sec', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_live_sec'])));
        add_post_meta($button_id, 'pp_smart_checkout_test_id', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_test_id'])));
        add_post_meta($button_id, 'pp_smart_checkout_test_sec', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_test_sec'])));

        add_post_meta($button_id, 'return_url', trim(sanitize_text_field($_REQUEST['return_url'])));

        //Redirect to the manage payment buttons interface
        $url = admin_url() . 'admin.php?page=simple_wp_membership_payments&tab=payment_buttons';
        SwpmMiscUtils::redirect_to_url($url);
    }

    if (isset($_REQUEST['swpm_pp_smart_checkout_edit_submit'])) {
        //This is a PayPal Smart Checkout button edit event.
        $button_id = sanitize_text_field($_REQUEST['button_id']);
        $button_id = absint($button_id);
        $button_type = sanitize_text_field($_REQUEST['button_type']);
        $button_name = sanitize_text_field($_REQUEST['button_name']);

        $button_post = array(
            'ID' => $button_id,
            'post_title' => $button_name,
            'post_type' => 'swpm_payment_button',
        );
        wp_update_post($button_post);

        update_post_meta($button_id, 'button_type', $button_type);
        update_post_meta($button_id, 'membership_level_id', sanitize_text_field($_REQUEST['membership_level_id']));
        update_post_meta($button_id, 'payment_amount', trim(sanitize_text_field($_REQUEST['payment_amount'])));

        update_post_meta($button_id, 'pp_smart_checkout_live_id', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_live_id'])));
        update_post_meta($button_id, 'pp_smart_checkout_live_sec', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_live_sec'])));
        update_post_meta($button_id, 'pp_smart_checkout_test_id', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_test_id'])));
        update_post_meta($button_id, 'pp_smart_checkout_test_sec', trim(sanitize_text_field($_REQUEST['pp_smart_checkout_test_sec'])));

        update_post_meta($button_id, 'return_url', trim(sanitize_text_field($_REQUEST['return_url'])));

        echo '<div id="message" class="updated fade"><p>Payment button data successfully updated!</p></div>';
    }
}
