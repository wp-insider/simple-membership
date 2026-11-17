<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<script>
    jQuery(document).ready(function ($) {
        $('#subscription_starts').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
            currentText: '<?php esc_attr_e("Current Month", "simple-membership") ?>',
        });
        $('#member_since').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
            currentText: '<?php esc_attr_e("Current Month", "simple-membership") ?>',
        });
        //Allow any field with class 'swpm-date-picker' to use the datepicker
        $('.swpm-date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+100",
            showButtonPanel: true,
            currentText: '<?php esc_attr_e("Current Month", "simple-membership") ?>',
        });
    });
</script>
