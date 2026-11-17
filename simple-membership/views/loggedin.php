<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//Trigger an action hook
do_action('swpm_before_loggedin_widget');
//Get the auth object
$auth = SwpmAuth::get_instance();

$is_attached_subscription_canceled = SwpmMemberUtils::get_subscription_data_extra_info($auth->get('member_id'), 'subscription_status') === 'inactive';

?>
<div class="swpm-login-widget-logged">
    <div class="swpm-logged-username">
        <div class="swpm-logged-username-label swpm-logged-label"><?php esc_attr_e('Logged in as', 'simple-membership') ?></div>
        <div class="swpm-logged-username-value swpm-logged-value"><?php echo esc_html( apply_filters('swpm_login_widget_logged_in_username', $auth->get('user_name')) ); ?></div>
    </div>
    <div class="swpm-logged-status">
        <div class="swpm-logged-status-label swpm-logged-label"><?php esc_attr_e('Account Status', 'simple-membership') ?></div>
        <div class="swpm-logged-status-value swpm-logged-value"><?php echo esc_html( SwpmUtils::get_account_state_options()[ $auth->get('account_state') ] ?? ucfirst( $auth->get('account_state') ) ); ?></div>
    </div>
    <div class="swpm-logged-membership">
        <div class="swpm-logged-membership-label swpm-logged-label"><?php esc_attr_e('Membership', 'simple-membership') ?></div>
        <div class="swpm-logged-membership-value swpm-logged-value"><?php echo esc_attr( $auth->get('alias') ); ?></div>
    </div>
    <div class="swpm-logged-expiry">
        <div class="swpm-logged-expiry-label swpm-logged-label"><?php esc_attr_e('Account Expiry', 'simple-membership') ?></div>
        <div class="swpm-logged-expiry-value swpm-logged-value"><?php echo esc_html( $auth->get_expire_date() ); ?></div>
    </div>

	<?php if ($is_attached_subscription_canceled) { ?>
        <div class="swpm-logged-subs-status">
            <div class="swpm-logged-subs-status-label swpm-logged-label"><?php esc_attr_e('Subscription Payment Status', 'simple-membership') ?></div>
            <div class="swpm-logged-subs-status-value swpm-logged-value">
                <?php esc_attr_e('Canceled or Expired', 'simple-membership') ?>
            </div>
            <div class="swpm-logged-subs-status-description">
                <?php esc_attr_e('You can purchase a new subscription when needed to reactivate', 'simple-membership') ?>
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
        echo '<a href="' . esc_url( $edit_profile_page_url ) . '">' . esc_html__( "Edit Profile", 'simple-membership' ) . '</a>';
        echo '</div>';
    }

    //Show the logout link
    ?>
    <div class="swpm-logged-logout-link">
        <a href="?swpm-logout=true"><?php esc_attr_e('Logout', 'simple-membership') ?></a>
    </div>
</div>
