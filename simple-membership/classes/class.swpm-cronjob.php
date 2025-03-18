<?php

/**
 * The cron job class that handles the cron job tasks.
 */
class SwpmCronJob {

    public function __construct() {
        //Daily cron job event(s)
        add_action('swpm_account_status_event', array(&$this, 'update_account_status'));
        add_action('swpm_account_status_event', array(&$this, 'clean_expired_session_tokens_of_members'));

        add_action('swpm_delete_pending_account_event', array(&$this, 'delete_pending_account'));
        add_action('swpm_delete_pending_account_event', array($this, 'delete_pending_email_activation_data'));

        //Our new daily and twicedaily cron job events
        add_action('swpm_daily_cron_event', array( &$this, 'handle_daily_cron_event' ) );
        add_action('swpm_twicedaily_cron_event', array( &$this, 'handle_twicedaily_cron_event' ) );
    }

    public function update_account_status() {
        global $wpdb;
        for ($counter = 0;; $counter += 100) {
            $query = $wpdb->prepare("SELECT member_id, membership_level, subscription_starts, account_state
                    FROM {$wpdb->prefix}swpm_members_tbl
                    WHERE membership_level NOT IN ( SELECT id FROM {$wpdb->prefix}swpm_membership_tbl
                    WHERE subscription_period = '' OR subscription_period = '0' )
                    LIMIT %d, 100", $counter);
            $results = $wpdb->get_results($query);
            if (empty($results)) {
                //No more records to process. Break out of the loop.
                break;
            }
            $expired = array();
            foreach ($results as $result) {
                $timestamp = SwpmUtils::get_expiration_timestamp($result);
                if ($timestamp < time() && $result->account_state == 'active') {
                    $expired[] = $result->member_id;
                }
            }
            if (count($expired) > 0) {
                $query = "UPDATE {$wpdb->prefix}swpm_members_tbl
                SET account_state='expired'  WHERE member_id IN (" . implode(',', $expired) . ")";
                $wpdb->query($query);
                
                //Trigger an action hook
                do_action('swpm_cronjob_account_status_updated_to_expired', $expired);
            }
        }
    }

    public function delete_pending_account() {
        global $wpdb;
        $interval = SwpmSettings::get_instance()->get_value('delete-pending-account');
        if (empty($interval)) {
            return;
        }
        for ($counter = 0;; $counter += 100) {
            $query = $wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}swpm_members_tbl WHERE account_state='pending' AND subscription_starts < DATE_SUB(NOW(), INTERVAL %d MONTH) LIMIT %d, 100", $interval, $counter);
            $results = $wpdb->get_results($query);
            if (empty($results)) {
                //No more records to process. Break out of the loop.
                break;
            }
            $to_delete = array();
            foreach ($results as $result) {
                $to_delete[] = $result->member_id;
            }
            if (count($to_delete) > 0) {
                SwpmLog::log_simple_debug("Auto deleting pending account.", true);
                $query = "DELETE FROM {$wpdb->prefix}swpm_members_tbl
                          WHERE member_id IN (" . implode(',', $to_delete) . ")";
                $wpdb->query($query);
            }
        }
    }

    public function delete_pending_email_activation_data() {
        //Delete pending email activation data after 1 day (24 hours).
        global $wpdb;
        $q = "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '%swpm_email_activation_data_usr_%'";
        $res = $wpdb->get_results($q);
        if (empty($res)) {
            return;
        }
        foreach ($res as $data) {
            $value = unserialize($data->option_value);
            $timestamp = isset($value['timestamp']) ? $value['timestamp'] : 0;
            $now = time();
            if ($now > $timestamp + (60 * 60 * 24) ) {
                delete_option($data->option_name);
            }
        }
    }

    public function clean_expired_session_tokens_of_members(){
	    // Clean expired session tokens of swpm members (the valid ones will be kept).
        SwpmLog::log_auth_debug('CRON JOB: Cleaning expired session tokens of swpm members.', true);

        global $wpdb;
        for ($counter = 0;; $counter += 100) {
            //Get 100 member records at a time to process.
            $query = $wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}swpm_members_tbl LIMIT %d, 100", $counter);
            $results = $wpdb->get_results($query);
            if (empty($results)) {
                //No more records to process. Break out of the loop.
                break;
            }

            foreach ($results as $result) {
                $member_id = $result->member_id;
                SwpmLimitActiveLogin::delete_expired_session_tokens($member_id);
            }
        }

    }

    public function handle_twicedaily_cron_event() {
        //Perform any cron job tasks that needs to be done twice daily.
        //At the moment, we don't have any tasks that needs to be done twice daily.
        //This is a placeholder for future use.
    }

	public function handle_daily_cron_event(){
		$auto_prune_login_events = SwpmSettings::get_instance()->get_value('auto_prune_login_events');
		if ( $auto_prune_login_events ){
            $prune_cuttoff_date = date("Y-m-d H:i:s", strtotime("- 1 day"));
			SwpmEventLogger::prune_events_db_table(SwpmEventLogger::EVENT_TYPE_LOGIN_SUCCESS, $prune_cuttoff_date);
		}
	}
}
