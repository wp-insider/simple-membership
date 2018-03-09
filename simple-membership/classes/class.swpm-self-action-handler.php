<?php

class SwpmSelfActionHandler {
    
    public function __construct() {
        //Register all the self action hooks the plugin needs to handle
        add_action('swpm_front_end_registration_complete_fb_user_data', array(&$this, 'after_registration_callback'));
        add_action('swpm_front_end_registration_complete_user_data', array(&$this, 'after_registration_callback'));

    }    
    
    public function after_registration_callback($user_data){
        return;
        
        //Handle auto login after registration if enabled
        //TODO - check if auto login enabled in the settings.
        SwpmLog::log_simple_debug("Auto login after registration is enabled in settings. Performing auto login for user: " . $user_data['user_name'], true);
        $login_page_url = SwpmSettings::get_instance()->get_value('login-page-url');
        $encoded_pass = base64_encode($user_data['plain_password']);
        $swpm_auto_login_nonce = wp_create_nonce('swpm-auto-login-nonce');
        $arr_params = array(
            'swpm_auto_login' => '1',
            'swpm_user_name' => urlencode($user_data['user_name']),
            'swpm_encoded_pw' => $encoded_pass,
            'swpm_auto_login_nonce' => $swpm_auto_login_nonce,
        );
        $redirect_page = add_query_arg($arr_params, $login_page_url);
        wp_redirect($redirect_page);         
        exit(0);
    }
    
}