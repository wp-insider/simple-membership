/**
 * PayPal Standard buttons: creates a checkout token when the button is clicked.
 * The token is saved in a cookie of the buyer's browser and added to the 'custom' field. The IPN saves the token's hash on the new member,
 * so the thank you page only shows the paid registration link in the browser that made the payment (see SwpmUtils::get_paid_member_of_this_browser()).
 * Without JavaScript, the member gets the link by email only.
 */
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.classList || !form.classList.contains('swpm-pp-std-checkout-form')) {
        return;
    }
    var custom = form.querySelector('input[name="custom"]');
    if (!custom || !window.crypto || !window.crypto.getRandomValues) {
        return;
    }
    var bytes = new Uint8Array(12);
    window.crypto.getRandomValues(bytes);
    var token = '';
    for (var i = 0; i < bytes.length; i++) {
        token += ('0' + bytes[i].toString(16)).slice(-2);
    }
    //The custom field value is URL encoded. Replace a token added by an earlier click.
    custom.value = custom.value.replace(/%26swpm_pp_ct%3D[0-9a-f]{24}/g, '') + '%26swpm_pp_ct%3D' + token;

    //Keep the latest 5 tokens (example: checkouts in several tabs).
    var match = document.cookie.match(/(?:^|;\s*)swpm_pp_checkout_tokens=([^;]*)/);
    var tokens = (match && match[1]) ? match[1].split('.') : [];
    tokens.push(token);
    tokens = tokens.slice(-5);
    var cookie = 'swpm_pp_checkout_tokens=' + tokens.join('.') + '; path=/; max-age=' + (7 * 24 * 60 * 60) + '; SameSite=Lax';
    if (window.location.protocol === 'https:') {
        cookie += '; Secure';
    }
    document.cookie = cookie;
}, true);
