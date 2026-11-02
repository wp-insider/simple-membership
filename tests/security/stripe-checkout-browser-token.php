<?php
/**
 * Standalone regression check: php tests/security/stripe-checkout-browser-token.php
 * Exercises the real browser check with the cookies returned by independent checkouts.
 * No WordPress database or Stripe connection is needed.
 */
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

$button_id = 42;
$token_a = StripeUtilFunctions::create_checkout_browser_token($button_id);
$token_b = StripeUtilFunctions::create_checkout_browser_token($button_id);
$reference_a = 'swpm_' . $token_a . '|42';
$reference_b = 'swpm_' . $token_b . '|42';
$cookie_a = StripeUtilFunctions::get_checkout_browser_token_cookie_name($button_id, $token_a);
$cookie_b = StripeUtilFunctions::get_checkout_browser_token_cookie_name($button_id, $token_b);

//A browser stores both responses before returning from either checkout.
$_COOKIE = array($cookie_a => $token_a, $cookie_b => $token_b);
check_browser('Checkout A remains valid after starting B', $reference_a, $button_id, true);
check_browser('Checkout B also remains valid', $reference_b, $button_id, true);
check_browser('Returning again to A remains valid', $reference_a, $button_id, true);
check_browser('A different button cannot use these cookies', $reference_a, 43, false);

$_COOKIE = array($cookie_b => $token_b);
check_browser('B alone cannot authorize A', $reference_a, $button_id, false);
$_COOKIE = array();
check_browser('A browser without cookies is rejected', $reference_a, $button_id, false);
$_COOKIE = array($cookie_a => $token_b);
check_browser('A mismatched token is rejected', $reference_a, $button_id, false);

//An update can happen while an older checkout is still open in another tab.
$legacy_cookie = StripeUtilFunctions::get_checkout_browser_token_cookie_name($button_id);
$_COOKIE = array($legacy_cookie => $token_a, $cookie_b => $token_b);
check_browser('Pre-update checkout remains valid alongside B', $reference_a, $button_id, true);
check_browser('New checkout remains valid alongside legacy cookie', $reference_b, $button_id, true);
$_COOKIE = array($legacy_cookie => $token_b);
check_browser('Legacy fallback still requires a matching token', $reference_a, $button_id, false);
