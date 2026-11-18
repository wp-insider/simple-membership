<?php
/**
 * Basic checks that the plugin loads and installs correctly in WordPress.
 */
class Test_Plugin_Smoke extends WP_UnitTestCase {

	public function test_plugin_is_loaded() {
		$this->assertTrue( class_exists( 'SimpleWpMembership' ), 'The main plugin class is not loaded.' );
		$this->assertTrue( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ), 'The plugin version constant is not defined.' );
	}

	public function test_activation_creates_the_plugin_tables() {
		global $wpdb;
		$tables = array( 'swpm_members_tbl', 'swpm_members_meta_tbl', 'swpm_membership_tbl', 'swpm_membership_meta_tbl', 'swpm_events_tbl', 'swpm_payments_tbl' );
		foreach ( $tables as $table ) {
			$name = $wpdb->prefix . $table;
			$this->assertSame( $name, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $name ) ), "The table {$name} was not created." );
		}
	}

	public function test_activation_saves_the_db_version_and_default_settings() {
		$this->assertSame( SIMPLE_WP_MEMBERSHIP_DB_VER, get_option( 'swpm_db_version' ) );
		$this->assertNotEmpty( SwpmSettings::get_instance()->get_value( 'login-page-url' ), 'The login page URL setting is empty after activation.' );
	}

	public function test_main_shortcodes_are_registered() {
		$shortcodes = array( 'swpm_registration_form', 'swpm_login_form', 'swpm_profile_form', 'swpm_reset_form', 'swpm_payment_button', 'swpm_thank_you_page_registration' );
		foreach ( $shortcodes as $shortcode ) {
			$this->assertTrue( shortcode_exists( $shortcode ), "The [{$shortcode}] shortcode is not registered." );
		}
	}

	public function test_login_form_renders_for_a_visitor() {
		$output = do_shortcode( '[swpm_login_form]' );
		$this->assertStringContainsString( 'name="swpm_user_name"', $output );
		$this->assertStringContainsString( 'name="swpm_password"', $output );
	}

	public function test_registration_form_shortcode_runs() {
		$output = do_shortcode( '[swpm_registration_form]' );
		$this->assertIsString( $output );
		$this->assertNotEmpty( $output );
	}

	/**
	 * Security regression (fixed in 4.8.9): the thank you page must not show a pending member's paid registration link
	 * to a visitor whose IP address matches (a spoofed X-Forwarded-For header). The link is only shown in the browser that paid.
	 */
	public function test_thank_you_page_does_not_show_a_paid_registration_link_by_ip() {
		global $wpdb;
		$buyer_ip = '203.0.113.10';
		$wpdb->insert(
			$wpdb->prefix . 'swpm_members_tbl',
			array(
				'user_name'             => '',
				'email'                 => 'buyer@example.com',
				'membership_level'      => 2,
				'account_state'         => 'active',
				'reg_code'              => md5( 'test-pending-member' ),
				'last_accessed_from_ip' => $buyer_ip,
			)
		);
		$_SERVER['REMOTE_ADDR']          = $buyer_ip;
		$_SERVER['HTTP_X_FORWARDED_FOR'] = $buyer_ip;
		$_COOKIE                         = array();

		$output = do_shortcode( '[swpm_thank_you_page_registration]' );

		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$this->assertStringNotContainsString( 'member_id=', $output, 'The paid registration link was shown to a visitor found by IP address.' );
		$this->assertStringNotContainsString( md5( 'test-pending-member' ), $output );
	}
}
