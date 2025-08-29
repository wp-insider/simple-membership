<?php
/**
 * The installation class that handles the installation and upgrade tasks.
 */
class SwpmInstallation {

    /*
     * This function is capable of handing both single site or multi-site install and upgrade all in one.
     */
    static function run_safe_installer() {
        global $wpdb;

        //Do this if multi-site setup
        if (function_exists('is_multisite') && is_multisite()) {
            // check if it is a network activation - if so, run the activation function for each blog id
            if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
                $old_blog = $wpdb->blogid;
                // Get all blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    SwpmInstallation::installer();
                    SwpmInstallation::initdb();
                }
                switch_to_blog($old_blog);
                return;
            }
        }

        //Do this if single site standard install
        SwpmInstallation::installer();
        //Set default values for the settings in the DB.
        SwpmInstallation::initdb();
    }

    public static function installer() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        } else {
            $charset_collate = "DEFAULT CHARSET=utf8";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }

        //The members table
        $sql = "CREATE TABLE " . $wpdb->prefix . "swpm_members_tbl (
			member_id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			user_name varchar(255) NOT NULL,
			first_name varchar(64) DEFAULT '',
			last_name varchar(64) DEFAULT '',
			password varchar(255) NOT NULL,
			member_since date NOT NULL DEFAULT '0000-00-00',
			membership_level smallint(6) NOT NULL,
			more_membership_levels VARCHAR(100) DEFAULT NULL,
			account_state enum('active','inactive','activation_required','expired','pending','unsubscribed') DEFAULT 'pending',
			last_accessed datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			last_accessed_from_ip varchar(128) NOT NULL,
			email varchar(255) DEFAULT NULL,
			phone varchar(64) DEFAULT NULL,
			address_street varchar(255) DEFAULT NULL,
			address_city varchar(255) DEFAULT NULL,
			address_state varchar(255) DEFAULT NULL,
			address_zipcode varchar(255) DEFAULT NULL,
			home_page varchar(255) DEFAULT NULL,
			country varchar(255) DEFAULT NULL,
			gender enum('male','female','not specified') DEFAULT 'not specified',
			referrer varchar(255) DEFAULT NULL,
			extra_info text,
			reg_code varchar(255) DEFAULT NULL,
			subscription_starts date DEFAULT NULL,
			initial_membership_level smallint(6) DEFAULT NULL,
			txn_id varchar(255) DEFAULT '',
			subscr_id varchar(255) DEFAULT '',
			company_name varchar(255) DEFAULT '',
			notes text DEFAULT NULL,
			flags int(11) DEFAULT '0',
			profile_image varchar(255) DEFAULT ''
          )" . $charset_collate . ";";
        dbDelta($sql);

        //The members meta table
        $sql = "CREATE TABLE " . $wpdb->prefix . "swpm_members_meta_tbl (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            member_id bigint(20) unsigned NOT NULL DEFAULT '0',
            meta_key varchar(255) DEFAULT NULL,
            meta_value longtext,
            KEY member_id (member_id)
        )" . $charset_collate . ";";
        dbDelta($sql);

        //The membership level table
        $sql = "CREATE TABLE " . $wpdb->prefix . "swpm_membership_tbl (
			id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			alias varchar(127) NOT NULL,
			role varchar(255) NOT NULL DEFAULT 'subscriber',
			permissions tinyint(4) NOT NULL DEFAULT '0',
			subscription_period varchar(11) NOT NULL DEFAULT '-1',
                        subscription_duration_type tinyint NOT NULL default 0,
			subscription_unit   VARCHAR(20)        NULL,
			loginredirect_page  text NULL,
			category_list longtext,
			page_list longtext,
			post_list longtext,
			comment_list longtext,
			attachment_list longtext,
			custom_post_list longtext,
			disable_bookmark_list longtext,
			options longtext,
                        protect_older_posts  tinyint(1) NOT NULL DEFAULT '0',
			campaign_name varchar(255) NOT NULL DEFAULT ''
          )" . $charset_collate . " AUTO_INCREMENT=1 ;";
        dbDelta($sql);
        $sql = "SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id = 1";
        $results = $wpdb->get_row($sql);
        if (is_null($results)) {
            $sql = "INSERT INTO  " . $wpdb->prefix . "swpm_membership_tbl  (
			id ,
			alias ,
			role ,
			permissions ,
			subscription_period ,
			subscription_unit,
			loginredirect_page,
			category_list ,
			page_list ,
			post_list ,
			comment_list,
			disable_bookmark_list,
			options,
			campaign_name
			)VALUES (1 , 'Content Protection', 'administrator', '15', '0',NULL,NULL, NULL , NULL , NULL , NULL,NULL,NULL,'');";
            $wpdb->query($sql);
        }
        $sql = "UPDATE  " . $wpdb->prefix . "swpm_membership_tbl SET subscription_duration_type = 1 WHERE subscription_unit='days' AND subscription_duration_type = 0";
        $wpdb->query($sql);

        $sql = "UPDATE  " . $wpdb->prefix . "swpm_membership_tbl SET subscription_duration_type = 2 WHERE subscription_unit='weeks' AND subscription_duration_type = 0";
        $wpdb->query($sql);

        $sql = "UPDATE  " . $wpdb->prefix . "swpm_membership_tbl SET subscription_duration_type = 3 WHERE subscription_unit='months' AND subscription_duration_type = 0";
        $wpdb->query($sql);

        $sql = "UPDATE  " . $wpdb->prefix . "swpm_membership_tbl SET subscription_duration_type = 4 WHERE subscription_unit='years' AND subscription_duration_type = 0";
        $wpdb->query($sql);

        //The membership level meta table
        $sql = "CREATE TABLE " . $wpdb->prefix . "swpm_membership_meta_tbl (
                    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    level_id int(11) NOT NULL,
                    meta_key varchar(255) NOT NULL,
                    meta_label varchar(255) NULL,
                    meta_value text,
                    meta_type varchar(255) NOT NULL DEFAULT 'text',
                    meta_default text,
                    meta_context varchar(255) NOT NULL DEFAULT 'default',
                    KEY level_id (level_id)
        )" . $charset_collate . " AUTO_INCREMENT=1;";
        dbDelta($sql);

        //The Events table (For storing event_type such as: login, logout, profile_edited, registration etc.)
        $sql = "CREATE TABLE " . $wpdb->prefix . "swpm_events_tbl (
            event_id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            event_type varchar(255) DEFAULT NULL,
            event_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            member_id varchar(16) DEFAULT '',
            username varchar(255) DEFAULT '',
            ip_address varchar(128) DEFAULT '',
            user_agent mediumtext DEFAULT ''
        )" . $charset_collate . " AUTO_INCREMENT=1;";
        dbDelta($sql);

        //[Deprecated] TODO: Delete later. The payments table (Note: We now use the SWPM Transactions Custom Post Type)
        $sql = "CREATE TABLE " . $wpdb->prefix . "swpm_payments_tbl (
                    id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    email varchar(255) DEFAULT NULL,
                    first_name varchar(64) DEFAULT '',
                    last_name varchar(64) DEFAULT '',
                    member_id varchar(16) DEFAULT '',
                    membership_level varchar(64) DEFAULT '',
                    txn_date date NOT NULL DEFAULT '0000-00-00',
                    txn_id varchar(255) NOT NULL DEFAULT '',
                    subscr_id varchar(255) NOT NULL DEFAULT '',
                    reference varchar(255) NOT NULL DEFAULT '',
                    payment_amount varchar(32) NOT NULL DEFAULT '',
                    gateway varchar(32) DEFAULT '',
                    status varchar(255) DEFAULT '',
                    ip_address varchar(128) DEFAULT ''
                    )" . $charset_collate . ";";
        dbDelta($sql);

        //Save the current DB version
        update_option("swpm_db_version", SIMPLE_WP_MEMBERSHIP_DB_VER);
    }

    public static function initdb() {
        $settings = SwpmSettings::get_instance();

        $installed_version = $settings->get_value('swpm-active-version');

        //Set other default settings values
        $reg_prompt_email_subject = "Complete your registration";
        $reg_prompt_email_body = "Dear {first_name} {last_name}" .
                "\n\nThank you for joining us!" .
                "\n\nPlease complete your registration by visiting the following link:" .
                "\n\n{reg_link}" .
                "\n\nThank You";
        $reg_email_subject = "Your registration is complete";
        $reg_email_body = "Dear {first_name} {last_name}\n\n" .
                "Your registration is now complete!\n\n" .
                "Registration details:\n" .
                "Username: {user_name}\n" .
                "Password: {password}\n\n" .
                "Please login to the member area at the following URL:\n\n" .
                "{login_link}\n\n" .
                "Thank You";
        $reg_email_subject_admin = "Notification of New Member Registration";
        $reg_email_body_admin = "A new member has completed the registration.\n\n" .
                "Username: {user_name}\n" .
                "Email: {email}\n\n" .
                "Please login to the admin dashboard to view details of this user.\n\n" .
                "You can customize this email message from the Email Settings menu of the plugin.\n\n" .
                "Thank You";

        $upgrade_email_subject = "Account Upgrade Confirmation Email";
        $upgrade_email_body = "Dear {first_name} {last_name}" .
                "\n\nYour Account Has Been Upgraded." .
                "\n\nThank You";

	    $renew_email_subject = "Account Renewal Confirmation Email";
	    $renew_email_body = "Dear {first_name} {last_name}" .
	                          "\n\nYour Account Has Been Renewed." .
	                          "\n\nThank You";
		// Set default value if it is not set.
	    $renew_complete_mail_subject = $settings->get_value('renew-complete-mail-subject', false);
	    if ( $renew_complete_mail_subject === false ){
		    $settings->set_value('renew-complete-mail-subject', stripslashes($renew_email_subject));
	    }
	    $renew_complete_mail_body = $settings->get_value('renew-complete-mail-body', false);
	    if ( $renew_complete_mail_body === false ){
		    $settings->set_value('renew-complete-mail-body', stripslashes($renew_email_body));
	    }

        $reset_email_subject = get_bloginfo('name') . ": New Password";
        $reset_email_body = "Dear {first_name} {last_name}" .
                "\n\nHere is your new password:" .
                "\n\nUsername: {user_name}" .
                "\nPassword: {password}" .
                "\n\nYou can change the password from the edit profile section of the site (after you log into the site)" .
                "\n\nThank You";

        $status_change_email_subject = "Account Updated!";
        $status_change_email_body = "Dear {first_name} {last_name}," .
                "\n\nYour account status has been updated!" .
                " Please login to the member area at the following URL:" .
                "\n\n {login_link}" .
                "\n\nThank You";

        $bulk_activate_email_subject = "Account Activated!";
        $bulk_activate_email_body = "Hi," .
                "\n\nYour account has been activated!" .
                "\n\nYou can now login to the member area." .
                "\n\nThank You";

        $email_activation_mail_subject = "Action Required to Activate Your Account";
        $email_activation_mail_body = "Dear {first_name}" .
                "\n\nThank you for registering. To activate your account, please click on the following link (this will confirm your email address):" .
                "\n\n{activation_link}" .
                "\n\nThank You";


        $curr_email_act_mail_subj = $settings->get_value('email-activation-mail-subject', false);
        if ($curr_email_act_mail_subj === false) {
            $settings->set_value('email-activation-mail-subject', stripslashes($email_activation_mail_subject));
        }

        $curr_email_act_mail_body = $settings->get_value('email-activation-mail-body', false);
        if ($curr_email_act_mail_body === false) {
            $settings->set_value('email-activation-mail-body', stripslashes($email_activation_mail_body));
        }

	    $subscription_cancel_member_mail_subject = "Subscription payment agreement has been canceled or expired";
	    $subscription_cancel_member_mail_body = "Dear {first_name}" .
                "\n\nYour subscription payment agreement with the details below has been canceled or expired." .
                "\n\nMember ID: {member_id}" .
                "\nSubscription ID: {subscription_id}" .
                "\n\nThank You";

	    $curr_subscription_cancel_member_mail_subject = $settings->get_value('subscription-cancel-member-mail-subject', false);
	    if ($curr_subscription_cancel_member_mail_subject === false) {
		    $settings->set_value('subscription-cancel-member-mail-subject', stripslashes($subscription_cancel_member_mail_subject));
	    }

	    $curr_subscription_cancel_member_mail_body = $settings->get_value('subscription-cancel-member-mail-body', false);
	    if ($curr_subscription_cancel_member_mail_body === false) {
		    $settings->set_value('subscription-cancel-member-mail-body', stripslashes($subscription_cancel_member_mail_body));
	    }

	    $manual_approval_mail_subject = "Your account has been approved";
	    $manual_approval_mail_body = "Dear {first_name}" .
	                                "\n\nYour account has been reviewed and approved successfully." .
	                                "\n\nYou can now log in and start using your account." .
	                                "\n\nThank You";
	    $current_manual_approval_mail_subject = $settings->get_value( 'manual-account-approve-member-mail-subject', false );
	    if ( $current_manual_approval_mail_subject === false ) {
		    $settings->set_value( 'manual-account-approve-member-mail-subject', stripslashes( $manual_approval_mail_subject ) );
	    }

	    $current_manual_approval_mail_body = $settings->get_value( 'manual-account-approve-member-mail-body', false );
	    if ( $current_manual_approval_mail_body === false ) {
		    $settings->set_value( 'manual-account-approve-member-mail-body', stripslashes( $manual_approval_mail_body ) );
	    }

        //Check if the plugin is being installed for the first time
        if (empty($installed_version)) {
            //Do fresh install tasks
            //Create the mandatory pages (if they are not there)
            SwpmMiscUtils::create_mandatory_wp_pages();
            //End of page creation

            $example_from_address = 'hello@' . SwpmMiscUtils::get_home_url_without_http_and_www();
            $senders_email_address = get_bloginfo('name') . " <" . $example_from_address . ">";

            $settings->set_value('reg-complete-mail-subject', stripslashes($reg_email_subject))
                    ->set_value('reg-complete-mail-body', stripslashes($reg_email_body))
                    ->set_value('reg-prompt-complete-mail-subject', stripslashes($reg_prompt_email_subject))
                    ->set_value('reg-prompt-complete-mail-body', stripslashes($reg_prompt_email_body))
                    ->set_value('upgrade-complete-mail-subject', stripslashes($upgrade_email_subject))
                    ->set_value('upgrade-complete-mail-body', stripslashes($upgrade_email_body))
                    ->set_value('reset-mail-subject', stripslashes($reset_email_subject))
                    ->set_value('reset-mail-body', stripslashes($reset_email_body))
                    ->set_value('account-change-email-subject', stripslashes($status_change_email_subject))
                    ->set_value('account-change-email-body', stripslashes($status_change_email_body))
                    ->set_value('email-from', $senders_email_address);

            $settings->set_value('reg-complete-mail-subject-admin', stripslashes($reg_email_subject_admin));
            $settings->set_value('reg-complete-mail-body-admin', stripslashes($reg_email_body_admin));

            $settings->set_value('bulk-activate-notify-mail-subject', stripslashes($bulk_activate_email_subject));
            $settings->set_value('bulk-activate-notify-mail-body', stripslashes($bulk_activate_email_body));

            //Preparing it to be enabled by default in the future.
            //$settings->set_value("force-wp-user-sync", "checked='checked'");

            $settings->set_value("use-new-form-ui", "checked='checked'");

            $settings->set_value("max-failed-login-attempts", SwpmLimitFailedLoginAttempts::DEFAULT_MAX_FAILED_LOGIN_ATTEMPTS);
            $settings->set_value("failed-login-attempt-lockdown-time", SwpmLimitFailedLoginAttempts::DEFAULT_LOCKOUT_TIME_IN_MINUTES);
        }

        if (version_compare($installed_version, SIMPLE_WP_MEMBERSHIP_VER) == -1) {
            //Do upgrade tasks
        }

        //save everything.
        $settings->set_value('swpm-active-version', SIMPLE_WP_MEMBERSHIP_VER)->save(); 
        //Generate and save a swpm private key for this site
        $unique_id = uniqid('', true);
        add_option('swpm_private_key_one', $unique_id);
    }

}
