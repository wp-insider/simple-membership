<div class="wrap">
    <h1><?php echo  SwpmUtils::_('Simple WP Membership::Membership Levels') ?>
        <a href="admin.php?page=simple_wp_membership_levels&level_action=add" class="add-new-h2"><?php SwpmUtils::e('Add New', 'Level'); ?></a></h1>    
    <?php include_once(SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_membership_level_menu.php'); ?>   
    <?php echo $output ?>
</div><!-- end of .wrap -->
