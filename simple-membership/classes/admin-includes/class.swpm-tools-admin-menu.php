<?php

class SwpmToolsAdminMenu {

	public function __construct() {

	}

	public function handle_tools_admin_menu() {
		do_action( 'swpm_tools_menu_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Tools Admin Menu' );

		$output   = '';
		$tab      = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		$selected = $tab;
		?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

        <h1><?php _e( 'Tools' , 'simple-membership') ?></h1><!-- page title -->

        <!-- start nav menu tabs -->
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ( $tab == '' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_tools"><?php _e( 'General Tools', 'simple-membership' ); ?></a>
            <a class="nav-tab <?php echo ( $tab == 'systeminfo' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_tools&tab=systeminfo"><?php _e( 'System Info', 'simple-membership' ); ?></a>
            <a class="nav-tab <?php echo ( $tab == 'migration' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_tools&tab=migration"><?php _e( 'Data Migration', 'simple-membership' ); ?></a>
			<?php

			//Trigger hooks that allows an extension to add extra nav tabs in the payments menu.
			do_action( 'swpm_tools_menu_nav_tabs', $selected );

			$menu_tabs = apply_filters( 'swpm_tools_menu_additional_menu_tabs_array', array() );
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

		do_action( 'swpm_tools_menu_after_nav_tabs' );

		//Allows an addon to completely override the body section of the payments admin menu for a given action.
		$output = apply_filters( 'swpm_tools_menu_body_override', '', $tab );
		if ( ! empty( $output ) ) {
			//An addon has overriden the body of this page for the given tab/action. So no need to do anything in core.
			echo $output;
			echo '</div>';//<!-- end of wrap -->

			return;
		}

		echo '<div id="poststuff"><div id="post-body">';

		//Switch case for the various different tabs handled by the core plugin.
		switch ( $tab ) {
			case 'migration':
                include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-migration-tools-menu-tab.php');
                $migration_tools_tab = new SWPM_Migration_Tools_Menu_Tab();
                $migration_tools_tab->handle_migration_tools_menu_tab();
				break;
			case 'systeminfo':
				include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-system-info-menu-tab.php');
				$migration_tools_tab = new SWPM_System_Info_Menu_Tab();
				$migration_tools_tab->handle_system_info_menu_tab();
				break;
            case 'general':
			default:
				include_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_tools_settings.php';
				break;
		}

		echo '</div></div>'; //<!-- end of post-body -->

		echo '</div>'; //<!-- end of .wrap -->
	}

}

