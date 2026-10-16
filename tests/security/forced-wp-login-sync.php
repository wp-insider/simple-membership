<?php
/**
 * Standalone regression check: php tests/security/forced-wp-login-sync.php
 * Exercises the real logout-sync method with authentication test doubles.
 */
class SwpmSettings {
    public static $enabled;
    public static function get_instance() { return new self(); }
    public function get_value($key) { return self::$enabled; }
}
class SwpmLog {
    public static function log_auth_debug($message, $success) {}
}
class SwpmMemberUtils {
    public static $logged_in;
    public static $login_on_check;
    public static function is_member_logged_in() {
        //Model lazy auth initialization processing a successful login submission.
        if (self::$login_on_check) {
            $GLOBALS['wp_logged_in'] = true;
            self::$logged_in = true;
        }
        return self::$logged_in;
    }
}
class SwpmAuth {
    public static $logouts;
    public static function get_instance() { return new self(); }
    public function logout_silent_and_redirect() { self::$logouts++; }
}
function is_user_logged_in() { return $GLOBALS['wp_logged_in']; }

require dirname(__DIR__, 2) . '/simple-membership/classes/class.swpm-wp-loaded-tasks.php';

$tasks = new SwpmWpLoadedTasks();
$cases = array(
    //Label, initial WP session, initial member session, login during check, force sync, expected logouts.
    array('Fresh AJAX login succeeds', false, false, true, true, 0),
    array('Expired WP session logs member out', false, true, false, true, 1),
    array('Disabled synchronization preserves member session', false, true, false, false, 0),
    array('Both sessions already logged in', true, true, false, true, 0),
    array('Anonymous or failed login stays logged out', false, false, false, true, 0),
    array('Fresh login with synchronization disabled', false, false, true, false, 0),
);
foreach ($cases as $case) {
    list($label, $GLOBALS['wp_logged_in'], SwpmMemberUtils::$logged_in,
        SwpmMemberUtils::$login_on_check, SwpmSettings::$enabled, $expected) = $case;
    SwpmAuth::$logouts = 0;
    $tasks->synchronise_swpm_logout_for_wp_users();
    if (SwpmAuth::$logouts !== $expected) {
        throw new RuntimeException($label . ': unexpected forced logout count.');
    }
    echo 'PASS: ' . $label . "\n";
}
