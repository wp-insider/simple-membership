<?php
$form_id = 'swpm-edit-level';

SimpleWpMembership::enqueue_validation_scripts_v2(
	'swpm-membership-level-form-validator',
	array(
		'form_id' => $form_id,
	)
);

$is_email_activation_conflicting = SwpmSettings::get_instance()->get_value( 'default-account-status' ) == 'pending' && checked($email_activation, true, false) ? true : false;
?>
<div class="wrap" id="swpm-level-page">
<form action="" method="post" name="swpm-edit-level" id="<?php echo esc_attr($form_id); ?>" class="swpm-validate-form"<?php do_action('level_edit_form_tag');?>>
<input name="action" type="hidden" value="editlevel" />
<?php wp_nonce_field( 'edit_swpmlevel_admin_end', '_wpnonce_edit_swpmlevel_admin_end' ) ?>
<h2><?php echo  SwpmUtils::_('Edit membership level'); ?></h2>
<p>
    <?php 
    echo __('You can edit details of a selected membership level from this interface. ', 'simple-membership');
    echo __(' Refer to ', 'simple-membership');
    echo '<a href="https://simple-membership-plugin.com/adding-membership-access-levels-site/" target="_blank">' . __('this documentation', 'simple-membership') . '</a>';
    echo __(' to learn how a membership level works.', 'simple-membership');
    ?>
</p>
<?php 
    echo '<p><strong>';
    echo __('You are currently editing: ', 'simple-membership');
    echo esc_attr(stripslashes($alias));
    echo __(' (Level ID: ', 'simple-membership') . esc_attr($id) . ')';
    echo '</strong></p>';
?>
<table class="form-table">
    <tbody>
	<tr>
		<th scope="row"><label for="alias"><?php echo  SwpmUtils::_('Membership Level Name'); ?> <span class="description"><?php echo  SwpmUtils::_('(required)'); ?></span></label></th>
		<td><input class="regular-text validate[required]" name="alias" type="text" id="alias" value="<?php echo esc_attr(stripslashes($alias)); ?>" aria-required="true" /></td>
	</tr>
	<tr class="form-field form-required">
		<th scope="row"><label for="role"><?php echo  SwpmUtils::_('Default WordPress Role'); ?> <span class="description"><?php echo  SwpmUtils::_('(required)'); ?></span></label></th>
		<td><select  class="regular-text" name="role"><?php wp_dropdown_roles( $role ); ?></select></td>
	</tr>
    <tr>
        <th scope="row"><label for="subscription_period"><?php echo  SwpmUtils::_('Access Duration'); ?> <span class="description"><?php echo  SwpmUtils::_('(required)'); ?></span></label>
        </th>
        <td>
                <p><input type="radio" <?php echo  checked(SwpmMembershipLevel::NO_EXPIRY,$subscription_duration_type,false)?> value="<?php echo  SwpmMembershipLevel::NO_EXPIRY?>" name="subscription_duration_type" /> <?php echo  SwpmUtils::_('No Expiry (Access for this level will not expire until cancelled)')?></p>                                
                <p><input type="radio" <?php echo  checked(SwpmMembershipLevel::DAYS,$subscription_duration_type,false)?> value="<?php echo  SwpmMembershipLevel::DAYS ?>" name="subscription_duration_type" /> <?php echo  SwpmUtils::_('Expire After')?> 
                    <input type="text" value="<?php echo  checked(SwpmMembershipLevel::DAYS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?php echo  SwpmMembershipLevel::DAYS ?>"> <?php echo  SwpmUtils::_('Days (Access expires after given number of days)')?></p>
                
                <p><input type="radio" <?php echo  checked(SwpmMembershipLevel::WEEKS,$subscription_duration_type,false)?> value="<?php echo  SwpmMembershipLevel::WEEKS?>" name="subscription_duration_type" /> <?php echo  SwpmUtils::_('Expire After')?> 
                    <input type="text" value="<?php echo  checked(SwpmMembershipLevel::WEEKS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?php echo  SwpmMembershipLevel::WEEKS ?>"> <?php echo  SwpmUtils::_('Weeks (Access expires after given number of weeks)')?></p>
                
                <p><input type="radio" <?php echo  checked(SwpmMembershipLevel::MONTHS,$subscription_duration_type,false)?> value="<?php echo  SwpmMembershipLevel::MONTHS?>" name="subscription_duration_type" /> <?php echo  SwpmUtils::_('Expire After')?> 
                    <input type="text" value="<?php echo  checked(SwpmMembershipLevel::MONTHS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?php echo  SwpmMembershipLevel::MONTHS?>"> <?php echo  SwpmUtils::_('Months (Access expires after given number of months)')?></p>
                
                <p><input type="radio" <?php echo  checked(SwpmMembershipLevel::YEARS,$subscription_duration_type,false)?> value="<?php echo  SwpmMembershipLevel::YEARS?>" name="subscription_duration_type" /> <?php echo  SwpmUtils::_('Expire After')?> 
                    <input type="text" value="<?php echo  checked(SwpmMembershipLevel::YEARS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?php echo  SwpmMembershipLevel::YEARS?>"> <?php echo  SwpmUtils::_('Years (Access expires after given number of years)')?></p>                
                
                <p><input type="radio" <?php echo  checked(SwpmMembershipLevel::FIXED_DATE,$subscription_duration_type,false)?> value="<?php echo  SwpmMembershipLevel::FIXED_DATE?>" name="subscription_duration_type" /> <?php echo  SwpmUtils::_('Fixed Date Expiry')?> 
                    <input type="text" class="swpm-date-picker" value="<?php echo  checked(SwpmMembershipLevel::FIXED_DATE,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?php echo  SwpmMembershipLevel::FIXED_DATE?>" id="subscription_period_<?php echo  SwpmMembershipLevel::FIXED_DATE?>"> <?php echo  SwpmUtils::_('(Access expires on a fixed date)')?></p>                                
        </td>        
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="role"><?php _e('Default Account Status', 'simple-membership'); ?></label></th>
        <td>
            <select name="default_account_status">
                <option value=""> <?php _e('Use global settings', 'simple-membership') ?> </option>
				<?php echo SwpmUtils::account_state_dropdown($default_account_status) ?>
            </select>
            <p class="description">
                <?php _e('Select the default account status for newly created members of this membership level. This option is useful if you want to manually approve members for certain membership levels. ', 'simple-membership'); ?>
                <?php echo '<a href="https://simple-membership-plugin.com/manually-approve-members-membership-site/" target="_blank">' . __('View Documentation', 'simple-membership') . '</a>.'; ?>
                <?php _e('Note: This setting has no effect if email activation is enabled.', 'simple-membership'); ?>
            </p>
        </td>
    </tr>    
    <tr>
        <th scope="row">
            <label for="email_activation"><?php echo  SwpmUtils::_('Email Activation'); ?></label>
        </th>
        <td>
            <input name="email_activation" type="checkbox" value="1" <?php echo $is_email_activation_conflicting ? 'disabled' : checked($email_activation) ;?>>
            <?php if ($is_email_activation_conflicting) { ?>
                <!-- Display a warning message if manual approval and email activation both enabled at the same time (they are mutually exclusive) -->
                <div class="swpm-yellow-box">
                    <p>
                        <b><?php _e( "Attention: ", 'simple-membership')?></b>
                        <?php _e( "Your current setting for the default account status is 'pending' as found in the", 'simple-membership');?> 
                        <a href="admin.php?page=simple_wp_membership_settings"><?php _e('general settings menu', 'simple-membership') ?></a> 
                        <?php _e( "This configuration conflicts with the email activation feature. To enable email activation, set the default account status back to the default setting of 'active'.", 'simple-membership');?>
                    </p>
                </div>
            <?php } ?>
            <p class="description">
                <?php echo  SwpmUtils::_('Enable new user activation via email. When enabled, members will need to click on an activation link that is sent to their email address to activate the account. Useful for free membership.');?>
                <?php echo '<a href="https://simple-membership-plugin.com/email-activation-for-members/" target="_blank">' . SwpmUtils::_('View Documentation') . '</a>.'; ?>
                <?php echo '<br><strong>'.SwpmUtils::_('Note:').'</strong> '.SwpmUtils::_('If enabled, decryptable member password is temporarily stored in the database until the account is activated.'); ?>
            </p>
            <br />
            <label for="after_activation_redirect_page"><?php echo SwpmUtils::_( 'After Email Activation Redirection Page (optional)' ); ?></label>
            <input class="regular-text" name="after_activation_redirect_page" type="text" value="<?php echo esc_url( $after_activation_redirect_page ); ?>">
            <p class="description">
                <?php echo SwpmUtils::_( 'This option can be used to redirect the users to a designated page after they click on the email activation link and activate the account.' ); ?>
            </p>
        </td>
	</tr>
    <?php echo apply_filters('swpm_admin_edit_membership_level_ui', '', $id); ?>
</tbody>
</table>
<?php //submit_button(SwpmUtils::_('Save Membership Level '), 'primary', 'editswpmlevel', true, array( 'id' => 'editswpmlevelsub' ) ); ?>
<p class="submit">
    <button type="submit" class="button-primary"><?php _e('Save Membership Level', 'simple-membership') ?></button>
    <input type="hidden" name="editswpmlevel" value="1">
</p>
</form>
</div>
<script>
    jQuery(document).ready(function ($) {
        $('.swpm-date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
            currentText: '<?php _e("Current Month", "simple-membership") ?>',
        });
    });
</script>
