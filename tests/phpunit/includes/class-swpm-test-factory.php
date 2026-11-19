<?php
/**
 * Creates test data for the Simple Membership tests: membership levels, members and transaction records.
 * Every value the plugin's code reads is set, because the tests treat PHP notices as errors.
 * The data is removed after each test (each test runs in a database transaction).
 */
class SWPM_Test_Factory {

	private static $counter = 0;

	/** A unique suffix for names, emails and IDs. */
	public static function unique( $prefix = '' ) {
		self::$counter++;
		return $prefix . self::$counter . wp_generate_password( 6, false );
	}

	/** Creates a membership level and returns its ID. */
	public static function create_level( $args = array() ) {
		global $wpdb;
		$data = wp_parse_args(
			$args,
			array(
				'alias'                      => 'Test Level ' . self::unique(),
				'role'                       => 'subscriber',
				'permissions'                => 0,
				'subscription_period'        => '0',
				'subscription_duration_type' => 0,
			)
		);
		$wpdb->insert( $wpdb->prefix . 'swpm_membership_tbl', $data );
		return (int) $wpdb->insert_id;
	}

	/** Sets a custom field of a membership level (example: 'role_after_expiry'). */
	public static function set_level_field( $level_id, $key, $value ) {
		SwpmMembershipLevelCustom::get_instance_by_id( $level_id )->set(
			array(
				'meta_key'     => $key,
				'meta_value'   => $value,
				'meta_context' => 'default',
			)
		);
	}

	/** Creates a member record and returns its member ID. */
	public static function create_member( $args = array() ) {
		global $wpdb;
		$data = wp_parse_args(
			$args,
			array(
				'user_name'             => '',
				'first_name'            => 'Test',
				'last_name'             => 'Member',
				'password'              => '',
				'member_since'          => gmdate( 'Y-m-d' ),
				'membership_level'      => 2,
				'account_state'         => 'active',
				'last_accessed_from_ip' => '',
				'email'                 => self::unique( 'member' ) . '@example.com',
				'reg_code'              => '',
				'subscr_id'             => '',
			)
		);
		$wpdb->insert( $wpdb->prefix . 'swpm_members_tbl', $data );
		return (int) $wpdb->insert_id;
	}

	/** Returns a member record (fresh from the database). */
	public static function get_member( $member_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = %d", $member_id ) );
	}

	/** The IPN data array for SwpmTransactions::save_txn_record() and swpm_handle_subsc_signup_stand_alone(), with every value they read. */
	public static function ipn_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'custom'          => '',
				'payer_email'     => self::unique( 'buyer' ) . '@example.com',
				'first_name'      => 'Test',
				'last_name'       => 'Buyer',
				'txn_id'          => self::unique( 'txn_' ),
				'subscr_id'       => '',
				'mc_gross'        => '10.00',
				'gateway'         => 'stripe',
				'status'          => 'Completed',
				'txn_type'        => '',
				'address_street'  => '',
				'address_city'    => '',
				'address_state'   => '',
				'address_zipcode' => '',
				'address_country' => '',
			)
		);
	}

	/** Saves a transaction record and returns its post ID (the swpm_transactions post). */
	public static function save_txn( $args = array() ) {
		$ipn_data = self::ipn_data( $args );
		SwpmTransactions::save_txn_record( $ipn_data );
		$post = SwpmTransactions::get_transaction_row_by_txn_id( $ipn_data['txn_id'] );
		return $post ? (int) $post->ID : 0;
	}

	/** Returns the transaction row object (post meta format) that the refund functions use. */
	public static function get_txn_row( $txn_id ) {
		return SwpmTransactions::get_transaction_row_by_txn_id( $txn_id, true );
	}
}
