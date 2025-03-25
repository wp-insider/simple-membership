<?php

$settings = SwpmSettings::get_instance();

if ( isset( $_POST['default_content_protection_form_submit'] ) && check_admin_referer('default_content_protection_form_nonce')){

    $enable_default_content_protection = isset($_POST['enable_default_content_protection']) ? 'checked="checked"': '';

    $default_protect_this_content = isset($_POST['default_protect_this_content']) && sanitize_text_field($_POST['default_protect_this_content']) == 2 ? true : false;
    $default_protection_membership_levels = isset($_POST['default_protection_membership_levels']) ? array_map('sanitize_text_field', $_POST['default_protection_membership_levels']) : array();

    $settings->set_value('enable_default_content_protection', $enable_default_content_protection);

    $settings->set_value('default_protect_this_content', $default_protect_this_content);
    $settings->set_value('default_protection_membership_levels', $default_protection_membership_levels);
    $settings->save();

    echo '<div class="notice notice-success"><p>'. __('Default Content Protection Settings Updated.', 'simple-membership') .'</p></div>';
}

$enable_default_content_protection = $settings->get_value('enable_default_content_protection', false);

$default_protect_this_content = $settings->get_value('default_protect_this_content', false);
$default_protection_membership_levels = $settings->get_value('default_protection_membership_levels', array());
?>

<div id="poststuff"><div id="post-body">

        <div class="swpm-yellow-box">
            <?php _e('Read the ', 'simple-membership'); ?> <a href="https://simple-membership-plugin.com/apply-content-protection/" target="_blank"><?php _e('content protection documentation', 'simple-membership'); ?></a> <?php _e('to learn more.', 'simple-membership'); ?>
        </div>
        <h3><?php _e('How to Apply Content Protection', 'simple-membership'); ?></h3>

        <p><?php _e('Take the following steps to apply protection to your content so only members can have access to it.', 'simple-membership'); ?></p>

        <ol>
            <li><?php _e('Edit the Post or Page that you want to protect in WordPress editor.', 'simple-membership'); ?></li>
            <li><?php _e('Scroll down to the section titled \'Simple WP Membership Protection\'.', 'simple-membership'); ?></li>
            <li><?php _e('Select \'Yes, Protect this content\' option.', 'simple-membership'); ?></li>
            <li><?php _e('Check the membership levels that should have access to that page\'s content.', 'simple-membership'); ?></li>
            <li><?php _e('Hit the Update/Save Button to save the changes.', 'simple-membership'); ?></li>
        </ol>
        <br/>
        <h3><?php echo SwpmUtils::_('Example Content Protection Settings') ?></h3>

        <img src="<?php echo SIMPLE_WP_MEMBERSHIP_URL . '/images/simple-membership-content-protection-usage.png'; ?>" alt="Content protection example usage">

    </div>

    <br>
    <div>
        <h3><?php _e('Default Content Protection Settings', 'simple-membership') ?></h3>

        <form action="" method="post">
            <table class="form-table">
                <tbody>
                <tr>
                    <th>
                        <label><?php _e('Enable Default Content Protection', 'simple-membership') ?></label>
                    </th>
                    <td>
                        <input
                                type="checkbox"
                                name="enable_default_content_protection"
                                id="enable_default_content_protection"
                            <?php esc_attr_e($enable_default_content_protection) ?>
                        >
                        <p class="description">
                            <?php _e('When creating a new post or page, the content protection settings will be pre-filled based on the default protection template you set below.', 'simple-membership') ?>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>

            <div>
                <h3><?php _e('Default Content Protection Template', 'simple-membership') ?></h3>

                <h4><?php _e("Do you want to protect this content?", 'simple-membership') ?></h4>
                <input type="radio" <?php echo !$default_protect_this_content ? 'checked' : "" ?>  name="default_protect_this_content" value="1" /><?php _e('No, Do not protect this content.', 'simple-membership') ?><br/>
                <input type="radio" <?php echo $default_protect_this_content ? 'checked' : "" ?>  name="default_protect_this_content" value="2" /><?php _e('Yes, Protect this content.', 'simple-membership') ?><br/>
                <h4><?php _e("Select the membership level that can access this content:", 'simple-membership') ?></h4>

                <?php
                $membership_levels = SwpmMembershipLevelUtils::get_all_membership_levels_in_array();

                foreach ($membership_levels as $level_id => $level_alias) {
	                $is_checked = in_array($level_id, $default_protection_membership_levels);

	                echo '<input type="checkbox" ' . ($is_checked ? "checked='checked'" : "") .
	                     ' name="default_protection_membership_levels[' . $level_id . ']" value="' . $level_id . '" /> ' . $level_alias . "<br/>";
                }

                ?>
            </div>

            <?php echo wp_nonce_field('default_content_protection_form_nonce'); ?>

            <p class="submit">
                <input type="submit" class="button-primary" name="default_content_protection_form_submit"  value="<?php _e('Save Changes', 'simple-membership') ?>">
            </p>
        </form>
    </div>

</div><!-- end of poststuff and post-body -->
