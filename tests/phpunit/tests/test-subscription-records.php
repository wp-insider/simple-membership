<?php
/**
 * Subscription agreement records. PayPal (PPCP and Standard) save the agreement record's status translated
 * (example: 'Abonnement erstellt'), so the plugin also recognizes it by its txn_type.
 */
class Test_Subscription_Records extends WP_UnitTestCase {

	public function test_agreement_record_with_a_translated_status_is_found_by_its_txn_type() {
		$agreement_id = SWPM_Test_Factory::save_txn(
			array( 'subscr_id' => 'I-TRANSLATED', 'status' => 'Abonnement erstellt', 'txn_type' => 'pp_subscription_new', 'gateway' => 'paypal_subscription_checkout' )
		);
		SWPM_Test_Factory::save_txn(
			array( 'subscr_id' => 'I-TRANSLATED', 'status' => 'Completed', 'txn_type' => 'pp_subscription_sale_completed_webhook', 'gateway' => 'paypal_subscription_checkout' )
		);

		$this->assertSame( $agreement_id, (int) SWPM_Utils_Subscriptions::get_subscription_agreement_cpt_id_by_subs_id( 'I-TRANSLATED' ) );
	}

	public function test_older_agreement_record_without_a_txn_type_is_found_by_its_english_status() {
		$agreement_id = SWPM_Test_Factory::save_txn( array( 'subscr_id' => 'sub_OLD', 'status' => 'subscription created', 'gateway' => 'stripe-sca-subs' ) );

		$this->assertSame( $agreement_id, (int) SWPM_Utils_Subscriptions::get_subscription_agreement_cpt_id_by_subs_id( 'sub_OLD' ) );
	}

	public function test_renewal_records_are_not_an_agreement_record() {
		SWPM_Test_Factory::save_txn( array( 'subscr_id' => 'sub_RENEWALS', 'status' => 'subscription', 'txn_type' => 'recurring_payment', 'gateway' => 'stripe-sca-subs' ) );

		$this->assertNull( SWPM_Utils_Subscriptions::get_subscription_agreement_cpt_id_by_subs_id( 'sub_RENEWALS' ) );
	}

	/**
	 * PayPal Standard: the first subscr_payment of a subscription is not a recurring payment, even when the agreement record's
	 * status is translated. Otherwise, a paid trial payment would be checked against the full price and rejected.
	 */
	public function test_first_paypal_standard_payment_is_not_recurring_when_the_agreement_status_is_translated() {
		SWPM_Test_Factory::save_txn(
			array( 'subscr_id' => 'S-PPSTD1', 'status' => 'Abonnement erstellt', 'txn_type' => 'pp_std_subscription_new', 'gateway' => 'paypal_std_sub_checkout' )
		);

		$this->assertFalse( swpm_is_paypal_recurring_payment( array( 'txn_type' => 'subscr_payment', 'item_number' => '1', 'subscr_id' => 'S-PPSTD1' ) ) );
	}

	public function test_a_paypal_standard_payment_after_an_earlier_payment_is_recurring() {
		SWPM_Test_Factory::save_txn(
			array( 'subscr_id' => 'S-PPSTD2', 'status' => 'Completed', 'txn_type' => 'subscr_payment', 'gateway' => 'paypal' )
		);

		$this->assertTrue( swpm_is_paypal_recurring_payment( array( 'txn_type' => 'subscr_payment', 'item_number' => '1', 'subscr_id' => 'S-PPSTD2' ) ) );
	}

	private function payments_list_table() {
		require_once SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-payments-list-table.php';
		//The list table's constructor needs the admin screen. These tests only call methods that don't use it.
		return ( new ReflectionClass( 'SWPMPaymentsListTable' ) )->newInstanceWithoutConstructor();
	}

	public function test_payments_list_shows_a_translated_agreement_status_with_its_color() {
		$table = $this->payments_list_table();

		$created = $table->column_status( array( 'status' => 'Abonnement erstellt', 'txn_type' => 'pp_subscription_new', 'subscr_status' => '' ) );
		$this->assertStringContainsString( 'swpm_status_subscription_created', $created );

		$canceled = $table->column_status( array( 'status' => 'Abonnement erstellt', 'txn_type' => 'pp_subscription_new', 'subscr_status' => 'canceled' ) );
		$this->assertStringContainsString( 'swpm_status_subscription_cancelled', $canceled );
	}

	public function test_payments_list_still_shows_the_other_statuses() {
		$table = $this->payments_list_table();

		$this->assertStringContainsString( 'swpm_status_completed', $table->column_status( array( 'status' => 'completed', 'txn_type' => '', 'subscr_status' => '' ) ) );
		$this->assertStringContainsString( 'swpm_status_refunded', $table->column_status( array( 'status' => 'Refunded', 'txn_type' => '', 'subscr_status' => '' ) ) );
		$this->assertStringContainsString( 'swpm_status_subscription', $table->column_status( array( 'status' => 'subscription', 'txn_type' => 'recurring_payment', 'subscr_status' => '' ) ) );
	}

	/** Deleting a payment record also deletes its row in the old payments table (the post meta is gone after the post is deleted). */
	public function test_deleting_a_payment_record_also_deletes_its_payments_table_row() {
		global $wpdb;
		$post_id   = SWPM_Test_Factory::save_txn( array( 'txn_id' => 'ch_delete' ) );
		$db_row_id = (int) get_post_meta( $post_id, 'db_row_id', true );
		$this->assertGreaterThan( 0, $db_row_id, 'The test record has no payments table row.' );

		$this->payments_list_table()->delete_record( $post_id );

		$this->assertNull( get_post( $post_id ), 'The payment record post was not deleted.' );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}swpm_payments_tbl WHERE id = %d", $db_row_id ) ), 'The payments table row was not deleted.' );
	}
}
