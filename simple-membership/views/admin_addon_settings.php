
<p>
    <?php echo SwpmUtils::_("Some of the simple membership plugin's addon settings and options will be displayed here (if you have them)") ?>
</p>
<form action="" method="POST">
    <input type="hidden" name="tab" value="<?php echo $current_tab; ?>" />
    <?php do_action('swpm_addon_settings_section'); ?>
    <?php submit_button(SwpmUtils::_('Save Changes'), 'primary', 'swpm-addon-settings'); ?>
</form>
