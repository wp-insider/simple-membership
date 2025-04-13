<?php
$settings = SwpmSettings::get_instance();

$force_strong_pass = $settings->get_value('force-strong-passwords');
$custom_pass_pattern_validator = "";
$custom_pass_pattern_validator_msg = "";
$custom_pass_min_length_validator = null;
$custom_pass_min_length_validator_msg = "";

if (!empty($force_strong_pass)) {
    // Leaving the value empty will take the default strong password validation rule and its message.
    // filters for password pattern customization.
    $custom_pass_pattern_validator = apply_filters( "swpm_reg_pass_pattern_validation", "" );
    $custom_pass_pattern_validator_msg = apply_filters( "swpm_reg_pass_pattern_validation_msg", "" );
    // filters for password min length customization.
    $custom_pass_min_length_validator = apply_filters( "swpm_reg_pass_min_length_validation", null );
    $custom_pass_min_length_validator_msg = apply_filters( "swpm_reg_pass_min_length_validation_msg", "" );
}

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
    'swpm-reg-form-validator',
    array(
        'query_args' => array(
            'member_id' => filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT),
            'nonce' => wp_create_nonce('swpm-rego-form-ajax-nonce'),
        ),
        'form_id' => $form_id,
        'is_terms_enabled' => $is_terms_enabled,
        'is_pp_enabled' => $is_pp_enabled,
        'is_strong_password_enabled' => $is_strong_password_enabled,
        'custom_pass_pattern_validator' => $custom_pass_pattern_validator,
        'custom_pass_pattern_validator_msg' => $custom_pass_pattern_validator_msg,
        'custom_pass_min_length_validator' => $custom_pass_min_length_validator,
        'custom_pass_min_length_validator_msg' => $custom_pass_min_length_validator_msg,
    )
);

?>
<div class="swpm-registration-widget-form">
    <form id="<?php echo $form_id ?>" class="swpm-form" name="swpm-registration-form" method="post" action="">
        <input type="hidden" name="level_identifier" value="<?php echo $level_identifier ?>" />
        <div class="swpm-registration-form-section">
            <div class="swpm-form-row swpm-username-row" <?php apply_filters('swpm_registration_form_username_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-username-label-wrap">
                    <label for="user_name"><?php _e('Username', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-username-input-wrap">
                    <input type="text" id="user_name" autocomplete="new-username" class="swpm-form-field swpm-form-username" value="<?php echo esc_attr($user_name); ?>" name="user_name" <?php apply_filters('swpm_registration_form_username_input_attributes', ''); ?> />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-email-row">
                <div class="swpm-form-label-wrap swpm-form-email-label-wrap">
                    <label for="email"><?php _e('Email', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-email-input-wrap">
                    <input type="text" autocomplete="off" id="email" class="swpm-form-field swpm-form-email" value="<?php echo esc_attr($email); ?>" name="email" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-password-row">
                <div class="swpm-form-label-wrap swpm-form-password-label-wrap">
                    <label for="password"><?php _e('Password', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-password-input-wrap">
                    <input type="password" autocomplete="new-password" id="password" class="swpm-form-field swpm-form-password" value="" name="password" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-repass-row">
                <div class="swpm-form-label-wrap swpm-form-repass-label-wrap">
                    <label for="password_re"><?php _e('Repeat Password', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-repass-input-wrap">
                    <input type="password" autocomplete="new-password" id="password_re" class="swpm-form-field swpm-form-repass" value="" name="password_re" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-firstname-row" <?php apply_filters('swpm_registration_form_firstname_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-firstname-label-wrap">
                    <label for="first_name"><?php echo _e('First Name', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-firstname-input-wrap">
                    <input type="text" id="first_name" class="swpm-form-field swpm-form-firstname" value="<?php echo esc_attr($first_name); ?>" name="first_name" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div class="swpm-form-row swpm-lastname-row" <?php apply_filters('swpm_registration_form_lastname_tr_attributes', ''); ?>>
                <div class="swpm-form-label-wrap swpm-form-lastname-label-wrap">
                    <label for="last_name"><?php echo _e('Last Name', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-input-wrap swpm-form-lastname-input-wrap">
                    <input type="text" id="last_name" class="swpm-form-field swpm-form-lastname" value="<?php echo esc_attr($last_name); ?>" name="last_name" />
                </div>
                <div class="swpm-form-desc"></div>
            </div>
            <div
                class="swpm-form-row swpm-membership-level-row"
                <?php apply_filters('swpm_registration_form_membership_level_tr_attributes', ''); ?>
                style="<?php echo !empty($hide_membership_level_field) ? 'display:none' : '' ?>"
            >
                <div class="swpm-form-label-wrap swpm-form-membership-level-label-wrap">
                    <label for="membership_level"><?php _e('Membership Level', "simple-membership") ?></label>
                </div>
                <div class="swpm-form-membership-level-value">
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
                <div class="swpm-form-row swpm-terms-row">
                    <div>
                        <label><input type="checkbox" id="swpm-accept-terms" name="accept_terms" class="swpm-form-field swpm-form-terms" value="1"> <?php _e('I accept the ', 'simple-membership') ?> <a href="<?php echo esc_url($terms_page_url); ?>" target="_blank"><?php _e('Terms and Conditions', 'simple-membership') ?></a></label>
                    </div>
                    <div class="swpm-form-desc"></div>
                </div>
            <?php 
            }
            // Check if we need to display Privacy Policy checkbox.
            if (!empty($pp_enabled)) {
                $pp_page_url = $settings->get_value('privacy-policy-page-url');
            ?>
                <div class="swpm-form-row swpm-pp-row">
                    <div>
                        <label><input type="checkbox" id="swpm-accept-pp" name="accept_pp" class="swpm-form-field swpm-form-pp" value="1"> <?php _e('I agree to the ', 'simple-membership') ?> <a href="<?php echo esc_url($pp_page_url); ?>" target="_blank"><?php _e('Privacy Policy', 'simple-membership') ?></a></label>
                    </div>
                    <div class="swpm-form-desc"></div>
                </div>
            <?php 
            } 
            ?>

            <?php 
            // Trigger action hook. Can be used to add content to the registration form.
            do_action( 'swpm_before_registration_submit_section');
            ?>
            <div class="swpm-before-registration-submit-section" align="center"><?php echo apply_filters('swpm_before_registration_submit_button', ''); ?></div>

            <div class="swpm-form-row swpm-submit-section swpm-registration-submit-section">
                <button type="submit" class="swpm-submit swpm-registration-submit-button swpm-submit-btn-default-style"><?php _e('Register', "simple-membership") ?></button>
                <input type="hidden" name="swpm_registration_submit" value="Register">
            </div>
        </div>

        <input type="hidden" name="action" value="custom_posts" />
    </form>

    <style>
        .swpm-registration-submit-section{
            text-align: center;
        }
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