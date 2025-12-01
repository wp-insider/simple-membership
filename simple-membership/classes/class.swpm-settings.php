<?php

class SwpmSettings {

	private static $_this;
	private $settings;
	public $current_tab;
	private $tabs;

	private function __construct() {
		$this->settings = (array) get_option( 'swpm-settings' );
	}

	public function init_config_hooks() {
		//This function is called from "admin_init"
		//It sets up the various tabs and the fields for the settings admin page.

		if ( is_admin() ) { // for frontend just load settings but dont try to render settings page.
			//Handle view log request
			$view_log_type = isset( $_GET['swpm_view_log'] ) ? sanitize_text_field( stripslashes ( $_GET['swpm_view_log'] ) ) : '';
			if ( ! empty( $view_log_type ) ) {
				switch ( $view_log_type ) {
					case 'd':
						check_admin_referer( 'swpm_view_debug_log' );
						break;
					case 'a':
						check_admin_referer( 'swpm_view_auth_log' );
						break;
					default:
						break;
				}

				SwpmLog::output_log( $view_log_type );
			}

			//Read the value of tab query arg.
			$tab               = isset( $_REQUEST['tab'] ) ? sanitize_text_field( $_REQUEST['tab'] ) : 1;
			$this->current_tab = empty( $tab ) ? 1 : $tab;

			//Setup the available settings tabs array.
			$this->tabs = array(
				1 => SwpmUtils::_( 'General Settings' ),
				2 => SwpmUtils::_( 'Payment Settings' ),
				3 => SwpmUtils::_( 'Email Settings' ),
				5 => SwpmUtils::_( 'Advanced Settings' ),
                                6 => SwpmUtils::_( 'Blacklisting & Whitelisting' ),
				7 => SwpmUtils::_( 'Addons Settings' ),
			);

			//Register the draw tab action hook. It will be triggered using do_action("swpm-draw-settings-nav-tabs")
			add_action( 'swpm-draw-settings-nav-tabs', array( &$this, 'draw_tabs' ) );

			//Register the various settings fields for the current tab.
			$method = 'tab_' . $this->current_tab;
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}
	}

	private function tab_1() {
		//Register settings sections and fileds for the general settings tab.

		register_setting( 'swpm-settings-tab-1', 'swpm-settings', array( &$this, 'sanitize_tab_1' ) );

		//This settings section has no heading. Useful for hooking at this section and doing arbitrary request parameter checks and show response accordingly (for this settings tab)
		add_settings_section( 'swpm-general-post-submission-check', '', array( &$this, 'swpm_general_post_submit_check_callback' ), 'simple_wp_membership_settings' );

        //The documentation settings section for this tab
		add_settings_section( 'swpm-documentation', SwpmUtils::_( 'Plugin Documentation' ), array( &$this, 'swpm_documentation_callback' ), 'simple_wp_membership_settings' );

		/* General Settings Section */
		add_settings_section( 'general-settings', SwpmUtils::_( 'General Settings' ), array( &$this, 'general_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'enable-free-membership',
			SwpmUtils::_( 'Enable Free Membership' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'enable-free-membership',
				'message' => SwpmUtils::_( 'Enable/disable registration for free membership level. When you enable this option, make sure to specify a free membership level ID in the field below.' ),
			)
		);
		add_settings_field(
			'free-membership-id',
			SwpmUtils::_( 'Free Membership Level ID' ),
			array( &$this, 'textfield_small_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'free-membership-id',
				'message' => SwpmUtils::_( 'Assign free membership level ID' ),
			)
		);
		add_settings_field(
			'enable-moretag',
			SwpmUtils::_( 'Enable More Tag Protection' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'enable-moretag',
				'message' => SwpmUtils::_( 'Enables or disables "more" tag protection in the posts and pages. Anything after the More tag is protected. Anything before the more tag is teaser content.' ),
			)
		);

		add_settings_field(
			'default-account-status',
			SwpmUtils::_( 'Default Account Status' ),
			array( &$this, 'selectbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'default-account-status',
				'options' => SwpmUtils::get_account_state_options(),
				'default' => 'active',
				'message' => SwpmUtils::_( 'Select the default account status for newly registered users. The default value should be active. If you want to manually approve the members then read <a href="https://simple-membership-plugin.com/manually-approve-members-membership-site/" target="_blank">this documentation</a> to learn more.' ),
			)
		);

		add_settings_field(
			'default-account-status-after-payment',
			SwpmUtils::_( 'Default Account Status After Payment' ),
			array( &$this, 'selectbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'default-account-status-after-payment',
				'options' => SwpmUtils::get_account_state_options(),
				'default' => 'active',
				'message' => SwpmUtils::_( 'The account status that will be applied to the profile after a payment. The default value should be active.' ),
			)
		);

		add_settings_field(
			'members-login-to-comment',
			SwpmUtils::_( 'Members Must be Logged in to Comment' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'members-login-to-comment',
				'message' => SwpmUtils::_( 'Enable this option if you only want the members of the site to be able to post a comment.' ),
			)
		);

		add_settings_field(
			'password-visibility-login-form',
			SwpmUtils::_( 'Enable Toggle Password Visibility in Login Form' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'password-visibility-login-form',
				'message' => SwpmUtils::_( 'You can use it to show a toggle password visibility option in the login form. It will add a Show Password checkbox.' ),
			)
		);

		add_settings_field(
			'password-reset-using-link',
			SwpmUtils::_( 'Enable Password Reset Using Link' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'general-settings',
			array(
				'item'    => 'password-reset-using-link',
				'message' => SwpmUtils::_( 'You can enable this option if you want to handle the password reset functionality using a reset link that is emailed to the member. Read <a href="https://simple-membership-plugin.com/password-reset-notification-email-customization/" target="_blank">this documentation</a> to learn more about the password reset function.' )
			)
		);

		/* Pages Settings Section*/
		add_settings_section( 'pages-settings', SwpmUtils::_( 'Pages Settings' ), array( &$this, 'pages_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'login-page-url',
			SwpmUtils::_( 'Login Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'pages-settings',
			array(
				'item'    => 'login-page-url',
				'message' => '',
			)
		);
		add_settings_field(
			'registration-page-url',
			SwpmUtils::_( 'Registration Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'pages-settings',
			array(
				'item'    => 'registration-page-url',
				'message' => '',
			)
		);
		add_settings_field(
			'join-us-page-url',
			SwpmUtils::_( 'Join Us Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'pages-settings',
			array(
				'item'    => 'join-us-page-url',
				'message' => '',
			)
		);
		add_settings_field(
			'profile-page-url',
			SwpmUtils::_( 'Edit Profile Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'pages-settings',
			array(
				'item'    => 'profile-page-url',
				'message' => '',
			)
		);
		add_settings_field(
			'reset-page-url',
			SwpmUtils::_( 'Password Reset Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'pages-settings',
			array(
				'item'    => 'reset-page-url',
				'message' => '',
			)
		);

		/* Optional Pages Settings Section */
		add_settings_section( 'optional-pages-settings', SwpmUtils::_( 'Optional Pages Settings' ), array( &$this, 'optional_pages_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'thank-you-page-url',
			SwpmUtils::_( 'Thank You Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'optional-pages-settings',
			array(
				'item'    => 'thank-you-page-url',
				'message' => SwpmUtils::_( 'It is useful to use a thank you page in your payment button configuration. Read <a href="https://simple-membership-plugin.com/paid-registration-from-the-thank-you-page/" target="_blank">this documentation</a> to learn more.' ),
			)
		);

		/* Debug Settings Section */
		add_settings_section( 'debug-settings', SwpmUtils::_( 'Test & Debug Settings' ), array( &$this, 'testndebug_settings_callback' ), 'simple_wp_membership_settings' );

		$debug_log_url = add_query_arg(
			array(
				'page'          => 'simple_wp_membership_settings',
				'swpm_view_log' => 'd',
				'_wpnonce'         => wp_create_nonce( 'swpm_view_debug_log' ),
			),
			admin_url( 'admin.php' )
		);

		$auth_log_url = add_query_arg(
			array(
				'page'          => 'simple_wp_membership_settings',
				'swpm_view_log' => 'a',
				'_wpnonce'         => wp_create_nonce( 'swpm_view_auth_log' ),
			),
			admin_url( 'admin.php' )
		);

		$debug_field_help_text  = __( 'Check this option to enable debug logging.', 'simple-membership' );
		$debug_field_help_text .= __( ' This can be useful when troubleshooting an issue. Turn it off and reset the log files after the troubleshooting is complete.', 'simple-membership' );
		$debug_field_help_text .= '<br />';
		$debug_field_help_text .= '<br />- ' . __( 'View general debug log file by ', 'simple-membership' ) . '<a href="' . $debug_log_url . '" target="_blank">' . __( 'clicking here', 'simple-membership' ) . '</a>.';
		$debug_field_help_text .= '<br />- ' . __( 'View login related debug log file by ', 'simple-membership' ) . '<a href="' . $auth_log_url . '" target="_blank">' . __( 'clicking here', 'simple-membership' ) . '</a>.';
		$debug_field_help_text .= '<br />- ' . __( 'Reset debug log files by ', 'simple-membership' ) . '<a href="javascript:void(0)" style="color: #CC0000;" id="swpm_reset_log_anchor">' . __( 'clicking here', 'simple-membership') . '</a>.';
		ob_start();
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function (){
                const swpm_reset_log_anchor = document.getElementById('swpm_reset_log_anchor');
                swpm_reset_log_anchor.addEventListener('click', function (e){
                    e.preventDefault();
                    const ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' )?>';
                    const payload = new URLSearchParams();
                    payload.append('action', 'swpm_reset_log_action');
                    payload.append('nonce',  '<?php echo esc_js( wp_create_nonce( 'swpm_reset_log_action' ) ) ?>');
                    fetch(
                        ajaxUrl,
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: payload.toString()
                        }
                    ).then((response) => {
                        return response.json();
                    }).then((res) => {
                        alert(res.data.message);
                    }).catch(err => {
                        alert('The ajax request to reset the log files has failed unexpectedly. Please try again later.');
                    })
                });
            })


        </script>
        <?php
		$debug_field_help_text .= ob_get_clean();

		add_settings_field(
			'enable-debug',
			SwpmUtils::_( 'Enable Debug' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'debug-settings',
			array(
				'item'    => 'enable-debug',
				'message' => $debug_field_help_text,
			)
		);

	}

	private function tab_2() {
		//Register settings sections and fileds for the payment settings tab.
		//register_setting( 'swpm-settings-tab-2', 'swpm-settings', array( $this, 'sanitize_tab_2' ) );

		//This settings section has no heading. Useful for hooking at this section and doing arbitrary request parameter checks and show response accordingly (on this settings tab)
		//add_settings_section( 'swpm-settings-tab-2-before-other-fields', '', array( &$this, 'swpm_settings_tab_2_before_fields_callback' ), 'simple_wp_membership_settings' );

		//Sandbox or Testmode payment settings section.
		//add_settings_section( 'testmode-payment-settings', SwpmUtils::_( 'Sandbox or Test Mode Payment Settings' ), array( &$this, 'testmode_payment_settings_callback' ), 'simple_wp_membership_settings' );

		// add_settings_field(
		// 	'enable-sandbox-testing',
		// 	SwpmUtils::_( 'Enable Sandbox or Test Mode' ),
		// 	array( &$this, 'checkbox_callback' ),
		// 	'simple_wp_membership_settings',
		// 	'testmode-payment-settings',
		// 	array(
		// 		'item'    => 'enable-sandbox-testing',
		// 		'message' => SwpmUtils::_( 'Enable this option if you want to do sandbox payment testing.' ),
		// 	)
		// );
	}

	private function tab_3() {
		//Register settings sections and fileds for the email settings tab.

		register_setting( 'swpm-settings-tab-3', 'swpm-settings', array( &$this, 'sanitize_tab_3' ) );

		add_settings_section( 'email-settings-overview', SwpmUtils::_( 'Email Settings Overview' ), array( &$this, 'email_settings_overview_callback' ), 'simple_wp_membership_settings' );
		add_settings_section( 'email-misc-settings', SwpmUtils::_( 'Email Misc. Settings' ), array( &$this, 'email_misc_settings_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'email-misc-from',
			SwpmUtils::_( 'From Email Address' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'email-misc-settings',
			array(
				'item'    => 'email-from',
				'message' => __("This value will be used as the sender's address for the emails. Example value: Your Name &lt;sales@your-domain.com&gt;", "simple-membership"),
			)
		);

		add_settings_field(
			'email-enable-html',
			SwpmUtils::_( 'Allow HTML in Emails' ),
			array( $this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'email-misc-settings',
			array(
				'item'    => 'email-enable-html',
				'message' => __("Enables HTML support in emails. We recommend using plain text (non HTML) email as it has better email delivery rate.", "simple-membership"),
			)
		);

		//Prompt to complete registration email settings
		add_settings_section( 'reg-prompt-email-settings', SwpmUtils::_( 'Email Settings (Prompt to Complete Registration )' ), array( &$this, 'reg_prompt_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'reg-prompt-complete-mail-subject',
			SwpmUtils::_( 'Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'reg-prompt-email-settings',
			array(
				'item'    => 'reg-prompt-complete-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'reg-prompt-complete-mail-body',
			SwpmUtils::_( 'Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'reg-prompt-email-settings',
			array(
				'item'    => 'reg-prompt-complete-mail-body',
				'message' => '',
			)
		);

		//Registration complete email settings
		$msg_for_admin_notify_email_field  = SwpmUtils::_( 'Enter the email address where you want the admin notification email to be sent to.' );
		$msg_for_admin_notify_email_field .= SwpmUtils::_( ' You can put multiple email addresses separated by comma (,) in the above field to send the notification to multiple email addresses.' );

		$msg_for_admin_notify_email_subj = SwpmUtils::_( 'Enter the subject for the admin notification email.' );
		$admin_notify_email_body_msg     = SwpmUtils::_( 'This email will be sent to the admin when a new user completes the membership registration. Only works if you have enabled the "Send Notification to Admin" option above.' );

		add_settings_section( 'reg-email-settings', SwpmUtils::_( 'Email Settings (Registration Complete)' ), array( &$this, 'reg_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'reg-complete-mail-subject',
			SwpmUtils::_( 'Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'reg-complete-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'reg-complete-mail-body',
			SwpmUtils::_( 'Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'reg-complete-mail-body',
				'message' => '',
			)
		);
		add_settings_field(
			'enable-admin-notification-after-reg',
			SwpmUtils::_( 'Send Notification to Admin' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'enable-admin-notification-after-reg',
				'message' => SwpmUtils::_( 'Enable this option if you want the admin to receive a notification when a member registers.' ),
			)
		);
		add_settings_field(
			'admin-notification-email',
			SwpmUtils::_( 'Admin Email Address' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'admin-notification-email',
				'message' => $msg_for_admin_notify_email_field,
			)
		);
		add_settings_field(
			'reg-complete-mail-subject-admin',
			SwpmUtils::_( 'Admin Notification Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'reg-complete-mail-subject-admin',
				'message' => $msg_for_admin_notify_email_subj,
			)
		);
		add_settings_field(
			'reg-complete-mail-body-admin',
			SwpmUtils::_( 'Admin Notification Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'reg-complete-mail-body-admin',
				'message' => $admin_notify_email_body_msg,
			)
		);

		add_settings_field(
			'enable-notification-after-manual-user-add',
			SwpmUtils::_( 'Send Email to Member When Added via Admin Dashboard' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'reg-email-settings',
			array(
				'item'    => 'enable-notification-after-manual-user-add',
				'message' => '',
			)
		);

		//Password reset email settings
		add_settings_section( 'reset-password-settings', SwpmUtils::_( 'Email Settings (Password Reset)' ), array( &$this, 'reset_password_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'reset-mail-subject',
			SwpmUtils::_( 'Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'reset-password-settings',
			array(
				'item'    => 'reset-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'reset-mail-body',
			SwpmUtils::_( 'Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'reset-password-settings',
			array(
				'item'    => 'reset-mail-body',
				'message' => '',
			)
		);

		//Account upgrade email settings
		add_settings_section( 'upgrade-email-settings', SwpmUtils::_( ' Email Settings (Account Upgrade Notification)' ), array( &$this, 'upgrade_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'upgrade-complete-mail-subject',
			SwpmUtils::_( 'Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'upgrade-email-settings',
			array(
				'item'    => 'upgrade-complete-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'upgrade-complete-mail-body',
			SwpmUtils::_( 'Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'upgrade-email-settings',
			array(
				'item'    => 'upgrade-complete-mail-body',
				'message' => '',
			)
		);
		add_settings_field(
			'disable-email-after-upgrade',
			SwpmUtils::_( 'Disable Email Notification After Upgrade' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'upgrade-email-settings',
			array(
				'item'    => 'disable-email-after-upgrade',
				'message' => SwpmUtils::_( 'You can use this option to disable the email notification that gets sent to the members when they make a payment for account upgrade.' ),
			)
		);
		add_settings_section( 'renewal-email-settings', __( 'Email Settings (Account Renewal Notification)', 'simple-membership' ), array( &$this, 'renewal_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'renew-complete-mail-subject',
			__( 'Email Subject', 'simple-membership' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'renewal-email-settings',
			array(
				'item'    => 'renew-complete-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'renew-complete-mail-body',
			__( 'Email Body', 'simple-membership' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'renewal-email-settings',
			array(
				'item'    => 'renew-complete-mail-body',
				'message' => '',
			)
		);
		add_settings_field(
			'disable-email-after-renew',
			__( 'Disable Email Notification After Renewal', 'simple-membership' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'renewal-email-settings',
			array(
				'item'    => 'disable-email-after-renew',
				'message' => __( 'You can use this option to disable the email notification that gets sent to the members when they make a payment for account renewal.', 'simple-membership' ),
			)
		);

		//Bulk account activate and notify email settings.
		add_settings_section( 'bulk-activate-email-settings', SwpmUtils::_( ' Email Settings (Bulk Account Activate Notification)' ), array( &$this, 'bulk_activate_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'bulk-activate-notify-mail-subject',
			SwpmUtils::_( 'Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'bulk-activate-email-settings',
			array(
				'item'    => 'bulk-activate-notify-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'bulk-activate-notify-mail-body',
			SwpmUtils::_( 'Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'bulk-activate-email-settings',
			array(
				'item'    => 'bulk-activate-notify-mail-body',
				'message' => '',
			)
		);

		//Email activation email settings.
		add_settings_section( 'email-activation-email-settings', SwpmUtils::_( ' Email Settings (Email Activation)' ), array( &$this, 'email_activation_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'email-activation-mail-subject',
			SwpmUtils::_( 'Email Subject' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'email-activation-email-settings',
			array(
				'item'    => 'email-activation-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'email-activation-mail-body',
			SwpmUtils::_( 'Email Body' ),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'email-activation-email-settings',
			array(
				'item'    => 'email-activation-mail-body',
				'message' => '',
			)
		);

		//Subscription Cancel email settings.
		add_settings_section( 'subscription-cancel-email-settings', __( ' Email Settings (Subscription Payment Canceled or Expired)', 'simple-membership'), array( &$this, 'subscription_cancel_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'subscription-cancel-member-mail-enable',
			__( 'Send Notification to Member', 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'subscription-cancel-email-settings',
			array(
				'item'    => 'subscription-cancel-member-mail-enable',
				'message' => __( 'Enable this option to send an email notification to members when their subscription payment is canceled or expires.', 'simple-membership'),
			)
		);
        add_settings_field(
			'subscription-cancel-member-mail-subject',
			__( 'Email Subject', 'simple-membership'),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'subscription-cancel-email-settings',
			array(
				'item'    => 'subscription-cancel-member-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'subscription-cancel-member-mail-body',
			__( 'Email Body', 'simple-membership'),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'subscription-cancel-email-settings',
			array(
				'item'    => 'subscription-cancel-member-mail-body',
				'message' => '',
			)
		);
		add_settings_field(
			'subscription-cancel-admin-mail-enable',
			__( 'Send Notification to Admin', 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'subscription-cancel-email-settings',
			array(
				'item'    => 'subscription-cancel-admin-mail-enable',
				'message' => __( 'Enable this option to send a notification to the admin.', 'simple-membership'),
			)
		);
		add_settings_field(
			'subscription-cancel-admin-mail-address',
			__( 'Admin Email Address', 'simple-membership' ),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'subscription-cancel-email-settings',
			array(
				'item'    => 'subscription-cancel-admin-mail-address',
				'message' => __( 'Enter the email address where you want the admin notification email to be sent to.', 'simple-membership'),
			)
		);

		//Manual account approval email settings.
		add_settings_section( 'manual-account-approve-email-settings', __( 'Email Settings (Manual Account Approval)', 'simple-membership'), array( &$this, 'manual_account_approve_email_settings_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'manual-account-approve-member-mail-enable',
			__( 'Send Notification After Approval', 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'manual-account-approve-email-settings',
			array(
				'item'    => 'manual-account-approve-member-mail-enable',
				'message' => __( 'Enable this to send an email notification to members when their account is manually approved.', 'simple-membership'),
			)
		);
		add_settings_field(
			'manual-account-approve-member-mail-subject',
			__( 'Email Subject', 'simple-membership'),
			array( &$this, 'textfield_callback' ),
			'simple_wp_membership_settings',
			'manual-account-approve-email-settings',
			array(
				'item'    => 'manual-account-approve-member-mail-subject',
				'message' => '',
			)
		);
		add_settings_field(
			'manual-account-approve-member-mail-body',
			__( 'Email Body', 'simple-membership'),
			array( &$this, 'wp_editor_callback' ),
			'simple_wp_membership_settings',
			'manual-account-approve-email-settings',
			array(
				'item'    => 'manual-account-approve-member-mail-body',
				'message' => '',
			)
		);
	}

	private function tab_4() {
		//Register settings sections and fileds for the tools tab.
	}

	private function tab_5() {
		//Register settings sections and fileds for the advanced settings tab.

		register_setting( 'swpm-settings-tab-5', 'swpm-settings', array( &$this, 'sanitize_tab_5' ) );

		add_settings_section( 'advanced-settings', SwpmUtils::_( 'Advanced Settings' ), array( &$this, 'advanced_settings_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'enable-expired-account-login',
			SwpmUtils::_( 'Enable Expired Account Login' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'enable-expired-account-login',
				'message' => SwpmUtils::_( "When enabled, expired members will be able to log into the system but won't be able to view any protected content. This allows them to easily renew their account by making another payment." ),
			)
		);

		add_settings_field(
			'renewal-page-url',
			SwpmUtils::_( 'Membership Renewal URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'renewal-page-url',
				'message' => SwpmUtils::_( 'You can create a renewal page for your site. Read <a href="https://simple-membership-plugin.com/creating-membership-renewal-button/" target="_blank">this documentation</a> to learn how to create a renewal page.' ),
			)
		);

		add_settings_field(
			'after-rego-redirect-page-url',
			SwpmUtils::_( 'After Registration Redirect URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'after-rego-redirect-page-url',
				'message' => SwpmUtils::_( 'You can enter an URL here to redirect the members to this page after they submit the registration form. Read <a href="https://simple-membership-plugin.com/configure-after-registration-redirect-for-members/" target="_blank">this documentation</a> to learn how to setup after registration redirect.' ),
			)
		);

		add_settings_field(
			'auto-login-after-rego',
			SwpmUtils::_( 'Enable Auto Login After Registration' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'auto-login-after-rego',
				'message' => SwpmUtils::_( 'Use this option if you want the members to be automatically logged into your site right after they complete the registration. This option will override any after registration redirection and instead it will trigger the after login redirection. Read <a href="https://simple-membership-plugin.com/configure-auto-login-after-registration-members/" target="_blank">this documentation</a> to learn more.' ),
			)
		);

		add_settings_field(
			'hide-reg-form-membership-level-field',
			__( 'Hide Membership Level Field on Registration Form', 'simple-membership' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'hide-reg-form-membership-level-field',
				'message' => __( "Enable this option to hide the membership level field on the registration form. While the field will remain part of the form, it will be hidden from view. This is useful for sites where you prefer not to display the membership level to users.", "simple-membership" ),
			)
		);

		add_settings_field(
			'hide-join-us-link',
			__( 'Hide the Join Us Link' , 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'hide-join-us-link',
				'message' => __( "Select this option to hide the 'Join Us' link if you prefer visitors not to see the registration option on your site. Refer to <a href='https://simple-membership-plugin.com/hiding-join-option-from-visitors/' target='_blank'>this documentation</a> to learn more." , "simple-membership"),
			)
		);

		add_settings_field(
			'hide-rego-form-to-logged-users',
			SwpmUtils::_( 'Hide Registration Form to Logged Users' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'hide-rego-form-to-logged-users',
				'message' => SwpmUtils::_( 'Use this option if you want to hide the registration form to the logged-in members. If logged-in members visit the registration page, they will see a message instead of the registration form.' ),
			)
		);

		add_settings_field(
			'after-logout-redirection-url',
			SwpmUtils::_( 'After Logout Redirect URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'after-logout-redirection-url',
				'message' => SwpmUtils::_( 'You can enter an URL here to redirect the members to this page after they click the logout link to logout from your site.' ),
			)
		);

		add_settings_field(
			'logout-member-on-browser-close',
			SwpmUtils::_( 'Logout Member on Browser Close' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'logout-member-on-browser-close',
				'message' => SwpmUtils::_( 'Enable this option if you want the member to be logged out of the account when he closes the browser.' ),
			)
		);

		add_settings_field(
			'allow-account-deletion',
			SwpmUtils::_( 'Allow Account Deletion' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'allow-account-deletion',
				'message' => SwpmUtils::_( 'Allow users to delete their accounts.' ),
			)
		);

		add_settings_field(
			'force-strong-passwords',
			SwpmUtils::_( 'Force Strong Password for Members' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'force-strong-passwords',
				'message' => SwpmUtils::_( 'Enable this if you want the users to be forced to use a strong password for their accounts.' ),
			)
		);

		add_settings_field(
			'delete-pending-account',
			SwpmUtils::_( 'Auto Delete Pending Account' ),
			array( &$this, 'selectbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'delete-pending-account',
				'options' => array(
					0 => __('Do not delete', 'simple-membership'),
					1 => __('Older than 1 month', 'simple-membership'),
					2 => __('Older than 2 months', 'simple-membership'),
				),
				'default' => '0',
				'message' => SwpmUtils::_( 'Select how long you want to keep "pending" account.' ),
			)
		);

		add_settings_field(
			'admin-dashboard-access-permission',
			SwpmUtils::_( 'Admin Dashboard Access Permission' ),
			array( &$this, 'selectbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'admin-dashboard-access-permission',
				'options' => array(
					'manage_options'       => translate_user_role( 'Administrator' ),
					'edit_pages'           => translate_user_role( 'Editor' ),
					'edit_published_posts' => translate_user_role( 'Author' ),
					'edit_posts'           => translate_user_role( 'Contributor' ),
				),
				'default' => 'manage_options',
				'message' => SwpmUtils::_( 'SWPM admin dashboard is accessible to admin users only (just like any other plugin). You can allow users with other WP user roles to access the SWPM admin dashboard by selecting a value here. Note that this option cannot work if you enabled the "Disable Access to WP Dashboard" option in Advanced Settings.' ),
			)
		);

		add_settings_field(
			'force-wp-user-sync',
			SwpmUtils::_( 'Force WP User Synchronization' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'force-wp-user-sync',
				'message' => SwpmUtils::_( 'Enable this option if you want to force the member login to be synchronized with WP user account. This can be useful if you are using another plugin that uses WP user records. For example: bbPress plugin.' ),
			)
		);

		add_settings_field(
			'payment-notification-forward-url',
			SwpmUtils::_( 'Payment Notification Forward URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'payment-notification-forward-url',
				'message' => SwpmUtils::_( 'You can enter an URL here to forward the payment notification after the membership payment has been processed by this plugin. Useful if you want to forward the payment notification to an external script for further processing.' ),
			)
		);

		add_settings_field(
			'use-new-form-ui',
			__( 'Activate New Form and Validation Interface', 'simple-membership' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'advanced-settings',
			array(
				'item'    => 'use-new-form-ui',
				'message' => __( 'Enable the improved user interface for registration and profile editing, featuring enhanced validation that adapts seamlessly across various devices and screen sizes.' ),
			)
		);

		// WP Toolbar and Admin Dashboard Related settings section
		add_settings_section( 'wp-toolbar-and-admin-dashboard-related', __( 'WP Toolbar and Admin Dashboard Related' , 'simple-membership'), array( &$this, 'advanced_settings_wp_toolbar_related_section_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'hide-adminbar',
			__( 'Hide Adminbar' , 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'wp-toolbar-and-admin-dashboard-related',
			array(
				'item'    => 'hide-adminbar',
				'message' => __( 'WordPress displays an admin toolbar to logged-in users. Enable this option if you want to hide the admin toolbar on the frontend of your site.' , 'simple-membership'),
			)
		);

		add_settings_field(
			'show-adminbar-admin-only',
			__( 'Show Adminbar to Admin' , 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'wp-toolbar-and-admin-dashboard-related',
			array(
				'item'    => 'show-adminbar-admin-only',
				'message' => __( 'Use this option if you want to show the admin toolbar to admin users only. The admin toolbar will be hidden for all other users.' , 'simple-membership'),
			)
		);

		add_settings_field(
			'disable-access-to-wp-dashboard',
			__( 'Disable Access to WP Dashboard' , 'simple-membership'),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'wp-toolbar-and-admin-dashboard-related',
			array(
				'item'    => 'disable-access-to-wp-dashboard',
				'message' => __( 'WordPress allows a standard wp user to be able to go to the wp-admin URL and access his profile from the wp dashboard. Enabling this option will restrict non-admin users from accessing the WordPress dashboard.' , 'simple-membership'),
			)
		);

		//Auto create SWPM user related settings section
		add_settings_section( 'auto-create-swpm-user-settings', SwpmUtils::_( 'Create Member Accounts for New WP Users' ), array( &$this, 'advanced_settings_auto_create_swpm_uses_settings_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'enable-auto-create-swpm-members',
			SwpmUtils::_( 'Enable Auto Create Member Accounts' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'auto-create-swpm-user-settings',
			array(
				'item'    => 'enable-auto-create-swpm-members',
				'message' => SwpmUtils::_( 'Enable this option to automatically create member accounts for any new WP user that is created by another plugin.' ),
			)
		);

		$levels_array = SwpmMembershipLevelUtils::get_all_membership_levels_in_array();
		add_settings_field(
			'auto-create-default-membership-level',
			SwpmUtils::_( 'Default Membership Level' ),
			array( &$this, 'selectbox_callback' ),
			'simple_wp_membership_settings',
			'auto-create-swpm-user-settings',
			array(
				'item'    => 'auto-create-default-membership-level',
				'options' => $levels_array,
				'default' => '',
				'message' => SwpmUtils::_( 'When automatically creating a member account using this feature, the membership level of the user will be set to the one you specify here.' ),
			)
		);

		$status_array = SwpmUtils::get_account_state_options();
		add_settings_field(
			'auto-create-default-account-status',
			SwpmUtils::_( 'Default Account Status' ),
			array( &$this, 'selectbox_callback' ),
			'simple_wp_membership_settings',
			'auto-create-swpm-user-settings',
			array(
				'item'    => 'auto-create-default-account-status',
				'options' => $status_array,
				'default' => '',
				'message' => SwpmUtils::_( 'When automatically creating a member account using this feature, the membership account status of the user will be set to the one you specify here.' ),
			)
		);

		//Terms and conditions section
		add_settings_section( 'terms-and-conditions', SwpmUtils::_( 'Terms and Conditions' ), array( &$this, 'advanced_settings_terms_and_conditions_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'enable-terms-and-conditions',
			SwpmUtils::_( 'Enable Terms and Conditions' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'terms-and-conditions',
			array(
				'item'    => 'enable-terms-and-conditions',
				'message' => SwpmUtils::_( 'Users must accept the terms before they can complete the registration.' ),
			)
		);
		add_settings_field(
			'terms-and-conditions-page-url',
			SwpmUtils::_( 'Terms and Conditions Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'terms-and-conditions',
			array(
				'item'    => 'terms-and-conditions-page-url',
				'message' => SwpmUtils::_( 'Enter the URL of your terms and conditions page. You can create a WordPress page and specify your terms in there then specify the URL of that page in the above field.' ),
			)
		);
		add_settings_field(
			'enable-privacy-policy',
			SwpmUtils::_( 'Enable Privacy Policy' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'terms-and-conditions',
			array(
				'item'    => 'enable-privacy-policy',
				'message' => SwpmUtils::_( 'Users must accept it before they can complete the registration.' ),
			)
		);
		add_settings_field(
			'privacy-policy-page-url',
			SwpmUtils::_( 'Privacy Policy Page URL' ),
			array( &$this, 'textfield_long_callback' ),
			'simple_wp_membership_settings',
			'terms-and-conditions',
			array(
				'item'    => 'privacy-policy-page-url',
				'message' => SwpmUtils::_( 'Enter the URL of your privacy policy page.' ),
			)
		);

		//Terms and conditions section
		add_settings_section( 'limit-active-logins', __( 'Active Login Limit', 'simple-membership' ), array( &$this, 'advanced_settings_limit_active_logins_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'enable-login-limiter',
			__( 'Enable Active Login Limit', 'simple-membership' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'limit-active-logins',
			array(
				'item'    => 'enable-login-limiter',
				'message' => __( 'Check this box to enable the login limit feature.', 'simple-membership'),
			)
		);
		add_settings_field(
			'maximum-active-logins',
			__( 'Maximum Active Logins', 'simple-membership' ),
			array( &$this, 'maximum_active_login_callback' ),
			'simple_wp_membership_settings',
			'limit-active-logins',
            array(
				'item'    => 'maximum-active-logins',
			)
		);
        //		add_settings_field(
        //			'login-logic',
        //            __( 'Login Logic', 'simple-membership' ),
        //			array( &$this, 'login_logic_callback' ),
        //			'simple_wp_membership_settings',
        //			'limit-active-logins',
        //            array(
        //				'item'    => 'login-logic',
        //			)
        //		);

        add_settings_section( 'failed-login-attempt-limit', __( 'Failed Login Attempt Limit', 'simple-membership' ), array( &$this, 'advanced_settings_failed_login_attempt_limit_callback' ), 'simple_wp_membership_settings' );
		add_settings_field(
			'enable-failed-login-attempt-limiter',
			__( 'Enable Failed Login Attempt Limit', 'simple-membership' ),
			array( $this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'failed-login-attempt-limit',
			array(
				'item' => 'enable-failed-login-attempt-limiter',
				'message' => __( 'Check this box to enable the failed login attempt limit feature.', 'simple-membership'),
			)
		);
		add_settings_field(
			'max-failed-login-attempts',
			__( 'Maximum Failed Login Attempts', 'simple-membership' ),
			array( $this, 'max_failed_login_attempts_callback' ),
			'simple_wp_membership_settings',
			'failed-login-attempt-limit',
			array(
				'item' => 'max-failed-login-attempts',
			)
		);
		add_settings_field(
			'failed-login-attempt-lockdown-time',
			__( 'Lockout Duration (in minutes)', 'simple-membership' ),
			array( $this, 'failed_login_attempts_lockdown_time_callback' ),
			'simple_wp_membership_settings',
			'failed-login-attempt-limit',
			array(
				'item' => 'failed-login-attempt-lockdown-time',
			)
		);
	}


	private function tab_6() {
		//Register settings sections and fields for the blacklisting and whitelisting settings tab.

		register_setting( 'swpm-settings-tab-6', 'swpm-settings', array( &$this, 'sanitize_tab_6' ) );

                /* Overview section at the top */
                add_settings_section( 'blacklist-whitelist-settings-overview', SwpmUtils::_( 'Configure Blacklisting & Whitelisting' ), array( &$this, 'blacklist_whitelist_overview_callback' ), 'simple_wp_membership_settings' );

                /* Whitelisting settings section */
		add_settings_section( 'whitelist-settings', SwpmUtils::_( 'Whitelisting' ), array( &$this, 'whitelist_settings_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'enable-whitelisting',
			SwpmUtils::_( 'Enable Whitelisting Feature' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'whitelist-settings',
			array(
				'item'    => 'enable-whitelisting',
				'message' => SwpmUtils::_( "When enabled, whitelisting feature will be activated." ),
			)
		);

		add_settings_field(
			'whitelist-email-address',
			SwpmUtils::_( 'Email Address Whitelisting' ),
			array( &$this, 'textarea_callback' ),
			'simple_wp_membership_settings',
			'whitelist-settings',
			array(
				'item'    => 'whitelist-email-address',
				'message' => SwpmUtils::_( 'Enter a list (comma separated) of email addresses to whitelist.' ),
			)
		);

		add_settings_field(
			'whitelist-email-address-pattern',
			SwpmUtils::_( 'Email Address Pattern Whitelisting' ),
			array( &$this, 'textarea_callback' ),
			'simple_wp_membership_settings',
			'whitelist-settings',
			array(
				'item'    => 'whitelist-email-address-pattern',
				'message' => SwpmUtils::_( 'Enter a list (comma separated) of email addresses pattern to whitelist. Example value: @gooddomain.com, @gmail.com, @yahoo.com' ),
			)
		);

		add_settings_field(
			'whitelist-block-message',
			SwpmUtils::_( 'Whitelist Message Customization' ),
			array( &$this, 'textarea_callback' ),
			'simple_wp_membership_settings',
			'whitelist-settings',
			array(
				'item'    => 'whitelist-block-message',
				'message' => SwpmUtils::_( 'Enter the message you want to show to the user when the whitelisted condition is met. Leave it empty to use the default message.' ),
			)
		);

		/** BLACKLIST SETTINGS **/
		add_settings_section( 'blacklist-settings', SwpmUtils::_( 'Blacklisting' ), array( &$this, 'blacklist_settings_callback' ), 'simple_wp_membership_settings' );

		add_settings_field(
			'enable-blacklisting',
			SwpmUtils::_( 'Enable Blacklisting Feature' ),
			array( &$this, 'checkbox_callback' ),
			'simple_wp_membership_settings',
			'blacklist-settings',
			array(
				'item'    => 'enable-blacklisting',
				'message' => SwpmUtils::_( "When enabled, blacklisting feature will be activated." ),
			)
		);

		add_settings_field(
			'blacklist-email-address',
			SwpmUtils::_( 'Email Address Blacklisting' ),
			array( &$this, 'textarea_callback' ),
			'simple_wp_membership_settings',
			'blacklist-settings',
			array(
				'item'    => 'blacklist-email-address',
				'message' => SwpmUtils::_( 'Enter a list (comma separated) of email addresses to blacklist.' ),
			)
		);

		add_settings_field(
			'blacklist-email-address-pattern',
			SwpmUtils::_( 'Email Address Pattern Blacklisting' ),
			array( &$this, 'textarea_callback' ),
			'simple_wp_membership_settings',
			'blacklist-settings',
			array(
				'item'    => 'blacklist-email-address-pattern',
				'message' => SwpmUtils::_( 'Enter a list (comma separated) of email addresses pattern to blacklist. Example value: @baddomain.com, @crazydomain.com' ),
			)
		);

		add_settings_field(
			'blacklist-block-message',
			SwpmUtils::_( 'Blacklist Message Customization' ),
			array( &$this, 'textarea_callback' ),
			'simple_wp_membership_settings',
			'blacklist-settings',
			array(
				'item'    => 'blacklist-block-message',
				'message' => SwpmUtils::_( 'Enter the message you want to show to the user when the blacklisted condition is met. Leave it empty to use the default message.' ),
			)
		);
	}

	private function tab_7() {
		//Register settings sections and fields for the addon settings tab.

	}

	public static function get_instance() {
		self::$_this = empty( self::$_this ) ? new SwpmSettings() : self::$_this;
		return self::$_this;
	}

	public function selectbox_callback( $args ) {
		$item     = $args['item'];
		$options  = $args['options'];
		$default  = $args['default'];
		$msg      = isset( $args['message'] ) ? $args['message'] : '';
		$selected = esc_attr( $this->get_value( $item, $default ) );
		echo "<select name='swpm-settings[" . $item . "]' >";
		foreach ( $options as $key => $value ) {
			$is_selected = ( $key == $selected ) ? 'selected="selected"' : '';
			echo '<option ' . $is_selected . ' value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . $msg . '</p>';
	}

	public function checkbox_callback( $args ) {
		$item = $args['item'];
		$msg  = isset( $args['message'] ) ? $args['message'] : '';
		$is   = esc_attr( $this->get_value( $item ) );
		echo "<input type='checkbox' $is name='swpm-settings[" . $item . "]' value=\"checked='checked'\" />";
		echo '<p class="description">' . $msg . '</p>';
	}

	public function textarea_callback( $args ) {
		$item = $args['item'];
		$msg  = isset( $args['message'] ) ? $args['message'] : '';
		$text = esc_attr( $this->get_value( $item ) );
		echo "<textarea name='swpm-settings[" . $item . "]'  rows='6' cols='60' >" . $text . '</textarea>';
		echo '<p class="description">' . $msg . '</p>';
	}

	public function textfield_small_callback( $args ) {
		$item = $args['item'];
		$msg  = isset( $args['message'] ) ? $args['message'] : '';
		$text = esc_attr( $this->get_value( $item ) );
		echo "<input type='text' name='swpm-settings[" . $item . "]'  size='5' value='" . $text . "' />";
		echo '<p class="description">' . $msg . '</p>';
	}

	public function textfield_callback( $args ) {
		$item = $args['item'];
		$msg  = isset( $args['message'] ) ? $args['message'] : '';
		$text = $this->get_value( $item );
		echo sprintf( '<input type="text" name="swpm-settings[%s]" size="50" value="%s" />', esc_attr( $item ), esc_attr( $text ) );
		echo sprintf( '<p class="description">%s</p>', $msg );
	}

	public function textfield_long_callback( $args ) {
		$item = $args['item'];
		$msg  = isset( $args['message'] ) ? $args['message'] : '';
		$text = esc_attr( $this->get_value( $item ) );
		echo "<input type='text' name='swpm-settings[" . $item . "]'  size='100' value='" . $text . "' />";
		echo '<p class="description">' . $msg . '</p>';
	}

	public function set_default_editor( $r ) {
		$r = 'html';
		return $r;
	}

	public function wp_editor_callback( $args ) {
		$item         = $args['item'];
		$msg          = isset( $args['message'] ) ? $args['message'] : '';
		$text         = $this->get_value( $item );
		$html_enabled = $this->get_value( 'email-enable-html' );
		add_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
		echo '<style>#wp-' . esc_attr( sprintf( '%s', $item ) ) . '-wrap{max-width:40em;}</style>';
		wp_editor(
			html_entity_decode( $text ),
			$item,
			array(
				'textarea_name'  => 'swpm-settings[' . $item . ']',
				'teeny'          => true,
				'default_editor' => ! empty( $html_enabled ) ? 'QuickTags' : '',
				'textarea_rows'  => 15,
			)
		);
		remove_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
		echo "<p class=\"description\">{$msg}</p>";
	}

	public function swpm_documentation_callback() {
		?>
		<div class="swpm-orange-box">
			<?php printf( SwpmUtils::_( 'Visit the %s to read setup and configuration documentation.' ), '<a target="_blank" href="https://simple-membership-plugin.com/">' . SwpmUtils::_( 'Simple Membership Plugin Site' ) . '</a>' ); ?>
			<?php printf( SwpmUtils::_( 'Please %s if you like the plugin.' ), '<a href="https://wordpress.org/support/view/plugin-reviews/simple-membership?filter=5" target="_blank">' . SwpmUtils::_( 'give us a rating' ) . '</a>' ); ?>
		</div>
		<?php
	}

	public function swpm_general_post_submit_check_callback() {
		//Show settings updated message
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			echo '<div id="message" class="updated fade"><p>' . SwpmUtils::_( 'Settings updated!' ) . '</p></div>';
		}
	}

	public function general_settings_callback() {
		_e( 'General Plugin Settings.', 'simple-membership' );
	}

	public function pages_settings_callback() {
		_e( 'Page Setup and URL Related settings.', 'simple-membership' );

		echo '<p>';
		_e( 'The following pages are required for the plugin to function correctly. These pages were automatically created by the plugin at install time.', 'simple-membership' );
		_e( ' Read <a href="https://simple-membership-plugin.com/recreating-required-pages-simple-membership-plugin/" target="_blank">this documentation</a> to learn how to recreate them (if needed).', 'simple-membership' );
		echo '</p>';
	}

	public function optional_pages_settings_callback() {
		echo '<p>';
		_e( 'Optional page. It is automatically created by the plugin when you install the plugin for the first time.', 'simple-membership' );
		echo '</p>';
	}

	public function testndebug_settings_callback() {
		_e( 'Testing and Debug Related Settings.', 'simple-membership' );
	}

	public function reg_email_settings_callback() {
		_e( 'This email will be sent to your users when they complete the registration and become a member.', 'simple-membership' );
	}

	public function reset_password_settings_callback() {
		_e( 'This email will be sent to your users when they use the password reset functionality.', 'simple-membership' );
	}

	public function email_settings_overview_callback() {
		echo '<div class="swpm-grey-box">';
		echo '<p>';
		_e( 'This interface lets you customize the various emails that get sent to your members for various actions. The default settings should be good to get your started.', 'simple-membership' );
		echo '</p>';

		echo '<p>';
		echo '<a href="https://simple-membership-plugin.com/email-merge-tags-email-shortcodes-for-email-customization/" target="_blank">' . SwpmUtils::_( 'This documentation' ) . '</a>';
		_e( ' explains what email merge tags you can use in the email body field to customize it (if you want to).', 'simple-membership' );
		echo '</p>';
		echo '</div>';
	}

	public function email_misc_settings_callback() {

		//Show settings updated message when it is updated
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			//This status message need to be in the callback function to prevent header sent warning
			echo '<div id="message" class="updated fade"><p>' . SwpmUtils::_( 'Settings updated!' ) . '</p></div>';
		}

		_e( 'Settings in this section apply to all emails.', 'simple-membership' );
	}

	public function upgrade_email_settings_callback() {
		_e( 'This email will be sent to your users after account upgrade (when an existing member pays for a new membership level).', 'simple-membership' );
	}

	public function renewal_email_settings_callback() {
		_e( 'This email will be sent to your users after account renewal (when an existing member pays for their current membership level).', 'simple-membership' );
	}

	public function bulk_activate_email_settings_callback() {
		_e( 'This email will be sent to your members when you use the bulk account activate and notify action.', 'simple-membership' );
	}

	public function email_activation_email_settings_callback() {
		_e( 'This email will be sent if Email Activation is enabled for a Membership Level.', 'simple-membership' );
	}

    public function subscription_cancel_email_settings_callback() {
		_e( "This email will be sent when a member's subscription is canceled or expires.", 'simple-membership' );
	}

    public function manual_account_approve_email_settings_callback() {
		_e( 'This email is sent to notify a member when their account has been manually approved by an administrator.', 'simple-membership' );
		echo ' ' . '<a href="https://simple-membership-plugin.com/manually-approve-members-membership-site" target="_blank">' . __( 'Manual approval documentation', 'simple-membership' ) . '</a>.';
	}

	public function reg_prompt_email_settings_callback() {
		_e( 'This email will be sent to prompt users to complete registration after the payment.', 'simple-membership' );
	}

	public function advanced_settings_callback() {

		//Show settings updated message when it is updated
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			//This status message need to be in the callback function to prevent header sent warning
			echo '<div id="message" class="updated fade"><p>' . SwpmUtils::_( 'Settings updated!' ) . '</p></div>';

				/* Check if any conflicting setting options have been enabled together. */
				$disable_wp_dashboard_for_non_admins = $this->get_value('disable-access-to-wp-dashboard');
				if ($disable_wp_dashboard_for_non_admins) {
					//The disable wp dashboard option is enabled.
					//Check to make sure the "Admin Dashboard Access Permission" option is not being used for other roles.
					$admin_dashboard_permission = $this->get_value( 'admin-dashboard-access-permission' );
					if ( empty( $admin_dashboard_permission ) || $admin_dashboard_permission == 'manage_options' ) {
						//This is fine.
					} else {
						//Conflicting options enabled.
						//Show warning and reset the option value to default.
						$this->set_value('admin-dashboard-access-permission', 'manage_options');
						$this->save();
						echo '<div id="message" class="error"><p>' . SwpmUtils::_( 'Note: You cannot enable both the "Disable Access to WP Dashboard" and "Admin Dashboard Access Permission" options at the same time. Only use one of those options.' ) . '</p></div>';
					}
				}
				/* End of conflicting options check */
		}

        echo '<div class="swpm-grey-box">';
		echo '<p>';
		_e( 'This page allows you to configure some advanced features of the plugin.', 'simple-membership' );
		echo '</p>';
		echo '</div>';
	}

	public function blacklist_whitelist_overview_callback() {
		//Show settings updated message when it is updated
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			//This status message need to be in the callback function to prevent header sent warning
			echo '<div id="message" class="updated fade"><p>' . SwpmUtils::_( 'Settings updated!' ) . '</p></div>';
		}

		echo '<div class="swpm-grey-box">';
		echo '<p>';
		_e( 'This interface lets you configure blacklisting & whitelisting for email addresses. ', 'simple-membership' );
		echo '<a href="https://simple-membership-plugin.com/blacklisting-whitelisting-feature/" target="_blank">' . SwpmUtils::_( 'This blacklisting & whitelisting documentation' ) . '</a>';
		_e( ' explains how to use this feature.', 'simple-membership' );
		echo '</p>';
		echo '</div>';
	}

	public function whitelist_settings_callback() {
		_e( 'This section allows you to configure whitelisting settings.', 'simple-membership' );
	}

	public function blacklist_settings_callback() {
		_e( 'This section allows you to configure blacklisting settings.', 'simple-membership' );
	}

	public function advanced_settings_wp_toolbar_related_section_callback() {
		_e( "The options in this section allow you to customize the default behavior of the WordPress toolbar. If you choose to use them, ensure you test and verify that the behavior meets your needs.", "simple-membership" );
	}

	public function advanced_settings_auto_create_swpm_uses_settings_callback() {
		_e( 'This section allows you to configure automatic creation of member accounts when new WP User records are created by another plugin. It can be useful if you are using another plugin that creates WP user records and you want them to be recognized in the membership plugin.', 'simple-membership' );
	}

	public function advanced_settings_terms_and_conditions_callback() {
		_e( 'This section allows you to configure terms and conditions and privacy policy that users must accept at registration time.', 'simple-membership' );
		echo ' <a href="https://simple-membership-plugin.com/adding-a-terms-and-conditions-and-privacy-policy-to-member-registration-page/" target="_blank">' . __('Read this documentation', 'simple-membership') . '</a>' . __(' to learn more.', 'simple-membership');
	}

	public function advanced_settings_limit_active_logins_callback() {
		_e( 'This section lets you set active login limits for your members.', 'simple-membership' );
		echo ' <a href="https://simple-membership-plugin.com/configuring-active-login-limit/" target="_blank">' . __('Read this documentation', 'simple-membership') . '</a>' . __(' to learn more.', 'simple-membership');
	}

	public function advanced_settings_failed_login_attempt_limit_callback() {
		_e( 'This section allows you to enable and configure limits for failed login attempts on user accounts.', 'simple-membership' );
		echo ' <a href="https://simple-membership-plugin.com/configuring-the-failed-login-attempt-limit-feature/" target="_blank">' . __('Read this documentation', 'simple-membership') . '</a>' . __(' to learn more.', 'simple-membership');
	}

	public function maximum_active_login_callback( $args ) {
		// Get settings value.
		$item = $args['item'];
		$value = $this->get_value( $item, 3 );

		echo '<p><input type="number" name="swpm-settings['.esc_attr($item).']" id="loggedin_maximum" min="1" value="' . intval( $value ) . '" /></p>';
		echo '<p class="description">' . esc_html__( 'Set the maximum number of active logins allowed for a user account.', 'simple-membership' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Once this limit is reached, any new login will automatically terminate all active sessions of the user from other browsers or devices.', 'simple-membership' ) . '</p>';
	}

	public function max_failed_login_attempts_callback( $args ) {
		// Get settings value.
		$item = $args['item'];
		$value = $this->get_value( $item, 3 );

		echo '<p><input type="number" name="swpm-settings['.esc_attr($item).']" id="'.esc_attr(sanitize_key($item)).'" min="1" value="' . absint( $value ) . '" /></p>';
		echo '<p class="description">' . esc_html__( 'Set the maximum number of failed login attempts allowed before the visitor is temporarily locked out.', 'simple-membership' ) . '</p>';
	}

	public function failed_login_attempts_lockdown_time_callback( $args ) {
		// Get settings value.
		$item = $args['item'];
		$value = $this->get_value( $item, 3 );

		echo '<p><input type="number" name="swpm-settings['.esc_attr($item).']" id="'.esc_attr(sanitize_key($item)).'" min="1" value="' . absint( $value ) . '" /></p>';
		echo '<p class="description">' . esc_html__( 'Set the duration (in minutes) that a visitor will be locked out after reaching the maximum number of failed login attempts.', 'simple-membership' ) . '</p>';
	}

	public function sanitize_tab_1( $input ) {
		if ( empty( $this->settings ) ) {
			$this->settings = (array) get_option( 'swpm-settings' );
		}
		$output = $this->settings;
		//general settings block

		$output['protect-everything']     = isset( $input['protect-everything'] ) ? esc_attr( $input['protect-everything'] ) : '';
		$output['enable-free-membership'] = isset( $input['enable-free-membership'] ) ? esc_attr( $input['enable-free-membership'] ) : '';
		$output['enable-moretag']         = isset( $input['enable-moretag'] ) ? esc_attr( $input['enable-moretag'] ) : '';
		$output['enable-debug']           = isset( $input['enable-debug'] ) ? esc_attr( $input['enable-debug'] ) : '';

		$output['free-membership-id']       = ( $input['free-membership-id'] != 1 ) ? absint( $input['free-membership-id'] ) : '';
		$output['login-page-url']           = esc_url( $input['login-page-url'] );
		$output['registration-page-url']    = esc_url( $input['registration-page-url'] );
		$output['profile-page-url']         = esc_url( $input['profile-page-url'] );
		$output['reset-page-url']           = esc_url( $input['reset-page-url'] );
		$output['thank-you-page-url']       = esc_url( $input['thank-you-page-url'] );
		$output['password-reset-using-link'] = isset( $input['password-reset-using-link'] ) ? esc_attr( $input['password-reset-using-link'] ) : '';
		$output['join-us-page-url']         = esc_url( $input['join-us-page-url'] );
		$output['default-account-status']   = esc_attr( $input['default-account-status'] );
		$output['default-account-status-after-payment']   = esc_attr( $input['default-account-status-after-payment'] );
		$output['members-login-to-comment'] = isset( $input['members-login-to-comment'] ) ? esc_attr( $input['members-login-to-comment'] ) : '';
		$output['password-visibility-login-form'] = isset( $input['password-visibility-login-form'] ) ? esc_attr( $input['password-visibility-login-form'] ) : '';

		return $output;
	}

	public function sanitize_tab_2( $input ) {
		//This is the callback function for the second tab.
		//It sanitizes the input data for the second tab.
		//This tab has been moved to the payments menu. In the future, we can remove this tab or re-use it for something else.
	}

	public function sanitize_tab_3( $input ) {
		if ( empty( $this->settings ) ) {
			$this->settings = (array) get_option( 'swpm-settings' );
		}
		$output                              = $this->settings;
		$output['reg-complete-mail-subject'] = sanitize_text_field( $input['reg-complete-mail-subject'] );

		$output['reg-complete-mail-body']          = wp_kses_post( $input['reg-complete-mail-body'] );
		$output['reg-complete-mail-subject-admin'] = sanitize_text_field( $input['reg-complete-mail-subject-admin'] );
		$output['reg-complete-mail-body-admin']    = wp_kses_post( $input['reg-complete-mail-body-admin'] );

		$output['reset-mail-subject'] = sanitize_text_field( $input['reset-mail-subject'] );
		$output['reset-mail-body']    = wp_kses_post( $input['reset-mail-body'] );

		$output['upgrade-complete-mail-subject'] = sanitize_text_field( $input['upgrade-complete-mail-subject'] );
		$output['upgrade-complete-mail-body']    = wp_kses_post( $input['upgrade-complete-mail-body'] );
		$output['disable-email-after-upgrade']   = isset( $input['disable-email-after-upgrade'] ) ? esc_attr( $input['disable-email-after-upgrade'] ) : '';

		$output['renew-complete-mail-subject'] = sanitize_text_field( $input['renew-complete-mail-subject'] );
		$output['renew-complete-mail-body']    = wp_kses_post( $input['renew-complete-mail-body'] );
		$output['disable-email-after-renew']   = isset( $input['disable-email-after-renew'] ) ? esc_attr( $input['disable-email-after-renew'] ) : '';

		$output['bulk-activate-notify-mail-subject'] = sanitize_text_field( $input['bulk-activate-notify-mail-subject'] );
		$output['bulk-activate-notify-mail-body']    = wp_kses_post( $input['bulk-activate-notify-mail-body'] );

		$output['email-activation-mail-subject'] = sanitize_text_field( $input['email-activation-mail-subject'] );
		$output['email-activation-mail-body']    = wp_kses_post( $input['email-activation-mail-body'] );

		$output['subscription-cancel-member-mail-enable']       = isset( $input['subscription-cancel-member-mail-enable'] ) ? esc_attr( $input['subscription-cancel-member-mail-enable'] ) : '';
		$output['subscription-cancel-member-mail-subject']    = sanitize_text_field( $input['subscription-cancel-member-mail-subject'] );
		$output['subscription-cancel-member-mail-body']    = wp_kses_post( $input['subscription-cancel-member-mail-body'] );

        $output['subscription-cancel-admin-mail-enable']       = isset( $input['subscription-cancel-admin-mail-enable'] ) ? esc_attr( $input['subscription-cancel-admin-mail-enable'] ) : '';
        $output['subscription-cancel-admin-mail-address'] = sanitize_text_field( $input['subscription-cancel-admin-mail-address'] );

		$output['manual-account-approve-member-mail-enable'] = isset( $input['manual-account-approve-member-mail-enable'] ) ? esc_attr( $input['manual-account-approve-member-mail-enable'] ) : '';
		$output['manual-account-approve-member-mail-subject'] = sanitize_text_field( $input['manual-account-approve-member-mail-subject'] );
		$output['manual-account-approve-member-mail-body'] = wp_kses_post( $input['manual-account-approve-member-mail-body'] );

		$output['reg-prompt-complete-mail-subject'] = sanitize_text_field( $input['reg-prompt-complete-mail-subject'] );
		$output['reg-prompt-complete-mail-body']    = wp_kses_post( $input['reg-prompt-complete-mail-body'] );
		$output['email-from']                       = trim( $input['email-from'] );
		$output['email-enable-html']                = isset( $input['email-enable-html'] ) ? esc_attr( $input['email-enable-html'] ) : '';

		$output['enable-admin-notification-after-reg']       = isset( $input['enable-admin-notification-after-reg'] ) ? esc_attr( $input['enable-admin-notification-after-reg'] ) : '';
		$output['admin-notification-email']                  = sanitize_text_field( $input['admin-notification-email'] );
		$output['enable-notification-after-manual-user-add'] = isset( $input['enable-notification-after-manual-user-add'] ) ? esc_attr( $input['enable-notification-after-manual-user-add'] ) : '';

		return $output;
	}

	public function sanitize_tab_5( $input ) {
		if ( empty( $this->settings ) ) {
			$this->settings = (array) get_option( 'swpm-settings' );
		}
		$output                                      = $this->settings;
		$output['enable-expired-account-login']      = isset( $input['enable-expired-account-login'] ) ? esc_attr( $input['enable-expired-account-login'] ) : '';
		$output['logout-member-on-browser-close']    = isset( $input['logout-member-on-browser-close'] ) ? esc_attr( $input['logout-member-on-browser-close'] ) : '';
		$output['allow-account-deletion']            = isset( $input['allow-account-deletion'] ) ? esc_attr( $input['allow-account-deletion'] ) : '';
		$output['delete-pending-account']            = isset( $input['delete-pending-account'] ) ? esc_attr( $input['delete-pending-account'] ) : 0;
		$output['admin-dashboard-access-permission'] = isset( $input['admin-dashboard-access-permission'] ) ? esc_attr( $input['admin-dashboard-access-permission'] ) : '';
		$output['renewal-page-url']                  = esc_url( $input['renewal-page-url'] );
		$output['after-rego-redirect-page-url']      = esc_url( $input['after-rego-redirect-page-url'] );
		$output['after-logout-redirection-url']      = esc_url( $input['after-logout-redirection-url'] );
		$output['force-strong-passwords']            = isset( $input['force-strong-passwords'] ) ? esc_attr( $input['force-strong-passwords'] ) : '';
		$output['auto-login-after-rego']             = isset( $input['auto-login-after-rego'] ) ? esc_attr( $input['auto-login-after-rego'] ) : '';
		$output['hide-reg-form-membership-level-field'] = isset( $input['hide-reg-form-membership-level-field'] ) ? esc_attr( $input['hide-reg-form-membership-level-field'] ) : '';
        $output['hide-rego-form-to-logged-users']    = isset( $input['hide-rego-form-to-logged-users'] ) ? esc_attr( $input['hide-rego-form-to-logged-users'] ) : '';
		$output['hide-join-us-link']                = isset( $input['hide-join-us-link'] ) ? esc_attr( $input['hide-join-us-link'] ) : '';
		$output['force-wp-user-sync']                = isset( $input['force-wp-user-sync'] ) ? esc_attr( $input['force-wp-user-sync'] ) : '';
		$output['payment-notification-forward-url']  = esc_url( $input['payment-notification-forward-url'] );
		$output['use-new-form-ui']            		 = isset( $input['use-new-form-ui'] ) ? esc_attr( $input['use-new-form-ui'] ) : '';
		$output['hide-adminbar']                  = isset( $input['hide-adminbar'] ) ? esc_attr( $input['hide-adminbar'] ) : '';
		$output['show-adminbar-admin-only']       = isset( $input['show-adminbar-admin-only'] ) ? esc_attr( $input['show-adminbar-admin-only'] ) : '';
		$output['disable-access-to-wp-dashboard'] = isset( $input['disable-access-to-wp-dashboard'] ) ? esc_attr( $input['disable-access-to-wp-dashboard'] ) : '';

		//Auto create swpm user related settings
		$output['enable-auto-create-swpm-members']      = isset( $input['enable-auto-create-swpm-members'] ) ? esc_attr( $input['enable-auto-create-swpm-members'] ) : '';
		$output['auto-create-default-membership-level'] = isset( $input['auto-create-default-membership-level'] ) ? esc_attr( $input['auto-create-default-membership-level'] ) : '';
		$output['auto-create-default-account-status']   = isset( $input['auto-create-default-account-status'] ) ? esc_attr( $input['auto-create-default-account-status'] ) : '';
		//Terms and conditions related settings
		$output['enable-terms-and-conditions']   = isset( $input['enable-terms-and-conditions'] ) ? esc_attr( $input['enable-terms-and-conditions'] ) : '';
		$output['terms-and-conditions-page-url'] = esc_url( $input['terms-and-conditions-page-url'] );
		$output['enable-privacy-policy']         = isset( $input['enable-privacy-policy'] ) ? esc_attr( $input['enable-privacy-policy'] ) : '';
		$output['privacy-policy-page-url']       = esc_url( $input['privacy-policy-page-url'] );

		$output['enable-login-limiter'] = isset( $input['enable-login-limiter'] ) && !empty($input['enable-login-limiter']) ? esc_attr( $input['enable-login-limiter'] ) : '';
        $output['maximum-active-logins'] = isset( $input['maximum-active-logins'] ) && !empty($input['maximum-active-logins']) ? esc_attr( $input['maximum-active-logins'] ) : '';
        // $output['login-logic'] = isset( $input['login-logic'] ) && !empty($input['login-logic']) ? esc_attr( $input['login-logic'] ) : '';

		$output['enable-failed-login-attempt-limiter'] = isset( $input['enable-failed-login-attempt-limiter'] ) && !empty($input['enable-failed-login-attempt-limiter']) ? esc_attr( $input['enable-failed-login-attempt-limiter'] ) : '';
		$output['max-failed-login-attempts'] = isset( $input['max-failed-login-attempts'] ) && !empty($input['max-failed-login-attempts']) ? esc_attr( $input['max-failed-login-attempts'] ) : '';
		$output['failed-login-attempt-lockdown-time'] = isset( $input['failed-login-attempt-lockdown-time'] ) && !empty($input['failed-login-attempt-lockdown-time']) ? esc_attr( $input['failed-login-attempt-lockdown-time'] ) : '';

		return $output;
	}

	public function sanitize_tab_6( $input ) {
		if ( empty( $this->settings ) ) {
			$this->settings = (array) get_option( 'swpm-settings' );
		}
		$output = $this->settings;
		$output['enable-whitelisting'] = isset( $input['enable-whitelisting'] ) ? esc_attr( $input['enable-whitelisting'] ) : '';
		$output['whitelist-email-address'] = isset( $input['whitelist-email-address'] ) ? esc_attr( $input['whitelist-email-address'] ) : '';
		$output['whitelist-email-address-pattern'] = isset( $input['whitelist-email-address-pattern'] ) ? esc_attr( $input['whitelist-email-address-pattern'] ) : '';
		$output['whitelist-block-message'] = isset( $input['whitelist-block-message'] ) ? esc_attr( $input['whitelist-block-message'] ) : '';

		$output['enable-blacklisting'] = isset( $input['enable-blacklisting'] ) ? esc_attr( $input['enable-blacklisting'] ) : '';
		$output['blacklist-email-address'] = isset( $input['blacklist-email-address'] ) ? esc_attr( $input['blacklist-email-address'] ) : '';
		$output['blacklist-email-address-pattern'] = isset( $input['blacklist-email-address-pattern'] ) ? esc_attr( $input['blacklist-email-address-pattern'] ) : '';
		$output['blacklist-block-message'] = isset( $input['blacklist-block-message'] ) ? esc_attr( $input['blacklist-block-message'] ) : '';
		return $output;
	}

	public function get_value( $key, $default = '' ) {
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}
		return $default;
	}

	public function set_value( $key, $value ) {
		$this->settings[ $key ] = $value;
		return $this;
	}

	public function save() {
		update_option( 'swpm-settings', $this->settings );
	}

	public function draw_tabs() {
		$current = $this->current_tab;
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $this->tabs as $id => $label ) { ?>
				<a class="nav-tab <?php echo ( $current == $id ) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=simple_wp_membership_settings&tab=<?php echo $id; ?>"><?php echo $label; ?></a>
			<?php } ?>
		</h2>
		<?php
	}

	public function handle_main_settings_admin_menu() {
		do_action( 'swpm_settings_menu_start' );

		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin( 'Main Settings Menu' );

		?>
		<div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

			<h1><?php _e( 'Settings' ); ?></h1><!-- page title -->

			<!-- start nav menu tabs -->
			<?php do_action( 'swpm-draw-settings-nav-tabs' ); ?>
			<!-- end nav menu tabs -->
			<?php
			do_action( 'swpm_settings_menu_after_nav_tabs' );

			//Switch to handle the body of each of the various settings pages based on the currently selected tab
			$current_tab = $this->current_tab;
			switch ( $current_tab ) {
				case 1:
					//General settings
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_settings.php';
					break;
				case 2:
					//Payment settings
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/admin_payment_settings.php';
					break;
				case 3:
					//Email settings
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_settings.php';
					break;
				/* Note: The Tools tab has been moved to an independant menu. */
				case 5:
					//Advanced settings
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_settings.php';
					break;
				case 6:
					//Blacklist & whitelist settings
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_settings.php';
					break;
				case 7:
					//Addon settings
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_addon_settings.php';
					break;
				default:
					//The default fallback (general settings)
					include SIMPLE_WP_MEMBERSHIP_PATH . 'views/admin_settings.php';
					break;
			}

			echo '</div>'; //<!-- end of wrap -->
	}

}

