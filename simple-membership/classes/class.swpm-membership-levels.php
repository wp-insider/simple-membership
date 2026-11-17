<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if (!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SwpmMembershipLevels extends WP_List_Table {

    function __construct() {
        parent::__construct(array(
            'singular' => SwpmUtils::_('Membership Level'),
            'plural' => esc_html__( 'Membership Levels', 'simple-membership' ),
            'ajax' => false
        ));
    }

    function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />'
            , 'id' => SwpmUtils::_('ID')
            , 'alias' => SwpmUtils::_('Membership Level')
            , 'role' => SwpmUtils::_('Role')
            , 'valid_for' => SwpmUtils::_('Access Valid For/Until')
        );
    }

    function get_sortable_columns() {
        return array(
            'id' => array('id', true),
            'alias' => array('alias', true)
        );
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk_delete' => SwpmUtils::_('Delete')
        );
        return $actions;
    }

    function column_default($item, $column_name) {
        if ($column_name == 'valid_for') {
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::NO_EXPIRY) {
                return 'No Expiry';
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::FIXED_DATE) {
                $formatted_date = SwpmUtils::get_formatted_date_according_to_wp_settings($item['subscription_period']);
                return $formatted_date;
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::DAYS) {
                return $item['subscription_period'] . " Day(s)";
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::WEEKS) {
                return $item['subscription_period'] . " Week(s)";
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::MONTHS) {
                return $item['subscription_period'] . " Month(s)";
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::YEARS) {
                return $item['subscription_period'] . " Year(s)";
            }
	        if ($item['subscription_duration_type'] == SwpmMembershipLevel::ANNUAL_FIXED_DATE) {
		        $formatted_date = gmdate('F d', strtotime($item['subscription_period']));
		        return $formatted_date;
	        }
        }
        if ($column_name == 'role') {
            return ucfirst($item['role']);
        }
        return stripslashes($item[$column_name]);
    }

    function column_id($item) {
        $delete_swpmlevel_nonce = wp_create_nonce( 'nonce_delete_swpmlevel_admin_end' );

        $actions = array(
            'edit' => sprintf('<a href="admin.php?page=simple_wp_membership_levels&level_action=edit&id=%s">Edit</a>', $item['id']),
            'delete' => sprintf('<a href="admin.php?page=simple_wp_membership_levels&level_action=delete&id=%s&delete_swpmlevel_nonce=%s" onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>', $item['id'],$delete_swpmlevel_nonce),
        );
        return $item['id'] . $this->row_actions($actions);
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="ids[]" value="%s" />', $item['id']
        );
    }

    function prepare_items() {
        global $wpdb;

        $this->process_bulk_action();

        $search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'id';
        $sortable = $this->get_sortable_columns();
        $orderby = isset( $sortable[ $orderby ] ) ? $orderby : 'id';
        $order = isset( $_GET['order'] ) && strtoupper( sanitize_text_field( $_GET['order'] ) ) === 'ASC' ? 'ASC' : 'DESC';
        $totalitems = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}swpm_membership_tbl WHERE id != 1 AND (%s = '' OR alias LIKE %s)",
            $search, $like
        ) );
        $perpage = 50;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $this->set_pagination_args( array(
            'total_items' => $totalitems, 'total_pages' => ceil( $totalitems / $perpage ), 'per_page' => $perpage,
        ) );
        $this->_column_headers = array( $this->get_columns(), array(), $sortable );
        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE id != 1 AND (%s = '' OR alias LIKE %s) ORDER BY " . esc_sql( $orderby ) . ' ' . esc_sql( $order ) . ' LIMIT %d OFFSET %d',
            $search, $like, $perpage, ( $paged - 1 ) * $perpage
        ), ARRAY_A );
    }

    function no_items() {
        esc_attr_e('No membership levels found.', 'simple-membership');
    }

    function process_form_request() {
        if (isset($_REQUEST['id'])) {
            //This is a level edit action
            $record_id = sanitize_text_field($_REQUEST['id']);
            if(!is_numeric($record_id)){
                wp_die( wp_kses_post( 'Error! ID must be numeric.' ));
            }
            return $this->edit($record_id);
        }

        //Level add action
        return $this->add();
    }

    function add() {
        //Level add interface
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_add_level.php');
        return false;
    }

    function edit($id) {
        global $wpdb;
        
        $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE id = %d", absint($id)), ARRAY_A);
        extract($membership, EXTR_SKIP);
        $email_activation = get_option('swpm_email_activation_lvl_'.$id);
        $custom_fields = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $after_activation_redirect_page = sanitize_url($custom_fields->get('after_activation_redirect_page'));
        $default_account_status = sanitize_text_field($custom_fields->get('default_account_status'));
	    $annual_fixed_date_min_period = sanitize_text_field($custom_fields->get('annual_fixed_date_min_period'));
        $role_after_expiry = sanitize_text_field($custom_fields->get('role_after_expiry'));
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_edit_level.php');
        return false;
    }

    function process_bulk_action() {
        //Detect when a bulk action is being triggered...
        global $wpdb;

        if ('bulk_delete' === $this->current_action()) {
            $records_to_delete = array_map( 'sanitize_text_field', $_REQUEST['ids'] );
            if (empty($records_to_delete)) {
                echo '<div id="message" class="updated fade"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
                return;
            }

            $action = 'bulk-' . $this->_args['plural'];
            check_admin_referer( $action );

            foreach ($records_to_delete as $record_id) {
                if( !is_numeric( $record_id )){
                    wp_die( wp_kses_post( 'Error! ID must be numeric.' ));
                }
                
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id = %d", $record_id));
            }
            echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
        }
    }

    function delete_level() {
        global $wpdb;
        if (isset($_REQUEST['id'])) {

            //Check we are on the admin end and user has management permission
            SwpmMiscUtils::check_user_permission_and_is_admin('membership level delete');

            //Check nonce
            if ( !isset($_REQUEST['delete_swpmlevel_nonce']) || !wp_verify_nonce($_REQUEST['delete_swpmlevel_nonce'], 'nonce_delete_swpmlevel_admin_end' )){
                //Nonce check failed.
                wp_die(esc_html__( "Error! Nonce verification failed for membership level delete from admin end.", 'simple-membership' ));
            }

            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);
            
            $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id = %d", $id));
            echo '<div id="message" class="updated fade"><p>Selected record deleted successfully!</p></div>';
        }
    }

    function show_levels() {
        ?>
        <div class="swpm-margin-top-10"></div>
        <form method="post">
            <p class="search-box">
                <label class="screen-reader-text" for="search_id-search-input">
                    search:</label>
                <input id="search_id-search-input" type="text" name="s" value="" />
                <input id="search-submit" class="button" type="submit" name="" value="<?php echo  esc_html__( 'Search', 'simple-membership' )?>" />
            </p>
        </form>

        <?php $this->prepare_items(); ?>
        <form method="post">
            <?php $this->display(); ?>
        </form>

        <p>
            <a href="admin.php?page=simple_wp_membership_levels&level_action=add" class="button-primary"><?php esc_attr_e('Add New', 'simple-membership') ?></a>
        </p>
        <?php
    }

    function manage() {
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_membership_manage.php');
    }

    function manage_categroy() {
        $selected = "category_list";
        include_once('class.swpm-category-list.php');
        $category_list = new SwpmCategoryList();
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_category_list.php');
    }

    function manage_post() {
        $selected = "post_list";
        include_once('class.swpm-post-list.php');
        $post_list = new SwpmPostList();
        include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_post_list.php');
    }

    function handle_main_membership_level_admin_menu(){
        do_action( 'swpm_membership_level_menu_start' );

        //Check current_user_can() or die.
        SwpmMiscUtils::check_user_permission_and_is_admin('Main Membership Level Admin Menu');

        $level_action = isset($_GET['level_action']) ? sanitize_text_field($_GET['level_action']) : '';
        $action = $level_action;
        $selected= $action;

        ?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

        <!-- page title -->
        <h1><?php esc_attr_e('Membership Levels', 'simple-membership') ?></h1>

        <!-- start nav menu tabs -->
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ($selected == "") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels"><?php echo esc_html__( 'Membership Levels', 'simple-membership' ) ?></a>
            <a class="nav-tab <?php echo ($selected == "add") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=add"><?php echo esc_html__( 'Add Level', 'simple-membership' ) ?></a>
            <a class="nav-tab <?php echo ($selected == "manage") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=manage"><?php echo esc_html__( 'Manage Content Protection', 'simple-membership' ) ?></a>
            <a class="nav-tab <?php echo ($selected == "category_list") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=category_list"><?php echo esc_html__( 'Category Protection', 'simple-membership' ) ?></a>
            <a class="nav-tab <?php echo ($selected == "post_list") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=post_list"><?php echo esc_html__( 'Post and Page Protection', 'simple-membership' ) ?></a>
            <?php

            //Trigger hooks that allows an extension to add extra nav tabs in the membership levels menu.
            do_action ('swpm_membership_levels_menu_nav_tabs', $selected);

            $menu_tabs = apply_filters('swpm_membership_levels_additional_menu_tabs_array', array());
            foreach ($menu_tabs as $level_action => $title){
                ?>
                <a class="nav-tab <?php echo ($selected == $level_action) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=<?php echo esc_attr($level_action); ?>" ><?php echo esc_attr( $title ); ?></a>
                <?php
            }

            ?>
        </h2>
        <!-- end nav menu tabs -->

        <?php

        do_action( 'swpm_membership_level_menu_after_nav_tabs' );

        //Trigger hook so anyone listening for this particular action can handle the output.
        do_action( 'swpm_membership_level_menu_body_' . $action );

        //Allows an addon to completely override the body section of the membership level admin menu for a given action.
        $output = apply_filters('swpm_membership_level_menu_body_override', '', $action);
        if (!empty($output)) {
            //An addon has overriden the body of this page for the given action. So no need to do anything in core.
            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters.
            echo '</div>';//<!-- end of wrap -->
            return;
        }

        //Switch case for the various different actions handled by the core plugin.
        switch ($action) {
            case 'add':
            case 'edit':
                $this->process_form_request();
                break;
            case 'manage':
                $this->manage();
                break;
            case 'category_list':
                $this->manage_categroy();
                break;
            case 'post_list':
                $this->manage_post();
                break;
            case 'delete':
                $this->delete_level();
            default:
                $this->show_levels();
                break;
        }

        echo '</div>';//<!-- end of wrap -->
    }

}
