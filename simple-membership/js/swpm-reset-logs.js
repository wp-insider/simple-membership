/* global swpm_reset_logs_vars */

/**
 * Reset log files.
 */
document.addEventListener('DOMContentLoaded', function (){
    const swpm_reset_log_anchor = document.getElementById('swpm_reset_log_anchor');
    swpm_reset_log_anchor?.addEventListener('click', function (e){
        e.preventDefault();
        const ajaxUrl = swpm_reset_logs_vars.ajaxUrl;
        const payload = new URLSearchParams();
        payload.append('action', 'swpm_reset_log_action');
        // payload.append('nonce',  '<?php echo esc_js( wp_create_nonce( 'swpm_reset_log_action' ) ) ?>');
        payload.append('nonce',  swpm_reset_logs_vars.nonce);
        fetch(
            ajaxUrl,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: payload.toString()
            }
        ).then((response) => {
            return response.json();
        }).then((res) => {
            alert(res.data.message);
        }).catch(err => {
            alert('The ajax request to reset the log files has failed unexpectedly. Please try again later.');
        })
    });
})