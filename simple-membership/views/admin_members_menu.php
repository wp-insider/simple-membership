<h2 class="nav-tab-wrapper">
    <a class="nav-tab <?php echo ($selected == "") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership"><?php echo SwpmUtils::_('Members') ?></a>
    <a class="nav-tab <?php echo ($selected == "add") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership&member_action=add"><?php echo SwpmUtils::_('Add Member') ?></a>
    <?php
    $menu = apply_filters('swpm_admin_members_menu_hook', array());
    foreach ($menu as $member_action => $title):
        ?>
        <a class="nav-tab <?php echo ($selected == $member_action) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership&member_action=<?php echo $member_action; ?>" ><?php SwpmUtils::e($title) ?></a>
        <?php
    endforeach;
    ?>
</h2>