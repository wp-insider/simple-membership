<?php

class SWPM_Membership_Level_Report_Menu_Tab {

	public $settings;

	public function __construct() {
		$this->settings = SwpmSettings::get_instance();
	}

	/*
	* This function is used to render and handle the menu tab.
	*/
	public function handle_menu_tab() {
		do_action( 'swpm_membership_level_report_menu_tab_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Membership Levels Report Menu Tab' );

        $output = '';
        $output .= '<div class="swpm-grey-box">'.__('This interface displays reports related to membership levels.', 'simple-membership').'</div>';
        $output .= $this->render_members_by_membership_level();

		$output = apply_filters( 'swpm_membership_level_report_menu_tab_content', $output );

		echo $output;
	}

    public function render_members_by_membership_level() {
	    global $wpdb;
	    $query = 'SELECT COUNT(member_id) AS count, alias FROM ' . $wpdb->prefix . 'swpm_members_tbl '
	             . ' LEFT JOIN ' . $wpdb->prefix . 'swpm_membership_tbl ON (membership_level=id) '
	             . ' GROUP BY (membership_level)';
	    $results = $wpdb->get_results( $query );

	    ob_start();
	    ?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e('Members by Membership Level', 'simple-membership');?></label>
            </h3>
            <div class="char-column" id="member-by-level"></div>
            <div class="inside swpm-stats-container">
                <div class="table-column">
	                <?php if (empty($results)) {
		                echo __('No entries found.', 'simple-membership');
	                } else { ?>
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><?php _e('Membership Level', 'simple-membership'); ?></th>
                            <th><?php _e('Count', 'simple-membership'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
					    <?php
                        $stats = array(array('Level', 'Count'));
					    $count       = 0;
					    foreach ( $results as $result ) { ?>
                            <tr>
                                <td>
								    <?php echo ucfirst( $result->alias ); ?>
                                </td>
                                <td>
								    <?php echo $result->count; ?>
								    <?php
								    $stats[] = array( ucfirst( $result->alias ), intval( $result->count ) );
								    $count   += $result->count;
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
	                <?php } ?>
                </div>
            </div>
        </div>

	    <?php if (!empty($results)) { ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function (){
                google.load('visualization', '1.0', {'packages': ['corechart']});
                google.setOnLoadCallback(function(){
                    swpmRenderPieChart({
                        stats: <?php echo json_encode( $stats ); ?>,
                        mountPoint: 'member-by-level',
                        options: {
                            // 'title': 'Members by Membership Level',
                            'width': 360,
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

