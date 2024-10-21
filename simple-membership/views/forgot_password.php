<?php 
//CSS class for the pass reset submit button
$pass_reset_submit_class = 'swpm-pw-reset-submit';
$render_new_form_ui = SwpmSettings::get_instance()->get_value('use-new-form-ui');
if ( !empty( $render_new_form_ui ) ){
    $pass_reset_submit_class .= ' swpm-submit-btn-default-style';
}
?>
<div class="swpm-pw-reset-widget-form">
    <form id="swpm-pw-reset-form" name="swpm-reset-form" method="post" action="">
        <div class="swpm-pw-reset-widget-inside">
            <div class="swpm-pw-reset-email swpm-margin-top-10">
                <label for="swpm_reset_email" class="swpm_label swpm-pw-reset-email-label"><?php _e('Email Address', 'simple-membership') ?></label>
            </div>
            <div class="swpm-pw-reset-email-input swpm-margin-top-10">
                <input type="text" name="swpm_reset_email" class="swpm-text-field swpm-pw-reset-text" id="swpm_reset_email"  value="" size="30" />
            </div>
            <div class="swpm-before-login-submit-section"><?php echo apply_filters('swpm_before_pass_reset_form_submit_button', ''); ?></div>
            <div class="swpm-pw-reset-submit-button">
                <input type="submit" name="swpm-reset" class="<?php echo esc_attr($pass_reset_submit_class); ?>" value="<?php _e('Reset Password', 'simple-membership'); ?>" />
            </div>
        </div>
    </form>
</div>
<div class="swpm_pass_reset_processing_msg_section">
    <p id="swpm_pass_reset_processing_msg" hidden="hidden">
        <?php _e('Processing password reset request...', 'simple-membership'); ?>
    </p>
</div>
<script>
  document.getElementById("swpm-pw-reset-form").addEventListener("submit", function() {
  let p = document.getElementById('swpm_pass_reset_processing_msg');
    p.removeAttribute("hidden");
  });
</script>
