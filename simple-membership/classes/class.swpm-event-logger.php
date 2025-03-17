<?php


class SwpmEventLogger {

	public function __construct() {
		add_action('wp_ajax_swpm_reset_login_event_logs', array($this, 'reset_login_event_logs') );
	}

	public function reset_login_event_logs() {
		if (!isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'swpm_reset_login_event_logs' ) ) {
			wp_send_json_error(array(
				'message' => __('Nonce verification failed!', 'simple-membership')
			));
		}

		global $wpdb;

		$table = $wpdb->prefix . 'swpm_events_tbl';
		$query = $wpdb->prepare('DELETE FROM ' . $table . ' WHERE event_type = %s', 'login_success');
		$delete = $wpdb->query($query);

		if ( ! $delete ) {
			wp_send_json_error( array(
				'message' => __( 'Error: log entries could not be deleted!', 'simple-membership' ),
			) );
		}

		wp_send_json_success(array(
			'message' => __( 'Log entries deleted successfully!', 'simple-membership' ),
		));
	}

	public static function log_login_event( $username ){
		$username = sanitize_text_field($username);
		$member = SwpmMemberUtils::get_user_by_user_name($username);
		$member_id = !empty($member) ? $member->member_id : '';
		$date_time = date('Y-m-d H:i:s');
		$ip = SwpmUtils::get_user_ip_address();
		$user_agent = serialize(self::get_parsed_user_agent());

		self::log('login_success', $member_id , $username, $date_time, $ip, $user_agent);
	}

	public static function log( $event_type, $member_id, $username, $date_time, $ip ='', $user_agent='' ){
		global $wpdb;

		$data = array(
			'event_type' => $event_type,
			'event_date_time' => $date_time,
			'member_id' => $member_id,
			'username' => $username,
			'ip_address' => $ip,
			'user_agent' => $user_agent
		);

		$wpdb->insert($wpdb->prefix . 'swpm_events_tbl', $data );
	}

	public static function get_parsed_user_agent() {
		$agent = $_SERVER['HTTP_USER_AGENT'];

		$user_agent_data = array(
			'browser' => '',
			'platform' => '',
		);

		if (empty($agent)){
			return $user_agent_data;
		}

		$browserArray = array(
			'Microsoft Edge' => 'Edg',
			'Opera' => '(OPR)|(OPX)',
			'Vivaldi' => 'Vivaldi',
			'Firefox' => 'Firefox',
			"Samsung Browser" => 'SamsungBrowser',
			'Chrome' => 'Chrome',
			'Internet Explorer' => 'MSIE',
			'Safari' => 'Safari'
		);

		$user_agent_data['browser'] = "Other";

		foreach ($browserArray as $k => $v) {
			if (preg_match("/$v/", $agent)) {
				$user_agent_data['browser'] = $k;
				break;
			}
		}

		$platformArray = array(
			"Windows Phone" => "(Windows Phone)|(Microsoft; Lumia)",
			"Android" => "(Linux; Android)|Android",
			"ChromeOS" => "(X11; CrOS)",
			"SymbianOS" => "SymbianOS",
			'Windows 98' => '(Win98)|(Windows 98)',
			'Windows 2000' => '(Windows 2000)|(Windows NT 5.0)',
			'Windows ME' => 'Windows ME',
			'Windows XP' => '(Windows XP)|(Windows NT 5.1)',
			'Windows Vista' => 'Windows NT 6.0',
			'Windows 8' => 'Windows NT 6.2',
			'Windows 8.1' => 'Windows NT 6.3',
			'Windows 7' => '(Windows NT 6.1)|(Windows NT 7.0)',
			'Windows' => 'Windows NT 10.0',
			'Linux' => '(X11)|(Linux)',
			'iOS' => '(Apple-iPhone)|(iPhone)|(iPhone OS)',
			'Mac OS' => '(Mac_PowerPC)|(Macintosh)|(Mac OS)'
		);
		$user_agent_data['platform'] = "Other";

		foreach ($platformArray as $k => $v) {
			if (preg_match("/$v/", $agent)) {
				$user_agent_data['platform'] = $k;
				break;
			}
		}

		return $user_agent_data;
	}

	public static function delete_login_event_older_than_one_year() {
		global $wpdb;

		$table = $wpdb->prefix . 'swpm_events_tbl';

		$query = "DELETE FROM " . $table . " WHERE event_type = 'login_success' AND event_date_time < NOW() - INTERVAL 1 YEAR";

		$wpdb->query($query);
	}
}