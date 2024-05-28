<?php

/*******************************************************************
 * Render and process the interface for add new transaction manually
 ******************************************************************/

function swpm_handle_add_new_txn_manually(){
    echo '<div class="swpm-grey-box">';
    _e( 'You can add a new transaction record manually using this interface. It can be useful if you manually accept cash payment for your memberships.', 'simple-membership' );
    echo '</div>';

    if( isset( $_POST['swpm_add_new_txn_save_submit'] ) ){
        //Check nonce first
        check_admin_referer( 'swpm_admin_add_new_txn_form_action', 'swpm_admin_add_new_txn_form_field' );

        //let's also store transactions data in swpm_transactions CPT
        $post                = array();
        $post['post_title']  = '';
        $post['post_status'] = 'publish';
        $post['content']     = '';
        $post['post_type']   = 'swpm_transactions';
        $post_id = wp_insert_post( $post );

        $current_date = SwpmUtils::get_current_date_in_wp_zone();

        if (isset($_POST['email_address']) && $_POST['email_address'] != '') {
            $email_address = sanitize_text_field($_POST['email_address']);
            update_post_meta( $post_id, 'email', $email_address);
        }

        if (isset($_POST['first_name']) && $_POST['first_name'] != '') {
            $first_name = sanitize_text_field($_POST['first_name']);
            update_post_meta( $post_id, 'first_name', $first_name);
        }

        if (isset($_POST['last_name']) && $_POST['last_name'] != '') {
            $last_name = sanitize_text_field($_POST['last_name']);
            update_post_meta( $post_id, 'last_name', $last_name);
        }

        if (isset($_POST['member_id']) && $_POST['member_id'] != '') {
            $member_id = intval(sanitize_text_field($_POST['member_id']));
            update_post_meta( $post_id, 'member_id', $member_id);
        }

        if (isset($_POST['membership_level_id']) && $_POST['membership_level_id'] != '') {
            $membership_level_id = intval(sanitize_text_field($_POST['membership_level_id']));
            update_post_meta( $post_id, 'membership_level', $membership_level_id);
        }

        if (isset($_POST['payment_amount']) && !empty($_POST['payment_amount'])) {
            $payment_amount = sanitize_text_field($_POST['payment_amount']);
            // Validate if value is a float
            if (!empty(floatval($payment_amount))) {
                update_post_meta( $post_id, 'payment_amount', $payment_amount);
            }
        }

        if (isset($_POST['discount_amount']) && !empty($_POST['discount_amount'])) {
            $discount_amount = sanitize_text_field($_POST['discount_amount']);
            // Validate if value is a float
            if (!empty(floatval($discount_amount))) {
                update_post_meta( $post_id, 'discount_amount', $discount_amount);
            }
        }

        $txn_date = isset ( $_POST['txn_date'] ) ? sanitize_text_field( $_POST['txn_date' ] ) : $current_date;
        update_post_meta( $post_id, 'txn_date', $txn_date);

        $txn_id = isset ( $_POST['txn_id'] ) ? sanitize_text_field( $_POST['txn_id' ] ) : '';
        update_post_meta( $post_id, 'txn_id', $txn_id);

        $subscr_id = isset ( $_POST['subscriber_id'] ) ? sanitize_text_field( $_POST['subscriber_id' ] ) : '';
        update_post_meta( $post_id, 'subscr_id', $subscr_id);

        $txn_status = isset ( $_POST['txn_status'] ) ? sanitize_text_field( $_POST['txn_status' ] ) : '';
        update_post_meta( $post_id, 'status', $txn_status);

        update_post_meta( $post_id, 'reference', '');
        update_post_meta( $post_id, 'ip_address', '');
        update_post_meta( $post_id, 'gateway', 'manual');

        SwpmLog::log_simple_debug("Manual transaction added successfully.", true);

        echo '<div class="swpm-orange-box">';
        _e('Manual transaction added successfully. ', 'simple-membership');
        echo '<a href="admin.php?page=simple_wp_membership_payments">'.__('View all transactions', 'wp-express-checkout').'</a>';
        echo '</div>';

    } else {
        //Show the form to add manual txn record
        swpm_show_add_new_txn_form();
    }

}

function swpm_show_add_new_txn_form(){
    ?>
    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php _e('Add New Transaction', 'simple-membership'); ?></label></h3>
        <div class="inside">

            <form id="pp_button_config_form" method="post">
                <table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">

                    <tr valign="top">
                        <th scope="row"><?php _e('Email Address', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="70" name="email_address" value="" required />
                            <p class="description"><?php _e('Email address of the customer.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('First Name', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="50" name="first_name" value="" required />
                            <p class="description"><?php _e('First name of the customer.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Last Name', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="50" name="last_name" value="" required />
                            <p class="description"><?php _e('Last name of the customer.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Member ID', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="20" name="member_id" value="" />
                            <p class="description"><?php _e('The Member ID number of the member\'s profile that corresponds to this transaction.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Membership Level', 'simple-membership'); ?></th>
                        <td>
                            <select id="membership_level_id" name="membership_level_id">
                                <?php echo SwpmUtils::membership_level_dropdown(); ?>
                            </select>
                            <p class="description"><?php _e('Select the membership level this transaction is for.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Amount', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="10" name="payment_amount" value="" required />
                            <p class="description"><?php _e('Enter the payment amount. Example values: 10.00 or 19.50 or 299.95 etc (do not put currency symbol).')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Discount Amount', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="10" name="discount_amount" value=""/>
                            <p class="description"><?php _e('Enter the discount amount. Example values: 10.00 or 19.50 or 299.95 etc (do not put currency symbol).')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Date', 'simple-membership'); ?></th>
                        <td>
                            <input type="date" size="20" name="txn_date" value="" />
                            <p class="description"><?php _e('The date for this transaction.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Transaction ID', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="50" name="txn_id" value="" />
                            <p class="description"><?php _e('The unique transaction ID of this transaction so you can identify it easily.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Subscriber ID', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="50" name="subscriber_id" value="" />
                            <p class="description"><?php _e('The subscriber ID (if any) from the member\'s profile.')?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Status/Note', 'simple-membership'); ?></th>
                        <td>
                            <input type="text" size="50" name="txn_status" value="" />
                            <p class="description"><?php _e('A status value for this transaction. This will go to the Status/Note column of the transaction record.')?></p>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <?php wp_nonce_field( 'swpm_admin_add_new_txn_form_action', 'swpm_admin_add_new_txn_form_field' ) ?>
                    <input type="submit" name="swpm_add_new_txn_save_submit" class="button-primary" value="<?php _e('Save Transaction Data', 'simple-membership'); ?>" >
                </p>

            </form>

        </div>
    </div>
    <?php
}