<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$auth = SwpmAuth::get_instance();
$user_data = (array) $auth->userData;
$user_data['membership_level_alias'] = $auth->get('alias');
extract($user_data, EXTR_SKIP);
$settings=SwpmSettings::get_instance();
$force_strong_pass=$settings->get_value('force-strong-passwords');
if (!empty($force_strong_pass)) {
    $pass_class = apply_filters( "swpm_profile_strong_pass_validation", "validate[custom[strongPass],minSize[8]]" );
} else {
    $pass_class="";
}
SimpleWpMembership::enqueue_validation_scripts();
//The admin ajax causes an issue with the JS validation if done on form submission. The edit profile doesn't need JS validation on email. There is PHP validation which will catch any email error.
//SimpleWpMembership::enqueue_validation_scripts(array('ajaxEmailCall' => array('extraData'=>'&action=swpm_validate_email&member_id='.SwpmAuth::get_instance()->get('member_id'))));
?>
<div class="swpm-edit-profile-form">
    <form id="swpm-editprofile-form" name="swpm-editprofile-form" method="post" action="" class="swpm-validate-form">
        <?php wp_nonce_field('swpm_profile_edit_nonce_action', 'swpm_profile_edit_nonce_val') ?>
        <table>
            <?php echo apply_filters('swpm_edit_profile_form_before_username', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>
            <tr class="swpm-profile-username-row" <?php echo apply_filters('swpm_edit_profile_form_username_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="user_name"><?php esc_attr_e('Username', 'simple-membership'); ?></label></td>
                <td><?php echo esc_attr($user_name) ?></td>
            </tr>
            <tr class="swpm-profile-email-row">
                <td><label for="email"><?php esc_attr_e('Email', 'simple-membership'); ?></label></td>
                <td><input type="text" id="email" name="email" size="50" autocomplete="off" class="" value="<?php echo esc_attr($email); ?>" /></td>
            </tr>
            <tr class="swpm-profile-password-row">
                <td><label for="password"><?php esc_attr_e('Password', 'simple-membership'); ?></label></td>
                <td><input type="password" id="password" value="" size="50" name="password" class="<?php echo esc_attr( $pass_class );?>" autocomplete="off" placeholder="<?php esc_attr_e('Leave empty to keep the current password', 'simple-membership'); ?>" /></td>
            </tr>
            <tr class="swpm-profile-password-retype-row">
                <td><label for="password_re"><?php esc_attr_e('Repeat Password', 'simple-membership'); ?></label></td>
                <td><input type="password" id="password_re" value="" size="50" name="password_re" autocomplete="off" placeholder="<?php esc_attr_e('Leave empty to keep the current password', 'simple-membership'); ?>" /></td>
            </tr>
            <tr class="swpm-profile-firstname-row" <?php echo apply_filters('swpm_edit_profile_form_firstname_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="first_name"><?php esc_attr_e('First Name', 'simple-membership'); ?></label></td>
                <td><input type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>" size="50" name="first_name" /></td>
            </tr>
            <tr class="swpm-profile-lastname-row" <?php echo apply_filters('swpm_edit_profile_form_lastname_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="last_name"><?php esc_attr_e('Last Name', 'simple-membership'); ?></label></td>
                <td><input type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>" size="50" name="last_name" /></td>
            </tr>
            <tr class="swpm-profile-phone-row" <?php echo apply_filters('swpm_edit_profile_form_phone_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="phone"><?php esc_attr_e('Phone', 'simple-membership'); ?></label></td>
                <td><input type="text" id="phone" value="<?php echo esc_attr($phone); ?>" size="50" name="phone" /></td>
            </tr>
            <tr class="swpm-profile-street-row" <?php echo apply_filters('swpm_edit_profile_form_street_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="address_street"><?php esc_attr_e('Street', 'simple-membership'); ?></label></td>
                <td><input type="text" id="address_street" value="<?php echo esc_attr($address_street); ?>" size="50" name="address_street" /></td>
            </tr>
            <tr class="swpm-profile-city-row" <?php echo apply_filters('swpm_edit_profile_form_city_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="address_city"><?php esc_attr_e('City', 'simple-membership'); ?></label></td>
                <td><input type="text" id="address_city" value="<?php echo esc_attr($address_city); ?>" size="50" name="address_city" /></td>
            </tr>
            <tr class="swpm-profile-state-row" <?php echo apply_filters('swpm_edit_profile_form_state_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="address_state"><?php esc_attr_e('State', 'simple-membership'); ?></label></td>
                <td><input type="text" id="address_state" value="<?php echo esc_attr($address_state); ?>" size="50" name="address_state" /></td>
            </tr>
            <tr class="swpm-profile-zipcode-row" <?php echo apply_filters('swpm_edit_profile_form_zipcode_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="address_zipcode"><?php esc_attr_e('Zipcode', 'simple-membership'); ?></label></td>
                <td><input type="text" id="address_zipcode" value="<?php echo esc_attr($address_zipcode); ?>" size="50" name="address_zipcode" /></td>
            </tr>
            <tr class="swpm-profile-country-row" <?php echo apply_filters('swpm_edit_profile_form_country_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="country"><?php esc_attr_e('Country', 'simple-membership'); ?></label></td>
                <td><select id="country" name="country"><?php echo wp_kses( SwpmMiscUtils::get_countries_dropdown($country), SwpmKsesUtils::wp_kses_post_tags_with_form() ) ?></select></td>
            </tr>
            <tr class="swpm-profile-company-row" <?php echo apply_filters('swpm_edit_profile_form_company_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="company_name"><?php esc_attr_e('Company Name', 'simple-membership'); ?></label></td>
                <td><input type="text" id="company_name" value="<?php echo esc_attr($company_name); ?>" size="50" name="company_name" /></td>
            </tr>            
            <tr class="swpm-profile-membership-level-row" <?php echo apply_filters('swpm_edit_profile_form_membership_level_tr_attributes', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>>
                <td><label for="membership_level"><?php esc_attr_e('Membership Level', 'simple-membership'); ?></label></td>
                <td>
                    <?php echo esc_attr($membership_level_alias); ?>
                </td>
            </tr>
            <?php echo apply_filters('swpm_edit_profile_form_after_membership_level', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>
        </table>
        <?php echo apply_filters('swpm_edit_profile_form_before_submit', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>
        <p class="swpm-edit-profile-submit-section">
            <input type="submit" value="<?php esc_attr_e('Update', 'simple-membership') ?>" class="swpm-edit-profile-submit" name="swpm_editprofile_submit" />
        </p>
        <?php echo SwpmUtils::delete_account_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped or trusted HTML built internally or by filters. ?>

        <input type="hidden" name="action" value="custom_posts" />

    </form>
</div>
