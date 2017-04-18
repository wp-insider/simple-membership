<script>
    jQuery(document).ready(function ($) {
        //$('#member_since').dateinput({'format':'yyyy-mm-dd',selectors: true,yearRange:[-100,100]});
        //$('#subscription_starts').dateinput({'format':'yyyy-mm-dd',selectors: true,yearRange:[-100,100]});
        $('#subscription_starts').datepicker({dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, yearRange: "-100:+100"});
        $('#member_since').datepicker({dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, yearRange: "-100:+100"});
    });
</script>
