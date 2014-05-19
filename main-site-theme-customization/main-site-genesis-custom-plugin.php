<?php
/*
Plugin Name: Theme Customization Plugin (https://simple-membership-plugin.com)
Version: v1.0
Plugin URI: https://simple-membership-plugin.com
Author: Ruhul Amin
Author URI: https://simple-membership-plugin.com
Description: Contains all the custom tweaks for main https://simple-membership-plugin.com WordPress install
*/
//DOC - http://my.studiopress.com/snippets/
//DOC2 - http://gregrickaby.com/genesis-code-snippets/
//HELP - http://my.studiopress.com/help/

define('MGC_CUSTOM_PLUGIN_URL', plugins_url('',__FILE__));

add_action('init', 'ugc_custom_plugin_init');
function ugc_custom_plugin_init()
{
    wp_enqueue_style('mgc-genesis-custom-css', MGC_CUSTOM_PLUGIN_URL. '/css/mgc-genesis-custom-styles.css');
}

/* customize the footer output */
add_filter( 'genesis_footer_output', 'mgc_custom_footer', 100);
function mgc_custom_footer( $output ) {
    $output = '<div class="creds"><p>';
    $output .= 'Copyright &copy; ';
    $output .= date('Y');
    $output .= ' | <a href="https://simple-membership-plugin.com">Simple Membership Plugin</a>';
    $output .= '</p></div>';
    return $output;
}


