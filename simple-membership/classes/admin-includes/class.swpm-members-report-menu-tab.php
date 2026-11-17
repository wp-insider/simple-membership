<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


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
        $output .= '<div class="swpm-grey-box">'.__('This interface displays reports related to members.', 'simple-membership').'</div>';
        $output .= $this->render_registration_by_month();
        $output .= $this->render_member_by_account_status();

		$output = apply_filters( 'swpm_members_report_menu_tab_content', $output );

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters.
	}

    public function render_registration_by_month() {
	    global $wpdb;

	    $last_one_year = gmdate("Y-m-d", strtotime("-1 year"));
        list($start_date, $end_date, $date_range_from_html) = SwpmReportsAdminMenu::date_range_selector('reg_by_month', $last_one_year);

        $members_count = $wpdb->get_var( "SELECT COUNT(*) FROM ". $wpdb->prefix ."swpm_members_tbl" );

	    ob_start();
	    ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php esc_attr_e('Member Registrations by Month', 'simple-membership') ?></label>
            </h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-month"></div>
                <div class="table-column">
                    <?php
                    if( empty($members_count) ) {
                        echo esc_html__( 'No entries found.', 'simple-membership' );
                    } else {
                        echo $date_range_from_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters.

                        

                        $results = $wpdb->get_results( $wpdb->prepare(
                            'SELECT COUNT(member_id) AS count, MONTHNAME(member_since) AS month 
                            FROM ' . $wpdb->prefix . 'swpm_members_tbl 
                            WHERE member_since BETWEEN %s AND %s 
                            GROUP BY MONTH(member_since)',
                            $start_date,
                            $end_date
                        ) );

                        if (count($results) == 0){
	                        echo esc_html__( 'No entries found.', 'simple-membership' );
                        } else { ?>
                        <table class="widefat striped">
                            <thead>
                            <tr>
                                <th><?php esc_attr_e('Month', 'simple-membership') ?></th>
                                <th><?php esc_attr_e('Count', 'simple-membership') ?></th>
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
                                        <?php echo esc_attr( ucfirst( $result->month ) ); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_attr( $result->count ); ?>
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
                                <?php echo esc_html__( 'Total registrations: ', 'simple-membership' ) . esc_html( $count ); ?>
                            </p>
                        </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>

	    <?php
	    return ob_get_clean();
    }

    public function render_member_by_account_status() {
	    global $wpdb;

	    
	    $results = $wpdb->get_results( 'SELECT COUNT(member_id) AS count, account_state FROM ' . $wpdb->prefix . 'swpm_members_tbl GROUP BY (account_state)' );

	    ob_start();
        ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php esc_attr_e('Members by Account Status', 'simple-membership'); ?></label>
            </h3>
            <div class="inside swpm-stats-container">
                <div class="char-column" id="member-by-state"></div>
                <div class="table-column">
                    <?php if(empty($results)) {
                        echo esc_html__( 'No entries found.', 'simple-membership' );
                    } else { ?>
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><?php esc_attr_e('Account Status', 'simple-membership'); ?></th>
                            <th><?php esc_attr_e('Count', 'simple-membership'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
					    <?php $stats = array(array('Status', 'Count'));
					    $count       = 0; ?>
					    <?php
					    foreach ( $results as $result ) { ?>
                            <tr>
                                <td>
								    <?php echo esc_attr( ucfirst( $result->account_state ) ); ?>
                                </td>
                                <td>
								    <?php echo esc_attr( $result->count ); ?>
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
						    <?php echo esc_html__( 'Total members: ', 'simple-membership' ) . esc_html( $count ); ?>
                        </p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

	    <?php
	    return ob_get_clean();
    }

}