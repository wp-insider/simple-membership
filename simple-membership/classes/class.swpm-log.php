<?php

class SwpmLog {
	private static $log_file;
	private static $log_auth_file;

	public function __construct() {
		//Register the ajax action handler for resetting the log files.
		add_action('wp_ajax_swpm_reset_log_action', array('SwpmLog', 'handle_reset_log_action'));
	}

	//The log files are saved in the 'logs' folder of the plugin (it has an .htaccess file to block direct access).
	private static function get_log_dir() {
		return SIMPLE_WP_MEMBERSHIP_PATH . 'logs/';
	}

	//Restore missing protection files before creating or writing logs. Preserve existing files.
	private static function ensure_log_dir() {
		$log_dir = self::get_log_dir();
		if ( ! wp_mkdir_p( $log_dir ) ) {
			return false;
		}
		$protection_files = array(
			'.htaccess' => "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n",
			'index.html' => '',
		);
		foreach ( $protection_files as $name => $contents ) {
			$path = $log_dir . $name;
			if ( ! file_exists( $path ) && false === file_put_contents( $path, $contents ) ) {
				return false;
			}
		}
		return true;
	}

	private static function gen_log_file_names() {
		if ( ! empty( self::$log_file ) && ! empty( self::$log_auth_file ) ) {
			return;
		}
		$settings = SwpmSettings::get_instance();
		$suffix   = $settings->get_value( 'log-file-suffix' );
		//A long random suffix makes the log file names hard to guess. An old short suffix (from uniqid()) is replaced.
		if ( empty( $suffix ) || strlen( $suffix ) < 32 ) {
			$suffix = wp_generate_password( 32, false );
			$settings->set_value( 'log-file-suffix', $suffix );
			$settings->save();
		}
		self::$log_file      = "log-{$suffix}.txt";
		self::$log_auth_file = "log-auth-{$suffix}.txt";
	}

	/*
	* This function will output the debug log file to the browser.
	* $type = 'd' for the standard debug-log-file and 'a' for auth-log-file.
	*/
	public static function output_log( $type = 'd' ) {
		if ( 'd' !== $type && 'a' !== $type ) {
			//Invalid log file type.
			return;
		}
		self::gen_log_file_names();
		$log_file = ('d' === $type) ? self::$log_file : self::$log_auth_file;
        $log_file_full_path = self::get_log_dir() . $log_file;

		if ( ! file_exists( $log_file_full_path ) ) {
			//Log file does not exist so we will call the reset log function to create a blank log file.
			self::reset_swmp_log_files();
		}

		//Check if the log file now exists.
		if ( ! file_exists( $log_file_full_path ) ) {
			//Log file still does not exist so we will show an error message.
			$log_file_missing_msg = '<p>Could not find the log file. Reset the log file from the settings menu to regenerate it then try again. You can find the Reset Debug Log option in the Debug Settings section.</p>';
			$log_file_missing_msg .= '<p>If it still fails to open the log file after that, check if the logs directory of the plugin (' . self::get_log_dir() . ') is writeable on your server.</p>';
			wp_die( $log_file_missing_msg );
		}

        //Open the log file
		$fp = fopen( $log_file_full_path, 'rb' );
		if ( ! $fp ) {
			wp_die( 'Can\'t open the log file.' );
		}
		header( 'Content-Type: text/plain' );

		if ( function_exists( 'fpassthru' ) ) {
			fpassthru( $fp );
		} else {
			echo stream_get_contents( $fp );
		}

		die;
	}

	public static function log_simple_debug( $message, $success, $end = false ) {
		$settings = SwpmSettings::get_instance();
		$debug_enabled = $settings->get_value( 'enable-debug' );
		if ( empty( $debug_enabled ) ) {//Debug is not enabled
			return;
		}

		//Lets write to the log file
		self::gen_log_file_names();
		if ( ! self::ensure_log_dir() ) {
			return;
		}
		$debug_log_file_name = self::get_log_dir() . self::$log_file;

		// Timestamp
		$log_timestamp = SwpmUtils::get_current_timestamp_for_debug_log();
		$text = '[' . $log_timestamp . '] - ' . ( ( $success ) ? 'SUCCESS: ' : 'FAILURE: ' ) . $message . "\n";
		if ( $end ) {
			$text .= "\n------------------------------------------------------------------\n\n";
		}
		// Write to log
		$fp = fopen( $debug_log_file_name, 'a' );
		if ( $fp ) {//The log file may not be writeable. Skip this log entry in that case.
			fwrite( $fp, $text );
			fclose( $fp );  // close file
		}
	}

	public static function log_array_data_to_debug( $array_to_write, $success, $end = false ) {
		$settings = SwpmSettings::get_instance();
		$debug_enabled = $settings->get_value( 'enable-debug' );
		if ( empty( $debug_enabled ) ) {//Debug is not enabled
			return;
		}

		//Lets write to the log file
		self::gen_log_file_names();
		if ( ! self::ensure_log_dir() ) {
			return;
		}
		$debug_log_file_name = self::get_log_dir() . self::$log_file;

		// Timestamp
		$log_timestamp = SwpmUtils::get_current_timestamp_for_debug_log();
		$text = '[' . $log_timestamp . '] - ' . ( ( $success ) ? 'SUCCESS: ' : 'FAILURE: ' ) . "\n";
		ob_start();
		print_r( $array_to_write );
		$var = ob_get_contents();
		ob_end_clean();
		$text .= $var;

		if ( $end ) {
			$text .= "\n------------------------------------------------------------------\n\n";
		}
		// Write to log
		$fp = fopen( $debug_log_file_name, 'a' );
		if ( $fp ) {//The log file may not be writeable. Skip this log entry in that case.
			fwrite( $fp, $text );
			fclose( $fp );  // close file
		}
	}

	public static function log_auth_debug( $message, $success, $end = false ) {
		$settings = SwpmSettings::get_instance();
		$debug_enabled = $settings->get_value( 'enable-debug' );
		if ( empty( $debug_enabled ) ) {//Debug is not enabled
			return;
		}

		//Lets write to the log file
		self::gen_log_file_names();
		if ( ! self::ensure_log_dir() ) {
			return;
		}
		$debug_log_file_name = self::get_log_dir() . self::$log_auth_file;

		// Timestamp
		$log_timestamp = SwpmUtils::get_current_timestamp_for_debug_log();
		$text = '[' . $log_timestamp . '] - ' . ( ( $success ) ? 'SUCCESS: ' : 'FAILURE: ' ) . $message . "\n";
		if ( $end ) {
			$text .= "\n------------------------------------------------------------------\n\n";
		}
		// Write to log
		$fp = fopen( $debug_log_file_name, 'a' );
		if ( $fp ) {//The log file may not be writeable. Skip this log entry in that case.
			fwrite( $fp, $text );
			fclose( $fp );  // close file
		}
	}

	public static function log_auth_debug_array_data( $array_to_write, $success, $end = false ) {
		$settings = SwpmSettings::get_instance();
		$debug_enabled = $settings->get_value( 'enable-debug' );
		if ( empty( $debug_enabled ) ) {//Debug is not enabled
			return;
		}

		//Lets write to the log file
		self::gen_log_file_names();
		if ( ! self::ensure_log_dir() ) {
			return;
		}
		$debug_log_file_name = self::get_log_dir() . self::$log_auth_file;

		// Timestamp
		$log_timestamp = SwpmUtils::get_current_timestamp_for_debug_log();
		$text = '[' . $log_timestamp . '] - ' . ( ( $success ) ? 'SUCCESS: ' : 'FAILURE: ' ) . "\n";
		ob_start();
		print_r( $array_to_write );
		$var = ob_get_contents();
		ob_end_clean();
		$text .= $var;

		if ( $end ) {
			$text .= "\n------------------------------------------------------------------\n\n";
		}
		// Write to log
		$fp = fopen( $debug_log_file_name, 'a' );
		if ( $fp ) {//The log file may not be writeable. Skip this log entry in that case.
			fwrite( $fp, $text );
			fclose( $fp );  // close file
		}
	}

	public static function handle_reset_log_action(){
		if (!check_ajax_referer( 'swpm_reset_log_action', 'nonce', false )) {
			wp_send_json_error(array(
				'message' => __('Error! Nonce verification failed for reset log files action', 'simple-membership'),
			));
		}

		if ( SwpmLog::reset_swmp_log_files() ) {
			wp_send_json_success(array(
				'message' => __('Log files have been reset.', 'simple-membership'),
			));
		} else {
			wp_send_json_error(array(
				'message' => __("Failed to reset the log files. Ensure the server's file permission is correct.", "simple-membership"),
			));
		}
	}

	public static function reset_swmp_log_files() {
		$log_reset = true;
		self::gen_log_file_names();
		if ( ! self::ensure_log_dir() ) {
			return false;
		}
		$logfile_list = array(
			self::get_log_dir() . self::$log_file,
			self::get_log_dir() . self::$log_auth_file,
		);

		foreach ( $logfile_list as $logfile ) {
			if ( empty( $logfile ) ) {
				continue;
			}

			$log_timestamp = SwpmUtils::get_current_timestamp_for_debug_log();
			$text = '[' . $log_timestamp . '] - SUCCESS: Log file reset';
			$text .= "\n------------------------------------------------------------------\n\n";
			$fp = fopen( $logfile, 'w' );
			if ( $fp != false ) {
				@fwrite( $fp, $text );
				@fclose( $fp );
			} else {
				$log_reset = false;
			}
		}
		return $log_reset;
	}

}

//Initialize the log class (so the constructor runs and the ajax action handler is registered).
$swpm_log = new SwpmLog();
