<?php

class SWPM_Login_Events_List_Table extends WP_List_Table {

	private $table_data;

    private $total_count;

    private $start_date;

    private $end_date;

    private $search;

	public function __construct() {
		parent::__construct([
			'singular' => 'login event log',
			'plural'   => 'login event logs',
			'ajax'     => false
		]);
	}


    public function set_start_date( $start_date ){
        $this->start_date = $start_date;
    }

    public function set_end_date( $end_date ){
        $this->end_date = $end_date;
    }

	public function set_search_text( $search ){
		$this->search = $search;
	}

	public function get_columns() {
		return [
			'cb' => '<input type="checkbox" />',
			'event_id' => __('Event ID', 'simple-membership'),
			'member' => __('Member', 'simple-membership'),
			'event_date_time' => __( 'Date', 'simple-membership'),
			'ip_address' => __('IP Address', 'simple-membership'),
			// 'browser' => __('Browser', 'simple-membership')
		];
	}

	public function column_cb($item) {
		return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['event_id']);
	}

	public function column_default( $item, $column_name ){
		return $item[$column_name];
	}

	public function column_member($item) {
		return '<a href="admin.php?page=simple_wp_membership&member_action=edit&member_id='.esc_attr($item['member_id']).'">'. esc_attr($item['username']).'</a>';
	}

	public function column_event_date_time( $item ) {
		return date(SwpmReportsAdminMenu::get_date_format() . ' \a\t ' . SwpmReportsAdminMenu::get_time_format(), strtotime($item['event_date_time']));
	}

	//public function column_browser($item){
	//    echo print_r(maybe_unserialize($item['user_agent']), true);
	//}

	public function prepare_items() {
		$this->process_bulk_action();

		$per_page = 50;
		$current_page = $this->get_pagenum();
		$this->total_count = $this->get_total_item_count();

		$offset = ($current_page - 1) * $per_page;

		$this->table_data = $this->get_table_data( $per_page, $offset );

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$primary  = 'name';
		$this->_column_headers = array($columns, $hidden, $sortable, $primary);

		usort($this->table_data, array(&$this, 'usort_reorder'));

		$this->set_pagination_args(array(
			'total_items' => $this->total_count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $this->total_count / $per_page )
		));

		$this->items = $this->table_data;
	}

	public function get_total_item_count() {
		global $wpdb;

		$table = $wpdb->prefix . 'swpm_events_tbl';

        $query = "SELECT COUNT(*) FROM $table WHERE event_type = 'login_success'";

        $args = array();

        if ( isset($this->search) && !empty($this->search) ){
	        $username = "%" . $wpdb->esc_like($this->search) . "%";
	        $query .= " AND username LIKE %s";

	        $args[] = $username;
        }

        if ( isset($this->start_date) && isset($this->end_date) ){
            $query .= " AND event_date_time BETWEEN %s AND %s";

	        $args[] = date("Y-m-d H:i:s", strtotime($this->start_date));
	        $args[] = date("Y-m-d 11:59:59", strtotime($this->end_date));
        }

        $query = !empty($args) ? $wpdb->prepare($query, ...$args) : $query;

		return $wpdb->get_var( $query );
	}

	private function get_table_data( $limit, $offset ) {
		global $wpdb;

		$table = $wpdb->prefix . 'swpm_events_tbl';

        $query = "SELECT * FROM $table WHERE event_type = 'login_success'";

		if ( isset($this->search) && !empty($this->search) ){
			$username = "%" . $wpdb->esc_like($this->search) . "%";

			$query .= " AND username LIKE %s";

			$args[] = $username;
		}

		if ( isset($this->start_date) && isset($this->end_date) ){
		    $query .= " AND event_date_time BETWEEN %s AND %s";

            $args[] = date("Y-m-d H:i:s", strtotime($this->start_date));
			$args[] = date("Y-m-d 11:59:59", strtotime($this->end_date));
		}

        $query .= " ORDER BY event_id DESC LIMIT %d OFFSET %d";
        $args[] = $limit;
        $args[] = $offset;

		$query = !empty($args) ? $wpdb->prepare($query, ...$args) : $query;

		return $wpdb->get_results($query, ARRAY_A);
	}

	protected function get_sortable_columns() {
		return array(
			'event_id'  => array('event_id', false),
			'member'  => array('username', false),
		);
	}

	public function usort_reorder($a, $b) {
		// If no sort, default to event_id
		$orderby = (!empty($_GET['orderby'])) ? sanitize_text_field($_GET['orderby']) : 'event_id';

		// If no order, default to desc
		$order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

		// Determine sort order
		if ($orderby == 'event_id'){
			// compare as numeric value
			$result = $a[$orderby] <=> $b[$orderby];
		} else {
			// compare as string value
			$result = strcmp($a[$orderby], $b[$orderby]);
		}

		// Send final sort direction to usort
		return ($order === 'asc') ? $result : -$result;
	}

	public function get_bulk_actions() {
		return [
			'delete' => 'Delete'
		];
	}

	public function process_bulk_action() {
		if ( 'delete' === $this->current_action() ){

			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( stripslashes ( $_POST['_wpnonce'] ) ) : '';
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( __( 'Nonce verification failed!', 'simple-membership' ) );
			}

			if ( ! isset( $_POST['bulk-delete'] ) || empty($_POST['bulk-delete']) ) {
				echo '<div class="notice notice-error"><strong>' . __( 'No entries were selected.', 'simple-membership' ) . '</strong></div>';
				return;
			}

            $row_ids_arr = array_values($_POST['bulk-delete']);

			$row_ids_arr = array_map('sanitize_text_field', $row_ids_arr);
			$row_ids_arr = array_map('intval', $row_ids_arr);

			$row_ids = implode(',', $row_ids_arr);

			global $wpdb;
			$table = $wpdb->prefix . 'swpm_events_tbl';
			$del_row = $wpdb->query('DELETE FROM ' . $table . ' WHERE event_id IN ( '. $row_ids .' )');

			if ( $del_row ) {
				echo '<div class="notice notice-success"><p>' . __( 'Log entries deleted successfully!', 'simple-membership' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . __( 'Error: log entries could not be deleted!', 'simple-membership' ) . '</p></div>';
			}
		}
	}

	public function display_filter_data_section() {
        $search_btn_text = __('Filter', 'simple-membership');
        ?>
        <div class="alignleft actions searchactions" style="display: flex; align-items: end; flex-wrap: wrap; margin-bottom: 18px">
            <div>
                <label for="swpm_date_range_start"><?php _e('Start Date', 'simple-membership') ?></label>
                <br>
                <input type="date" name="start_date" id="swpm_date_range_start" value="<?php esc_attr_e(date('Y-m-d', strtotime($this->start_date)));?>">
            </div>

            <div>
                <label for="swpm_date_range_end"><?php _e('End Date', 'simple-membership') ?></label>
                <br>
                <input type="date" name="end_date" id="swpm_date_range_end" value="<?php esc_attr_e(date('Y-m-d', strtotime($this->end_date)));?>">
            </div>

            <div>
                <label for="swpm-search-input"><?php esc_attr_e($search_btn_text) ?>:</label>
                <br>
                <input type="search" id="swpm-search-input" name="s" value="<?php _admin_search_query(); ?>"/>
            </div>

            <?php submit_button( $search_btn_text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </div>

        <?php
	}

    public function display_reset_logs_section(){
        ?>
        <div>
            <button
                    type="button"
                    id="swpm-reset-login-event-logs"
                    class="button"
            >
			    <?php _e('Reset Log Entries', 'simple-membership') ?>
            </button>
            <p class="description">
                <?php _e('This button will reset all log entries. It can useful if you want to delete all your log entries', 'simple-membership') ?>
            </p>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function(){
                const resetBtn = document.getElementById('swpm-reset-login-event-logs');
                resetBtn.addEventListener('click', async function (e){
                    if(!confirm("Are you sure you want to delete all logs?")) {
                        return;
                    }
                    try {
                        const payload = new URLSearchParams({
                            action: 'swpm_reset_login_event_logs',
                            nonce: '<?php echo wp_create_nonce('swpm_reset_login_event_logs') ?>'
                        })
                        const response = await fetch('<?php echo admin_url( 'admin-ajax.php')?>', {
                            method: 'post',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: payload,
                        })

                        const result = await response.json();
                        console.log(result);

                        if (!result.success) {
                            throw new Error(result.data.message);
                        }

                        alert(result.data.message);

                        window.location.replace(window.location.href);
                    } catch (error) {
                        console.log(error);
                        alert(error.message);
                    }
                })
            })
        </script>

        <?php
    }

    public function display_table() {
	    ?>
            <form action="" method="post" id="swpm-recent-login-events-table">
                <?php $this->display_filter_data_section() ?>
                <?php $this->display(); ?>
            </form>

            <br>
            <?php $this->display_reset_logs_section() ?>
        <?php
    }
}