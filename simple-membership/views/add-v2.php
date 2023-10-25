<?php
// SimpleWpMembership::enqueue_validation_scripts(array('ajaxEmailCall' => array('extraData' => '&action=swpm_validate_email&member_id=' . filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT))));

/**
 * TODO: Need to fix script enqueuing.
 */
wp_enqueue_script('swpm-reg-form-validator', SIMPLE_WP_MEMBERSHIP_URL . '/js/swpm.reg-form-validator.js', null, wp_rand(0, 100), true);

$settings = SwpmSettings::get_instance();
$force_strong_pass = $settings->get_value('force-strong-passwords');
if (!empty($force_strong_pass)) {
    $pass_class = apply_filters("swpm_registration_strong_pass_validation", "validate[required,custom[strongPass],minSize[8]]");
} else {
    $pass_class = "";
}
$terms_enabled = $settings->get_value('enable-terms-and-conditions');
$pp_enabled = $settings->get_value('enable-privacy-policy');
$strong_password_enabled = $settings->get_value("force-strong-passwords");
// Filter allowing to change the default value of user_name.
$user_name = apply_filters('swpm_registration_form_set_username', $user_name);

// Let javascript know that the fields are enabled and need to be validated.
$form_id = uniqid("swpm-registration-form-");
wp_add_inline_script("swpm-reg-form-validator", "var form_id = '$form_id';", "before");
$is_terms_enabled = empty($terms_enabled) ? 'false' : 'true';
wp_add_inline_script("swpm-reg-form-validator", "var terms_enabled = $is_terms_enabled;", "before");
$is_pp_enabled = empty($pp_enabled) ? 'false' : 'true';
wp_add_inline_script("swpm-reg-form-validator", "var pp_enabled = $is_pp_enabled;", "before");
$is_strong_password_enabled = empty($strong_password_enabled) ? 'false' : 'true';
wp_add_inline_script("swpm-reg-form-validator", "var strong_password_enabled = $is_strong_password_enabled;", "before");
$validation_messages = wp_json_encode(array(
    "username" => array(
        "required" => __("Username is required", "simple-membership"),
        "invalid" => __("Invalid username", "simple-membership"),
        "regex" => __("Usernames can only contain: letters, numbers and .-_*@", "simple-membership"),
        "minLength" => __("Minimum 4 characters required", "simple-membership"),
        "exists" => __("Username already exists", "simple-membership"),
    ),
    "email" => array(
        "required" => __("Email is required", "simple-membership"),
        "invalid" => __("Invalid email", "simple-membership"),
        "exists" => __("Email already exists", "simple-membership"),
    ),
    "password" => array(
        "required" => __("Password is required", "simple-membership"),
        "invalid" => __("Invalid password", "simple-membership"),
        "regex" => __("Must contain a digit, an uppercase and a lowercase letter", "simple-membership"),
        "minLength" => __("Minimum 8 characters required", "simple-membership")
    ),
    "repass" => array(
        "required" => __("Retype password is required", "simple-membership"),
        "invalid" => __("Invalid password", "simple-membership"),
        "mismatch" => __("Password don't match", "simple-membership"),
        "minLength" => __("Minimum 8 characters required", "simple-membership")
    ),
    "firstname" => array(
        "required" => __("First name is required", "simple-membership"),
        "invalid" => __("Invalid name", "simple-membership")
    ),
    "lastname" => array(
        "required" => __("Last name is required", "simple-membership"),
        "invalid" => __("Invalid name", "simple-membership")
    ),
    "terms" => array(
        "required" => __("You must accept the terms & conditions", "simple-membership")
    ),
    "pp" => array(
        "required" => __("You must accept the privacy policy", "simple-membership")
    )
));
wp_add_inline_script("swpm-reg-form-validator", "var validationMsg = $validation_messages", "before");

?>
<div class="swpm-registration-widget-form">
    <ul>
        <li><?php echo $strong_password_enabled; ?></li>
    </ul>
    <form id="<?php echo $form_id ?>" class="swpm-registration-form" name="swpm-registration-form" method="post" action="">
        <input type="hidden" name="level_identifier" value="<?php echo $level_identifier ?>" />
        <div>
            <div class="swpm-registration-form-row swpm-registration-username-row" <?php apply_filters('swpm_registration_form_username_tr_attributes', ''); ?>>
                <div><label for="user_name"><?php echo SwpmUtils::_('Username') ?></label></div>
                <div><input type="text" id="user_name" class="swpm-registration-form-field swpm-registration-form-username" value="<?php echo esc_attr($user_name); ?>" name="user_name" <?php apply_filters('swpm_registration_form_username_input_attributes', ''); ?> /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-email-row">
                <div><label for="email"><?php echo SwpmUtils::_('Email') ?></label></div>
                <div><input type="text" autocomplete="off" id="email" class="swpm-registration-form-field swpm-registration-form-email" value="<?php echo esc_attr($email); ?>" name="email" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-password-row">
                <div><label for="password"><?php echo SwpmUtils::_('Password') ?></label></div>
                <div><input type="password" autocomplete="off" id="password" class="swpm-registration-form-field swpm-registration-form-password <?php echo apply_filters('swpm_registration_input_pass_class', $pass_class); ?>" value="" name="password" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-repass-row">
                <div><label for="password_re"><?php echo SwpmUtils::_('Repeat Password') ?></label></div>
                <div><input type="password" autocomplete="off" id="password_re" class="swpm-registration-form-field swpm-registration-form-repass" value="" name="password_re" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-firstname-row" <?php apply_filters('swpm_registration_form_firstname_tr_attributes', ''); ?>>
                <div><label for="first_name"><?php echo SwpmUtils::_('First Name') ?></label></div>
                <div><input type="text" id="first_name" class="swpm-registration-form-field swpm-registration-form-firstname" value="<?php echo esc_attr($first_name); ?>" name="first_name" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-lastname-row" <?php apply_filters('swpm_registration_form_lastname_tr_attributes', ''); ?>>
                <div><label for="last_name"><?php echo SwpmUtils::_('Last Name') ?></label></div>
                <div><input type="text" id="last_name" class="swpm-registration-form-field swpm-registration-form-lastname" value="<?php echo esc_attr($last_name); ?>" name="last_name" /></div>
                <div class="swpm-registration-form-desc"></div>
            </div>
            <div class="swpm-registration-form-row swpm-registration-membership-level-row" <?php apply_filters('swpm_registration_form_membership_level_tr_attributes', ''); ?>>
                <div><label for="membership_level"><?php echo SwpmUtils::_('Membership Level') ?></label></div>
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
                    <input type="submit" value="<?php echo SwpmUtils::_('Register') ?>" class="swpm-registration-submit" name="swpm_registration_submit" />
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