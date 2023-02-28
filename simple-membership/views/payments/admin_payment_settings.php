<?php
//This file is used to render the settings page for the payment settings tab.

//Tab overview message and documentation link.
echo '<div class="swpm-grey-box">';
echo '<p>';
_e('You can create membership payment buttons from the ', 'simple-membership');
echo '<a href="admin.php?page=simple_wp_membership_payments&tab=create_new_button" target="_blank">' . SwpmUtils::_('payments menu') . '</a>';
_e(' of this plugin (useful if you want to offer paid memberships on the site).', 'simple-membership');
_e(' Read the ', 'simple-membership');
echo '<a href="https://simple-membership-plugin.com/simple-membership-documentation/#membership-payment-options" target="_blank">' . SwpmUtils::_('membership payment section') . '</a>';
_e(' of our documentation to learn more about creating membership payment buttons.', 'simple-membership');
echo '</p>';
echo '</div>';

//Any other arbitrary HTML code and forms can be added here. 
//We can also use settings section (with empty heading) inside the tab_2() method to render arbitrary HTML code. 
//However, you can't add forms in there since it will be wrapped by the main settings form. You can create links to use GET query arguments.
//See the "paypal-webhooks-settings" section for example.

//Handle the webhook create/delete requests
if (isset($_GET['swpm_paypal_create_live_webhook'])){
    check_admin_referer( 'swpm_paypal_create_live_webhook' );
    $pp_webhook = new SWPM_PayPal_Webhook();
    $ret = $pp_webhook->check_and_create_webhook_for_live_mode();

    //Create the response message.
	$live_wh_create_result = '';
    if( isset($ret['status']) && ($ret['status'] == 'yes' )){
        //Add an extra checkmark in the message for visual appeal.
        $live_wh_create_result .= '<span class="dashicons dashicons-yes" style="color:green;"></span>' . __(' Success! ', 'simple-membership');
    }
    $live_wh_create_result .= isset($ret['msg']) ? $ret['msg'] : '';
    $live_wh_create_result .= '<p><a href="#paypal-subscription-webhooks">Click here</a> to go to the webhook section below.</p>';
    echo '<div class="swpm-yellow-box"><p><strong>Create Live Webhook: </strong>' . $live_wh_create_result . '</p></div>';

}
if (isset($_GET['swpm_paypal_create_sandbox_webhook'])){
    check_admin_referer( 'swpm_paypal_create_sandbox_webhook' );
    $pp_webhook = new SWPM_PayPal_Webhook();
    $ret = $pp_webhook->check_and_create_webhook_for_sandbox_mode();

    //Create the response message.
	$sandbox_wh_create_result = '';
    if( isset($ret['status']) && ($ret['status'] == 'yes' )){
        //Add an extra checkmark in the message for visual appeal.
        $sandbox_wh_create_result .= '<span class="dashicons dashicons-yes" style="color:green;"></span>' . __(' Success! ', 'simple-membership');
    }    
    $sandbox_wh_create_result .= isset($ret['msg']) ? $ret['msg'] : '';
    $sandbox_wh_create_result .= '<p><a href="#paypal-subscription-webhooks">Click here</a> to go to the webhook section below.</p>';
    echo '<div class="swpm-yellow-box"><p><strong>Create Sandbox Webhook: </strong>' . $sandbox_wh_create_result . '</p></div>';
}
if (isset($_GET['swpm_paypal_delete_webhook'])){
    check_admin_referer( 'swpm_paypal_delete_webhook' );
    $pp_webhook = new SWPM_PayPal_Webhook();
    $delete_action_result = $pp_webhook->check_and_delete_webhooks_for_both_modes();
	$delete_action_result .= '<p><a href="#paypal-subscription-webhooks">Click here</a> to go to the webhook section below.</p>';
    echo '<div class="swpm-yellow-box"><p>' . $delete_action_result . '</p></div>';
}
?>

<!-- render the rest of the settings fields for tab-2 -->
<form action="options.php" method="POST">
    <input type="hidden" name="tab" value="2" />
    <?php settings_fields('swpm-settings-tab-' . $current_tab); ?>
    <?php do_settings_sections('simple_wp_membership_settings'); ?>
    <?php submit_button(); ?>
</form>
