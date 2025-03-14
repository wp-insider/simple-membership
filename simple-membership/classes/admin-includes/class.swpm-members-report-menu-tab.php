<?php

class SWPM_Members_Report_Menu_Tab {

	public $settings;

	public function __construct() {
		$this->settings = SwpmSettings::get_instance();
	}

	/*
	* This function is used to render and handle the menu tab.
	*/
	public function handle_menu_tab() {
		do_action( 'swpm_members_report_menu_tab_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Members Report Menu Tab' );

        $output = '';
        $output .= $this->render_registration_by_month();
        $output .= $this->render_member_by_account_status();

		$output = apply_filters( 'swpm_members_report_menu_tab_content', $output );

		echo $output;
	}

    public function render_registration_by_month() {
	    global $wpdb;

        list($start_date, $end_date, $date_range_from_html) = SwpmReportsAdminMenu::date_range_selector('reg_by_month');

	    $query = $wpdb->prepare(
            'SELECT COUNT(member_id) AS count, MONTHNAME(member_since) AS month 
            FROM ' . $wpdb->prefix . 'swpm_members_tbl 
            WHERE member_since BETWEEN %s AND %s 
            GROUP BY MONTH(member_since)',
		    $start_date,
		    $end_date
	    );

        $results = $wpdb->get_results( $query );

	    ob_start();
	    ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e('Member Registrations by Month', 'simple-membership') ?></label>
            </h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-month"></div>
                <div class="table-column">
                    <?php echo $date_range_from_html; ?>
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><?php _e('Month', 'simple-membership') ?></th>
                            <th><?php _e('Count', 'simple-membership') ?></th>
                        </tr>
                        </thead>
                        <tbody>
					    <?php
					    $stats = array( array( 'Month', 'Count' ) );
					    $count       = 0;
					    foreach ( $results as $result ) {
						    ?>
                            <tr>
                                <td>
								    <?php echo ucfirst( $result->month ); ?>
                                </td>
                                <td>
								    <?php echo $result->count; ?>
								    <?php
								    $stats[] = array( ucfirst( $result->month ), intval( $result->count ) );
								    $count   += $result->count;
								    ?>
                                </td>
                            </tr>
					    <?php } ?>
                    </table>
                    <div class="swpm_report_total_container">
                        <p class="description">
						    <?php echo __('Total registrations: ', 'simple-membership') . $count; ?>
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
                        mountPoint: 'member-by-month',
                        stats: <?php echo json_encode( $stats ); ?>,
                        options: {
                            // title: 'Member Registrations by Month',
                            chartArea: {
                                width: '90%',
                                // height: '70%'
                            },
                            hAxis: {title: 'Month'},
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

    public function render_member_by_account_status() {
	    global $wpdb;

	    $query = 'SELECT COUNT(member_id) AS count, account_state FROM ' . $wpdb->prefix . 'swpm_members_tbl GROUP BY (account_state)';
	    $results = $wpdb->get_results( $query );

	    ob_start();
        ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e('Members by Account Status', 'simple-membership'); ?></label>
            </h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-state"></div>
                <div class="table-column">
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><?php _e('Account Status', 'simple-membership'); ?></th>
                            <th><?php _e('Count', 'simple-membership'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
					    <?php $stats = array(array('Status', 'Count'));
					    $count       = 0; ?>
					    <?php
					    foreach ( $results as $result ) { ?>
                            <tr>
                                <td>
								    <?php echo ucfirst( $result->account_state ); ?>
                                </td>
                                <td>
								    <?php echo $result->count; ?>
								    <?php
								    $stats[] = array( ucfirst( $result->account_state ), intval( $result->count ) );
								    $count += $result->count;
								    ?>
                                </td>
                            </tr>
					    <?php } ?>
                    </table>
                    <div class="swpm_report_total_container">
                        <p class="description">
						    <?php echo __('Total members: ', 'simple-membership') . $count; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

	    <?php if (!empty($results)) { ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                google.load('visualization', '1.0', {'packages': ['corechart']});
                google.setOnLoadCallback(function(){
                    swpmRenderPieChart({
                        mountPoint: 'member-by-state',
                        stats: <?php echo json_encode( $stats ); ?>,
                        options: {
                            // title: 'Members by Account Status',
                            // 'width': 250,
                            // 'height': 150
                        }
                    })
                });
            })
        </script>
	    <?php
	    }
	    return ob_get_clean();
    }

}