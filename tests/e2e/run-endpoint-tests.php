<?php
/**
 * Endpoint tests: sends real PayPal Standard IPN and Stripe webhook requests to a running test site, then checks the database.
 * Run by the GitHub Actions "Plugin Tests" workflow (the "Endpoint tests" job sets up the site). Environment variables:
 * E2E_URL (example: http://127.0.0.1:8080), E2E_WP_PATH (the site's folder), E2E_DB_HOST, E2E_DB_NAME, E2E_DB_USER, E2E_DB_PASS,
 * E2E_STRIPE_SECRET (the Stripe webhook signing secret saved on the site by setup-site.php).
 */
$url           = rtrim( getenv( 'E2E_URL' ), '/' );
$wp_path       = getenv( 'E2E_WP_PATH' );
$stripe_secret = getenv( 'E2E_STRIPE_SECRET' );
$db            = new mysqli( getenv( 'E2E_DB_HOST' ), getenv( 'E2E_DB_USER' ), getenv( 'E2E_DB_PASS' ), getenv( 'E2E_DB_NAME' ) );
$prefix        = 'wp_';
$failures      = 0;

function check( $label, $condition ) {
	global $failures;
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		$failures++;
		echo "FAIL: {$label}\n";
	}
}

function wp_cli( $command ) {
	global $wp_path;
	$output = array();
	exec( 'wp --path=' . escapeshellarg( $wp_path ) . ' ' . $command . ' 2>&1', $output, $exit_code );
	if ( 0 !== $exit_code ) {
		echo "WP-CLI command failed: wp {$command}\n" . implode( "\n", $output ) . "\n";
		exit( 1 );
	}
	return implode( "\n", $output );
}

/** Sends a POST request. Returns the HTTP status code. */
function http_post( $endpoint, $body, $headers ) {
	global $url;
	$curl = curl_init( $url . $endpoint );
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
		)
	);
	curl_exec( $curl );
	return (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
}

/** Sends a GET request (redirects are not followed). Returns the HTTP status code and the body. */
function http_get( $endpoint ) {
	global $url;
	$curl = curl_init( $url . $endpoint );
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 30,
		)
	);
	$body = (string) curl_exec( $curl );
	return array(
		'status' => (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE ),
		'body'   => $body,
	);
}

/** An email activation link request. The member ID is replaced, so the responses for different members can be compared. */
function activation_response( $member_id, $token ) {
	$response = http_get( '/?swpm_email_activation=1&swpm_member_id=' . rawurlencode( $member_id ) . '&swpm_token=' . rawurlencode( $token ) );
	$response['body'] = preg_replace( '/swpm_member_id(=|&#0?61;|%3D)\d+/', 'swpm_member_id=ID', $response['body'] );
	return $response;
}

/** The response text without the HTML, shortened (printed when a comparison fails). */
function response_text( $response ) {
	$text = trim( preg_replace( '/\s+/', ' ', strip_tags( preg_replace( '#<(style|script)\b.*?</\1>#s', '', $response['body'] ) ) ) );
	return 'HTTP ' . $response['status'] . ': ' . substr( $text, 0, 300 );
}

/** Checks that two responses are the same (status code and body), and shows both when they differ. */
function check_same_response( $label, $expected, $actual ) {
	$same = $expected['status'] === $actual['status'] && $expected['body'] === $actual['body'];
	check( $label, $same );
	if ( ! $same ) {
		echo '    Expected: ' . response_text( $expected ) . "\n";
		echo '    Actual:   ' . response_text( $actual ) . "\n";
	}
}

function db_value( $sql, $types = '', $params = array() ) {
	global $db;
	$statement = $db->prepare( $sql );
	if ( $types ) {
		$statement->bind_param( $types, ...$params );
	}
	$statement->execute();
	$row = $statement->get_result()->fetch_row();
	return $row ? $row[0] : null;
}

/** The number of transaction records (swpm_transactions posts) with this transaction ID. */
function txn_count( $txn_id ) {
	global $prefix;
	return (int) db_value( "SELECT COUNT(*) FROM {$prefix}postmeta pm JOIN {$prefix}posts p ON p.ID = pm.post_id WHERE p.post_type = 'swpm_transactions' AND pm.meta_key = 'txn_id' AND pm.meta_value = ?", 's', array( $txn_id ) );
}

/** A post meta value of the transaction record with this transaction ID. */
function txn_meta( $txn_id, $key ) {
	global $prefix;
	return db_value( "SELECT m2.meta_value FROM {$prefix}postmeta m1 JOIN {$prefix}postmeta m2 ON m2.post_id = m1.post_id AND m2.meta_key = ? WHERE m1.meta_key = 'txn_id' AND m1.meta_value = ? ORDER BY m1.post_id DESC LIMIT 1", 'ss', array( $key, $txn_id ) );
}

function member_value( $member_id, $column ) {
	global $prefix;
	return db_value( "SELECT {$column} FROM {$prefix}swpm_members_tbl WHERE member_id = ?", 'i', array( $member_id ) );
}

function member_count_by_email( $email ) {
	global $prefix;
	return (int) db_value( "SELECT COUNT(*) FROM {$prefix}swpm_members_tbl WHERE email = ?", 's', array( $email ) );
}

function token_hash_saved( $member_id, $token ) {
	global $prefix;
	return (int) db_value( "SELECT COUNT(*) FROM {$prefix}swpm_members_meta_tbl WHERE member_id = ? AND meta_key = 'paid_reg_browser_token_hash' AND meta_value = ?", 'is', array( $member_id, hash( 'sha256', $token ) ) ) > 0;
}

/** Sends a PayPal Standard IPN made from the anonymized fixture, with the given values. */
function send_ipn( $values ) {
	$fixture = trim( file_get_contents( dirname( __DIR__ ) . '/fixtures/paypal-ipn/web-accept-sandbox.txt' ) );
	$values  = array_merge(
		array(
			'{RECEIVER_EMAIL}' => rawurlencode( 'seller@example.com' ),
			'{AMOUNT}'         => '19.95',
			'{CURRENCY}'       => 'USD',
		),
		$values
	);
	return http_post( '/?swpm_process_ipn=1', strtr( $fixture, $values ), array( 'Content-Type: application/x-www-form-urlencoded' ) );
}

/** Sends a Stripe webhook event, signed with the site's signing secret (or a wrong one). */
function send_stripe_event( $type, $object, $previous_attributes = null, $signing_secret = null ) {
	global $stripe_secret;
	$data = array( 'object' => $object );
	if ( null !== $previous_attributes ) {
		$data['previous_attributes'] = $previous_attributes;
	}
	$payload   = json_encode(
		array(
			'id'          => 'evt_' . bin2hex( random_bytes( 8 ) ),
			'object'      => 'event',
			'type'        => $type,
			'api_version' => '2026-07-29.dahlia',
			'livemode'    => false,
			'created'     => time(),
			'data'        => $data,
		)
	);
	$timestamp = time();
	$signature = hash_hmac( 'sha256', $timestamp . '.' . $payload, null === $signing_secret ? $stripe_secret : $signing_secret );
	return http_post( '/?swpm_process_stripe_subscription=1&hook=1', $payload, array( 'Content-Type: application/json', 'Stripe-Signature: t=' . $timestamp . ',v1=' . $signature ) );
}

function renewal_invoice( $sub_id, $customer_id, $charge_id ) {
	return array(
		'id'             => 'in_' . bin2hex( random_bytes( 8 ) ),
		'object'         => 'invoice',
		'billing_reason' => 'subscription_cycle',
		'subscription'   => $sub_id,
		'customer'       => $customer_id,
		'amount_paid'    => 1995,
		'currency'       => 'usd',
		'customer_name'  => 'Test Buyer',
		'customer_email' => 'e2e-buyer@example.com',
		'charge'         => $charge_id,
	);
}

//Create the test data.
$setup_output = wp_cli( 'eval-file ' . escapeshellarg( __DIR__ . '/setup-site.php' ) );
$lines        = array_filter( array_map( 'trim', explode( "\n", $setup_output ) ) );
$ids          = json_decode( end( $lines ), true );
if ( empty( $ids['button_id'] ) ) {
	echo "Could not create the test data. Output:\n{$setup_output}\n";
	exit( 1 );
}
$button_id = (string) $ids['button_id'];
$level_id  = (string) $ids['level_id'];

echo "--- PayPal Standard IPN ---\n";

//1. A new buyer with a checkout token.
$token = str_repeat( 'c3', 12 );
send_ipn( array( '{PAYER_EMAIL}' => rawurlencode( 'new-buyer@example.com' ), '{BUTTON_ID}' => $button_id, '{LEVEL_ID}' => $level_id, '{TXN_ID}' => 'E2E-NEW-1', '{TOKEN}' => $token ) );
$new_member_id = (int) db_value( "SELECT member_id FROM {$prefix}swpm_members_tbl WHERE email = ?", 's', array( 'new-buyer@example.com' ) );
check( 'A verified IPN creates the transaction record', 1 === txn_count( 'E2E-NEW-1' ) );
check( 'A verified IPN from a new buyer creates a member who needs to complete the registration', $new_member_id > 0 && '' === member_value( $new_member_id, 'user_name' ) );
check( 'The checkout token hash is saved on the new member', $new_member_id > 0 && token_hash_saved( $new_member_id, $token ) );

//2. The same IPN sent again.
send_ipn( array( '{PAYER_EMAIL}' => rawurlencode( 'new-buyer@example.com' ), '{BUTTON_ID}' => $button_id, '{LEVEL_ID}' => $level_id, '{TXN_ID}' => 'E2E-NEW-1', '{TOKEN}' => $token ) );
check( 'A duplicate IPN (same txn_id) is ignored', 1 === txn_count( 'E2E-NEW-1' ) && 1 === member_count_by_email( 'new-buyer@example.com' ) );

//3. A buyer with the email of an existing pending member.
$token_existing = str_repeat( 'd4', 12 );
send_ipn( array( '{PAYER_EMAIL}' => rawurlencode( 'existing-pending@example.com' ), '{BUTTON_ID}' => $button_id, '{LEVEL_ID}' => $level_id, '{TXN_ID}' => 'E2E-EXISTING-1', '{TOKEN}' => $token_existing ) );
check( 'A payment matched to an existing member by email is recorded', 1 === txn_count( 'E2E-EXISTING-1' ) && 1 === member_count_by_email( 'existing-pending@example.com' ) );
check( 'A payment matched to an existing member by email does not save the browser proof (checkout token)', ! token_hash_saved( $ids['pending_member_id'], $token_existing ) );

//4. A wrong receiver email.
send_ipn( array( '{PAYER_EMAIL}' => rawurlencode( 'wrong-receiver@example.com' ), '{RECEIVER_EMAIL}' => rawurlencode( 'someone-else@example.com' ), '{BUTTON_ID}' => $button_id, '{LEVEL_ID}' => $level_id, '{TXN_ID}' => 'E2E-RECEIVER-1', '{TOKEN}' => $token ) );
check( 'An IPN for another PayPal account (receiver email) is rejected', 0 === txn_count( 'E2E-RECEIVER-1' ) && 0 === member_count_by_email( 'wrong-receiver@example.com' ) );

//5. An amount lower than the button price.
send_ipn( array( '{PAYER_EMAIL}' => rawurlencode( 'underpaid@example.com' ), '{AMOUNT}' => '1.00', '{BUTTON_ID}' => $button_id, '{LEVEL_ID}' => $level_id, '{TXN_ID}' => 'E2E-UNDERPAID-1', '{TOKEN}' => $token ) );
check( 'An IPN with an amount lower than the price is rejected', 0 === txn_count( 'E2E-UNDERPAID-1' ) && 0 === member_count_by_email( 'underpaid@example.com' ) );

//6. PayPal answers INVALID to the verification request.
wp_cli( 'option update swpm_e2e_ipn_answer INVALID' );
send_ipn( array( '{PAYER_EMAIL}' => rawurlencode( 'invalid@example.com' ), '{BUTTON_ID}' => $button_id, '{LEVEL_ID}' => $level_id, '{TXN_ID}' => 'E2E-INVALID-1', '{TOKEN}' => $token ) );
wp_cli( 'option delete swpm_e2e_ipn_answer' );
check( 'An IPN that PayPal does not verify is rejected', 0 === txn_count( 'E2E-INVALID-1' ) && 0 === member_count_by_email( 'invalid@example.com' ) );

echo "--- Stripe webhook ---\n";

//7. A renewal of a subscription created on this site.
$status = send_stripe_event( 'invoice.payment_succeeded', renewal_invoice( 'sub_E2E_SITE', 'cus_E2E_SITE', 'ch_E2E_SITE_R1' ) );
check( 'A renewal webhook returns HTTP 200', 200 === $status );
check( 'A renewal of a subscription created on this site is recorded', 1 === txn_count( 'ch_E2E_SITE_R1' ) );
check( 'The renewal record is linked to the member', (string) $ids['site_member_id'] === (string) txn_meta( 'ch_E2E_SITE_R1', 'member_id' ) );

//8. A renewal of another site's subscription (same Stripe account).
$status = send_stripe_event( 'invoice.payment_succeeded', renewal_invoice( 'sub_E2E_OTHER_SITE', 'cus_E2E_OTHER_SITE', 'ch_E2E_OTHER_R1' ) );
check( "A renewal of another site's subscription returns HTTP 200 and is ignored", 200 === $status && 0 === txn_count( 'ch_E2E_OTHER_R1' ) );

//9. A renewal of a Stripe Legacy subscription (found by the customer ID).
send_stripe_event( 'invoice.payment_succeeded', renewal_invoice( 'sub_E2E_LEGACY', 'cus_E2E_LEGACY', 'ch_E2E_LEGACY_R1' ) );
check( 'A renewal of a Stripe Legacy subscription (found by the customer ID) is recorded', 1 === txn_count( 'ch_E2E_LEGACY_R1' ) );
check( 'The Stripe Legacy renewal record is linked to the member', (string) $ids['legacy_member_id'] === (string) txn_meta( 'ch_E2E_LEGACY_R1', 'member_id' ) );

//10. A webhook with a wrong signature.
$status = send_stripe_event( 'invoice.payment_succeeded', renewal_invoice( 'sub_E2E_SITE', 'cus_E2E_SITE', 'ch_E2E_BAD_SIGNATURE' ), null, 'whsec_wrong_secret' );
check( 'A webhook with a wrong signature is rejected (HTTP 400) and nothing is saved', 400 === $status && 0 === txn_count( 'ch_E2E_BAD_SIGNATURE' ) );

//11. A subscription cancellation.
send_stripe_event( 'customer.subscription.deleted', array( 'id' => 'sub_E2E_CANCEL', 'object' => 'subscription', 'customer' => 'cus_E2E_CANCEL', 'status' => 'canceled' ) );
check( 'A cancelled subscription deactivates the member of a no-expiry level', 'inactive' === member_value( $ids['cancel_member_id'], 'account_state' ) );
//The cancel handler saves 'cancelled' on the member's original checkout record and 'canceled' on the agreement record (here, the same record).
check( "The cancellation updates the subscription agreement record's status", in_array( txn_meta( 'cus_E2E_CANCEL', 'subscr_status' ), array( 'canceled', 'cancelled' ), true ) );

//12. A full refund of a one-time payment.
send_stripe_event(
	'charge.refunded',
	array( 'id' => 'ch_E2E_ONE_TIME', 'object' => 'charge', 'customer' => 'cus_E2E_ONE_TIME', 'amount' => 1000, 'amount_refunded' => 1000, 'refunded' => true, 'currency' => 'usd' ),
	array( 'amount_refunded' => 0 )
);
check( 'A full refund marks the payment record as Refunded', 'Refunded' === txn_meta( 'ch_E2E_ONE_TIME', 'status' ) );
check( 'A refund deactivates the member (default refund setting)', 'inactive' === member_value( $ids['refund_member_id'], 'account_state' ) );

//13. A Stripe Legacy subscription cancellation (the member and the checkout record have the customer ID, not the subscription ID).
send_stripe_event( 'customer.subscription.deleted', array( 'id' => 'sub_E2E_LEGACY_CANCEL', 'object' => 'subscription', 'customer' => 'cus_E2E_LEGACY_CANCEL', 'status' => 'canceled' ) );
check( 'A cancelled Stripe Legacy subscription (found by the customer ID) deactivates the member of a no-expiry level', 'inactive' === member_value( $ids['legacy_cancel_member_id'], 'account_state' ) );
check( "The Stripe Legacy cancellation updates the checkout record's subscription status", in_array( txn_meta( 'cus_E2E_LEGACY_CANCEL', 'subscr_status' ), array( 'canceled', 'cancelled' ), true ) );

//14. A full refund of a Stripe Legacy subscription's renewal payment.
send_stripe_event( 'invoice.payment_succeeded', renewal_invoice( 'sub_E2E_LEGACY_REFUND', 'cus_E2E_LEGACY_REFUND', 'ch_E2E_LEGACY_REFUND_R1' ) );
send_stripe_event(
	'charge.refunded',
	array( 'id' => 'ch_E2E_LEGACY_REFUND_R1', 'object' => 'charge', 'customer' => 'cus_E2E_LEGACY_REFUND', 'amount' => 1995, 'amount_refunded' => 1995, 'refunded' => true, 'currency' => 'usd' ),
	array( 'amount_refunded' => 0 )
);
check( 'A full refund of a Stripe Legacy renewal payment marks the payment record as Refunded', 'Refunded' === txn_meta( 'ch_E2E_LEGACY_REFUND_R1', 'status' ) );
check( 'A refund of a Stripe Legacy renewal payment (found by the customer ID) deactivates the member', 'inactive' === member_value( $ids['legacy_refund_member_id'], 'account_state' ) );

echo "--- Email activation ---\n";

//15. A wrong activation code must not show whether the member exists or is already active (member enumeration).
$wrong_code = 'wrongActivationCode0000000000000';
$pending_wrong_code = activation_response( $ids['activation_member_id'], $wrong_code );
check( 'A wrong activation code does not activate the account', 'activation_required' === member_value( $ids['activation_member_id'], 'account_state' ) );
check( 'A wrong activation code shows the invalid link message', false !== strpos( $pending_wrong_code['body'], 'This activation link is invalid or has expired' ) );
check_same_response( 'A wrong activation code for a member ID that does not exist gets the same response as for a member who needs activation', $pending_wrong_code, activation_response( 999999, $wrong_code ) );
check_same_response( 'A wrong activation code for an active member gets the same response as for a member who needs activation', $pending_wrong_code, activation_response( $ids['active_member_id'], $wrong_code ) );
check_same_response( 'An activation link without a code gets the same response as a wrong code', $pending_wrong_code, activation_response( $ids['active_member_id'], '' ) );

//16. The correct activation code, then the same link again.
activation_response( $ids['activation_member_id'], 'e2eActivationCode0123456789abcdef' );
check( 'The correct activation code activates the account', 'active' === member_value( $ids['activation_member_id'], 'account_state' ) );
check( 'The activation code is deleted once it is used', null === db_value( "SELECT option_value FROM {$prefix}options WHERE option_name = ?", 's', array( 'swpm_email_activation_data_usr_' . $ids['activation_member_id'] ) ) );
check_same_response( 'A used activation link gets the same response as a wrong code', $pending_wrong_code, activation_response( $ids['activation_member_id'], 'e2eActivationCode0123456789abcdef' ) );

//17. The resend activation email request shows the same message for every account (already protected, this keeps it that way).
$resend_missing = http_get( '/?swpm_resend_activation_email=1&swpm_member_id=999998' );
$resend_active  = http_get( '/?swpm_resend_activation_email=1&swpm_member_id=' . $ids['active_member_id'] );
check( 'The resend activation email request shows the general message', false !== strpos( $resend_missing['body'], 'If this account needs activation' ) );
check_same_response( 'The resend activation email request gets the same response for a missing member and an active member', $resend_missing, $resend_active );

echo "\n" . ( 0 === $failures ? 'All endpoint tests passed.' : "{$failures} endpoint test(s) failed." ) . "\n";
exit( 0 === $failures ? 0 : 1 );
