<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SwpmMembers extends WP_List_Table {

	function __construct() {
		parent::__construct(
			array(
				'singular' => SwpmUtils::_( 'Member' ),
				'plural'   => SwpmUtils::_( 'Members' ),
				'ajax'     => false,
			)
		);
	}

	function get_columns() {
		$columns = array(
			'cb'                  => '<input type="checkbox" />',
			'member_id'           => __('ID' , 'simple-membership'),
			'user_name'           => __('Username' , 'simple-membership'),
			'first_name'          => __('First Name' , 'simple-membership'),
			'last_name'           => __('Last Name' , 'simple-membership'),
			'email'               => __('Email' , 'simple-membership'),
			'alias'               => __('Membership Level' , 'simple-membership'),
			'subscription_starts' => __('Access Starts' , 'simple-membership'),
			'account_state'       => __('Account State' , 'simple-membership'),
			'last_accessed'       => __('Last Login Date' , 'simple-membership'),
			'admin_notes'         => __('Notes' , 'simple-membership'),
		);
		return apply_filters( 'swpm_admin_members_table_columns', $columns );
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'member_id'           => array( 'member_id', true ), //True means already sorted
			'user_name'           => array( 'user_name', false ),
			'first_name'          => array( 'first_name', false ),
			'last_name'           => array( 'last_name', false ),
			'email'               => array( 'email', false ),
			'alias'               => array( 'alias', false ),
			'subscription_starts' => array( 'subscription_starts', false ),
			'account_state'       => array( 'account_state', false ),
			'last_accessed'       => array( 'last_accessed', false ),
		);
		return apply_filters( 'swpm_admin_members_table_sortable_columns', $sortable_columns );
	}

	function get_bulk_actions() {
		$actions = array(
			'bulk_delete'        => SwpmUtils::_( 'Delete' ),
			'bulk_active'        => SwpmUtils::_( 'Set Status to Active' ),
			'bulk_active_notify' => SwpmUtils::_( 'Set Status to Active and Notify' ),
			'bulk_inactive'      => SwpmUtils::_( 'Set Status to Inactive' ),
			'bulk_pending'       => SwpmUtils::_( 'Set Status to Pending' ),
			'bulk_expired'       => SwpmUtils::_( 'Set Status to Expired' ),
		);
		return $actions;
	}

	function column_default( $item, $column_name ) {
                $column_value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		$column_data = apply_filters( 'swpm_admin_members_table_column_' . $column_name, $column_value, $item );
		return $column_data;
	}

	function column_account_state( $item ) {
		$account_state_column_val = isset( $item['account_state'] ) ? $item['account_state'] : '';
		if ($account_state_column_val == 'activation_required'){
			//For better translation support.
			$acc_state_str = __( 'Activation Required', 'simple-membership' );
		} else {
			$acc_state_str = __( ucfirst( $account_state_column_val ), 'simple-membership' );
		}
		return $acc_state_str;
	}

	function column_member_id( $item ) {
		$delete_swpmuser_nonce = wp_create_nonce( 'delete_swpmuser_admin_end' );
		$actions               = array(
			'edit'   => sprintf( '<a href="admin.php?page=simple_wp_membership&member_action=edit&member_id=%s">Edit/View</a>', $item['member_id'] ),
			'delete' => sprintf( '<a href="admin.php?page=simple_wp_membership&member_action=delete&member_id=%s&delete_swpmuser_nonce=%s" onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>', $item['member_id'], $delete_swpmuser_nonce ),
		);
		return $item['member_id'] . $this->row_actions( $actions );
	}

	function column_user_name( $item ) {
		$user_name = $item['user_name'];
		if ( empty( $user_name ) ) {
			$user_name = '[' . SwpmUtils::_( 'incomplete' ) . ']';
		}
		return $user_name;
	}

	function column_admin_notes( $item ) {
		$admin_notes = isset($item['notes']) ? $item['notes'] : '';
		if ( empty( $admin_notes ) ) {
			//Admin notes not found for this member.
			return '&mdash;';
		}

		//Truncate the admin notes if it is too long.
		$max_length = 256;
		if (strlen($admin_notes) > $max_length) {
			$admin_notes_text = substr($admin_notes, 0, $max_length) . ' ... ';
		} else {
			$admin_notes_text = $admin_notes;
		}
		
		//Display the notes in a tooltip.
		$member_id = intval($item['member_id']);
		$notes_tooltip_id = 'swpm_note_tooltip_' . $member_id;
        ob_start();
        ?>
        <div class="swpm-tooltip-notes-container">
			<a href="javascript:void(0)" onclick="const tooltip=document.getElementById('<?php echo $notes_tooltip_id; ?>'); tooltip.style.display = (tooltip.style.display === 'block' ? 'none' : 'block');">
    		<?php _e('Show/Hide Notes', 'simple-membership'); ?>
			</a>
            <div class="swpm-tooltip-notes-style-1" id="<?php echo esc_attr($notes_tooltip_id)?>" onclick="this.style.display='none'">
				<?php echo esc_attr($admin_notes_text) ?>
			</div>
        </div>
        <?php
	    return ob_get_clean();
    }

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="members[]" value="%s" />',
			$item['member_id']
		);
	}

	function prepare_items() {
		global $wpdb;

		$this->process_bulk_action();

		$records_query_head = 'SELECT member_id,user_name,first_name,last_name,email,alias,subscription_starts,account_state,notes,last_accessed';
		$count_query_head   = 'SELECT COUNT(member_id)';

		$query  = ' ';
		$query .= ' FROM ' . $wpdb->prefix . 'swpm_members_tbl';
		$query .= ' LEFT JOIN ' . $wpdb->prefix . 'swpm_membership_tbl';
		$query .= ' ON ( membership_level = id ) ';

		//Get the search string (if any)
		$s = filter_input( INPUT_GET, 's' );
		if ( empty( $s ) ) {
			$s = filter_input( INPUT_POST, 's' );
		}

		$status = filter_input( INPUT_GET, 'status' );
                $status = esc_attr( $status );//Escape value

		$filters = array();

		//Add the search parameter to the query
		if ( ! empty( $s ) ) {
			$s = sanitize_text_field( $s );
			$s = trim( $s ); //Trim the input
                        $s = esc_sql( $s );
			$filters[] = "( user_name LIKE '%" . strip_tags( $s ) . "%' "
					. " OR first_name LIKE '%" . strip_tags( $s ) . "%' "
					. " OR last_name LIKE '%" . strip_tags( $s ) . "%' "
					. " OR email LIKE '%" . strip_tags( $s ) . "%' "
					. " OR address_city LIKE '%" . strip_tags( $s ) . "%' "
					. " OR address_state LIKE '%" . strip_tags( $s ) . "%' "
					. " OR country LIKE '%" . strip_tags( $s ) . "%' "
					. " OR company_name LIKE '%" . strip_tags( $s ) . "%' )";
		}

		//Add account status filtering to the query
		if ( ! empty( $status ) ) {
			if ( $status == 'incomplete' ) {
				$filters[] = "user_name = ''";
			} else {
				$filters[] = "account_state = '" . $status . "'";
			}
		}

		//Add membership level filtering
		$membership_level = filter_input( INPUT_GET, 'membership_level', FILTER_SANITIZE_NUMBER_INT );

		if ( ! empty( $membership_level ) ) {
			$filters[] = sprintf( "membership_level = '%d'", $membership_level );
		}

		//Build the WHERE clause of the query string
		if ( ! empty( $filters ) ) {
			$filter_str = '';
			foreach ( $filters as $ind => $filter ) {
				$filter_str .= $ind === 0 ? $filter : ' AND ' . $filter;
			}
			$query .= 'WHERE ' . $filter_str;
		}

		//Build the orderby and order query parameters
		$orderby          = filter_input( INPUT_GET, 'orderby' );
		$orderby          = apply_filters( 'swpm_admin_members_table_orderby', $orderby );
		$orderby          = empty( $orderby ) ? 'member_id' : $orderby;
		$order            = filter_input( INPUT_GET, 'order' );
		$order            = empty( $order ) ? 'DESC' : $order;
		$sortable_columns = $this->get_sortable_columns();
		$orderby          = SwpmUtils::sanitize_value_by_array( $orderby, $sortable_columns );
		$order            = SwpmUtils::sanitize_value_by_array(
			$order,
			array(
				'DESC' => '1',
				'ASC'  => '1',
			)
		);
		$query           .= ' ORDER BY ' . $orderby . ' ' . $order;

		//Execute the query
		$totalitems = $wpdb->get_var( $count_query_head . $query );
		//Pagination setup
		$perpage = apply_filters( 'swpm_members_menu_items_per_page', 50 );
		$paged   = filter_input( INPUT_GET, 'paged' );
		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}
		$totalpages = ceil( $totalitems / $perpage );
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}
		$this->set_pagination_args(
			array(
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page'    => $perpage,
			)
		);

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $wpdb->get_results( $records_query_head . $query, ARRAY_A );
		$this->items           = apply_filters( 'swpm_admin_members_table_items', $this->items );
	}

	function get_user_count_by_account_state() {
		global $wpdb;
		$query  = 'SELECT count(member_id) AS count, account_state FROM ' . $wpdb->prefix . 'swpm_members_tbl GROUP BY account_state';
		$result = $wpdb->get_results( $query, ARRAY_A );
		$count  = array();

		$all = 0;
		foreach ( $result as $row ) {
			$count[ $row['account_state'] ] = $row['count'];
			$all                           += intval( $row['count'] );
		}
		$count ['all'] = $all;

		$count_incomplete_query = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . "swpm_members_tbl WHERE user_name = ''";
		$count['incomplete']    = $wpdb->get_var( $count_incomplete_query );

		return $count;
	}

	function no_items() {
		_e( 'No member found.', 'simple-membership' );
	}

	function process_form_request() {
		if ( isset( $_REQUEST['member_id'] ) ) {
			//This is a member profile edit action
			$record_id = sanitize_text_field( $_REQUEST['member_id'] );
			if ( ! is_numeric( $record_id ) ) {
				wp_die( 'Error! ID must be numeric.' );
			}
			return $this->edit( absint( $record_id ) );
		}

		//This is a profile add action.
		return $this->add();
	}

    public static function membership_lvl_not_configured_msg_box()
    {
        $output = '<div class="swpm-yellow-box">';
        $output .= '<p>';
        $output .= __("Each member account must be assigned a membership level. It appears that you don't have any membership levels configured. Please create a membership level first before adding or editing any member records.", 'simple-membership');
        $output .= '</p>';
		$output .= '<p>';
		$output .= __("Read the ", 'simple-membership');
		$output .= '<a href="https://simple-membership-plugin.com/adding-membership-access-levels-site/" target="_blank">' . __("membership level documentation", 'simple-membership') . '</a>';
		$output .= __(" to learn how to create a membership level.", 'simple-membership');
		$output .= '</p>';
        $output .= '<br />';
        $output .= '<a href="'. admin_url() . 'admin.php?page=simple_wp_membership_levels&level_action=add" class="button button-primary">';
        $output .= __('Create a Membership Level', 'simple-membership');
        $output .= '</a>';
        $output .= '</div>';
        return $output;
    }

	function add() {
        if(!SwpmMembershipLevelUtils::is_membership_level_configured()){
            echo self::membership_lvl_not_configured_msg_box();
            return;
        }

		$form = apply_filters( 'swpm_admin_registration_form_override', '' );
		if ( ! empty( $form ) ) {
			echo $form;
			return;
		}
		global $wpdb;
		$member                        = SwpmTransfer::$default_fields;
		$member['member_since']        = SwpmUtils::get_current_date_in_wp_zone();//date( 'Y-m-d' );
		$member['subscription_starts'] = SwpmUtils::get_current_date_in_wp_zone();//date( 'Y-m-d' );
		if ( isset( $_POST['createswpmuser'] ) ) {
			$member = array_map( 'sanitize_text_field', $_POST );
		}
		extract( $member, EXTR_SKIP );
		$query  = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE  id !=1 ';
		$levels = $wpdb->get_results( $query, ARRAY_A );

		$render_new_form_ui = SwpmSettings::get_instance()->get_value('use-new-form-ui');
		if (!empty($render_new_form_ui)) {
			$add_user_template_path = apply_filters('swpm_admin_registration_add_user_template_path', SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_add_v2.php');
		}else{
			$add_user_template_path = apply_filters('swpm_admin_registration_add_user_template_path', SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_add.php');
		}
		
		include_once $add_user_template_path;

		return false;
	}

	function edit( $id ) {
        if(!SwpmMembershipLevelUtils::is_membership_level_configured()){
            echo self::membership_lvl_not_configured_msg_box();
            return;
        }

		global $wpdb;
		$id = absint( $id );
		$query = "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = $id";
		$member = $wpdb->get_row( $query, ARRAY_A );
		if ( ! $member ) {
			//Member record not found. Show an error message.
			$error_msg = __( 'Error! Member record not found. You may have deleted this member profile. ', 'simple-membership' );
			$error_msg .= __( 'Please go back to the members menu and try to edit another member profile.', 'simple-membership' );
			echo '<div class="swpm-erro-msg swpm-red-box"><p>' . $error_msg . '</p></div>';
			return;
		}

		if ( isset( $_POST['editswpmuser'] ) ) {
			$_POST['user_name'] = sanitize_text_field( $member['user_name'] );
			$_POST['email']     = sanitize_email( $member['email'] );
			foreach ( $_POST as $key => $value ) {
				$key = sanitize_text_field( $key );
				if ( $key == 'email' ) {
					$member[ $key ] = sanitize_email( $value );
				} else {
					$member[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		extract( $member, EXTR_SKIP );
		$query  = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE  id !=1 ';
		$levels = $wpdb->get_results( $query, ARRAY_A );


		$render_new_form_ui = SwpmSettings::get_instance()->get_value('use-new-form-ui');
		if (!empty($render_new_form_ui)) {
			$edit_user_template_path = apply_filters('swpm_admin_registration_edit_user_template_path', SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_edit_v2.php');
		}else{
			$edit_user_template_path = apply_filters('swpm_admin_registration_edit_user_template_path', SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_edit.php');
		}

		include_once $edit_user_template_path;

		return;
	}

	function process_bulk_action() {
		//Detect when a bulk action is being triggered... then perform the action.
		$members = isset( $_REQUEST['members'] ) ? $_REQUEST['members'] : array();
		$members = array_map( 'sanitize_text_field', $members );

		$current_action = $this->current_action();
		if ( ! empty( $current_action ) ) {
			//Bulk operation action. Lets make sure multiple records were selected before going ahead.
			if ( empty( $members ) ) {
				echo '<div id="message" class="error"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
				return;
			}
		} else {
			//No bulk operation.
			return;
		}

		check_admin_referer( 'swpm_bulk_action', 'swpm_bulk_action_nonce' );

		//perform the bulk operation according to the selection
		if ( 'bulk_delete' === $current_action ) {
			foreach ( $members as $record_id ) {
				if ( ! is_numeric( $record_id ) ) {
					wp_die( 'Error! ID must be numeric.' );
				}
				self::delete_user_by_id( $record_id );
			}
			echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
			return;
		} elseif ( 'bulk_active' === $current_action ) {
			$this->bulk_set_status( $members, 'active' );
		} elseif ( 'bulk_active_notify' == $current_action ) {
			$this->bulk_set_status( $members, 'active', true );
		} elseif ( 'bulk_inactive' == $current_action ) {
			$this->bulk_set_status( $members, 'inactive' );
		} elseif ( 'bulk_pending' == $current_action ) {
			$this->bulk_set_status( $members, 'pending' );
		} elseif ( 'bulk_expired' == $current_action ) {
			$this->bulk_set_status( $members, 'expired' );
		}

		echo '<div id="message" class="updated fade"><p>Bulk operation completed successfully!</p></div>';
	}

	function bulk_set_status( $members, $status, $notify = false ) {
		$ids = implode( ',', array_map( 'absint', $members ) );
		if ( empty( $ids ) ) {
			return;
		}
		global $wpdb;
		$query = 'UPDATE ' . $wpdb->prefix . 'swpm_members_tbl ' .
				" SET account_state = '" . $status . "' WHERE member_id in (" . $ids . ')';
		$wpdb->query( $query );

		if ( $notify ) {
			$settings = SwpmSettings::get_instance();

			$members = $wpdb->get_results( 'SELECT email, member_id FROM ' . $wpdb->prefix . 'swpm_members_tbl ' . " WHERE member_id IN ( $ids  ) " );

			$subject = $settings->get_value( 'bulk-activate-notify-mail-subject' );
			if ( empty( $subject ) ) {
                $subject = 'Account Activated!';
			}
			$body = $settings->get_value( 'bulk-activate-notify-mail-body' );
			if ( empty( $body ) ) {
                $body = 'Hi, Your account has been activated successfully!';
			}

			$from_address = $settings->get_value( 'email-from' );
			$headers = 'From: ' . $from_address . "\r\n";

            foreach ($members as $member) {
                $member_email = $member->email;
                $member_id = $member->member_id;
                $body = SwpmMiscUtils::replace_dynamic_tags( $body, $member_id );

                //Send the activation email one by one to all the selected members.
                $subject = apply_filters( 'swpm_email_bulk_set_status_subject', $subject );
                $body = apply_filters( 'swpm_email_bulk_set_status_body', $body );
                $to_email = trim($member_email);

                SwpmMiscUtils::mail( $to_email, $subject, $body, $headers );
                SwpmLog::log_simple_debug( 'Bulk activation email notification sent. Activation email sent to the following email: ' . $to_email, true );
            }
		}
	}

	function delete() {
		if ( isset( $_REQUEST['member_id'] ) ) {
			//Check we are on the admin end and user has management permission
			SwpmMiscUtils::check_user_permission_and_is_admin( 'member deletion by admin' );

			//Check nonce
			if ( ! isset( $_REQUEST['delete_swpmuser_nonce'] ) || ! wp_verify_nonce( $_REQUEST['delete_swpmuser_nonce'], 'delete_swpmuser_admin_end' ) ) {
				//Nonce check failed.
				wp_die( SwpmUtils::_( 'Error! Nonce verification failed for user delete from admin end.' ) );
			}

			$id = sanitize_text_field( $_REQUEST['member_id'] );
			$id = absint( $id );
			self::delete_user_by_id( $id );
		}
	}

	public static function delete_user_by_id( $id ) {
		if ( ! is_numeric( $id ) ) {
			wp_die( 'Error! Member ID must be numeric.' );
		}

		//Trigger action hook
		do_action( 'swpm_before_user_delete_action', $id );
		do_action( 'swpm_admin_end_user_delete_action', $id );

		$swpm_user = SwpmMemberUtils::get_user_by_id( $id );
		$user_name = $swpm_user->user_name;
		self::delete_wp_user( $user_name ); //Deletes the WP User record
		self::delete_swpm_user_by_id( $id ); //Deletes the SWPM record
	}

	public static function delete_swpm_user_by_id( $id ) {
		self::delete_user_subs( $id );
		global $wpdb;
		$query = 'DELETE FROM ' . $wpdb->prefix . "swpm_members_tbl WHERE member_id = $id";
		$wpdb->query( $query );
	}

	public static function delete_wp_user( $user_name ) {
		$wp_user_id = username_exists( $user_name );
		if ( empty( $wp_user_id ) || ! is_numeric( $wp_user_id ) ) {
			return;
		}

        if (SwpmMemberUtils::wp_user_has_admin_role($wp_user_id)){
            // For safety, we do not allow deletion of any associated WordPress account with administrator role.
            return;
        }

		if ( ! self::is_wp_super_user( $wp_user_id ) ) {
			//Not an admin user so it is safe to delete this user.
			include_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $wp_user_id, 1 ); //assigns all related to this user to admin.
		} else {
			//This is an admin user. So not going to delete the WP User record.
			SwpmLog::log_simple_debug( 'For safety, we do not allow deletion of any associated WordPress account with administrator role.', true );
			SwpmTransfer::get_instance()->set( 'status', 'For safety, we do not allow deletion of any associated WordPress account with administrator role.' );
			return;
		}
	}

	private static function delete_user_subs( $member_id ) {

		$member = SwpmMemberUtils::get_user_by_id( $member_id );

		if ( ! $member ) {
			return false;
		}

		SwpmLog::log_simple_debug("Cancelling all subscription of member id: " . $member_id, true);

        $subscription_utils = new SWPM_Utils_Subscriptions($member_id);
        $subscription_utils->load_subs_data();

		$active_subs = $subscription_utils->get_active_subscriptions();

		if ( empty($active_subs) ) {
		    SwpmLog::log_simple_debug("No active subscriptions found for member ID: " . $member_id, true);
			return false;
		}

		SwpmLog::log_simple_debug("Active subscriptions found for member ID: " . $member_id, true);
		SwpmLog::log_simple_debug( "Active subscription IDs: ". implode(', ', array_keys($active_subs)) , true);

		foreach ( $active_subs as $sub ) {
			switch($sub['gateway']){
				case 'stripe-sca-subs':
                    SwpmLog::log_simple_debug("Cancelling Stripe SCA subscription with subscription ID: ". $sub['sub_id'], true);
					$subscription_utils->cancel_subscription_stripe_sca( $sub['sub_id'] );
					break;
				case 'paypal_subscription_checkout':
                    SwpmLog::log_simple_debug("Cancelling PayPal PPCP subscription with subscription ID: ". $sub['sub_id'], true);
					$subscription_utils->cancel_subscription_paypal( $sub['sub_id'] );
					break;
				default:
					break;
			}
		}
	}

	public static function is_wp_super_user( $wp_user_id ) {
		//Note: Better to use the wp_user_has_admin_role() method to check if a user has administrator role.
		$user_data = get_userdata( $wp_user_id );
		if ( empty( $user_data ) ) {
			//Not an admin user if we can't find his data for the given ID.
			return false;
		}
		if ( isset( $user_data->wp_capabilities['administrator'] ) ) {//Check capability
			//admin user
			return true;
		}
		if ( $user_data->wp_user_level == 10 ) {//Check for old style wp user level
			//admin user
			return true;
		}
		//This is not an admin user
		return false;
	}

	function bulk_operation_menu() {
		echo '<div id="poststuff"><div id="post-body">';

		if ( isset( $_REQUEST['swpm_bulk_change_level_process'] ) ) {
			//Check nonce
			$swpm_bulk_change_level_nonce = filter_input( INPUT_POST, 'swpm_bulk_change_level_nonce' );
			if ( ! wp_verify_nonce( $swpm_bulk_change_level_nonce, 'swpm_bulk_change_level_nonce_action' ) ) {
				//Nonce check failed.
				wp_die( SwpmUtils::_( 'Error! Nonce security verification failed for Bulk Change Membership Level action. Clear cache and try again.' ) );
			}

			$error_msg = '';
			$from_level_id = sanitize_text_field( $_REQUEST['swpm_bulk_change_level_from'] );
			$to_level_id   = sanitize_text_field( $_REQUEST['swpm_bulk_change_level_to'] );

			if ( $from_level_id == 'please_select' || $to_level_id == 'please_select' ) {
				$error_msg = SwpmUtils::_( 'Error! Please select a membership level first.' );
			}

			if ( empty( $error_msg ) ) {//No validation errors so go ahead
				$member_records = SwpmMemberUtils::get_all_members_of_a_level( $from_level_id );
				if ( $member_records ) {
					foreach ( $member_records as $row ) {
						$member_id = $row->member_id;
						SwpmMemberUtils::update_membership_level_and_role( $member_id, $to_level_id );
					}
				}
			}

			$message = '';
			if ( ! empty( $error_msg ) ) {
				$message = $error_msg;
			} else {
				$message = SwpmUtils::_( 'Membership level change operation completed successfully.' );
			}
			echo '<div id="message" class="updated fade"><p><strong>';
			echo $message;
			echo '</strong></p></div>';
		}

		if ( isset( $_REQUEST['swpm_bulk_user_start_date_change_process'] ) ) {
			//Check nonce
			$swpm_bulk_start_date_nonce = filter_input( INPUT_POST, 'swpm_bulk_start_date_nonce' );
			if ( ! wp_verify_nonce( $swpm_bulk_start_date_nonce, 'swpm_bulk_start_date_nonce_action' ) ) {
				//Nonce check failed.
				wp_die( SwpmUtils::_( 'Error! Nonce security verification failed for Bulk Change Access Starts Date action. Clear cache and try again.' ) );
			}

			$error_msg = '';
			$level_id = sanitize_text_field( $_REQUEST['swpm_bulk_user_start_date_change_level'] );
			$new_date = sanitize_text_field( $_REQUEST['swpm_bulk_user_start_date_change_date'] );

			if ( $level_id == 'please_select' ) {
				$error_msg = SwpmUtils::_( 'Error! Please select a membership level first.' );
			}

			if ( empty( $error_msg ) ) {//No validation errors so go ahead
				$member_records = SwpmMemberUtils::get_all_members_of_a_level( $level_id );
				if ( $member_records ) {
					foreach ( $member_records as $row ) {
						$member_id = $row->member_id;
						SwpmMemberUtils::update_access_starts_date( $member_id, $new_date );
					}
				}
			}

			$message = '';
			if ( ! empty( $error_msg ) ) {
				$message = $error_msg;
			} else {
				$message = SwpmUtils::_( 'Access starts date change operation successfully completed.' );
			}
			echo '<div id="message" class="updated fade"><p><strong>';
			echo $message;
			echo '</strong></p></div>';
		}

		if ( isset( $_REQUEST['swpm_bulk_account_status_change_process'] ) ) {
			//Check nonce
			$swpm_bulk_change_level_nonce = filter_input( INPUT_POST, 'swpm_bulk_account_status_change_nonce' );
			if ( ! wp_verify_nonce( $swpm_bulk_change_level_nonce, 'swpm_bulk_account_status_change_nonce_action' ) ) {
				//Nonce check failed.
				wp_die( SwpmUtils::_( 'Error! Nonce security verification failed for Bulk Change Account Status action. Clear cache and try again.' ) );
			}

			$error_msg = '';
			$from_level_id = sanitize_text_field( $_POST['swpm_bulk_account_status_change_level_of'] );
			$to_account_status   = sanitize_text_field( $_POST['swpm_bulk_change_account_status_to'] );

			if ( $from_level_id == 'please_select' ) {
				$error_msg = __( 'Error! Please select a membership level first.', 'simple-membership' );
			}else if($to_account_status == 'please_select' || empty($to_account_status)){
				$error_msg = __( 'Error! Please select a account status.', 'simple-membership' );
			}

			if ( empty( $error_msg ) ) { //No validation errors so go ahead
				SwpmLog::log_simple_debug( 'Updating bulk account status value of membership level ID: ' . $from_level_id . ' to the account status of: ' . $to_account_status, true );
				$member_records = SwpmMemberUtils::get_all_members_of_a_level( $from_level_id );
				if ( $member_records ) {
					foreach ( $member_records as $row ) {
						$member_id = $row->member_id;
						SwpmMemberUtils::update_account_state( $member_id, $to_account_status );
					}
				}
			}

			if ( ! empty( $error_msg ) ) {
				echo '<div id="message" class="notice notice-error"><p><strong>';
				echo $error_msg;
				echo '</strong></p></div>';
			}else{
				echo '<div id="message" class="notice notice-success"><p><strong>';
				_e( 'Account status change operation completed successfully.', 'simple-membership');
				echo '</strong></p></div>';
			}
		}

		if ( isset( $_REQUEST['swpm_bulk_delete_account_process'] ) ) {
			//Check nonce
			$swpm_bulk_delete_account_nonce = filter_input( INPUT_POST, 'swpm_bulk_delete_account_nonce' );
			if ( ! wp_verify_nonce( $swpm_bulk_delete_account_nonce, 'swpm_bulk_delete_account_nonce_action' ) ) {
				//Nonce check failed.
				wp_die( SwpmUtils::_( 'Error! Nonce security verification failed for Bulk Delete Account action. Clear cache and try again.' ) );
			}

			$error_msg = '';

			//Check if the user has selected a membership level.
			$from_level_id = sanitize_text_field( $_POST['swpm_bulk_delete_account_level_of'] );
			if ( $from_level_id == 'please_select' ) {
				$error_msg = __( 'Error! Please select a membership level first.', 'simple-membership' );
			}

			if ( empty( $error_msg ) ) { //No validation errors so go ahead
				SwpmLog::log_simple_debug( 'Executing bulk delete accounts of membership level ID: ' . $from_level_id, true );
				$member_records = SwpmMemberUtils::get_all_members_of_a_level( $from_level_id );
				if ( $member_records ) {
					foreach ( $member_records as $row ) {
						$member_id = $row->member_id;
						SwpmMemberUtils::delete_member_and_wp_user( $member_id );
					}
				}
			}

			if ( ! empty( $error_msg ) ) {
				echo '<div id="message" class="notice notice-error"><p><strong>';
				echo $error_msg;
				echo '</strong></p></div>';
			}else{	
				echo '<div id="message" class="notice notice-success"><p><strong>';
				_e( 'Bulk delete of member accounts completed successfully.', 'simple-membership');
				echo '</strong></p></div>';
			}
		}		

		if ( isset( $_REQUEST['swpm_bulk_delete_account_status_process'] ) ) {
			//Check nonce
			$swpm_bulk_delete_account_by_status_nonce = filter_input( INPUT_POST, 'swpm_bulk_delete_account_by_status_nonce' );
			if ( ! wp_verify_nonce( $swpm_bulk_delete_account_by_status_nonce, 'swpm_bulk_delete_account_by_status_nonce_action' ) ) {
				//Nonce check failed.
				wp_die( SwpmUtils::_( 'Error! Nonce security verification failed for Bulk Delete Account by Status action. Clear cache and try again.' ) );
			}

			$error_msg = '';

			//Check if the user has selected a membership level.
			$from_status = sanitize_text_field( $_POST['swpm_bulk_delete_account_status_of'] );
			if ( $from_status == 'please_select' ) {
				$error_msg = __( 'Error! Please select a account status first.', 'simple-membership' );
			}

			if ( empty( $error_msg ) ) { //No validation errors so go ahead
				SwpmLog::log_simple_debug( 'Executing bulk delete accounts of status: ' . $from_status, true );
				$member_records = SwpmMemberUtils::get_all_members_of_account_status( $from_status );
				if ( $member_records ) {
					foreach ( $member_records as $row ) {
						$member_id = $row->member_id;
						SwpmMemberUtils::delete_member_and_wp_user( $member_id );
					}
				}
			}

			if ( ! empty( $error_msg ) ) {
				echo '<div id="message" class="notice notice-error"><p><strong>';
				echo $error_msg;
				echo '</strong></p></div>';
			}else{
				echo '<div id="message" class="notice notice-success"><p><strong>';
				_e( 'Bulk delete of member accounts by status completed successfully.', 'simple-membership');
				echo '</strong></p></div>';
			}
		}
		?>

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e( 'Bulk Update Membership Level of Members', 'simple-membership' ); ?></label></h3>
			<div class="inside">
				<p>
					<?php _e( 'You can manually change the membership level of any member by editing the record from the members menu. ', 'simple-membership' ); ?>
					<?php _e( 'You can use the following option to bulk update the membership level of users who belong to the level you select below.', 'simple-membership' ); ?>
				</p>
				<form method="post" action="">
					<input type="hidden" name="swpm_bulk_change_level_nonce" value="<?php echo wp_create_nonce( 'swpm_bulk_change_level_nonce_action' ); ?>" />

					<table width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Membership Level: ', 'simple-membership' ); ?></strong>
							</td>
							<td align="left">
								<select name="swpm_bulk_change_level_from">
									<option value="please_select"><?php _e( 'Select Current Level', 'simple-membership' ); ?></option>
									<?php echo SwpmUtils::membership_level_dropdown(); ?>
								</select>
								<p class="description"><?php _e( 'Select the current membership level (the membership level of all members who are in this level will be updated).', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Level to Change to: ', 'simple-membership' ); ?></strong>
							</td>
							<td align="left">
								<select name="swpm_bulk_change_level_to">
									<option value="please_select"><?php _e( 'Select Target Level', 'simple-membership' ); ?></option>
									<?php echo SwpmUtils::membership_level_dropdown(); ?>
								</select>
								<p class="description"><?php _e( 'Select the new membership level.', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<input type="submit" class="button" name="swpm_bulk_change_level_process" value="<?php _e( 'Bulk Change Membership Level', 'simple-membership' ); ?>" />
							</td>
							<td align="left"></td>
						</tr>

					</table>
				</form>
			</div>
		</div>

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e( 'Bulk Update Access Starts Date of Members', 'simple-membership' ); ?></label></h3>
			<div class="inside">

				<p>
					<?php _e( 'The access starts date of a member is set to the day the user registers. This date value is used to calculate how long the member can access your content that are protected with a duration type protection in the membership level. ', 'simple-membership' ); ?>
					<?php _e( 'You can manually set a specific access starts date value of all members who belong to a particular level using the following option.', 'simple-membership' ); ?>
				</p>
				<form method="post" action="">
					<input type="hidden" name="swpm_bulk_start_date_nonce" value="<?php echo wp_create_nonce( 'swpm_bulk_start_date_nonce_action' ); ?>" />

					<table width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Membership Level: ', 'simple-membership' ); ?></strong>
							</td><td align="left">
								<select name="swpm_bulk_user_start_date_change_level">
									<option value="please_select"><?php _e( 'Select Level', 'simple-membership' ); ?></option>
									<?php echo SwpmUtils::membership_level_dropdown(); ?>
								</select>
								<p class="description"><?php _e( 'Select the Membership level (the access start date of all members who are in this level will be updated).', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Access Starts Date: ', 'simple-membership' ); ?></strong>
							</td><td align="left">
								<input name="swpm_bulk_user_start_date_change_date" id="swpm_bulk_user_start_date_change_date" class="swpm-select-date" type="text" size="20" value="<?php echo ( date( 'Y-m-d' ) ); ?>" />
								<p class="description"><?php _e( 'Specify the Access Starts date value.', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<input type="submit" class="button" name="swpm_bulk_user_start_date_change_process" value="<?php _e( 'Bulk Change Access Starts Date', 'simple-membership' ); ?>" />
							</td>
							<td align="left"></td>
						</tr>

					</table>
				</form>
			</div>
		</div>

		<script>
			jQuery(document).ready(function ($) {
				$('#swpm_bulk_user_start_date_change_date').datepicker({dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, yearRange: "-100:+100"});
			});
		</script>

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e( 'Bulk Update Account Status of Members', 'simple-membership' ); ?></label></h3>
			<div class="inside">
				<p>
					<?php _e( 'You can manually change the account status of any member by editing the record from the members menu. You can use the following option to bulk update the account status of users who belong to the level you select below.', 'simple-membership' ); ?>
				</p>
				<form method="post" action="">
					<input type="hidden" name="swpm_bulk_account_status_change_nonce" value="<?php echo wp_create_nonce( 'swpm_bulk_account_status_change_nonce_action' ); ?>" />

					<table width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Membership Level: ', 'simple-membership' ); ?></strong>
							</td>
							<td align="left">
								<select name="swpm_bulk_account_status_change_level_of">
									<option value="please_select"><?php _e( 'Select Level', 'simple-membership' ); ?></option>
									<?php echo SwpmUtils::membership_level_dropdown(); ?>
								</select>
								<p class="description"><?php _e( 'Select the Membership level (the account status of all members who are in this level will be updated).', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Account Status to Change to: ', 'simple-membership' ); ?></strong>
							</td>
							<td align="left">
								<select name="swpm_bulk_change_account_status_to">
									<option value="please_select"><?php _e( 'Select Target Status', 'simple-membership' ); ?></option>
									<?php echo SwpmUtils::account_state_dropdown('please_select'); ?>
								</select>
								<p class="description"><?php _e( 'Select the new account status.', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<input type="submit" class="button" name="swpm_bulk_account_status_change_process" value="<?php _e( 'Bulk Change Account Status', 'simple-membership' ); ?>" />
							</td>
							<td align="left"></td>
						</tr>

					</table>
				</form>
			</div>
		</div>

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e( 'Bulk Delete Member Accounts', 'simple-membership' ); ?></label></h3>
			<div class="inside">
				<p>
					<?php _e( 'This option allows you to bulk delete all members from a selected level, including their associated WordPress user records. ', 'simple-membership' ); ?>
					<?php _e('The WP user record will be deleted only if the user is not an administrator user.', 'simple-membership'); ?>
				</p>
				<form method="post" action="">
					<input type="hidden" name="swpm_bulk_delete_account_nonce" value="<?php echo wp_create_nonce( 'swpm_bulk_delete_account_nonce_action' ); ?>" />

					<table width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr valign="top">
							<td width="25%" align="left">
								<strong><?php _e( 'Membership Level: ', 'simple-membership' ); ?></strong>
							</td>
							<td align="left">
								<select name="swpm_bulk_delete_account_level_of">
									<option value="please_select"><?php _e( 'Select Level', 'simple-membership' ); ?></option>
									<?php echo SwpmUtils::membership_level_dropdown(); ?>
								</select>
								<p class="description"><?php _e( 'Select the Membership level (the accounts of all members who are in this level will be deleted).', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<td width="25%" align="left">
								<input type="submit" class="button" style="color:red;" name="swpm_bulk_delete_account_process" value="<?php _e( 'Bulk Delete Member Accounts', 'simple-membership' ); ?>" onclick="return confirm('Are you sure you want to bulk delete all member accounts with the selected membership level?');" />
							</td>
							<td align="left"></td>
						</tr>

					</table>
				</form>
			</div>
		</div>

        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e( 'Bulk Delete Member Accounts By Status', 'simple-membership' ); ?></label></h3>
            <div class="inside">
                <p>
					<?php _e( 'This option enables you to bulk delete all members with a specific account status, including their associated WordPress user records. ', 'simple-membership' ); ?>
					<?php _e( 'The WP user record will be deleted only if the user is not an administrator user.', 'simple-membership' ); ?>
                </p>
                <form method="post" action="">
                    <input type="hidden" name="swpm_bulk_delete_account_by_status_nonce" value="<?php echo wp_create_nonce( 'swpm_bulk_delete_account_by_status_nonce_action' ); ?>" />

                    <table width="100%" border="0" cellspacing="0" cellpadding="6">
                        <tr valign="top">
                            <td width="25%" align="left">
                                <strong><?php _e( 'Account Status: ', 'simple-membership' ); ?></strong>
                            </td>
                            <td align="left">
                                <select name="swpm_bulk_delete_account_status_of">
                                    <option value="please_select"><?php _e( 'Select Status', 'simple-membership' ); ?></option>
	                                <?php echo SwpmUtils::account_state_dropdown('please_select'); ?>
                                </select>
                                <p class="description"><?php _e( 'Select the Account Status (the accounts of all members with this account status will be deleted).', 'simple-membership' ); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <td width="25%" align="left">
                                <input type="submit" class="button" style="color:red;" name="swpm_bulk_delete_account_status_process" value="<?php _e( 'Bulk Delete Member Accounts', 'simple-membership' ); ?>" onclick="return confirm('Are you sure you want to bulk delete all member accounts with the selected account status?');" />
                            </td>
                            <td align="left"></td>
                        </tr>

                    </table>
                </form>
            </div>
        </div>


		<?php
		echo '</div></div>'; //<!-- end of #poststuff #post-body -->
	}

	function show_all_members()
	{
		ob_start();
		$status = filter_input(INPUT_GET, 'status');
		include_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_members_list.php';
		$output = ob_get_clean();
		return $output;
	}

	public function set_default_editor($r)
	{
		$r = 'html';
		return $r;
	}

	function handle_main_members_admin_menu()
	{
		do_action('swpm_members_menu_start');

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin('Main Members Admin Menu');

		$action = filter_input(INPUT_GET, 'member_action');
		$action = empty($action) ? filter_input(INPUT_POST, 'action') : $action;
		$selected = $action;
		?>
		<div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

		<h1><?php _e('Members'); ?><!-- page title -->
			<a href="admin.php?page=simple_wp_membership&member_action=add"
			   class="add-new-h2"><?php echo SwpmUtils::_('Add New'); ?></a>
		</h1>

		<h2 class="nav-tab-wrapper swpm-members-nav-tab-wrapper"><!-- start nav menu tabs -->
			<a class="nav-tab <?php echo ($selected == '') ? 'nav-tab-active' : ''; ?>"
			   href="admin.php?page=simple_wp_membership"><?php echo SwpmUtils::_('Members'); ?></a>
			<a class="nav-tab <?php echo ($selected == 'add') ? 'nav-tab-active' : ''; ?>"
			   href="admin.php?page=simple_wp_membership&member_action=add"><?php echo SwpmUtils::_('Add Member'); ?></a>
			<a class="nav-tab <?php echo ($selected == 'bulk') ? 'nav-tab-active' : ''; ?>"
			   href="admin.php?page=simple_wp_membership&member_action=bulk"><?php echo SwpmUtils::_('Bulk Operation'); ?></a>
			<a class="nav-tab <?php echo ($selected == 'send_direct_email') ? 'nav-tab-active' : ''; ?>"
			   href="admin.php?page=simple_wp_membership&member_action=send_direct_email"><?php echo SwpmUtils::_('Send Direct Email'); ?></a>
			<?php
			if ($selected == 'edit') {//Only show the "edit member" tab when a member profile is being edited from the admin side.
				echo '<a class="nav-tab nav-tab-active" href="#">Edit Member</a>';
			}

			//Trigger hooks that allows an extension to add extra nav tabs in the members menu.
			do_action('swpm_members_menu_nav_tabs', $selected);

			$menu_tabs = apply_filters('swpm_members_additional_menu_tabs_array', array());
			foreach ($menu_tabs as $member_action => $title) {
					?>
					<a class="nav-tab <?php echo ( $selected == $member_action ) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership&member_action=<?php echo $member_action; ?>" ><?php _e( $title, 'simple-membership' ); ?></a>
					<?php
				}
				?>
			</h2><!-- end nav menu tabs -->
			<?php
			do_action( 'swpm_members_menu_after_nav_tabs' );

			//Trigger hook so anyone listening for this particular action can handle the output.
			do_action( 'swpm_members_menu_body_' . $action );

			//Allows an addon to completely override the body section of the members admin menu for a given action.
			$output = apply_filters( 'swpm_members_menu_body_override', '', $action );
			if ( ! empty( $output ) ) {
				//An addon has overriden the body of this page for the given action. So no need to do anything in core.
				echo $output;
				echo '</div>'; //<!-- end of wrap -->
				return;
			}

			//Switch case for the various different actions handled by the core plugin.
			switch ( $action ) {
				case 'members_list':
					//Show the members listing
					echo $this->show_all_members();
					break;
				case 'add':
					//Process member profile add
					$this->process_form_request();
					break;
				case 'edit':
					//Process member profile edit
					$this->process_form_request();
					break;
				case 'bulk':
					//Handle the bulk operation menu
					$this->bulk_operation_menu();
					break;
				case 'send_direct_email':
					//Handle the send email operation menu
					include_once(SIMPLE_WP_MEMBERSHIP_PATH . "classes/admin-includes/class.swpm-send-direct-email-menu.php");
					$send_direct_email_menu = new SWPM_Send_Direct_Email_Menu();
					$send_direct_email_menu->handle_send_direct_email_menu();
					break;
				default:
					//Show the members listing page by default.
					echo $this->show_all_members();
					break;
			}

			echo '</div>'; //<!-- end of wrap -->
	}

}

