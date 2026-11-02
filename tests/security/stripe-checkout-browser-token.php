<?php
/**
 * Standalone regression check: php tests/security/stripe-checkout-browser-token.php
 * Exercises the real browser check with the cookie written by several checkouts.
 * No WordPress database or Stripe connection is needed.
 */
ob_start();//Buffer the output so setcookie() can still send its header.
function absint($value) { return abs((int) $value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_unslash($value) { return stripslashes($value); }
function wp_generate_password($length, $special_chars) { return bin2hex(random_bytes($length / 2)); }
function is_ssl() { return false; }
define('DAY_IN_SECONDS', 86400);
define('COOKIEPATH', '/');
define('COOKIE_DOMAIN', '');

require dirname(__DIR__, 2) . '/simple-membership/lib/stripe-util-functions.php';

function check_browser($label, $reference, $button_id, $expected) {
    $actual = StripeUtilFunctions::is_checkout_from_this_browser($reference, $button_id);
    if ($actual !== $expected) {
        throw new RuntimeException($label . ': unexpected browser verification result.');
    }
    echo 'PASS: ' . $label . "\n";
}

$cookie = StripeUtilFunctions::CHECKOUT_BROWSER_TOKEN_COOKIE;
$button_id = 42;

//The code also updates $_COOKIE when it sets the cookie, so $_COOKIE stands in for what the browser sends back.
//Two checkouts in the same browser (example: two tabs) with the same button.
$_COOKIE = array();
$hash_a = StripeUtilFunctions::create_checkout_browser_token($button_id);
$hash_b = StripeUtilFunctions::create_checkout_browser_token($button_id);
$reference_a = 'swpm_' . $hash_a . '|42';
$reference_b = 'swpm_' . $hash_b . '|42';
$browser_cookie = $_COOKIE[$cookie];

check_browser('Checkout A remains valid after starting B', $reference_a, $button_id, true);
check_browser('Checkout B also remains valid', $reference_b, $button_id, true);
check_browser('A different button cannot use this cookie', $reference_a, 43, false);

//The URL only has the hash. A cookie made from the URL value is rejected.
if (strpos($browser_cookie, $hash_a) !== false) {
    throw new RuntimeException('The cookie must not contain the hash from the URL.');
}
$_COOKIE = array($cookie => substr($hash_a, 0, 32));
check_browser('A cookie made from the URL hash is rejected', $reference_a, $button_id, false);

$_COOKIE = array();
check_browser('A browser without the cookie is rejected', $reference_a, $button_id, false);

$_COOKIE = array($cookie => $browser_cookie);
check_browser('An empty reference is rejected', 'swpm_|42', $button_id, false);
check_browser('A reference without the prefix is rejected', $hash_a . '|42', $button_id, false);

//Many checkouts: the cookie keeps only the latest 5 tokens.
$_COOKIE = array();
$hashes = array();
for ($i = 0; $i < 20; $i++) {
    $hashes[] = StripeUtilFunctions::create_checkout_browser_token($button_id);
}
$token_count = count(explode('.', $_COOKIE[$cookie]));
if ($token_count !== StripeUtilFunctions::CHECKOUT_BROWSER_TOKEN_MAX) {
    throw new RuntimeException('The cookie must keep only the latest tokens. Count: ' . $token_count);
}
echo 'PASS: The cookie keeps only the latest ' . $token_count . ' tokens (' . strlen($_COOKIE[$cookie]) . " bytes)\n";
check_browser('The latest checkout is valid', 'swpm_' . $hashes[19] . '|42', $button_id, true);
check_browser('The 5th latest checkout is valid', 'swpm_' . $hashes[15] . '|42', $button_id, true);
check_browser('An older checkout falls back to the safe mode', 'swpm_' . $hashes[14] . '|42', $button_id, false);

//Invalid values in the cookie are ignored.
$_COOKIE = array($cookie => 'bad.<script>.' . str_repeat('x', 40));
check_browser('Invalid cookie values are ignored', $reference_a, $button_id, false);
$hash_c = StripeUtilFunctions::create_checkout_browser_token($button_id);
if (count(explode('.', $_COOKIE[$cookie])) !== 1) {
    throw new RuntimeException('Invalid cookie values must be removed when a new token is added.');
}
check_browser('A new checkout works after invalid cookie values', 'swpm_' . $hash_c . '|42', $button_id, true);
