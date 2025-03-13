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
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                google.load('visualization', '1', {packages: ['corechart', 'bar']});
                google.setOnLoadCallback(function(){
                    swpmRenderBarChart({
                        mountPoint: 'member-by-date',
                        stats: <?php echo json_encode( $stats ); ?>,
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
        </script>
		<?php
		}
		return ob_get_clean();
	}

    public function render_login_history() {
	    global $wpdb;

	    $last30thDay = date("Y-m-d", strtotime("-30 days"));

	    list($start_date, $end_date, $date_range_from_html) = SwpmReportsAdminMenu::date_range_selector('recent_logins', $last30thDay, 'Y-m-d');

	    $query = $wpdb->prepare(
		    'SELECT member_id, user_name, last_accessed, last_accessed_from_ip 
            FROM ' . $wpdb->prefix . 'swpm_members_tbl 
            WHERE last_accessed BETWEEN %s AND %s
            ORDER BY last_accessed DESC',
		    $start_date,
		    $end_date
	    );

	    $results = $wpdb->get_results( $query );

        ob_start();
        ?>
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e('Member Recent Logins', 'simple-membership') ?></label></h3>
            <div class="inside">
                <?php echo $date_range_from_html; ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php _e('Member ID', 'simple-membership') ?></th>
                        <th><?php _e('Member Username', 'simple-membership') ?></th>
                        <th><?php _e('Last Login Date', 'simple-membership') ?></th>
                        <th><?php _e('IP Address', 'simple-membership') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ( $results as $result ) { ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=simple_wp_membership&member_action=edit&member_id=' . $result->member_id) ?>">
                                    <?php esc_attr_e($result->member_id); ?>
                                </a>
                            </td>
                            <td><?php esc_attr_e($result->user_name) ?></td>
                            <td>
                                <?php echo sprintf(
                                        '%s %s %s',
                                        date( SwpmReportsAdminMenu::get_date_format(), strtotime( $result->last_accessed ) ),
                                        __( 'at' , 'simple-membership'),
                                        date( SwpmReportsAdminMenu::get_time_format(), strtotime( $result->last_accessed ) )
                                ) ?>
                            </td>
                            <td><?php echo $result->last_accessed_from_ip ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
	    <?php
        return ob_get_clean();
    }
}