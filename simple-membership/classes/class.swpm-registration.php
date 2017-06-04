<?php

/**
 * Description of BRegistration
 *
 * @author nur
 */
abstract class SwpmRegistration {

    protected $member_info = array();
    protected static $_intance = null;

    //public abstract static function get_instance();
    protected function send_reg_email() {
        global $wpdb;
        if (empty($this->member_info)) {
            return false;
        }
        $member_info = $this->member_info;
        $settings = SwpmSettings::get_instance();
        $subject = $settings->get_value('reg-complete-mail-subject');
        $body = $settings->get_value('reg-complete-mail-body');
        $from_address = $settings->get_value('email-from');
        $login_link = $settings->get_value('login-page-url');
        $headers = 'From: ' . $from_address . "\r\n";
        $member_info['membership_level_name'] = SwpmPermission::get_instance($member_info['membership_level'])->get('alias');
        $member_info['password'] = $member_info['plain_password'];
        $member_info['login_link'] = $login_link;
        $values = array_values($member_info);
        $keys = array_map('swpm_enclose_var', array_keys($member_info));
        $body = html_entity_decode($body);
        $body = str_replace($keys, $values, $body);
        
        $swpm_user = SwpmMemberUtils::get_user_by_user_name($member_info['user_name']);
        $member_id = $swpm_user->member_id;
        $body = SwpmMiscUtils::replace_dynamic_tags($body, $member_id);//Do the standard merge var replacement.
        
        $email = sanitize_email(filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW));
        
        $body = apply_filters('swpm_registration_complete_email_body', $body);//This filter can be used to modify the registration complete email body dynamically.
        
        //Send notification email to the member
        $subject=apply_filters('swpm_email_registration_complete_subject',$subject);
        $body=apply_filters('swpm_email_registration_complete_body',$body);
        wp_mail(trim($email), $subject, $body, $headers);
        SwpmLog::log_simple_debug('Member registration complete email sent to: '.$email.'. From email address value used: '.$from_address, true);
        
        if ($settings->get_value('enable-admin-notification-after-reg')) {
            //Send notification email to the site admin
            $admin_notification = $settings->get_value('admin-notification-email');
            $admin_notification = empty($admin_notification) ? $from_address : $admin_notification;
            $notify_emails_array = explode(",", $admin_notification);

            $headers = 'From: ' . $from_address . "\r\n";
            
            $admin_notify_subject = $settings->get_value('reg-complete-mail-subject-admin');
            if(empty($admin_notify_subject)){
                $admin_notify_subject = "Notification of New Member Registration";
            }
            
            $admin_notify_body = $settings->get_value('reg-complete-mail-body-admin');
            if(empty($admin_notify_body)){
                $admin_notify_body = "A new member has completed the registration.\n\n" .
                "Username: {user_name}\n" .
                "Email: {email}\n\n" .
                "Please login to the admin dashboard to view details of this user.\n\n" .
                "You can customize this email message from the Email Settings menu of the plugin.\n\n" .
                "Thank You";                        
            }
            $additional_args = array('password' => $member_info['plain_password']);
            $admin_notify_body = SwpmMiscUtils::replace_dynamic_tags($admin_notify_body, $member_id, $additional_args);//Do the standard merge var replacement.
            
            foreach ($notify_emails_array as $to_email){
                $to_email = trim($to_email);
                $admin_notify_subject=apply_filters('swpm_email_admin_notify_subject',$admin_notify_subject);
                $admin_notify_body=apply_filters('swpm_email_admin_notify_body',$admin_notify_body);
                wp_mail($to_email, $admin_notify_subject, $admin_notify_body, $headers);
                SwpmLog::log_simple_debug('Admin notification email sent to: '.$to_email, true);
            }
        }
        return true;
    }

}

function swpm_enclose_var($n) {
    return '{' . $n . '}';
}
