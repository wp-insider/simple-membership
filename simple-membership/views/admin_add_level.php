<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$form_id = 'swpm-create-level';

SimpleWpMembership::enqueue_validation_scripts_v2(
	'swpm-membership-level-form-validator',
	array(
		'form_id' => $form_id,
	)
);

?>
<div class="wrap" id="swpm-level-page">
<form action="" method="post" name="swpm-create-level" id="<?php echo esc_attr($form_id) ?>" class="swpm-validate-form">
<input name="action" type="hidden" value="createlevel" />
<h3><?php echo esc_html__( 'Add Membership Level', 'simple-membership' ); ?></h3>
<p class="swpm-grey-box">
    <?php 
    echo esc_html__( 'Create new membership level.', 'simple-membership' );
    echo esc_html__( ' Refer to ', 'simple-membership' );
    echo '<a href="https://simple-membership-plugin.com/adding-membership-access-levels-site/" target="_blank">' . esc_html__( 'this documentation', 'simple-membership' ) . '</a>';
    echo esc_html__( ' to learn how a membership level works.', 'simple-membership' );
    ?>
</p>
<?php wp_nonce_field( 'create_swpmlevel_admin_end', '_wpnonce_create_swpmlevel_admin_end' ) ?>
<table class="form-table">
    <tbody>
	<tr>
            <th scope="row"><label for="alias"><?php echo  esc_html__( 'Membership Level Name', 'simple-membership' ); ?> <span class="description"><?php echo  esc_html__( '(required)', 'simple-membership' ); ?></span></label></th>
            <td><input class="regular-text" name="alias" type="text" id="alias" value="" aria-required="true" /></td>
	</tr>
	<tr class="form-field form-required">
            <th scope="row"><label for="role"><?php echo  esc_html__( 'Default WordPress Role', 'simple-membership' ); ?> <span class="description"><?php echo  esc_html__( '(required)', 'simple-membership' ); ?></span></label></th>
            <td><select  class="regular-text" name="role"><?php wp_dropdown_roles( 'subscriber' ); ?></select></td>
	</tr>
    <tr>
        <th scope="row"><label for="subscription_period"><?php echo  esc_html__( 'Access Duration', 'simple-membership' ); ?> <span class="description"><?php echo  esc_html__( '(required)', 'simple-membership' ); ?></span></label>
        </th>
        <td>
            <p><input type="radio" checked="checked" value="<?php echo esc_attr( SwpmMembershipLevel::NO_EXPIRY )?>" name="subscription_duration_type" /> <?php echo  esc_html__( 'No Expiry (Access for this level will not expire until cancelled', 'simple-membership' )?>)</p>
            <p><input type="radio" value="<?php echo esc_attr( SwpmMembershipLevel::DAYS )?>" name="subscription_duration_type" /> <?php echo  esc_html__( 'Expire After', 'simple-membership' )?>
                <input type="text" value="" name="subscription_period_<?php echo esc_attr( SwpmMembershipLevel::DAYS )?>"> <?php echo  esc_html__( 'Days (Access expires after given number of days)', 'simple-membership' )?></p>
            <p><input type="radio" value="<?php echo esc_attr( SwpmMembershipLevel::WEEKS )?>" name="subscription_duration_type" /> <?php echo  esc_html__( 'Expire After', 'simple-membership' )?>
                <input type="text" value="" name="subscription_period_<?php echo esc_attr( SwpmMembershipLevel::WEEKS )?>"> <?php echo  esc_html__( 'Weeks (Access expires after given number of weeks', 'simple-membership' )?></p>
            <p><input type="radio"  value="<?php echo esc_attr( SwpmMembershipLevel::MONTHS )?>" name="subscription_duration_type" /> <?php echo  esc_html__( 'Expire After', 'simple-membership' )?>
                <input type="text" value="" name="subscription_period_<?php echo esc_attr( SwpmMembershipLevel::MONTHS )?>"> <?php echo  esc_html__( 'Months (Access expires after given number of months)', 'simple-membership' )?></p>
            <p><input type="radio"  value="<?php echo esc_attr( SwpmMembershipLevel::YEARS )?>" name="subscription_duration_type" /> <?php echo  esc_html__( 'Expire After', 'simple-membership' )?>
                <input type="text" value="" name="subscription_period_<?php echo esc_attr( SwpmMembershipLevel::YEARS )?>"> <?php echo  esc_html__( 'Years (Access expires after given number of years)', 'simple-membership' )?></p>
            <p><input type="radio" value="<?php echo esc_attr( SwpmMembershipLevel::FIXED_DATE )?>" name="subscription_duration_type" /> <?php echo  esc_html__( 'Fixed Date Expiry', 'simple-membership' )?>
                <input type="text" class="swpm-date-picker" value="<?php echo esc_attr( gmdate('Y-m-d') );?>" name="subscription_period_<?php echo esc_attr( SwpmMembershipLevel::FIXED_DATE )?>"> <?php echo  esc_html__( '(Access expires on a fixed date)', 'simple-membership' )?></p>
            <p><input type="radio" value="<?php echo esc_attr( SwpmMembershipLevel::ANNUAL_FIXED_DATE )?>" name="subscription_duration_type" /> <?php esc_attr_e('Annual Expiration Date','simple-membership')?>
                <?php SwpmMiscUtils::month_day_selector(); ?>
	            <?php printf(
                /* translators: %s: number input for the minimum membership duration in days. */
                esc_html__( ' with a minimum period of %s days', 'simple-membership' ), '<span><input name="annual_fixed_date_min_period" type="number" min="0" value="" style="width: 60px;"></span>')?>
	            <?php esc_attr_e( '(Memberships will expire on this date every year. Example value: December 31 for calendar-year memberships or June 30 for fiscal alignments). ', 'simple-membership' ); ?>
                <?php echo '<a href="https://simple-membership-plugin.com/annual-calendar-or-fiscal-year-memberships/" target="_blank">' . esc_html__( 'View Documentation', 'simple-membership' ) . '</a>.'; ?>
            </p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="role"><?php esc_attr_e('Default Account Status', 'simple-membership'); ?></label></th>
        <td>
            <select name="default_account_status">
                <option value=""> <?php esc_attr_e('Use global settings', 'simple-membership') ?> </option>
                <?php echo wp_kses( SwpmUtils::account_state_dropdown(''), SwpmKsesUtils::wp_kses_select_option_tags() ) ?>
            </select>
            <p class="description">
                <?php esc_attr_e('Select the default account status for newly created members of this membership level. This option is useful if you want to manually approve members for certain membership levels. ', 'simple-membership'); ?>
                <?php echo '<a href="https://simple-membership-plugin.com/manually-approve-members-membership-site/" target="_blank">' . esc_html__( 'View Documentation', 'simple-membership' ) . '</a>.'; ?>
                <?php esc_attr_e('Note: This setting has no effect if email activation is enabled.', 'simple-membership'); ?>
            </p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="role_after_expiry"><?php esc_html_e( 'WordPress Role After Expiry', 'simple-membership' ); ?> <span class="description"><?php esc_html_e( '(optional)', 'simple-membership' ); ?></span></label></th>
        <td>
            <select name="role_after_expiry" id="role_after_expiry">
                <option value=""><?php esc_html_e( 'No change (keep current role)', 'simple-membership' ); ?></option>
                <?php
                foreach ( SwpmMemberUtils::get_roles_for_role_after_expiry() as $swpm_role_key => $swpm_role_name ) {
                    echo '<option value="' . esc_attr( $swpm_role_key ) . '">' . esc_html( translate_user_role( $swpm_role_name ) ) . '</option>';
                }
                ?>
            </select>
            <p class="description">
                <?php esc_html_e( 'Optional. When a member of this level expires, their WordPress role is changed to the role selected here. When the member makes a new payment (renewal or subscription payment), or an admin reactivates the account, the level\'s Default WordPress Role is applied again. Leave this set to "No change" to keep the current behaviour.', 'simple-membership' ); ?>
                <?php echo '<a href="https://simple-membership-plugin.com/wordpress-roles-for-members-assign-and-change-roles-on-expiry/" target="_blank">' . esc_html__( 'View Documentation', 'simple-membership' ) . '</a>.'; ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="email_activation"><?php echo  esc_html__( 'Email Activation', 'simple-membership' ); ?></label>
        </th>
        <td>
            <input name="email_activation" type="checkbox" value="1">
            <p class="description">
                <?php echo esc_html__( 'Enable new user activation via email. When enabled, members will need to click on an activation link that is sent to their email address to activate the account. Useful for free membership. ', 'simple-membership' ); ?>
                <?php echo '<a href="https://simple-membership-plugin.com/email-activation-for-members/" target="_blank">' . esc_html__( 'View Documentation', 'simple-membership' ) . '</a>.'; ?>
                <?php echo '<br><strong>'.esc_html__( 'Note:', 'simple-membership' ).'</strong> '.esc_html__( 'If enabled, the member\'s decryptable password is temporarily stored in the database until the account is activated.', 'simple-membership' ); ?>
            </p>
            <br />
            <label for="after_activation_redirect_page"><?php echo esc_html__( 'After Email Activation Redirection Page (optional)', 'simple-membership' ); ?></label>
            <input class="regular-text" name="after_activation_redirect_page" type="text" value="">
            <p class="description">
                <?php echo esc_html__( 'This option can be used to redirect the users to a designated page after they click on the email activation link and activate the account.', 'simple-membership' ); ?>
            </p>                
        </td>
	</tr>
    <?php echo  apply_filters('swpm_admin_add_membership_level_ui', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>
</tbody>
</table>
<p class="submit">
    <button type="submit" class="button-primary"><?php esc_attr_e('Add New Membership Level', 'simple-membership') ?></button>
    <input type="hidden" name="createswpmlevel" value="1">
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
            currentText: '<?php esc_attr_e("Current Month", "simple-membership") ?>',
        });
    });
</script>
