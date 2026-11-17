<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//$this refers to class "SwpmMembers" in this context.

if (isset($_REQUEST['member_action']) && $_REQUEST['member_action'] == 'delete') {
    //Delete this record
    $this->delete();
    $success_msg = '<div id="message" class="updated"><p>';
    $success_msg .= SwpmUtils::_('The selected entry was deleted!');
    $success_msg .= '</p></div>';
    echo wp_kses_post( $success_msg );
}

$this->prepare_items();
$count = $this->get_user_count_by_account_state();

global $wpdb;

$levels = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE  id !=1 ", ARRAY_A);

$account_state = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$membership_level = filter_input(INPUT_GET, 'membership_level', FILTER_SANITIZE_NUMBER_INT);
?>
<style>
    select.swpm-admin-search-dropdown {
        vertical-align: inherit;
    }
    input.button.swpm-admin-search-btn {
        vertical-align: top;
    }
</style>
<ul class="subsubsub">
    <li class="all"><a href="admin.php?page=simple_wp_membership" <?php echo $status == "" ? "class='current'" : ""; ?> ><?php echo esc_html__( 'All', 'simple-membership' ) ?> <span class="count">(<?php echo esc_attr( $count['all'] ); ?>)</span></a> |</li>
    <li class="active"><a href="admin.php?page=simple_wp_membership&status=active" <?php echo $status == "active" ? "class='current'" : ""; ?>><?php esc_attr_e( 'Active', 'simple-membership' ); ?> <span class="count">(<?php echo esc_attr( isset($count['active']) ? $count['active'] : 0 )?>)</span></a> |</li>
    <li class="active"><a href="admin.php?page=simple_wp_membership&status=inactive" <?php echo $status == "inactive" ? "class='current'" : ""; ?>><?php esc_attr_e( 'Inactive', 'simple-membership' ); ?> <span class="count">(<?php echo esc_attr( isset($count['inactive']) ? $count['inactive'] : 0 )?>)</span></a> |</li>
    <li class="pending"><a href="admin.php?page=simple_wp_membership&status=activation_required" <?php echo $status == "activation_required" ? "class='current'" : ""; ?>><?php esc_attr_e( 'Activation Required', 'simple-membership' ); ?> <span class="count">(<?php echo esc_attr( isset($count['activation_required']) ? $count['activation_required'] : 0 )?>)</span></a> |</li>
    <li class="pending"><a href="admin.php?page=simple_wp_membership&status=pending" <?php echo $status == "pending" ? "class='current'" : ""; ?>><?php esc_attr_e( 'Pending', 'simple-membership' ); ?> <span class="count">(<?php echo esc_attr( isset($count['pending']) ? $count['pending'] : 0 )?>)</span></a> |</li>
    <li class="incomplete"><a href="admin.php?page=simple_wp_membership&status=incomplete" <?php echo $status == "incomplete" ? "class='current'" : ""; ?>><?php esc_attr_e( 'Incomplete', 'simple-membership' ); ?> <span class="count">(<?php echo esc_attr( isset($count['incomplete']) ? $count['incomplete'] : 0 )?>)</span></a> |</li>
    <li class="expired"><a href="admin.php?page=simple_wp_membership&status=expired" <?php echo $status == "expired" ? "class='current'" : ""; ?>><?php esc_attr_e( 'Expired', 'simple-membership' ); ?> <span class="count">(<?php echo esc_attr( isset($count['expired']) ? $count['expired'] : 0 )?>)</span></a></li>
</ul>

<br />
<form method="get">
    <p class="search-box">
        <select name="status" class="swpm-admin-search-dropdown" id="account_state">
            <option value=""<?php echo empty($account_state) ? ' selected' : ''; ?>> <?php echo esc_html__( 'Account State', 'simple-membership' ); ?></option>
            <?php echo wp_kses( SwpmUtils::account_state_dropdown($account_state), SwpmKsesUtils::wp_kses_select_option_tags() ); ?>
            <option value="incomplete"<?php echo $account_state === "incomplete" ? ' selected' : ''; ?>> <?php echo esc_html__( 'Incomplete', 'simple-membership' ); ?></option>
        </select>
        <select name="membership_level" class="swpm-admin-search-dropdown" id="membership_level">
            <option value=""<?php echo empty($membership_level) ? ' selected' : ''; ?>> <?php echo esc_html__( 'Membership Level', 'simple-membership' ); ?></option>
            <?php foreach ($levels as $level): ?>
                <option <?php echo ($level['id'] == $membership_level) ? "selected='selected'" : ""; ?> value="<?php echo esc_attr( $level['id'] ); ?>"> <?php echo esc_attr( $level['alias'] )?></option>
            <?php endforeach; ?>
        </select>
        <input id="search_id-search-input" type="text" name="s" value="<?php echo isset($_REQUEST['s']) ? esc_attr(stripslashes($_REQUEST['s'])) : ''; ?>" />
        <input id="search-submit" class="button swpm-admin-search-btn" type="submit" name="" value="<?php echo esc_html__( 'Search', 'simple-membership' ) ?>" />
        <input type="hidden" name="page" value="simple_wp_membership" />
    </p>
</form>

<form id="tables-filter" method="get" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
    <!-- Now we can render the completed list table -->
    <?php $this->display(); ?>
    <?php wp_nonce_field( 'swpm_bulk_action', 'swpm_bulk_action_nonce' ); ?>
</form>

<p>
    <a href="admin.php?page=simple_wp_membership&member_action=add" class="button-primary"><?php echo esc_html__( 'Add New', 'simple-membership' ) ?></a>
</p>