/* global swpm_admin_login_events_vars */

/**
 * Filter login events
 */
document.addEventListener("DOMContentLoaded", function (){
    const submitBtn = document.getElementById('swpm-login-events-filter-submit');
    const fieldset = document.getElementById('swpm-login-events-filter-fieldset');
    const fields = fieldset?.querySelectorAll('input');

    submitBtn?.addEventListener('click', function (e){
        e.preventDefault();

        const currentURL = new URL(window.location.href);

        fields.forEach(input => {
            const inputValue = input.value.trim();

            if (inputValue.length){
                currentURL.searchParams.set(input.name, inputValue) // Append query arg.
            }else{
                currentURL.searchParams.delete(input.name) // Remove existing empty query args.
            }

            currentURL.searchParams.delete('paged') // Always remove the pagination args when filter is applied.
        });

        window.location.replace(currentURL);
    })
})

/**
 * Reset login events
 */
document.addEventListener('DOMContentLoaded', function(){
    const resetBtn = document.getElementById('swpm-reset-login-event-entries');
    resetBtn.addEventListener('click', async function (e){
        if(!confirm("Are you sure you want to delete all logs?")) {
            return;
        }
        try {
            const payload = new URLSearchParams({
                action: 'swpm_reset_login_events',
                nonce: swpm_admin_login_events_vars.nonce
            })
            const response = await fetch(swpm_admin_login_events_vars.ajaxUrl, {
                method: 'post',
                    headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: payload,
            })

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.data.message);
            }

            alert(result.data.message);

            window.location.replace(window.location.href);
        } catch (error) {
            console.log(error);
            alert(error.message);
        }
    })
})