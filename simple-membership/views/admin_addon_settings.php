<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="swpm-grey-box">
<p>
    <?php echo esc_html__( "Some of the simple membership plugin's addon settings and options will be displayed here (if you have them)", 'simple-membership' ) ?>
</p>
</div>
<form action="" method="POST">
    <input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />
    <?php do_action('swpm_addon_settings_section');
    wp_nonce_field('swpm_addon_settings_section','swpm_addon_settings_section_save_settings');
    submit_button(SwpmUtils::_('Save Changes'), 'primary', 'swpm-addon-settings'); ?>
</form>
