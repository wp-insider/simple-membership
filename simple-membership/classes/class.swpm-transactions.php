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

		//Prepare the transaction data array.
		$txn_data = array();
		$txn_data['email']            = $ipn_data['payer_email'];
		$txn_data['first_name']       = $ipn_data['first_name'];
		$txn_data['last_name']        = $ipn_data['last_name'];
		$txn_data['ip_address']       = $ip_address;

		//Get the member ID. First, get from the custom field (highest priority). If not found, try to get from $ipn_data['member_id'] (When handling IPN we try to set it if we find a reference for it).
		$txn_data['member_id'] = isset ( $custom_var['swpm_id'] ) ? $custom_var['swpm_id'] : '';
		if( empty( $txn_data['member_id'] ) ){
			$txn_data['member_id'] = isset ( $ipn_data['member_id'] ) ? $ipn_data['member_id'] : '';
		}
		//SwpmLog::log_simple_debug( 'Member ID value: ' . $txn_data['member_id'], true );

		//Get the membership level reference.
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
		
		//Save the transaction record to the payments database table (this is for backwards compatibility).
		$wpdb->insert( $wpdb->prefix . 'swpm_payments_tbl', $txn_data );

		$db_row_id = $wpdb->insert_id;

        /*** Save to the swpm_transactions CPT ***/
		$post = array();
		$post['post_title']  = '';
		$post['post_status'] = 'publish';
		$post['content'] = '';
		$post['post_type'] = 'swpm_transactions';

		$post_id = wp_insert_post( $post );

		//The key that connects the 'swpm_transactions' CPT post and the the swpm_payments_tbl row.
		update_post_meta( $post_id, 'db_row_id', $db_row_id );

		//Save additional data based on the checkout gateway.
		if( isset( $ipn_data['gateway'])) {
			//Check if this is a PayPal std subscription, or PayPal PPCP subscription, or a Stripe subscription checkout.
			if ( $ipn_data['gateway'] == 'paypal_subscription_checkout' || $ipn_data['gateway'] == 'stripe-sca-subs' || $ipn_data['gateway'] == 'paypal_std_sub_checkout' ) {
				//Save the swpm_transactions CPT post ID of the original checkout in the member's proifle. Useful to retreive some of the original checkout txn data (example: custom_field data).
				$member_record = SwpmMemberUtils::get_user_by_subsriber_id( $subscr_id );
				if( ! $member_record ){
					SwpmLog::log_simple_debug( 'Error! Could not find an existing member record for the given subscriber ID: ' . $subscr_id . '. This member profile may have been deleted.', false );
				} else {
					$member_id = $member_record->member_id;
					$extra_info = SwpmMemberUtils::get_account_extra_info( $member_id );
					//Check if the extra_info is an array. If not, initialize it as an array before adding the orig_swpm_txn_cpt_id.
					if (!is_array($extra_info)) {
						$extra_info = array(); // Initialize as array if not already
					}					
					$extra_info['orig_swpm_txn_cpt_id'] = $post_id;
					//Update the member's extra_info with the orig_swpm_txn_cpt_id.
					SwpmMemberUtils::update_account_extra_info( $member_id, $extra_info );
				}
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
		
		//Add the discount_amount value to the txn_data array so it can be saved to the swpm_transactions CPT.
		if ( isset( $ipn_data['discount_amount'] ) ) {
			$txn_data['discount_amount'] = $ipn_data['discount_amount'];
		}

		//Add the txn_type value to the txn_data array so it can be saved to the swpm_transactions CPT.
		if ( isset( $ipn_data['txn_type'] ) ) {
			$txn_data['txn_type'] = $ipn_data['txn_type'];
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
        // TODO: Old Code. Need to remove.
		// global $wpdb;
        // $payments_table_name = $wpdb->prefix . 'swpm_payments_tbl';
        // SwpmLog::log_simple_debug( 'Updating the payment status value of transaction to: ' . $new_status . '. Row ID: ' . $txn_row_id, true );
        // $query = $wpdb->prepare( "UPDATE $payments_table_name SET status=%s WHERE id=%s", $new_status, $txn_row_id );
        // $resultset = $wpdb->query( $query );

        SwpmLog::log_simple_debug( 'Updating the payment status value of transaction to: ' . $new_status . '. Post ID: ' . $txn_row_id, true );
        update_post_meta($txn_row_id, 'status', $new_status);
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

    /**
     * Get transaction cpt record from posts table by subscription id.
     *
     * @param string $subscription_id The subscription id.
     * @param bool $return_post_metas Whether to also retrieve post meta associated with this post.
     *
     * @return object|null
     */
	public static function get_transaction_row_by_subscr_id (string $subscription_id, bool $return_post_metas = false)
    {
        $meta_query = array(
            array(
				'key'     => 'subscr_id',
				'value'   => $subscription_id,
				'compare' => '='
			)
        );

        if ($return_post_metas){
            return self::get_txn_post_using_meta_query_with_metadata( $meta_query );
        }
        return self::get_txn_post_using_meta_query($meta_query);
	}

    /**
     * Get transaction cpt record from posts table by transaction id.
     *
     * @param string $txn_id The transaction id.
     * @param bool $return_post_metas Whether to also retrieve post meta associated with this post.
     *
     * @return object|null
     */
	public static function get_transaction_row_by_txn_id ( $txn_id, $return_post_metas = false)
    {
        $meta_query = array(
			array(
				'key'     => 'txn_id',
				'value'   => $txn_id,
				'compare' => '='
        	)
		);

        if ($return_post_metas){
            return self::get_txn_post_using_meta_query_with_metadata( $meta_query );
        }
        return self::get_txn_post_using_meta_query($meta_query);
	}

    /**
     * Get transaction cpt record from posts table by transaction id and email.
     *
     * @param string $txn_id The transaction id.
     * @param string $email The payer email.
     * @param bool $return_post_metas Whether to also retrieve post meta associated with this post.
     *
     * @return object|null
     */
    public static function get_transaction_row_by_txn_id_and_email( $txn_id, $email, $return_post_metas = false)
    {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => 'txn_id',
                'value'   => $txn_id,
                'compare' => '='
            ),
            array(
                'key'     => 'email',
                'value'   => $email,
                'compare' => '='
            ),
        );

        if ($return_post_metas){
            return self::get_txn_post_using_meta_query_with_metadata( $meta_query );
        }
        return self::get_txn_post_using_meta_query($meta_query);
    }

    /**
     * Get transaction cpt record from posts table by transaction id and subscription id.
     *
     * @param string $txn_id The transaction id.
     * @param string $subscription_id The subscription id.
     * @param bool $return_post_metas Whether to also retrieve post meta associated with this post.
     *
     * @return object|null
     */
    public static function get_transaction_row_by_txn_id_and_subscription_id( $txn_id, $subscription_id, $return_post_metas = false)
    {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => 'txn_id',
                'value'   => $txn_id,
                'compare' => '='
            ),
            array(
                'key'     => 'subscr_id',
                'value'   => $subscription_id,
                'compare' => '='
            ),
        );

        if ($return_post_metas){
            return self::get_txn_post_using_meta_query_with_metadata( $meta_query );
        }
        return self::get_txn_post_using_meta_query($meta_query);
    }

    /**
     * Retrieve first transaction post using meta query.
     *
     * @param array $meta_query
     *
     * @return object|null The retrieved post as WP_Post object. NULL if no posts found.
     */
    public static function get_txn_post_using_meta_query( array $meta_query )
    {
        // Get the transaction posts types.
        $txn_posts = get_posts(
            array(
                'post_type' => 'swpm_transactions',
                'posts_per_page' => -1,
                'meta_query' => $meta_query
            )
        );

        wp_reset_postdata();

        if ( count( $txn_posts ) ){
			$the_txn_post = isset($txn_posts[0]) ? $txn_posts[0] : null;
            return $the_txn_post;
        }

        return null;
    }

    /**
     * Retrieve first transaction post using meta query with associated metadata.
     *
     * @param array $meta_query
     *
     * @return object|null Post data as object. NULL if posts not found.
     */
    public static function get_txn_post_using_meta_query_with_metadata( array $meta_query )
    {
        $txn_post = self::get_txn_post_using_meta_query($meta_query);

        // Check if a matching transaction post found or not.
        if (empty($txn_post)) {
            return null;
        }

        return self::get_txn_post_meta_data_in_object_format($txn_post->ID);
    }

	/**
	 * Retrieve all the transaction CPT posts by member ID.
	 */
	public static function get_all_txn_cpts_with_metadata_by_member_id( $member_id ) {
		$meta_query = array(
			array(
				'key' => 'member_id',
				'value' => $member_id,
				'compare' => '='
			)
		);

		$all_txn_cpts = self::get_all_txn_posts_using_meta_query_with_metadata($meta_query);
		return $all_txn_cpts;
	}
   
	/**
     * Retrieve all the transaction CPT posts using meta query with metadata.
     *
     * @param array $meta_query The standard 'meta_query' array argument that is used in the get_posts() function.
     *
     * @return array|null The retrieved posts as array of WP_Post object. NULL if no posts found.
     */
    public static function get_all_txn_posts_using_meta_query_with_metadata( array $meta_query )
    {
		/*** Tip ***/
		//Use the following technique to print all post metas of a CPT so we can craft the meta_query parameter as needed.
		//$all_post_metas_of_a_cpt = get_post_meta( "1234" );
		//print_r($all_post_metas_of_a_cpt);
		
        // Get the transaction posts types.
        $txn_posts = get_posts(
            array(
                'post_type' => 'swpm_transactions',
                'posts_per_page' => -1,
                'meta_query' => $meta_query
            )
        );

        wp_reset_postdata();

        if (count($txn_posts) < 1){
            return null;
        }

		$result = array();

		foreach ($txn_posts as $txn_post) {
			$result[] = self::get_txn_post_meta_data_in_array_format($txn_post->ID);
		}

        return $result;
    }

	/*
	 * Get all the transaction meta data of a transaction in array format.
	 * @param string $post_id The transaction post ID.
	 * @return array
	 */
    public static function get_txn_post_meta_data_in_array_format( int $post_id){
        $meta_data = array(
            'id' => $post_id,
            'db_row_id' => get_post_meta($post_id, 'db_row_id', true),
            'email' => get_post_meta($post_id, 'email', true),
            'first_name' => get_post_meta($post_id, 'first_name', true),
            'last_name' => get_post_meta($post_id, 'last_name', true),
            'member_id' => get_post_meta($post_id, 'member_id', true),
            'membership_level' => get_post_meta($post_id, 'membership_level', true),
            'txn_date' => get_post_meta($post_id, 'txn_date', true),
            'txn_id' => get_post_meta($post_id, 'txn_id', true),
            'subscr_id' => get_post_meta($post_id, 'subscr_id', true),
            'reference' => get_post_meta($post_id, 'reference', true),
            'payment_amount' => get_post_meta($post_id, 'payment_amount', true),
            'gateway' => get_post_meta($post_id, 'gateway', true),
            'status' => get_post_meta($post_id, 'status', true),
            'subscr_status' => get_post_meta($post_id, 'subscr_status', true), // For subscription type transactions only.
            'ip_address' => get_post_meta($post_id, 'ip_address', true),
            'payment_button_id' => get_post_meta($post_id, 'payment_button_id', true),
            'is_live' => get_post_meta($post_id, 'is_live', true),
            'discount_amount' => get_post_meta($post_id, 'discount_amount', true),
            'custom' => get_post_meta($post_id, 'custom', true),
        );
        return $meta_data;
    }

    /**
     * Retrieve all the post metadata for a transaction post type by its id.
     *
     * @param int $post_id ID of transaction post
     *
     * @return object All associated post metas
     */
	public static function get_txn_post_meta_data_in_object_format( int $post_id){
		$meta_data_obj = (object) self::get_txn_post_meta_data_in_array_format($post_id);
		return $meta_data_obj;
	}

	/**
	 * Get the original subscription checkout transaction CPT Post ID by the subscriber ID.
	 * Get's the original transaction CPT Post ID from the member's profile (we save it in the extra_info when the subscription is created).
	 */
	public static function get_original_swpm_txn_cpt_id_by_subscr_id ( $subscr_id ) {
		$extra_info = SwpmMemberUtils::get_account_extra_info_by_subscr_id( $subscr_id );
		if( isset( $extra_info['orig_swpm_txn_cpt_id'] ) && !empty( $extra_info['orig_swpm_txn_cpt_id'] )){
			$txn_cpt_post_id = $extra_info['orig_swpm_txn_cpt_id'];
			return $txn_cpt_post_id;
		}
		return '';
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
