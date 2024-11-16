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
	 * Save a list (in array format) of all protected post and page IDs (includes posts from all post types of the site).
     * This can be useful when we need to quickly check if a post is protected or not given its ID.
	 */
	public static function save_swpm_all_protected_post_ids_list() {
        // Get all post IDs of all post type (includes post, pages, custom post types) from the site.
		$all_posts_ids = get_posts(array(
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'fields'         => 'ids', /* Only get IDs, not the full post objects. */
			'posts_per_page' => -1
		));

		$protected_post_ids = array();
		foreach ($all_posts_ids as $post_id){
			if (SwpmProtection::get_instance()->is_protected($post_id)){
				$protected_post_ids[] = $post_id;
			}
		}
		if (!empty($protected_post_ids)){
            // Save the list of all protected post IDs in the WP options table.
			update_option('swpm_all_protected_post_ids_list', $protected_post_ids);
		}
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

}
