<?php 

//$this refers to class "SwpmMembers" in this context.

if (isset($_REQUEST['member_action']) && $_REQUEST['member_action'] == 'delete') {
    //Delete this record
    $this->delete();
    $success_msg = '<div id="message" class="updated"><p>';
    $success_msg .= SwpmUtils::_('The selected entry was deleted!');
    $success_msg .= '</p></div>';
    echo $success_msg;    
}

$this->prepare_items(); 
$count = $this->get_user_count_by_account_state();
?>
<ul class="subsubsub">
    <li class="all"><a href="admin.php?page=simple_wp_membership" <?php echo $status == ""? "class='current'": "";?> ><?php echo SwpmUtils::_('All') ?> <span class="count">(<?php echo $count['all'];?>)</span></a> |</li>
	<li class="active"><a href="admin.php?page=simple_wp_membership&status=active" <?php echo $status == "active"? "class='current'": "";?>><?php echo SwpmUtils::_('Active') ?> <span class="count">(<?php echo isset($count['active'])? $count['active']: 0 ?>)</span></a> |</li>
        <li class="active"><a href="admin.php?page=simple_wp_membership&status=inactive" <?php echo $status == "inactive"? "class='current'": "";?>><?php echo SwpmUtils::_('Inactive') ?> <span class="count">(<?php echo isset($count['inactive'])? $count['inactive']: 0 ?>)</span></a> |</li>
        <li class="pending"><a href="admin.php?page=simple_wp_membership&status=pending" <?php echo $status == "pending"? "class='current'": "";?>><?php echo SwpmUtils::_('Pending') ?> <span class="count">(<?php echo isset($count['pending'])? $count['pending']: 0 ?>)</span></a> |</li>
        <li class="incomplete"><a href="admin.php?page=simple_wp_membership&status=incomplete" <?php echo $status == "incomplete"? "class='current'": "";?>><?php echo SwpmUtils::_('Incomplete') ?> <span class="count">(<?php echo isset($count['incomplete'])? $count['incomplete']: 0 ?>)</span></a> |</li>
	<li class="expired"><a href="admin.php?page=simple_wp_membership&status=expired" <?php echo $status == "expired"? "class='current'": "";?>><?php echo SwpmUtils::_('Expired') ?> <span class="count">(<?php echo isset($count['expired'])? $count['expired']: 0 ?>)</span></a></li>
</ul>

<br />
<form method="get">
    <p class="search-box">
        <input id="search_id-search-input" type="text" name="s" value="<?php echo isset($_REQUEST['s'])? esc_attr($_REQUEST['s']): ''; ?>" />
        <input id="search-submit" class="button" type="submit" name="" value="<?php echo SwpmUtils::_('Search') ?>" />
        <input type="hidden" name="page" value="simple_wp_membership" />
    </p>
</form>

<form id="tables-filter" method="get" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
    <!-- Now we can render the completed list table -->
    <?php $this->display(); ?>
</form>

<p>
    <a href="admin.php?page=simple_wp_membership&member_action=add" class="button-primary"><?php echo SwpmUtils::_('Add New') ?></a>
</p>