<?php 
//This file is used to render the settings form for the admin settings page.
//We can also use settings section (with empty heading) inside the tab_1() or tab_2() method to render arbitrary HTML code. 
//Search the code with 'swpm-documentation' to see an example of this.
//However, you can't add your own forms in there since it will be wrapped by the main settings form (so it will be a form inside a form situation).
//You can create links to use GET query arguments.

?>
<!-- This file outputs the settings form fields for a lot of the settings pages -->
<form action="options.php" method="POST">
    <input type="hidden" name="tab" value="<?php echo $current_tab;?>" />
    <?php settings_fields('swpm-settings-tab-' . $current_tab); ?>
    <?php do_settings_sections('simple_wp_membership_settings'); ?>
    <?php submit_button(); ?>
</form>

