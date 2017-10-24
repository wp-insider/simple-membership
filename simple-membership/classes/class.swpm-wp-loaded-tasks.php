<?php

class SwpmWpLoadedTasks {

    public function __construct() {
        
    }

    /* 
     * This is triggered after all plugins, themes and WP has loaded.
     * It is triggered after init, plugins_loaded etc.
     */
    public function do_wp_loaded_tasks() {
        $this->synchronise_swpm_logout_for_wp_users();
        
    }

    /* 
     * Logs out the user from the swpm session if they are logged out of the WP user session 
     */
    public function synchronise_swpm_logout_for_wp_users() {
        if (!is_user_logged_in()) {
            /* WP user is logged out. So logout the SWPM user (if applicable) */
            if (SwpmMemberUtils::is_member_logged_in()) {
                /* SWPM user is logged in the system. Log him out. */
                SwpmLog::log_auth_debug("synchronise_swpm_logout_for_wp_users() - WP user session is logged out for this user. So logging out of the swpm session also.", true);
                wp_logout();
            }
        }
    }

}
