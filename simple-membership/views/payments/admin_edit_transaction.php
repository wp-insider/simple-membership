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

		// echo '<div class="swpm-orange-box">';
		// _e('Transaction data updated successfully. ', 'simple-membership');
		// echo '<a href="admin.php?page=simple_wp_membership_payments">'. __('View all transactions', 'simple-membership') .'</a>';
		// echo '</div>';

		SwpmLog::log_simple_debug("Transaction data updated successfully.", true);

		$redirect_to = admin_url(). "/admin.php?page=simple_wp_membership_payments";
		SwpmMiscUtils::redirect_to_url($redirect_to);
	} else {
		//Show the transaction edit from.
		swpm_show_edit_txn_form($post);
	}
}

function swpm_show_edit_txn_form($post)
{
	$post_id = $post->ID;

	$email = get_post_meta($post_id, 'email', true);
	$first_name = get_post_meta($post_id, 'first_name', true);
	$last_name = get_post_meta($post_id, 'last_name', true);

	$member_id = get_post_meta($post_id, 'member_id', true);
	if (empty($member_id)) {
		$member_id = 'N/A';
	}

	$membership_level = get_post_meta($post_id, 'membership_level', true);
	if (!empty($membership_level)) {
		$membership_level = SwpmMembershipLevelUtils::get_membership_level_name_by_level_id($membership_level);
	} else {
		$membership_level = 'N/A';
	}

	$txn_date = get_post_meta($post_id, 'txn_date', true);
	$txn_id = get_post_meta($post_id, 'txn_id', true);
	$subscr_id = get_post_meta($post_id, 'subscr_id', true);
	if (empty($subscr_id)) {
		$subscr_id = 'N/A';
	}

	$reference = get_post_meta($post_id, 'reference', true);
	$payment_amount = get_post_meta($post_id, 'payment_amount', true);

	$gateway = get_post_meta($post_id, 'gateway', true);
	if (!empty($gateway)) {
		$gateway = SwpmUtils::get_formatted_payment_gateway_name($gateway);
	} else {
		$gateway = 'N/A';
	}

	$status = get_post_meta($post_id, 'status', true);
	$ip_address = get_post_meta($post_id, 'ip_address', true);
	$payment_button_id = get_post_meta($post_id, 'payment_button_id', true);
	if (empty($payment_button_id)) {
		$payment_button_id = 'N/A';
	}

	$is_live = get_post_meta($post_id, 'is_live', true);
	if (!empty($is_live)) {
		$is_live = __("Yes", 'simple-membership');
	} else {
		$is_live = __("No", 'simple-membership');
	}

	$discount_amount = get_post_meta($post_id, 'discount_amount', true);
	if (empty($discount_amount)) {
		$discount_amount = floatval(0);
	}

	$custom = get_post_meta($post_id, 'custom', true);
	if (empty($custom)) {
		$custom = 'N/A';
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
						<td><?php _e("Subscriber ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($subscr_id); ?></td>
					</tr>
					<tr>
						<td><?php _e("Payment Gateway", "simple-membership"); ?></td>
						<td><?php echo esc_attr($gateway); ?></td>
					</tr>
					<tr>
						<td><?php _e("Status", "simple-membership"); ?></td>
						<td><?php echo ucfirst(esc_attr($status)); ?></td>
					</tr>
					<tr>
						<td><?php _e("Member ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($member_id); ?></td>
					</tr>
					<tr>
						<td><?php _e("Membership Level", "simple-membership"); ?></td>
						<td><?php echo esc_attr($membership_level); ?></td>
					</tr>
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
					<tr>
						<td><?php _e("Discount Amount", "simple-membership"); ?></td>
						<td><input type="text" size="20" name="swpm_txn_discount_amount" value="<?php echo esc_attr($discount_amount); ?>" /></td>
					</tr>
					<tr>
						<td><?php _e("Reference", "simple-membership"); ?></td>
						<td><input type="text" size="20" name="swpm_txn_reference" value="<?php echo esc_attr($reference); ?>" /></td>
					</tr>

					<!-- Additional Data -->
					<tr>
						<td><?php _e("Payment Button ID", "simple-membership"); ?></td>
						<td><?php echo esc_attr($payment_button_id); ?></td>
					</tr>
					<tr>
						<td><?php _e("Is Live", "simple-membership"); ?></td>
						<td><?php echo esc_attr($is_live); ?></td>
					</tr>
					<tr>
						<td><?php _e("Custom", "simple-membership"); ?></td>
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
}
