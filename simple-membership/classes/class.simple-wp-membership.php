<?php

include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-utils-misc.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-utils.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-utils-member.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-utils-membership-level.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-utils-template.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-init-time-tasks.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-wp-loaded-tasks.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-wp-tasks.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-self-action-handler.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-comment-form-related.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-settings.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-protection.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-permission.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-auth.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-access-control.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-form.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-transfer.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-front-form.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-level-form.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-membership-levels.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-log.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-messages.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-ajax.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-registration.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-front-registration.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-admin-registration.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-membership-level.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-membership-level-custom.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-permission-collection.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-auth-permission-collection.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-transactions.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/shortcode-related/class.swpm-shortcodes-handler.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-utils-subscription.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-block.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-members-meta.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-cronjob.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_subsc_ipn.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'lib/paypal/class-swpm-paypal-main.php' );
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-limit-active-login.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-limit-login-attempts.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-event-logger.php');
include_once( SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-woocommerce-protection.php');

class SimpleWpMembership {
    
    public function __construct() {

        new SwpmShortcodesHandler(); //Tackle the shortcode definitions and implementation.
        new SwpmSelfActionHandler(); //Tackle the self action hook handling.
	    new SwpmLimitActiveLogin(); // Tackle login limit functionalities.
	    new SwpmLimitFailedLoginAttempts(); // Tackle login limit functionalities.
		new SwpmEventLogger(); // Tackle event log related functionalities.

        //Load the plugin text domain
        //We are loading the text domain in init with a high priority for better compatibility with other plugins. Most langauges (example: de_DE) work fine with this. Alternative is to load it in plugins_loaded.
        add_action('init', array(&$this, 'load_swpm_textdomain'), 5);

        //The init and wp_loaded hooks.
        add_action('init', array(&$this, 'init_hook'));
        add_action('wp_loaded', array(&$this, 'handle_wp_loaded_tasks'));
        add_action('wp', array(&$this, 'handle_wp_tasks'));

        //Admin menu hook.
        add_action('admin_menu', array(&$this, 'menu'));

        //Other general hooks
        add_filter('the_content', array(&$this, 'filter_content'), 20, 1);
        add_filter('widget_text', 'do_shortcode');
        add_filter('show_admin_bar', array(&$this, 'hide_adminbar'));
        add_filter('comment_text', array(&$this, 'filter_comment'));
        add_filter('comment_form_defaults', array('SwpmCommentFormRelated', 'customize_comment_fields'));
        add_filter('wp_get_attachment_url', array(&$this, 'filter_attachment_url'), 10, 2);
        add_filter('wp_get_attachment_metadata', array(&$this, 'filter_attachment'), 10, 2);
        add_filter('attachment_fields_to_save', array(&$this, 'save_attachment_extra'), 10, 2);
        add_filter('rest_request_before_callbacks', array(&$this, 'filter_media_rest_request_before_callbacks'), 10, 3);//For filtering REST API calls for media.

        add_action('wp_head', array(&$this, 'wp_head_callback'));
        add_action('save_post', array(&$this, 'save_postdata'));
        add_action('admin_notices', array(&$this, 'do_admin_notices'));
        add_action('wp_enqueue_scripts', array(&$this, 'front_library'));
        add_action('load-toplevel_page_simple_wp_membership', array(&$this, 'admin_library'));
        add_action('load-wp-membership_page_simple_wp_membership_levels', array(&$this, 'admin_library'));

        //Core WP hooks that we need to hook into
        add_action('wp_authenticate', array(&$this, 'wp_authenticate_handler'), 1, 2);//This hook is triggered before WordPress authenticates the user. Useful for pre-authentication tasks.
        add_action('wp_login', array(&$this, 'wp_login_hook_handler'), 10, 2);//This hook is triggered after WordPress authenticates a user. It passes the user's username and user object (of the authenticated user).
        add_action('wp_logout', array(&$this, 'wp_logout_handler'));
        add_action('password_reset', array(&$this, 'wp_password_reset_hook'), 10, 2);
        add_action('user_register', array(&$this, 'swpm_handle_wp_user_registration'));
        add_action('profile_update', array(&$this, 'sync_with_wp_profile'), 10, 3);        

        //SWPM login/logout hooks.
        //Note: These should only handle/execute when the login or logout originates from our plugin's login/logout form to prevent loop.
        add_action('swpm_after_login_authentication', array('SimpleWpMembership', 'handle_after_login_authentication'), 10, 3 );
        add_action('swpm_after_logout_function_executed', array(&$this, 'swpm_after_member_logout_tasks'));

        //AJAX hooks
        add_action('wp_ajax_swpm_validate_email', 'SwpmAjax::validate_email_ajax');
        add_action('wp_ajax_nopriv_swpm_validate_email', 'SwpmAjax::validate_email_ajax');
        add_action('wp_ajax_swpm_validate_user_name', 'SwpmAjax::validate_user_name_ajax');
        add_action('wp_ajax_nopriv_swpm_validate_user_name', 'SwpmAjax::validate_user_name_ajax');

        //init is too early for settings api.
        add_action('admin_init', array(&$this, 'admin_init_hook'));
        add_action('plugins_loaded', array(&$this, "plugins_loaded"));

        //Filter to exclude the protected posts from the search results.
	    add_filter('pre_get_posts', array(&$this, 'exclude_swpm_protected_posts_from_wp_search_result'));
    }

    public function load_swpm_textdomain() {
		//Set up localisation. First loaded ones will override strings present in later loaded file.
		//The following technique allows users to have a customized language in a different folder.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'simple-membership' );
		load_textdomain( 'simple-membership', WP_LANG_DIR . "/simple-membership-$locale.mo" );
        //Load the plugin's language files.
		load_plugin_textdomain( 'simple-membership', false, SIMPLE_WP_MEMBERSHIP_DIRNAME . '/languages/' );
    }

	public function exclude_swpm_protected_posts_from_wp_search_result($query) {

		// Trigger a filter so that other plugins can override this feature and allow protected posts to be included in search results.
		$override_protected_post_exclusion = apply_filters('swpm_override_protected_post_exclusion_from_search', false);
		if ($override_protected_post_exclusion){
			// Allow searching protected posts without filtering them. Return from here to maintain this behavior.
			return;
		}

        //Let's determine if this query is for a standard WP search or a REST API search.
        $is_search_query = false;
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $is_search_query = true;
        } else if (
            defined('REST_REQUEST') && REST_REQUEST && // Check if it's a REST request
            strpos($_SERVER['REQUEST_URI'], '/wp/v2/search') !== false // Confirm it's the search endpoint
        ){
            $is_search_query = true;
        }

        if( !$is_search_query ){
            //This is not a search query. Nothing to exclude.
            return;
        }

        //Get the list of all protected post IDs.
        $protected_post_ids = SwpmProtection::get_all_protected_post_ids_list_from_db();
        if (empty($protected_post_ids)){
            return;
        }

        //Check if the user is logged in. If so, filter the protected post IDs for the current user.
		if (SwpmAuth::get_instance()->is_logged_in()){
			$protected_post_ids = SwpmProtection::filter_protected_post_ids_list_for_current_user($protected_post_ids);
		}

		// Modify the search query to exclude the protected posts.
		$query->set('post__not_in', $protected_post_ids);
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

        //Admin user feedback notice
        include_once SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-admin-user-feedback.php';
        $user_feedback = new SWPM_Admin_User_Feedback();
        $user_feedback->init();

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

    public static function handle_after_login_authentication($username, $pass, $rememberme = true) {
        //This function is called after the authentication is successful in SWPM.
        //Note: This function should ONLY be executed/handled by us when the login originates from our plugin's login form.

        //Check if the login request originated from our plugin's login form.
        if ( !isset($_REQUEST['swpm_user_name']) ) {
            //This is not a login request from our plugin's login form. 
            //Our plugin's login request (Standard login, login after registration, 2FA login, etc.) should have the 'swpm_user_name' parameter.
            //We have also added 'swpm_login_origination_flag' parameter to all our logins to identify the login request origination.

            //Return from here since WP or the other plugin will handle the full login operation and post-login redirection.
            SwpmLog::log_auth_debug("The 'swpm_user_name' query parameter is not set. This login action didn't originate from our plugin's login form.", true);
            SwpmLog::log_auth_debug("Exiting this function to skip wp_signon and after_login_redirection since the login was initiated by WP or another plugin.", true);
            //Trigger an action hook for this scenario.
            do_action('swpm_after_login_authentication_external_login_action');
            return;
        }

        //Check if the login request is for a user that is already logged in.
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            SwpmLog::log_auth_debug("Static function handle_after_login_authentication(). User is logged in. WP Username: " . $current_user->user_login, true);
            if ($current_user->user_login == $username) {
                //The user is already logged in. Nothing to do.
                return;
            }
        }
        SwpmLog::log_auth_debug("Trying wp_signon() with username: " . $username, true);

        //For Wordfence plugin's captcha compatibility.
        add_filter('wordfence_ls_require_captcha', '__return_false');

        //Try to log-in the user into the WP user system.
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

        SwpmLog::log_auth_debug("Triggering swpm_login_auth_completed_filter hook.", true);
        $proceed_after_auth = apply_filters('swpm_login_auth_completed_filter', true);
        if (!$proceed_after_auth) {
            $auth = SwpmAuth::get_instance();
            $auth->logout();
            return;
        }

        SwpmLog::log_auth_debug("Triggering swpm_after_login hook.", true);
        do_action('swpm_after_login');//This hook is triggered when login originates from our plugin's login form.

        if ( !SwpmUtils::is_ajax() ) {
            //Redirection after login to make sure the page loads with all the correct variables set everywhere.

            //Check if "redirect_to" parameter is set. If so, use that URL.
            if(isset($_REQUEST['redirect_to'])){
                $redirect_url = sanitize_url($_REQUEST['redirect_to']);
                //Validate the redirect URL
                $fallback_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
                //The 'allowed_redirect_hosts' filter hook can be used to add or remove allowed hosts.
                $redirect_url = wp_validate_redirect( $redirect_url, $fallback_url );
                SwpmLog::log_auth_debug("The redirect_to query parameter is set. URL value after the wp validation: ". $redirect_url, true);
            } else {
                //The 'redirect_to' parameter is not set. By default we will use the current page URL as the redirect URL.
                $redirect_url = SwpmMiscUtils::get_current_page_url();

                //Check if this is after an auto-login authentication (if yes, we need to override the URL).
                if( isset( $_REQUEST['swpm_auto_login']) && $_REQUEST['swpm_auto_login'] == '1' ){
                    //On some servers the current page URL may contain the 'swpm_auto_login' parameter (when the auto-login feature is used) . 
                    //In that case, we don't want to create a loop of auto-login redirect.
                    //We will use the site's login-page URL as the redirect URL for that condition.
                    SwpmLog::log_auth_debug("This is after an auto-login authentication. Setting the login page URL as the redirect URL.", true);
                    $redirect_url = SwpmSettings::get_instance()->get_value('login-page-url');
                }                
            }

            //Check if the URL is still empty. If so, use the site home URL.
            if(empty($redirect_url)){
                $redirect_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL;
            }

            $redirect_url = apply_filters('swpm_after_login_redirect_url', $redirect_url);
            SwpmLog::log_auth_debug("After triggering the default swpm_after_login_redirect_url hook. Redirect URL: ". $redirect_url, true);
            wp_redirect($redirect_url);
            exit(0);
        }
    }

    public function swpm_after_member_logout_tasks() {
        //The hook is triggered after the user is logged out of SWPM.
        //This function should only handle/execute when the logout originates from our plugin's logout form/link.
        if (is_user_logged_in()) {
            //NOTE: Instead of using wp_logout() function, we will do the following that wp_logout() does then trigger our own action hook. 
            //This prevents the 'wp_logout' hook from getting triggered from the wp_logout() function and creating a loop. 
            //It allows a seamless logout process and any after logout tasks or redirection to be handled by our own action hook.
            $user_id = get_current_user_id();
	        wp_destroy_current_session();
	        wp_clear_auth_cookie();
            wp_set_current_user(0);
            do_action( 'swpm_wp_user_logout_complete', $user_id );
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

    /* 
    * Used to log the user into SWPM system using the wp_login hook. Some social plugins use this hook to handle the login.
    */
    public function wp_login_hook_handler($user_login, $user){
        //This hook is triggered after WordPress authenticates a user. 
        //It passes the user's username and user object (of the authenticated user).        
        SwpmLog::log_auth_debug('wp_login hook triggered. Username: ' . $user_login, true);
        $auth = SwpmAuth::get_instance();
        if ($auth->is_logged_in()) {
            //User is already logged-in. Nothing to do.
            return;
        }
        $auth->login_to_swpm_using_wp_user($user);
    }

    /*
    * We can use this function to do any pre-authentication tasks.
    * This function is triggered before WordPress authenticates the user.
    */
    public function wp_authenticate_handler($username, $password) {
        //This hook is triggered before WordPress authenticates the user. Useful for pre-authentication tasks.

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

    public function wp_logout_handler() {
        $auth = SwpmAuth::get_instance();
        if ($auth->is_logged_in()) {
            $auth->logout();
        }
    }

    public function sync_with_wp_profile($wp_user_id, $old_user_data, $userdata = array()) {
        //Reference - https://developer.wordpress.org/reference/hooks/profile_update/

        //Check if the SWPM profile update form was submitted.
		$swpm_editprofile_submit = filter_input( INPUT_POST, 'swpm_editprofile_submit' );
		if ( ! empty( $swpm_editprofile_submit ) ) {
            //This is a SWPM profile update form submission. Nothing to do here.
            //SwpmLog::log_simple_debug( 'WP profile_update hook handler - SWPM profile update form submission detected. Nothing to do here.', true );
            return;
        }

        //Trigger a filter hook to allow any addon(s) to override the wp profile_update hook handling.
        $overriden_msg = apply_filters('swpm_wp_profile_update_hook_override', '');
        if( !empty( $overriden_msg ) ){
            //The WP profile_update hook handling has been overridden by an addon.
            SwpmLog::log_simple_debug( 'WP profile_update hook handling has been overridden by an addon. Nothing to do here.', true );
            return;
        }

        //Retrieve the SWPM user profile for the given WP user ID.
        global $wpdb;
        $wp_user_data = get_userdata($wp_user_id);
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl WHERE " . ' user_name=%s', $wp_user_data->user_login);
        $profile = $wpdb->get_row($query, ARRAY_A);
        $profile = (array) $profile;
        if ( empty($profile) ) {
            //No SWPM user found for this WP user. Nothing to do.
            return;
        }

        //Useful for debugging purpose
        //SwpmLog::log_simple_debug('WP User ID: ' . $wp_user_id, true);
        //SwpmLog::log_array_data_to_debug($wp_user_data, true);
        //SwpmLog::log_array_data_to_debug($userdata, true);

        //Update the SWPM user profile with the latest WP user data that we received via the 'profile_update' action hook.  
        $profile['user_name'] = $wp_user_data->user_login;
        $profile['email'] = $wp_user_data->user_email;
        $profile['first_name'] = $wp_user_data->user_firstname;
        $profile['last_name'] = $wp_user_data->user_lastname;
        $profile['password'] = $wp_user_data->user_pass;
        $wpdb->update($wpdb->prefix . "swpm_members_tbl", $profile, array('member_id' => $profile['member_id']));

        //===============/
        //TODO - Only reset SWPM auth cookies only since this is an update from WP end. WP will handle their cookie reset. 
        //NOTE: This hook will be triggered for both 
        //1) When admin is updating WP user profile from the WP user's menu. 
        //2) When user is updating their own profile from the WP user's profile update interface.
        //Otherwise any profile update from WP Admin by the site admin can cause logout. 
        //===============/
        //Since the encrypted/hashed password is getting updated with the one from WP User entry, the SWPM auth cookies need to be reset to keep the SWPM user logged in.     
        // $auth_object = SwpmAuth::get_instance();
        // $swpm_user_name = $profile['user_name'];
        // $user_info_params = array(
        //     'member_id' => $profile['member_id'],
        //     'user_name' => $swpm_user_name,
        //     'new_enc_password' => $profile['password'],
        // );
        // $auth_object->reset_swpm_auth_cookies_only($user_info_params);

        SwpmLog::log_simple_debug( 'Completed the profile_update hook handling - SWPM user profile (Member ID: '.$profile['member_id'].') updated with the latest WP user data.', true );
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
    
    /**
     * If any message/notice was set during the execution then this function will output that message.
     *
     * @return boolean
    */
    public function notices() {
        $message = SwpmTransfer::get_instance()->get('status');
        $succeeded = false;
        if (empty($message)) {
            return false;
        }

        $output = '';
        if ($message['succeeded']) {
            $output .= '<div class="notice notice-success is-dismissible">';
            $succeeded = true;
        } else {
            $output .= '<div class="notice notice-error is-dismissible">';
        }
        $output .= '<p><b>';
        $output .= $message['message'];
        $output .= '</b></p>';
        $extra = isset($message['extra']) ? $message['extra'] : array();
        if (is_string($extra)) {
            $output .= $extra;
        } else if (is_array($extra) && !empty($extra)) {
            $output .= '<ol style="margin-top: 0.5rem">';
            foreach ($extra as $key => $value) {
                $output .= '<li>' . $value . '</li>';
            }
            $output .= '</ol>';
        }
        $output .= '</div>';
        if (isset($message['pass_reset_sent'])) {
            $succeeded = true;
        }

        echo $output;
        return $succeeded;
    }

    /**
     * This function is hooked to WordPress's admin_notices action hook
     * It is used to show any plugin specific notices/warnings in the admin interface
     */
    public function do_admin_notices() {
        //Show any execution specific notices in the admin interface.
        $this->notices();
        //Show any other general warnings/notices to the admin.
        if (SwpmMiscUtils::is_swpm_admin_page()) {
            //we are in an admin page for SWPM plugin.

            $msg = '';
            //Show notice if running in sandbox mode.
            $settings = SwpmSettings::get_instance();
            $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
            if ($sandbox_enabled) {
                $msg .= '<p>' . __('You have the sandbox payment mode enabled in plugin settings. Make sure to turn off the sandbox mode when you want to do live transactions.', 'simple-membership') . '</p>';
            }

            if (!empty($msg)) {//Show warning messages if any.
                echo '<div id="message" class="notice notice-warning">';
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

		$settings = SwpmSettings::get_instance();

		$is_add_new_post_screen = get_current_screen()->action == 'add';
	    $default_membership_level = array();
	    $enable_default_content_protection = !empty( $settings->get_value('enable_default_content_protection', '') );
        if ( $is_add_new_post_screen && $enable_default_content_protection){
	        $is_protected = $settings->get_value('default_protect_this_content', false);
	        $default_membership_level = $settings->get_value('default_protection_membership_levels', array());
        }

		//Nonce input
        echo '<input type="hidden" name="swpm_post_protection_box_nonce" value="' . wp_create_nonce('swpm_post_protection_box_nonce_action') . '" />';

        // The actual fields for data entry
        echo '<h4>' . __("Do you want to protect this content?", 'simple-membership') . '</h4>';
        echo '<input type="radio" ' . ((!$is_protected) ? 'checked' : "") . '  name="swpm_protect_post" value="1" /> ' . __('No, Do not protect this content.', 'simple-membership') . '<br/>';
        echo '<input type="radio" ' . (($is_protected) ? 'checked' : "") . '  name="swpm_protect_post" value="2" /> ' . __('Yes, Protect this content.', 'simple-membership') . '<br/>';
        echo $protection_obj->get_last_message();

        echo '<h4>' . __("Select the membership level that can access this content:", 'simple-membership') . "</h4>";
        $query = "SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE  id !=1 ";
        $levels = $wpdb->get_results($query, ARRAY_A);
        foreach ($levels as $level) {
			$is_checked = SwpmPermission::get_instance( $level['id'] )->is_permitted($id);

			// Check if default protection membership level configured.
			if ($is_add_new_post_screen && in_array($level['id'], $default_membership_level)){
				$is_checked = true;
			}

            echo '<input type="checkbox" ' . ($is_checked ? "checked='checked'" : "") .
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
                    //Apply the post ID to the protection list and then save it in the database.
                    SwpmPermission::get_instance($level)->apply(array($post_id), $post_type)->save();
                } else {
                    SwpmPermission::get_instance($level)->remove(array($post_id), $post_type)->save();
                }
            }
        }
        
        //Return data.
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

    public function handle_wp_tasks() {
	    $wp_tasks = new SwpmWpTasks();
	    $wp_tasks->do_wp_tasks();

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
        wp_register_script('swpm-reg-form-validator', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm-reg-form-validator.js', null, SIMPLE_WP_MEMBERSHIP_VER, true);
        wp_register_script('swpm-profile-form-validator', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm-profile-form-validator.js', null, SIMPLE_WP_MEMBERSHIP_VER, true);

        //Stripe libraries
        wp_register_script("swpm.stripe", "https://js.stripe.com/v3/", array("jquery"), SIMPLE_WP_MEMBERSHIP_VER);
	wp_register_style("swpm.stripe.style", "https://checkout.stripe.com/v3/checkout/button.css", array(), SIMPLE_WP_MEMBERSHIP_VER);
    }

    public static function enqueue_validation_scripts_v2($handle, $params = array()){

        if ( ! wp_script_is( $handle, 'registered' ) ) {
            wp_register_script($handle, SIMPLE_WP_MEMBERSHIP_URL . "/js/".$handle.".js", null, SIMPLE_WP_MEMBERSHIP_VER, true);
        }

        $validation_messages = array(
            "username" => array(
                "required" => __("Username is required", "simple-membership"),
                "invalid" => __("Invalid username", "simple-membership"),
                "regex" => __("Usernames can only contain: letters, numbers and .-_@", "simple-membership"),
                "minLength" => __("Minimum 4 characters required", "simple-membership"),
                "exists" => __("Username already exists", "simple-membership"),
            ),
            "email" => array(
                "required" => __("Email is required", "simple-membership"),
                "invalid" => __("Invalid email", "simple-membership"),
                "exists" => __("Email already exists", "simple-membership"),
            ),
            "password" => array(
                "required" => __("Password is required", "simple-membership"),
                "invalid" => __("Invalid password", "simple-membership"),
                "regex" => __("Must contain a digit, an uppercase and a lowercase letter", "simple-membership"),
                "minLength" => __("Minimum 8 characters required", "simple-membership")
            ),
            "repass" => array(
                "required" => __("Retype password is required", "simple-membership"),
                "invalid" => __("Invalid password", "simple-membership"),
                "mismatch" => __("Password don't match", "simple-membership"),
                "minLength" => __("Minimum 8 characters required", "simple-membership")
            ),
            "firstname" => array(
                "required" => __("First name is required", "simple-membership"),
                "invalid" => __("Invalid name", "simple-membership")
            ),
            "lastname" => array(
                "required" => __("Last name is required", "simple-membership"),
                "invalid" => __("Invalid name", "simple-membership")
            ),
            "terms" => array(
                "required" => __("You must accept the terms & conditions", "simple-membership")
            ),
            "pp" => array(
                "required" => __("You must accept the privacy policy", "simple-membership")
            ),

	        // Membership Level related:
            "membershipLevelAlias" => array(
	            "required" => __("Membership level name is required", "simple-membership")
            ),
        );

        $ajax_url =  admin_url('admin-ajax.php');

        wp_add_inline_script($handle, "var swpmFormValidationAjax = ".wp_json_encode(array(
            'ajax_url' => $ajax_url,
            'query_args' => isset($params['query_args']) ? $params['query_args'] : array(),
        )), "before");
       
        wp_add_inline_script($handle, "var form_id = '".$params['form_id']."';", "before");

        if (isset($params['custom_pass_pattern_validator']) && !empty($params['custom_pass_pattern_validator'])) {
            wp_add_inline_script($handle, "var custom_pass_pattern_validator = ".$params['custom_pass_pattern_validator'].";", "before");
        }
        if (isset($params['custom_pass_pattern_validator_msg']) && !empty($params['custom_pass_pattern_validator_msg'])) {
            $validation_messages['password']['regex'] = $params['custom_pass_pattern_validator_msg'];
        }
        if (isset($params['custom_pass_min_length_validator']) && !empty($params['custom_pass_min_length_validator'])) {
            wp_add_inline_script($handle, "var custom_pass_min_length_validator = ".$params['custom_pass_min_length_validator'].";", "before");
        }
        if (isset($params['custom_pass_min_length_validator_msg']) && !empty($params['custom_pass_min_length_validator_msg'])) {
            $validation_messages['password']['minLength'] = $params['custom_pass_min_length_validator_msg'];
        }

        if (isset($params['is_terms_enabled'])) {
            wp_add_inline_script($handle, "var terms_enabled = ".$params['is_terms_enabled'].";", "before");
        }
        if (isset($params['is_pp_enabled'])) {
            wp_add_inline_script($handle, "var pp_enabled = ".$params['is_pp_enabled'].";", "before");
        }
        if (isset($params['is_strong_password_enabled'])) {
            wp_add_inline_script($handle, "var strong_password_enabled = ".$params['is_strong_password_enabled'].";", "before");
        }

        wp_localize_script($handle, "validationMsg", $validation_messages);

        wp_enqueue_script($handle);
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
                'alertText' => '* ' . SwpmUtils::_('Invalid Username').'<br>'.SwpmUtils::_('Usernames can only contain: letters, numbers and .-_@'),
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


    public function menu() {
        $menu_parent_slug = 'simple_wp_membership';

        add_menu_page(__("WP Membership", 'simple-membership'), __("WP Membership", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, $menu_parent_slug, array(&$this, "admin_members_menu"), 'dashicons-id');
        add_submenu_page($menu_parent_slug, __("Members", 'simple-membership'), __('Members', 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership', array(&$this, "admin_members_menu"));
        add_submenu_page($menu_parent_slug, __("Membership Levels", 'simple-membership'), __("Membership Levels", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_levels', array(&$this, "admin_membership_levels_menu"));
        add_submenu_page($menu_parent_slug, __("Settings", 'simple-membership'), __("Settings", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_settings', array(&$this, "admin_settings_menu"));
        add_submenu_page($menu_parent_slug, __("Payments", 'simple-membership'), __("Payments", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_payments', array(&$this, "admin_payments_menu"));
        add_submenu_page($menu_parent_slug, __("Tools", 'simple-membership'), __("Tools", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_tools', array(&$this, "admin_tools_menu"));
        add_submenu_page($menu_parent_slug, __("Reports", 'simple-membership'), __("Reports", 'simple-membership'), SWPM_MANAGEMENT_PERMISSION, 'simple_wp_membership_reports', array(&$this, "admin_reports_menu"));
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

	public function admin_tools_menu() {
		include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-tools-admin-menu.php');
		$tools_admin = new SwpmToolsAdminMenu();
		$tools_admin->handle_tools_admin_menu();
	}

	public function admin_reports_menu() {
		include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-reports-admin-menu.php');
		$tools_admin = new SwpmReportsAdminMenu();
		$tools_admin->handle_reports_admin_menu();
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
        //Schedule the cron job events.
        //We use the daily cronjob events for account status and expiry checks, delete pending accounts, prune events etc. 
        //The daily cron event is also used by the ENB extension.

        //TODO - Move the all the daily crons to the newly added 'swpm_daily_cron_event' hook.
        wp_schedule_event(time(), 'daily', 'swpm_account_status_event');
        wp_schedule_event(time(), 'daily', 'swpm_delete_pending_account_event');

        //New daily and twicedaily cron events.
        wp_schedule_event(time(), 'daily', 'swpm_daily_cron_event');
        wp_schedule_event(time(), 'twicedaily', 'swpm_twicedaily_cron_event');

        //Run the standard installer steps
        include_once('class.swpm-installation.php');
        SwpmInstallation::run_safe_installer();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('swpm_account_status_event');
        wp_clear_scheduled_hook('swpm_delete_pending_account_event');
        wp_clear_scheduled_hook('swpm_daily_cron_event');
        wp_clear_scheduled_hook('swpm_twicedaily_cron_event');
    }

}
