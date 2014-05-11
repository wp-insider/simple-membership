<?php
/*
Plugin Name: Simple WordPress Membership
Version: v1.2
Plugin URI: http://www.tipsandtricks-hq.com/development-center
Author: Tips and Tricks HQ
Author URI: http://www.tipsandtricks-hq.com/
Description: Simple WordPress Membership plugin to add Membership functionality to your wordpress blog.
*/
//Direct access to this file is not permitted
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"])){
	exit("Do not access this file directly.");
}
include_once('classes/class.simple-wp-membership.php');
define('SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL', home_url());
define('SIMPLE_WP_MEMBERSHIP_VER', '1.2');
define('SIMPLE_WP_MEMBERSHIP_PATH', dirname(__FILE__) . '/');
define('SIMPLE_WP_MEMBERSHIP_URL', plugins_url('',__FILE__));
define('SIMPLE_WP_MEMBERSHIP_AUTH', 'simple_wp_membership_'. COOKIEHASH); 
define('SIMPLE_WP_MEMBERSHIP_SEC_AUTH', 'simple_wp_membership_sec_'. COOKIEHASH); 
register_activation_hook( SIMPLE_WP_MEMBERSHIP_PATH .'simple-wp-membership.php', 'SimpleWpMembership::activate' );
register_deactivation_hook( SIMPLE_WP_MEMBERSHIP_PATH . 'simple-wp-membership.php', 'SimpleWpMembership::deactivate' );	
add_action('swpm_login','SimpleWpMembership::swpm_login', 10,3);
add_action('plugins_loaded', function(){new SimpleWpMembership();});
