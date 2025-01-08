<?php

class SWPM_Migration_Tools_Menu_Tab {

	public function __construct() {

	}

	/*
	* This function is used to render and handle the migration related tool menu tab.
	*/
	public function handle_migration_tools_menu_tab() {
		do_action( 'swpm_payment_settings_menu_tab_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Migration Related Tool Menu Tab' );

		ob_start();
		?>
        <div class="swpm-yellow-box">
            <p class="description">
				<?php _e( "To migrate the Simple Membership plugin's data to another WordPress installation, please download and install the free ", "simple-membership" ) ?>
				<?php echo '<a href="https://simple-membership-plugin.com/simple-membership-data-migration-between-wordpress-sites/" target="_blank">' . __( "Data Migration Addon", "simple-membership" ) . '</a>.'; ?>
            </p>
        </div>
		<?php
		$output = ob_get_clean();

		$output = apply_filters( 'swpm_migration_tools_menu_tab_content', $output );

        echo $output;
	}
}