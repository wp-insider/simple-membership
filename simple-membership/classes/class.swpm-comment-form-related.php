<?php

class SwpmCommentFormRelated {

    public static function customize_comment_form() {
        $allow_comments = SwpmSettings::get_instance()->get_value('members-login-to-comment');
        if (empty($allow_comments)){
            return;
        }        
        if (SwpmAuth::get_instance()->is_logged_in()){
            return;            
        }
        
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#respond').html("<?php SwpmUtils::e("Please Login to Comment."); ?>");
            });
        </script>
        <?php        
    }
    
    public static function customize_comment_fields($fields){
        
        //Check if login to comment feature is enabled.
        $allow_comments = SwpmSettings::get_instance()->get_value('members-login-to-comment');
        if (empty($allow_comments)){//Feature is disabled
            return $fields;
        }        
        
        if (SwpmAuth::get_instance()->is_logged_in()){//Member is logged-in.
            return $fields;
        }
        
        //Member is not logged-in so show the protection message.
        $fields = array();
        $login_link = SwpmUtils::_('Please Login to Comment.');
        $fields['comment_field'] = $login_link;
        $fields['title_reply'] = '';
        $fields['cancel_reply_link'] = '';
        $fields['comment_notes_before'] = '';
        $fields['comment_notes_after'] = '';
        $fields['fields'] = '';
        $fields['label_submit'] = '';
        $fields['title_reply_to'] = '';
        $fields['id_submit'] = '';
        $fields['id_form'] = '';
        
        return $fields;        
    }
    
}