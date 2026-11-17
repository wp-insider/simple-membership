<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    

<div class="swpm-yellow-box">    
    <p>
        <?php echo esc_html__( 'First of all, globally protect the category on your site by selecting "General Protection" from the drop-down box below and then select the categories that should be protected from non-logged in users.', 'simple-membership' ); ?>
    </p>
    <p>
        <?php echo esc_html__( 'Next, select an existing membership level from the drop-down box below and then select the categories you want to grant access to (for that particular membership level).', 'simple-membership' ); ?>
    </p>
    <p>
        <?php echo esc_html__( 'Read the ', 'simple-membership' ); ?><a href="https://simple-membership-plugin.com/use-category-protection-membership-site/" target="_blank"><?php echo esc_html__( 'category protection documentation', 'simple-membership' ); ?></a> <?php echo esc_html__( 'to learn more.', 'simple-membership' ); ?>
    </p>
</div>

<form id="category_list_form" method="post">
    <input type="hidden" name="swpm_category_prot_update_nonce" value="<?php echo esc_attr( wp_create_nonce('swpm_category_prot_update_nonce_action') ); ?>" />
    
    <p class="swpm-select-box-left">
        <label for="membership_level_id"><?php esc_attr_e('Membership Level:', 'simple-membership'); ?></label>
        <select id="membership_level_id" name="membership_level_id">
            <option <?php echo $category_list->selected_level_id == 1 ? "selected" : "" ?> value="1"><?php echo esc_html__( 'General Protection', 'simple-membership' ); ?></option>
            <?php echo wp_kses( SwpmUtils::membership_level_dropdown($category_list->selected_level_id), SwpmKsesUtils::wp_kses_select_option_tags() ); ?>
        </select>                
    </p>
    <p class="swpm-select-box-left">
        <input type="submit" class="button-primary" name="update_category_list" value="<?php esc_attr_e('Update', 'simple-membership'); ?>">
    </p>
        <?php $category_list->prepare_items(); ?>   
        <?php $category_list->display(); ?>
</form>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#membership_level_id').change(function() {
            $('#category_list_form').submit();
        });
    });
</script>
