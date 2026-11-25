<?php
/**
 * The registration code of a new paid member (the "code" of the paid registration link, saved in reg_code).
 * Security report (4.9.0): the code was md5(uniqid()). uniqid() is the server time in microseconds, so an attacker who
 * knows roughly when the payment was made can find the code by trying every value in that time window.
 */
class Test_Paid_Registration_Code extends WP_UnitTestCase {

	private $level_id;

	public function set_up() {
		parent::set_up();
		$this->level_id = SWPM_Test_Factory::create_level();
	}

	private function checkout() {
		$ipn_data = SWPM_Test_Factory::ipn_data();
		swpm_handle_subsc_signup_stand_alone( $ipn_data, $this->level_id, $ipn_data['txn_id'] );
		$this->assertNotEmpty( $ipn_data['member_id'], 'The checkout did not create a member.' );
		return (string) SWPM_Test_Factory::get_member( $ipn_data['member_id'] )->reg_code;
	}

	/** The attack from the report: try md5(uniqid()) for every microsecond around the payment time. */
	public function test_the_registration_code_cannot_be_found_from_the_payment_time() {
		$start = microtime( true );
		$code  = $this->checkout();
		$end   = microtime( true );

		//uniqid() is the seconds and the microseconds in hexadecimal ('%08x%05x'). A small margin covers the rounding.
		$from = (int) floor( ( $start - 0.005 ) * 1000000 );
		$to   = (int) ceil( ( $end + 0.005 ) * 1000000 );
		$found = false;
		for ( $time = $from; $time <= $to; $time++ ) {
			if ( md5( sprintf( '%08x%05x', intdiv( $time, 1000000 ), $time % 1000000 ) ) === $code ) {
				$found = true;
				break;
			}
		}
		$this->assertFalse( $found, 'The registration code is md5(uniqid()) of the payment time, so it can be found by trying the values around that time.' );
	}

	/** The format doesn't change (32 lowercase hexadecimal characters), so links and cookies keep working. */
	public function test_the_registration_code_is_32_hexadecimal_characters() {
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/', $this->checkout() );
	}

	public function test_each_new_member_gets_a_different_registration_code() {
		$this->assertNotSame( $this->checkout(), $this->checkout() );
	}
}
