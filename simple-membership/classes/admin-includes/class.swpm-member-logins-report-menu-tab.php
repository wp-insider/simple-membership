<?php

class SWPM_Member_Logins_Report_Menu_Tab {

	public $settings;

	public function __construct() {
		$this->settings = SwpmSettings::get_instance();
	}

	/*
	* This function is used to render and handle the menu tab.
	*/
	public function handle_menu_tab() {
		do_action( 'swpm_member_logins_report_menu_tab_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Member Logins Report Menu Tab' );

		$subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( $_GET['subtab'] ) : '';
		?>
        <!-- show info box -->
        <div class="swpm-grey-box"><?php _e('This interface displays reports related to member logins.', 'simple-membership'); ?></div>

        <!-- start nav menu sub tabs -->
        <h3 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ( $subtab == '' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports&tab=member-logins"><?php _e( 'Login History', 'simple-membership' ); ?></a>
            <a class="nav-tab <?php echo ( $subtab == 'login-counts-by-date' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports&tab=member-logins&subtab=login-counts-by-date"><?php _e( 'Login Counts by Date', 'simple-membership' ); ?></a>
        </h3>
        <!-- end nav menu sub tabs -->

        <br>

		<?php
        $output = '';
        
		//Switch case for the various different tabs handled by the core plugin.
		switch ( $subtab ) {
            case 'login-counts-by-date':
	            $output .= $this->render_unique_member_login_count_by_date();
	            $output .= $this->render_member_login_count_by_date();
				break;
            case 'login-history':
			default:
                $output .= $this->render_login_history_settings();
                $output .= $this->render_login_history();
				break;
		}

		$output = apply_filters( 'swpm_member_logins_report_menu_tab_content', $output );

		echo $output;
	}

	public function render_unique_member_login_count_by_date() {
		global $wpdb;
		$query   = "SELECT COUNT(DISTINCT member_id) AS count, DATE(event_date_time) as date 
                FROM " . $wpdb->prefix . "swpm_events_tbl 
                WHERE event_date_time > " . date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ) - 1, date( 'd' ), date( 'Y' ) ) ) . " 
                GROUP BY DATE(event_date_time) 
                ORDER BY DATE(event_date_time) DESC";
		$results = $wpdb->get_results( $query );

		ob_start();
		?>
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e('Unique Member Account Login Counts by Date', 'simple-membership');?></label></h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-date"></div>
                <div class="table-column">
                    <p class="description">
                        <?php _e('This table displays the number of unique member logins by date over the past 30 days.', 'simple-membership');?>
                    </p>
                    <br>
	                <?php if (empty($results)) {
		                echo __('No entries found.', 'simple-membership');
	                } else { ?>
                        <table class="widefat striped">
                            <thead>
                            <tr>
                                <th><?php _e('Date', 'simple-membership') ?></th>
                                <th><?php _e('Unique Login Count', 'simple-membership');?></th>
                            </tr>
                            </thead>
                            <tbody>
			                <?php $stats = array( array( 'Date', 'Count' ) );
			                $count = 0;
			                foreach ( $results as $result ) { ?>
                                <tr>
                                    <td>
						                <?php echo date( SwpmReportsAdminMenu::get_date_format(), strtotime( $result->date ) ); ?>
                                    </td>
                                    <td>
						                <?php echo $result->count; ?>
						                <?php
						                $stats[] = array( date( SwpmReportsAdminMenu::get_date_format(), strtotime( $result->date ) ), intval($result->count) );
						                $count  += $result->count;
						                ?>
                                    </td>
                                </tr>
			                <?php } ?>
                        </table>
                        <div class="swpm_report_total_container">
                            <p class="description">
				                <?php echo __('Total login count: ', 'simple-membership') . $count; ?>
                            </p>
                        </div>
	                <?php } ?>
                </div>
            </div>
        </div>

        <?php
		return ob_get_clean();
	}

	public function render_member_login_count_by_date() {
		global $wpdb;
		$query   = "SELECT COUNT(member_id) AS count, DATE(event_date_time) as date 
                FROM " . $wpdb->prefix . "swpm_events_tbl 
                WHERE event_date_time > " . date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ) - 1, date( 'd' ), date( 'Y' ) ) ) . " 
                GROUP BY DATE(event_date_time) 
                ORDER BY DATE(event_date_time) DESC";
		$results = $wpdb->get_results( $query );

		ob_start();
		?>
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e('Member Account Login Counts by Date', 'simple-membership');?></label></h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-date"></div>
                <div class="table-column">
                    <p class="description">
                        <?php _e('This table shows the total number of logins per day over the past 30 days. Multiple login events from the same user are counted here.', 'simple-membership');?>
                    </p>
                    <br>
                    <?php if (empty($results)) {
	                    echo __('No entries found.', 'simple-membership');
                    } else { ?>
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><?php _e('Date', 'simple-membership') ?></th>
                            <th><?php _e('Total Login Count', 'simple-membership');?></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php $stats = array( array( 'Date', 'Count' ) );
						$count = 0;
						foreach ( $results as $result ) { ?>
                            <tr>
                                <td>
									<?php echo date( SwpmReportsAdminMenu::get_date_format(), strtotime( $result->date ) ); ?>
                                </td>
                                <td>
									<?php echo $result->count; ?>
									<?php
									$stats[] = array( date( SwpmReportsAdminMenu::get_date_format(), strtotime( $result->date ) ), intval($result->count) );
									$count  += $result->count;
									?>
                                </td>
                            </tr>
						<?php } ?>
                    </table>
                    <div class="swpm_report_total_container">
                        <p class="description">
							<?php echo __('Total login count: ', 'simple-membership') . $count; ?>
                        </p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php
		return ob_get_clean();
	}

    public function render_login_history_settings() {
	    if (isset($_POST['swpm_login_log_settings']) && check_admin_referer('swpm_login_log_settings_nonce')){
		    $enable_login_event_tracking = isset( $_POST['enable_login_event_tracking'] ) ? "checked='checked'" : '';
		    $auto_prune_login_events = isset( $_POST['auto_prune_login_events'] ) ? "checked='checked'" : '';

		    $this->settings->set_value('enable_login_event_tracking', $enable_login_event_tracking);
		    $this->settings->set_value('auto_prune_login_events', $auto_prune_login_events);
		    $this->settings->save();

            echo '<div class="notice notice-success"><p>'.__('Login event tracking settings updated.', 'simple-membership').'</p></div>';
	    }

	    $enable_login_event_tracking = $this->settings->get_value('enable_login_event_tracking');
	    $auto_prune_login_events = $this->settings->get_value('auto_prune_login_events');

        ob_start();
        ?>
            <div class="postbox">
            <h3 class="hndle">
                <label>
				    <?php _e('Login Event Tracking Settings', 'simple-membership') ?>
                </label>
            </h3>
            <div class="inside">
                <form action="" method="post">
                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th>
                                <label><?php _e('Enable Login Event Tracking', 'simple-membership') ?></label>
                            </th>
                            <td>
                                <input id="enable_login_event_tracking" type="checkbox" name="enable_login_event_tracking" value="1" <?php esc_attr_e($enable_login_event_tracking) ?>>
                                <p class="description">
								    <?php _e('If checked, the plugin will capture member login events.', 'simple-membership') ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e('Auto Prune Login Events', 'simple-membership') ?></label>
                            </th>
                            <td>
                                <input id="auto_prune_login_events" type="checkbox" name="auto_prune_login_events" value="1" <?php esc_attr_e($auto_prune_login_events) ?>>
                                <p class="description">
								    <?php _e('When enabled, it will auto prune login events data older than 1 year.', 'simple-membership') ?>
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

				    <?php echo wp_nonce_field('swpm_login_log_settings_nonce') ?>
                    <input class="button-primary" type="submit" name="swpm_login_log_settings" value="<?php _e('Save Changes', 'simple-membership') ?>">
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_login_history() {
	    require_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-login-events-list-table.php');

	    $table = new SWPM_Login_Events_List_Table();

	    if ( isset( $_REQUEST['swpm_start_date'] ) && !empty( $_REQUEST['swpm_start_date'] ) ){
		    $table->set_start_date(sanitize_text_field($_REQUEST['swpm_start_date']));
	    }

	    if ( isset( $_REQUEST['swpm_end_date'] ) && !empty( $_REQUEST['swpm_end_date'] ) ){
		    $table->set_end_date(sanitize_text_field($_REQUEST['swpm_end_date']));
	    }

	    if ( isset( $_REQUEST['swpm_search'] ) && !empty( $_REQUEST['swpm_search'] ) ){
		    $table->set_search_text(sanitize_text_field($_REQUEST['swpm_search']));
	    }

        $table->prepare_items();

	    ob_start();
        ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e('Recent Login Events', 'simple-membership') ?></label>
            </h3>
            <div class="inside">
                <?php 
                if( SwpmEventLogger::has_event_entries(SwpmEventLogger::EVENT_TYPE_LOGIN_SUCCESS)){
                    $table->display_table();
                }
                else{
                    echo '<p>' . __('No login events found.', 'simple-membership') . '</p>';
                }
                ?>
            </div>
        </div>
	    <?php
        return ob_get_clean();
    }
}