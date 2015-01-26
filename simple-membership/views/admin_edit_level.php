<div class="wrap" id="swpm-level-page">
<form action="" method="post" name="swpm-edit-level" id="swpm-edit-level" class="validate"<?php do_action('level_edit_form_tag');?>>
<input name="action" type="hidden" value="editlevel" />
<?php wp_nonce_field( 'edit-swpmlevel', '_wpnonce_edit-swpmlevel' ) ?>
<h3><?= BUtils::_('Edit membership level'); ?></h3>
<p><?= BUtils::_('Edit membership level.'); ?></p>
<table class="form-table">
    <tbody>
	<tr>
		<th scope="row"><label for="alias"><?= BUtils::_('Membership Level Name'); ?> <span class="description"><?= BUtils::_('(required)'); ?></span></label></th>
		<td><input class="regular-text validate[required]" name="alias" type="text" id="alias" value="<?php echo stripslashes($alias);?>" aria-required="true" /></td>
	</tr>
	<tr class="form-field form-required">
		<th scope="row"><label for="role"><?= BUtils::_('Default WordPress Role'); ?> <span class="description"><?= BUtils::_('(required)'); ?></span></label></th>
		<td><select  class="regular-text" name="role"><?php wp_dropdown_roles( $role ); ?></select></td>
	</tr>
    <tr>
        <th scope="row"><label for="subscription_period"><?= BUtils::_('Subscription Duration'); ?> <span class="description"><?= BUtils::_('(required)'); ?></span></label>
        </th>
        <td>
                <p><input type="radio" <?= checked(BMembershipLevel::NO_EXPIRY,$subscription_duration_type,false)?> value="<?= BMembershipLevel::NO_EXPIRY?>" name="subscription_duration_type" /> No Expiry (Access for this level will not expire until cancelled)</p>                                
                <p><input type="radio" <?= checked(BMembershipLevel::DAYS,$subscription_duration_type,false)?> value="<?= BMembershipLevel::DAYS ?>" name="subscription_duration_type" /> Expire After 
                    <input type="text" value="<?= checked(BMembershipLevel::DAYS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?= BMembershipLevel::DAYS ?>"> Days (Access expires after given number of days)</p>
                
                <p><input type="radio" <?= checked(BMembershipLevel::WEEKS,$subscription_duration_type,false)?> value="<?= BMembershipLevel::WEEKS?>" name="subscription_duration_type" /> Expire After 
                    <input type="text" value="<?= checked(BMembershipLevel::WEEKS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?= BMembershipLevel::WEEKS ?>"> Weeks (Access expires after given number of weeks)</p>
                
                <p><input type="radio" <?= checked(BMembershipLevel::MONTHS,$subscription_duration_type,false)?> value="<?= BMembershipLevel::MONTHS?>" name="subscription_duration_type" /> Expire After 
                    <input type="text" value="<?= checked(BMembershipLevel::MONTHS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?= BMembershipLevel::MONTHS?>"> Months (Access expires after given number of months)</p>
                
                <p><input type="radio" <?= checked(BMembershipLevel::YEARS,$subscription_duration_type,false)?> value="<?= BMembershipLevel::YEARS?>" name="subscription_duration_type" /> Expire After 
                    <input type="text" value="<?= checked(BMembershipLevel::YEARS,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?= BMembershipLevel::YEARS?>"> Years (Access expires after given number of years)</p>                
                
                <p><input type="radio" <?= checked(BMembershipLevel::FIXED_DATE,$subscription_duration_type,false)?> value="<?= BMembershipLevel::FIXED_DATE?>" name="subscription_duration_type" /> Fixed Date Expiry 
                    <input type="text" class="swpm-date-picker" value="<?= checked(BMembershipLevel::FIXED_DATE,$subscription_duration_type,false)? $subscription_period: "";?>" name="subscription_period_<?= BMembershipLevel::FIXED_DATE?>" id="subscription_period_<?= BMembershipLevel::FIXED_DATE?>"> (Access expires on a fixed date)</p>                                
        </td>        
    </tr>
    <?php if(function_exists('swpm_protect_older_posts_addon')){ ?>
    <tr class="form-field">
        <th scope="row"><label for="role"><?= BUtils::_('Protect Older Posts (optional)'); ?></span></label></th>
        <td>
            <input type="checkbox" <?= empty($protect_older_posts)? "": "checked='checked'"?>  value="1"  name="protect_older_posts" id="protect_older_posts" />
            <p class="description">Only allow access to protected posts published after the members's join date.</p>
        </td>
    </tr>
    <?php } ?>
    
    <?= apply_filters('swpm_admin_edit_membership_level_ui', '', $id);?>
</tbody>
</table>
<?php submit_button(BUtils::_('Edit Membership Level '), 'primary', 'editswpmlevel', true, array( 'id' => 'editswpmlevelsub' ) ); ?>
</form>
</div>
<script>
jQuery(document).ready(function($){
    $('.swpm-date-picker').dateinput({'format':'yyyy-mm-dd',selectors: true,yearRange:[-100,100]});
    $("#swpm-edit-level").validationEngine('attach');
});
</script>
