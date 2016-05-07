    <h1><?php echo SwpmUtils::_('Simple Membership::Payments') ?></h1>

    <h2 class="nav-tab-wrapper">
        <a class="nav-tab <?php echo ($selected == '') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments"><?php SwpmUtils::e('Transactions'); ?></a>
        <a class="nav-tab <?php echo ($selected == 'payment_buttons') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments&tab=payment_buttons"><?php SwpmUtils::e('Manage Payment Buttons'); ?></a>
        <a class="nav-tab <?php echo ($selected == 'create_new_button') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments&tab=create_new_button"><?php SwpmUtils::e('Create New Button'); ?></a>
    <?php
    $menu = apply_filters('swpm_admin_payments_menu_hook', array());
    $base_url = 'admin.php?page=simple_wp_membership_payments&tab=';
    foreach ($menu as $member_action => $title):
        echo SwpmUtils::build_tab_menu($base_url, $title, $member_action, $selected);
    endforeach;
    if ($selected == 'edit_button') {//Only show the "edit button" tab when a button is being edited.
        echo '<a class="nav-tab nav-tab-active" href="#">Edit Button</a>';
    }
    ?>                
    </h2>