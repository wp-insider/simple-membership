/* global swpm_admin_js_vars */

document.addEventListener('DOMContentLoaded', function () {
    const notice = document.getElementById('swpm_stripe_api_old_version_notice');
    if (!notice) return;

    notice.addEventListener('click', function (e) {

        const formData = new FormData();
        formData.append('action', 'swpm_old_stripe_api_notice_dismiss');
        formData.append('_wpnonce', swpm_admin_js_vars.nonce);

        if (e.target.classList.contains('notice-dismiss')) {
            fetch(swpm_admin_js_vars.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                }
            ).catch(function (e) {
                console.log(e.message)
            })
        }
    });
});

