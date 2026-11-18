<?php
/**
 * PHPUnit bootstrap for the Simple Membership plugin tests.
 *
 * It needs the WordPress test library (installed by tests/bin/install-wp-tests.sh) and a test database.
 * The GitHub Actions "Tests" workflow sets this up. WP_TESTS_DIR is the folder of the WordPress test library.
 */

require __DIR__ . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/vendor/yoast/phpunit-polyfills' );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run tests/bin/install-wp-tests.sh first." . PHP_EOL;
	exit( 1 );
}

//Gives access to tests_add_filter().
require_once "{$_tests_dir}/includes/functions.php";

//Load the plugin, then run what the plugin's activation does (create the tables, save the default settings and pages, schedule the cron events).
tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__, 2 ) . '/simple-membership/simple-wp-membership.php';
		SimpleWpMembership::activate();
	}
);

//Start the WordPress test environment.
require "{$_tests_dir}/includes/bootstrap.php";
