<?php
/** Standalone regression check: php tests/security/plugin-check-database-queries.php */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
function apply_filters( $hook, $value ) { return $value; }
function do_action( $hook, ...$args ) {}
function check_query( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
class QueryRecorder {
    public $prefix = 'custom_';
    public $options = 'custom_options';
    public $calls = array();
    public $rows = array();
    public function prepare( $template, ...$args ) {
        if ( count( $args ) === 1 && is_array( $args[0] ) ) { $args = $args[0]; }
        check_query( substr_count( $template, '%s' ) + substr_count( $template, '%d' ) === count( $args ), 'Every query value has a placeholder.' );
        return array( 'template' => $template, 'args' => $args );
    }
    public function get_results( $query ) {
        $this->calls[] = $query;
        return array_shift( $this->rows ) ?? array();
    }
    public function get_row( $query ) { $this->calls[] = $query; return null; }
    public function get_var( $query ) { $this->calls[] = $query; return 0; }
    public function query( $query ) { $this->calls[] = $query; return 1; }
    public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
}
class SwpmUtils {
    public static function get_expiration_timestamp( $member ) { return 0; }
}
require dirname( __DIR__, 2 ) . '/simple-membership/classes/class.swpm-utils-member.php';
require dirname( __DIR__, 2 ) . '/simple-membership/classes/class.swpm-members-meta.php';
require dirname( __DIR__, 2 ) . '/simple-membership/classes/class.swpm-event-logger.php';
require dirname( __DIR__, 2 ) . '/simple-membership/classes/class.swpm-protection-base.php';
require dirname( __DIR__, 2 ) . '/simple-membership/classes/class.swpm-cronjob.php';
class QueryProtection extends SwpmProtectionBase {
    public function __construct( $id ) { $this->init( $id ); }
}
$wpdb = new QueryRecorder();
$attack = "x' OR 1=1 --";
foreach ( array( array( -1, 'all', array() ), array( 7, 'all', array( 7 ) ), array( -1, $attack, array( $attack ) ), array( 7, $attack, array( 7, $attack ) ) ) as $case ) {
    SwpmMemberUtils::get_all_members_of_a_level_and_a_state( $case[0], $case[1] );
    $call = end( $wpdb->calls );
    if ( $case[2] ) {
        check_query( $call['args'] === $case[2], 'Member filters remain bound values.' );
        check_query( strpos( $call['template'], $attack ) === false, 'Member filters cannot change SQL syntax.' );
    } else { check_query( is_string( $call ) && strpos( $call, 'WHERE' ) === false, 'The unfiltered query has no fabricated conditions.' ); }
}
echo "PASS: all member filter combinations and hostile state values\n";
foreach ( array( '', $attack ) as $key ) {
    check_query( SwpmMembersMeta::get( 7, $key ) === false, 'Missing metadata still returns false.' );
    $call = end( $wpdb->calls );
    check_query( $call['args'] === ( $key === '' ? array( 7 ) : array( 7, $key ) ), 'Optional metadata keys are bound.' );
}
echo "PASS: optional metadata key and missing-data behavior\n";
foreach ( array( 7, $attack ) as $id ) {
    new QueryProtection( $id );
    $call = end( $wpdb->calls );
    check_query( $call['args'] === array( $id ), 'Protection identifiers remain bound values.' );
    check_query( strpos( $call['template'], is_numeric( $id ) ? 'id = %d' : 'md5(id) = %s' ) !== false, 'Numeric and hashed identifiers retain their distinct lookup rules.' );
}
echo "PASS: numeric and hashed protection lookups\n";
SwpmEventLogger::prune_events_db_table( $attack, '2025-01-01' );
$call = end( $wpdb->calls );
check_query( $call['args'] === array( $attack, '2025-01-01' ), 'Event pruning binds both filters.' );
SwpmEventLogger::count_events();
check_query( is_string( end( $wpdb->calls ) ), 'Counting all events uses fixed SQL.' );
SwpmEventLogger::count_events( $attack );
check_query( end( $wpdb->calls )['args'] === array( $attack ), 'Counting one event type binds its value.' );
echo "PASS: event counting and pruning\n";
$wpdb->rows = array( array( (object) array( 'member_id' => '7) OR 1=1 --', 'account_state' => 'active' ), (object) array( 'member_id' => 8, 'account_state' => 'active' ) ), array() );
// Bypass constructor hook registration; exercise the real batch updater.
$cron = ( new ReflectionClass( 'SwpmCronJob' ) )->newInstanceWithoutConstructor();
$start = count( $wpdb->calls );
$cron->update_account_status();
$update = $wpdb->calls[ $start + 1 ];
check_query( $update['args'] === array( '7) OR 1=1 --', 8 ), 'Each expired member ID gets its own bound integer placeholder.' );
check_query( strpos( $update['template'], 'IN (%d,%d)' ) !== false, 'The batch update contains only generated placeholders.' );
echo "PASS: batched expiry update binds every member ID\n";
