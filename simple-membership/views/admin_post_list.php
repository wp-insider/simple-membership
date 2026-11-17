<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    

<div class="swpm-yellow-box">    
    <p>
        <?php echo esc_html__( 'First of all, globally protect posts and pages on your site by selecting "General Protection" from the drop-down box below and then select posts and pages that should be protected from non-logged in users.', 'simple-membership' ); ?>
    </p>
    <p>
        <?php echo esc_html__( 'Next, select an existing membership level from the drop-down box below and then select posts and pages you want to grant access to (for that particular membership level).', 'simple-membership' ); ?>
    </p>
    <p>    
        <?php echo esc_html__( 'Read the ', 'simple-membership' ); ?><a href="https://simple-membership-plugin.com/apply-protection-posts-pages-bulk/" target="_blank"><?php echo esc_html__( 'bulk protect posts and pages documentation', 'simple-membership' ); ?></a><?php echo esc_html__( ' to learn how to use it.', 'simple-membership' ); ?>
    </p>
</div>
<style>
    #swpm-list-type-nav .nav-tab {
        padding: 1px 15px;
        font-size: 12px;
    }
</style>
<div id="swpm-list-type-nav" class="nav-tab-wrapper">
    <a class="nav-tab<?php echo $post_list->type == 'post' ? ' nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=post_list&list_type=post"><?php esc_attr_e('Posts', 'simple-membership'); ?></a>
    <a class="nav-tab<?php echo $post_list->type == 'page' ? ' nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=post_list&list_type=page"><?php esc_attr_e('Pages', 'simple-membership'); ?></a>
    <a class="nav-tab<?php echo $post_list->type == 'custom_post' ? ' nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_levels&level_action=post_list&list_type=custom_post"><?php esc_attr_e('Custom Posts', 'simple-membership'); ?></a>
</div>

<br />
<div class="swpm_post_protection_list_form">
<form id="post_list_form" method="post">
    <input type="hidden" name="swpm_post_prot_update_nonce" value="<?php echo esc_attr( wp_create_nonce('swpm_post_prot_update_nonce_action') ); ?>" />
    
    <p class="swpm-select-box-left">
        <label for="membership_level_id"><?php esc_attr_e('Membership Level:', 'simple-membership'); ?></label>
        <select id="membership_level_id" name="membership_level_id">
            <option <?php echo $post_list->selected_level_id == 1 ? "selected" : "" ?> value="1"><?php echo esc_html__( 'General Protection', 'simple-membership' ); ?></option>
            <?php echo wp_kses( SwpmUtils::membership_level_dropdown($post_list->selected_level_id), SwpmKsesUtils::wp_kses_select_option_tags() ); ?>
        </select>                
    </p>
    <p class="swpm-select-box-left">
        <input type="submit" class="button-primary" name="update_post_list" value="<?php esc_attr_e('Update', 'simple-membership'); ?>">
    </p>
        <?php $post_list->prepare_items(); ?>   
        <?php $post_list->display(); ?>
    <input type="hidden" name="list_type" value="<?php echo esc_attr($post_list->type); ?>">
</form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('#membership_level_id').change(function () {
            $('#post_list_form').submit();
        });
    });
</script>
