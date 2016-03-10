<div class="swpm-edit-profile-form">
<form id="swpm-editprofile-form" name="swpm-editprofile-form" method="post" action="">
    <table>
        <tr class="swpm-profile-username-row">
            <td><label for="user_name"><?php echo  SwpmUtils::_('Username') ?></label></td>
            <td><?php echo  $user_name ?></td>
        </tr>
        <tr class="swpm-profile-email-row">
            <td><label for="email"><?php echo  SwpmUtils::_('Email')?></label></td>
            <td><input type="text" id="email" class="validate[required,custom[email],ajax[ajaxEmailCall]]" value="<?php echo $email;?>" size="50" name="email" /></td>
        </tr>
        <tr class="swpm-profile-password-row">
            <td><label for="password"><?php echo  SwpmUtils::_('Password')?></label></td>
            <td><input type="text" id="password" value=""  size="50" name="password" /></td>
        </tr>
        <tr class="swpm-profile-password-retype-row">
            <td><label for="password_re"><?php echo  SwpmUtils::_('Repeat Password')?></label></td>
            <td><input type="text" id="password_re" value="" size="50" name="password_re" /></td>
        </tr>
        <tr class="swpm-profile-firstname-row">
            <td><label for="first_name"><?php echo  SwpmUtils::_('First Name')?></label></td>
            <td><input type="text" id="first_name" value="<?php echo  $first_name; ?>" size="50" name="first_name" /></td>
        </tr>
        <tr class="swpm-profile-lastname-row">
            <td><label for="last_name"><?php echo  SwpmUtils::_('Last Name')?></label></td>
            <td><input type="text" id="last_name" value="<?php echo  $last_name; ?>" size="50" name="last_name" /></td>
        </tr>
        <tr class="swpm-profile-phone-row">
            <td><label for="phone"><?php echo  SwpmUtils::_('Phone')?></label></td>
            <td><input type="text" id="phone" value="<?php echo  $phone; ?>" size="50" name="phone" /></td>
        </tr>
        <tr class="swpm-profile-street-row">
            <td><label for="address_street"><?php echo  SwpmUtils::_('Street')?></label></td>
            <td><input type="text" id="address_street" value="<?php echo  $address_street; ?>" size="50" name="address_street" /></td>
        </tr>
        <tr class="swpm-profile-city-row">
            <td><label for="address_city"><?php echo  SwpmUtils::_('City')?></label></td>
            <td><input type="text" id="address_city" value="<?php echo  $address_city; ?>" size="50" name="address_city" /></td>
        </tr>
        <tr class="swpm-profile-state-row">
            <td><label for="address_state"><?php echo  SwpmUtils::_('State')?></label></td>
            <td><input type="text" id="address_state" value="<?php echo  $address_state; ?>" size="50" name="address_state" /></td>
        </tr>
        <tr class="swpm-profile-zipcode-row">
            <td><label for="address_zipcode"><?php echo  SwpmUtils::_('Zipcode')?></label></td>
            <td><input type="text" id="address_zipcode" value="<?php echo  $address_zipcode; ?>" size="50" name="address_zipcode" /></td>
        </tr>
        <tr class="swpm-profile-country-row">
            <td><label for="country"><?php echo  SwpmUtils::_('Country') ?></label></td>
            <td><input type="text" id="country" value="<?php echo  $country; ?>" size="50" name="country" /></td>
        </tr>
        <tr class="swpm-profile-membership-level-row">
            <td><label for="membership_level"><?php echo  SwpmUtils::_('Membership Level')?></label></td>
            <td>
                <?php echo  $membership_level_alias; ?>
            </td>
        </tr>
    </table>
    <p class="swpm-edit-profile-submit-section">
        <input type="submit" value="<?php echo SwpmUtils::_('Update')?>" class="swpm-edit-profile-submit" name="swpm_editprofile_submit" />
    </p>
    <?php echo SwpmUtils::delete_account_button(); ?>
    
    <input type="hidden" name="action" value="custom_posts" />

</form>
</div>
<script>
jQuery(document).ready(function($){
    $.validationEngineLanguage.allRules['ajaxEmailCall']['url']= '<?php echo admin_url('admin-ajax.php');?>';
    $.validationEngineLanguage.allRules['ajaxEmailCall']['extraData'] = '&action=swpm_validate_email&member_id=<?php echo SwpmAuth::get_instance()->get('member_id'); ?>';
    $("#swpm-editprofile-form").validationEngine('attach');
});
</script>