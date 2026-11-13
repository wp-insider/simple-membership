<?php
/**
 * Standalone regression check: php tests/security/paid-registration-link-browser.php
 * The thank you page must only show the paid registration link in the browser that made the payment,
 * never using the visitor's IP address (it can be spoofed with the X-Forwarded-For header).
 * No WordPress database is needed.
 */
ob_start();//Buffer the output so setcookie() can still send its header.
define('WEEK_IN_SECONDS', 604800);
define('COOKIEPATH', '/');
define('COOKIE_DOMAIN', '');
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_unslash($value) { return stripslashes($value); }
function is_ssl() { return false; }

//A small in-memory database for the members table and the members meta table.
class BrowserLinkDatabase {
    public $prefix = 'wp_';
    public $members = array();
    public $meta = array();
    public function prepare($sql, ...$args) { return array('sql' => $sql, 'args' => $args); }
    public function get_row($query) {
        //MySQL compares the registration code case-insensitively.
        foreach (array_reverse($this->members) as $member) {
            if ($member->reg_code !== '' && strtolower($member->reg_code) === strtolower($query['args'][0])) {
                return $member;
            }
        }
        return null;
    }
    public function get_var($query) {
        foreach (array_reverse($this->meta) as $row) {
            if ($row['meta_key'] === $query['args'][0] && $row['meta_value'] === $query['args'][1]) {
                return $row['member_id'];
            }
        }
        return null;
    }
}
class SwpmMembersMeta {
    public static function get_table_name() { return 'wp_swpm_members_meta_tbl'; }
    public static function update($member_id, $meta_key, $meta_value) {
        global $wpdb;
        $wpdb->meta[] = array('member_id' => $member_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value);
    }
}
class SwpmMemberUtils {
    public static function get_user_by_id($member_id) {
        global $wpdb;
        foreach ($wpdb->members as $member) {
            if ((int) $member->member_id === (int) $member_id) {
                return $member;
            }
        }
        return null;
    }
}

require dirname(__DIR__, 2) . '/simple-membership/classes/class.swpm-utils.php';

function check_link($label, $condition) {
    if (!$condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS: ' . $label . "\n";
}
function found_member_id() {
    $member = SwpmUtils::get_incomplete_paid_member_info_by_ip();
    return $member ? (int) $member->member_id : 0;
}

$wpdb = new BrowserLinkDatabase();
$buyer_ip = '203.0.113.10';
$wpdb->members = array(
    (object) array('member_id' => 41, 'reg_code' => md5('older'), 'user_name' => '', 'last_accessed_from_ip' => $buyer_ip),
    (object) array('member_id' => 42, 'reg_code' => md5('pending'), 'user_name' => '', 'last_accessed_from_ip' => $buyer_ip),
    (object) array('member_id' => 43, 'reg_code' => '', 'user_name' => 'completed_member', 'last_accessed_from_ip' => $buyer_ip),
);

//The IP address is never used.
$_COOKIE = array();
$_SERVER['REMOTE_ADDR'] = $buyer_ip;
$_SERVER['HTTP_X_FORWARDED_FOR'] = $buyer_ip;
check_link('A browser without the payment cookie gets no link, even with the buyer IP (and a spoofed X-Forwarded-For)', found_member_id() === 0);

//The registration code cookie (Stripe, PayPal PPCP, Braintree).
$_COOKIE = array('swpm_paid_reg_code' => md5('pending'));
check_link('The browser with the registration code cookie gets the link of its member', found_member_id() === 42);
$_COOKIE = array('swpm_paid_reg_code' => strtoupper(md5('pending')));
check_link('A registration code with a different case is rejected', found_member_id() === 0);
$_COOKIE = array('swpm_paid_reg_code' => md5('wrong'));
check_link('A wrong registration code gets no link', found_member_id() === 0);
$_COOKIE = array('swpm_paid_reg_code' => '');
check_link('An empty registration code gets no link (a completed registration has an empty code)', found_member_id() === 0);

//Saving the cookie after the payment.
$_COOKIE = array();
SwpmUtils::set_paid_registration_link_browser_only(41, false);
check_link('A request that may not come from the buyer browser saves no cookie', found_member_id() === 0);
SwpmUtils::set_paid_registration_link_browser_only(41, true);
check_link('The buyer browser gets the link of its member after the payment', found_member_id() === 41);
$_COOKIE = array();
SwpmUtils::set_paid_registration_link_browser_only(43, true);
check_link('No cookie is saved for a member that completed the registration', empty($_COOKIE['swpm_paid_reg_code']));

//The PayPal Standard checkout tokens.
$token_a = str_repeat('a1', 12);
$token_b = str_repeat('b2', 12);
$token_c = str_repeat('c3', 12);
SwpmUtils::save_paid_registration_browser_token(41, $token_a);
SwpmUtils::save_paid_registration_browser_token(42, $token_b);
SwpmUtils::save_paid_registration_browser_token(43, $token_c);
SwpmUtils::save_paid_registration_browser_token(42, 'not-a-token');
check_link('Only valid tokens of members that need to complete the registration are saved', count($wpdb->meta) === 2);
check_link('The token hash is saved, not the token', $wpdb->meta[0]['meta_value'] === hash('sha256', $token_a));

$_COOKIE = array('swpm_pp_checkout_tokens' => $token_b);
check_link('The browser with the PayPal Standard token gets the link of its member', found_member_id() === 42);
$_COOKIE = array('swpm_pp_checkout_tokens' => $token_a . '.' . $token_b);
check_link('With several tokens, the newest checkout is used', found_member_id() === 42);
$_COOKIE = array('swpm_pp_checkout_tokens' => str_repeat('d4', 12));
check_link('An unknown token gets no link', found_member_id() === 0);
$_COOKIE = array('swpm_pp_checkout_tokens' => hash('sha256', $token_b));
check_link('A cookie made from the saved hash gets no link', found_member_id() === 0);
$_COOKIE = array('swpm_pp_checkout_tokens' => 'bad.<script>.' . $token_a);
check_link('Invalid cookie values are ignored', found_member_id() === 41);

//The check for one member.
$_COOKIE = array('swpm_paid_reg_code' => md5('pending'));
check_link('The link of the member of this browser is allowed', SwpmUtils::is_paid_registration_link_allowed_in_this_browser($wpdb->members[1]) === true);
check_link('The link of another member is not allowed', SwpmUtils::is_paid_registration_link_allowed_in_this_browser($wpdb->members[0]) === false);
