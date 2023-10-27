<?php
$settings = SwpmSettings::get_instance();

$force_strong_pass = $settings->get_value('force-strong-passwords');
$terms_enabled = $settings->get_value('enable-terms-and-conditions');
$pp_enabled = $settings->get_value('enable-privacy-policy');

// Filter allowing to change the default value of user_name.
$user_name = apply_filters('swpm_registration_form_set_username', $user_name);

// $form_id = uniqid("swpm-registration-form-");
$form_id = "swpm-registration-form";

// Let javascript know that the fields are enabled and need to be validated.
$is_terms_enabled = empty($terms_enabled) ? 'false' : 'true';
$is_pp_enabled = empty($pp_enabled) ? 'false' : 'true';
$is_strong_password_enabled = empty($force_strong_pass) ? 'false' : 'true';

SimpleWpMembership::enqueue_validation_scripts_v2(
    array(
        'query_args' => array(
            'member_id' => filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT),
            'nonce' => wp_create_nonce('swpm-rego-form-ajax-nonce'),
        ),
        'form_id' => $form_id,
        'is_terms_enabled' => $is_terms_enabled,
        'is_pp_enabled' => $is_pp_enabled,
        'is_strong_password_enabled' => $is_strong_password_enabled,
    )
);

?>
<div class="swpm-registration-widget-form">
    <form id="<?php echo $form_id ?>" class="swpm-registration-form" name="swpm-registration-form" method="post" action="">
        <input type="hidden" name="level_identifier" value="<?php echo $level_identifier ?>" />
        <div>
            <div class="swpm-registration-form-row swpm-registration-username-row" <?php apply_filters('swpm_registration_form_username_tr_attributes', ''); ?>>
                <div><label for="user_name"><?php _e('Username', "simple-membership") ?></label></div>
                <div><input type="text" id="user_name" class="swpm-registration-form-field swpm-registration-form-username" value="<?php echo esc_attr($user_name); ?>" name="user_name" <?php apply_filters('swpm_registration_form_username_input_attributes', ''); ?> /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-email-row">
                <div><label for="email"><?php _e('Email', "simple-membership") ?></label></div>
                <div><input type="text" autocomplete="off" id="email" class="swpm-registration-form-field swpm-registration-form-email" value="<?php echo esc_attr($email); ?>" name="email" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-password-row">
                <div><label for="password"><?php _e('Password', "simple-membership") ?></label></div>
                <div><input type="password" autocomplete="off" id="password" class="swpm-registration-form-field swpm-registration-form-password <?php echo apply_filters('swpm_registration_input_pass_class', $pass_class); ?>" value="" name="password" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-repass-row">
                <div><label for="password_re"><?php _e('Repeat Password', "simple-membership") ?></label></div>
                <div><input type="password" autocomplete="off" id="password_re" class="swpm-registration-form-field swpm-registration-form-repass" value="" name="password_re" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-firstname-row" <?php apply_filters('swpm_registration_form_firstname_tr_attributes', ''); ?>>
                <div><label for="first_name"><?php echo _e('First Name', "simple-membership") ?></label></div>
                <div><input type="text" id="first_name" class="swpm-registration-form-field swpm-registration-form-firstname" value="<?php echo esc_attr($first_name); ?>" name="first_name" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-lastname-row" <?php apply_filters('swpm_registration_form_lastname_tr_attributes', ''); ?>>
                <div><label for="last_name"><?php echo _e('Last Name', "simple-membership") ?></label></div>
                <div><input type="text" id="last_name" class="swpm-registration-form-field swpm-registration-form-lastname" value="<?php echo esc_attr($last_name); ?>" name="last_name" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-membership-level-row" <?php apply_filters('swpm_registration_form_membership_level_tr_attributes', ''); ?>>
                <div><label for="membership_level"><?php _e('Membership Level', "simple-membership") ?></label></div>
                <div>
                    <?php
                    echo $membership_level_alias; //Show the level name in the form.
                    //Add the input fields for the level data.
                    echo '<input type="hidden" value="' . $membership_level . '" size="50" name="swpm_membership_level" id="membership_level" />';
                    //Add the level input verification data.
                    $swpm_p_key = get_option('swpm_private_key_one');
                    if (empty($swpm_p_key)) {
                        $swpm_p_key = uniqid('', true);
                        update_option('swpm_private_key_one', $swpm_p_key);
                    }
                    $swpm_level_hash = md5($swpm_p_key . '|' . $membership_level); //level hash
                    echo '<input type="hidden" name="swpm_level_hash" value="' . $swpm_level_hash . '" />';
                    ?>
                </div>
            </div>
            <?php
            apply_filters('swpm_registration_form_before_terms_and_conditions', '');

            // Check if we need to display Terms and Conditions checkbox.
            if (!empty($terms_enabled)) {
                $terms_page_url = $settings->get_value('terms-and-conditions-page-url');
            ?>
                <div class="swpm-registration-form-row swpm-registration-terms-row">
                    <div>
                        <label><input type="checkbox" id="swpm-accept-terms" name="accept_terms" class="swpm-registration-form-field swpm-registration-form-terms" value="1"> <?php echo SwpmUtils::_('I accept the ') ?> <a href="<?php echo $terms_page_url; ?>" target="_blank"><?php echo SwpmUtils::_('Terms and Conditions') ?></a></label>
                    </div>
                    <div class="swpm-registration-form-desc"></div>
                </div>
            <?php }
            // Check if we need to display Privacy Policy checkbox.
            if (!empty($pp_enabled)) {
                $pp_page_url = $settings->get_value('privacy-policy-page-url');
            ?>
                <div class="swpm-registration-form-row swpm-registration-pp-row">
                    <div>
                        <label><input type="checkbox" id="swpm-accept-pp" name="accept_pp" class="swpm-registration-form-field swpm-registration-form-pp" value="1"> <?php echo SwpmUtils::_('I agree to the ') ?> <a href="<?php echo $pp_page_url; ?>" target="_blank"><?php echo SwpmUtils::_('Privacy Policy') ?></a></label>
                    </div>
                    <div class="swpm-registration-form-desc"></div>
                </div>
            <?php } ?>

            <div class="swpm-before-registration-submit-section"><?php echo apply_filters('swpm_before_registration_submit_button', ''); ?></div>

            <div class="swpm-registration-form-row swpm-registration-submit-section">
                <div>
                    <button type="submit" class="swpm-registration-submit"><?php _e('Register', "simple-membership") ?></button>
                    <input type="hidden" name="swpm_registration_submit" value="Register">
                </div>
            </div>
        </div>

        <input type="hidden" name="action" value="custom_posts" />
    </form>

    <style>
        form.swpm-registration-form .swpm-registration-form-row {
            margin-bottom: 6px;
        }

        form.swpm-registration-form .swpm-registration-form-row>div>input[type="submit"] {
            margin-top: 12px;
        }

        form.swpm-registration-form .swpm-registration-form-row.error .swpm-registration-form-field {
            border-color: #cc0000 !important;
            outline-color: #cc0000 !important;
        }

        form.swpm-registration-form .swpm-registration-form-row.error .swpm-registration-form-desc {
            color: #cc0000 !important;
            font-size: smaller !important;
        }

        form.swpm-registration-form .swpm-registration-form-row.error .swpm-registration-form-desc>ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 4px 0 0 !important;
        }
    </style>
</div>