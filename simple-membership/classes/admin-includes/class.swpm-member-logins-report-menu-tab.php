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

        $output = '';

		$subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( $_GET['subtab'] ) : '';
		?>

        <!-- start nav menu sub tabs -->
        <h3 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ( $subtab == '' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports&tab=member-logins"><?php _e( 'Login History', 'simple-membership' ); ?></a>
            <a class="nav-tab <?php echo ( $subtab == 'login-by-date' ) ? 'nav-tab-active' : ''; ?>"
               href="admin.php?page=simple_wp_membership_reports&tab=member-logins&subtab=login-by-date"><?php _e( 'Login by Date', 'simple-membership' ); ?></a>
        </h3>
        <!-- end nav menu sub tabs -->

        <br>

		<?php
		//Switch case for the various different tabs handled by the core plugin.
		switch ( $subtab ) {
            case 'login-by-date':
	            $output .= $this->render_logins_by_date();
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

	public function render_logins_by_date() {
		global $wpdb;
		$query   = "SELECT COUNT(member_id) AS count, DATE(last_accessed) as date 
                FROM " . $wpdb->prefix . "swpm_members_tbl 
                WHERE last_accessed > " . date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ) - 1, date( 'd' ), date( 'Y' ) ) ) . " 
                GROUP BY DATE(last_accessed) 
                ORDER BY DATE(last_accessed) DESC";
		$results = $wpdb->get_results( $query );

		ob_start();
		?>
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e('Member Login in Last 30 Days', 'simple-membership');?></label></h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-date"></div>
                <div class="table-column">
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><?php _e('Date', 'simple-membership') ?>></th>
                            <th><?php _e('Login Count', 'simple-membership');?></th>
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
                </div>
            </div>
        </div>

        <?php if (!empty($results)) { ?>
        <!--<script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                google.load('visualization', '1', {packages: ['corechart', 'bar']});
                google.setOnLoadCallback(function(){
                    swpmRenderBarChart({
                        mountPoint: 'member-by-date',
                        stats: <?php /*echo json_encode( $stats ); */?>,
                        options: {
                            // title:  'Member Login in last 30 days',
                            chartArea: {
                                width: '90%',
                                // height: '70%'
                            },
                            hAxis: {title: 'Date'},
                            vAxis: {title: 'Count', minValue: 0},
                            legend: {position: 'none'}
                        }
                    });
                });
            })
        </script>-->
		<?php
		}
		return ob_get_clean();
	}

    public function render_login_history_settings() {
	    if (isset($_POST['swpm_login_log_settings']) && check_admin_referer('swpm_login_log_settings_nonce')){
		    $enable_login_event_tracking = isset( $_POST['enable_login_event_tracking'] ) ? "checked='checked'" : '';
		    $auto_prune_login_events = isset( $_POST['auto_prune_login_events'] ) ? "checked='checked'" : '';

		    $this->settings->set_value('enable_login_event_tracking', $enable_login_event_tracking);
		    $this->settings->set_value('auto_prune_login_events', $auto_prune_login_events);
		    $this->settings->save();
	    }

	    $enable_login_event_tracking = $this->settings->get_value('enable_login_event_tracking');
	    $auto_prune_login_events = $this->settings->get_value('auto_prune_login_events');

        ob_start();
        ?>
            <div class="postbox">
            <h3 class="hndle">
                <label>
				    <?php _e('Login Log Settings', 'simple-membership') ?>
                </label>
            </h3>
            <div class="inside">
                <form action="" method="post">
                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th>
                                <label for="enable_login_event_tracking"><?php _e('Enable Login Event Tracking', 'simple-membership') ?></label>
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
                                <label for="auto_prune_login_events"><?php _e('Auto Prune Login Events', 'simple-membership') ?></label>
                            </th>
                            <td>
                                <input id="auto_prune_login_events" type="checkbox" name="auto_prune_login_events" value="1" <?php esc_attr_e($auto_prune_login_events) ?>>
                                <p class="description">
								    <?php _e('When enabled it will auto trim login event logs older than 1 year.', 'simple-membership') ?>
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

				    <?php echo wp_nonce_field('swpm_login_log_settings_nonce') ?>

                    <p class="submit">
                        <input class="button-primary" type="submit" name="swpm_login_log_settings" value="<?php _e('Save Changes', 'simple-membership') ?>">
                    </p>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_login_history() {
	    require_once(SIMPLE_WP_MEMBERSHIP_PATH . 'classes/admin-includes/class.swpm-login-events-list-table.php');

	    $table = new SWPM_Login_Events_List_Table();

	    if ( isset( $_REQUEST['sDate'] ) && !empty( $_REQUEST['sDate'] ) ){
		    $table->set_start_date(sanitize_text_field($_REQUEST['sDate']));
	    }

	    if ( isset( $_REQUEST['eDate'] ) && !empty( $_REQUEST['eDate'] ) ){
		    $table->set_end_date(sanitize_text_field($_REQUEST['eDate']));
	    }

	    if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) ){
		    $table->set_search_text(sanitize_text_field($_REQUEST['s']));
	    }

        $table->prepare_items();

	    ob_start();
        ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e('Member Recent Logins', 'simple-membership') ?></label>
            </h3>
            <div class="inside">
                <?php $table->display_table() ?>
            </div>
        </div>
	    <?php
        return ob_get_clean();
    }
}