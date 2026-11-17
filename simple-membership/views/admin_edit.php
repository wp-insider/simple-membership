<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//This file is used to edit member's profile from the admin dashboard of the plugin.

//The admin ajax causes an issue with the JS validation if done on form submission. The edit profile doesn't need JS validation on email. There is PHP validation which will catch any email error.
//SimpleWpMembership::enqueue_validation_scripts(array('ajaxEmailCall' => array('extraData'=>'&action=swpm_validate_email&member_id='.$member_id)));

$is_attached_subscription_canceled = SwpmMemberUtils::get_subscription_data_extra_info($member_id, 'subscription_status') === 'inactive';

//Get the current expiry date based on the membership level of this member.
$member_current_expiry_date = SwpmMemberUtils::get_formatted_expiry_date_by_user_id($member_id);
?>
<div class="wrap" id="swpm-profile-page" type="edit">
    <form action="" method="post" name="swpm-edit-user" id="swpm-edit-user" enctype="multipart/form-data" class="validate swpm-validate-form"<?php do_action('user_new_form_tag');?>>
    <input name="action" type="hidden" value="edituser" />
    <?php wp_nonce_field( 'edit_swpmuser_admin_end', '_wpnonce_edit_swpmuser_admin_end' ) ?>
    <h3><?php echo  esc_html__( 'Edit Member', 'simple-membership' ) ?></h3>
    <p>
        <?php echo  esc_html__( 'Edit existing member details.', 'simple-membership' ); ?>
        <?php echo  esc_html__( ' You are currently editing member with member ID: ', 'simple-membership' ); ?>
        <?php echo esc_attr($member_id); ?>
    </p>
    <table class="form-table">
        <tr class="form-field form-required swpm-admin-edit-username">
            <th scope="row"><label for="user_name"><?php echo  esc_html__( 'Username', 'simple-membership' ); ?> <span class="description"><?php echo  esc_html__( '(required)', 'simple-membership' ); ?></span></label></th>
            <td>
                <?php
                if (empty($user_name)) {
                    //This is a record with incomplete registration. The member need to complete the registration by clicking on the unique link sent to them
                    ?>
                    <div class="swpm-yellow-box" style="max-width:450px;">
                        <p>This user account registration is not complete yet. The member needs to click on the unique registration completion link (sent to his email) and complete the registration by choosing a username and password.</p>
                        <br />
                        <p>You can go to the <a href="admin.php?page=simple_wp_membership_tools" target="_blank">Tools Interface</a> and generate another unique "Registration Completion" link then send the link to the user. Alternatively, you can use that link yourself and complete the registration on behalf of the user.</p>
                        <br />
                        <p>If you suspect that this user has lost interest in becoming a member then you can delete this member record.</p>
                    </div>
                    <?php
                } else {
                    echo esc_attr($user_name);
                }
                ?>
            </td>
        </tr>
        <tr class="form-required swpm-admin-edit-email">
            <th scope="row"><label for="email"><?php echo  esc_html__( 'E-mail', 'simple-membership' ); ?> <span class="description"><?php echo  esc_html__( '(required)', 'simple-membership' ); ?></span></label></th>
            <td><input name="email" autocomplete="off" class="regular-text validate[required,custom[email],ajax[ajaxEmailCall]]" type="text" id="email" value="<?php echo esc_attr($email); ?>" /></td>
        </tr>
        <tr class="swpm-admin-edit-password">
            <th scope="row"><label for="password"><?php echo  esc_html__( 'Password', 'simple-membership' ); ?> <span class="description"><?php esc_attr_e('(twice, leave empty to retain old password)', 'simple-membership'); ?></span></label></th>
            <td><input class="regular-text"  name="password" type="password" id="pass1" autocomplete="off" /><br />
            <input class="regular-text" name="password_re" type="password" id="pass2" autocomplete="off" />
            <br />
            <div id="pass-strength-result"><?php echo esc_html__( 'Strength indicator', 'simple-membership' ); ?></div>
            <p class="description indicator-hint"><?php echo esc_html__( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'simple-membership' ); ?></p>
            </td>
	</tr>
	<tr class="swpm-admin-edit-account-state">
            <th scope="row"><label for="account_state"><?php echo  esc_html__( 'Account Status', 'simple-membership' ); ?></label></th>
            <td>
                <select class="regular-text" name="account_state" id="account_state">
                        <?php echo  wp_kses( SwpmUtils::account_state_dropdown($account_state), SwpmKsesUtils::wp_kses_post_tags_with_form() );?>
                </select>
                <p class="description">
                    <?php echo esc_html__( "This is the member's account status. If you want to manually activate an expired member's account then read", 'simple-membership' ); ?>
                    <a href="https://simple-membership-plugin.com/manually-activating-expired-members-account/" target="_blank"><?php echo esc_html__( "this documentation", 'simple-membership' ); ?></a>
                    <?php echo esc_html__( " to learn how to do it.", 'simple-membership' ); ?>
                </p>
            </td>
	</tr>
	<tr class="swpm-admin-edit-notify-user">
            <th scope="row"><label for="account_state_change"><?php echo  esc_html__( 'Notify User', 'simple-membership' ); ?></label></th>
            <td><input type="checkbox" id="account_status_change" name="account_status_change" />
                <p class="description indicator-hint">
                    <?php echo esc_html__( "You can use this option to send a quick notification email to this member (the email will be sent when you hit the save button below).", 'simple-membership' ); ?>
                </p>
            </td>
	</tr>
        <tr class="swpm-admin-edit-membership-level">
            <th scope="row"><label for="membership_level"><?php echo esc_html__( 'Membership Level', 'simple-membership' ); ?></label></th>
            <td>
                <?php
                //This is an edit member record view. Check that the membershp level is set.
                if ( !isset( $membership_level ) || empty( $membership_level ) ){
                    //The member's membership level is not set. Show an error message.
                    echo '<div class="swpm-yellow-box" style="max-width:450px;">';
                    echo '<p>' . 'Error! This user\'s membership level is not set. Please select a membership level and save the record.' . '</p>';
                    echo '<p>';
                    echo 'If member accounts are created without a level, that indicates a problem in your setup. Please review your ';
                    echo '<a href="https://simple-membership-plugin.com/membership-registration-process-overview/" target="_blank">registration setup</a>.';
                    echo '</p>';
                    echo '</div>';
                }
                ?>
                <select class="regular-text" name="membership_level" id="membership_level">
                    <?php
                    if ( !isset( $membership_level ) || empty( $membership_level ) ){
                        echo '<option value="2">--</option>';//Show select prompt and set the action value to the default level ID.
                    }
                    ?>
                    <?php foreach ($levels as $level): ?>
                        <option <?php echo ($level['id'] == $membership_level) ? "selected='selected'" : ""; ?> value="<?php echo esc_attr( $level['id'] ); ?>"> <?php echo esc_attr( $level['alias'] )?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    <?php include('admin_member_form_common_part.php');?>
    <tr class="swpm-admin-edit-subscriber-id">
		<th scope="row"><label for="subscr_id"><?php echo  esc_html__( 'Subscriber ID/Reference', 'simple-membership' ) ?> </label></th>
		<td><input class="regular-text" name="subscr_id" type="text" id="subscr_id" value="<?php echo esc_attr($subscr_id); ?>" /></td>
	</tr>
    <?php if ($is_attached_subscription_canceled) { ?>
        <tr class="swpm-form-row swpm-subscription-status-row">
            <th scope="row"><label for="subscr_id"><?php esc_attr_e('Subscription Status', 'simple-membership') ?> </label></th>
            <td>
                    <span style="color: #CC0000">
                        <b><?php esc_attr_e('Canceled or Expired', 'simple-membership') ?></b>
                    </span>
                    <p class="description">
                        <?php esc_attr_e('The subscription associated with this member profile has been canceled or expired. The member may purchase a new subscription when needed.', 'simple-membership') ?>
                        <?php esc_attr_e(' The account will expire based on the membership level settings. To learn more about the membership level settings, refer to ', 'simple-membership') ?>
                        <a href="https://simple-membership-plugin.com/adding-membership-access-levels-site/" target="_blank"><?php esc_attr_e('this documentation', 'simple-membership') ?></a>.
                    </p>
            </td>
        </tr>
    <?php } ?>
    <tr class="swpm-admin-edit-expiry-date">
		<th scope="row"><label for="member_expiry_date"><?php echo esc_html__( 'Expiry Date', 'simple-membership' ) ?> </label></th>
		<td>
                    <?php
                    echo esc_attr($member_current_expiry_date);
                    ?>
                    <p class="description indicator-hint">
                        <?php 
                        esc_attr_e('This is calculated based on the current membership level assigned to this member and the expiry condition that you have set for that membership level.', 'simple-membership');
                        esc_attr_e(' To learn more about membership level configuration, refer to ', 'simple-membership');
                        echo wp_kses_post( __( '<a href="https://simple-membership-plugin.com/adding-membership-access-levels-site/" target="_blank">this documentation</a>.', 'simple-membership' ) );
                        ?>
                    </p>
                </td>
	</tr>
    <tr class="swpm-admin-edit-last-accessed">
		<th scope="row"><label for="last_accessed"><?php echo esc_html__( 'Last Accessed Date', 'simple-membership' ) ?> </label></th>
		<td>
                    <?php echo esc_attr($last_accessed); ?>
                    <p class="description indicator-hint"><?php echo esc_html__( 'This value gets updated when this member logs into your site.', 'simple-membership' ) ?></p>
        </td>
	</tr>
    <tr class="swpm-admin-edit-last-accessed-ip">
		<th scope="row"><label for="last_accessed_from_ip"><?php echo  esc_html__( 'Last Accessed From IP', 'simple-membership' ) ?> </label></th>
		<td>
                    <?php echo esc_attr($last_accessed_from_ip); ?>
                    <p class="description indicator-hint"><?php echo esc_html__( 'This value gets updated when this member logs into your site.', 'simple-membership' ) ?></p>
        </td>
	</tr>

    <?php if( isset($extra_info) && !empty($extra_info) ){ ?>
    <tr class="swpm-admin-edit-any-extra-info">
		<th scope="row"><label for="extra_info"><?php echo  esc_html__( 'System-Related Additional Data', 'simple-membership' ) ?> </label></th>
		<td>
                    <?php echo esc_attr($extra_info); ?>
                    <p class="description indicator-hint"><?php echo esc_html__( 'The plugin saves this information for system purposes for some profiles. There is no need for you to take any action regarding this value.', 'simple-membership' ) ?></p>
        </td>
	</tr>
    <?php } ?>
    <?php 
    //Filter hook before the table ends.
    echo apply_filters('swpm_admin_edit_member_extra_rows', '', $member_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters.
    ?>
    </table>

    <?php include('admin_member_form_common_js.php'); ?>
    <?php echo apply_filters('swpm_admin_custom_fields', '',$membership_level); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>
    <?php
    submit_button( SwpmUtils::_('Save Data'), 'primary', 'editswpmuser', true, array( 'id' => 'createswpmusersub' ) );
	?>
    </form>
    <?php 
    //Additional actions section.
    echo '<div style="margin-top: 15px">';
    echo '<hr>';
    echo '<h3>' . esc_html__( 'Additional Member Actions', 'simple-membership' ) . '</h3>';

    //Delete user profile link.    
    $delete_swpmuser_nonce = wp_create_nonce( 'delete_swpmuser_admin_end' );
    $member_delete_url = "?page=simple_wp_membership&member_action=delete&member_id=" . $member_id . "&delete_swpmuser_nonce=" . $delete_swpmuser_nonce;
    echo '<div class="swpm-admin-delete-user-profile-link">';
    echo '<div class="swpm-margin-top-10">';
    echo '<a class="button" style="color:red;font-weight:bold;" href="' . esc_url( $member_delete_url ) . '" onclick="return confirm(\'Are you sure you want to delete this user profile?\')">' . esc_html__( 'Delete User Profile', 'simple-membership' ) . '</a>';
    echo '<p class="description">' . esc_html__( 'Use this button to permanently delete this user profile.', 'simple-membership' ) . '</p>';
    echo '</div>';

    //Manual account approval button.
    if ( strtolower( $account_state ) == 'pending' ) {
        echo '<div style="margin-top: 15px">';
        echo '<form action="" method="post">';
        wp_nonce_field('swpm_admin_member_account_approve', 'swpm_admin_member_account_approve_nonce');
        echo '<div class="swpm_admin_member_account_approve_btn_wrap">';
        echo '<input type="hidden" name="swpm_admin_member_account_approve" value="1">';
        echo '<input type="hidden" name="member_id" value="'.esc_attr($member_id) .'">';
        echo '<input type="hidden" name="member_email" value="'.esc_attr($email) .'">';
        echo '<input type="submit" name="swpm_admin_approve_account_btn" id="swpm_admin_member_account_approve_btn" class="button" value="' . esc_html__( 'Approve Account', 'simple-membership' ) . '" onclick="return confirm(\'Are you sure you want to approve this account?\');">';
        echo '<p class="description">' . esc_html__( 'Use this button to approve this user profile. See the ', 'simple-membership' ) . '<a href="https://simple-membership-plugin.com/manually-approve-members-membership-site" target="_blank">' . esc_html__( 'manual approval documentation', 'simple-membership' ) . '</a>.</p>';
        echo '</div>';
        echo '</form>';
    }
    ?>
</div>
<script>
jQuery(document).ready(function($){
    $('#account_status_change').change(function(){
        var target = $(this).closest('tr');
        var $body = '<textarea rows="5" cols="60" id="notificationmailbody" name="notificationmailbody">' + SwpmSettings.statusChangeEmailBody + '</textarea>';
        var $head = '<input type="text" size="60" id="notificationmailhead" name="notificationmailhead" value="' + SwpmSettings.statusChangeEmailHead + '" />';
        var content = '<tr><th scope="row">Mail Subject</th><td>' + $head + '</td></tr>';
        content += '<tr><th scope="row">Mail Body</th><td>' + $body + '</td></tr>';
        if (this.checked) {
            target.after(content);
        }
        else {
            if (target.next('tr').find('#notificationmailhead').length > 0) {
                target.next('tr').remove();
                target.next('tr').remove();
            }
        }
    });
});
</script>
