<?php
/**
 * Standalone regression check: php tests/security/auto-login-after-registration.php
 * Exercises the real completion callback with WordPress/authentication test doubles.
 */
class SwpmSettings {
    public static $enabled = true;
    public static function get_instance() { return new self(); }
    public function get_value($key) {
        return $key === 'auto-login-after-rego' ? self::$enabled : 'https://example.org/login/';
    }
}
class SwpmLog {
    public static function log_simple_debug($message, $success) {}
}
class SwpmAuth {
    public static $calls = array();
    public static function get_instance() { return new self(); }
    public function login($username, $password) {
        self::$calls[] = array($username, $password, $_REQUEST);
        //Returning also exercises the fallback when authentication is denied.
        return false;
    }
}
class AutoLoginRedirect extends Exception {
    public $url;
    public function __construct($url) { $this->url = $url; }
}
function add_action($hook, $callback, $priority = 10) {
    $GLOBALS['test_hooks'][$hook] = $callback;
}
function add_filter($hook, $callback) {}
function apply_filters($hook, $value) {
    return $hook === 'swpm_after_reg_callback_login_page_url' ? $value . '?custom=1' : $value;
}
function wp_redirect($url) { throw new AutoLoginRedirect($url); }
function check_auto_login($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

require dirname(__DIR__, 2) . '/simple-membership/classes/class.swpm-self-action-handler.php';
new SwpmSelfActionHandler();

foreach (array('swpm_front_end_registration_complete_user_data', 'swpm_front_end_registration_complete_fb') as $hook) {
    foreach (array(true, false) as $enabled) {
        SwpmSettings::$enabled = $enabled;
        SwpmAuth::$calls = array();
        $_REQUEST = array();
        $password = 'Secret+&/=? password';
        $redirect = null;
        try {
            call_user_func($GLOBALS['test_hooks'][$hook], array(
                'user_name' => 'new_member',
                'plain_password' => $password,
            ));
        } catch (AutoLoginRedirect $result) {
            $redirect = $result->url;
        }
        if ($enabled) {
            check_auto_login(count(SwpmAuth::$calls) === 1, 'Must authenticate exactly once.');
            $call = SwpmAuth::$calls[0];
            check_auto_login($call[0] === 'new_member' && $call[1] === $password, 'Credentials must reach authentication intact.');
            check_auto_login($call[2]['swpm_user_name'] === 'new_member', 'WP login synchronization must recognize the login.');
            check_auto_login($call[2]['swpm_auto_login'] === '1', 'After-login redirection must recognize auto-login.');
            check_auto_login(!isset($call[2]['swpm_encoded_pw']), 'Must not create a URL password parameter.');
            check_auto_login($redirect === 'https://example.org/login/?custom=1', 'Fallback redirect must preserve the URL filter and contain no credentials.');
        } else {
            check_auto_login(SwpmAuth::$calls === array() && $redirect === null, 'Disabled auto-login must do nothing.');
        }
    }
}
echo "PASS: core and Form Builder callbacks authenticate server-side; redirects contain no credentials; disabled setting is respected.\n";
