
<div class="swpm-pw-reset-widget-form">

    <div>
        <?php 
        $is_valid_key = check_password_reset_key($_GET['key'], $_GET['login']);
        $user_login = $_GET['login'];

        
        
        if(is_wp_error($is_valid_key))
        {
           echo $is_valid_key->get_error_message();
           wp_die();
         } 

                  
         ?>


    </div>

    <div class="error">
        <?php echo get_transient("swpm-passsword-reset-error"); ?>
        <?php delete_transient("swpm-passsword-reset-error");?>
    </div>
    

    <form id="swpm-password-reset-using-link" name="swpm-password-reset-using-link" method="post" action="">
        <div class="swpm-pw-reset-widget-inside">
            <div class="swpm-pw-reset-email swpm-margin-top-10">
                <label for="swpm_new_password" class="swpm_label swpm-pw-reset-email-label"><?php echo SwpmUtils::_('New password') ?></label>
            </div>
            <div class="swpm-pw-reset-email-input swpm-margin-top-10">
                <input type="password" name="swpm_new_password" class="swpm-text-field swpm-pw-reset-text" id="swpm_new_password"  value="" size="60" />
            </div>


            <div class="swpm-pw-reset-email swpm-margin-top-10">
                <label for="swpm_reenter_new_password" class="swpm_label swpm-pw-reset-email-label"><?php echo SwpmUtils::_('Re-enter new password') ?></label>
            </div>
            <div class="swpm-pw-reset-email-input swpm-margin-top-10">
                <input type="password" name="swpm_reenter_new_password" class="swpm-text-field swpm-pw-reset-text" id="swpm_reenter_new_password"  value="" size="60" />
            </div>


            <input type="hidden" name="swpm_user_login" value="<?php echo $user_login;?>" />
            <div class="swpm-before-login-submit-section swpm-margin-top-10"><?php echo apply_filters('swpm_before_pass_reset_form_submit_button', ''); ?></div>
            <div class="swpm-pw-reset-submit-button swpm-margin-top-10">
                <input type="submit" name="swpm-password-reset-using-link" class="swpm-pw-reset-submit" value="<?php echo SwpmUtils::_('Reset Password'); ?>" />
            </div>
        </div>
    </form>
</div>
