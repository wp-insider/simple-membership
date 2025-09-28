<?php
$auth = SwpmAuth::get_instance();
$user_data = (array) $auth->userData;
$user_data['membership_level_alias'] = $auth->get('alias');
extract($user_data, EXTR_SKIP);
$settings = SwpmSettings::get_instance();
$force_strong_pass = $settings->get_value('force-strong-passwords');

$custom_pass_pattern_validator = "";
$custom_pass_pattern_validator_msg = "";
$custom_pass_min_length_validator = null;
$custom_pass_min_length_validator_msg = "";

if (!empty($force_strong_pass)) {
    // Leaving the value empty will take the default strong password validation rule and its message.
    // filters for password pattern customization.
    $custom_pass_pattern_validator = apply_filters( "swpm_profile_pass_pattern_validation", "" );
    $custom_pass_pattern_validator_msg = apply_filters( "swpm_profile_pass_pattern_validation_msg", "" );
    // filters for password min length customization.
    $custom_pass_min_length_validator = apply_filters( "swpm_profile_pass_min_length_validation", null );
    $custom_pass_min_length_validator_msg = apply_filters( "swpm_profile_pass_min_length_validation_msg", "" );
}

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
        'custom_pass_pattern_validator' => $custom_pass_pattern_validator,
        'custom_pass_pattern_validator_msg' => $custom_pass_pattern_validator_msg,
        'custom_pass_min_length_validator' => $custom_pass_min_length_validator,
        'custom_pass_min_length_validator_msg' => $custom_pass_min_length_validator_msg,
    )
);
?>
<div class="swpm-edit-profile-form">
    <form id="<?php echo $form_id ?>" class="swpm-form" name="swpm-editprofile-form" method="post" action="">
        <?php wp_nonce_field('swpm_profile_edit_nonce_action', 'swpm_profile_edit_nonce_val') ?>
        <div class="swpm-edit-profile-form-inner">
            <?php echo apply_filters('swpm_edit_profile_form_before_username', ''); ?>
            <div class="swpm-form-row swpm-username-row" <?php echo apply_filters('swpm_edit_profile_form_username_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-username-label-wrap">
                    <label for="user_name"><?php _e('Username', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-username-input-wrap"><?php echo esc_attr($user_name) ?></div>
            </div>
            <div class="swpm-form-row swpm-email-row">
                <div class="swpm-form-label-wrap swpm-form-email-label-wrap">
                    <label for="email"><?php _e('Email', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-email-input-wrap">
                    <input type="text" id="email" name="email" autocomplete="off" class="swpm-form-field swpm-form-email" value="<?php echo esc_attr($email); ?>" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-password-row">
                <div class="swpm-form-label-wrap swpm-form-password-label-wrap">
                    <label for="password"><?php _e('Password', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-password-input-wrap">
                    <input type="password" id="password" value="" name="password" class="swpm-form-field swpm-form-password" autocomplete="new-password" placeholder="<?php _e('Leave empty to keep the current password', 'simple-membership'); ?>" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-repass-row">
                <div class="swpm-form-label-wrap swpm-form-repass-label-wrap">
                    <label for="password_re"><?php _e('Repeat Password', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-repass-input-wrap">
                    <input type="password" id="password_re" value="" name="password_re" class="swpm-form-field swpm-form-repass" autocomplete="new-password" placeholder="<?php _e('Leave empty to keep the current password', 'simple-membership'); ?>" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-firstname-row" <?php echo apply_filters('swpm_edit_profile_form_firstname_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-firstname-label-wrap">
                    <label for="first_name"><?php _e('First Name', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-firstname-input-wrap">
                    <input type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>" name="first_name" class="swpm-form-field swpm-form-firstname" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-lastname-row" <?php echo apply_filters('swpm_edit_profile_form_lastname_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-lastname-label-wrap">
                    <label for="last_name"><?php _e('Last Name', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-lastname-input-wrap">
                    <input type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>" name="last_name" class="swpm-form-field swpm-form-lastname" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-phone-row" <?php echo apply_filters('swpm_edit_profile_form_phone_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-phone-label-wrap">
                    <label for="phone"><?php _e('Phone', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-phone-input-wrap">
                    <input type="text" id="phone" value="<?php echo esc_attr($phone); ?>" name="phone" class="swpm-form-field swpm-form-phone" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-street-row" <?php echo apply_filters('swpm_edit_profile_form_street_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-street-label-wrap">
                    <label for="address_street"><?php _e('Street', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-street-input-wrap">
                    <input type="text" id="address_street" value="<?php echo esc_attr($address_street); ?>" name="address_street" class="swpm-form-field swpm-form-street" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-city-row" <?php echo apply_filters('swpm_edit_profile_form_city_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-city-label-wrap">
                    <label for="address_city"><?php _e('City', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-city-input-wrap">
                    <input type="text" id="address_city" value="<?php echo esc_attr($address_city); ?>" name="address_city" class="swpm-form-field swpm-form-city" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-state-row" <?php echo apply_filters('swpm_edit_profile_form_state_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-state-label-wrap">
                    <label for="address_state"><?php _e('State', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-state-input-wrap">
                    <input type="text" id="address_state" value="<?php echo esc_attr($address_state); ?>" name="address_state" class="swpm-form-field swpm-form-state" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-zipcode-row" <?php echo apply_filters('swpm_edit_profile_form_zipcode_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-zipcode-label-wrap">
                    <label for="address_zipcode"><?php _e('Zipcode', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-zipcode-input-wrap">
                    <input type="text" id="address_zipcode" value="<?php echo esc_attr($address_zipcode); ?>" name="address_zipcode" class="swpm-form-field swpm-form-zipcode" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-country-row" <?php echo apply_filters('swpm_edit_profile_form_country_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-country-label-wrap">
                    <label for="country"><?php _e('Country', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-country-input-wrap">
                    <select id="country" name="country" class="swpm-form-field swpm-profile-form-country"><?php echo SwpmMiscUtils::get_countries_dropdown($country) ?></select>
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-company-row" <?php echo apply_filters('swpm_edit_profile_form_company_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-company-label-wrap">
                    <label for="company_name"><?php _e('Company Name', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-company-input-wrap">
                    <input type="text" id="company_name" value="<?php echo esc_attr($company_name); ?>" name="company_name" class="swpm-form-field swpm-form-company" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-membership-level-row" <?php echo apply_filters('swpm_edit_profile_form_membership_level_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-membership-level-label-wrap">
                    <label for="membership_level"><?php _e('Membership Level', 'simple-membership'); ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-membership-level-input-wrap">
                    <?php echo esc_attr($membership_level_alias); ?>
                </div>
            </div>
            <?php echo apply_filters('swpm_edit_profile_form_after_membership_level', ''); ?>
        </div>
        <?php 
        // Trigger action hook. Can be used to add content to the registration form.
        do_action( 'swpm_before_profile_form_submit_section');

        // Trigger a filter hook.
        echo apply_filters('swpm_edit_profile_form_before_submit', '');
        ?>

        <div class="swpm-form-row swpm-submit-section swpm-edit-profile-submit-section">
            <button type="submit" class="swpm-submit swpm-profile-submit-button swpm-submit-btn-default-style"><?php _e('Update', 'simple-membership') ?></button>
            <input type="hidden" value="Update" name="swpm_editprofile_submit">
        </div>

        <?php echo SwpmUtils::delete_account_button(); ?>

        <input type="hidden" name="action" value="custom_posts" />

    </form>

    <style>
        .swpm-form .swpm-form-row {
            margin-bottom: 0.8rem;
        }
        .swpm-form .swpm-submit-section {
            margin-top: 1rem;
        }
        .swpm-form .swpm-form-row.error .swpm-form-field {
            border-color: #cc0000 !important;
            outline-color: #cc0000 !important;
        }
        .swpm-form .swpm-form-row.error .swpm-form-desc {
            color: #cc0000 !important;
            font-size: smaller !important;
        }
        .swpm-form .swpm-form-row.error .swpm-form-desc>ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 4px 0 0 !important;
        }
    </style>
</div>
