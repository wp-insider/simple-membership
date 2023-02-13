<?php

include_once('class.swpm-utils-misc.php');
include_once('class.swpm-utils.php');
include_once('class.swpm-utils-member.php');
include_once('class.swpm-utils-membership-level.php');
include_once('class.swpm-utils-template.php');
include_once('class.swpm-init-time-tasks.php');
include_once('class.swpm-wp-loaded-tasks.php');
include_once('class.swpm-self-action-handler.php');
include_once('class.swpm-comment-form-related.php');
include_once('class.swpm-settings.php');
include_once('class.swpm-protection.php');
include_once('class.swpm-permission.php');
include_once('class.swpm-auth.php');
include_once('class.swpm-access-control.php');
include_once('class.swpm-form.php');
include_once('class.swpm-transfer.php');
include_once('class.swpm-front-form.php');
include_once('class.swpm-level-form.php');
include_once('class.swpm-membership-levels.php');
include_once('class.swpm-log.php');
include_once('class.swpm-messages.php');
include_once('class.swpm-ajax.php');
include_once('class.swpm-registration.php');
include_once('class.swpm-front-registration.php');
include_once('class.swpm-admin-registration.php');
include_once('class.swpm-membership-level.php');
include_once('class.swpm-membership-level-custom.php');
include_once('class.swpm-permission-collection.php');
include_once('class.swpm-auth-permission-collection.php');
include_once('class.swpm-transactions.php');
include_once('shortcode-related/class.swpm-shortcodes-handler.php');
include_once('class-swpm-member-subscriptions.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_subsc_ipn.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-main.php' );

class SimpleWpMembership {

    public $execution_success_notice = false;
    public $success_notice_pw_reset = false;
    
    public function __construct() {

        new SwpmShortcodesHandler(); //Tackle the shortcode definitions and implementation.
        new SwpmSelfActionHandler(); //Tackle the self action hook handling.

        add_action('admin_menu', array(&$this, 'menu'));
        add_action('init', array(&$this, 'init_hook'));
        add_action('wp_loaded', array(&$this, 'handle_wp_loaded_tasks'));

        add_filter('the_content', array(&$this, 'filter_content'), 20, 1);
        add_filter('widget_text', 'do_shortcode');
        add_filter('show_admin_bar', array(&$this, 'hide_adminbar'));
        add_filter('comment_text', array(&$this, 'filter_comment'));
        add_filter('comment_form_defaults', array('SwpmCommentFormRelated', 'customize_comment_fields'));
        add_filter('wp_get_attachment_url', array(&$this, 'filter_attachment_url'), 10, 2);
        add_filter('wp_get_attachment_metadata', array(&$this, 'filter_attachment'), 10, 2);
        add_filter('attachment_fields_to_save', array(&$this, 'save_attachment_extra'), 10, 2);
        add_filter('rest_request_before_callbacks', array(&$this, 'filter_media_rest_request_before_callbacks'), 10, 3);//For filtering REST API calls for media.

        //TODO - refactor these shortcodes into the shortcodes handler class
        add_shortcode("swpm_registration_form", array(&$this, 'registration_form'));
        add_shortcode('swpm_profile_form', array(&$this, 'profile_form'));
        add_shortcode('swpm_login_form', array(&$this, 'login'));
        add_shortcode('swpm_reset_form', array(&$this, 'reset'));

        add_action('wp_head', array(&$this, 'wp_head_callback'));
        add_action('save_post', array(&$this, 'save_postdata'));
        add_action('admin_notices', array(&$this, 'do_admin_notices'));
        add_action('wp_enqueue_scripts', array(&$this, 'front_library'));
        add_action('load-toplevel_page_simple_wp_membership', array(&$this, 'admin_library'));
        add_action('load-wp-membership_page_simple_wp_membership_levels', array(&$this, 'admin_library'));

        add_action('wp_login', array(&$this, 'wp_login_hook_handler'), 10, 2);
        add_action('wp_authenticate', array(&$this, 'wp_authenticate_handler'), 1, 2);
        add_action('wp_logout', array(&$this, 'wp_logout'));
        add_action('swpm_logout', array(&$this, 'swpm_do_user_logout'));
        add_action('user_register', array(&$this, 'swpm_handle_wp_user_registration'));
        add_action('profile_update', array(&$this, 'sync_with_wp_profile'), 10, 2);

        //AJAX hooks
        add_action('wp_ajax_swpm_validate_email', 'SwpmAjax::validate_email_ajax');
        add_action('wp_ajax_nopriv_swpm_validate_email', 'SwpmAjax::validate_email_ajax');
        add_action('wp_ajax_swpm_validate_user_name', 'SwpmAjax::validate_user_name_ajax');
        add_action('wp_ajax_nopriv_swpm_validate_user_name', 'SwpmAjax::validate_user_name_ajax');

        //init is too early for settings api.
        add_action('admin_init', array(&$this, 'admin_init_hook'));
        add_action('plugins_loaded', array(&$this, "plugins_loaded"));
        add_action('password_reset', array(&$this, 'wp_password_reset_hook'), 10, 2);
        
    }

    public function wp_head_callback() {
        //This function is triggered by the wp_head action hook
        //Check if members only commenting is allowed then customize the form accordingly
        SwpmCommentFormRelated::customize_comment_form();

        //Other wp_head related tasks go here.
    }

    function wp_password_reset_hook($user, $pass) {
        $swpm_user = SwpmMemberUtils::get_user_by_user_name($user->user_login);

        //Check if SWPM user entry exists
        if (empty($swpm_user)) {
            SwpmLog::log_auth_debug("wp_password_reset_hook() - SWPM user not found for username: '" . $user->user_login ."'. This is OK, assuming that this user was created directly in WP Users menu (not using SWPM).", true);
            return;
        }

        $swpm_id = $swpm_user->member_id;
        if (!empty($swpm_id)) {
            $password_hash = SwpmUtils::encrypt_password($pass);
            global $wpdb;
            $wpdb->update($wpdb->prefix . "swpm_members_tbl", array('password' => $password_hash), array('member_id' => $swpm_id));
        }
    }

    public function save_attachment_extra($post, $attachment) {
        $this->save_postdata($post['ID']);
        return $post;
    }

    public function filter_media_rest_request_before_callbacks( $response, $handler, $request ) {
        //Trigger a filter to override this feature from custom code.
        $overridden = apply_filters('swpm_override_filter_media_rest_request_before_callbacks', "");
        if ( ! empty ( $overridden )){
            //This filter has been overridden in a custom code/plugin.
            return $response;
        }
        
        if ( is_admin() ) {
            //No need to filter on the admin dashboard side
            return $response;
        }
        
        //Check if this is a WP REST API query for media.
        $req_route = $request->get_route();
        //SwpmLog::log_simple_debug($req_route, true);
        if ( stripos($req_route, 'media') === false ){
            //Not a media request.
            //SwpmLog::log_simple_debug('Not a media request.', true);
            return $response;
        }
        
        //Check if the media belongs to a post/page that is protected.
        $req_qry_params = $request->get_query_params();
        if ( isset ( $req_qry_params['parent'] ) ){
            //The media has a parent post/page. Lets check if that parent is protected.
            $acl = SwpmAccessControl::get_instance();

            $post_ids = $req_qry_params['parent'];
            foreach ( $post_ids as $post_id){
                //SwpmLog::log_simple_debug('Post ID: ' . $post_id, true);
                //Check access control
                $post = get_post($post_id);
                if ($acl->can_i_read_post($post)) {
                    //I have permission read this post
                    return $response;
                } else {
                    //No permission. Throw an error.
                    return new WP_Error( 'forbidden', 'Access forbidden! The post or page that this media belongs to is protected.', array( 'status' => 403 ) );
                }
            }
        } else {
            //Not for any post/page. Return the normal respose.
            return $response;
        }
    }
    
    public function filter_attachment($content, $post_id) {
        if (is_admin()) {//No need to filter on the admin side
            return $content;
        }

        $acl = SwpmAccessControl::get_instance();
        if (has_post_thumbnail($post_id)) {
            return $content;
        }

        $post = get_post($post_id);
        if ($acl->can_i_read_post($post)) {
            return $content;
        }

        if (isset($content['file'])) {
            $content['file'] = 'restricted-icon.png';
            $content['width'] = '400';
            $content['height'] = '400';
        }

        if (isset($content['sizes'])) {
            if (isset($content['sizes']['thumbnail'])) {
                $content['sizes']['thumbnail']['file'] = 'restricted-icon.png';
                $content['sizes']['thumbnail']['mime-type'] = 'image/png';
            }
            if (isset($content['sizes']['medium'])) {
                $content['sizes']['medium']['file'] = 'restricted-icon.png';
                $content['sizes']['medium']['mime-type'] = 'image/png';
            }
            if (isset($content['sizes']['post-thumbnail'])) {
                $content['sizes']['post-thumbnail']['file'] = 'restricted-icon.png';
                $content['sizes']['post-thumbnail']['mime-type'] = 'image/png';
            }
        }
        return $content;
    }

    public function filter_attachment_url($content, $post_id) {
        if (is_admin()) {//No need to filter on the admin side
            return $content;
        }
        $acl = SwpmAccessControl::get_instance();
        if (has_post_thumbnail($post_id)) {
            return $content;
        }

        $post = get_post($post_id);
        if ($acl->can_i_read_post($post)) {
            return $content;
        }

        return SwpmUtils::get_restricted_image_url();
    }

    public function admin_init_hook() {
        //This hook is triggered in the wp-admin side only.

        $this->common_library(); //Load the common JS libraries and Styles
        $swpm_settings_obj = SwpmSettings::get_instance();

        //Check if the "Disable Access to WP Dashboard" option is enabled.
        $disable_wp_dashboard_for_non_admins = $swpm_settings_obj->get_value('disable-access-to-wp-dashboard');
        if ($disable_wp_dashboard_for_non_admins) {
            //This option is enabled
            if ((defined('DOING_AJAX') && DOING_AJAX)) {
                //This is an ajax request. Don't do the disable dashboard check for ajax.
            } else {
                //Not an ajax request. Do the check.
                if (!current_user_can('administrator')) {
                    //This is a non-admin user. Do not show the wp dashboard.
                    $message = '<p>' . SwpmUtils::_('The admin of this site does not allow users to access the wp dashboard.') . '</p>';
                    $message .= '<p>' . SwpmUtils::_('Go back to the home page by ') . '<a href="' . SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '">' . SwpmUtils::_('clicking here') . '</a>.' . '</p>';
                    wp_die($message);
                }
            }
        }

        //Initialize the settings menu hooks.
        $swpm_settings_obj->init_config_hooks();
        $addon_saved = isset($_POST['swpm-addon-settings']) ? sanitize_text_field($_POST['swpm-addon-settings']) : '';
        if (!empty($addon_saved) && current_user_can('manage_options')) {
            check_admin_referer('swpm_addon_settings_section', 'swpm_addon_settings_section_save_settings');
            do_action('swpm_addon_settings_save');
        }
    }

    public function hide_adminbar() {

        //Never show admin toolbar if the user is not even logged in
        if (function_exists('is_user_logged_in') && !is_user_logged_in()) {
            return false;
        }

        //Show admin toolbar to admin only feature is enabled.
        $show_to_admin = SwpmSettings::get_instance()->get_value('show-adminbar-admin-only');
        if ($show_to_admin) {
            if (current_user_can('administrator')) {
                //This is an admin user so show the tooldbar
                return true;
            } else {
                return false;
            }
        }

        //Hide admin toolbar if the hide adminbar feature is enabled
        $hide = SwpmSettings::get_instance()->get_value('hide-adminbar');
        return $hide ? FALSE : TRUE;
    }

    public function shutdown() {
        SwpmLog::writeall();
    }

    public static function swpm_login($username, $pass, $rememberme = true) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            SwpmLog::log_auth_debug("static function swpm_login(). User is logged in. WP Username: " . $current_user->user_login, true);
            if ($current_user->user_login == $username) {
                return;
            }
        }
        SwpmLog::log_auth_debug("Trying wp_signon() with username: " . $username, true);

        add_filter('wordfence_ls_require_captcha', '__return_false');//For Wordfence plugin's captcha compatibility

        $user_obj = wp_signon(array('user_login' => $username, 'user_password' => $pass, 'remember' => $rememberme), is_ssl());
        if ($user_obj instanceof WP_User) {
            wp_set_current_user($user_obj->ID, $user_obj->user_login);
            SwpmLog::log_auth_debug("Setting current WP user to: " . $user_obj->user_login, true);
        } else {
            SwpmLog::log_auth_debug("wp_signon() failed for the corresponding WP user account.", false);
            if (is_wp_error($user_obj)) {
                //SwpmLog::log_auth_debug("Error Message: ". $user_obj->get_error_message(), false);
                $force_wp_user_sync = SwpmSettings::get_instance()->get_value('force-wp-user-sync');
                if (!empty($force_wp_user_sync)) {
                    //Force WP user login sync is enabled. Show error and exit out since the WP user login failed.
                    $error_msg = SwpmUtils::_("Error! This site has the force WP user login feature enabled in the settings. We could not find a WP user record for the given username: ") . $username;
                    $error_msg .= "<br /><br />" . SwpmUtils::_("This error is triggered when a member account doesn't have a corresponding WP user account. So the plugin fails to log the user into the WP User system.");
                    $error_msg .= "<br /><br />" . SwpmUtils::_("Contact the site admin and request them to check your username in the WP Users menu to see what happened with the WP user entry of your account.");
                    $error_msg .= "<br /><br />" . SwpmUtils::_("The site admin can disable the Force WP User Synchronization feature in the settings to disable this feature and this error will go away.");
                    $error_msg .= "<br /><br />" . SwpmUtils::_("You can use the back button of your browser to go back to the site.");
                    wp_die($error_msg);
                }
            }
        }

        $proceed_after_auth = apply_filters('swpm_login_auth_completed_filter', true);

        if (!$proceed_after_auth) {
            $auth = SwpmAuth::get_instance();
            $auth->logout();
            return;
        }

        SwpmLog::log_auth_debug("Triggering swpm_after_login hook.", true);
        do_action('swpm_after_login');
        if (!SwpmUtils::is_ajax()) {
            //Redirection after login to make sure the page loads with all the correct variables set everywhere.
            $redirect_url = SwpmMiscUtils::get_current_page_url();
            if(empty($redirect_url)){
                $redirect_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
            }
            $redirect_url = apply_filters('swpm_after_login_redirect_url', $redirect_url);
            SwpmLog::log_auth_debug("After triggering the default swpm_after_login_redirect_url hook. Redirect URL: ". $redirect_url, true);
            wp_redirect($redirect_url);
            exit(0);
        }
    }

    public function swpm_do_user_logout() {
        if (is_user_logged_in()) {
            wp_logout();
            wp_set_current_user(0);
        }
    }

    /* This function can be used to authenticate a member using currently logged in wp user. */
    public function set_current_user_handler() {
        $auth = SwpmAuth::get_instance();
        if ($auth->is_logged_in()) {
            return;
        }
        $user = wp_get_current_user();
        if (empty($user) || $user->ID === 0) {
            return false;
        }
        SwpmLog::log_auth_debug('set_current_user action. Attempting to login user ' . $user->user_login, true);
        //remove hook in order for it to not be called several times in the process
        remove_action('set_current_user', array($this, 'set_current_user_handler'));
        $auth->login_to_swpm_using_wp_user($user);
    }

    /* Used to log the user into SWPM system using the wp_login hook. Some social plugins use this hook to handle the login */
    public function wp_login_hook_handler($user_login, $user){
        SwpmLog::log_auth_debug('wp_login hook triggered. Username: ' . $user_login, true);
        $auth = SwpmAuth::get_instance();
        if ($auth->is_logged_in()) {
            //User is already logged-in. Nothing to do.
            return;
        }
        $auth->login_to_swpm_using_wp_user($user);
    }

    public function wp_authenticate_handler($username, $password) {

        $auth = SwpmAuth::get_instance();
        if (($auth->is_logged_in() && ($auth->userData->user_name == $username))) {
            SwpmLog::log_auth_debug('wp_authenticate action. User with username: ' . $username . ' is already logged in.', true);
            return;
        }
        if (!empty($username)) {
            SwpmLog::log_auth_debug('wp_authenticate action. Handling login for username: ' . $username, true);
            $auth->login($username, $password, true);
        } else {
            //empty username can mean some plugin trying to login WP user using its own methods.
            //Let's add hook for set_current_user action and let it handle the login if needed.
            SwpmLog::log_auth_debug('wp_authenticate action. Empty username provided. Adding set_current_username hook to catch potential login attempt.', true);
            add_action('set_current_user', array($this, 'set_current_user_handler'));
        }
    }

    public function login() {
        ob_start();
        $auth = SwpmAuth::get_instance();
        if ($auth->is_logged_in()) {
            //Load the template for logged-in member
            SwpmUtilsTemplate::swpm_load_template('loggedin.php', false);
        } else {
            //Load JS only if option is set
            $display_password_toggle = SwpmSettings::get_instance()->get_value('password-visibility-login-form');
            if ( !empty( $display_password_toggle ) ){
                wp_enqueue_script('swpm.password-toggle');
            }
            //Load the login widget template
            SwpmUtilsTemplate::swpm_load_template('login.php', false);
        }
        return ob_get_clean();
    }

    public function wp_logout() {
        $auth = SwpmAuth::get_instance();
        if ($auth->is_logged_in()) {
            $auth->logout();
        }
    }

    public function sync_with_wp_profile($wp_user_id) {
        global $wpdb;
        $wp_user_data = get_userdata($wp_user_id);
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl WHERE " . ' user_name=%s', $wp_user_data->user_login);
        $profile = $wpdb->get_row($query, ARRAY_A);
        $profile = (array) $profile;
        if (empty($profile)) {
            return;
        }
        $profile['user_name'] = $wp_user_data->user_login;
        $profile['email'] = $wp_user_data->user_email;
        $profile['password'] = $wp_user_data->user_pass;
        $profile['first_name'] = $wp_user_data->user_firstname;
        $profile['last_name'] = $wp_user_data->user_lastname;
        $wpdb->update($wpdb->prefix . "swpm_members_tbl", $profile, array('member_id' => $profile['member_id']));
    }

    function swpm_handle_wp_user_registration($user_id) {

        $swpm_settings_obj = SwpmSettings::get_instance();
        $enable_auto_create_swpm_members = $swpm_settings_obj->get_value('enable-auto-create-swpm-members');
        $default_level = $swpm_settings_obj->get_value('auto-create-default-membership-level');
        $default_ac_status = $swpm_settings_obj->get_value('auto-create-default-account-status');

        if (empty($enable_auto_create_swpm_members)) {
            return;
        }
        if (empty($default_level)) {
            return;
        }

        $user_info = get_userdata($user_id);
        if (SwpmMemberUtils::get_user_by_user_name($user_info->user_login)) {
            SwpmLog::log_simple_debug("swpm_handle_wp_user_registration() - SWPM member account with this username already exists! No new account will be created for this user.", false);
            return;
        }
        if (SwpmMemberUtils::get_user_by_email($user_info->user_email)) {
            SwpmLog::log_simple_debug("swpm_handle_wp_user_registration() - SWPM member account with this email already exists! No new account will be created for this user.", false);
            return;
        }
        $fields = array();
        $fields['user_name'] = $user_info->user_login;
        $fields['password'] = $user_info->user_pass;
        $fields['email'] = $user_info->user_email;
        $fields['first_name'] = $user_info->first_name;
        $fields['last_name'] = $user_info->last_name;
        $fields['membership_level'] = $default_level;
        $fields['member_since'] = SwpmUtils::get_current_date_in_wp_zone();
        $fields['account_state'] = $default_ac_status;
        $fields['subscription_starts'] = SwpmUtils::get_current_date_in_wp_zone();
        SwpmMemberUtils::create_swpm_member_entry_from_array_data($fields);
    }

    public function reset() {        
        //Check if the form has been submitted and there is a success message.
        $any_notice_output = $this->capture_any_notice_output();
        if( !empty( $any_notice_output ) && $this->success_notice_pw_reset ){
            //The password reset form execution was a success. Return the success notice output string (it will be used with the shortcode output).
            return $any_notice_output;
        }        

        if( isset( $_GET["action"]) && $_GET["action"] == "swpm-reset-using-link" ) {
            ob_start();
            echo $any_notice_output;//Include any output from the execution (for showing output inside the shortcode)
            //Load the reset password template
            SwpmUtilsTemplate::swpm_load_template('reset_password_using_link.php', false);
            return ob_get_clean();
        }
        else {
            ob_start();
            echo $any_notice_output;//Include any output from the execution (for showing output inside the shortcode)
            //Load the forgot password template
            SwpmUtilsTemplate::swpm_load_template('forgot_password.php', false);
            return ob_get_clean();
        }
    }

    public function profile_form() {
        $output = '';
        $auth = SwpmAuth::get_instance();
        $any_notice_output = $this->capture_any_notice_output();
        if ($auth->is_logged_in()) {
            $override_out = apply_filters('swpm_profile_form_override', '');
            if (!empty($override_out)) {
                return $override_out;
            }
            ob_start();
            echo $any_notice_output;//Include any output from the execution (for showing output inside the shortcode)
            //Load the edit profile template
            SwpmUtilsTemplate::swpm_load_template('edit.php', false);
            return ob_get_clean();
        }
        //User is not logged into the site. Show appropriate message.
        $output .= '<div class="swpm_profile_not_logged_in_msg">';
        $output .= SwpmUtils::_('You are not logged in.');
        $output .= '</div>';
        return $output;
    }

    /*
     * Similar to $this->notices() function but instead of echoing the message, it returns the message (if any).
     * Useful for using inside a shortcode output.
     */
    public function capture_any_notice_output() {
        $output = '';
        $message = SwpmTransfer::get_instance()->get('status');
        if ( empty( $message ) ) {
            return $output;
        }
        
        if ( $message['succeeded'] ) {
            $output .= "<div id='swpm_message' class='swpm_success'>";
            $this->execution_success_notice = true;
        } else {
            $output .= "<div id='swpm_message' class='swpm_error'>";
        }
        $output .= $message['message'];
        $extra = isset( $message['extra'] ) ? $message['extra'] : array();
        if ( is_string($extra) ) {
            $output .= $extra;
        } else if ( is_array($extra) && !empty($extra) ) {
            $output .= '<ul>';
            foreach ($extra as $key => $value) {
                $output .= '<li>' . $value . '</li>';
            }
            $output .= '</ul>';
        }
        $output .= "</div>";
        //If password reset notice was sent, set the flag.
        if (isset($message['pass_reset_sent'])) {
            $this->success_notice_pw_reset = true;
        }        
        return $output;
    }
    
    /* If any message/notice was set during the execution then this function will output that message */
    public function notices() {
        $message = SwpmTransfer::get_instance()->get('status');
        $succeeded = false;
        if (empty($message)) {
            return false;
        }
        if ($message['succeeded']) {
            echo "<div id='swpm_message' class='swpm_success'>";
            $succeeded = true;
        } else {
            echo "<div id='swpm_message' class='swpm_error'>";
        }
        echo $message['message'];
        $extra = isset($message['extra']) ? $message['extra'] : array();
        if (is_string($extra)) {
            echo $extra;
        } else if (is_array($extra) && !empty($extra)) {
            echo '<ul>';
            foreach ($extra as $key => $value) {
                echo '<li>' . $value . '</li>';
            }
            echo '</ul>';
        }
        echo "</div>";
        if (isset($message['pass_reset_sent'])) {
            $succeeded = true;
        }
        return $succeeded;
    }

    /*
     * This function is hooked to WordPress's admin_notices action hook
     * It is used to show any plugin specific notices/warnings in the admin interface
     */

    public function do_admin_notices() {
        $this->notices(); //Show any execution specific notices in the admin interface.
        //Show any other general warnings/notices to the admin.
        if (SwpmMiscUtils::is_swpm_admin_page()) {
            //we are in an admin page for SWPM plugin.

            $msg = '';
            //Show notice if running in sandbox mode.
            $settings = SwpmSettings::get_instance();
            $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
            if ($sandbox_enabled) {
                $msg .= '<p>' . SwpmUtils::_('You have the sandbox payment mode enabled in plugin settings. Make sure to turn off the sandbox mode when you want to do live transactions.') . '</p>';
            }

            if (!empty($msg)) {//Show warning messages if any.
                echo '<div id="message" class="error">';
                echo $msg;
                echo '</div>';
            }
        }
    }

    public function meta_box() {
        if (function_exists('add_meta_box')) {
            $post_types = get_post_types();
            foreach ($post_types as $post_type => $post_type) {
                add_meta_box('swpm_sectionid', __('Simple WP Membership Protection', 'simple-membership'), array(&$this, 'inner_custom_box'), $post_type, 'advanced');
            }
        } else {//older version doesn't have custom post type so modification isn't needed.
            add_action('dbx_post_advanced', array(&$this, 'show_old_custom_box'));
            add_action('dbx_page_advanced', array(&$this, 'show_old_custom_box'));
        }
    }

    public function show_old_custom_box() {
        echo '<div class="dbx-b-ox-wrapper">' . "\n";
        echo '<fieldset id="swpm_fieldsetid" class="dbx-box">' . "\n";
        echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">' .
        __('Simple Membership Protection options', 'simple-membership') . "</h3></div>";
        echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';
        // output editing form
        $this->inner_custom_box();
        // end wrapper
        echo "</div></div></fieldset></div>\n";
    }

    public function inner_custom_box() {
        global $post, $wpdb;
        $id = $post->ID;
        $protection_obj = SwpmProtection::get_instance();
        $is_protected = $protection_obj->is_protected($id);

        //Nonce input
        echo '<input type="hidden" name="swpm_post_protection_box_nonce" value="' . wp_create_nonce('swpm_post_protection_box_nonce_action') . '" />';

        // The actual fields for data entry
        echo '<h4>' . __("Do you want to protect this content?", 'simple-membership') . '</h4>';
        echo '<input type="radio" ' . ((!$is_protected) ? 'checked' : "") . '  name="swpm_protect_post" value="1" /> ' . SwpmUtils::_('No, Do not protect this content.') . '<br/>';
        echo '<input type="radio" ' . (($is_protected) ? 'checked' : "") . '  name="swpm_protect_post" value="2" /> ' . SwpmUtils::_('Yes, Protect this content.') . '<br/>';
        echo $protection_obj->get_last_message();

        echo '<h4>' . __("Select the membership level that can access this content:", 'simple-membership') . "</h4>";
        $query = "SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE  id !=1 ";
        $levels = $wpdb->get_results($query, ARRAY_A);
        foreach ($levels as $level) {
            echo '<input type="checkbox" ' . (SwpmPermission::get_instance($level['id'])->is_permitted($id) ? "checked='checked'" : "") .
            ' name="swpm_protection_level[' . $level['id'] . ']" value="' . $level['id'] . '" /> ' . $level['alias'] . "<br/>";
        }
    }

    public function save_postdata($post_id) {
        global $wpdb;
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
        $swpm_protect_post = isset($_POST['swpm_protect_post']) ? sanitize_text_field($_POST['swpm_protect_post']) : '';

        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        //Check nonce
        $swpm_post_protection_box_nonce = filter_input(INPUT_POST, 'swpm_post_protection_box_nonce');
        if (!wp_verify_nonce($swpm_post_protection_box_nonce, 'swpm_post_protection_box_nonce_action')) {
            //Nonce check failed.
            return $post_id;
        }

        if ('page' == $post_type) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }
        if (empty($swpm_protect_post)) {
            return;
        }
        // OK, we're authenticated: we need to find and save the data
        $isprotected = ($swpm_protect_post == 2);
        $args = array('swpm_protection_level' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
        ));
        $swpm_protection_level = filter_input_array(INPUT_POST, $args);
        $swpm_protection_level = $swpm_protection_level['swpm_protection_level'];
        if (!empty($post_type)) {
            if ($isprotected) {
                SwpmProtection::get_instance()->apply(array($post_id), $post_type);
            } else {
                SwpmProtection::get_instance()->remove(array($post_id), $post_type);
            }
            SwpmProtection::get_instance()->save();
            $query = "SELECT id FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE  id !=1 ";
            $level_ids = $wpdb->get_col($query);
            foreach ($level_ids as $level) {
                if (isset($swpm_protection_level[$level])) {
                    SwpmPermission::get_instance($level)->apply(array($post_id), $post_type)->save();
                } else {
                    SwpmPermission::get_instance($level)->remove(array($post_id), $post_type)->save();
                }
            }
        }
        $enable_protection = array();
        $enable_protection['protect'] = $swpm_protect_post;
        $enable_protection['level'] = $swpm_protection_level;
        return $enable_protection;
    }

    public function filter_comment($content) {
        if (is_admin()) {
            //Do not apply filtering for admin side viewing
            return $content;
        }

        $acl = SwpmAccessControl::get_instance();
        global $comment;
        return $acl->filter_comment($comment, $content);
    }

    public function filter_content($content) {
        if (is_preview() || is_admin()) {
            //If the user is logged-in as an admin user then do not apply filtering for admin side viewing or preview page viewing.
            if ( current_user_can('administrator') ){
                //The user is logged in as admin in this browser.
                return $content;
            }
        }
        $acl = SwpmAccessControl::get_instance();
        global $post;
        return $acl->filter_post($post, $content);
    }

    public function init_hook() {
        $init_tasks = new SwpmInitTimeTasks();
        $init_tasks->do_init_tasks();
    }

    public function handle_wp_loaded_tasks() {
        $wp_loaded_tasks = new SwpmWpLoadedTasks();
        $wp_loaded_tasks->do_wp_loaded_tasks();
    }

    public function admin_library() {
        //Only loaded on selective swpm admin menu page rendering.
        $this->common_library();
        wp_enqueue_script('password-strength-meter');
        wp_enqueue_script('swpm.password-meter', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm.password-meter.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
        //jQuery UI style
        wp_register_style('swpm-jquery-ui', SIMPLE_WP_MEMBERSHIP_URL . '/css/jquery-ui.min.css', array(), SIMPLE_WP_MEMBERSHIP_VER);
        wp_enqueue_style('swpm-jquery-ui');
        wp_enqueue_script('jquery-ui-datepicker');
        $settings = array('statusChangeEmailHead' => SwpmSettings::get_instance()->get_value('account-change-email-subject'),
            'statusChangeEmailBody' => SwpmSettings::get_instance()->get_value('account-change-email-body'));
        wp_localize_script('swpm.password-meter', 'SwpmSettings', $settings);
    }

    public function front_library() {
        $this->common_library();
    }

    private function common_library() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('swpm.common', SIMPLE_WP_MEMBERSHIP_URL . '/css/swpm.common.css', array(), SIMPLE_WP_MEMBERSHIP_VER);

        //In order to not clog WP with scripts and styles we're only using with forms, let's just register those for now
        //Scripts will be queued when forms are actually displayed
        wp_register_style('validationEngine.jquery', SIMPLE_WP_MEMBERSHIP_URL . '/css/validationEngine.jquery.css', array(), SIMPLE_WP_MEMBERSHIP_VER);
        wp_register_script('jquery.validationEngine', SIMPLE_WP_MEMBERSHIP_URL . '/js/jquery.validationEngine.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
        wp_register_script('jquery.validationEngine-en', SIMPLE_WP_MEMBERSHIP_URL . '/js/jquery.validationEngine-en.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
        wp_register_script('swpm.validationEngine-localization', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm.validationEngine-localization.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
        wp_register_script('swpm.password-toggle', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm.password-toggle.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
        
        //Stripe libraries
        wp_register_script("swpm.stripe", "https://js.stripe.com/v3/", array("jquery"), SIMPLE_WP_MEMBERSHIP_VER);
	wp_register_style("swpm.stripe.style", "https://checkout.stripe.com/v3/checkout/button.css", array(), SIMPLE_WP_MEMBERSHIP_VER);
    }

    public static function enqueue_validation_scripts( $additional_params = array() ) {
        //This function gets called from a shortcode. So use the below technique to make the inline script loading process work smoothly.
        
        //In some themes (block themes) this may not have been registered yet since that process can be delayed. So do it now before adding any inline or localize scripts to it.
        if ( ! wp_script_is( 'jquery.validationEngine', 'registered' ) ) {
            wp_register_script('jquery.validationEngine', SIMPLE_WP_MEMBERSHIP_URL . '/js/jquery.validationEngine.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
        }
        if ( ! wp_script_is( 'jquery.validationEngine-en', 'registered' ) ) {
            wp_register_script('jquery.validationEngine-en', SIMPLE_WP_MEMBERSHIP_URL . '/js/jquery.validationEngine-en.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
	}
	if ( ! wp_script_is( 'swpm.validationEngine-localization', 'registered' ) ) {
            wp_register_script('swpm.validationEngine-localization', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm.validationEngine-localization.js', array('jquery'), SIMPLE_WP_MEMBERSHIP_VER);
	}

        //The above code ensures that the scripts are registered for sure. Now we can enqueue and add inline script to them. This process works on all themes.
        wp_enqueue_style('validationEngine.jquery');
        wp_enqueue_script('jquery.validationEngine');
        wp_enqueue_script('jquery.validationEngine-en');
        wp_enqueue_script('swpm.validationEngine-localization');
        
        //Localization for jquery.validationEngine
        //This array will be merged with $.validationEngineLanguage.allRules object from jquery.validationEngine-en.js file
        $loc_data = array(
            'ajaxUserCall' => array(
                'url' => admin_url('admin-ajax.php'),
                'alertTextLoad' => '* ' . SwpmUtils::_('Validating, please wait'),
            ),
            'ajaxEmailCall' => array(
                'url' => admin_url('admin-ajax.php'),
                'alertTextLoad' => '* ' . SwpmUtils::_('Validating, please wait'),
            ),
            'email' => array(
                'alertText' => '* ' . SwpmUtils::_('Invalid email address'),
            ),
            'required' => array(
                'alertText' => '* ' . SwpmUtils::_('This field is required'),
            ),
            'strongPass' => array(
                'alertText' => '* ' . SwpmUtils::_('Password must contain at least:').'<br>'.SwpmUtils::_('- a digit').'<br>'.SwpmUtils::_('- an uppercase letter').'<br>'.SwpmUtils::_('- a lowercase letter'),
            ),
            'SWPMUserName' => array(
                'alertText' => '* ' . SwpmUtils::_('Invalid Username').'<br>'.SwpmUtils::_('Usernames can only contain: letters, numbers and .-_*@'),
            ),
            'minSize' => array(
                'alertText' => '* ' . SwpmUtils::_('Minimum '),
                'alertText2' => SwpmUtils::_(' characters required'),
            ),
            'noapostrophe' => array(
                'alertText' => '* ' . SwpmUtils::_('Apostrophe character is not allowed'),
            ),
        );

        $nonce = wp_create_nonce( 'swpm-rego-form-ajax-nonce' );

        if ($additional_params) {
            // Additional parameters should be added to the array, replacing existing ones
            if (isset($additional_params['ajaxEmailCall'])) {
                if (isset($additional_params['ajaxEmailCall']['extraData'])) {
                    $additional_params['ajaxEmailCall']['extraData'].='&nonce='.$nonce;
                }
            }
            $loc_data = array_replace_recursive($additional_params, $loc_data);
        }

        //The scripts are registered and enqueued. We can now add inline script to any of those registered scripts.
        wp_localize_script('swpm.validationEngine-localization', 'swpm_validationEngine_localization', $loc_data);

        wp_localize_script('jquery.validationEngine-en', 'swpmRegForm', array('nonce' => $nonce));
    }

    public function registration_form($atts) {
        $output = "";
        
        //Check if the form has been submitted and there is a success message.
        $any_notice_output = $this->capture_any_notice_output();
        if( !empty( $any_notice_output ) && $this->execution_success_notice ){
            //The registration form execution was a success. Return the success notice output string (it will be used with the shortcode output).
            return $any_notice_output;
        }
        
        $is_free = SwpmSettings::get_instance()->get_value('enable-free-membership');
        $free_level = absint(SwpmSettings::get_instance()->get_value('free-membership-id'));
        $level = isset($atts['level']) ? absint($atts['level']) : ($is_free ? $free_level : null);
        
        $output .= $any_notice_output;
        $output .= SwpmFrontRegistration::get_instance()->regigstration_ui($level);
        return $output;
    }

    public function menu() {
        $menu_parent_slug = 'simple_wp_membership';

        add_menu_page(__("WP Membership", 'simple-membership'), __("WP Membership", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, $menu_parent_slug, array(&$this, "admin_members_menu"), 'dashicons-id');
        add_submenu_page($menu_parent_slug, __("Members", 'simple-membership'), __('Members', 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership', array(&$this, "admin_members_menu"));
        add_submenu_page($menu_parent_slug, __("Membership Levels", 'simple-membership'), __("Membership Levels", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_levels', array(&$this, "admin_membership_levels_menu"));
        add_submenu_page($menu_parent_slug, __("Settings", 'simple-membership'), __("Settings", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_settings', array(&$this, "admin_settings_menu"));
        add_submenu_page($menu_parent_slug, __("Payments", 'simple-membership'), __("Payments", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_payments', array(&$this, "admin_payments_menu"));
        add_submenu_page($menu_parent_slug, __("Add-ons", 'simple-membership'), __("Add-ons", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_addons', array(&$this, "admin_add_ons_menu"));

        do_action('swpm_after_main_admin_menu', $menu_parent_slug);

        $this->meta_box();
    }

    /* Render the members menu in admin dashboard */

    public function admin_members_menu() {
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-members.php');
        $members = new SwpmMembers();
        $members->handle_main_members_admin_menu();
    }

    /* Render the membership levels menu in admin dashboard */

    public function admin_membership_levels_menu() {
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-membership-levels.php');
        $levels = new SwpmMembershipLevels();
        $levels->handle_main_membership_level_admin_menu();
    }

    /* Render the settings menu in admin dashboard */

    public function admin_settings_menu() {
        $settings = SwpmSettings::get_instance();
        $settings->handle_main_settings_admin_menu();
    }

    public function admin_payments_menu() {
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-payments-admin-menu.php');
        $payments_admin = new SwpmPaymentsAdminMenu();
        $payments_admin->handle_main_payments_admin_menu();
    }

    public function admin_add_ons_menu() {
        include(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_add_ons_page.php');
    }

    public function plugins_loaded() {
        //Runs when plugins_loaded action gets fired
        if (is_admin()) {
            //Check and run DB upgrade operation (if needed)
            if (get_option('swpm_db_version') != SIMPLE_WP_MEMBERSHIP_DB_VER) {
                include_once('class.swpm-installation.php');
                SwpmInstallation::run_safe_installer();
            }
        }
    }

    public static function activate() {
        wp_schedule_event(time(), 'daily', 'swpm_account_status_event');
        wp_schedule_event(time(), 'daily', 'swpm_delete_pending_account_event');
        include_once('class.swpm-installation.php');
        SwpmInstallation::run_safe_installer();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('swpm_account_status_event');
        wp_clear_scheduled_hook('swpm_delete_pending_account_event');
    }

}
