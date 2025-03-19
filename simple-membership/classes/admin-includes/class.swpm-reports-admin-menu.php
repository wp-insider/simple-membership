<?php

class SwpmReportsAdminMenu {

	public function __construct() {
		wp_enqueue_script( 'swpm-charts-api', 'https://www.gstatic.com/charts/loader.js', null, SIMPLE_WP_MEMBERSHIP_VER, array(
            'strategy' => 'defer',
            'in_footer' => true
        ) );
		wp_enqueue_script( 'swpm-stats', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm-stats.js', array('swpm-charts-api'), SIMPLE_WP_MEMBERSHIP_VER, array(
			'strategy' => 'defer',
			'in_footer' => true
		) );
	}

	public function handle_reports_admin_menu() {
		do_action( 'swpm_reports_menu_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Reports Admin Menu' );

		$output   = '';
		$tab      = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		$selected = $tab;
		?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

        <h1><?php _e( 'Reports' , 'simple-membership') ?></h1><!-- page title -->

        <!-- start nav menu tabs -->
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ( $tab == '' || $tab == 'member-logins' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports"><?php _e( 'Member Logins', 'simple-membership' ); ?></a>
            <a class="nav-tab <?php echo ( $tab == 'members' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports&tab=members"><?php _e( 'Members', 'simple-membership' ); ?></a>
            <a class="nav-tab <?php echo ( $tab == 'membership-level' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports&tab=membership-level"><?php _e( 'Membership Levels', 'simple-membership' ); ?></a>
			<?php

			//Trigger hooks that allows an extension to add extra nav tabs in the payments menu.
			do_action( 'swpm_reports_menu_nav_tabs', $selected );

			$menu_tabs = apply_filters( 'swpm_reports_menu_additional_menu_tabs_array', array() );
			foreach ( $menu_tabs as $menu_action => $title ) {
				?>
                <a class="nav-tab <?php echo ( $selected == $menu_action ) ? 'nav-tab-active' : ''; ?>"
                   href="admin.php?page=simple_wp_membership_payments&tab=<?php echo $menu_action; ?>"><?php _e( $title, 'simple-membership' ); ?></a>
				<?php
			}
			?>
        </h2>
        <!-- end nav menu tabs -->

		<?php

		do_action( 'swpm_reports_menu_after_nav_tabs' );

		//Allows an addon to completely override the body section of the payments admin menu for a given action.
		$output = apply_filters( 'swpm_reports_menu_body_override', '', $tab );
		if ( ! empty( $output ) ) {
			//An addon has overriden the body of this page for the given tab/action. So no need to do anything in core.
			echo $output;
			echo '</div>';//<!-- end of wrap -->

			return;
		}

		echo '<div id="poststuff"><div id="post-body">';

		//Switch case for the various different tabs handled by the core plugin.
		switch ( $tab ) {
			case 'membership-level':
				include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-membership-level-report-menu-tab.php');
				$migration_tools_tab = new SWPM_Membership_Level_Report_Menu_Tab();
				$migration_tools_tab->handle_menu_tab();
				break;
            case 'members':
                include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-members-report-menu-tab.php');
                $migration_tools_tab = new SWPM_Members_Report_Menu_Tab();
                $migration_tools_tab->handle_menu_tab();
				break;
            case 'member-logins':
			default:
                include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-member-logins-report-menu-tab.php');
                $migration_tools_tab = new SWPM_Member_Logins_Report_Menu_Tab();
                $migration_tools_tab->handle_menu_tab();
				break;
		}

		echo '</div></div>'; //<!-- end of post-body -->

		echo '</div>'; //<!-- end of .wrap -->
	}


    public static function date_range_selector($stats_name = '', $start_date = 'Y-m-d', $end_date = 'Y-m-d') {
	    $start_date = date($start_date);
	    $end_date = date($end_date);

        $form_name = 'swpm_date_range_form';
        if (!empty($stats_name)){
	        $form_name .= '_' . $stats_name;
        }

	    if ( isset( $_POST[$form_name] )){
		    if ( isset( $_POST[$form_name]['start_date'] ) && !empty( $_POST[$form_name]['start_date'] ) ){
			    $start_date = $_POST[$form_name]['start_date'];
		    }

		    if ( isset( $_POST[$form_name]['end_date'] ) && !empty( $_POST[$form_name]['end_date'] ) ){
			    $end_date = $_POST[$form_name]['end_date'];
		    }
	    }

        $output = '';
        ob_start();
        ?>
        <form action="" method="post" class="swpm_report_date_range_form">
            <div>
                <label for="swpm_date_range_start"><?php _e('Start Date', 'simple-membership') ?></label><br>
                <input type="date" name="<?php esc_attr_e($form_name);?>[start_date]" id="swpm_date_range_start" value="<?php esc_attr_e($start_date);?>">
            </div>
            <div>
                <label for="swpm_date_range_end"><?php _e('End Date', 'simple-membership') ?></label><br>
                <input type="date" name="<?php esc_attr_e($form_name);?>[end_date]" id="swpm_date_range_end" value="<?php esc_attr_e($end_date);?>">
            </div>
            <div>
                <input type="submit" class="button button-secondary" value="<?php _e('Apply', 'simple-membership');?>">
            </div>
        </form>
        <?php
	    $output .= ob_get_clean();

        return array(
            date("Y-m-d H:i:s", strtotime($start_date)),
            date("Y-m-d 11:59:59", strtotime($end_date)),
            $output,
        );
    }

    public static function get_date_format() {
        return get_option('date_format');
    }

    public static function get_time_format() {
        return get_option('time_format');
    }
}

