<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 SimpleWpMembership::enqueue_validation_scripts(); ?>
<div class="wrap" id="swpm-profile-page" type="add">
<style>#swpm-create-user input {position: relative;}</style>
    <form action="" method="post" name="swpm-create-user" id="swpm-create-user" class="validate swpm-validate-form"<?php do_action('user_new_form_tag'); ?>>
        <input name="action" type="hidden" value="createuser" />
        <?php wp_nonce_field('create_swpmuser_admin_end', '_wpnonce_create_swpmuser_admin_end') ?>
        <h3><?php echo esc_html__( 'Add Member', 'simple-membership' ) ?></h3>
        <p><?php echo esc_html__( 'Create a brand new user and add it to this site.', 'simple-membership' ); ?></p>
        <table class="form-table">
            <tbody>
                <tr class="form-required swpm-admin-add-username">
                    <th scope="row"><label for="user_name"><?php echo esc_html__( 'Username', 'simple-membership' ); ?> <span class="description"><?php echo esc_html__( '(required)', 'simple-membership' ); ?></span></label></th>
                    <td><input class="regular-text validate[required,custom[noapostrophe],custom[SWPMUserName],minSize[4],ajax[ajaxUserCall]]" name="user_name" type="text" id="user_name" value="<?php echo esc_attr(stripslashes($user_name)); ?>" aria-required="true" /></td>
                </tr>
                <tr class="form-required swpm-admin-add-email">
                    <th scope="row"><label for="email"><?php echo esc_html__( 'E-mail', 'simple-membership' ); ?> <span class="description"><?php echo esc_html__( '(required)', 'simple-membership' ); ?></span></label></th>
                    <td><input name="email" autocomplete="off" class="regular-text validate[required,custom[email],ajax[ajaxEmailCall]]" type="text" id="email" value="<?php echo esc_attr($email); ?>" /></td>
                </tr>
                <tr class="form-required swpm-admin-add-password">
                    <th scope="row"><label for="password"><?php echo esc_html__( 'Password', 'simple-membership' ); ?> <span class="description"><?php esc_attr_e('(twice, required)', 'simple-membership'); ?></span></label></th>
                    <td><input class="regular-text"  name="password" type="password" id="pass1" autocomplete="off" />
                        <br />
                        <input class="regular-text" name="password_re" type="password" id="pass2" autocomplete="off" />
                        <br />
                        <div id="pass-strength-result"><?php echo esc_html__( 'Strength indicator', 'simple-membership' ); ?></div>
                        <p class="description indicator-hint"><?php echo esc_html__( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'simple-membership' ); ?></p>
                    </td>
                </tr>
                <tr class="swpm-admin-add-account-state">
                    <th scope="row"><label for="account_state"><?php echo esc_html__( 'Account Status', 'simple-membership' ); ?></label></th>
                    <td><select class="regular-text" name="account_state" id="account_state">
                            <?php echo wp_kses( SwpmUtils::account_state_dropdown('active'), SwpmKsesUtils::wp_kses_select_option_tags() ); ?>
                        </select>
                    </td>
                </tr>
                <tr class="swpm-admin-edit-membership-level">
                    <th scope="row"><label for="membership_level"><?php echo esc_html__( 'Membership Level', 'simple-membership' ); ?></label></th>
                    <td><select class="regular-text" name="membership_level" id="membership_level">
                            <?php foreach ($levels as $level): ?>
                                <option <?php echo ($level['id'] == $membership_level) ? "selected='selected'" : ""; ?> value="<?php echo esc_attr( $level['id'] ); ?>"> <?php echo esc_attr( $level['alias'] )?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php include('admin_member_form_common_part.php'); ?>
            </tbody>
        </table>
        <?php include('admin_member_form_common_js.php'); ?>
        <?php submit_button(SwpmUtils::_('Add New Member '), 'primary', 'createswpmuser', true, array('id' => 'createswpmusersub')); ?>
    </form>
</div>
