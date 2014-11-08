<?php screen_icon( 'options-general' );?>
<h1><?= BUtils::_('Simple WP Membership::Settings')?></h1>
<div class="wrap">
<?php do_action("swpm-draw-tab"); ?>
    <form action="" method="POST">
        <input type="hidden" name="tab" value="<?php echo $current_tab;?>" />
        <?php do_action('swpm_addon_settings_section');?>
        <?php submit_button('Save Changes', 'primary', 'swpm-addon-settings'); ?>
    </form>
</div><!-- end of wrap -->