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

        // $output = '';
        // $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
        // $selected = $tab;
        
        //Tab overview message and documentation link.
        echo '<div class="swpm-grey-box">';
        echo '<p>';
        _e('You can create membership payment buttons from the ', 'simple-membership');
        echo '<a href="admin.php?page=simple_wp_membership_payments&tab=create_new_button" target="_blank">' . SwpmUtils::_('create new button tab') . '</a>';
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

        if (isset($_GET['swpm_ppcp_after_onboarding'])){
            $environment_mode = isset($_GET['environment_mode']) ? $_GET['environment_mode'] : '';
            $onboarding_action_result = '<p>PayPal merchant account connection setup completed for environment mode: '. $environment_mode .'</p>';
            $onboarding_action_result .= '<p><a href="#paypal-ppcp-connection-section">Click here</a> to go to the PayPal Account Setup section below.</p>';
            echo '<div class="swpm-yellow-box"><p>' . $onboarding_action_result . '</p></div>';
        }
        if (isset($_GET['swpm_ppcp_sandbox_disconnect'])){
            //Verify nonce
            check_admin_referer( 'swpm_sandbox_ac_disconnect_nonce' );

            SWPM_PayPal_PPCP_Onboarding_Serverside::reset_seller_api_credentials('sandbox');
            $disconnect_action_result = '<p>PayPal sandbox account disconnected.</p>';
            $disconnect_action_result .= '<p><a href="#paypal-ppcp-connection-section">Click here</a> to go to the PayPal Account Setup section below.</p>';
            echo '<div class="swpm-yellow-box"><p>' . $disconnect_action_result . '</p></div>';
        }

        
        // Check test-mode settings submit.
        if (isset($_POST['swpm-enable-test-mode-submit']) && check_admin_referer('swpm-enable-test-mode-nonce')) {
            $settings->set_value('enable-sandbox-testing' ,( isset($_POST['enable-sandbox-testing']) && esc_attr($_POST['enable-sandbox-testing']) == '1' ? "checked=\"checked\"" : ''));

            $settings->save();

            echo '<div class="notice notice-success"><p>' . __('Test mode settings updated successfully ','simple-membership') . '</p></div>';
        }

        // Check stripe settings submit.
        if (isset($_POST['swpm-paypal-settings-submit']) && check_admin_referer('swpm-paypal-settings-nonce')) {
            $settings->set_value('paypal-live-client-id' ,( isset($_POST['paypal-live-client-id']) ? sanitize_text_field($_POST['paypal-live-client-id']) : ''));
            $settings->set_value('paypal-live-secret-key' ,( isset($_POST['paypal-live-secret-key']) ? sanitize_text_field($_POST['paypal-live-secret-key']) : ''));
            $settings->set_value('paypal-sandbox-client-id' ,( isset($_POST['paypal-sandbox-client-id']) ? sanitize_text_field($_POST['paypal-sandbox-client-id']) : ''));
            $settings->set_value('paypal-sandbox-secret-key' ,( isset($_POST['paypal-sandbox-secret-key']) ? sanitize_text_field($_POST['paypal-sandbox-secret-key']) : ''));

            $settings->save();

            echo '<div class="notice notice-success"><p>' . __('Paypal settings updated successfully ','simple-membership') . '</p></div>';
        }
        
        // Check stripe settings submit.
        if (isset($_POST['swpm-stripe-settings-submit']) && check_admin_referer('swpm-stripe-settings-nonce')) {
            $settings->set_value('stripe-prefill-member-email' ,( isset($_POST['stripe-prefill-member-email']) && esc_attr($_POST['stripe-prefill-member-email']) == '1' ? "checked=\"checked\"" : ''));
            $settings->set_value('stripe-test-public-key' ,( isset($_POST['stripe-test-public-key']) ? sanitize_text_field($_POST['stripe-test-public-key']) : ''));
            $settings->set_value('stripe-test-secret-key' ,( isset($_POST['stripe-test-secret-key']) ? sanitize_text_field($_POST['stripe-test-secret-key']) : ''));
            $settings->set_value('stripe-live-public-key' ,( isset($_POST['stripe-live-public-key']) ? sanitize_text_field($_POST['stripe-live-public-key']) : ''));
            $settings->set_value('stripe-live-secret-key' ,( isset($_POST['stripe-live-secret-key']) ? sanitize_text_field($_POST['stripe-live-secret-key']) : ''));

            $settings->save();

            echo '<div class="notice notice-success"><p>' . __('Stripe settings updated successfully ','simple-membership') . '</p></div>';
        }

        // Test-mode settings
        $enable_sandbox_testing = $settings->get_value( 'enable-sandbox-testing' );

        // Paypal settings
        $paypal_live_client_id = $settings->get_value( 'paypal-live-client-id' );
        $paypal_live_secret_key = $settings->get_value( 'paypal-live-secret-key' );
        $paypal_sandbox_client_id = $settings->get_value( 'paypal-sandbox-client-id' );
        $paypal_sandbox_secret_key = $settings->get_value( 'paypal-sandbox-secret-key' );
        
        // Stripe settings
        $stripe_prefill_member_email = $settings->get_value( 'stripe-prefill-member-email' );
        $stripe_test_public_key = $settings->get_value( 'stripe-test-public-key' );
        $stripe_test_secret_key = $settings->get_value( 'stripe-test-secret-key' );
        $stripe_live_public_key = $settings->get_value( 'stripe-live-public-key' );
        $stripe_live_secret_key = $settings->get_value( 'stripe-live-secret-key' );

        ?>

        <!-- Example of post box (This is just for example) -->
        <div class="postbox-container">
        <div class="postbox">
            <h2><?php _e("Sandbox or Test Mode Payment Settings", 'simple-membership'); ?></h2>
  
            <div class="inside">
                <p>
                    <?php _e( 'This section allows you to enable/disable sandbox or test mode for the payment buttons.', 'simple-membership' ); ?>
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
                    <input type="submit" name="swpm-enable-test-mode-submit" class="button-primary" value="<?php _e('Save Changes')?>" />
                    <?php wp_nonce_field('swpm-enable-test-mode-nonce');?>
                </form>
            </div>
        </div>

        <!-- Paypal PPCP Connection postbox -->
        <!--
        <div class="postbox">
            <h2 id="paypal-ppcp-connection-section"><?php _e("PayPal Account Connection", 'simple-membership'); ?></h2>
            <div class="inside">
                <?php 
                $this->paypal_ppcp_connection_settings();
                ?>
            </div>
        </div>
        -->

        <!-- Paypal Settings postbox -->
        <div class="postbox">
            <h2><?php _e("PayPal Settings", 'simple-membership'); ?></h3>
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
                                    <?php _e('Live Client ID', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-live-client-id" size="100" value="<?php echo $paypal_live_client_id; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Client ID for live mode.', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Secret Key', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-live-secret-key" size="100" value="<?php echo $paypal_live_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Secret Key for live mode.', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Sandbox Client ID', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-sandbox-client-id" size="100" value="<?php echo $paypal_sandbox_client_id; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Client ID for sandbox mode.', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Sandbox Secret Key', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="paypal-sandbox-secret-key" size="100" value="<?php echo $paypal_sandbox_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Enter your PayPal Secret Key for sandbox mode.', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="swpm-paypal-settings-submit" class="button-primary" value="<?php _e('Save Changes')?>" />
                    <?php wp_nonce_field('swpm-paypal-settings-nonce');?>
                </form>
            </div>
        </div>

        <!-- Paypal Webhooks postbox -->
        <div class="postbox">
            <h2 id="paypal-subscription-webhooks"><?php _e("PayPal Webhooks", 'simple-membership'); ?></h2>
            <div class="inside">
                <?php 
                $this->paypal_webhooks_settings_section(); 
                ?>
            </div>
        </div>

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
                                    <?php _e('Pre-fill Member Email Address', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" name="stripe-prefill-member-email" value="1" <?php echo $stripe_prefill_member_email; ?>/>
                                <p class="description">
                                    <?php _e('Pre-fills the email address of the logged-in member on the Stripe checkout form when possible', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Test Publishable Key', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-test-public-key" size="100" value="<?php echo $stripe_test_public_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Test publishable key', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Test Secret Key', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-test-secret-key" size="100" value="<?php echo $stripe_test_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Test secret key', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Publishable Key', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-live-public-key" size="100" value="<?php echo $stripe_live_public_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Live publishable key', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
                                    <?php _e('Live Secret Key', 'simple-membership');?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="stripe-live-secret-key" size="100" value="<?php echo $stripe_live_secret_key; ?>">
                                <p class="description">
                                    <?php _e('Stripe API Live secret key', 'simple-membership');?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="swpm-stripe-settings-submit" class="button-primary" value="<?php _e('Save Changes')?>" />
                    <?php wp_nonce_field('swpm-stripe-settings-nonce');?>
                </form>
            </div>
        </div>

        </div>

        <?php 
        do_action('swpm_payment_settings_menu_tab_end');
    }

	public function paypal_ppcp_connection_settings() {
        $settings = SwpmSettings::get_instance();

		$ppcp_onboarding_instance = SWPM_PayPal_PPCP_Onboarding::get_instance();

		//TODO - if all API credentials are missing, show a message.
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
					//TODO - Check if the live account is connected
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
						//TODO - Show disconnect option for live account.
						//$ppcp_onboarding_instance->output_live_ac_disconnect_link();
					} else {
						//Production account is NOT connected.
						echo '<div class="swpm-paypal-live-account-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
						_e( 'Live PayPal account is not connected.', 'simple-membership');
						echo '</div>';

						//TODO - Show the onboarding link
						//ppcp_onboarding_instance->output_live_onboarding_link_code();
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

	public function paypal_webhooks_settings_section() {
		$settings = SwpmSettings::get_instance();

		echo '<p>' . SwpmUtils::_( 'The PayPal payment buttons that uses the new API require webhooks. The plugin will auto-create the required webhooks when you create a PayPal payment button.' ) . '</p>';
		echo '<p>' . SwpmUtils::_( 'If you have issues with the webhooks, you can delete it and create again.' ) . '</p>';

		$all_api_creds_missing = false;
		if( empty( $settings->get_value('paypal-sandbox-client-id')) && empty( $settings->get_value('paypal-sandbox-secret-key')) && empty( $settings->get_value('paypal-live-client-id')) && empty( $settings->get_value('paypal-live-secret-key')) ){
			$all_api_creds_missing = true;
		}
		?>
		<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">Live Webhook Status</th>
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
							_e( 'No webhook found. Use the following Create Live Webhook link to create a new webhook automatically.', 'simple-membership' );
							echo '</span>';
							$create_live_webhook_url = admin_url( 'admin.php?page=simple_wp_membership_settings&tab=2&swpm_paypal_create_live_webhook=1' );
							$create_live_webhook_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_create_live_webhook' ), $create_live_webhook_url );
							echo '<p><a class="button swpm-paypal-create-live-webhook-btn" href="' . esc_url_raw( $create_live_webhook_url_nonced ) . '" target="_blank">'.SwpmUtils::_('Create Live Webhook').'</a></p>';
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">Test Webhook Status</th>
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
							_e( 'No webhook found. Use the following Create Sandbox Webhook link to create a new webhook automatically.', 'simple-membership' );
							echo '</span>';
							$create_sandbox_webhook_url = admin_url( 'admin.php?page=simple_wp_membership_settings&tab=2&swpm_paypal_create_sandbox_webhook=1' );
							$create_sandbox_webhook_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_create_sandbox_webhook' ), $create_sandbox_webhook_url );
							echo '<p><a class="button swpm-paypal-create-sandbox-webhook-btn" href="' . esc_url_raw( $create_sandbox_webhook_url_nonced ) . '" target="_blank">'.SwpmUtils::_('Create Sandbox Webhook').'</a></p>';
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
							$delete_webhook_url = admin_url( 'admin.php?page=simple_wp_membership_settings&tab=2&swpm_paypal_delete_webhook=1' );
							$delete_webhook_url_nonced = add_query_arg( '_wpnonce', wp_create_nonce( 'swpm_paypal_delete_webhook' ), $delete_webhook_url );
							echo '<p><a class="button swpm-paypal-delete-webhook-btn" href="'.esc_url_raw($delete_webhook_url_nonced).'" target="_blank">'.SwpmUtils::_('Delete Webhooks').'</a></p>';					
						}
					?>
				</td>
			</tr>
		</tbody>
		</table>
		<?php
	}

}
