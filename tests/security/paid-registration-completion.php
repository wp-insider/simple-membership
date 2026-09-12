<?php
/** Standalone regression check: php tests/security/paid-registration-completion.php */
class SwpmUtils {
    public static $paid = true;
    public static $member;
    public static $users = array();
    public static function is_paid_registration() { return self::$paid; }
    public static function get_paid_member_info() { return self::$member; }
    public static function get_free_level() { return 2; }
    public static function get_user_ip_address() { return '127.0.0.1'; }
    public static function get_current_date_in_wp_zone() { return '2026-09-11'; }
    public static function _($text) { return $text; }
    public static function create_wp_user($data) { self::$users[] = $data; }
}
class SwpmForm {
    public static function get_membership_level_from_request() { return $_POST['swpm_membership_level']; }
}
class SwpmFrontForm {
    public function __construct($member) {}
    public function is_valid() { return true; }
    public function get_sanitized_member_form_data() {
        return array('user_name' => 'new_member', 'email' => 'new@example.org', 'plain_password' => 'secret', 'password' => 'hash');
    }
}
class SwpmTransfer {
    public static $default_fields = array();
    public static function get_instance() { return new self(); }
    public function set($key, $value) {}
}
class SwpmMemberUtils {
    public static function check_and_die_if_existing_wp_user_exists($data) {}
    public static function check_and_die_if_email_belongs_to_admin_user($email) {}
}
class SwpmSettings {
    public static function get_instance() { return new self(); }
    public function get_value($key, $default = '') { return $default; }
}
class SwpmMembershipLevelCustom {
    public static function get_instance_by_id($id) { return new self(); }
    public function get($key) { return 'active'; }
}
class SwpmLog { public static function log_simple_debug($text, $success) {} }
class CompletionDatabase {
    public $prefix = 'wp_';
    public $insert_id = 123;
    public $result = 1;
    public $updates = array();
    public $inserts = array();
    public function update($table, $data, $where) { $this->updates[] = array($data, $where); return $this->result; }
    public function insert($table, $data) { $this->inserts[] = $data; return 1; }
    public function prepare($sql, $id) { return $id; }
    public function get_var($id) { return (int) $id === 9 ? 'editor' : 'subscriber'; }
}
function do_action($hook, ...$args) {}
function apply_filters($hook, $value) { return $value; }
function sanitize_text_field($value) { return $value; }
function get_option($key) { return $key === 'swpm_private_key_one' ? 'test-key' : false; }
function wp_die($message) { throw new RuntimeException($message); }
function verify_completion($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
require dirname(__DIR__, 2) . '/simple-membership/classes/class.swpm-registration.php';
require dirname(__DIR__, 2) . '/simple-membership/classes/class.swpm-front-registration.php';
class CompletionRegistration extends SwpmFrontRegistration {
    //Stop after account creation so this test does not send mail or redirect.
    protected function send_reg_email() { return false; }
}
$pending = (object) array('member_id' => 42, 'reg_code' => 'valid-code', 'user_name' => '', 'membership_level' => 9);
$used = clone $pending;
$used->user_name = 'existing_member';
foreach (array(
    'invalid code or missing member' => array(true, null, 2, 1, false),
    'already completed' => array(true, $used, 9, 1, false),
    'wrong submitted level' => array(true, $pending, 2, 1, false),
    'concurrent completion or replay' => array(true, $pending, 9, 0, false),
    'database failure' => array(true, $pending, 9, false, false),
    'valid paid completion' => array(true, $pending, 9, 1, true),
    'free registration' => array(false, null, 2, 1, true),
) as $name => $case) {
    list(SwpmUtils::$paid, SwpmUtils::$member, $level, $result, $success) = $case;
    SwpmUtils::$users = array();
    $wpdb = new CompletionDatabase();
    $wpdb->result = $result;
    $_POST = array('swpm_membership_level' => $level, 'swpm_level_hash' => md5('test-key|' . $level));
    (new CompletionRegistration())->register_front_end();
    verify_completion(count(SwpmUtils::$users) === ($success ? 1 : 0), $name . ': unexpected WordPress account creation');
    if ($success && SwpmUtils::$paid) {
        verify_completion(SwpmUtils::$users[0]['role'] === 'editor', 'Valid paid account must retain its validated role.');
        list($data, $where) = $wpdb->updates[0];
        verify_completion($data['reg_code'] === '' && $data['membership_level'] === 9, 'Must consume code and preserve validated level.');
        verify_completion($where === (array) $pending, 'Update must guard the validated pending record.');
    }
    if ($success && !SwpmUtils::$paid) {
        verify_completion(SwpmUtils::$users[0]['role'] === 'subscriber' && count($wpdb->inserts) === 1, 'Free registration must retain its normal role and record.');
    }
    echo 'PASS: ' . $name . "\n";
}
