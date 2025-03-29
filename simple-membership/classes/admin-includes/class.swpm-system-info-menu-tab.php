<?php

class SWPM_System_Info_Menu_Tab {

	public $settings;

	public function __construct() {
		$this->settings = SwpmSettings::get_instance();
	}

	/*
	* This function is used to render and handle the migration related tool menu tab.
	*/
	public function handle_system_info_menu_tab() {
		do_action( 'swpm_system_info_menu_tab_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'System Info Menu Tab' );

		ob_start();
		?>

        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( 'WordPress Environment', 'simple-membership' ); ?></label>
            </h3>
            <div class="inside">
                <table class="widefat striped " cellspacing="0" id="status">
                    <tbody>
					<?php foreach ( $this->get_wp_environment_data() as $info ) { ?>
                        <tr>
                            <td><?php esc_attr_e( $info['title'] ) ?>:</td>
                            <td>
                                <span
                                        class="dashicons dashicons-editor-help"
                                        tabindex="0"
                                        aria-label="<?php esc_attr_e( $info['description'] ) ?>"
                                        title="<?php esc_attr_e( $info['description'] ) ?>"
                                ></span>
                            </td>
                            <td><?php echo wp_kses_post( $info['value'] ) ?></td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( 'Server Environment', 'simple-membership' ); ?></label>
            </h3>
            <div class="inside">
                <table class="widefat striped " cellspacing="0" id="status">
                    <tbody>
					<?php foreach ( $this->get_server_environment_data() as $info ) { ?>
                        <tr>
                            <td><?php esc_attr_e( $info['title'] ) ?>:</td>
                            <td>
                                <span
                                        class="dashicons dashicons-editor-help"
                                        tabindex="0"
                                        aria-label="<?php esc_attr_e( $info['description'] ) ?>"
                                        title="<?php esc_attr_e( $info['description'] ) ?>"
                                ></span>
                            </td>
                            <td><?php echo wp_kses_post( $info['value'] ) ?></td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( 'Simple Membership Page', 'simple-membership' ); ?></label>
            </h3>
            <div class="inside">
                <table class="widefat striped " cellspacing="0" id="status">
                    <tbody>
					<?php foreach ( $this->get_swpm_data() as $info ) { ?>
                        <tr>
                            <td><?php esc_attr_e( $info['title'] ) ?>:</td>
                            <td>
                                <span
                                        class="dashicons dashicons-editor-help"
                                        tabindex="0"
                                        aria-label="<?php esc_attr_e( $info['description'] ) ?>"
                                        title="<?php esc_attr_e( $info['description'] ) ?>"
                                ></span>
                            </td>
                            <td><?php echo wp_kses_post( $info['value'] ) ?></td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( 'Cron Jobs', 'simple-membership' ); ?></label>
            </h3>
            <div class="inside">
                <table class="widefat striped " cellspacing="0" id="status">
                    <tbody>
					<?php foreach ( $this->get_cronjobs() as $info ) { ?>
                        <tr>
                            <td><?php esc_attr_e( $info['title'] ) ?>:</td>
                            <td><i><?php esc_attr_e( $info['cron'] ) ?></i></td>
                            <td><?php echo wp_kses_post( $info['value'] ) ?></td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

		<?php
		$output = ob_get_clean();

		$output = apply_filters( 'swpm_system_info_menu_tab_content', $output );

		echo $output;
	}

	public function convert_bool_to_symbol( $bool ) {
		if ( boolval( $bool ) ) {
			return '<span class="dashicons dashicons-yes" style="color: green"></span>';
		} else {
			return '<span>-</span>';
		}
	}

	public function get_wp_environment_data() {
		return array(
			array(
				'title'       => __( 'WordPress address (URL)', 'simple-membership' ),
				'value'       => esc_url( get_bloginfo( 'wpurl' ) ),
				'description' => __( 'The root URL of your site.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Site address (URL)', 'simple-membership' ),
				'value'       => esc_url( get_site_url() ),
				'description' => __( 'The homepage URL of your site.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Simple Membership version', 'simple-membership' ),
				'value'       => SIMPLE_WP_MEMBERSHIP_VER,
				'description' => __( 'The version of Simple Membership plugin installed on your site.', 'simple-membership' )
			),
			array(
				'title'       => __( 'WordPress version', 'simple-membership' ),
				'value'       => esc_attr( get_bloginfo( 'version' ) ),
				'description' => __( 'The version of WordPress installed on your site.', 'simple-membership' )
			),
			array(
				'title'       => __( 'WordPress multisite', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( is_multisite() ),
				'description' => __( 'Whether or not you have WordPress Multisite enabled.', 'simple-membership' )
			),
			array(
				'title'       => __( 'WordPress memory limit', 'simple-membership' ),
				'value'       => esc_attr( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : ini_get( 'memory_limit' ) ),
				'description' => __( 'The maximum amount of memory (RAM) that your site can use at one time.', 'simple-membership' )
			),
			array(
				'title'       => __( 'WordPress debug mode', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ),
				'description' => __( 'Displays whether WordPress debug is enabled or not.', 'simple-membership' )
			),
			array(
				'title'       => __( 'WordPress Crons', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( wp_next_scheduled( 'wp_version_check' ) ),
				//This checks if WordPress has scheduled its version check, which indicates WP-Cron is functional.
				'description' => __( 'Displays whether WordPress cronjobs ar running or not.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Language', 'simple-membership' ),
				'value'       => esc_attr( get_locale() ),
				'description' => __( 'The current language used by WordPress.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Translation Files', 'simple-membership' ),
				'value'       => $this->get_translation_files(),
				'description' => __( 'The list translation files loaded for Simple Membership plugin.', 'simple-membership' )
			),
		);
	}

	public function get_server_environment_data() {
		return array(
			array(
				'title'       => __( 'Server Info', 'simple-membership' ),
				'value'       => esc_attr( $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ),
				'description' => __( 'Information about the web server that is currently hosting your site.', 'simple-membership' )
			),
			array(
				'title'       => __( 'PHP Version', 'simple-membership' ),
				'value'       => PHP_VERSION,
				'description' => __( 'The version of PHP installed on your hosting server.', 'simple-membership' )
			),
			array(
				'title'       => __( 'PHP Max Execution Time', 'simple-membership' ),
				'value'       => esc_attr( ini_get( 'max_execution_time' ) ),
				'description' => __( 'The largest filesize that can be contained in one post.', 'simple-membership' )
			),
			array(
				'title'       => __( 'PHP Post Max Size', 'simple-membership' ),
				'value'       => esc_attr( ini_get( 'post_max_size' ) ),
				'description' => __( 'The largest filesize that can be contained in one post.', 'simple-membership' )
			),
			array(
				'title'       => __( 'PHP Max Input Vars', 'simple-membership' ),
				'value'       => esc_attr( ini_get( 'max_input_vars' ) ),
				'description' => __( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', 'simple-membership' )
			),
			array(
				'title'       => __( 'cURL Version', 'simple-membership' ),
				'value'       => esc_attr( function_exists( 'curl_version' ) ? curl_version()['version'] : 'Not Installed' ),
				'description' => __( 'The version of cURL installed on your server.', 'simple-membership' )
			),
			array(
				'title'       => __( 'SUHOSIN Installed', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( extension_loaded( 'suhosin' ) ),
				'description' => __( 'Suhosin is an advanced protection system for PHP installations. It was designed to protect your servers on the one hand against a number of well known problems in PHP applications and on the other hand against potential unknown vulnerabilities within these applications or the PHP core itself. If enabled on your server, Suhosin may need to be configured to increase its data submission limits.', 'simple-membership' )
			),
			array(
				'title'       => __( 'MySQL Version', 'simple-membership' ),
				'value'       => esc_attr( $GLOBALS['wpdb']->db_version() ),
				'description' => __( 'The version of MySQL installed on your hosting server.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Max Upload Size', 'simple-membership' ),
				'value'       => esc_attr( ini_get( 'upload_max_filesize' ) ),
				'description' => __( 'The largest filesize that can be uploaded to your WordPress installation.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Default Timezone', 'simple-membership' ),
				'value'       => esc_attr( date_default_timezone_get() ),
				'description' => __( 'The default timezone for your server.', 'simple-membership' )
			),
			array(
				'title'       => __( 'fsockopen/cURL', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( function_exists( 'fsockopen' ) || function_exists( 'curl_version' ) ),
				'description' => __( 'Payment gateways can use cURL to communicate with remote servers to authorize payments, other plugins may also use it when communicating with remote services.', 'simple-membership' )
			),
			array(
				'title'       => __( 'SoapClient', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( class_exists( 'SoapClient' ) ),
				'description' => __( 'Some webservices like shipping use SOAP to get information from remote servers, for example, live shipping quotes from FedEx require SOAP to be installed.', 'simple-membership' )
			),
			array(
				'title'       => __( 'DOMDocument', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( class_exists( 'DOMDocument' ) ),
				'description' => __( 'HTML/Multipart emails use DOMDocument to generate inline CSS in templates.', 'simple-membership' )
			),
			array(
				'title'       => __( 'GZip', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( function_exists( 'gzcompress' ) ),
				'description' => __( 'GZip (gzopen) is used to open the GEOIP database from MaxMind.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Multibyte String', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( extension_loaded( 'mbstring' ) ),
				'description' => __( 'Multibyte String (mbstring) is used to convert character encoding, like for emails or converting characters to lowercase.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Remote Post', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( wp_remote_post( 'https://www.google.com' ) ),
				'description' => __( 'PayPal uses this method of communicating when sending back transaction information.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Remote Get', 'simple-membership' ),
				'value'       => $this->convert_bool_to_symbol( wp_remote_get( 'https://www.google.com' ) ),
				'description' => __( 'WooCommerce plugins may use this method of communication when checking for plugin updates.', 'simple-membership' )
			),
		);
	}

	public function get_swpm_data() {
		return array(
			array(
				'title'       => __( 'Login', 'simple-membership' ),
				'value'       => $this->get_page_url( 'login-page-url' ),
				'description' => __( 'The URL of your login page.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Registration', 'simple-membership' ),
				'value'       => $this->get_page_url( 'registration-page-url' ),
				'description' => __( 'The URL of your registration page.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Join Us', 'simple-membership' ),
				'value'       => $this->get_page_url( 'join-us-page-url' ),
				'description' => __( 'The URL of you join us page.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Profile', 'simple-membership' ),
				'value'       => $this->get_page_url( 'profile-page-url' ),
				'description' => __( 'The URL of your profile page.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Password Page', 'simple-membership' ),
				'value'       => $this->get_page_url( 'profile-page-url' ),
				'description' => __( 'The URL of your password reset page.', 'simple-membership' )
			),
			array(
				'title'       => __( 'Thank You', 'simple-membership' ),
				'value'       => $this->get_page_url( 'thank-you-page-url' ),
				'description' => __( 'The URL of your thank you page.', 'simple-membership' )
			),
		);
	}

	public function get_page_url( $option_name ) {
		$url = $this->settings->get_value( $option_name );

		if ( ! empty( $url ) ) {
			return ! empty( $url ) ? '<span>' . $url . '</span>' : '';
		}

		return '<span style="color: darkred"><span class="dashicons dashicons-warning"></span> ' . __( 'Page Not Set!', 'simple-membership' ) . '</span>';
	}

	public function get_translation_files() {
        $filename = 'simple-membership-' . get_locale() . '.mo';
		$lang_dir_file  = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'languages/plugins/' . $filename; // Path to the wp-content languages folder
		$plugin_lang_dir_file  = SIMPLE_WP_MEMBERSHIP_PATH . 'languages/' . $filename; // Path to the plugins languages folder

        if ( file_exists($lang_dir_file) ){
	        $lang_dir_file = $this->convert_bool_to_symbol(true) . ' ' . $lang_dir_file;
        } else {
	        $lang_dir_file = '<span class="dashicons dashicons-no-alt" style="margin-right: 4px"></span>' . $lang_dir_file;
        }

        if ( file_exists($plugin_lang_dir_file) ){
	        $plugin_lang_dir_file = $this->convert_bool_to_symbol(true) . ' ' . $plugin_lang_dir_file;
        } else {
	        $plugin_lang_dir_file = '<span class="dashicons dashicons-no-alt" style="margin-right: 4px"></span>' . $plugin_lang_dir_file;
        }

		$lang_dir_file = str_replace(ABSPATH, '', $lang_dir_file);
        $plugin_lang_dir_file = str_replace(ABSPATH, '', $plugin_lang_dir_file);

        return $lang_dir_file . '<br>' . $plugin_lang_dir_file;
	}

	public function get_cronjobs() {
		return array(
			array(
				'title'       => __( 'Daily Cron', 'simple-membership' ),
				'cron'        => 'swpm_account_status_event',
				'value'       => $this->get_cron_job( 'swpm_account_status_event' ),
				'description' => '',
			),

            array(
				'title'       => __( 'Daily Cron', 'simple-membership' ),
				'cron'        => 'swpm_delete_pending_account_event',
				'value'       => $this->get_cron_job( 'swpm_delete_pending_account_event' ),
				'description' => '',
			),

            array(
				'title'       => __( 'Daily Cron', 'simple-membership' ),
				'cron'        => 'swpm_daily_cron_event',
				'value'       => $this->get_cron_job( 'swpm_daily_cron_event' ),
				'description' => '',
			),

			array(
				'title'       => __( 'Twice Daily Cron', 'simple-membership' ),
				'cron'        => 'swpm_twicedaily_cron_event',
				'value'       => $this->get_cron_job( 'swpm_twicedaily_cron_event' ),
				'description' => '',
			)
		);
	}

	public function get_cron_job( $name ) {
		$next_run = wp_next_scheduled( $name );

		if ( $next_run ) {
			return '<span style="color: green">' . $this->convert_bool_to_symbol( true ) . __( 'Next scheduled', 'simple-membership' ) . ': ' . date( 'Y-m-d H:i:s', $next_run ) . '<span>';
		} else {
			return '<span style="color: darkred">' . $this->convert_bool_to_symbol( false ) . __( 'Not scheduled!', 'simple-membership' ) . '<span>';
		}
	}
}