<form method="post">
    <p class="search-box">
        <label class="screen-reader-text" for="search_id-search-input">
            search:</label>
        <input id="search_id-search-input" type="text" name="s" value="" />
        <input id="search-submit" class="button" type="submit" name="" value="<?php echo SwpmUtils::_('search') ?>" />
        <input type="hidden" name="page" value="simple_wp_membership" />
    </p>
</form>
<?php 
if (isset($_REQUEST['member_action']) && $_REQUEST['member_action'] == 'delete') {
    //Delete this record
    $this->delete();
    $success_msg = '<div id="message" class="updated"><p>';
    $success_msg .= SwpmUtils::_('The selected entry was deleted!');
    $success_msg .= '</p></div>';
    echo $success_msg;    
}

$this->prepare_items(); 
?>
<form id="tables-filter" method="get" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <!-- Now we can render the completed list table -->
    <?php $this->display(); ?>
</form>

<p>
    <a href="admin.php?page=simple_wp_membership&member_action=add" class="button-primary"><?php echo SwpmUtils::_('Add New') ?></a>
</p>