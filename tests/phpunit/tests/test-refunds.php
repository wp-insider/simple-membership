<?php
/**
 * Refund handling: swpm_save_payment_refund() (full, partial, repeated and late notifications) and the
 * checkout_txn_id passed to the swpm_payment_refunded action (Stripe first payment, PayPal PPCP first sale).
 */
class Test_Refunds extends WP_UnitTestCase {

	private function refund( $txn_id, $args = array() ) {
		$row = SWPM_Test_Factory::get_txn_row( $txn_id );
		return swpm_save_payment_refund(
			$row,
			wp_parse_args(
				$args,
				array(
					'gateway'        => 'stripe',
					'txn_id'         => $txn_id,
					'refund_id'      => SWPM_Test_Factory::unique( 're_' ),
					'refund_amount'  => 10,
					'total_refunded' => null,
					'currency'       => 'USD',
					'is_full_refund' => null,
				)
			)
		);
	}

	public function test_full_refund_marks_the_transaction_refunded() {
		$post_id = SWPM_Test_Factory::save_txn( array( 'txn_id' => 'ch_full', 'mc_gross' => '10.00' ) );

		$refund = $this->refund( 'ch_full', array( 'refund_amount' => 10 ) );

		$this->assertTrue( $refund['is_full_refund'] );
		$this->assertSame( 10.0, $refund['total_refunded'] );
		$this->assertSame( $post_id, $refund['txn_record_id'] );
		$this->assertSame( 'Refunded', get_post_meta( $post_id, 'status', true ) );
	}

	public function test_partial_refunds_add_up_to_a_full_refund() {
		$post_id = SWPM_Test_Factory::save_txn( array( 'txn_id' => 'ch_partial', 'mc_gross' => '50.00' ) );

		$first = $this->refund( 'ch_partial', array( 'refund_amount' => 20 ) );
		$this->assertFalse( $first['is_full_refund'] );
		$this->assertSame( 20.0, $first['total_refunded'] );
		$this->assertSame( 'Partially Refunded', get_post_meta( $post_id, 'status', true ) );

		$second = $this->refund( 'ch_partial', array( 'refund_amount' => 30 ) );
		$this->assertTrue( $second['is_full_refund'] );
		$this->assertSame( 50.0, $second['total_refunded'] );
		$this->assertSame( 'Refunded', get_post_meta( $post_id, 'status', true ) );
	}

	public function test_a_repeated_notification_is_not_counted_twice() {
		SWPM_Test_Factory::save_txn( array( 'txn_id' => 'ch_repeat', 'mc_gross' => '50.00' ) );

		$this->refund( 'ch_repeat', array( 'refund_id' => 're_same', 'refund_amount' => 20 ) );
		$again = $this->refund( 'ch_repeat', array( 'refund_id' => 're_same', 'refund_amount' => 20 ) );

		$this->assertTrue( ! empty( $again['repeat'] ), 'A repeated notification must be flagged.' );
		$this->assertSame( 20.0, $again['total_refunded'] );
	}

	public function test_a_late_partial_notification_does_not_undo_a_full_refund() {
		$post_id = SWPM_Test_Factory::save_txn( array( 'txn_id' => 'ch_late', 'mc_gross' => '30.00' ) );

		$this->refund( 'ch_late', array( 'refund_amount' => 30 ) );
		$late = $this->refund( 'ch_late', array( 'refund_amount' => 10, 'is_full_refund' => false ) );

		$this->assertSame( 'Refunded', get_post_meta( $post_id, 'status', true ) );
		$this->assertTrue( $late['is_full_refund'] );
	}

	public function test_a_refund_without_a_transaction_record_saves_nothing() {
		$refund = swpm_save_payment_refund(
			false,
			array(
				'gateway'       => 'stripe',
				'txn_id'        => 'ch_unknown',
				'refund_id'     => 're_unknown',
				'refund_amount' => 5,
			)
		);

		$this->assertSame( 0, $refund['txn_record_id'] );
		$this->assertSame( 5.0, $refund['total_refunded'] );
		$this->assertTrue( $refund['is_full_refund'] );
		$this->assertSame( '', $refund['member_id'] );
	}

	public function test_stripe_first_payment_refund_passes_the_checkout_txn_id() {
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'cus_A', 'subscr_id' => 'sub_A', 'status' => 'subscription created', 'txn_type' => 'stripe_subscription_new', 'gateway' => 'stripe-sca-subs' )
		);
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'ch_A1', 'subscr_id' => 'sub_A', 'status' => 'subscription', 'txn_type' => 'stripe_subscription_first_payment', 'gateway' => 'stripe-sca-subs' )
		);
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'ch_A2', 'subscr_id' => 'sub_A', 'status' => 'subscription', 'txn_type' => 'recurring_payment', 'gateway' => 'stripe-sca-subs' )
		);

		$this->assertSame( 'cus_A', $this->refund( 'ch_A1' )['checkout_txn_id'], 'The first payment refund must pass the checkout transaction ID.' );
		$this->assertSame( '', $this->refund( 'ch_A2' )['checkout_txn_id'], 'A renewal refund must not pass the checkout transaction ID.' );
	}

	public function test_ppcp_first_sale_refund_passes_the_order_id() {
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'ORDER-1', 'subscr_id' => 'I-SUB1', 'status' => 'subscription created', 'txn_type' => 'pp_subscription_new', 'gateway' => 'paypal_subscription_checkout', 'is_trial_txn' => 'no' )
		);
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'SALE-1', 'subscr_id' => 'I-SUB1', 'txn_type' => 'pp_subscription_sale_completed_webhook', 'gateway' => 'paypal_subscription_checkout' )
		);
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'SALE-2', 'subscr_id' => 'I-SUB1', 'txn_type' => 'pp_subscription_sale_completed_webhook', 'gateway' => 'paypal_subscription_checkout' )
		);

		$this->assertSame( 'ORDER-1', $this->refund( 'SALE-1', array( 'gateway' => 'paypal' ) )['checkout_txn_id'], 'The first sale refund must pass the order ID.' );
		$this->assertSame( '', $this->refund( 'SALE-2', array( 'gateway' => 'paypal' ) )['checkout_txn_id'], 'A renewal refund must not pass the order ID.' );
	}

	public function test_ppcp_first_sale_after_a_free_trial_does_not_pass_the_order_id() {
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'ORDER-2', 'subscr_id' => 'I-SUB2', 'status' => 'subscription created', 'txn_type' => 'pp_subscription_new', 'gateway' => 'paypal_subscription_checkout', 'is_trial_txn' => 'yes', 'trial_amount' => '0' )
		);
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'SALE-3', 'subscr_id' => 'I-SUB2', 'txn_type' => 'pp_subscription_sale_completed_webhook', 'gateway' => 'paypal_subscription_checkout' )
		);

		$this->assertSame( '', $this->refund( 'SALE-3', array( 'gateway' => 'paypal' ) )['checkout_txn_id'] );
	}

	public function test_ppcp_first_sale_saved_more_than_a_day_after_the_checkout_does_not_pass_the_order_id() {
		global $wpdb;
		SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'ORDER-3', 'subscr_id' => 'I-SUB3', 'status' => 'subscription created', 'txn_type' => 'pp_subscription_new', 'gateway' => 'paypal_subscription_checkout' )
		);
		$sale_post_id = SWPM_Test_Factory::save_txn(
			array( 'txn_id' => 'SALE-4', 'subscr_id' => 'I-SUB3', 'txn_type' => 'pp_subscription_sale_completed_webhook', 'gateway' => 'paypal_subscription_checkout' )
		);
		$wpdb->update( $wpdb->posts, array( 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() + 2 * DAY_IN_SECONDS ) ), array( 'ID' => $sale_post_id ) );
		clean_post_cache( $sale_post_id );

		$this->assertSame( '', $this->refund( 'SALE-4', array( 'gateway' => 'paypal' ) )['checkout_txn_id'] );
	}
}
