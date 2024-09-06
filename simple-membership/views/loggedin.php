<?php
//Trigger an action hook
do_action('swpm_before_loggedin_widget');
//Get the auth object
$auth = SwpmAuth::get_instance();

$is_attached_subscription_canceled = SwpmMemberUtils::get_subscription_data_extra_info($auth->get('member_id'), 'subscription_status') === 'inactive';

?>
<div class="swpm-login-widget-logged">
    <div class="swpm-logged-username">
        <div class="swpm-logged-username-label swpm-logged-label"><?php _e('Logged in as', 'simple-membership') ?></div>
        <div class="swpm-logged-username-value swpm-logged-value"><?php echo $auth->get('user_name'); ?></div>
    </div>
    <div class="swpm-logged-status">
        <div class="swpm-logged-status-label swpm-logged-label"><?php _e('Account Status', 'simple-membership') ?></div>
        <div class="swpm-logged-status-value swpm-logged-value"><?php _e(ucfirst($auth->get('account_state')), 'simple-membership'); ?></div>
    </div>
    <div class="swpm-logged-membership">
        <div class="swpm-logged-membership-label swpm-logged-label"><?php _e('Membership', 'simple-membership') ?></div>
        <div class="swpm-logged-membership-value swpm-logged-value"><?php echo $auth->get('alias'); ?></div>
    </div>
    <div class="swpm-logged-expiry">
        <div class="swpm-logged-expiry-label swpm-logged-label"><?php _e('Account Expiry', 'simple-membership') ?></div>
        <div class="swpm-logged-expiry-value swpm-logged-value"><?php echo $auth->get_expire_date(); ?></div>
    </div>

	<?php if ($is_attached_subscription_canceled) { ?>
        <div class="swpm-logged-subs-status">
            <div class="swpm-logged-subs-status-label swpm-logged-label"><?php _e('Subscription Payment Status', 'simple-membership') ?></div>
            <div class="swpm-logged-subs-status-value swpm-logged-value">
                <?php _e('Canceled or Expired', 'simple-membership') ?>
            </div>
            <div class="swpm-logged-subs-status-description">
                <?php _e('You can purchase a new subscription when needed to reactivate', 'simple-membership') ?>
            </div>
        </div>
	<?php } ?>

    <?php
    //Add some spac before the edit profile and logout links
    echo '<div class="swpm-margin-bottom-10"></div>';
    
    //Show the edit profile link
    $edit_profile_page_url = SwpmSettings::get_instance()->get_value('profile-page-url');
    if (!empty($edit_profile_page_url)) {
        //Show the edit profile link
        echo '<div class="swpm-edit-profile-link">';
        echo '<a href="' . $edit_profile_page_url . '">' . __("Edit Profile", 'simple-membership') . '</a>';
        echo '</div>';
    }

    //Show the logout link
    ?>
    <div class="swpm-logged-logout-link">
        <a href="?swpm-logout=true"><?php _e('Logout', 'simple-membership') ?></a>
    </div>
</div>
