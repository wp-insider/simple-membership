<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SWPMPaymentsListTable extends WP_List_Table {

	public $items = array();

	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct(
			array(
				'singular' => 'transaction', // singular name of the listed records
				'plural'   => 'transactions', // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	function column_default( $item, $column_name ) {
		$val = $item[ $column_name ];
		switch ( $column_name ) {
			case 'payment_amount':
				$val = SwpmMiscUtils::format_money( $val );
				$val = apply_filters( 'swpm_transactions_page_amount_display', $val, $item );
				break;
			default:
				break;
		}
		return $val;
	}

	function column_id( $item ) {

		// Build row actions
		$actions = array(
			// 'edit' => sprintf('<a href="admin.php?page=simple_wp_membership_payments&action=edit_txn&id=%s">Edit</a>', $item['id']),//TODO - Need to fix
			'edit' => sprintf('<a href="admin.php?page=simple_wp_membership_payments&tab=edit_txn&id=%s"">Edit/View</a>', $item['id']),
			'delete' => sprintf( '<a href="admin.php?page=simple_wp_membership_payments&action=delete_txn&id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this record?\')">Delete</a>', $item['id'], wp_create_nonce( 'swpm_delete_txn_' . $item['id'] ) ),
		);

		// Return the refid column contents
		return $item['id'] . $this->row_actions( $actions );
	}

	function column_member_profile( $item ) {
		global $wpdb;
		$member_id = $item['member_id'];
		$subscr_id = $item['subscr_id'];
        $txn_id = $item['txn_id'];
		$column_value = '';

		if ( empty( $member_id ) ) {// Lets try to get the member id using unique reference
			if ( ! empty( $subscr_id ) ) {
				$resultset = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl where subscr_id=%s", $subscr_id ), OBJECT );
				if ( $resultset ) {
					// Found a record using the "subscr_id" of the payments table.
					$member_id = $resultset->member_id;
				}
			} else if ( ! empty ( $txn_id ) ){
                //Fallback - lets try to find a member record using the "txn_id". See if this "txn_id" is found in the subscr_id of a member's profile.
                $resultset = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_members_tbl where subscr_id=%s", $txn_id ), OBJECT );
				if ( $resultset ) {
					// Found a record using the "txn_id" of the payments table.
					$member_id = $resultset->member_id;
				}
            }
		}

		if ( ! empty( $member_id ) ) {
			$profile_page = 'admin.php?page=simple_wp_membership&member_action=edit&member_id=' . $member_id;
			$column_value = '<a href="' . $profile_page . '">' . SwpmUtils::_( 'View Profile' ) . '</a>';
		} else {
			$column_value = '';
		}
		return $column_value;
	}

	function column_status( $item ) {
		$status = ucfirst($item['status']);
		$column_value = '';

		if ( strtolower($status) == 'completed' ) {
			$column_value = '<span class="swpm_status_completed">' . $status . '</span>';
		} else if ( strtolower($status) == 'refunded' ) {
			$column_value = '<span class="swpm_status_refunded">' . $status . '</span>';
		} else if ( strtolower($status) == 'subscription created' ) {
            if ( in_array(strtolower($item['subscr_status']), array('canceled', 'cancelled')) ){
                $column_value = '<span class="swpm_status_subscription_cancelled">Subscription '. $item['subscr_status'] .'</span>';
            } else {
			    $column_value = '<span class="swpm_status_subscription_created">' . $status . '</span>';
            }
		} else if ( strtolower($status) == 'stripe subscription created' ) {
			$column_value = '<span class="swpm_status_subscription_created">' . $status . '</span>';
		} else if ( strtolower($status) == 'subscription' ) {
			$column_value = '<span class="swpm_status_subscription">' . $status . '</span>';
		} else {
			$column_value = '<span class="swpm_status_general">' . $status . '</span>';
		}
		return $column_value;
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/* $1%s */ $this->_args['singular'], // Let's reuse singular label (affiliate)
			/* $2%s */ $item['id'] // The value of the checkbox should be the record's key/id
		);
	}

	function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />', // Render a checkbox instead of text
			'id'               => __( 'ID' , 'simple-membership'),
			'email'            => __( 'Email Address' , 'simple-membership'),
			'first_name'       => __( 'First Name' , 'simple-membership'),
			'last_name'        => __( 'Last Name' , 'simple-membership'),
			'member_profile'   => __( 'Member Profile' , 'simple-membership'),
			'txn_date'         => __( 'Date' , 'simple-membership'),
			'txn_id'           => __( 'Transaction ID' , 'simple-membership'),
			'subscr_id'        => __( 'Subscriber ID' , 'simple-membership'),
			'payment_amount'   => __( 'Amount' , 'simple-membership'),
			'membership_level' => __( 'Membership Level' , 'simple-membership'),
			'status'           => __( 'Status/Note' , 'simple-membership'),
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id'               => array( 'id', false ), // true means its already sorted
			'membership_level' => array( 'membership_level', false ),
			'last_name'        => array( 'last_name', false ),
			'txn_date'         => array( 'txn_date', false ),
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'simple-membership' ),
		);
		return $actions;
	}

	public function process_bulk_action() {
		// Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			if ( empty( $_GET['transaction'] ) ) {
				echo '<div id="message" class="notice notice-error"><p>'.__('Error! You need to select multiple records to perform a bulk action!', 'simple-membership').'</p></div>';
				return;
			}
			$records_to_delete = array_map( 'sanitize_text_field', $_GET['transaction'] );

			$action = 'bulk-' . $this->_args['plural'];
			check_admin_referer( $action );

			foreach ( $records_to_delete as $post_id ) {
				$this->delete_record($post_id);
			}

			echo '<div id="message" class="notice notice-success"><p>'.__('Selected records deleted successfully!', 'simple-membership').'</p></div>';
		}
	}

	/**
	 * Deletes a record by post id.
	 *
	 * @param int|string $post_id
	 * 
	 * @return bool TRUE if deletion successful, FALSE otherwise.
	 */
	public function delete_record( $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			wp_die( __('Error! ID must be numeric.', 'simple-membership') );
		}
		// Delete the record form posts table
		$deletion_result = wp_delete_post( $post_id, true );

		// Delete the record from old custom swpm_payments_tbl table as well (if exists).
		$db_row_id = get_post_meta($post_id, 'db_row_id', true);
		if (!empty($db_row_id)) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'swpm_payments_tbl WHERE id = %d', $db_row_id ) );
		}

		wp_reset_postdata();

		return $deletion_result instanceof \WP_Post ? true : false;
	}

	function prepare_items() {
		global $wpdb;

		// Lets decide how many records per page to show
		$per_page = apply_filters( 'swpm_transactions_menu_items_per_page', 50 );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		// This checks for sorting input. Read and sanitize the inputs
		$orderby_column = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '';
		$sort_order     = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : '';
		if ( empty( $orderby_column ) ) {
			$orderby_column = 'id';
			$sort_order     = 'DESC';
		}
		$orderby_column = SwpmUtils::sanitize_value_by_array( $orderby_column, $sortable );
		$sort_order     = SwpmUtils::sanitize_value_by_array(
			$sort_order,
			array(
				'DESC' => '1',
				'ASC'  => '1',
			)
		);

		// pagination requirement
		$current_page = $this->get_pagenum();

		$search_term = isset( $_POST['swpm_txn_search'] ) ? sanitize_text_field( stripslashes ( $_POST['swpm_txn_search'] ) ) : '';
		$search_term = trim( $search_term );

		if ( $search_term ) {
			// Only load the searched records.

			$search_str = esc_sql( $search_term );

			// Get the post ids if searched transaction post type.
			$post_ids = get_posts(
				array(
					'post_type'  => 'swpm_transactions',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => 'email',
							'value'   => $search_str,
							'compare' => 'LIKE'
						),						
						array(
							'key'     => 'txn_id',
							'value'   => $search_str,
							'compare' => 'LIKE'
						),						
						array(
							'key'     => 'first_name',
							'value'   => $search_str,
							'compare' => 'LIKE'
						),						
						array(
							'key'     => 'last_name',
							'value'   => $search_str,
							'compare' => 'LIKE'
						),						
						array(
							'key'     => 'subscr_id',
							'value'   => $search_str,
							'compare' => 'LIKE'
						),
					),
				)
			);
			
			$total_items   = count( $post_ids );

		} else { 
			// Load all data in an optimized way (so it is only loading data for the current page)

			// Get the total transaction post counts.
			$transactions_count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type='swpm_transactions'";
			$total_items = $wpdb->get_var( $transactions_count_query );

			$offset = ( $current_page - 1 ) * $per_page;

			// TODO: Old code. Need to remove
			// $query = "SELECT * FROM {$wpdb->prefix}swpm_payments_tbl ORDER BY $orderby_column $sort_order";
			// $query .= ' LIMIT ' . (int) $offset . ',' . (int) $per_page;
			// $data = $wpdb->get_results( $query, ARRAY_A );

			// Get the post ids of all transaction post type with pagination.
			$post_ids = get_posts(
				array(
					'post_type' => 'swpm_transactions',
					'posts_per_page' => (int) $per_page, // pagination requirement
					'offset' => (int) $offset,
					'fields' => 'ids',
				)
			);
		}

		$this->process_txn_records_from_post_ids($post_ids);
		
		// Now we add our *sorted* data to the items property, where it can be used by the rest of the class.
		// $this->items = $transaction_records;
		// wp_die('<pre>'. print_r($transaction_records, true) .'</pre>');

		// pagination requirement
		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items
				'per_page'    => $per_page, // WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $per_page ),   // WE have to calculate the total number of pages
			)
		);
	}

	/**
	 * Populate the $items property with appropriate fields by their post ids.
	 *
	 * @param array $post_ids Post IDs of transaction post type.
	 * 
	 * @return void
	 */
	private function process_txn_records_from_post_ids(&$post_ids){
		foreach ($post_ids as $post_id) {
			array_push($this->items, SwpmTransactions::get_txn_post_meta_data_in_array_format($post_id));
		}
	}

}
