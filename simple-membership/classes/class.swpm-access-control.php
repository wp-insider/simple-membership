<?php
class SwpmAccessControl {
    private $lastError;
    private $moretags;
    private static $_this;
    private function __construct(){
        $this->lastError = '';
        $this->moretags  = array();
    }
    public static function get_instance(){
        self::$_this = empty(self::$_this)? new SwpmAccessControl():self::$_this;
        return self::$_this;
    }

    public function can_i_read_post($post){
        if (!is_a($post, 'WP_Post')) {return SwpmUtils::_('Error! $post is not a valid WP_Post object.'); }
        $id = $post->ID;
        $this->lastError = '';
        $auth = SwpmAuth::get_instance();
        $protect_everything = SwpmSettings::get_instance()->get_value('protect-everything');
        if(!empty($protect_everything)){ 
            $error_msg = SwpmUtils::_( 'You need to login to view this content. ' ) . SwpmMiscUtils::get_login_link();
            $this->lastError = apply_filters('swpm_not_logged_in_post_msg', $error_msg);
            return false;                       
        }
        $protected = SwpmProtection::get_instance();
        if (!$protected->is_protected($id)){ return true;}        
        if(!$auth->is_logged_in()){
            $error_msg = SwpmUtils::_( 'You need to login to view this content. ' ) . SwpmMiscUtils::get_login_link();
            $this->lastError = apply_filters('swpm_not_logged_in_post_msg', $error_msg);
            return false;            
        }

        if ($auth->is_expired_account()){
            $text = SwpmUtils::_('Your account has expired. ') .  SwpmMiscUtils::get_renewal_link();
            $error_msg = '<div class="swpm-account-expired-msg swpm-yellow-box">'.$text.'</div>';
            $this->lastError = apply_filters('swpm_account_expired_msg', $error_msg);
            return false;                        
        }
        $protect_older_posts = apply_filters('swpm_should_protect_older_post', false, $id);
        if ($protect_older_posts){
            $this->lastError = apply_filters ('swpm_restricted_post_msg_older_post', 
                    SwpmUtils::_('This content can only be viewed by members who joined on or before ' 
                            . SwpmUtils::get_formatted_date_according_to_wp_settings($post->post_date) ));
            return false;
        }
        $permission = SwpmPermission::get_instance($auth->get('membership_level'));
        if($permission->is_permitted($id)) {return true;}
        $this->lastError = apply_filters ('swpm_restricted_post_msg', 
                '<div class="swpm-no-access-msg">'
                . SwpmUtils::_('This content is not permitted for your membership level.').'</div>') ;
        return false;
    }
    
    public function can_i_read_comment($comment){
        if (!is_a($comment, 'WP_Comment')) {
            return SwpmUtils::_('Error! $comment is not a valid WP_Comment object.');
        }

        $id = $comment->comment_ID;
        $post_id = $comment->comment_post_ID;
        $post = get_post($post_id);
        $this->lastError = '';
        $auth = SwpmAuth::get_instance();
        
        //Check if everything protected settings is on.
        //$protect_everything = SwpmSettings::get_instance()->get_value('protect-everything');
        //if(!empty($protect_everything)){ 
            //Everything is protected by default.
            //TODO - This feature is currently not implemented.
        //}
        
        //Check if the post (that this comment belongs to) is protected.
        $protected = SwpmProtection::get_instance();
        if (!$protected->is_protected($post_id)){ 
            //The post of this comment is not protected. So this is an unprotected comment. Show it to everyone.
            return true;
        }
        
        /*** At this point, we have a protected comment. So we need to check if this user can view this comment. ***/
        
        //Check if the user is logged-in as a member.
        if(!$auth->is_logged_in()){
            //User is not logged-in. Not allowed to see this protected comment.
            $error_msg = '<div class="swpm-comment-not-logged-in">' . SwpmUtils::_("You need to login to view this content. ") . '</div>';
            $this->lastError = apply_filters('swpm_not_logged_in_comment_msg', $error_msg);
            return false;            
        }

        //Check if member account is expired.
        if ($auth->is_expired_account()){
            //This user's account is expired. Not allowed to see this comment. Show account expiry notice also.
            $text = SwpmUtils::_('Your account has expired. ') .  SwpmMiscUtils::get_renewal_link();
            $error_msg = '<div class="swpm-comment-account-expired-msg swpm-yellow-box">'.$text.'</div>';
            $this->lastError = apply_filters('swpm_account_expired_msg', $error_msg);
            return false;                        
        }
        
        //Check if older post protection addon is active and protection according to it's settings.
        $protect_older_posts = apply_filters('swpm_should_protect_older_post', false, $post_id);
        if ($protect_older_posts){
            //This comment is protected due to the older post protection addon settings configuration.
            $text = SwpmUtils::_('This content can only be viewed by members who joined on or before ' . SwpmUtils::get_formatted_date_according_to_wp_settings($post->post_date));
            $error_msg = '<div class="swpm-comment-older-post-msg">'.$text.'</div>';
            $this->lastError = apply_filters ('swpm_restricted_comment_older_post', $error_msg);
            return false;
        }
        
        //Check if this member can view this comment based on his membership level
        $permission = SwpmPermission::get_instance($auth->get('membership_level'));
        if(!$permission->is_permitted($post_id)) {
            //This member's membership level doesn't have access to this comment's post. Not allowed to see this comment.
            $error_msg = '<div class="swpm-comment-no-access-msg">' . SwpmUtils::_('This content is not permitted for your membership level.').'</div>';
            $this->lastError = apply_filters ('swpm_restricted_comment_msg', $error_msg);
            return false;
        }
        
        //All checks have passed. Show this comment to this user.
        return true;
    }

    public function filter_post($post,$content){
        if (!is_a($post, 'WP_Post')) {return SwpmUtils::_('$post is not valid WP_Post object.'); }
        $id = $post->ID;
        if (self::is_current_url_unrestricted()) {return $content;}
        if(SwpmUtils::is_first_click_free($content)) {return $content;}
        if(in_array($id, $this->moretags)) {return $content; }
        if($this->can_i_read_post($post)) {return $content; } 
        
        $moretag = SwpmSettings::get_instance()->get_value('enable-moretag');
        if (empty($moretag)){
            return $this->lastError;
        }
        $post_segments = explode( '<!--more-->', $post->post_content);

        if (count($post_segments) >= 2){
            if (SwpmAuth::get_instance()->is_logged_in()){
                $error_msg = '<div class="swpm-margin-top-10">' 
                        . SwpmUtils::_(" The rest of the content is not permitted for your membership level.") . '</div>';
                $this->lastError = apply_filters ('swpm_restricted_more_tag_msg', $error_msg);
            }
            else {
                $error_msg = '<div class="swpm-margin-top-10">' 
                        . SwpmUtils::_("You need to login to view the rest of the content. ") 
                        . SwpmMiscUtils::get_login_link() . '</div>';
                $this->lastError = apply_filters('swpm_not_logged_in_more_tag_msg', $error_msg);
            }
            return do_shortcode($post_segments[0]) . $this->lastError;
        }

        return $this->lastError;
    }
    
    public function filter_comment($comment, $content){
        if (self::is_current_url_unrestricted()) {return $content;}
        if($this->can_i_read_comment($comment)) { return $content; }
        return $this->lastError;
    }
    
    public function filter_post_with_moretag($post, $more_link, $more_link_text){
        if (!is_a($post, 'WP_Post')) {return SwpmUtils::_('$post is not valid WP_Post object.'); }
        $id = $post->ID;
        if (self::is_current_url_unrestricted()) {return $more_link;}
        if (SwpmUtils::is_first_click_free($more_link)) {return $more_link;}
        $this->moretags[] = $id;
        if($this->can_i_read_post($post)) {
            return $more_link;
        }
        $msg = '<div class="swpm-margin-top-10">'
                . SwpmUtils::_("You need to login to view the rest of the content. ") 
                . SwpmMiscUtils::get_login_link(). '</div>';
        return apply_filters('swpm_not_logged_in_more_tag_msg', $msg);
    }
    
    public function why(){
        return $this->lastError;
    }
    
    public static function is_current_url_unrestricted(){ 
        $renewal_url = SwpmSettings::get_instance()->get_value('renewal-page-url');        
        if (empty($renewal_url)) {return false;}
        
        $current_page_url = SwpmMiscUtils::get_current_page_url();
        if (SwpmMiscUtils::compare_url($renewal_url, $current_page_url)) {return true;}

        $login_page_url = SwpmSettings::get_instance()->get_value('login-page-url');
        if (empty($login_page_url)) {return false;}
        
        if (SwpmMiscUtils::compare_url($login_page_url, $current_page_url)) {return true;}

        $registration_page_url = SwpmSettings::get_instance()->get_value('registration-page-url');
        if (empty($registration_page_url)) {return false;}
        
        return SwpmMiscUtils::compare_url($registration_page_url, $current_page_url);
    }
}
