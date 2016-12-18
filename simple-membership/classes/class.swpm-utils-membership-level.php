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
    
}
