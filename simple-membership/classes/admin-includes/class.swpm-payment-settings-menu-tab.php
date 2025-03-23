<?php

class SWPM_Payment_Settings_Menu_Tab {

    function __construct() {

    }

    /*
    * This function is used to render and handle the payment settings menu tab.
    */
    public function handle_payment_settings_menu_tab() {
        do_action('swpm_payment_settings_menu_tab_start');

        $settings = SwpmSettings::get_instance();

        //Check current_user_can() or die.
        SwpmMiscUtils::check_user_permission_and_is_admin('Payment Settings Menu Tab');

        //Tab overview message and documentation link.
        echo '<div class="swpm-grey-box">';
        _e('You can create membership payment buttons from the ', 'simple-membership');
        echo '<a href="admin.php?page=simple_wp_membership_payments&tab=create_new_button" target="_blank">' . __('create new button tab', 'simple-membership') . '</a>';
        _e(' of this plugin (useful if you want to offer paid memberships on the site).', 'simple-membership');
        _e(' Read the ', 'simple-membership');
        echo '<a href="https://simple-membership-plugin.com/simple-membership-documentation/#membership-payment-options" target="_blank">' . __('membership payment section', 'simple-membership') . '</a>';
        _e(' of our documentation to learn more about creating membership payment buttons.', 'simple-membership');
        echo '</div>';

        //Sub nav tabs related code.
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : '';
        $selected_subtab = $subtab;
        ?>
        <!-- start payment settings menu's sub nav tabs -->
        <h3 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ($subtab == '' || $subtab == 'ps_general') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_general"><?php _e('General', 'simple-membership'); ?></a>
            <a class="nav-tab <?php echo ($subtab == 'ps_pp_api') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_api"><?php _e('PayPal API', 'simple-membership'); ?></a>
            <a class="nav-tab <?php echo ($subtab == 'ps_pp_webhooks') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_webhooks"><?php _e('PayPal Webhooks', 'simple-membership'); ?></a>
            <a class="nav-tab <?php echo ($subtab == 'ps_stripe') ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_stripe"><?php _e('Stripe Settings', 'simple-membership'); ?></a>
            <!-- See example in the "class.swpm-payments-admin-menu.php" file -->
        </h3>
        <br />
        <?php

        //Handle the webhook create/delete requests
        if (isset($_GET['swpm_paypal_create_live_webhook'])){
            check_admin_referer( 'swpm_paypal_create_live_webhook' );
            $pp_webhook = new SWPM_PayPal_Webhook();
            $ret = $pp_webhook->check_and_create_webhook_for_live_mode();

            //Create the response message.
            $pre_msg_html_markup = '';
            if( isset($ret['status']) && ($ret['status'] == 'yes' )){
                //Add an extra checkmark in the message for visual appeal.
                $pre_msg_html_markup .= '<span class="dashicons dashicons-yes" style="color:green;"></span>' . __(' Success! ', 'simple-membership');
            }
            $live_wh_create_result_msg = isset($ret['msg']) ? sanitize_text_field($ret['msg']) : '';
            echo '<div class="swpm-yellow-box"><p><strong>Create Live Webhook: </strong>' . $pre_msg_html_markup . esc_attr($live_wh_create_result_msg) . '</p></div>';
        }
        if (isset($_GET['swpm_paypal_create_sandbox_webhook'])){
            check_admin_referer( 'swpm_paypal_create_sandbox_webhook' );
            $pp_webhook = new SWPM_PayPal_Webhook();
            $ret = $pp_webhook->check_and_create_webhook_for_sandbox_mode();

            //Create the response message.
            $pre_msg_html_markup = '';
            if( isset($ret['status']) && ($ret['status'] == 'yes' )){
                //Add an extra checkmark in the message for visual appeal.
                $pre_msg_html_markup = '<span class="dashicons dashicons-yes" style="color:green;"></span>' . __(' Success! ', 'simple-membership');
            }
            $sandbox_wh_create_result_msg = isset($ret['msg']) ? sanitize_text_field($ret['msg']) : '';
            echo '<div class="swpm-yellow-box"><p><strong>Create Sandbox Webhook: </strong>' . $pre_msg_html_markup . esc_attr($sandbox_wh_create_result_msg) . '</p></div>';
        }
        if (isset($_GET['swpm_paypal_delete_webhook'])){
            check_admin_referer( 'swpm_paypal_delete_webhook' );
            $pp_webhook = new SWPM_PayPal_Webhook();
            $delete_action_result = $pp_webhook->check_and_delete_webhooks_for_both_modes();
            echo '<div class="swpm-yellow-box"><p>' . $delete_action_result . '</p></div>';
        }

        if (isset($_GET['swpm_ppcp_after_onboarding'])){
            $environment_mode = isset($_GET['environment_mode']) ? sanitize_text_field($_GET['environment_mode']) : '';
            $onboarding_action_result = '<p>PayPal merchant account connection setup completed for environment mode: '. esc_attr($environment_mode) .'</p>';
            echo '<div class="swpm-yellow-box"><p>' . $onboarding_action_result . '</p></div>';
        }

        if (isset($_GET['swpm_ppcp_disconnect_production'])){
            //Verify nonce
            check_admin_referer( 'swpm_ac_disconnect_nonce_production' );

            SWPM_PayPal_PPCP_Onboarding_Serverside::reset_seller_api_credentials('production');
            $disconnect_action_result = __('PayPal account disconnected.', 'simple-membership');
            echo '<div class="swpm-yellow-box"><p>' . $disconnect_action_result . '</p></div>';
        }        
        if (isset($_GET['swpm_ppcp_disconnect_sandbox'])){
            //Verify nonce
            check_admin_referer( 'swpm_ac_disconnect_nonce_sandbox' );

            SWPM_PayPal_PPCP_Onboarding_Serverside::reset_seller_api_credentials('sandbox');
            $disconnect_action_result = __('PayPal sandbox account disconnected.', 'simple-membership');
            echo '<div class="swpm-yellow-box"><p>' . $disconnect_action_result . '</p></div>';
        }
        
        // Check test-mode settings submit.
        if (isset($_POST['swpm-enable-test-mode-submit']) && check_admin_referer('swpm-enable-test-mode-nonce')) {
            $settings->set_value('enable-sandbox-testing' ,(( isset($_POST['enable-sandbox-testing']) && $_POST['enable-sandbox-testing'] == '1' ) ? "checked=\"checked\"" : ''));
            $settings->save();

            //Live/Test mode settings updated/changed. Delete the PayPal access token cache.
            SWPM_PayPal_Bearer::delete_cached_token();
            SwpmLog::log_simple_debug('Live/Test mode settings updated. Deleted the PayPal access token cache so a new one is generated.', true);

            echo '<div class="notice notice-success"><p>' . __('Test mode settings updated successfully.', 'simple-membership') . '</p></div>';
        }

        // Check PayPal settings submit.
        if (isset($_POST['swpm-paypal-settings-submit']) && check_admin_referer('swpm-paypal-settings-nonce')) {
            $settings->set_value('paypal-live-client-id' ,( isset($_POST['paypal-live-client-id']) ? sanitize_text_field($_POST['paypal-live-client-id']) : ''));
            $settings->set_value('paypal-live-secret-key' ,( isset($_POST['paypal-live-secret-key']) ? sanitize_text_field($_POST['paypal-live-secret-key']) : ''));
            $settings->set_value('paypal-sandbox-client-id' ,( isset($_POST['paypal-sandbox-client-id']) ? sanitize_text_field($_POST['paypal-sandbox-client-id']) : ''));
            $settings->set_value('paypal-sandbox-secret-key' ,( isset($_POST['paypal-sandbox-secret-key']) ? sanitize_text_field($_POST['paypal-sandbox-secret-key']) : ''));

            $settings->save();
            echo '<div class="notice notice-success"><p>' . __('PayPal API settings updated successfully.', 'simple-membership') . '</p></div>';
        }

        if (isset($_GET['swpm_paypal_delete_cache'])){
            check_admin_referer( 'swpm_paypal_delete_cache' );
            SWPM_PayPal_Bearer::delete_cached_token();
            echo '<div class="notice notice-success"><p>' . __('PayPal access token cache deleted successfully.', 'simple-membership') . '</p></div>';
        }
        
        // Check Stripe settings submit.
        if (isset($_POST['swpm-stripe-settings-submit']) && check_admin_referer('swpm-stripe-settings-nonce')) {
            $settings->set_value('stripe-prefill-member-email' ,( isset($_POST['stripe-prefill-member-email']) && esc_attr($_POST['stripe-prefill-member-email']) == '1' ? "checked=\"checked\"" : ''));
            $settings->set_value('stripe-test-public-key' ,( isset($_POST['stripe-test-public-key']) ? sanitize_text_field($_POST['stripe-test-public-key']) : ''));
            $settings->set_value('stripe-test-secret-key' ,( isset($_POST['stripe-test-secret-key']) ? sanitize_text_field($_POST['stripe-test-secret-key']) : ''));
            $settings->set_value('stripe-live-public-key' ,( isset($_POST['stripe-live-public-key']) ? sanitize_text_field($_POST['stripe-live-public-key']) : ''));
            $settings->set_value('stripe-live-secret-key' ,( isset($_POST['stripe-live-secret-key']) ? sanitize_text_field($_POST['stripe-live-secret-key']) : ''));
            $settings->set_value('stripe-webhook-signing-secret' ,( isset($_POST['stripe-webhook-signing-secret']) ? sanitize_text_field($_POST['stripe-webhook-signing-secret']) : ''));

            $settings->save();
            echo '<div class="notice notice-success"><p>' . __('Stripe settings updated successfully.', 'simple-membership') . '</p></div>';
        }

        //Switch case for the various different sub-tabs.
        switch ($selected_subtab) {
            case 'ps_general':
                $this->handle_general_payment_settings_subtab();
                break;
            case 'ps_pp_api':
                $this->handle_paypal_payment_settings_subtab();
                break;
            case 'ps_pp_webhooks':
                $this->handle_paypal_webhook_settings_subtab();
                break;
            case 'ps_stripe':
                $this->handle_stripe_payment_settings_subtab();
                break;
            default:
                $this->handle_general_payment_settings_subtab();
                break;
        }

        //End of the payment settings menu tab.
        do_action('swpm_payment_settings_menu_tab_end');
    }

    /**
     * Render general payment settings subtab view
     *
     * @return void
     */
    public function handle_general_payment_settings_subtab(){
        $settings = SwpmSettings::get_instance();

        // Test-mode settings
        $enable_sandbox_testing = $settings->get_value( 'enable-sandbox-testing' );
        ?>
        <div class="postbox">
            <h2><?php _e("Sandbox or Test Mode Payment Settings", 'simple-membership'); ?></h2>

            <div class="inside">
                <p>
                    <?php _e( 'This section allows you to enable/disable sandbox or test mode for the payment buttons and transactions.', 'simple-membership' ); ?>
                </p>
                <form action="" method="POST">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="">
                                <?php _e('Enable Sandbox or Test Mode', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" name="enable-sandbox-testing" value="1" <?php echo $enable_sandbox_testing;?> />
                                <p class="description">
                                <?php _e('Enable this option if you want to do sandbox payment testing. Keep it unchecked for live/production mode.', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="swpm-enable-test-mode-submit" class="button-primary" value="<?php _e('Save Changes', 'simple-membership'); ?>" />
                    <?php wp_nonce_field('swpm-enable-test-mode-nonce');?>
                </form>
                <div class="swpm-blue-box">
                    <?php
                    echo '<strong>' . __('Note for Subscription/Recurring Payment Testing: ','simple-membership') . '</strong>';
                    _e('Subscription plan modes cannot be switched between test and live modes. For testing, create all subscription plans and buttons in test mode. ', 'simple-membership'); 
                    _e('Once testing is complete, switch the plugin to live mode and recreate the plans and buttons for live use. Avoid reusing test mode plans/buttons in live mode to prevent errors from the payment gateway. ', 'simple-membership');
                    echo '<a href="https://simple-membership-plugin.com/payment-testing-checklist-for-simple-membership/" target="_blank">' . __('Read this payment testing checklist.', 'simple-membership') . '</a>';
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render paypal payment settings subtab view
     *
     * @return void
     */
    public function handle_paypal_payment_settings_subtab(){
        $settings = SwpmSettings::get_instance();
        // Paypal settings
        $paypal_live_client_id = $settings->get_value( 'paypal-live-client-id' );
        $paypal_live_secret_key = $settings->get_value( 'paypal-live-secret-key' );
        $paypal_sandbox_client_id = $settings->get_value( 'paypal-sandbox-client-id' );
        $paypal_sandbox_secret_key = $settings->get_value( 'paypal-sandbox-secret-key' );
        ?>

        <!-- PayPal PPCP Connection postbox -->
        <!-- <div class="postbox">
            <h2 id="paypal-ppcp-connection-section"><?php _e("PayPal Account Connection", 'simple-membership'); ?></h2>
            <div class="inside">
                <?php 
                $this->paypal_ppcp_connection_settings();
                ?>
            </div>
        </div> -->

        <!-- Paypal Settings postbox -->
        <div class="postbox">
            <h2><?php _e("PayPal API Credentials", 'simple-membership'); ?></h2>
            <div class="inside">
                <p>
                    <?php 	
                    _e( 'Configure the PayPal API credentials for the new PayPal checkout.', 'simple-membership' );
                    echo '&nbsp;' . '<a href="https://simple-membership-plugin.com/getting-paypal-api-credentials" target="_blank">' . SwpmUtils::_( 'Read this documentation' ) . '</a> ' . SwpmUtils::_( 'to learn how to get your PayPal API credentials.' );
                    ?>
                </p>
                <form action="" method="POST">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Client ID', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-live-client-id" size="100" value="<?php echo $paypal_live_client_id; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Client ID for live mode.', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Secret Key', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-live-secret-key" size="100" value="<?php echo $paypal_live_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Secret Key for live mode.', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Sandbox Client ID', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-sandbox-client-id" size="100" value="<?php echo $paypal_sandbox_client_id; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Client ID for sandbox mode.', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Sandbox Secret Key', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-sandbox-secret-key" size="100" value="<?php echo $paypal_sandbox_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Secret Key for sandbox mode.', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="swpm-paypal-settings-submit" class="button-primary" value="<?php _e('Save Changes', 'simple-membership'); ?>" />
                    <?php wp_nonce_field('swpm-paypal-settings-nonce');?>
                </form>
            </div>
        </div>

        <div class="postbox">
            <h2 id="paypal-delete-token-cache-section"><?php _e("Delete PayPal API Access Token Cache", 'simple-membership'); ?></h2>
            <div class="inside">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Delete PayPal Token Cache', 'simple-membership'); ?></th>
                        <td>
                            <?php
                            $delete_cache_url = admin_url( 'admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_api&swpm_paypal_delete_cache=1' );
                            $delete_cache_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_delete_cache' ), $delete_cache_url );
                            echo '<p><a class="button swpm-paypal-delete-cache-btn" href="'.esc_url_raw($delete_cache_url_nonced).'">'.__('Delete Token Cache', 'simple-membership').'</a></p>';
                            echo '<p class="description">' . __('This will delete the PayPal API access token cache. This is useful if you are having issues with the PayPal API after changing/updating the API credentials.', 'simple-membership') . '</p>';
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>        
        <?php
    }

    /**
     * Render paypal webhook settings subtab view
     *
     * @return void
     */
    public function handle_paypal_webhook_settings_subtab(){
        $settings = SwpmSettings::get_instance();
                    
        $all_api_creds_missing = false;
        if( empty( $settings->get_value('paypal-sandbox-client-id')) && empty( $settings->get_value('paypal-sandbox-secret-key')) && empty( $settings->get_value('paypal-live-client-id')) && empty( $settings->get_value('paypal-live-secret-key')) ){
            $all_api_creds_missing = true;
        }
        ?>
        <!-- Paypal Webhooks postbox -->
        <div class="postbox">
            <h2 id="paypal-subscription-webhooks"><?php _e("PayPal Webhooks", 'simple-membership'); ?></h2>
            <div class="inside">
                <p><?php _e( 'The PayPal payment buttons that uses the new API require webhooks. The plugin will auto-create the required webhooks when you create a PayPal payment button.', 'simple-membership'); ?></p>
                <p><?php _e( 'If you have issues with the webhooks, you can delete it and create again.', 'simple-membership'); ?></p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Live Webhook Status', 'simple-membership'); ?></th>
                            <td>
                                <?php
                                $production_webhook_id = get_option( 'swpm_paypal_webhook_id_production' );
                                if( !empty($production_webhook_id)){
                                    //Production webhook exists
                                    echo '<span class="swpm-paypal-live-webhook-status"><span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;';
                                    _e( 'Live Webhook exists. If you still have issues with webhooks, you can delete it and create again.', 'simple-membership' );
                                    echo '</span>';
                                } else {
                                    //Production webhook does not exist
                                    if( empty( $settings->get_value('paypal-live-client-id')) || empty( $settings->get_value('paypal-live-secret-key')) ){
                                        echo '<p><span class="swpm-paypal-live-webhook-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
                                        _e( 'Live PayPal API credentials are not set. Please set the Live PayPal API credentials first.', 'simple-membership');
                                        echo '</span></p>';
                                    } else {
                                        echo '<span class="swpm-paypal-live-webhook-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
                                        _e( 'No webhook found. Use the following link to create a new webhook for live mode.', 'simple-membership' );
                                        echo '</span>';
                                        $create_live_webhook_url = admin_url( 'admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_webhooks&swpm_paypal_create_live_webhook=1' );
                                        $create_live_webhook_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_create_live_webhook' ), $create_live_webhook_url );
                                        echo '<p><a class="button swpm-paypal-create-live-webhook-btn" href="' . esc_url_raw( $create_live_webhook_url_nonced ) . '">'.SwpmUtils::_('Create Live Webhook').'</a></p>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Test Webhook Status', 'simple-membership'); ?></th>
                            <td>
                                <?php
                                $sandbox_webhook_id = get_option( 'swpm_paypal_webhook_id_sandbox' );
                                if( !empty($sandbox_webhook_id)){
                                    //Sandbox webhook exists
                                    echo '<span class="swpm-paypal-sandbox-webhook-status"><span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;';
                                    _e( 'Sandbox Webhook exists. If you still have issues with webhooks, you can delete it and create again.', 'simple-membership');
                                    echo '</span>';
                                } else {
                                    //Sandbox webhook does not exist
                                    if( empty( $settings->get_value('paypal-sandbox-client-id')) || empty( $settings->get_value('paypal-sandbox-secret-key')) ){
                                        echo '<p><span class="swpm-paypal-sandbox-webhook-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
                                        _e( 'Sanbbox PayPal API credentials are not set. Please set the Sandbox PayPal API credentials first.', 'simple-membership' );
                                        echo '</span></p>';
                                    } else {
                                        echo '<span class="swpm-paypal-sandbox-webhook-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
                                        _e( 'No webhook found. Use the following link to create a new webhook for test mode.', 'simple-membership' );
                                        echo '</span>';
                                        $create_sandbox_webhook_url = admin_url( 'admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_webhooks&swpm_paypal_create_sandbox_webhook=1' );
                                        $create_sandbox_webhook_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_create_sandbox_webhook' ), $create_sandbox_webhook_url );
                                        echo '<p><a class="button swpm-paypal-create-sandbox-webhook-btn" href="' . esc_url_raw( $create_sandbox_webhook_url_nonced ) . '">'.SwpmUtils::_('Create Sandbox Webhook').'</a></p>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Delete Webhooks', 'simple-membership'); ?></th>
                            <td>
                                <?php
                                    if( $all_api_creds_missing ){
                                        echo '<p><span class="swpm-paypal-delete-webhook-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp';
                                        _e( 'PayPal API credentials are missing. Please set the PayPal API credentials.', 'simple-membership' ); 
                                        echo '</span></p>';
                                    } else {
                                        $delete_webhook_url = admin_url( 'admin.php?page=simple_wp_membership_payments&tab=payment_settings&subtab=ps_pp_webhooks&swpm_paypal_delete_webhook=1' );
                                        $delete_webhook_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_delete_webhook' ), $delete_webhook_url );
                                        echo '<p><a class="button swpm-paypal-delete-webhook-btn" href="'.esc_url_raw($delete_webhook_url_nonced).'">'.__('Delete Webhooks', 'simple-membership').'</a></p>';					
                                    }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render stripe payment settings subtab view
     *
     * @return void
     */
    public function handle_stripe_payment_settings_subtab(){
        $settings = SwpmSettings::get_instance();
        // Stripe settings
        $stripe_prefill_member_email = $settings->get_value( 'stripe-prefill-member-email' );
        $stripe_test_public_key = $settings->get_value( 'stripe-test-public-key' );
        $stripe_test_secret_key = $settings->get_value( 'stripe-test-secret-key' );
        $stripe_live_public_key = $settings->get_value( 'stripe-live-public-key' );
        $stripe_live_secret_key = $settings->get_value( 'stripe-live-secret-key' );
	    $stripe_webhook_signing_secret_key = $settings->get_value( 'stripe-webhook-signing-secret' );
        ?>
        <!-- Stripe Settings postbox -->
        <div class="postbox">
            <h2><?php _e("Stripe Global Settings", 'simple-membership'); ?></h2>   
                <div class="inside">
                <p>
                    <?php _e( 'This section allows you to configure Stripe payment related settings.', 'simple-membership' ); ?>
                </p>
                <form action="" method="POST">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="">
                                    <?php _e('Pre-fill Member Email Address', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" name="stripe-prefill-member-email" value="1" <?php echo $stripe_prefill_member_email; ?>/>
                                <p class="description">
                                    <?php _e('Pre-fills the email address of the logged-in member on the Stripe checkout form when possible', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Test Publishable Key', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-test-public-key" size="100" value="<?php echo $stripe_test_public_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Test publishable key', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Test Secret Key', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-test-secret-key" size="100" value="<?php echo $stripe_test_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Test secret key', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Publishable Key', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-live-public-key" size="100" value="<?php echo $stripe_live_public_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Live publishable key', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Secret Key', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-live-secret-key" size="100" value="<?php echo $stripe_live_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Live secret key', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
				                    <?php _e('Webhook Signing Secret Key (Optional)', 'simple-membership'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-webhook-signing-secret" size="100" value="<?php echo $stripe_webhook_signing_secret_key; ?>">
                                <p class="description">
				                    <?php _e('Enter a webhook signing secret key to apply webhook event protection.', 'simple-membership'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="swpm-stripe-settings-submit" class="button-primary" value="<?php _e('Save Changes', 'simple-membership'); ?>" />
                    <?php wp_nonce_field('swpm-stripe-settings-nonce');?>
                </form>
            </div>
        </div>
        <?php
    }

	public function paypal_ppcp_connection_settings() {
        $settings = SwpmSettings::get_instance();

		$ppcp_onboarding_instance = SWPM_PayPal_PPCP_Onboarding::get_instance();

		//If all API credentials are missing, show a message.
		$all_api_creds_missing = false;
		if( empty( $settings->get_value('paypal-sandbox-client-id')) && empty( $settings->get_value('paypal-sandbox-secret-key')) && empty( $settings->get_value('paypal-live-client-id')) && empty( $settings->get_value('paypal-live-secret-key')) ){
			$all_api_creds_missing = true;
		}
		?>
		<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php _e('Live Account Connnection Status', 'simple-membership'); ?></th>
				<td>
					<?php
					// Check if the live account is connected
					$live_account_connection_status = 'connected';
					if( empty( $settings->get_value('paypal-live-client-id')) || empty( $settings->get_value('paypal-live-secret-key')) ){
						//Sandbox API keys are missing. Account is not connected.
						$live_account_connection_status = 'not-connected';
					}

					if( $live_account_connection_status == 'connected'){
						//Production account connected
						echo '<div class="swpm-paypal-live-account-connection-status"><span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;';
						_e( 'Live account is connected. If you experience any issues, please disconnect and reconnect.', 'simple-membership' );
						echo '</div>';
						// Show disconnect option for live account.
						$ppcp_onboarding_instance->output_production_ac_disconnect_link();
					} else {
						//Production account is NOT connected.
						echo '<div class="swpm-paypal-live-account-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
						_e( 'Live PayPal account is not connected.', 'simple-membership');
						echo '</div>';

						// Show the onboarding link
						$ppcp_onboarding_instance->output_production_onboarding_link_code();
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Sandbox Account Connnection Status', 'simple-membership'); ?></th>
				<td>
					<?php
					//Check if the sandbox account is connected
					$sandbox_account_connection_status = 'connected';
					if( empty( $settings->get_value('paypal-sandbox-client-id')) || empty( $settings->get_value('paypal-sandbox-secret-key')) ){
						//Sandbox API keys are missing. Account is not connected.
						$sandbox_account_connection_status = 'not-connected';
					}

					if( $sandbox_account_connection_status == 'connected'){
						//Test account connected
						echo '<div class="swpm-paypal-test-account-connection-status"><span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;';
						_e( 'Sandbox account is connected. If you experience any issues, please disconnect and reconnect.', 'simple-membership' );
						echo '</div>';
						//Show disconnect option for sandbox account.
						$ppcp_onboarding_instance->output_sandbox_ac_disconnect_link();
					} else {
						//Sandbox account is NOT connected.
						echo '<div class="swpm-paypal-sandbox-account-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
						_e( 'Sandbox PayPal account is not connected.', 'simple-membership');
						echo '</div>';

						//Show the onboarding link for sandbox account.
						$ppcp_onboarding_instance->output_sandbox_onboarding_link_code();
					}
					?>
				</td>
			</tr>

		</tbody>
		</table>
		<?php
	}

}

