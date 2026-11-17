<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<tr class="swpm-admin-edit-access-starts">
    <th scope="row"><label for="subscription_starts"><?php echo esc_html__( 'Access Starts', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="subscription_starts" type="text" id="subscription_starts" value="<?php echo esc_attr($subscription_starts); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-first-name">
    <th scope="row"><label for="first_name"><?php echo esc_html__( 'First Name', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="first_name" type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-last-name">
    <th scope="row"><label for="last_name"><?php echo esc_html__( 'Last Name', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="last_name" type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-gender">
    <th scope="row"><label for="gender"><?php echo esc_html__( 'Gender', 'simple-membership' ); ?></label></th>
    <td><select class="regular-text" name="gender" id="gender">
            <?php echo wp_kses( SwpmUtils::gender_dropdown($gender), SwpmKsesUtils::wp_kses_select_option_tags() ) ?>
        </select>
    </td>
</tr>
<tr class="swpm-admin-edit-phone">
    <th scope="row"><label for="phone"><?php echo esc_html__( 'Phone', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="phone" type="text" id="phone" value="<?php echo esc_attr($phone); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-address-street">
    <th scope="row"><label for="address_street"><?php echo esc_html__( 'Street', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="address_street" type="text" id="address_street" value="<?php echo esc_attr($address_street); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-address-city">
    <th scope="row"><label for="address_city"><?php echo esc_html__( 'City', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="address_city" type="text" id="address_city" value="<?php echo esc_attr($address_city); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-address-state">
    <th scope="row"><label for="address_state"><?php echo esc_html__( 'State', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="address_state" type="text" id="address_state" value="<?php echo esc_attr($address_state); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-address-zipcode">
    <th scope="row"><label for="address_zipcode"><?php echo esc_html__( 'Zipcode', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="address_zipcode" type="text" id="address_zipcode" value="<?php echo esc_attr($address_zipcode); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-address-country">
    <th scope="row"><label for="country"><?php echo esc_html__( 'Country', 'simple-membership' ) ?> </label></th>
    <td><select class="regular-text" id="country" name="country"><?php echo wp_kses( SwpmMiscUtils::get_countries_dropdown($country), SwpmKsesUtils::wp_kses_post_tags_with_form() ) ?></select></td>
</tr>
<tr class="swpm-admin-edit-company">
    <th scope="row"><label for="company_name"><?php echo esc_html__( 'Company', 'simple-membership' ) ?></label></th>
    <td><input name="company_name" type="text" id="company_name" class="regular-text" value="<?php echo esc_attr($company_name); ?>" /></td>
</tr>
<tr class="swpm-admin-edit-member-since">
    <th scope="row"><label for="member_since"><?php echo esc_html__( 'Member Since', 'simple-membership' ) ?> </label></th>
    <td><input class="regular-text" name="member_since" type="text" id="member_since" value="<?php echo esc_attr($member_since); ?>" /></td>
</tr>
<tr class="swpm-form-row swpm-admin-notes-row">
    <th scope="row">
        <label for="admin_notes"><?php esc_attr_e('Admin Notes', 'simple-membership') ?></label>
    </th>
    <td>
        <textarea class="regular-text" name="notes" id="admin_notes" rows="4"><?php echo esc_attr($notes); ?></textarea>
        <p class="swpm-form-desc"><?php esc_attr_e('Use this field to save/add any notes about this user. This will only be visible to the admin.', 'simple-membership')?></p>
    </td>
</tr>
