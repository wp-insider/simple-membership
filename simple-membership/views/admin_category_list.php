<div class="wrap">
    <h2><?php screen_icon('users'); ?><?= BUtils::_('Simple WP Membership::Categories') ?></h2>
    <?php include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_membership_level_menu.php'); ?>
    <form id="category_list_form" method="post">    
        <p class="swpm-select-box-left">
            <label for="membership_level_id">
                Membership Level:</label>
            
            <select id="membership_level_id" name="membership_level_id">
                <option <?= $category_list->selected_level_id==1? "selected": "" ?> value="1">General Protection</option>
                <?= BUtils::membership_level_dropdown($category_list->selected_level_id); ?>
            </select>                
        </p>
        <p class="swpm-select-box-left"><i><?= BUtils::_('Select membership you want to set category protection/permission for 
                (page will refresh to load category details for selected membership level). 
                Check/uncheck checkboxes for each of the categories you want to enable/disable protection/permission. Finally
                press "Update" button to save your changes.');?></i></p>
        <p class="swpm-select-box-left"><input type="submit" name="update_category_list" value="Update"></p>        
        <?php $category_list->prepare_items(); ?>   
        <?php $category_list->display(); ?>
    </form>
</div><!-- end of .wrap -->
<script type="text/javascript">
    jQuery(document).ready(function($){
        $('#membership_level_id').change(function(){
            $('#category_list_form').submit();
        });
    });
</script>
