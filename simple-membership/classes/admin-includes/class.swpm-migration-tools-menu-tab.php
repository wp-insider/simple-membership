<?php

class SWPM_Migration_Tools_Menu_Tab {

	public function __construct() {

	}

	/*
	* This function is used to render and handle the migration tools menu tab.
	*/
	public function handle_migration_tools_menu_tab() {
		do_action( 'swpm_payment_settings_menu_tab_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Migration Tools Menu Tab' );

		ob_start();
		?>
        <div class="swpm-yellow-box">
            <p class="description">
				<?php _e( 'Please install the SWPM Data Migration Addon to activate this tab.', 'simple-membership' ) ?>
            </p>
            <button class="button button-primary">
				<?php _e( 'Install', 'simple-membership' ) ?>
            </button>
        </div>
		<?php
		$output = ob_get_clean();

		$output = apply_filters( 'swpm_migration_tools_menu_tab_content', $output );

        echo $output;
	}
}