<?php
/**
 * php tests/security/email-activation.php
 * Runs the real registration, activation and both resend implementations with
 * in-memory WordPress/database doubles. No email or network requests are sent.
 */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

class ActivationResponse extends RuntimeException {}
function wp_die( $message = '', $title = '' ) { throw new ActivationResponse( $message ); }
function __( $text, $domain = '' ) { return $text; }
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_url( $value ) { return $value; }
function is_email( $value ) { return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function get_home_url() { return 'https://example.test'; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function maybe_serialize( $value ) { return is_array( $value ) ? serialize( $value ) : $value; }
function maybe_unserialize( $value ) { return strpos( $value, 'a:' ) === 0 ? unserialize( $value ) : $value; }
function wp_cache_delete( $key, $group ) {}
function wp_generate_password( $length, $special, $extra ) { return substr( bin2hex( random_bytes( $length ) ), 0, $length ); }
function get_option( $key, $default = false ) { return isset( $GLOBALS['options'][$key] ) ? maybe_unserialize( $GLOBALS['options'][$key]['option_value'] ) : $default; }
function update_option( $key, $value, $autoload = null ) {
    if ( ! empty( $GLOBALS['write_failure'] ) ) { return false; }
    if ( isset( $GLOBALS['options'][$key] ) && get_option( $key ) === $value ) { return false; }
    $GLOBALS['options'][$key] = array( 'option_name' => $key, 'option_id' => $GLOBALS['options'][$key]['option_id'] ?? ++$GLOBALS['option_id'], 'option_value' => maybe_serialize( $value ) );
    return true;
}
function get_transient( $key ) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][$key] = $value; }
function wp_mail( $to, $subject, $body, $headers ) { $GLOBALS['mail'][] = compact( 'to', 'subject', 'body', 'headers' ); return true; }
function apply_filters( $hook, $value, ...$args ) {
    return isset( $GLOBALS['filters'][$hook] ) ? $GLOBALS['filters'][$hook]( $value, ...$args ) : $value;
}
class ActivationDatabase {
    public $options = 'wp_options';
    public function query( $args ) {
        if ( ! empty( $GLOBALS['write_failure'] ) ) { return false; }
        if ( isset( $GLOBALS['options'][$args[0]] ) ) { return 0; }
        update_option( $args[0], maybe_unserialize( $args[1] ), false );
        return 1;
    }
    public function delete( $table, $where, $formats ) {
        $key = $where['option_name'];
        if ( ! isset( $GLOBALS['options'][$key] ) || $GLOBALS['options'][$key]['option_value'] !== $where['option_value'] ) { return 0; }
        unset( $GLOBALS['options'][$key] );
        return 1;
    }
    public function esc_like( $text ) { return $text; }
    public function prepare( $sql, ...$args ) { return $args; }
    public function get_results( $args ) {
        $rows = array_filter( $GLOBALS['options'], function ( $row ) use ( $args ) {
            return strpos( $row['option_name'], 'swpm_email_activation_data_usr_' ) === 0 && $row['option_id'] > $args[1];
        } );
        usort( $rows, function ( $a, $b ) { return $a['option_id'] <=> $b['option_id']; } );
        return array_map( function ( $row ) { return (object) $row; }, array_slice( $rows, 0, 100 ) );
    }
    public function update( $table, $data, $where, $formats, $where_formats ) {
        if ( ! empty( $GLOBALS['write_failure'] ) ) { return false; }
        foreach ( $GLOBALS['options'] as &$row ) {
            $matches = isset( $where['option_id'] ) ? $row['option_id'] == $where['option_id'] : $row['option_name'] === $where['option_name'];
            if ( $matches && $row['option_value'] === $where['option_value'] ) {
                $row['option_value'] = $data['option_value'];
                return 1;
            }
        }
        return 0;
    }
}
class SwpmSettings {
    public static $values;
    public static function get_instance() { return new self(); }
    public function get_value( $key, $default = '' ) { return self::$values[$key] ?? $default; }
}
class SwpmLog { public static function log_simple_debug( ...$args ) {} }
class SwpmUtils {
    public static function _( $text ) { return $text; }
    public static function get_formatted_date_according_to_wp_settings( $date ) { return $date; }
    public static function get_membership_level_row_by_id( $id ) { return (object) array( 'id' => $id ); }
}
class SwpmMemberUtils {
    public static function get_user_by_id( $id ) { return isset( $GLOBALS['members'][$id] ) ? clone $GLOBALS['members'][$id] : false; }
    public static function get_user_by_user_name( $name ) {
        foreach ( $GLOBALS['members'] as $member ) { if ( $member->user_name === $name ) { return clone $member; } }
        return false;
    }
    public static function get_formatted_expiry_date_by_user_id( $id ) { return ''; }
    public static function update_account_state( $id, $state ) { $GLOBALS['members'][$id]->account_state = $state; }
}
class SwpmMembershipLevelUtils { public static function get_membership_level_name_of_a_member( $id ) { return 'Gold'; } }
class SwpmPermission {
    public static function get_instance( $id ) { return new self(); }
    public function get( $key ) { return 'Gold'; }
}
class SwpmMembershipLevelCustom {
    public static $redirect = 'https://example.test/level-welcome';
    public static function get_instance_by_id( $id ) { return new self(); }
    public function get( $key ) { return self::$redirect; }
}
class SwpmTransfer {
    public static $values = array();
    public static function get_instance() { return new self(); }
    public function set( $key, $value ) { self::$values[$key] = $value; }
}
$root = dirname( __DIR__, 2 ) . '/simple-membership/classes/';
require $root . 'class.swpm-email-activation.php';
require $root . 'class.swpm-registration.php';
require $root . 'class.swpm-front-registration.php';
require $root . 'class.swpm-utils-misc.php';
class ActivationRegistration extends SwpmFrontRegistration {
    public function send( $activation = true ) {
        $this->member_info = (array) SwpmMemberUtils::get_user_by_id( 7 );
        $this->member_info['plain_password'] = 'OriginalSecret!';
        $this->email_activation = $activation;
        return $this->send_reg_email();
    }
    public function password() { return $this->member_info['plain_password']; }
}
function check_activation( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function response( $callback ) {
    ob_start();
    try { $callback(); } catch ( ActivationResponse $response ) { return ob_get_clean() . $response->getMessage(); }
    ob_end_clean();
    throw new RuntimeException( 'Expected an endpoint response.' );
}
function fixture() {
    $GLOBALS['options'] = $GLOBALS['transients'] = $GLOBALS['mail'] = $GLOBALS['filters'] = array();
    $GLOBALS['option_id'] = 0;
    $GLOBALS['write_failure'] = false;
    SwpmMembershipLevelCustom::$redirect = 'https://example.test/level-welcome';
    $GLOBALS['wpdb'] = new ActivationDatabase();
    $_GET = array( 'swpm_member_id' => '7' );
    $_POST = array( 'email' => 'attacker@example.test' );
    $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
    $GLOBALS['members'] = array( 7 => (object) array(
        'member_id' => 7, 'user_name' => 'victim', 'email' => 'member@example.test', 'account_state' => 'activation_required',
        'membership_level' => 2, 'password' => 'HASH-MUST-NOT-BE-EMAILED', 'first_name' => 'Member', 'last_name' => '',
        'phone' => '', 'member_since' => '2026-09-01', 'subscription_starts' => '2026-09-01', 'company_name' => '',
    ) );
    SwpmSettings::$values = array(
        'reg-complete-mail-subject' => 'Complete', 'reg-complete-mail-body' => '{user_name} {email} {membership_level_name} {password} {plain_password} {login_link}',
        'email-activation-mail-subject' => 'Activate', 'email-activation-mail-body' => '{activation_link} {password} {plain_password}',
        'email-from' => 'site@example.test', 'login-page-url' => 'https://example.test/login',
        'enable-admin-notification-after-reg' => 1, 'admin-notification-email' => 'admin@example.test',
        'reg-complete-mail-body-admin' => '{user_name} {email} {password}',
    );
}
$key = 'swpm_email_activation_data_usr_7';

fixture();
$registration = new ActivationRegistration();
check_activation( $registration->send(), 'Initial activation email failed.' );
$data = get_option( $key );
check_activation( strlen( $data['act_code'] ) === 32 && ! isset( $data['plain_password'] ), 'Token/password storage.' );
check_activation( count( $GLOBALS['mail'] ) === 1 && $GLOBALS['mail'][0]['to'] === 'member@example.test', 'Activation recipient or premature admin notification.' );
check_activation( $registration->password() === 'OriginalSecret!', 'Must preserve in-memory password for account creation/auto-login.' );
$registration->send( false );
foreach ( $GLOBALS['mail'] as $message ) {
    check_activation( strpos( $message['body'], 'OriginalSecret!' ) === false && strpos( $message['body'], 'HASH-MUST-NOT-BE-EMAILED' ) === false, 'Credential emailed.' );
    check_activation( strpos( $message['body'], '{password}' ) === false && strpos( $message['body'], 'password reset' ) !== false, 'Legacy template guidance.' );
}
check_activation( $GLOBALS['mail'][2]['to'] === 'admin@example.test', 'Admin completion notification lost.' );
echo "PASS: saved recipients, member/admin templates, activation notification timing and in-memory password\n";

fixture();
$legacy = array( 'timestamp' => time() - 60, 'act_code' => md5( 'old-link' ), 'plain_password' => 'legacy-secret', 'fb_form_id' => 123 );
update_option( $key, $legacy );
$result = response( function () { ( new SwpmFrontRegistration() )->resend_activation_email(); } );
$stored = get_option( $key );
check_activation( $stored['act_code'] === $legacy['act_code'] && $stored['timestamp'] === $legacy['timestamp'] && $stored['fb_form_id'] === 123, 'Resend must preserve link lifetime and FB metadata.' );
check_activation( ! isset( $stored['plain_password'] ) && $GLOBALS['mail'][0]['to'] === 'member@example.test', 'Public resend leaked data.' );
$throttled = response( function () { ( new SwpmFrontRegistration() )->resend_activation_email(); } );
check_activation( $result === $throttled && count( $GLOBALS['mail'] ) === 1, 'Member cooldown or generic response.' );
$_GET['swpm_member_id'] = '999';
check_activation( response( function () { ( new SwpmFrontRegistration() )->resend_activation_email(); } ) === $result, 'Missing account enumeration.' );
$_GET['swpm_member_id'] = array( '7' );
check_activation( response( function () { ( new SwpmFrontRegistration() )->resend_activation_email(); } ) === $result, 'Malformed member ID.' );
SwpmMiscUtils::resend_activation_email_by_member_id( 7 );
check_activation( count( $GLOBALS['mail'] ) === 2 && $GLOBALS['mail'][1]['to'] === 'member@example.test' && get_option( $key ) === $stored, 'Admin resend must bypass public cooldown and preserve metadata.' );
echo "PASS: public/admin resend, existing links, Form Builder metadata, cooldown and generic responses\n";

fixture();
for ( $id = 1; $id <= 10; $id++ ) { check_activation( SwpmEmailActivation::allow_public_resend( $id ), 'Source limit applied too early.' ); }
$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.200';
check_activation( ! SwpmEmailActivation::allow_public_resend( 11 ), 'Source limit bypassed.' );
$_SERVER['REMOTE_ADDR'] = '192.0.2.2';
check_activation( SwpmEmailActivation::allow_public_resend( 11 ), 'Independent source blocked.' );
check_activation( ! SwpmEmailActivation::allow_public_resend( 1 ), 'Member cooldown must apply across sources.' );
echo "PASS: per-source limit ignores spoofed forwarding headers; member limit spans sources\n";

fixture();
update_option( $key, $legacy );
$_GET['swpm_token'] = $legacy['act_code'];
$GLOBALS['filters']['swpm_activation_feature_override_account_status'] = function () { return 'pending'; };
$GLOBALS['filters']['swpm_after_email_activation_redirect_url'] = function ( $url ) { return $url . '?custom=1'; };
$result = response( function () { ( new SwpmFrontRegistration() )->handle_email_activation(); } );
check_activation( $GLOBALS['members'][7]->account_state === 'pending' && get_option( $key ) === false, 'Activation state override or consumption failed.' );
check_activation( SWPM_EMAIL_ACTIVATION_FORM_ID === 123 && strpos( $result, 'level-welcome?custom=1' ) !== false, 'Form Builder context or level redirect hook lost.' );
check_activation( $GLOBALS['mail'][0]['to'] === 'member@example.test' && strpos( $GLOBALS['mail'][0]['body'], 'legacy-secret' ) === false, 'Activation completion leak.' );
$GLOBALS['members'][7]->account_state = 'activation_required';
response( function () { ( new SwpmFrontRegistration() )->handle_email_activation(); } );
check_activation( count( $GLOBALS['mail'] ) === 2 && $GLOBALS['members'][7]->account_state === 'activation_required', 'Replay activated or sent mail.' );
echo "PASS: legacy-token activation, single use, status filter, level redirect and Form Builder context\n";

foreach ( array( 'wrong', 'expired', 'future', 'missing', 'array' ) as $case ) {
    fixture();
    $record = array( 'timestamp' => time(), 'act_code' => 'correct' );
    if ( $case === 'expired' ) { $record['timestamp'] = time() - DAY_IN_SECONDS; }
    if ( $case === 'future' ) { $record['timestamp'] = time() + 3600; }
    if ( $case !== 'missing' ) { update_option( $key, $record ); }
    $_GET['swpm_token'] = $case === 'wrong' ? 'wrong' : ( $case === 'array' ? array( 'correct' ) : 'correct' );
    response( function () { ( new SwpmFrontRegistration() )->handle_email_activation(); } );
    check_activation( $GLOBALS['members'][7]->account_state === 'activation_required' && empty( $GLOBALS['mail'] ), $case . ' token accepted.' );
}
echo "PASS: invalid, expired, future-dated, missing and malformed tokens rejected\n";

fixture();
$expired = $legacy;
$expired['timestamp'] = time() - DAY_IN_SECONDS;
update_option( $key, $expired );
$GLOBALS['filters']['swpm_email_activation_data'] = function ( $data ) { $data['custom'] = 'preserved'; $data['plain_password'] = 'filter-secret'; return $data; };
$fresh = SwpmEmailActivation::get_or_create( 7 );
check_activation( $fresh['act_code'] !== $legacy['act_code'] && $fresh['fb_form_id'] === 123 && $fresh['custom'] === 'preserved' && ! isset( $fresh['plain_password'] ), 'Expired token refresh or filter compatibility.' );
check_activation( ! SwpmEmailActivation::consume( 7, $expired ) && get_option( $key ) === $fresh, 'Stale request consumed replacement token.' );
check_activation( SwpmEmailActivation::consume( 7, $fresh ) && ! SwpmEmailActivation::consume( 7, $fresh ), 'Concurrent consumption must have one winner.' );
$GLOBALS['write_failure'] = true;
check_activation( ( new ActivationRegistration() )->send() === false && empty( $GLOBALS['mail'] ), 'Must not email an unsaved token.' );
echo "PASS: expired-token renewal, addon filter, stale/concurrent consumption and save failure\n";

fixture();
for ( $id = 1; $id <= 105; $id++ ) { update_option( 'swpm_email_activation_data_usr_' . $id, $legacy ); }
SwpmEmailActivation::remove_legacy_passwords();
check_activation( get_option( 'swpm_activation_password_cleanup' ) !== 'done' && isset( get_option( 'swpm_email_activation_data_usr_105' )['plain_password'] ), 'Migration must be bounded.' );
SwpmEmailActivation::remove_legacy_passwords();
check_activation( get_option( 'swpm_activation_password_cleanup' ) === 'done', 'Migration did not complete.' );
$expected = $legacy;
unset( $expected['plain_password'] );
for ( $id = 1; $id <= 105; $id++ ) { check_activation( get_option( 'swpm_email_activation_data_usr_' . $id ) === $expected, 'Migration damaged activation data.' ); }
echo "PASS: bounded legacy password migration preserves every token, timestamp and addon field\n";

fixture();
$GLOBALS['members'][7]->email = 'invalid';
check_activation( ( new ActivationRegistration() )->send() === false && empty( $GLOBALS['mail'] ), 'Invalid saved email must not use POST fallback.' );
SwpmMiscUtils::resend_activation_email_by_member_id( 7 );
check_activation( empty( $GLOBALS['mail'] ), 'Admin resend used invalid email.' );
echo "PASS: invalid saved address fails safely\n";

fixture();
SwpmSettings::$values['email-enable-html'] = 1;
$GLOBALS['filters']['swpm_send_reg_email_activation_link'] = function ( $link ) { return $link . '&custom=1'; };
$GLOBALS['filters']['swpm_email_registration_complete_subject'] = function ( $subject ) { return 'Custom ' . $subject; };
( new ActivationRegistration() )->send();
check_activation( strpos( $GLOBALS['mail'][0]['headers'], 'text/html' ) !== false && $GLOBALS['mail'][0]['subject'] === 'Custom Activate' && strpos( $GLOBALS['mail'][0]['body'], '&custom=1' ) !== false, 'HTML, subject or activation-link filter broken.' );
$GLOBALS['mail'] = array();
$GLOBALS['filters']['swpm_email_registration_complete_body'] = function () { return ''; };
( new ActivationRegistration() )->send( false );
check_activation( count( $GLOBALS['mail'] ) === 1 && $GLOBALS['mail'][0]['to'] === 'admin@example.test', 'Suppressing member email must retain admin notification.' );
echo "PASS: HTML email, subject/link customization and member-email suppression\n";

fixture();
response( function () { ( new SwpmFrontRegistration() )->resend_activation_email(); } );
check_activation( SwpmEmailActivation::is_valid( get_option( $key ) ) && count( $GLOBALS['mail'] ) === 1, 'Resend must recover missing activation data.' );
$GLOBALS['members'][7]->account_state = 'active';
$GLOBALS['transients'] = array();
response( function () { ( new SwpmFrontRegistration() )->resend_activation_email(); } );
check_activation( count( $GLOBALS['mail'] ) === 1 && $GLOBALS['members'][7]->account_state === 'active', 'Resend must leave an active member alone.' );
echo "PASS: missing-data recovery and active-member protection\n";

foreach ( array( '', 'https://example.test/global-welcome' ) as $redirect ) {
    fixture();
    SwpmMembershipLevelCustom::$redirect = '';
    SwpmSettings::$values['after-rego-redirect-page-url'] = $redirect;
    $token = SwpmEmailActivation::get_or_create( 7 );
    $_GET['swpm_token'] = $token['act_code'];
    $result = response( function () { ( new SwpmFrontRegistration() )->handle_email_activation(); } );
    check_activation( strpos( $result, $redirect ?: 'https://example.test/login' ) !== false && $GLOBALS['members'][7]->account_state === 'active', 'Default activation state or redirect fallback broken.' );
}
echo "PASS: new-token activation and global/login redirect fallbacks\n";
