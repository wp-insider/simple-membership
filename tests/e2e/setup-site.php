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
//The renewal members have an old access start date, so the tests can check that a renewal updates it.
$site_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_site_sub', 'email' => 'site-sub@example.com', 'subscr_id' => 'sub_E2E_SITE', 'subscription_starts' => '2020-01-01' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_SITE', 'subscr_id' => 'sub_E2E_SITE' ) );

//Stripe Legacy: the member's subscr_id and the checkout record's txn_id are the customer ID, and the record has no subscr_id (before 2023).
//One member each for the renewal, the cancellation and the refund, so one test's result can't make another one pass.
$legacy_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_legacy_sub', 'email' => 'legacy-sub@example.com', 'subscr_id' => 'cus_E2E_LEGACY', 'subscription_starts' => '2020-01-01' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_LEGACY', 'status' => 'completed', 'txn_type' => '', 'gateway' => 'stripe' ) );
$legacy_cancel_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_legacy_cancel', 'email' => 'legacy-cancel@example.com', 'subscr_id' => 'cus_E2E_LEGACY_CANCEL' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_LEGACY_CANCEL', 'status' => 'completed', 'txn_type' => '', 'gateway' => 'stripe' ) );
$legacy_refund_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_legacy_refund', 'email' => 'legacy-refund@example.com', 'subscr_id' => 'cus_E2E_LEGACY_REFUND' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_LEGACY_REFUND', 'status' => 'completed', 'txn_type' => '', 'gateway' => 'stripe' ) );

//Stripe: a subscription that will be cancelled.
$cancel_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_cancel_sub', 'email' => 'cancel-sub@example.com', 'subscr_id' => 'sub_E2E_CANCEL' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'cus_E2E_CANCEL', 'subscr_id' => 'sub_E2E_CANCEL' ) );

//Stripe: a one-time payment that will be refunded (one-time payments use the charge ID as the subscr_id).
$refund_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_refund', 'email' => 'refund@example.com', 'subscr_id' => 'ch_E2E_ONE_TIME' ), $level_id );
swpm_e2e_txn( array( 'txn_id' => 'ch_E2E_ONE_TIME', 'subscr_id' => 'ch_E2E_ONE_TIME', 'status' => 'Completed', 'txn_type' => '', 'gateway' => 'stripe-sca', 'mc_gross' => '10.00' ) );

//Email activation: a member who needs to activate the account (with a valid activation code), and an active member.
$activation_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_activation', 'email' => 'activation@example.com', 'account_state' => 'activation_required' ), $level_id );
update_option(
	'swpm_email_activation_data_usr_' . $activation_member_id,
	array(
		'act_code'  => 'e2eActivationCode0123456789abcdef',
		'timestamp' => time() - 60,
	),
	false
);
$active_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_active', 'email' => 'active@example.com' ), $level_id );

//Registration forms: a level-specific form for a free (Subscriber) level, and levels with higher roles that a visitor must not be able to join.
function swpm_e2e_level( $alias, $role ) {
	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'swpm_membership_tbl',
		array(
			'alias'                      => $alias,
			'role'                       => $role,
			'subscription_period'        => '0',
			'subscription_duration_type' => 0,
		)
	);
	return (int) $wpdb->insert_id;
}
function swpm_e2e_registration_page( $title, $level_id ) {
	return (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => '[swpm_registration_form level=' . $level_id . ']',
		)
	);
}
$reg_free_level_id   = swpm_e2e_level( 'E2E Free Registration', 'subscriber' );
$reg_editor_level_id = swpm_e2e_level( 'E2E Editor Level', 'editor' );
$reg_admin_level_id  = swpm_e2e_level( 'E2E Administrator Level', 'administrator' );
$reg_free_page_id    = swpm_e2e_registration_page( 'E2E Free Registration', $reg_free_level_id );
$reg_admin_page_id   = swpm_e2e_registration_page( 'E2E Administrator Registration', $reg_admin_level_id );

//A paid Editor level member who still needs to complete the registration (the link sent after the payment).
$reg_paid_member_id = swpm_e2e_member( array( 'email' => 'paid-complete@example.com', 'reg_code' => md5( 'e2e-paid-complete' ) ), $reg_editor_level_id );

//A PayPal Standard subscription button (19.95 a month, 2 payments, no trial), like the one used for the subscription fixtures.
$sub_button_id = wp_insert_post(
	array(
		'post_type'   => 'swpm_payment_button',
		'post_status' => 'publish',
		'post_title'  => 'E2E PayPal Subscription',
	)
);
update_post_meta( $sub_button_id, 'button_type', 'pp_subscription' );
update_post_meta( $sub_button_id, 'membership_level_id', $level_id );
update_post_meta( $sub_button_id, 'payment_currency', 'USD' );
update_post_meta( $sub_button_id, 'paypal_email', 'seller@example.com' );
update_post_meta( $sub_button_id, 'billing_amount', '19.95' );
update_post_meta( $sub_button_id, 'billing_cycle', '1' );
update_post_meta( $sub_button_id, 'billing_cycle_term', 'M' );
update_post_meta( $sub_button_id, 'billing_cycle_count', '2' );
update_post_meta( $sub_button_id, 'trial_billing_cycle', '' );

//PayPal Standard, member ID (swpm_id) in the custom field. Logged-in members who pay (their real member ID is in the custom field).
//The PayPal email is different from the member's email, so the member can only be found by the swpm_id.
$pp_buynow_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_pp_buynow', 'email' => 'pp-buynow-member@example.com', 'account_state' => 'expired' ), $level_id );
$pp_sub_member_id    = swpm_e2e_member( array( 'user_name' => 'e2e_pp_sub', 'email' => 'pp-sub-member@example.com', 'account_state' => 'expired' ), $level_id );
//Members of existing subscriptions, for a refund and a cancellation with an invalid swpm_id (they can be found by the subscription ID).
$pp_bad_id_refund_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_pp_bad_refund', 'email' => 'pp-bad-refund@example.com', 'subscr_id' => 'I-E2E-BAD-REFUND' ), $level_id );
$pp_bad_id_cancel_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_pp_bad_cancel', 'email' => 'pp-bad-cancel@example.com', 'subscr_id' => 'I-E2E-BAD-CANCEL' ), $level_id );

//Admin roles: the plugin's dashboard is opened to Contributors (the "Admin Dashboard Access Permission" setting), like in the security reports.
//The delegated managers (Contributors) must not be able to give anyone a WordPress role with admin powers.
$settings->set_value( 'admin-dashboard-access-permission', 'edit_posts' );
$settings->save();
//A custom role with an admin power (promote_users): treated like the administrator role.
add_role( 'e2e_user_manager', 'E2E User Manager', array( 'read' => true, 'list_users' => true, 'promote_users' => true ) );
//A level with the administrator role, made by the site owner (allowed).
$owner_admin_level_id = swpm_e2e_level( 'E2E Owner Administrator Level', 'administrator' );
//Normal levels: the managers' own levels (one each, because a bulk change moves every member of a level) and a target level.
//The target level keeps the Contributor role, so a manager moved to it can still open the dashboard.
$mgr_target_level_id      = swpm_e2e_level( 'E2E Manager Target Level', 'contributor' );
$mgr_edit_level_id        = swpm_e2e_level( 'E2E Manager Edit Level', 'subscriber' );
$mgr_bulk_one_level_id    = swpm_e2e_level( 'E2E Manager Bulk Level 1', 'subscriber' );
$mgr_bulk_owner_level_id  = swpm_e2e_level( 'E2E Manager Bulk Owner Level', 'subscriber' );
$mgr_bulk_normal_level_id = swpm_e2e_level( 'E2E Manager Bulk Normal Level', 'subscriber' );
$admin_bulk_one_level_id  = swpm_e2e_level( 'E2E Admin Bulk Level 1', 'subscriber' );
$admin_edit_level_id      = swpm_e2e_level( 'E2E Admin Edit Level', 'subscriber' );

//A WP user and its member record (same username and email), for a user who logs in to the dashboard.
function swpm_e2e_wp_member( $user_name, $wp_role, $level_id ) {
	wp_insert_user(
		array(
			'user_login' => $user_name,
			'user_email' => $user_name . '@example.com',
			'user_pass'  => 'E2e-Manager-Password-123!',
			'role'       => $wp_role,
		)
	);
	return swpm_e2e_member( array( 'user_name' => $user_name, 'email' => $user_name . '@example.com', 'password' => SwpmUtils::encrypt_password( 'E2e-Manager-Password-123!' ) ), $level_id );
}
//The delegated managers (Contributors with a member record), one per test, so one test's result can't change another one.
swpm_e2e_wp_member( 'e2e_mgr_level', 'contributor', $mgr_target_level_id );
$mgr_edit_member_id       = swpm_e2e_wp_member( 'e2e_mgr_edit', 'contributor', $mgr_edit_level_id );
$mgr_bulk_one_member_id   = swpm_e2e_wp_member( 'e2e_mgr_bulk_one', 'contributor', $mgr_bulk_one_level_id );
$mgr_bulk_owner_member_id = swpm_e2e_wp_member( 'e2e_mgr_bulk_owner', 'contributor', $mgr_bulk_owner_level_id );
swpm_e2e_wp_member( 'e2e_mgr_button', 'contributor', $mgr_target_level_id );
//A member moved by a manager's normal bulk change (no WP user).
$mgr_bulk_normal_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_bulk_normal', 'email' => 'bulk-normal@example.com' ), $mgr_bulk_normal_level_id );
//The site owner (administrator), and members the owner moves (no WP user, so nobody becomes an administrator).
wp_insert_user( array( 'user_login' => 'e2e_owner', 'user_email' => 'e2e_owner@example.com', 'user_pass' => 'E2e-Manager-Password-123!', 'role' => 'administrator' ) );
$admin_bulk_one_member_id = swpm_e2e_member( array( 'user_name' => 'e2e_admin_bulk_one', 'email' => 'admin-bulk-one@example.com' ), $admin_bulk_one_level_id );
$admin_edit_member_id     = swpm_e2e_member( array( 'user_name' => 'e2e_admin_edit', 'email' => 'admin-edit@example.com', 'password' => SwpmUtils::encrypt_password( 'E2e-Manager-Password-123!' ) ), $admin_edit_level_id );

echo "\n" . wp_json_encode(
	array(
		'level_id'                => $level_id,
		'button_id'               => (int) $button_id,
		'pending_member_id'       => $pending_member_id,
		'site_member_id'          => $site_member_id,
		'legacy_member_id'        => $legacy_member_id,
		'legacy_cancel_member_id' => $legacy_cancel_member_id,
		'legacy_refund_member_id' => $legacy_refund_member_id,
		'cancel_member_id'        => $cancel_member_id,
		'refund_member_id'        => $refund_member_id,
		'activation_member_id'    => $activation_member_id,
		'active_member_id'        => $active_member_id,
		'reg_free_level_id'       => $reg_free_level_id,
		'reg_editor_level_id'     => $reg_editor_level_id,
		'reg_admin_level_id'      => $reg_admin_level_id,
		'reg_free_page_id'        => $reg_free_page_id,
		'reg_admin_page_id'       => $reg_admin_page_id,
		'reg_paid_member_id'      => $reg_paid_member_id,
		'reg_paid_code'           => md5( 'e2e-paid-complete' ),
		'sub_button_id'           => (int) $sub_button_id,
		'pp_buynow_member_id'     => $pp_buynow_member_id,
		'pp_sub_member_id'        => $pp_sub_member_id,
		'pp_bad_id_refund_member_id' => $pp_bad_id_refund_member_id,
		'pp_bad_id_cancel_member_id' => $pp_bad_id_cancel_member_id,
		'owner_admin_level_id'       => $owner_admin_level_id,
		'mgr_target_level_id'        => $mgr_target_level_id,
		'mgr_edit_level_id'          => $mgr_edit_level_id,
		'mgr_edit_member_id'         => $mgr_edit_member_id,
		'mgr_bulk_one_level_id'      => $mgr_bulk_one_level_id,
		'mgr_bulk_one_member_id'     => $mgr_bulk_one_member_id,
		'mgr_bulk_owner_level_id'    => $mgr_bulk_owner_level_id,
		'mgr_bulk_owner_member_id'   => $mgr_bulk_owner_member_id,
		'mgr_bulk_normal_level_id'   => $mgr_bulk_normal_level_id,
		'mgr_bulk_normal_member_id'  => $mgr_bulk_normal_member_id,
		'admin_bulk_one_level_id'    => $admin_bulk_one_level_id,
		'admin_bulk_one_member_id'   => $admin_bulk_one_member_id,
		'admin_edit_member_id'       => $admin_edit_member_id,
	)
) . "\n";
