<?php
/**
 * The paid registration link (security fix in 4.8.9): it is only shown in the browser that paid, and that browser only
 * gets the proof for a member created by its own checkout (never for an existing member found by the swpm_id or email).
 *
 * Note: the plugin saves the proof with setcookie(), which can't send a header once PHPUnit has printed output.
 * These tests set $_COOKIE directly, the way the browser sends the cookie back.
 */
class Test_Paid_Registration_Link extends WP_UnitTestCase {

	private $level_id;

	public function set_up() {
		parent::set_up();
		$this->level_id = SWPM_Test_Factory::create_level();
		$this->remove_payment_cookies();
	}

	public function tear_down() {
		$this->remove_payment_cookies();
		parent::tear_down();
	}

	//Only remove the payment proof cookies. Other cookies (example: the plugin's 'swpm_session', set at init) must stay.
	private function remove_payment_cookies() {
		unset( $_COOKIE['swpm_paid_reg_code'], $_COOKIE['swpm_pp_checkout_tokens'] );
	}

	private function checkout( $args = array(), $swpm_id = '' ) {
		$ipn_data = SWPM_Test_Factory::ipn_data( $args );
		swpm_handle_subsc_signup_stand_alone( $ipn_data, $this->level_id, $ipn_data['txn_id'], $swpm_id );
		return $ipn_data;
	}

	public function test_a_new_member_is_flagged_as_created_by_the_checkout() {
		$ipn_data = $this->checkout();

		$this->assertNotEmpty( $ipn_data['member_id'] );
		$this->assertTrue( ! empty( $ipn_data['new_member_created'] ), 'A new member must be flagged as created by this checkout.' );
		$member = SWPM_Test_Factory::get_member( $ipn_data['member_id'] );
		$this->assertSame( '', $member->user_name, 'The new member must still need to complete the registration.' );
		$this->assertNotEmpty( $member->reg_code );
	}

	public function test_an_existing_member_found_by_email_is_not_flagged() {
		$existing_id = SWPM_Test_Factory::create_member( array( 'email' => 'pending-victim@example.com', 'reg_code' => md5( 'victim' ), 'membership_level' => $this->level_id ) );

		$ipn_data = $this->checkout( array( 'payer_email' => 'pending-victim@example.com' ) );

		$this->assertSame( $existing_id, (int) $ipn_data['member_id'] );
		$this->assertTrue( empty( $ipn_data['new_member_created'] ), 'A checkout matched to an existing member (by email) must not get the browser proof.' );
	}

	public function test_an_existing_member_given_by_the_swpm_id_is_not_flagged() {
		$existing_id = SWPM_Test_Factory::create_member( array( 'reg_code' => md5( 'swpm-id-victim' ), 'membership_level' => $this->level_id ) );

		$ipn_data = $this->checkout( array( 'custom' => 'swpm_id=' . $existing_id ), $existing_id );

		$this->assertSame( $existing_id, (int) $ipn_data['member_id'] );
		$this->assertTrue( empty( $ipn_data['new_member_created'] ), 'A checkout for an existing member (by swpm_id) must not get the browser proof.' );
	}

	public function test_the_registration_code_cookie_finds_its_pending_member() {
		$ipn_data = $this->checkout();
		$member   = SWPM_Test_Factory::get_member( $ipn_data['member_id'] );

		$_COOKIE['swpm_paid_reg_code'] = $member->reg_code;

		$found = SwpmUtils::get_incomplete_paid_member_info_by_ip();
		$this->assertNotNull( $found );
		$this->assertSame( (int) $member->member_id, (int) $found->member_id );
	}

	public function test_no_cookie_and_a_spoofed_ip_header_find_nothing() {
		$ipn_data = $this->checkout( array( 'custom' => 'user_ip=203.0.113.20' ) );
		$this->assertNotEmpty( $ipn_data['member_id'] );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.20';
		$found                           = SwpmUtils::get_incomplete_paid_member_info_by_ip();
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		$this->assertNull( $found );
	}

	public function test_paypal_standard_token_finds_its_pending_member() {
		$ipn_data = $this->checkout();
		$token    = str_repeat( 'ab', 12 );
		SwpmUtils::save_paid_registration_browser_token( $ipn_data['member_id'], $token );

		$_COOKIE['swpm_pp_checkout_tokens'] = $token;

		$found = SwpmUtils::get_incomplete_paid_member_info_by_ip();
		$this->assertNotNull( $found );
		$this->assertSame( (int) $ipn_data['member_id'], (int) $found->member_id );
	}

	public function test_a_completed_checkout_does_not_hide_a_pending_one_of_the_same_browser() {
		$first  = $this->checkout();
		$second = $this->checkout();
		SwpmUtils::save_paid_registration_browser_token( $first['member_id'], str_repeat( 'a1', 12 ) );
		SwpmUtils::save_paid_registration_browser_token( $second['member_id'], str_repeat( 'b2', 12 ) );

		//The newer checkout completes the registration first.
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'swpm_members_tbl', array( 'user_name' => SWPM_Test_Factory::unique( 'done' ), 'reg_code' => '' ), array( 'member_id' => $second['member_id'] ) );

		$_COOKIE['swpm_pp_checkout_tokens'] = str_repeat( 'a1', 12 ) . '.' . str_repeat( 'b2', 12 );

		$found = SwpmUtils::get_incomplete_paid_member_info_by_ip();
		$this->assertNotNull( $found, 'The pending older checkout must still be found.' );
		$this->assertSame( (int) $first['member_id'], (int) $found->member_id );
	}
}
