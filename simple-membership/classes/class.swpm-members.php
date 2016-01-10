<?php

include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/common/class.swpm-list-table.php');

class SwpmMembers extends SWPM_List_Table {

    function __construct() {
        parent::__construct(array(
            'singular' => SwpmUtils::_('Member'),
            'plural' => SwpmUtils::_('Members'),
            'ajax' => false
        ));
    }

    function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />'
            , 'member_id' => SwpmUtils::_('ID')
            , 'user_name' => SwpmUtils::_('Username')
            , 'first_name' => SwpmUtils::_('First Name')
            , 'last_name' => SwpmUtils::_('Last Name')
            , 'email' => SwpmUtils::_('Email')
            , 'alias' => SwpmUtils::_('Membership Level')
            , 'subscription_starts' => SwpmUtils::_('Access Starts')
            , 'account_state' => SwpmUtils::_('Account State')
        );
    }

    function get_sortable_columns() {
        return array(
            'member_id' => array('member_id', true),//True means already sorted
            'user_name' => array('user_name', false),
            'email' => array('email', false),
            'alias' => array('alias', false),
            'account_state' => array('account_state', false),
        );
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk_delete' => SwpmUtils::_('Delete'),
            'bulk_active' => SwpmUtils::_('Set Status to Active'),
            /*'bulk_active_notify' => SwpmUtils::_('Set Status to Active and Notify'),*/
            'bulk_inactive' => SwpmUtils::_('Set Status to Inactive'),
            'bulk_pending' => SwpmUtils::_('Set Status to Pending'),
            'bulk_expired' => SwpmUtils::_('Set Status to Expired'),            
        );
        return $actions;
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_member_id($item) {
        $actions = array(
            'edit' => sprintf('<a href="admin.php?page=%s&member_action=edit&member_id=%s">Edit</a>', $_REQUEST['page'], $item['member_id']),
            'delete' => sprintf('<a href="?page=%s&member_action=delete&member_id=%s"
                                    onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>', $_REQUEST['page'], $item['member_id']),
        );
        return $item['member_id'] . $this->row_actions($actions);
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="members[]" value="%s" />', $item['member_id']
        );
    }

    function prepare_items() {
        global $wpdb;

        $this->process_bulk_action();

        $query = "SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl";
        $query .= " LEFT JOIN " . $wpdb->prefix . "swpm_membership_tbl";
        $query .= " ON ( membership_level = id ) ";
        $s = filter_input(INPUT_POST, 's');
        if (!empty($s)) {
            $query .= " WHERE  user_name LIKE '%" . strip_tags($s) . "%' "
                    . " OR first_name LIKE '%" . strip_tags($s) . "%' "
                    . " OR last_name LIKE '%" . strip_tags($s) . "%' "
                    . " OR email LIKE '%" . strip_tags($s) . "%' "
                    . " OR address_city LIKE '%" . strip_tags($s) . "%' "
                    . " OR address_state LIKE '%" . strip_tags($s) . "%' "
                    . " OR country LIKE '%" . strip_tags($s) . "%' "
                    . " OR company_name LIKE '%" . strip_tags($s) . "%' ";
        }
        $orderby = filter_input(INPUT_GET, 'orderby');
        $orderby = empty($orderby) ? 'member_id' : $orderby;
        $order = filter_input(INPUT_GET, 'order');
        $order = empty($order) ? 'DESC' : $order;

        $sortable_columns = $this->get_sortable_columns();
        $orderby = SwpmUtils::sanitize_value_by_array($orderby, $sortable_columns);
        $order = SwpmUtils::sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));

        $query.=' ORDER BY ' . $orderby . ' ' . $order;
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        $perpage = 20;
        $paged = filter_input(INPUT_GET, 'paged');
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $wpdb->get_results($query, ARRAY_A);
    }

    function no_items() {
        _e('No Member found.');
    }

    function process_form_request() {
        if (isset($_REQUEST['member_id'])){
            return $this->edit(absint($_REQUEST['member_id']));
        }
        return $this->add();
    }

    function add() {
        $form = apply_filters('swpm_admin_registration_form_override', '');
        if (!empty($form)) {
            echo $form;
            return;
        }
        global $wpdb;
        $member = SwpmTransfer::$default_fields;
        $member['member_since'] = date('Y-m-d');
        $member['subscription_starts'] = date('Y-m-d');
        if (isset($_POST['createswpmuser'])) {
            $member = $_POST;
        }
        extract($member, EXTR_SKIP);
        $query = "SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE  id !=1 ";
        $levels = $wpdb->get_results($query, ARRAY_A);
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_add.php');
        return false;
    }

    function edit($id) {
        global $wpdb;
        $id = absint($id);
        $query = "SELECT * FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = $id";
        $member = $wpdb->get_row($query, ARRAY_A);
        if (isset($_POST["editswpmuser"])) {
            $_POST['user_name'] = $member['user_name'];
            $_POST['email'] = $member['email'];
            foreach($_POST as $key=>$value){
                $member[$key] = $value;
            }
        }
        extract($member, EXTR_SKIP);
        $query = "SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE  id !=1 ";
        $levels = $wpdb->get_results($query, ARRAY_A);
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_edit.php');
        return false;
    }

    function process_bulk_action() {
        //Detect when a bulk action is being triggered... 
        $members = isset($_REQUEST['members'])? $_REQUEST['members']: array();
        $current_action = $this->current_action();
        if ('bulk_delete' === $current_action) {            
            if (empty($members)) {
                echo '<div id="message" class="updated fade"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
                return;
            }
            foreach ($members as $record_id) {
                SwpmMembers::delete_user_by_id($record_id);
            }
            echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
        }
        else if ('bulk_active' === $current_action){
            $this->bulk_set_status($members, 'active');
        }
        else if ('bulk_active_notify' == $current_action){
            $this->bulk_set_status($members, 'active', true);
        }
        else if ('bulk_inactive' == $current_action){
            $this->bulk_set_status($members, 'inactive');
        }
        else if ('bulk_pending' == $current_action){
            $this->bulk_set_status($members, 'pending');
        }
        else if ('bulk_expired' == $current_action){
            $this->bulk_set_status($members, 'expired');
        }        
    }
    
    function bulk_set_status($members, $status, $notify = false ){
        $ids = implode(',', array_map('absint', $members));
        if (empty($ids)) {return;}
        global $wpdb;   
        $query = "UPDATE " . $wpdb->prefix . "swpm_members_tbl " .
                " SET account_state = '" . $status . "' WHERE member_id in (" . $ids . ")";
        $wpdb->query($query);        
        
        if ($notify){
            // todo: add notification
        }
    }
    
    function delete() {
        if (isset($_REQUEST['member_id'])) {
            $id = absint($_REQUEST['member_id']);
            SwpmMembers::delete_user_by_id($id);
        }
    }

    public static function delete_user_by_id($id) {
        $swpm_user = SwpmMemberUtils::get_user_by_id($id);
        $user_name = $swpm_user->user_name;
        SwpmMembers::delete_wp_user($user_name);//Deletes the WP User record
        SwpmMembers::delete_swpm_user_by_id($id);//Deletes the SWPM record
    }

    public static function delete_swpm_user_by_id($id) {
        global $wpdb;
        $query = "DELETE FROM " . $wpdb->prefix . "swpm_members_tbl WHERE member_id = $id";
        $wpdb->query($query);
    }

    function show() {
        ob_start();
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_members_list.php');
        $output = ob_get_clean();
        return $output;
    }

    public static function delete_wp_user($user_name) {
        $wp_user_id = username_exists($user_name);
        $ud = get_userdata($wp_user_id);
        if (!empty($ud) && (isset($ud->wp_capabilities['administrator']) || $ud->wp_user_level == 10)) {
            SwpmTransfer::get_instance()->set('status', 'For consistency, we do not allow deleting any associated wordpress account with administrator role.<br/>'
                    . 'Please delete from <a href="users.php">Users</a> menu.');
            return;
        }
        if ($wp_user_id) {
            include_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($wp_user_id, 1); //assigns all related to this user to admin.
        }
    }

}
