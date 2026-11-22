<?php
/**
 * The member ID of a payment record, and how the payments list (admin) shows the records.
 * The member ID comes from the custom field of the checkout (swpm_id), which the buyer's browser sends, so it can be changed.
 * The bad value is from a stored XSS security report: it was saved on the payment record and printed in the payments list.
 */
class Test_Payment_Member_Id extends WP_UnitTestCase {

	const BAD_MEMBER_ID = '0"><svg/onload=confirm(4893)>';

	private function payments_list_table() {
		require_once SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-payments-list-table.php';
		//The list table's constructor needs the admin screen. These tests only call methods that don't use it.
		return ( new ReflectionClass( 'SWPMPaymentsListTable' ) )->newInstanceWithoutConstructor();
	}

	public function test_payments_list_member_profile_link_does_not_print_a_bad_member_id() {
		$output = $this->payments_list_table()->column_member_profile( array( 'member_id' => self::BAD_MEMBER_ID, 'subscr_id' => '', 'txn_id' => 'txn_bad_id' ) );

		$this->assertStringNotContainsString( '<svg', $output );
		$this->assertStringNotContainsString( '0">', $output, 'The member ID closes the link attribute.' );
	}

	public function test_payments_list_member_profile_link_for_a_member() {
		$output = $this->payments_list_table()->column_member_profile( array( 'member_id' => '12', 'subscr_id' => '', 'txn_id' => 'txn_member' ) );

		$this->assertStringContainsString( 'member_id=12', $output );
		$this->assertStringContainsString( 'member_action=edit', $output );
		$this->assertStringContainsString( SwpmUtils::_( 'View Profile' ), $output );
	}

	public function test_payments_list_member_profile_link_for_a_member_found_by_the_subscription_id() {
		$member_id = SWPM_Test_Factory::create_member( array( 'subscr_id' => 'I-PHPUNIT-LINK' ) );

		$output = $this->payments_list_table()->column_member_profile( array( 'member_id' => '', 'subscr_id' => 'I-PHPUNIT-LINK', 'txn_id' => 'txn_link' ) );

		$this->assertStringContainsString( 'member_id=' . $member_id, $output );
	}

	public function test_payments_list_status_is_escaped() {
		$table = $this->payments_list_table();
		$bad   = '<img src=x onerror=alert(1)>';

		$this->assertStringNotContainsString( '<img', $table->column_status( array( 'status' => $bad, 'txn_type' => '', 'subscr_status' => '' ) ) );
		//An agreement record (the status text is printed in another branch).
		$this->assertStringNotContainsString( '<img', $table->column_status( array( 'status' => $bad, 'txn_type' => 'pp_std_subscription_new', 'subscr_status' => '' ) ) );
	}

	public function test_save_txn_record_does_not_save_a_bad_member_id_from_the_custom_field() {
		global $wpdb;
		$post_id = SWPM_Test_Factory::save_txn( array( 'custom' => 'subsc_ref=2&swpm_id=' . self::BAD_MEMBER_ID ) );
		$this->assertGreaterThan( 0, $post_id, 'The payment record was not saved.' );

		$saved = (string) get_post_meta( $post_id, 'member_id', true );
		$this->assertMatchesRegularExpression( '/^\d*$/', $saved, 'The payment record has a member ID that is not a number.' );

		$db_row_id = (int) get_post_meta( $post_id, 'db_row_id', true );
		$table_id  = (string) $wpdb->get_var( $wpdb->prepare( "SELECT member_id FROM {$wpdb->prefix}swpm_payments_tbl WHERE id = %d", $db_row_id ) );
		$this->assertMatchesRegularExpression( '/^\d*$/', $table_id, 'The payments table row has a member ID that is not a number.' );
	}

	public function test_save_txn_record_with_a_bad_member_id_uses_the_member_of_the_subscription() {
		$member_id = SWPM_Test_Factory::create_member( array( 'subscr_id' => 'I-PHPUNIT-BAD-ID' ) );

		$post_id = SWPM_Test_Factory::save_txn( array( 'custom' => 'subsc_ref=2&swpm_id=' . self::BAD_MEMBER_ID, 'subscr_id' => 'I-PHPUNIT-BAD-ID' ) );

		$this->assertSame( (string) $member_id, (string) get_post_meta( $post_id, 'member_id', true ) );
	}

	public function test_save_txn_record_keeps_a_valid_member_id() {
		$post_id = SWPM_Test_Factory::save_txn( array( 'custom' => 'subsc_ref=2&user_ip=203.0.113.10&swpm_id=12' ) );

		$this->assertSame( '12', (string) get_post_meta( $post_id, 'member_id', true ) );
	}
}
