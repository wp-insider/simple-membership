<?php

// $form_id = uniqid("swpm-registration-form-");
$form_id = "swpm-create-user";

SimpleWpMembership::enqueue_validation_scripts_v2(
    'swpm-reg-form-validator',
    array(
        'query_args' => array(
            'member_id' => filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT),
            'nonce' => wp_create_nonce('swpm-rego-form-ajax-nonce'),
        ),
        'form_id' => $form_id,
    )
);

?>
<div class="wrap" id="swpm-profile-page" type="add">
    <form action="" method="post" name="swpm-create-user" id="<?php echo $form_id ?>" class="swpm-form" <?php do_action('user_new_form_tag'); ?>>
        <input name="action" type="hidden" value="createuser" />
        <?php wp_nonce_field('create_swpmuser_admin_end', '_wpnonce_create_swpmuser_admin_end') ?>
        <h3><?php _e('Add Member', 'simple-membership') ?></h3>
        <p><?php _e('Create a brand new user and add it to this site.', 'simple-membership'); ?></p>
        <table class="form-table">
            <tbody>
                <tr class="swpm-form-row swpm-username-row">
                    <th scope="row"><label for="user_name"><?php _e('Username', 'simple-membership'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
                    <td>
                        <input class="swpm-form-field swpm-form-username regular-text" name="user_name" autocomplete="new-username" type="text" id="user_name" value="<?php echo esc_attr(stripslashes($user_name)); ?>" aria-required="true" />
                        <div class="swpm-form-desc"></div>
                    </td>
                </tr>
                <tr class="swpm-form-row swpm-email-row">
                    <th scope="row"><label for="email"><?php _e('E-mail', 'simple-membership'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
                    <td>
                        <input class="swpm-form-field swpm-form-email regular-text" name="email" autocomplete="off" type="text" id="email" value="<?php echo esc_attr($email); ?>" />
                        <div class="swpm-form-desc"></div>
                    </td>
                </tr>
                <tr class="swpm-form-row swpm-password-row">
                    <th scope="row"><label for="password"><?php _e('Password', 'simple-membership'); ?> <span class="description"><?php _e('(twice, required)', 'simple-membership'); ?></span></label></th>
                    <td>
                        <input class="swpm-form-field swpm-form-password regular-text" name="password" type="password" id="pass1" autocomplete="new-password" />
                        <div class="swpm-form-desc"></div>
                    </td>
                </tr>
                <tr class="swpm-form-row swpm-repass-row">
                    <th scope="row"></th>
                    <td style="padding-top: 0;">
                        <input class="swpm-form-field swpm-form-repass regular-text" name="password_re" type="password" id="pass2" autocomplete="new-password" placeholder="<?php _e('Retype password', 'simple-membership')?>" />
                        <div class="swpm-form-desc"></div>
                        <div id="pass-strength-result"></div>
                        <p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'simple-membership'); ?></p>
                    </td>
                </tr>
                <tr class="swpm-form-row swpm-account-state-row">
                    <th scope="row"><label for="account_state"><?php _e('Account Status', 'simple-membership'); ?></label></th>
                    <td><select class="regular-text" name="account_state" id="account_state">
                            <?php echo SwpmUtils::account_state_dropdown('active'); ?>
                        </select>
                    </td>
                </tr>
                <tr class="swpm-form-row swpm-membership-level-row">
                    <th scope="row"><label for="membership_level"><?php _e('Membership Level', 'simple-membership'); ?></label></th>
                    <td><select class="regular-text" name="membership_level" id="membership_level">
                            <?php foreach ($levels as $level) : ?>
                                <option <?php echo ($level['id'] == $membership_level) ? "selected='selected'" : ""; ?> value="<?php echo $level['id']; ?>"> <?php echo $level['alias'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php include('admin_member_form_common_part.php'); ?>
            </tbody>
        </table>
        <?php include('admin_member_form_common_js.php'); ?>
        <input type="hidden" name="createswpmuser" value="Add New Member">
        <?php submit_button(__('Add New Member', 'simple-membership'), 'primary', null, true, array('id' => 'createswpmusersub')); ?>
    </form>

    <style>
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
            /* margin-bottom: -18px; */
        }
        
        form.swpm-form .swpm-form-row .swpm-form-desc .pass-strength-result {
            margin-bottom: 0 !important;
        }

        form.swpm-form .swpm-form-row.error .swpm-form-desc>ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 4px 0 0 !important;
        }
    </style>
</div>