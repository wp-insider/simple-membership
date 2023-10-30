<?php
$auth = SwpmAuth::get_instance();
$user_data = (array) $auth->userData;
$user_data['membership_level_alias'] = $auth->get('alias');
extract($user_data, EXTR_SKIP);
$settings = SwpmSettings::get_instance();
$force_strong_pass = $settings->get_value('force-strong-passwords');

if (!empty($force_strong_pass)) {
    $pass_class = apply_filters("swpm_profile_strong_pass_validation", "validate[custom[strongPass],minSize[8]]");
} else {
    $pass_class = "";
}

// $form_id = uniqid("swpm-registration-form-");
$form_id = "swpm-profile-form";
$is_strong_password_enabled = empty($force_strong_pass) ? 'false' : 'true';
SimpleWpMembership::enqueue_validation_scripts_v2(
    "swpm-profile-form-validator",
    array(
        'query_args' => array(
            'member_id' => filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT),
            'nonce' => wp_create_nonce('swpm-rego-form-ajax-nonce'),
        ),
        'form_id' => $form_id,
        'is_strong_password_enabled' => $is_strong_password_enabled,
    )
);
//The admin ajax causes an issue with the JS validation if done on form submission. The edit profile doesn't need JS validation on email. There is PHP validation which will catch any email error.
//SimpleWpMembership::enqueue_validation_scripts(array('ajaxEmailCall' => array('extraData'=>'&action=swpm_validate_email&member_id='.SwpmAuth::get_instance()->get('member_id'))));

?>
<div class="swpm-edit-profile-form">
    <form id="<?php echo $form_id ?>" name="swpm-editprofile-form" method="post" action="" class="swpm-profile-form swpm-validate-form">
        <?php wp_nonce_field('swpm_profile_edit_nonce_action', 'swpm_profile_edit_nonce_val') ?>
        <div>
            <?php apply_filters('swpm_edit_profile_form_before_username', ''); ?>
            <div class="swpm-profile-form-row swpm-profile-username-row" <?php apply_filters('swpm_edit_profile_form_username_tr_attributes', ''); ?>>
                <div><label for="user_name"><?php echo SwpmUtils::_('Username'); ?></label></div>
                <div><?php echo $user_name ?></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-email-row">
                <div><label for="email"><?php echo SwpmUtils::_('Email'); ?></label></div>
                <div><input type="text" id="email" name="email" autocomplete="off" class="swpm-profile-form-field swpm-profile-form-email" value="<?php echo $email; ?>" /></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-password-row">
                <div><label for="password"><?php echo SwpmUtils::_('Password'); ?></label></div>
                <div><input type="password" id="password" value="" name="password" class="swpm-profile-form-field swpm-profile-form-password <?php echo $pass_class; ?>" autocomplete="off" placeholder="<?php _e('Leave empty to keep the current password', 'simple-membership'); ?>" /></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-repass-row">
                <div><label for="password_re"><?php echo SwpmUtils::_('Repeat Password'); ?></label></div>
                <div><input type="password" id="password_re" value="" name="password_re" class="swpm-profile-form-field swpm-profile-form-repass" autocomplete="off" placeholder="<?php _e('Leave empty to keep the current password', 'simple-membership'); ?>" /></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-firstname-row" <?php apply_filters('swpm_edit_profile_form_firstname_tr_attributes', ''); ?>>
                <div><label for="first_name"><?php echo SwpmUtils::_('First Name'); ?></label></div>
                <div><input type="text" id="first_name" value="<?php echo $first_name; ?>" name="first_name" class="swpm-profile-form-field swpm-profile-form-firstname" /></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-lastname-row" <?php apply_filters('swpm_edit_profile_form_lastname_tr_attributes', ''); ?>>
                <div><label for="last_name"><?php echo SwpmUtils::_('Last Name'); ?></label></div>
                <div><input type="text" id="last_name" value="<?php echo $last_name; ?>" name="last_name" class="swpm-profile-form-field swpm-profile-form-lastname"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-phone-row" <?php apply_filters('swpm_edit_profile_form_phone_tr_attributes', ''); ?>>
                <div><label for="phone"><?php echo SwpmUtils::_('Phone'); ?></label></div>
                <div><input type="text" id="phone" value="<?php echo $phone; ?>" name="phone" class="swpm-profile-form-field swpm-profile-form-phone"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-street-row" <?php apply_filters('swpm_edit_profile_form_street_tr_attributes', ''); ?>>
                <div><label for="address_street"><?php echo SwpmUtils::_('Street'); ?></label></div>
                <div><input type="text" id="address_street" value="<?php echo $address_street; ?>" name="address_street" class="swpm-profile-form-field swpm-profile-form-street"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-city-row" <?php apply_filters('swpm_edit_profile_form_city_tr_attributes', ''); ?>>
                <div><label for="address_city"><?php echo SwpmUtils::_('City'); ?></label></div>
                <div><input type="text" id="address_city" value="<?php echo $address_city; ?>" name="address_city" class="swpm-profile-form-field swpm-profile-form-city"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-state-row" <?php apply_filters('swpm_edit_profile_form_state_tr_attributes', ''); ?>>
                <div><label for="address_state"><?php echo SwpmUtils::_('State'); ?></label></div>
                <div><input type="text" id="address_state" value="<?php echo $address_state; ?>" name="address_state" class="swpm-profile-form-field swpm-profile-form-state"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-zipcode-row" <?php apply_filters('swpm_edit_profile_form_zipcode_tr_attributes', ''); ?>>
                <div><label for="address_zipcode"><?php echo SwpmUtils::_('Zipcode'); ?></label></div>
                <div><input type="text" id="address_zipcode" value="<?php echo $address_zipcode; ?>" name="address_zipcode" class="swpm-profile-form-field swpm-profile-form-zipcode"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-country-row" <?php apply_filters('swpm_edit_profile_form_country_tr_attributes', ''); ?>>
                <div><label for="country"><?php echo SwpmUtils::_('Country'); ?></label></div>
                <div><select id="country" name="country" class="swpm-profile-form-field swpm-profile-form-country"><?php echo SwpmMiscUtils::get_countries_dropdown($country) ?></select></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-company-row" <?php apply_filters('swpm_edit_profile_form_company_tr_attributes', ''); ?>>
                <div><label for="company_name"><?php echo SwpmUtils::_('Company Name'); ?></label></div>
                <div><input type="text" id="company_name" value="<?php echo $company_name; ?>" name="company_name" class="swpm-profile-form-field swpm-profile-form-company"/></div>
                <div class="swpm-profile-form-desc"></div>
            </div>
            <div class="swpm-profile-form-row swpm-profile-membership-level-row" <?php apply_filters('swpm_edit_profile_form_membership_level_tr_attributes', ''); ?>>
                <div><label for="membership_level"><?php echo SwpmUtils::_('Membership Level'); ?></label></div>
                <div>
                    <?php echo $membership_level_alias; ?>
                </div>
            </div>
            <?php apply_filters('swpm_edit_profile_form_after_membership_level', ''); ?>
        </div>
        <?php apply_filters('swpm_edit_profile_form_before_submit', ''); ?>

        <div class="swpm-profile-form-row swpm-profile-submit-section">
            <div>
                <button type="submit" class="swpm-profile-submit"><?php _e('Update', 'simple-membership') ?></button>
                <input type="hidden" value="Update" name="swpm_editprofile_submit">
            </div>
        </div>

        <?php echo SwpmUtils::delete_account_button(); ?>

        <input type="hidden" name="action" value="custom_posts" />

    </form>

    <style>
        form.swpm-profile-form .swpm-profile-form-row {
            margin-bottom: 6px;
        }

        form.swpm-profile-form .swpm-profile-submit-section {
            margin-top: 12px;
        }

        form.swpm-profile-form .swpm-profile-form-row.error .swpm-profile-form-field {
            border-color: #cc0000 !important;
            outline-color: #cc0000 !important;
        }

        form.swpm-profile-form .swpm-profile-form-row.error .swpm-profile-form-desc {
            color: #cc0000 !important;
            font-size: smaller !important;
        }

        form.swpm-profile-form .swpm-profile-form-row.error .swpm-profile-form-desc>ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 4px 0 0 !important;
        }
    </style>
</div>
