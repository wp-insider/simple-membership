<?php
/** Run: php tests/security/recurring-payment-email.php. No network or mail is used. */
define('OBJECT', 'OBJECT');
class SwpmSettings {
    public static $values = array();
    public static function get_instance() { return new self(); }
    public function get_value($key, $default = '') { return self::$values[$key] ?? $default; }
}
class SwpmMemberUtils {
    public static $member;
    public static function get_user_by_id($id) { return self::$member; }
    public static function get_formatted_expiry_date_by_user_id($id) { return '2027-03-31'; }
}
class SwpmMembershipLevelUtils {
    public static function get_membership_level_name_of_a_member($id) { return 'Premium'; }
}
class SwpmUtils {
    public static function get_current_date_in_wp_zone() { return '2026-09-12'; }
    public static function get_formatted_date_according_to_wp_settings($date) { return $date; }
}
class SwpmTransactions {
    public static $duplicate = false;
    public static $previous;
    public static function get_transaction_row_by_txn_id_and_subscription_id($txn, $sub) { return self::$duplicate; }
    public static function get_transaction_row_by_subscr_id($id, $meta) { return self::$previous; }
}
class SwpmLog { public static function log_simple_debug(...$args) {} }
class RenewalDatabase {
    public $prefix = 'wp_';
    public $result = 1;
    public $updates = 0;
    public function prepare($sql, ...$args) { return $sql; }
    public function get_row($sql, $format) { return SwpmMemberUtils::$member; }
    public function query($sql) { $this->updates++; return $this->result; }
}
function apply_filters($hook, $value, ...$args) {
    if ($hook === 'swpm_send_recurring_payment_email' && !empty($GLOBALS['suppress'])) { return false; }
    if ($hook === 'swpm_recurring_payment_email_args' && !empty($GLOBALS['customize'])) { $value['subject'] = 'Custom receipt'; }
    return $value;
}
function do_action($hook, ...$args) { $GLOBALS['actions'][] = $hook; }
function __($text, $domain = '') { return $text; }
function wp_mail($to, $subject, $body, $headers) {
    $GLOBALS['mail'][] = compact('to', 'subject', 'body', 'headers');
    return true;
}
function check_renewal($ok, $label) { if (!$ok) { throw new RuntimeException($label); } }
require dirname(__DIR__, 2) . '/simple-membership/classes/class.swpm-utils-misc.php';
require dirname(__DIR__, 2) . '/simple-membership/ipn/swpm_handle_subsc_ipn.php';

foreach (array('disabled', 'member', 'admin', 'both-html', 'duplicate', 'paypal-first', 'paypal-created', 'paypal-renewal', 'ppcp-first', 'ppcp-renewal', 'missing-member', 'missing-txn', 'db-error', 'zero-update', 'suppressed', 'customized', 'manual') as $case) {
    SwpmSettings::$values = array('subscription-renewal-member-mail-enable' => true,
        'subscription-renewal-admin-mail-address' => 'admin@example.invalid', 'email-from' => 'site@example.invalid');
    SwpmMemberUtils::$member = (object) array('member_id' => 7, 'membership_level' => 3, 'subscr_id' => 'sub-1',
        'email' => 'member@example.invalid', 'user_name' => 'member', 'first_name' => 'Test', 'last_name' => 'Member',
        'account_state' => 'active', 'phone' => '', 'member_since' => '2026-01-01', 'subscription_starts' => '2026-09-12', 'company_name' => '');
    SwpmTransactions::$duplicate = $case === 'duplicate';
    SwpmTransactions::$previous = null;
    $wpdb = new RenewalDatabase();
    $mail = $actions = array();
    $suppress = $case === 'suppressed';
    $customize = $case === 'customized';
    $ipn = array('subscr_id' => 'sub-1', 'txn_id' => 'txn-1', 'txn_type' => 'recurring_payment', 'mc_gross' => 7000, 'mc_currency' => 'jpy');
    $expected = 1;
    if ($case === 'disabled') { SwpmSettings::$values = array(); $expected = 0; }
    if ($case === 'admin') { SwpmSettings::$values['subscription-renewal-member-mail-enable'] = false; SwpmSettings::$values['subscription-renewal-admin-mail-enable'] = true; }
    if ($case === 'both-html') { SwpmSettings::$values['subscription-renewal-admin-mail-enable'] = true; SwpmSettings::$values['email-enable-html'] = true; $expected = 2; }
    if (strpos($case, 'paypal-') === 0 || strpos($case, 'ppcp-') === 0) {
        $ipn['txn_type'] = strpos($case, 'ppcp-') === 0 ? 'pp_subscription_sale_completed_webhook' : 'subscr_payment';
        $expected = 0;
        if (strpos($case, 'renewal') !== false) { SwpmTransactions::$previous = (object) array('status' => 'Completed'); $expected = 1; }
        if ($case === 'paypal-created') { SwpmTransactions::$previous = (object) array('status' => 'subscription created'); }
    }
    if ($case === 'missing-member') { SwpmMemberUtils::$member = null; $expected = 0; }
    if ($case === 'missing-txn') { unset($ipn['txn_id']); $expected = 0; }
    if ($case === 'db-error') { $wpdb->result = false; $expected = 0; }
    if ($case === 'zero-update') { $wpdb->result = 0; }
    if ($case === 'manual') { $ipn['txn_type'] = 'web_accept'; $expected = 0; }
    if ($suppress || $case === 'duplicate') { $expected = 0; }
    swpm_update_member_subscription_start_date_if_applicable($ipn);
    check_renewal(count($mail) === $expected, "$case: mail count");
    check_renewal(count($actions) === ($case === 'missing-member' ? 0 : 1), "$case: existing action preserved");
    foreach ($mail as $message) {
        if ($message['to'] === 'member@example.invalid') {
            check_renewal(strpos($message['body'], 'Premium') !== false, "$case: membership level tag");
            check_renewal(strpos($message['body'], '2026-09-12') !== false, "$case: subscription start tag");
            check_renewal(strpos($message['body'], '2027-03-31') !== false, "$case: expiry date tag");
        } else {
            check_renewal(strpos($message['body'], 'Member ID: 7') !== false, "$case: admin member ID tag");
        }
        check_renewal(strpos($message['body'], '{') === false, "$case: unreplaced tags");
        if ($case === 'both-html') { check_renewal(count(array_keys($message['headers'], 'Content-Type: text/html; charset=UTF-8')) === 1, 'one content-type header'); }
        if ($customize) { check_renewal($message['subject'] === 'Custom receipt', 'custom message filter'); }
    }
    echo "PASS: $case\n";
}
$template = '{member_id}|{first_name}|{membership_level_name}|{subscription_starts}|{expiry_date}';
SwpmSettings::$values['subscription-renewal-member-mail-body'] = $template;
$expected_body = SwpmMiscUtils::replace_dynamic_tags($template, 7, array());
$mail = array();
swpm_send_subscription_renewal_notification_email(7, array('subscr_id' => 'sub-1', 'txn_id' => 'txn-2', 'txn_type' => 'recurring_payment'));
check_renewal(count($mail) === 1 && $mail[0]['body'] === $expected_body, 'same merge-tag context as account renewal');
check_renewal($expected_body === '7|Test|Premium|2026-09-12|2027-03-31', 'standard renewal tags');
echo "PASS: same merge tags as Account Renewal Notification\n";

$payment_template = '{payment_amount}|{payment_currency}|{subscription_id}|{transaction_id}|{membership_level}|{membership_level_name}|{next_billing_date}';
SwpmSettings::$values['subscription-renewal-member-mail-body'] = $payment_template;
SwpmSettings::$values['subscription-renewal-member-mail-subject'] = '{subscription_id}: {payment_amount} {payment_currency}';
foreach (array(
    'zero-decimal' => array(array('mc_gross' => 7000, 'mc_currency' => 'jpy'), '7000|JPY|sub-1|txn-3|3|Premium|', 'sub-1: 7000 JPY'),
    'paypal-event' => array(array('mc_gross' => '99.00', 'payment_amount' => '12.50', 'mc_currency' => 'usd', 'next_billing_date' => '2026-10-12T00:00:00Z'), '12.50|USD|sub-1|txn-3|3|Premium|2026-10-12T00:00:00Z', 'sub-1: 12.50 USD'),
    'missing-optional-data' => array(array(), '||sub-1|txn-3|3|Premium|', 'sub-1:  '),
) as $name => $case) {
    $mail = array();
    $ipn = array_merge(array('subscr_id' => 'sub-1', 'txn_id' => 'txn-3', 'txn_type' => 'recurring_payment'), $case[0]);
    swpm_send_subscription_renewal_notification_email(7, $ipn);
    check_renewal(count($mail) === 1 && $mail[0]['body'] === $case[1] && $mail[0]['subject'] === $case[2], $name . ': payment tags');
    echo "PASS: $name payment tags\n";
}
$unrelated = SwpmMiscUtils::replace_dynamic_tags('{payment_amount}|{next_billing_date}', 7, array());
check_renewal($unrelated === '{payment_amount}|{next_billing_date}', 'unrelated emails preserve custom tags without payment context');
echo "PASS: unrelated email tags unchanged\n";
