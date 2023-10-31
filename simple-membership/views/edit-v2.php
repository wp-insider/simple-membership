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
    <form id="<?php echo $form_id ?>" class="swpm-form" name="swpm-editprofile-form" method="post" action="">
        <?php wp_nonce_field('swpm_profile_edit_nonce_action', 'swpm_profile_edit_nonce_val') ?>
        <div>
            <?php apply_filters('swpm_edit_profile_form_before_username', ''); ?>
            <div class="swpm-form-row swpm-username-row" <?php apply_filters('swpm_edit_profile_form_username_tr_attributes', ''); ?>>
                <div><label for="user_name"><?php _e('Username', 'simple-membership'); ?></label></div>
                <div><?php echo $user_name ?></div>
            </div>
            <div class="swpm-form-row swpm-email-row">
                <div><label for="email"><?php _e('Email', 'simple-membership'); ?></label></div>
                <div><input type="text" id="email" name="email" autocomplete="off" class="swpm-form-field swpm-form-email" value="<?php echo $email; ?>" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-password-row">
                <div><label for="password"><?php _e('Password', 'simple-membership'); ?></label></div>
                <div><input type="password" id="password" value="" name="password" class="swpm-form-field swpm-form-password <?php echo $pass_class; ?>" autocomplete="off" placeholder="<?php _e('Leave empty to keep the current password', 'simple-membership'); ?>" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-repass-row">
                <div><label for="password_re"><?php _e('Repeat Password', 'simple-membership'); ?></label></div>
                <div><input type="password" id="password_re" value="" name="password_re" class="swpm-form-field swpm-form-repass" autocomplete="off" placeholder="<?php _e('Leave empty to keep the current password', 'simple-membership'); ?>" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-firstname-row" <?php apply_filters('swpm_edit_profile_form_firstname_tr_attributes', ''); ?>>
                <div><label for="first_name"><?php _e('First Name', 'simple-membership'); ?></label></div>
                <div><input type="text" id="first_name" value="<?php echo $first_name; ?>" name="first_name" class="swpm-form-field swpm-form-firstname" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-lastname-row" <?php apply_filters('swpm_edit_profile_form_lastname_tr_attributes', ''); ?>>
                <div><label for="last_name"><?php _e('Last Name', 'simple-membership'); ?></label></div>
                <div><input type="text" id="last_name" value="<?php echo $last_name; ?>" name="last_name" class="swpm-form-field swpm-form-lastname" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-phone-row" <?php apply_filters('swpm_edit_profile_form_phone_tr_attributes', ''); ?>>
                <div><label for="phone"><?php _e('Phone', 'simple-membership'); ?></label></div>
                <div><input type="text" id="phone" value="<?php echo $phone; ?>" name="phone" class="swpm-form-field swpm-form-phone" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-street-row" <?php apply_filters('swpm_edit_profile_form_street_tr_attributes', ''); ?>>
                <div><label for="address_street"><?php _e('Street', 'simple-membership'); ?></label></div>
                <div><input type="text" id="address_street" value="<?php echo $address_street; ?>" name="address_street" class="swpm-form-field swpm-form-street" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-city-row" <?php apply_filters('swpm_edit_profile_form_city_tr_attributes', ''); ?>>
                <div><label for="address_city"><?php _e('City', 'simple-membership'); ?></label></div>
                <div><input type="text" id="address_city" value="<?php echo $address_city; ?>" name="address_city" class="swpm-form-field swpm-form-city" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-state-row" <?php apply_filters('swpm_edit_profile_form_state_tr_attributes', ''); ?>>
                <div><label for="address_state"><?php _e('State', 'simple-membership'); ?></label></div>
                <div><input type="text" id="address_state" value="<?php echo $address_state; ?>" name="address_state" class="swpm-form-field swpm-form-state" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-zipcode-row" <?php apply_filters('swpm_edit_profile_form_zipcode_tr_attributes', ''); ?>>
                <div><label for="address_zipcode"><?php _e('Zipcode', 'simple-membership'); ?></label></div>
                <div><input type="text" id="address_zipcode" value="<?php echo $address_zipcode; ?>" name="address_zipcode" class="swpm-form-field swpm-form-zipcode" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-country-row" <?php apply_filters('swpm_edit_profile_form_country_tr_attributes', ''); ?>>
                <div><label for="country"><?php _e('Country', 'simple-membership'); ?></label></div>
                <div><select id="country" name="country" class="swpm-form-field swpm-profile-form-country"><?php echo SwpmMiscUtils::get_countries_dropdown($country) ?></select></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-company-row" <?php apply_filters('swpm_edit_profile_form_company_tr_attributes', ''); ?>>
                <div><label for="company_name"><?php _e('Company Name', 'simple-membership'); ?></label></div>
                <div><input type="text" id="company_name" value="<?php echo $company_name; ?>" name="company_name" class="swpm-form-field swpm-form-company" /></div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-membership-level-row" <?php apply_filters('swpm_edit_profile_form_membership_level_tr_attributes', ''); ?>>
                <div><label for="membership_level"><?php _e('Membership Level', 'simple-membership'); ?></label></div>
                <div>
                    <?php echo $membership_level_alias; ?>
                </div>
            </div>
            <?php apply_filters('swpm_edit_profile_form_after_membership_level', ''); ?>
        </div>
        <?php apply_filters('swpm_edit_profile_form_before_submit', ''); ?>

        <div class="swpm-form-row swpm-submit-section">
            <div>
                <button type="submit" class="swpm-submit"><?php _e('Update', 'simple-membership') ?></button>
                <input type="hidden" value="Update" name="swpm_editprofile_submit">
            </div>
        </div>

        <?php echo SwpmUtils::delete_account_button(); ?>

        <input type="hidden" name="action" value="custom_posts" />

    </form>

    <style>
        form.swpm-form .swpm-form-row {
            margin-bottom: 6px;
        }

        form.swpm-form .swpm-submit-section {
            margin-top: 12px;
        }

        form.swpm-form .swpm-form-row.error .swpm-form-field {
            border-color: #cc0000 !important;
            outline-color: #cc0000 !important;
        }

        form.swpm-form .swpm-form-row.error .swpm-form-desc {
            color: #cc0000 !important;
            font-size: smaller !important;
        }

        form.swpm-form .swpm-form-row.error .swpm-form-desc>ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 4px 0 0 !important;
        }
    </style>
</div>