<?php

/*
 * Provides some helpful functions to deal with the transactions
 */

class SwpmTransactions {

	static function save_txn_record( $ipn_data, $items = array() ) {
		global $wpdb;

		$current_date = SwpmUtils::get_current_date_in_wp_zone();//date( 'Y-m-d' );
		$custom_var   = self::parse_custom_var( $ipn_data['custom'] );

		$txn_data                     = array();
		$txn_data['email']            = $ipn_data['payer_email'];
		$txn_data['first_name']       = $ipn_data['first_name'];
		$txn_data['last_name']        = $ipn_data['last_name'];
		$txn_data['ip_address']       = $ipn_data['ip'];
		$txn_data['member_id']        = isset ( $custom_var['swpm_id'] ) ? $custom_var['swpm_id'] : '';
		$txn_data['membership_level'] = isset ( $custom_var['subsc_ref'] ) ? $custom_var['subsc_ref'] : '';

		$txn_data['txn_date']       = $current_date;
		$txn_data['txn_id']         = $ipn_data['txn_id'];
		$txn_data['subscr_id']      = $ipn_data['subscr_id'];
		$txn_data['reference']      = isset( $custom_var['reference'] ) ? $custom_var['reference'] : '';
		$txn_data['payment_amount'] = $ipn_data['mc_gross'];
		$txn_data['gateway']        = $ipn_data['gateway'];
		$txn_data['status']         = $ipn_data['status'];

		$txn_data = array_filter( $txn_data );//Remove any null values.
		$wpdb->insert( $wpdb->prefix . 'swpm_payments_tbl', $txn_data );

		$db_row_id = $wpdb->insert_id;

		//let's also store transactions data in swpm_transactions CPT
		$post                = array();
		$post['post_title']  = '';
		$post['post_status'] = 'publish';
		$post['content']     = '';
		$post['post_type']   = 'swpm_transactions';

		$post_id = wp_insert_post( $post );

		update_post_meta( $post_id, 'db_row_id', $db_row_id );

		if ( isset( $ipn_data['payment_button_id'] ) ) {
			$txn_data['payment_button_id'] = $ipn_data['payment_button_id'];
		}

		if ( isset( $ipn_data['is_live'] ) ) {
			$txn_data['is_live'] = $ipn_data['is_live'];
		}

		foreach ( $txn_data as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		do_action( 'swpm_txn_record_saved', $txn_data, $db_row_id, $post_id );

	}

	static function parse_custom_var( $custom ) {
		$delimiter       = '&';
		$customvariables = array();

		$namevaluecombos = explode( $delimiter, $custom );
		foreach ( $namevaluecombos as $keyval_unparsed ) {
			$equalsignposition = strpos( $keyval_unparsed, '=' );
			if ( $equalsignposition === false ) {
				$customvariables[ $keyval_unparsed ] = '';
				continue;
			}
			$key                     = substr( $keyval_unparsed, 0, $equalsignposition );
			$value                   = substr( $keyval_unparsed, $equalsignposition + 1 );
			$customvariables[ $key ] = $value;
		}

		return $customvariables;
	}

        static function get_transaction_row_by_subscr_id ($subscr_id) {
                global $wpdb;
                $query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_payments_tbl WHERE subscr_id = %s", $subscr_id ), OBJECT );
                return $query_db;
        }
}
