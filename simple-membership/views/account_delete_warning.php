
<h2 class="swpm-account-delete-heading">
    <?php _e('Confirm Account Deletion', 'simple-membership'); ?>
</h2>
<?php if (!empty($msg)) echo '<p>' . $msg . '</p>'; ?>
<p style="color:red;">
    <?php _e('You are about to delete an account. This will delete user data associated with this account. ', 'simple-membership'); ?>
    <?php _e('It will also delete the associated WordPress user account.', 'simple-membership'); ?>
    <?php _e('(NOTE: for safety, we do not allow deletion of any associated WordPress account with administrator role).', 'simple-membership'); ?>
</p>
<p style="font-weight: bold;">
<?php _e('To proceed with the deletion process, please enter the current password for this user account.', 'simple-membership'); ?>
</p>
<form method="post">
    <p><?php _e('Password: ', 'simple-membership'); ?><input name="account_delete_confirm_pass" type="password"></p>
    <p><input type="submit" name="confirm" value="<?php _e('Confirm Account Deletion', 'simple-membership'); ?>" /> </p>
    <?php wp_nonce_field('swpm_account_delete_confirm', 'account_delete_confirm_nonce'); ?>
</form>