<?php

/*******************************************************************
 * Render and process the interface for edit transaction.
 ******************************************************************/

function swpm_handle_edit_txn()
{
	if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
		wp_die(__('Error! ID must be provided.', 'simple-membership'));
	}

	$post = get_post(sanitize_text_field($_GET['id']));
	if (empty($post)) {
		wp_die(__('Error! Transaction record with the ID not found!.', 'simple-membership'));
	}

	if (isset($_POST['swpm_txn_save_submit'])) {

		check_admin_referer('swpm_admin_edit_txn_form_nonce_action', 'swpm_admin_edit_txn_form_nonce_field');

		if (isset($_POST['swpm_txn_first_name']) && $_POST['swpm_txn_first_name'] != '') {
			$first_name = sanitize_text_field($_POST['swpm_txn_first_name']);
			update_post_meta($post->ID, 'first_name', $first_name);
		}
		if (isset($_POST['swpm_txn_last_name']) && $_POST['swpm_txn_last_name'] != '') {
			$last_name = sanitize_text_field($_POST['swpm_txn_last_name']);
			update_post_meta($post->ID, 'last_name', $last_name);
		}
		if (isset($_POST['swpm_txn_email_address']) && $_POST['swpm_txn_email_address'] != '') {
			$email_address = sanitize_text_field($_POST['swpm_txn_email_address']);
			update_post_meta($post->ID, 'email', $email_address);
		}
		if (isset($_POST['swpm_txn_ipaddress']) && $_POST['swpm_txn_ipaddress'] != '') {
			$ip_address = sanitize_text_field($_POST['swpm_txn_ipaddress']);
			update_post_meta($post->ID, 'ip_address', $ip_address);
		}
		if (isset($_POST['swpm_txn_payment_amount']) && !empty($_POST['swpm_txn_payment_amount'])) {
			$payment_amount = sanitize_text_field($_POST['swpm_txn_payment_amount']);
			// Validate if value is a float
			if (!empty(floatval($payment_amount))) {
				update_post_meta($post->ID, 'payment_amount', $payment_amount);
			}
		}
		if (isset($_POST['swpm_txn_discount_amount']) && !empty($_POST['swpm_txn_discount_amount'])) {
			$discount_amount = sanitize_text_field($_POST['swpm_txn_discount_amount']);
			// Validate if value is a float
			if (!empty(floatval($discount_amount))) {
				update_post_meta($post->ID, 'discount_amount', $discount_amount);
			}
		}
		if (isset($_POST['swpm_txn_reference']) && $_POST['swpm_txn_reference'] != '') {
			$reference = sanitize_text_field($_POST['swpm_txn_reference']);
			update_post_meta($post->ID, 'reference', $reference);
		}

		echo '<div class="notice notice-success"><p>';
		_e('Transaction data updated successfully. ', 'simple-membership');
		echo '<a href="admin.php?page=simple_wp_membership_payments">'. __('View all transactions', 'simple-membership') .'</a>';
		echo '</p></div>';

		SwpmLog::log_simple_debug("Transaction data updated successfully.", true);

		// $redirect_to = admin_url(). "/admin.php?page=simple_wp_membership_payments";
		// SwpmMiscUtils::redirect_to_url($redirect_to);
	}

    if ( isset($_POST['swpm_admin_cancel_subscr_submit']) ) {
        check_admin_referer('swpm_admin_cancel_sub_nonce_action');

        $subscr_id  = isset($_POST['swpm_admin_cancel_subscr_id']) ? sanitize_text_field($_POST['swpm_admin_cancel_subscr_id']) : '';
        $gateway    = isset($_POST['swpm_admin_cancel_subscr_gateway']) ? sanitize_text_field($_POST['swpm_admin_cancel_subscr_gateway']) : '';
        $member_id  = isset($_POST['swpm_admin_cancel_subscr_member_id']) ? sanitize_text_field($_POST['swpm_admin_cancel_subscr_member_id']) : '';

        if (empty($subscr_id) || empty($gateway) || empty($member_id)){
            wp_die( __('Some subscription cancel related required fields not found!', 'simple-membership') );
        }

        $subscription_utils = new SWPM_Utils_Subscriptions( $member_id );
        $subscription_utils->load_subs_data_by_sub_id( $subscr_id );
        $subscriptions_data = $subscription_utils->get_subscription_data($subscr_id);
        if (empty($subscriptions_data)){
            // Subscription record not found.
            wp_die( __('Subscription record not found.', 'simple-membership') );
        }

        $response = $subscription_utils->dispatch_subscription_cancel_request($subscr_id, $gateway);
        if ($response !== true){
            wp_die($response);
        }

        // Subscription cancellation done, redirect to transactions list table page.
        $txn_list_table_url = admin_url('admin.php?page=simple_wp_membership_payments') ;

        $sub_cancel_msg = '<div class="swpm-yellow-box">';
        $sub_cancel_msg .= '<div>';
        $sub_cancel_msg .= __('Your subscription cancellation request has been successfully processed. The payment gateway may take a few seconds to complete the process.', 'simple-membership');
        $sub_cancel_msg .= '</div>';
        $sub_cancel_msg .= '<p>';
        $sub_cancel_msg .= '<a href="'.$txn_list_table_url.'">'.__('Go to the transactions page', 'simple-membership').'</a>' .__(' to view another transaction.', 'simple-membership');
		$sub_cancel_msg .= '</p>';
        $sub_cancel_msg .= '</div>';

        echo $sub_cancel_msg;
		//Return from this function as we don't want to show the edit transaction form.
        return;
    }

	//Show the transaction edit from.
	swpm_show_edit_txn_form($post);
}

function swpm_show_edit_txn_form($post)
{
	$post_id = $post->ID;

	$txn_date = get_post_meta($post_id, 'txn_date', true);
	$txn_id = get_post_meta($post_id, 'txn_id', true);
	$subscr_id = get_post_meta($post_id, 'subscr_id', true);
	if (empty($subscr_id)) {
		$subscr_id = '-';
	}

	$email = get_post_meta($post_id, 'email', true);
	$first_name = get_post_meta($post_id, 'first_name', true);
	$last_name = get_post_meta($post_id, 'last_name', true);

	//Get the member ID that maybe associated with this transaction.
	$member_id = get_post_meta($post_id, 'member_id', true);
	if (empty($member_id) && !empty($subscr_id)){
		//Try to get the member ID from the Subscription ID reference.
		$member_record = SwpmMemberUtils::get_user_by_subsriber_id( $subscr_id );
		if ( $member_record ) {
			$member_id = $member_record->member_id;
		}
	}
	$profile_link_output = '';
	if ( empty( $member_id ) ) {
		//If we still can't find the member ID, set it to a dash. The corresponding member profile may have been deleted.
		$member_id = '-';
	} else {
		if( !SwpmMemberUtils::member_record_exists( $member_id ) ) {
			//Looks like the profile may have been deleted. Add a note to the profile link.
			$profile_link_output = ' <span style="color:red;">' . __('(Profile Deleted)', 'simple-membership') . '</span>';
		} else {
			//Generate the corresponding member profile view/edit link		
			$profile_url = 'admin.php?page=simple_wp_membership&member_action=edit&member_id=' . esc_attr($member_id);
			$profile_link_output = '<a href="' . esc_url($profile_url) . '" target="_blank">' . __('(View Profile)', 'simple-membership') . '</a>';
		}
	}

	$membership_level_link_output = '';
	$membership_level_id = get_post_meta($post_id, 'membership_level', true);
	if (!empty($membership_level_id)) {
		//Get the membership level name.
		$membership_level_name = SwpmMembershipLevelUtils::get_membership_level_name_by_level_id($membership_level_id);

		//Generate the corresponding membership level view/edit link.
		$membership_level_url = 'admin.php?page=simple_wp_membership_levels&level_action=edit&id=' . esc_attr($membership_level_id);
		$membership_level_link_output = '<a href="' . esc_url($membership_level_url) . '" target="_blank">' . __('(View Membership Level)', 'simple-membership') . '</a>';
	} else {
		$membership_level_name = '-';
	}

	//We will use this field to save any additional note or reference for the transaction.
	$reference = get_post_meta($post_id, 'reference', true);
	
	$payment_amount = get_post_meta($post_id, 'payment_amount', true);

	$gateway_raw = get_post_meta($post_id, 'gateway', true);
	if (!empty($gateway_raw)) {
		$gateway_formatted = SwpmUtils::get_formatted_payment_gateway_name($gateway_raw);
	} else {
		$gateway_formatted = '-';
	}

	//Get the transaction type.
	// We now save the following transaction types for the 3 supported subscription gateways: 
	// 1) stripe_subscription_new
    // 2) pp_subscription_new
    // 3) pp_std_subscription_new
	$txn_type_raw = get_post_meta($post_id, 'txn_type', true);

	$status = get_post_meta($post_id, 'status', true);
	$ip_address = get_post_meta($post_id, 'ip_address', true);

	$payment_button_link_output = '';
	$payment_button_id = get_post_meta($post_id, 'payment_button_id', true);
	if (empty($payment_button_id)) {
		$payment_button_id = '-';
	} else {
		//Get the payment button type so we can link to the correct edit page for it to view the button configuration.
		$button_type = get_post_meta($payment_button_id, 'button_type', true);
		$payment_button_src = admin_url() . 'admin.php?page=simple_wp_membership_payments&tab=edit_button&button_id=' . esc_attr($payment_button_id) . '&button_type=' . esc_attr($button_type);
		$payment_button_link_output = '<a href="' . esc_url($payment_button_src) . '" target="_blank">' . __('(View Button Configuration)', 'simple-membership') . '</a>';
	}

	$is_live = get_post_meta($post_id, 'is_live', true);
	if( $is_live == 'yes' || $is_live == 'no' ){
		//This field has been set using the new 'yes' or 'no' value.
		$is_live = ucfirst($is_live);
	} else {
		//This field has been set using the old '1' or '0' value.
		if (!empty($is_live)) {
			$is_live = __("Yes", 'simple-membership');
		} else {
			$is_live = __("No", 'simple-membership');
		}
	}

	$discount_amount = get_post_meta($post_id, 'discount_amount', true);
	if (empty($discount_amount)) {
		$discount_amount = floatval(0);
	}

	$custom = get_post_meta($post_id, 'custom', true);
	if (empty($custom)) {
		$custom = '-';
	}

    $subscr_status = get_post_meta($post_id, 'subscr_status', true);

    $is_subscr_agreement_post = false;
    if ($status == 'subscription created' && in_array($gateway_raw, array('stripe-sca-subs', 'paypal_subscription_checkout'))){
        $is_subscr_agreement_post = true;
    }

    $subscr_status_active = false;
    $show_action_postbox = false;
    $is_active_subscr_status_retrieved_via_api_call = false;

	//Handle the PPCP and Stripe subscription agreement posts (these ones can be queried via API so we handle these separately to PP STD Subs).	
    if ( $is_subscr_agreement_post && !in_array($subscr_status, array('canceled', 'cancelled')) ) {
        $subscr_status_active = true;

        // Show the actions postbox if active subscription status.
        $show_action_postbox = $subscr_status_active;

        // Get the actual subscription status value via api call.
        $subscription_utils = new SWPM_Utils_Subscriptions( $member_id );
        $subscription_utils->load_subs_data_by_sub_id( $subscr_id );
        $subscriptions_data = $subscription_utils->get_subscription_data($subscr_id);
        if ( $subscriptions_data ){
            $subscr_status_retrieved_via_api_call = $subscriptions_data['status'];
            if ( SWPM_Utils_Subscriptions::is_active_status( $subscr_status_retrieved_via_api_call ) ){
                $is_active_subscr_status_retrieved_via_api_call = true;
            } else {
                // Update the 'subscr_status' meta value for older transactions records.
                // When an admin enters the transaction details page, this postmeta will be updated based on the actual subscription status.
                update_post_meta( $post_id, 'subscr_status', $subscr_status_retrieved_via_api_call );
            }
        }
    }

	//Check if this is a PayPal Standard subscription agreement and the subscr_status meta is 'canceled'.
	//Note: we handle this subscr_status separately to the PPCP and Stripe subs since the PPCP and Stripe subs can be queried via API.
	$is_pp_std_subscr_status_canceled = false;
	if( $txn_type_raw == 'pp_std_subscription_new' && in_array($subscr_status, array('canceled', 'cancelled'))){
		//This is a PP Std Subscr agreement type txn and the subscr_status is canceled.
		$is_pp_std_subscr_status_canceled = true;
	}
?>

	<div class="postbox">
		<h2><?php _e('Edit Transaction', 'simple-membership'); ?></h2>
		<div class="inside">
			<form id="swpm-edit-txn-form" method="post">
				<table class="widefat" style="border: none;">
					<tr>
						<td><?php _e("Post ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($post_id); ?></td>
					</tr>
					<tr>
						<td><?php _e("Transaction ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($txn_id); ?></td>
					</tr>
					<tr>
						<td><?php _e("Transaction Date", "simple-membership"); ?></td>
						<td><?php echo esc_attr(SwpmUtils::get_formatted_and_translated_date_according_to_wp_settings($txn_date)) ?></td>
					</tr>
					<tr>
						<td><?php _e("Subscription ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($subscr_id); ?></td>
					</tr>
					<tr>
						<td><?php _e("Payment Gateway", "simple-membership"); ?></td>
						<td><?php echo esc_attr($gateway_formatted); ?></td>
					</tr>
					<?php if (!empty($txn_type_raw)) { ?>
						<!-- Some older transactions may not have the txn_type. So only show the transaction type field if it is set. -->
						<tr>
							<td><?php _e("Transaction Type", "simple-membership"); ?></td>
							<td><?php echo esc_attr($txn_type_raw); ?></td>
						</tr>
					<?php } ?>
                    <?php if ( $is_subscr_agreement_post && !$subscr_status_active && !$is_active_subscr_status_retrieved_via_api_call ){ ?>
						<!-- This block handles the PPCP and Stripe subscription agreement type txn. These type of txns can be queried via API -->
						<!-- If this is a subscription agreement (sub-created) type txn and the status is not active, we show the status using the 'subscr_status' post meta. -->
						<tr>
							<td><?php _e("Subscription Payment Status", "simple-membership"); ?></td>
							<td>
								<span class="swpm_status_subscription_cancelled"><?php echo ucfirst(esc_attr($subscr_status)); ?></span>
							</td>
						</tr>
					<?php } else if ( $is_pp_std_subscr_status_canceled ){ ?>
						<!-- This block handles the PayPal standard subscription agreement type txn (it cannot be queried via API) -->
						<!-- If this is a PayPal standard subscription agreement (sub-created) type txn, and the subscr_status is canceled, we show the canceled status. -->
						<tr>
							<td><?php _e("Subscription Payment Status", "simple-membership"); ?></td>
							<td>
								<span class="swpm_status_subscription_cancelled"><?php echo ucfirst(esc_attr($subscr_status)); ?></span>
							</td>
						</tr>
                    <?php } else { ?>
						<!-- show the status field for all other types of transactions -->
						<tr>
							<td><?php _e("Status", "simple-membership"); ?></td>
							<td><?php echo ucfirst(esc_attr($status)); ?></td>
						</tr>
					<?php } ?>

					<tr>
						<td><?php _e("First Name", "simple-membership"); ?></td>
						<td><input type="text" size="40" name="swpm_txn_first_name" value="<?php echo esc_attr($first_name); ?>" /></td>
					</tr>
					<tr>
						<td><?php _e("Last Name", "simple-membership"); ?></td>
						<td><input type="text" size="40" name="swpm_txn_last_name" value="<?php echo esc_attr($last_name); ?>" /></td>
					</tr>
					<tr>
						<td><?php _e("Email Address", "simple-membership"); ?></td>
						<td><input type="text" size="40" name="swpm_txn_email_address" value="<?php echo esc_attr($email); ?>" /></td>
					</tr>
					<tr>
						<td><?php _e("IP Address", "simple-membership"); ?></td>
						<td><input type="text" size="40" name="swpm_txn_ipaddress" value="<?php echo esc_attr($ip_address); ?>" /></td>
					</tr>
					<tr>
						<td><?php _e("Payment Amount", "simple-membership"); ?></td>
						<td><input type="text" size="20" name="swpm_txn_payment_amount" value="<?php echo esc_attr($payment_amount); ?>" /></td>
					</tr>
					<?php 
					// Only show the discount amount field if there is a discount amount.
					if( !empty($discount_amount) && $discount_amount > 0 ) {
					?>
					<tr>
						<td><?php _e("Discount Amount", "simple-membership"); ?></td>
						<td><input type="text" size="20" name="swpm_txn_discount_amount" value="<?php echo esc_attr($discount_amount); ?>" /></td>
					</tr>
					<?php } ?>
					<tr>
						<td><?php _e("Note/Reference", "simple-membership"); ?></td>
						<td><input type="text" size="20" name="swpm_txn_reference" value="<?php echo esc_attr($reference); ?>" /></td>
					</tr>

					<!-- Additional Data -->
					<tr>
						<td colspan="2">
							<div style="border-bottom: 1px solid #dedede; height: 10px"></div>
						</td>
					</tr>
					<tr>
						<td><?php _e("Member ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($member_id) . ' ' . $profile_link_output; ?></td>
					</tr>
					<tr>
						<td><?php _e("Membership Level", "simple-membership"); ?></td>
						<td><?php echo esc_attr($membership_level_name) . ' ' . $membership_level_link_output; ?></td>
					</tr>					
					<tr>
						<td><?php _e("Payment Button ID", "simple-membership"); ?></td>
						<td>
							<?php echo esc_attr($payment_button_id) . ' ' . $payment_button_link_output; ?>
						</td>
					</tr>
					<tr>
						<td><?php _e("Live Mode Transaction?", "simple-membership"); ?></td>
						<td><?php echo esc_attr($is_live); ?></td>
					</tr>
					<tr>
						<td><?php _e("Custom (System Data)", "simple-membership"); ?></td>
						<td><?php echo esc_attr($custom); ?></td>
					</tr>
                </table>

				<p class="submit">
					<?php wp_nonce_field('swpm_admin_edit_txn_form_nonce_action', 'swpm_admin_edit_txn_form_nonce_field') ?>
					<input type="submit" name="swpm_txn_save_submit" class="button-primary" value="<?php _e('Save Transaction Data', 'simple-membership'); ?>">
				</p>
			</form>
		</div>
	</div>
    <?php
    // echo '<pre>' . print_r(get_post_meta($post_id), true) . '</pre>';
    /**
     * Check if it is a subscription agreement record.
     * Then check if the gateway is stripe-sca or papal-ppcp.
     * And also check if the 'subscr_status' is not set to 'cancelled'.
     * Only then show the action postbox.
     */
    if ($show_action_postbox) {
    ?>
    <div class="postbox">
        <h2>
            <?php _e('Cancel Subscription', 'simple-membership') ?>
        </h2>
        <div class="inside">
            <?php
            /**
             * For backward compatibility, we also need to check if the subscription is already cancelled or not via api call.
             */
            if ( $is_active_subscr_status_retrieved_via_api_call ){
            ?>
            <p><?php _e('You can use the button below to cancel the subscription. The subscription is canceled immediately once you confirm the cancellation.' , 'simple-membership'); ?> </p>
            <div class="swpm-yellow-box">
                <b><?php _e('NOTE:', 'simple-membership') ?></b> <?php _e('Canceled subscriptions cannot be reactivated. The user can purchase a new subscription if needed.', 'simple-membership'); ?>
            </div>
            <form method="post" class="swpm-admin-cancel-subscription-form">
                <?php echo wp_nonce_field( 'swpm_admin_cancel_sub_nonce_action' );?>
                <input type="hidden" name="swpm_admin_cancel_subscr_id" value="<?php echo esc_attr($subscr_id);?>">
                <input type="hidden" name="swpm_admin_cancel_subscr_gateway" value="<?php echo esc_attr($gateway_raw);?>">
                <input type="hidden" name="swpm_admin_cancel_subscr_member_id" value="<?php echo esc_attr($member_id);?>">
                <button
                        type="submit"
                        class="swpm-cancel-subscription-button swpm-cancel-subscription-button-active"
                        name="swpm_admin_cancel_subscr_submit"
                        onclick="return confirm(' <?php _e( 'Are you sure that you want to cancel this subscription?', 'simple-membership' )?> ')"
                >
                    <?php _e('Cancel Subscription', 'simple-membership') ?>
                </button>
            </form>
            <?php } else { ?>
                <div class="swpm-yellow-box">
                    <?php _e('This subscription has been cancelled already.', 'simple-membership') ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

<?php
}
