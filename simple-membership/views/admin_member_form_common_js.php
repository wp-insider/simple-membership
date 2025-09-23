<script>
    jQuery(document).ready(function ($) {
        $('#subscription_starts').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
        });
        $('#member_since').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
        });
        //Allow any field with class 'swpm-date-picker' to use the datepicker
        $('.swpm-date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
        });
    });
</script>
