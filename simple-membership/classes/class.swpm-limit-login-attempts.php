<?php

class SwpmLimitFailedLoginAttempts {

	const ERROR_TYPE_FAILED_ATTEMPT = 'failed-attempt';

	const ERROR_TYPE_LOCKDOWN = 'lockdown';

	const DEFAULT_MAX_FAILED_LOGIN_ATTEMPTS = 3;

	const DEFAULT_LOCKOUT_TIME_IN_MINUTES = 3;

	/**
	 * @var null|WP_Error
	 */
	public $login_limit_error = null;

	public function __construct() {
		if (self::is_enabled()){
			add_filter( 'authenticate', array($this, 'handle_authenticate'), 30, 3 );
			add_filter( 'swpm_authenticate_failed', array($this, 'handle_swpm_authenticate_failed'), 10, 2 );
		}
	}

	/**
	 * A WP_User object is returned if the credentials authenticate a user.
	 * WP_Error or null otherwise.
	 *
	 * @param null|WP_User|WP_Error $user WP_User if the user is authenticated.
	 * @param string $username Username or email address entered into the login form.
	 * @param string $password User password.
	 */
	public function handle_authenticate($user, $username, $password) {
		if ( $user instanceof \WP_User ) {
			// Authentication is successful. No need to execute login limiter. Reset previous failed attempt data.
			self::reset_failed_login_events_for_current_visitor();
			return $user;
		}

		$error_codes = $user->get_error_codes();
		if (in_array('empty_username', $error_codes) && in_array( 'empty_password', $error_codes)){
			// Visitor just refreshed the login page, don't execute login limiter.
			return $user;
		}

		/**
		 * Code came here, that means ita a failed attempt.
		 */

		return $this->handle_login_limit_execution($user, $username);
	}

	/**
	 * Handle login limit for wp login form.
	 *
	 * @param WP_Error $wp_error WP Error object.
	 * @param string $username Username or email address entered into the login form.
	 */
	public function handle_login_limit_execution( $wp_error, $username) {
		$visitor_ip = SwpmUtils::get_user_ip_address();
		$allowed_failed_attempts = self::allowed_max_failed_attempts();
		$lockdown_time_period = self::lockdown_time_period();
		$failed_attempts_count = self::get_failed_attempts_for_current_visitor();

		// Calculate attempt count.
		$attempts_left = $allowed_failed_attempts - $failed_attempts_count;

		// Track current failed attempt if max allowed attempts count is not over
		if ($attempts_left > 0 ){
			$this->track_failed_login_event($username);
			$attempts_left--;
		}

		// Check if no more attempts left.
		if ( $attempts_left <= 0 ) {
			// The visitor is out of attempts.
			// Check if enough (lockdown) time has passed since he/she last tried.
			// If enough time passed, clear his failed attempts records, and give the visitor fresh set of chances to try.
			// If not, show the lockdown state message and don't increase failed attempt count.

			$last_attempt_time = date_create( $this->get_last_attempt_time() );

			$lockdown_end_time_for_this_visitor = $last_attempt_time->add( new DateInterval( "PT{$lockdown_time_period}M" ) );

			$current_time = date_create( date( 'Y-m-d G:i:s' ) );

			$interval_in_second = $current_time->getTimestamp() - $lockdown_end_time_for_this_visitor->getTimestamp();

			$is_lockdown_period_over = $interval_in_second > 0;

			// Check if lockdown period is over
			if ( $is_lockdown_period_over ) {
				// Reset failed attempts records
				self::reset_failed_login_events_for_current_visitor();

				// Track this current attempt info.
				$this->track_failed_login_event($username);

				// Assign the new attempt count.
				$attempts_left = $allowed_failed_attempts - 1;
			} else {
				SwpmLog::log_auth_debug( 'Failed login attempt limit reached for visitor IP address: ' . $visitor_ip, false );
				return new \WP_Error(
					'swpm_failed_login_attempt_error',
					sprintf(
						__( 'You have reached the maximum number of login attempts. Please try again after the lockout period. Lockout period: %s.', 'simple-membership' ),
						self::format_seconds_to_readable_timestamp( $interval_in_second )
					),
					array(
						'error_type' => self::ERROR_TYPE_LOCKDOWN
					)
				);
			}
		}

		// Show how many attempts are left for the visitor.
		SwpmLog::log_auth_debug( 'Failed login attempt. The visitor IP address: ' . $visitor_ip . ' has ' . $attempts_left . ' attempts left.', true );
		$wp_error->add(
			'swpm_failed_login_attempt_error',
			"<br>".sprintf(
				__('You have %d login attempt(s) remaining.', 'simple-membership'),
				$attempts_left
			),
			array(
				'error_type' => self::ERROR_TYPE_FAILED_ATTEMPT
			)
		);

		return $wp_error;
	}

	/**
	 * Handle login limit for the swpm login form.
	 */
	public function handle_swpm_authenticate_failed( $username, $wp_error_obj ) {
		// Skip if login attempt is not originated from swpm form.
		if (! isset($_REQUEST['swpm-login'])){
			return;
		}

		$this->login_limit_error = $this->handle_login_limit_execution($wp_error_obj, $username);

		add_filter('swpm_login_form_action_msg', array($this, 'handle_swpm_login_form_action_msg'));
	}

	public function handle_swpm_login_form_action_msg($existing_error_msg = '') {
		if (!is_wp_error($this->login_limit_error)){
			return $existing_error_msg;
		}

		$error_message = $this->login_limit_error->get_error_message('swpm_failed_login_attempt_error');
		$error_data = $this->login_limit_error->get_error_data('swpm_failed_login_attempt_error');

		$is_lockdown_error = false;
		if (is_array($error_data) && isset($error_data['error_type']) && $error_data['error_type'] == self::ERROR_TYPE_LOCKDOWN ){
			$is_lockdown_error = true;
		}

		$error_message = "<span class='swpm-red-error-text'>" . esc_attr(wp_strip_all_tags($error_message)) . "</span>";

		// Don't show credential error data if lockdown error occurred for safety.
		if ($is_lockdown_error || empty($existing_error_msg)){
			return $error_message;
		}

		// Append failed attempt error message data with existing error message.
		return $existing_error_msg . ' ' . $error_message;
	}

	/**
	 * Check if failed login limit enabled or not.
	 */
	public static function is_enabled() {
		return ! empty( SwpmSettings::get_instance()->get_value( 'enable-failed-login-attempt-limiter' ) );
	}

	/**
	 * Get the max allowed failed login attempts.
	 */
	public static function allowed_max_failed_attempts() {
		return intval( SwpmSettings::get_instance()->get_value( 'max-failed-login-attempts', self::DEFAULT_MAX_FAILED_LOGIN_ATTEMPTS ) );
	}

	/**
	 * Get the lockdown time for failed login attempts
	 */
	public static function lockdown_time_period() {
		return intval( SwpmSettings::get_instance()->get_value( 'failed-login-attempt-lockdown-time', self::DEFAULT_LOCKOUT_TIME_IN_MINUTES ) );
	}

	/**
	 * Get the number of failed attempts count for a visitor.
	 */
	public static function get_failed_attempts_for_current_visitor() {
		global $wpdb;

		$visitor_ip = SwpmUtils::get_user_ip_address();

		$table = $wpdb->prefix . 'swpm_events_tbl';
		$query = "SELECT COUNT(*) FROM " . $table . " WHERE event_type = %s AND ip_address = %s";
		$count = $wpdb->get_var($wpdb->prepare($query, SwpmEventLogger::EVENT_TYPE_LOGIN_FAILED, $visitor_ip));

		return $count;
	}

	public function track_failed_login_event($username = '') {
		SwpmEventLogger::insert_event_to_db(SwpmEventLogger::EVENT_TYPE_LOGIN_FAILED, null, $username, date('Y-m-d H:i:s'), SwpmUtils::get_user_ip_address());
	}

	/**
	 * Get the last login attempts timestamp.
	 */
	public function get_last_attempt_time(){
		global $wpdb;
		$table = $wpdb->prefix . 'swpm_events_tbl';
		$query = $wpdb->prepare(
			"SELECT event_date_time FROM $table WHERE event_type = %s AND ip_address = %s ORDER BY event_id DESC LIMIT 1",
			SwpmEventLogger::EVENT_TYPE_LOGIN_FAILED,
			SwpmUtils::get_user_ip_address()
		);
		$timestamp = $wpdb->get_var( $query );

		return $timestamp;
	}

	/**
	 * Clear failed login attempts records for a visitor.
	 */
	public static function reset_failed_login_events_for_current_visitor() {
		global $wpdb;

		$visitor_ip = SwpmUtils::get_user_ip_address();

		$table = $wpdb->prefix . 'swpm_events_tbl';

		$delete = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $table . ' WHERE event_type = %s AND ip_address = %s', SwpmEventLogger::EVENT_TYPE_LOGIN_FAILED, $visitor_ip ) );
		if ( ! $delete ) {
			SwpmLog::log_auth_debug( 'Failed login limit could not be reset for visitor ip: ' . $visitor_ip, false );
			return;
		}

		SwpmLog::log_auth_debug( 'Failed login limit has been reset for visitor IP address: ' . $visitor_ip, true );
	}

	/**
	 * Format second in to hour, minute, and seconds string.
	 *
	 * @param int $seconds
	 */
	public static function format_seconds_to_readable_timestamp($seconds) {
		$seconds = abs($seconds);
		$hours = floor($seconds / 3600);
		$minutes = floor(($seconds % 3600) / 60);
		$secs = $seconds % 60;

		$parts = [];
		if ($hours > 0) {
			$parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
		}
		if ($minutes > 0) {
			$parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
		}
		if ($secs > 0) {
			$parts[] = $secs . ' second' . ($secs > 1 ? 's' : '');
		}

		return implode(' ', $parts);
	}


}