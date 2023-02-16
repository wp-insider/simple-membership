<?php

/*
 * Provides some helpful functions to deal with the transactions
 */

class SwpmTransactions {

	public static function save_txn_record( $ipn_data, $items = array() ) {
		global $wpdb;

		$current_date = SwpmUtils::get_current_date_in_wp_zone();//date( 'Y-m-d' );
		$custom_var = self::parse_custom_var( $ipn_data['custom'] );

		//Get the IP address (if available)
		$ip_address = isset($ipn_data['ip']) ? $ipn_data['ip'] : '';
		if( empty($ip_address) ){
			$ip_address = isset($custom_var['user_ip']) ? $custom_var['user_ip'] : '';
		}

		//Subscription ID
		$subscr_id = $ipn_data['subscr_id'];

		$txn_data                     = array();
		$txn_data['email']            = $ipn_data['payer_email'];
		$txn_data['first_name']       = $ipn_data['first_name'];
		$txn_data['last_name']        = $ipn_data['last_name'];
		$txn_data['ip_address']       = $ip_address;
		$txn_data['member_id']        = isset ( $custom_var['swpm_id'] ) ? $custom_var['swpm_id'] : '';
		$txn_data['membership_level'] = isset ( $custom_var['subsc_ref'] ) ? $custom_var['subsc_ref'] : '';

		$txn_data['txn_date']       = $current_date;
		$txn_data['txn_id']         = $ipn_data['txn_id'];
		$txn_data['subscr_id']      = $ipn_data['subscr_id'];
		$txn_data['reference']      = isset( $custom_var['reference'] ) ? $custom_var['reference'] : '';
		$txn_data['payment_amount'] = $ipn_data['mc_gross'];
		$txn_data['gateway']        = isset($ipn_data['gateway']) ? $ipn_data['gateway'] : '';
		$txn_data['status']         = isset($ipn_data['status']) ? $ipn_data['status'] : '';

		//Check that a transaction ID exists before saving the transaction record.
		if ( empty( $txn_data['txn_id'] ) ) {
			SwpmLog::log_simple_debug( 'Transaction ID is empty. This transaction record cannot be saved.', false );
			return;
		}

		$txn_data = array_filter( $txn_data );//Remove any null values.
		$wpdb->insert( $wpdb->prefix . 'swpm_payments_tbl', $txn_data );

		$db_row_id = $wpdb->insert_id;

        /*** Save to the swpm_transactions CPT also ***/
		//Let's also store the transactions data in swpm_transactions CPT.
		$post = array();
		$post['post_title']  = '';
		$post['post_status'] = 'publish';
		$post['content'] = '';
		$post['post_type'] = 'swpm_transactions';

		$post_id = wp_insert_post( $post );

		//The key that connects the 'swpm_transactions' CPT post and the the swpm_payments_tbl row.
		update_post_meta( $post_id, 'db_row_id', $db_row_id );

		//Check if this is a paypal subscription checkout.
		if ( isset( $ipn_data['gateway']) && $ipn_data['gateway'] == 'paypal_subscription_checkout' ) {
			//Save the swpm_transactions CPT post ID of the original checkout in the member's proifle. Useful to retreive some of the original checkout txn data (example: custom_field data).
			$member_record = SwpmMemberUtils::get_user_by_subsriber_id( $subscr_id );
			if( ! $member_record ){
				SwpmLog::log_simple_debug( 'Error! Could not find an existing member record for the given subscriber ID: ' . $subscr_id . '. This member profile may have been deleted.', false );
			} else {
				$member_id = $member_record->member_id;
				$extra_info = SwpmMemberUtils::get_account_extra_info( $member_id );
				$extra_info['orig_swpm_txn_cpt_id'] = $post_id;
				SwpmMemberUtils::update_account_extra_info( $member_id, $extra_info );
			}
		}

		//Save the subscr_id to the swpm_transactions CPT as post meta (so it can be used to query the CPT for a specific subscription).
		if ( isset( $subscr_id ) && ! empty( $subscr_id ) ) {
			update_post_meta( $post_id, 'subscr_id', $subscr_id );
		}		

                //Add the payment_button_id to the txn_data array so it can be saved to the swpm_transactions CPT.
		if ( isset( $ipn_data['payment_button_id'] ) ) {
			$txn_data['payment_button_id'] = $ipn_data['payment_button_id'];
		}

                //Add the is_live to the txn_data array so it can be saved to the swpm_transactions CPT.
		if ( isset( $ipn_data['is_live'] ) ) {
			$txn_data['is_live'] = $ipn_data['is_live'];
		}

                //Add the custom value to the txn_data array so it can be saved to the swpm_transactions CPT.
		if ( isset( $ipn_data['custom'] ) ) {
			$txn_data['custom'] = $ipn_data['custom'];
		}

                //Save the $txn_data to the swpm_transactions CPT as post meta.
		foreach ( $txn_data as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

                //Trigger the action hook.
		do_action( 'swpm_txn_record_saved', $txn_data, $db_row_id, $post_id );

	}

	/*
	 * Use this function to update or set account status of a member easily.
	 */
	public static function update_transaction_status( $txn_row_id, $new_status = 'Completed' ) {
		global $wpdb;
		$payments_table_name = $wpdb->prefix . 'swpm_payments_tbl';

		SwpmLog::log_simple_debug( 'Updating the payment status value of transaction to: ' . $new_status . '. Row ID: ' . $txn_row_id, true );
		$query = $wpdb->prepare( "UPDATE $payments_table_name SET status=%s WHERE id=%s", $new_status, $txn_row_id );
		$resultset = $wpdb->query( $query );
	}

	public static function parse_custom_var( $custom ) {
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

	public static function get_transaction_row_by_subscr_id ($subscr_id) {
			global $wpdb;
			$query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_payments_tbl WHERE subscr_id = %s", $subscr_id ), OBJECT );
			return $query_db;
	}

	public static function get_transaction_row_by_txn_id ($txn_id) {
		global $wpdb;
		$query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_payments_tbl WHERE txn_id = %s", $txn_id ), OBJECT );
		return $query_db;
	}

		/**
		 * Get the custom field data of the original subscription checkout from the user profile (if available).
		 * The Transaction CPT Post ID is saved in the member's profile when the subscription is created.
		 * @param string $subscr_id
		 * @return string
		 */
		public static function get_original_custom_value_from_transactions_cpt ( $subscr_id ) {
			$extra_info = SwpmMemberUtils::get_account_extra_info_by_subscr_id( $subscr_id );
			if( isset( $extra_info['orig_swpm_txn_cpt_id'] ) && !empty( $extra_info['orig_swpm_txn_cpt_id'] )){
				$txn_cpt_post_id = $extra_info['orig_swpm_txn_cpt_id'];
				//Example value: subsc_ref=2&orig_swpm_txn_cpt_id=123
				$custom = get_post_meta( $txn_cpt_post_id, 'custom', true );
				SwpmLog::log_simple_debug('Custom field data from the original subscription checkout: ' . $custom, true);
			}
			else{
				$custom = '';
				SwpmLog::log_simple_debug('Could not find the original subscription checkout custom field data.', true);
			}
			return $custom;
		}

        public static function get_original_custom_value_for_subscription_payment ( $subscr_id ) {
            if ( isset ( $subscr_id )){
                //Lets check if a proper custom field value is already saved in the CPT for this stripe subscription.
                $txn_cpt_qry_args = array(
                        'post_type'  => 'swpm_transactions',
                        'orderby'    => 'post_id',
                        'order'      => 'ASC',
                        'meta_query' => array(
                                array(
                                        'key' => 'subscr_id',
                                        'value' => $subscr_id
                                ),
                        )
                );
                $txn_cpt_qry = new WP_Query( $txn_cpt_qry_args );

                $found_posts = $txn_cpt_qry->found_posts;
                if ( $found_posts ) {
                    //Found a match so this is a subscription payment notification.
                    //Read the posts array.
                    $posts = $txn_cpt_qry->posts;

                    //The fist post entry will be the original stripe webhook notification.
                    $first_entry = array_shift($posts);
                    //Get the ID of the post.
                    $cpt_post_id = $first_entry->ID;
                    //Retrieve the original custom value saved for this post.
                    $orig_custom_value = get_post_meta( $cpt_post_id, 'custom', true );
                    return $orig_custom_value;
                }
            }
            return '';
        }

}
