<?php
//This file is loaded when the user clicks on the password reset link in the email to arrive at the password reset page. 
//The following query args are expected: key=XYZ&login=ABC

$is_valid_key = check_password_reset_key($_GET['key'], $_GET['login']);
$user_login = sanitize_text_field($_GET['login']);

if ( is_wp_error( $is_valid_key ) ) {

    if(isset($_POST['swpm-password-reset-using-link'])){
        //Reset with link form submitted. Just return. It is normal for the Key to be invalid after a successful rest operation. 
        //So no need to display any message here (it will be displayed in the standard password reset shortcode).                
        return;
    }
    $error_message = __("Error! The password reset key is either invalid or expired. Please generate a new password reset request.", "simple-membership");
    echo '<div class="swpm-pw-reset-key-invalid-error swpm-red-error-text">';
    echo $error_message;
    echo '</div>';
    return;
}

SimpleWpMembership::enqueue_validation_scripts();
$settings = SwpmSettings::get_instance();
$force_strong_pass = $settings->get_value('force-strong-passwords');
if (!empty($force_strong_pass)) {
    $pass_class = apply_filters("swpm_registration_strong_pass_validation", "validate[required,custom[strongPass],minSize[8]]");
} else {
    $pass_class = "";
}

//CSS class for the pass reset submit button
$pass_reset_submit_class = 'swpm-pw-reset-submit';
$render_new_form_ui = SwpmSettings::get_instance()->get_value('use-new-form-ui');
if ( !empty( $render_new_form_ui ) ){
    $pass_reset_submit_class .= ' swpm-submit-btn-default-style';
}

?>
<div class="swpm-pw-reset-using-link-widget-form">

    <div class="swpm-pw-reset-widget-error swpm-red-error-text">
        <!-- Display any error message from the password reset operation here -->
        <?php echo get_transient("swpm-passsword-reset-error"); ?>
        <?php delete_transient("swpm-passsword-reset-error"); ?>
    </div>

    <form id="swpm-password-reset-using-link" name="swpm-password-reset-using-link" class="swpm-validate-form" method="post" action="">
        <div class="swpm-pw-reset-widget-inside">
        <?php 
            $settings = SwpmSettings::get_instance();
            $force_strong_pass = $settings->get_value('force-strong-passwords');
            if( !empty($force_strong_pass) ) {
                echo '<div class="swpm-pw-reset-strong-pass-message swpm-margin-top-10">';
                _e('Password must be at least 8 characters long, contain at least 1 uppercase and 1 lowercase character, and at least 1 digit.', 'simple-membership');
                echo '</div>';
            }
            ?>
            <div class="swpm-pw-reset-email swpm-margin-top-10">
                <label for="swpm_new_password" class="swpm_label swpm-pw-reset-email-label"><?php _e('New password', 'simple-membership') ?></label>
            </div>
            <div class="swpm-pw-reset-email-input swpm-margin-top-10">
                <input type="password" name="swpm_new_password" class="<?php echo apply_filters('swpm_registration_input_pass_class', $pass_class); ?>" id="swpm_new_password" value="" size="60" />
            </div>

            <div class="swpm-pw-reset-email swpm-margin-top-10">
                <label for="swpm_reenter_new_password" class="swpm_label swpm-pw-reset-email-label"><?php _e('Re-enter new password', 'simple-membership') ?></label>
            </div>
            <div class="swpm-pw-reset-email-input swpm-margin-top-10">
                <input type="password" name="swpm_reenter_new_password" class="<?php echo apply_filters('swpm_registration_input_pass_class', $pass_class); ?>" id="swpm_reenter_new_password" value="" size="60" />
            </div>

            <input type="hidden" name="swpm_user_login" value="<?php echo esc_attr($user_login); ?>" />
            <div class="swpm-before-login-submit-section swpm-margin-top-10"><?php echo apply_filters('swpm_before_pass_reset_form_submit_button', ''); ?></div>
            <div class="swpm-pw-reset-submit-button">
                <input type="submit" name="swpm-password-reset-using-link" class="<?php echo esc_attr($pass_reset_submit_class); ?>" value="<?php _e('Reset Password', 'simple-membership'); ?>" />
            </div>
        </div>
    </form>
</div>