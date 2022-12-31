<?php
global $wpdb;

if (isset($_POST['swpm_generate_adv_code'])) {
    $paypal_ipn_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_ipn=1';
    $mem_level = trim(sanitize_text_field($_POST['swpm_paypal_adv_member_level']));
    $mem_level = absint($mem_level);
    $query = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'swpm_membership_tbl WHERE id !=1 AND id =%d', $mem_level);
    $membership_level_resultset = $wpdb->get_row($query);
    if ($membership_level_resultset) {
        $pp_av_code = 'notify_url=' . $paypal_ipn_url . '<br /> ' . 'custom=subsc_ref=' . $mem_level;
        echo '<div id="message" class="updated fade"><p>';
        echo '<strong>Paste the code below in the "Add advanced variables" field of your PayPal button for membership level ' . $mem_level . '</strong>';
        echo '<br /><br /><code>' . $pp_av_code . '</code>';
        echo '</p></div>';
    } else {
        echo '<div id="message" class="updated fade error"><p><strong>';
        SwpmUtils::e('Error! The membership level ID (' . $mem_level . ') you specified is incorrect. Please check this value again.');
        echo '</strong></p></div>';
    }
}

//Tab overview message and documentation link.
echo '<div class="swpm-grey-box">';
echo '<p>';
SwpmUtils::e('You can create membership payment buttons from the ');
echo '<a href="admin.php?page=simple_wp_membership_payments&tab=create_new_button" target="_blank">' . SwpmUtils::_('payments menu') . '</a>';
SwpmUtils::e(' of this plugin (useful if you want to offer paid memberships on the site).');
SwpmUtils::e(' Read the ');
echo '<a href="https://simple-membership-plugin.com/simple-membership-documentation/#membership-payment-options" target="_blank">' . SwpmUtils::_('membership payment section') . '</a>';
SwpmUtils::e(' of our documentation to learn more about creating membership payment buttons.');
echo '</p>';
echo '</div>';
?>

<h2><?php echo SwpmUtils::_('PayPal Hosted Button Integration Settings'); ?></h2>
<div class="swpm-white-box">
    <p><strong><?php echo SwpmUtils::_('Generate the "Advanced Variables" Code for your PayPal hosted button'); ?></strong></p>

    <form action="" method="post">
        <?php echo SwpmUtils::_('Enter the Membership Level ID'); ?>
        <input type="text" value="" size="4" name="swpm_paypal_adv_member_level">
        <input type="submit" value="<?php echo SwpmUtils::_('Generate Code'); ?>" class="button-primary" name="swpm_generate_adv_code">
    </form>
</div>

<!-- render the rest of the settings fields for tab-2 -->
<form action="options.php" method="POST">
    <input type="hidden" name="tab" value="2" />
    <?php settings_fields('swpm-settings-tab-' . $current_tab); ?>
    <?php do_settings_sections('simple_wp_membership_settings'); ?>
    <?php submit_button(); ?>
</form>
