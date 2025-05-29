<?php


class SwpmEventLogger {

	//At the moment we only use the following event types. In the future, we will add more.
	//Event Types: login_success.
	const EVENT_TYPE_LOGIN_SUCCESS = 'login_success';
	const EVENT_TYPE_LOGIN_FAILED = 'login_failed';
	const EVENT_TYPE_PROFILE_UPDATED = 'profile_updated';

	public function __construct() {
		add_action('wp_ajax_swpm_reset_login_events', array($this, 'reset_login_events') );
	}

	public function reset_login_events() {
		if (!isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'swpm_reset_login_events' ) ) {
			wp_send_json_error(array(
				'message' => __('Nonce verification failed!', 'simple-membership')
			));
		}

		global $wpdb;

		$table = $wpdb->prefix . 'swpm_events_tbl';

		$count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE event_type = %s', self::EVENT_TYPE_LOGIN_SUCCESS));
		if ($count == 0){
			wp_send_json_success(array(
				'message' => __( 'No log entries exists!', 'simple-membership' ),
			));
		}

		$delete = $wpdb->query($wpdb->prepare('DELETE FROM ' . $table . ' WHERE event_type = %s', self::EVENT_TYPE_LOGIN_SUCCESS));
		if ( ! $delete ) {
			wp_send_json_error( array(
				'message' => __( 'Error: log entries could not be deleted!', 'simple-membership' ),
			) );
		}

		wp_send_json_success(array(
			'message' => __( 'Event entries deleted successfully!', 'simple-membership' ),
		));
	}

	public static function track_login_event( $username ){
		$username = sanitize_text_field($username);
		$member = SwpmMemberUtils::get_user_by_user_name($username);
		$member_id = !empty($member) ? $member->member_id : '';
		$date_time = date('Y-m-d H:i:s');
		$ip = SwpmUtils::get_user_ip_address();
		$user_agent = serialize(self::get_parsed_user_agent());

		self::insert_event_to_db(self::EVENT_TYPE_LOGIN_SUCCESS, $member_id , $username, $date_time, $ip, $user_agent);
	}

	public static function insert_event_to_db( $event_type, $member_id, $username, $date_time, $ip ='', $user_agent='' ){
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
			'Aloha Browser' => 'AlohaBrowser',
			'Yandex Browser' => 'YaBrowser',
			'Microsoft Edge' => 'Edg',
			'Opera' => array('OPR', 'OPX', 'OPT'),
			'Vivaldi' => 'Vivaldi',
			'Firefox' => array('Firefox', 'FxiOS'),
			"Samsung Browser" => 'SamsungBrowser',
			'Chrome' => array('Chrome', 'CriOS'),
			'Internet Explorer' => 'MSIE',
			'DuckDuckGo' => 'Ddg',
			'Safari' => 'Safari',
		);

		$user_agent_data['browser'] = "Other";

		foreach ($browserArray as $k => $V) {
			if (is_array($V)){
				foreach ($V as $v){
					if (preg_match("/$v/", $agent)) {
						$user_agent_data['browser'] = $k;
						break 2;
					}
				}
			} else {
				if (preg_match("/$V/", $agent)) {
					$user_agent_data['browser'] = $k;
					break;
				}
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

	public static function prune_events_db_table($event_name, $cuttoff_date) {
		//Trigger a filter hook to allow other plugins to modify the cuttoff date.
		$cuttoff_date = apply_filters('swpm_prune_events_db_table_cuttoff_date', $cuttoff_date, $event_name);
		//Delete all the events that are older than the cuttoff date.
		global $wpdb;
		$table = $wpdb->prefix . 'swpm_events_tbl';
		$query = $wpdb->prepare("DELETE FROM " . $table . " WHERE event_type = %s AND event_date_time < %s", $event_name, $cuttoff_date);
		$wpdb->query($query);
	}

	public static function has_event_entries($event_type = '') {
		if(empty($event_type)){
			$event_type = self::EVENT_TYPE_LOGIN_SUCCESS;
		}
		$count = self::count_events($event_type);
		return $count > 0;
	}

	public static function count_events($event_type = '') {
		global $wpdb;
		$table = $wpdb->prefix . 'swpm_events_tbl';
		if( empty($event_type) ){
			$query = "SELECT COUNT(*) FROM " . $table;
			$count = $wpdb->get_var($query);
		} else {
			$query = "SELECT COUNT(*) FROM " . $table . " WHERE event_type = %s";
			$count = $wpdb->get_var($wpdb->prepare($query, $event_type));
		}
		return $count;
	}
}