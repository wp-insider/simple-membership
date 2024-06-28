<?php

/**
 * This class will contain various utility functions for the membership access level.
 */

class SwpmMembershipLevelUtils {

    public static function get_membership_level_name_of_a_member($member_id){
        $user_row = SwpmMemberUtils::get_user_by_id($member_id);
        $level_id = $user_row->membership_level;
        
        $level_row = SwpmUtils::get_membership_level_row_by_id($level_id);
        $level_name = $level_row->alias;
        return $level_name;
    }
    
    public static function get_membership_level_name_by_level_id($level_id){        
        $level_row = SwpmUtils::get_membership_level_row_by_id($level_id);
        $level_name = $level_row->alias;
        return $level_name;
    }
    
    public static function get_all_membership_levels_in_array(){
        //Creates an array like the following with all the available levels.
        //Array ( [2] => Free Level, [3] => Silver Level, [4] => Gold Level )
        
        global $wpdb;
        $levels_array = array();
        $query = "SELECT alias, id FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id != 1";
        $levels = $wpdb->get_results($query);
        foreach ($levels as $level) {
            if(isset($level->id)){
                $levels_array[$level->id] = $level->alias;
            }
        }
        return $levels_array; 
    }

    /**
     * Check if any membership level configured.
     * @return bool
     */
    public static function is_membership_level_configured() {
        $all_levels_array = self::get_all_membership_levels_in_array();
        if(!empty($all_levels_array)){
            //Membership level has been configured.
            return true;
        }
        return false;
    }

    /**
     * Check if a membership level exists by a membership level id.
     * @param $membership_lvl_id
     * @return bool
     */
    public static function check_if_membership_level_exists($membership_lvl_id) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id = %d", $membership_lvl_id);
        $membership_lvl_resultset = $wpdb->get_row($query);

        if ( $membership_lvl_resultset ) {
            //Membership level exists.
            return true;
        }

        //Membership level does not exist.
        return false;
    }
}
