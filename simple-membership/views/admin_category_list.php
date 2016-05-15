    

<div class="swpm-yellow-box">    
    <p>
        <?php echo SwpmUtils::_('First of all, globally protect the category on your site by selecting "General Protection" from the drop-down box below and then select the categories that should be protected from non-logged in users.'); ?>
    </p>
    <p>
        <?php echo SwpmUtils::_('Next, select an existing membership level from the drop-down box below and then select the categories you want to grant access to (for that particular membership level).'); ?>
    </p>
    <p>
        Read the <a href="https://simple-membership-plugin.com/use-category-protection-membership-site/" target="_blank">category protection documentation</a> to learn more.
    </p>
</div>

<form id="category_list_form" method="post">    
    <p class="swpm-select-box-left">
        <label for="membership_level_id"><?php SwpmUtils::e('Membership Level:'); ?></label>
        <select id="membership_level_id" name="membership_level_id">
            <option <?php echo $category_list->selected_level_id == 1 ? "selected" : "" ?> value="1">General Protection</option>
            <?php echo SwpmUtils::membership_level_dropdown($category_list->selected_level_id); ?>
        </select>                
    </p>
    <p class="swpm-select-box-left"><input type="submit" class="button-primary" name="update_category_list" value="<?php SwpmUtils::e('Update'); ?>"></p>
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
