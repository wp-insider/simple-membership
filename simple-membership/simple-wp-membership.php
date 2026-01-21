<?php
/*
Plugin Name: Simple Membership
Version: 4.7.0
Plugin URI: https://simple-membership-plugin.com/
Author: smp7, wp.insider
Author URI: https://simple-membership-plugin.com/
Description: A flexible, well-supported, and easy-to-use WordPress membership plugin for offering free and premium content from your WordPress site.
Text Domain: simple-membership
Domain Path: /languages/
Requires PHP: 7.4
*/

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Do not access this file directly.' );
}

//Define plugin constants
define( 'SIMPLE_WP_MEMBERSHIP_VER', '4.7.0' );
define( 'SIMPLE_WP_MEMBERSHIP_DB_VER', '1.5' );
define( 'SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL', home_url() );
define( 'SIMPLE_WP_MEMBERSHIP_PATH', dirname( __FILE__ ) . '/' );
define( 'SIMPLE_WP_MEMBERSHIP_URL', plugins_url( '', __FILE__ ) );
define( 'SIMPLE_WP_MEMBERSHIP_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
define( 'SIMPLE_WP_MEMBERSHIP_TEMPLATE_PATH', 'simple-membership' );
if ( ! defined( 'COOKIEHASH' ) ) {
	define( 'COOKIEHASH', md5( get_site_option( 'siteurl' ) ) );
}
define( 'SIMPLE_WP_MEMBERSHIP_AUTH', 'simple_wp_membership_' . COOKIEHASH );
define( 'SIMPLE_WP_MEMBERSHIP_SEC_AUTH', 'simple_wp_membership_sec_' . COOKIEHASH );
define( 'SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS', serialize( array( 'JPY', 'MGA', 'VND', 'KRW' ) ) );

//Include the main class file.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.simple-wp-membership.php' );
//Include the compatibility file (for backward compatibility). It needs to be included after the main class file has included the necessary files.
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'swpm-compat.php' );//It will be removed in the future.

//Perform some initial setup tasks.
SwpmUtils::do_misc_initial_plugin_setup_tasks();

//Register activation and deactivation hooks
register_activation_hook( SIMPLE_WP_MEMBERSHIP_PATH . 'simple-wp-membership.php', 'SimpleWpMembership::activate' );
register_deactivation_hook( SIMPLE_WP_MEMBERSHIP_PATH . 'simple-wp-membership.php', 'SimpleWpMembership::deactivate' );

//Instantiate the main class
$simple_membership = new SimpleWpMembership();
$simple_membership_cron = new SwpmCronJob();

//Add settings link in plugins listing page
function swpm_add_settings_link( $links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ) {
		$settings_link = '<a href="admin.php?page=simple_wp_membership_settings">Settings</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'swpm_add_settings_link', 10, 2 );
