<?php
global $wpdb;

//TODO - this code maybe reused for the webhook delete/refresh button. 
// if (isset($_POST['swpm_generate_adv_code'])) {
//     $paypal_ipn_url = SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL . '/?swpm_process_ipn=1';
//     $mem_level = trim(sanitize_text_field($_POST['swpm_paypal_adv_member_level']));
//     echo '<div id="message" class="updated fade"><p>';
//     echo '<strong>Paste the code below in the "Add advanced variables" field of your PayPal button for membership level ' . $mem_level . '</strong>';
//     echo '</p></div>';
// }

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

//Any other arbitrary HTML code and forms can be added here.
?>

<!-- render the rest of the settings fields for tab-2 -->
<form action="options.php" method="POST">
    <input type="hidden" name="tab" value="2" />
    <?php settings_fields('swpm-settings-tab-' . $current_tab); ?>
    <?php do_settings_sections('simple_wp_membership_settings'); ?>
    <?php submit_button(); ?>
</form>
