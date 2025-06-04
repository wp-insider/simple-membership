<?php
$links = array();
if(isset($_REQUEST['swpm_link_for'])){
    //Rego complete link feature
    $link_for = isset($_POST['swpm_link_for']) ? sanitize_text_field($_POST['swpm_link_for']) : '';
    $member_id = filter_input(INPUT_POST, 'member_id', FILTER_SANITIZE_NUMBER_INT);
    $send_email = isset($_REQUEST['swpm_reminder_email']) ? true : false;
    $links = SwpmUtils::get_registration_complete_prompt_link($link_for, $send_email, $member_id);
}

if(isset($_REQUEST['recreate-required-pages-submit'])){
    //Lets recreate the required pages
    SwpmMiscUtils::create_mandatory_wp_pages();
    echo '<div class="swpm-green-box">' . SwpmUtils::_('The required pages have been re-created.') . '</div>';
}

if ( isset($_POST['resend_activation_email_submit']) && isset($_POST['resend_activation_required_email_to'])){
    // Nonce check
	check_admin_referer('resend_activation_email_nonce_action');

    $send_emails_to = sanitize_text_field($_POST['resend_activation_required_email_to']);

    $activation_required_member_ids = array();

    if ($send_emails_to == 'all'){
        $members = SwpmMemberUtils::get_all_members_of_account_status('activation_required');
        if (!empty($members)){
            // Collect the member ids
            foreach ($members as $member_info){
	            $activation_required_member_ids[] = $member_info->member_id;
            }
        } else {
	        echo '<div class="notice notice-warning"><p>' . esc_html__('No member account found to send activation emails to.', 'simple-membership') . '</p></div>';
        }
    } else {
        if (isset($_POST['activation_member_id']) && is_numeric($_POST['activation_member_id'])){
            // Collect the member id.
	        $activation_required_member_ids[] = intval(sanitize_text_field($_POST['activation_member_id']));
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Empty or invalid member ID provided!', 'simple-membership') . '</p></div>';
        }
    }

    if (!empty($activation_required_member_ids)){
        $is_any_error = false;
        foreach ($activation_required_member_ids as $member_id){
            SwpmMiscUtils::resend_activation_email_by_member_id($member_id);

            // Check if any error.
            $error_msg = SwpmTransfer::get_instance()->get('resend_activation_email_error');
            if (!empty($error_msg)){
	            $is_any_error = true;
	            echo '<div class="notice notice-error"><p>' . esc_html($error_msg) . '</p></div>';
            }
        }

        // If no error, show the success msg.
        if (!$is_any_error){
            echo '<div class="notice notice-success"><p>' . esc_html__('Account activation email sent successfully!', 'simple-membership') . '</p></div>';
        }
    }
}
?>
<!-- Note: This view file is included inside of post-stuff and post-body divs already -->
<div class="swpm-grey-box">
<?php _e("This interface contains useful tools for various admin operations.", "simple-membership") ?>
</div>

<div class="postbox">
    <h3 class="hndle"><label for="title"><?php _e('Generate Registration Completion Link', 'simple-membership') ?></label></h3>
    <div class="inside">

        <p class="description">
            <?php _e('You can manually generate a unique registration completion link here and share it with your customer if they missed the email that was automatically sent after payment.', 'simple-membership'); ?>
        </p>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th>
                        <?php echo SwpmUtils::_('Generate Registration Completion Link') ?>
                    </th>
                    <td>
                        <input type="radio" value="one" name="swpm_link_for" /><?php _e('For a Particular Member ID', 'simple-membership'); ?>
                        <input type="text" name="member_id" size="5" value="" />
                        <div><?php echo SwpmUtils::_('OR') ?></div>
                        <input type="radio" checked="checked" value="all" name="swpm_link_for" /> <?php echo SwpmUtils::_('For All Incomplete Registrations') ?>
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <input type="checkbox" value="checked" name="swpm_reminder_email">
                        <?php 
                        echo ' ' . __('Send Registration Reminder Email Too', 'simple-membership') ;
                        ?>
                    </td>
                </tr>
            </table>
            <input type="submit" name="submit" class="button-primary" value="<?php _e('Submit', 'simple-membership') ?>" />

            <div class="swpm-margin-top-10"></div>
            <?php
            if (!empty($links)) {
                echo '<div class="swpm-green-box">' . SwpmUtils::_('Link(s) generated successfully. The following link(s) can be used to complete the registration.') . '</div>';
            } else {
                echo '<div class="swpm-grey-box">' . SwpmUtils::_('The registration completion link(s) will be displayed below.') . '</div>';
            }
            ?>
            <div class="swpm-margin-top-10"></div>
            <?php foreach ($links as $key => $link) { ?>
                <input type="text" size="120" readonly="readonly" name="link[<?php echo $key ?>]" value="<?php echo $link; ?>"/><br/>
            <?php } ?>

            <?php
            if (isset($_REQUEST['swpm_reminder_email'])) {
                echo '<div class="swpm-green-box">' . SwpmUtils::_('A prompt to complete registration email was also sent.') . '</div>';
            }
            ?>            
        </form>

    </div>
</div>

<div class="postbox">
    <h3 class="hndle"><label for="title"><?php echo SwpmUtils::_('Re-create the Required Pages') ?></label></h3>
    <div class="inside">

        <p class="description"><?php echo SwpmUtils::_('If you have accidentally deleted the required pages that this plugin creates at install time, you can use this option to re-create them.') ?></p>
        <p><a href="https://simple-membership-plugin.com/recreating-required-pages-simple-membership-plugin/" target="_blank"><?php echo SwpmUtils::_('This documentation'); ?></a><?php echo SwpmUtils::_(' has full explanation.'); ?></p>
        <form action="" method="post" onsubmit="return confirm('Do you really want to re-create the pages?');">
            <table>
                <tr>
                    <td>
                        <div class="swpm-margin-top-10"></div>
                        <input type="submit" name="recreate-required-pages-submit" class="button-primary" value="<?php echo SwpmUtils::_('Re-create the Required Pages') ?>" />
                    </td>
                </tr>
            </table>
        </form>

    </div>
</div>

<div class="postbox">
    <h3 class="hndle">
        <label for="title"><?php esc_html_e( 'Resend Account Activation Email', 'simple-membership' ) ?></label>
    </h3>
    <div class="inside">
        <p class="description">
            <?php 
            _e( 'If you are using the ', 'simple-membership');
            echo '<a href="https://simple-membership-plugin.com/email-activation-for-members/" target="_blank">' . __('Email Activation Feature', 'simple-membership') . '</a>';
            _e( ', this tool allows the admin to manually resend the activation email to member(s). It can be useful if a member did not receive the original email or needs a new activation link.', 'simple-membership');
            ?>
        </p>

        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Resend Activation Email To', 'simple-membership'); ?></th>
                    <td>
                        <div>
                            <label>
                                <input type="radio" name="resend_activation_required_email_to" value="one" checked>
                                <span>
                                    <?php esc_html_e('For a Specific Member ID', 'simple-membership'); ?>
                                    <input type="text" name="activation_member_id" size="5">
                                </span>
                            </label>
                        </div>
                        <div>
                            <?php esc_html_e('OR', 'simple-membership'); ?>
                        </div>
                        <div>
                            <label>
                                <input type="radio" name="resend_activation_required_email_to" value="all">
                                <span>
                                    <?php esc_html_e('For All Accounts Awaiting Activation', 'simple-membership'); ?>
                                </span>
                            </label>
                        </div>
                    </td>
                </tr>
            </table>

            <?php wp_nonce_field('resend_activation_email_nonce_action') ?>

            <p class="submit">
                <input type="submit" name="resend_activation_email_submit" class="button-primary" value="<?php esc_attr_e( 'Re-send Activation Email', 'simple-membership') ?>"/>
            </p>
        </form>

    </div>
</div>
