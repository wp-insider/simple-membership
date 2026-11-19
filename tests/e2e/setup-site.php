<?php
/**
 * Endpoint tests: creates the test data on the test site. Run with: wp eval-file tests/e2e/setup-site.php
 * Prints a JSON line with the IDs the tests need (the last line of the output).
 */
global $wpdb;

//Plugin settings: sandbox mode, debug logging (the log helps when a test fails) and the Stripe webhook signing secret.
$settings = SwpmSettings::get_instance();
$settings->set_value( 'enable-sandbox-testing', 'checked="checked"' );
$settings->set_value( 'enable-debug', 'checked="checked"' );
$settings->set_value( 'stripe-webhook-signing-secret', getenv( 'E2E_STRIPE_SECRET' ) );
$settings->save();

//A membership level with no expiry (a subscription cancellation deactivates its members).
$wpdb->insert(
	$wpdb->prefix . 'swpm_membership_tbl',
	array(
		'alias'                      => 'E2E Level',
		'role'                       => 'subscriber',
		'subscription_period'        => '0',
		'subscription_duration_type' => 0,
	)
);
$level_id = (int) $wpdb->insert_id;

//A PayPal Standard Buy Now button.
$button_id = wp_insert_post(
	array(
		'post_type'   => 'swpm_payment_button',
		'post_status' => 'publish',
		'post_title'  => 'E2E PayPal Buy Now',
	)
);
update_post_meta( $button_id, 'button_type', 'pp_buy_now' );
update_post_meta( $button_id, 'membership_level_id', $level_id );
update_post_meta( $button_id, 'payment_amount', '19.95' );
update_post_meta( $button_id, 'payment_currency', 'USD' );
update_post_meta( $button_id, 'paypal_email', 'seller@example.com' );

function swpm_e2e_member( $data, $level_id ) {
	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'swpm_members_tbl',
		array_merge(
			array(
				'user_name'             => '',
				'first_name'            => 'E2E',
				'last_name'             => 'Member',
				'password'              => '',
				'member_since'          => gmdate( 'Y-m-d' ),
				'membership_level'      => $level_id,
				'account_state'         => 'active',
				'last_accessed_from_ip' => '',
				'reg_code'              => '',
				'subscr_id'             => '',
			),
			$data
		)
	);
	return (int) $wpdb->insert_id;
}

function swpm_e2e_txn( $data ) {
	SwpmTransactions::save_txn_record(
		array_merge(
			array(
				'custom'      => '',
				'payer_email' => 'e2e-buyer@example.com',
				'first_name'  => 'E2E',
				'last_name'   => 'Buyer',
				'subscr_id'   => '',
				'mc_gross'    => '19.95',
				'gateway'     => 'stripe-sca-subs',
				'status'      => 'subscription created',
				'txn_type'    => 'stripe_subscription_new',
			),
			$data
		)
	);
}

//PayPal Standard: an existing member who still needs to complete the registration (an email match must not get the browser proof).
$pending_member_id = swpm_e2e_member( array( 'email' => 'existing-pending@example.com', 'reg_code' => md5( 'e2e-existing-pending' ) ), $level_id );

//Stripe: a subscription created on this site (member and agreement record).
$site_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_site_sub', 'email' => 'site-sub@example.com', 'subscr_id' => 'sub_E2E_SITE' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_SITE', 'subscr_id' => 'sub_E2E_SITE' ) );

//Stripe Legacy: the member's subscr_id and the checkout record's txn_id are the customer ID, and the record has no subscr_id (before 2023).
swpm_e2e_member( array( 'user_name' => 'e2e_legacy_sub', 'email' => 'legacy-sub@example.com', 'subscr_id' => 'cus_E2E_LEGACY' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_LEGACY', 'status' => 'completed', 'txn_type' => '', 'gateway' => 'stripe' ) );

//Stripe: a subscription that will be cancelled.
$cancel_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_cancel_sub', 'email' => 'cancel-sub@example.com', 'subscr_id' => 'sub_E2E_CANCEL' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_CANCEL', 'subscr_id' => 'sub_E2E_CANCEL' ) );

//Stripe: a one-time payment that will be refunded (one-time payments use the charge ID as the subscr_id).
$refund_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_refund', 'email' => 'refund@example.com', 'subscr_id' => 'ch_E2E_ONE_TIME' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'ch_E2E_ONE_TIME', 'subscr_id' => 'ch_E2E_ONE_TIME', 'status' => 'Completed', 'txn_type' => '', 'gateway' => 'stripe-sca', 'mc_gross' => '10.00' ) );

echo "\n" . wp_json_encode(
	array(
		'level_id'          => $level_id,
		'button_id'         => (int) $button_id,
		'pending_member_id' => $pending_member_id,
		'site_member_id'    => $site_member_id,
		'cancel_member_id'  => $cancel_member_id,
		'refund_member_id'  => $refund_member_id,
	)
) . "\n";
