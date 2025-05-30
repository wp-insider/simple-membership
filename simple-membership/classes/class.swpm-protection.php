<?php

include_once('class.swpm-protection-base.php');

class SwpmProtection extends SwpmProtectionBase {

    public $msg = "";
    private static $_this;

    private function __construct() {
        $this->msg = "";
        $this->init(1);
    }

    public static function get_instance() {
        self::$_this = empty(self::$_this) ? (new SwpmProtection()) : self::$_this;
        return self::$_this;
    }

    public function is_protected($id) {
        if ($this->post_in_parent_categories($id) || $this->post_in_categories($id)) {
            $this->msg = '<p style="background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; margin: 10px 0px 10px 0px; padding: 5px 5px 5px 10px;">
                    ' . SwpmUtils::_('The category or parent category of this post is protected. You can change the category protection settings from the ') . 
                    '<a href="admin.php?page=simple_wp_membership_levels&level_action=category_list" target="_blank">' . SwpmUtils::_('category protection menu') . '</a>.
                    </p>';
            return true;
        }
        return $this->in_posts($id) || $this->in_pages($id) || $this->in_attachments($id) || $this->in_custom_posts($id);
    }

    public function get_last_message() {
        return $this->msg;
    }

    public function is_protected_post($id) {
        return /* (($this->bitmap&4) != 4) && */ $this->in_posts($id);
    }

    public function is_protected_page($id) {
        return /* (($this->bitmap&4) != 4) && */ $this->in_pages($id);
    }

    public function is_protected_attachment($id) {
        return /* (($this->bitmap&16)!=16) && */ $this->in_attachments($id);
    }

    public function is_protected_custom_post($id) {
        return /* (($this->bitmap&32)!=32) && */ $this->in_custom_posts($id);
    }

    public function is_protected_comment($id) {
        return /* (($this->bitmap&2)!=2) && */ $this->in_comments($id);
    }

    public function is_post_in_protected_category($post_id) {
        return /* (($this->bitmap&1)!=1) && */ $this->post_in_categories($post_id);
    }

    public function is_post_in_protected_parent_category($post_id) {
        return /* (($this->bitmap&1)!=1) && */ $this->post_in_parent_categories($post_id);
    }

    public function is_protected_category($id) {
        return /* (($this->bitmap&1)!=1) && */ $this->in_categories($id);
    }

    public function is_protected_parent_category($id) {
        return /* (($this->bitmap&1)!=1) && */ $this->in_parent_categories($id);
    }

    /**
     * Creates a list of all protected post IDs (includes posts from all post types of the site).
     * It retrieves the data from the membership level DB table so it is more efficient.
     */
    public static function get_all_protected_post_ids_list_from_db() {
        global $wpdb;
        $all_protected_post_ids = array();

        //Get all the protected post IDs from the database (it is stored in the membership level table with ID 1).
        $level_id = 1; // Default level ID for General Protection.
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE id = %d", absint($level_id));
        $result = $wpdb->get_row($query);

        $standard_posts_list = isset($result->post_list) ? (array) unserialize($result->post_list) : array();
        $standard_pages_list = isset($result->page_list) ? (array) unserialize($result->page_list) : array();
        $custom_posts_list = isset($result->custom_post_list) ? (array) unserialize($result->custom_post_list) : array();

        $all_protected_post_ids = array_merge($standard_posts_list, $standard_pages_list, $custom_posts_list);
        // Sort the final list of post IDs in ascending order
        sort($all_protected_post_ids);
        return $all_protected_post_ids;
    }

	/**
	 * Filter and keep only the post ids that are not permitted for the current user.
	 */
	public static function filter_protected_post_ids_list_for_current_user($post_ids_list) {
        $filtered_protected_post_ids = array_filter($post_ids_list, 'SwpmProtection::is_post_protected_for_current_user');
        // Reindex the array
        $reindexed_array = array_values($filtered_protected_post_ids);        
		return $reindexed_array;
	}

    /**
     * Check if a post (given the post_id) is protected for the current user.
     */
	public static function is_post_protected_for_current_user($post_id) {
        $swpm_access_control = SwpmAccessControl::get_instance();
        if( $swpm_access_control->can_i_read_post_by_post_id($post_id) ){
            // Not protected for current suer (the user has permission to read this post).
            return false;
        } else {
            // Protected for current user (the user does not have permission to read this post).
            return true;
        }
	}

	/**
	 * Apply protection to a post by post-id.
	 *
	 * @param int $post_id The post id to apply protection on.
	 * @param array $allowed_levels List of membership level ids that will be allowed to access this post. Leave empty to not apply allowance to none.
	 * @param string $post_type Default 'post'. The type of post i.e. post, page, attachment, comment, category etc.
	 *
	 * @return void
	 */
	public static function apply_protection_to_post(int $post_id, array $allowed_levels = array(), string $post_type = 'post') {
		// Apply protection to the given post.
        $protection = SwpmProtection::get_instance();
        $applied_protection = $protection->apply(array($post_id), $post_type);
        $applied_protection->save();        

		if (!empty($allowed_levels)){
            // Ensure the SwpmPermission class is loaded.
            if ( ! class_exists( SwpmPermission::class ) ) {
                require_once SIMPLE_WP_MEMBERSHIP_PATH . 'classes/class.swpm-permission.php';
            }

            // Loop through each allowed level and apply the permission accordingly for the content.
            foreach ( $allowed_levels as $level_id ) {
                $permission_instance = SwpmPermission::get_instance( intval( $level_id ) );
                $applied_protection = $permission_instance->apply( array( $post_id ), $post_type );
                $applied_protection->save();
            }
		}
	}

	/**
	 * Apply protection to posts by an array of post-ids.
	 *
	 * @param array $post_ids List post ids to apply protection on.
	 * @param array $allowed_levels List of membership level ids that will be allowed to access these posts. Leave empty to not apply allowance to none.
	 * @param string $post_type Default 'post'. The type of post i.e. post, page, attachment, comment, category etc.
	 *
	 * @return void
	 */
	public static function apply_protection_to_posts(array $post_ids, array $allowed_levels = array(), string $post_type = 'post'){
		if (empty($post_ids)){
			return;
		}

		foreach ($post_ids as $post_id) {
			self::apply_protection_to_post( (int) $post_id,  $allowed_levels, $post_type);
		}
	}

}