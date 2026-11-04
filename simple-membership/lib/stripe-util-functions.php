<?php

/* Misc Utility Functions for the Stripe Gateway */

class StripeUtilFunctions {

    /*
     * Stripe checkout (SCA) browser token.
     * A random token is created for each checkout session and saved in a cookie in the browser that started the checkout.
     * Only a hash of the token (with the button ID) is used in the session's client_reference_id and the success URL (format: swpm_<hash>|<button_id>),
     * so the token can't be read from the URL. When the buyer returns from Stripe, a token in the cookie must match the hash,
     * so the payment is only applied to the account (and paid registration link) of the browser that started the checkout.
     * The cookie keeps the tokens of the latest few checkouts (so checkouts in several tabs work), and it never grows beyond that.
     */
    const CHECKOUT_BROWSER_TOKEN_COOKIE = 'swpm_stripe_checkout';
    const CHECKOUT_BROWSER_TOKEN_MAX = 5;

    public static function get_checkout_browser_token_hash($token, $button_id) {
        return hash('sha256', $token . '|' . absint($button_id));
    }

    public static function get_checkout_browser_tokens() {
        $cookie_value = isset($_COOKIE[self::CHECKOUT_BROWSER_TOKEN_COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::CHECKOUT_BROWSER_TOKEN_COOKIE])) : '';
        $tokens = array();
        foreach (explode('.', $cookie_value) as $token) {
            //Only keep valid tokens (32 letters and numbers).
            if (preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    /*
     * Creates a new token, saves it in the cookie (keeping only the latest few tokens) and returns its hash for the client_reference_id.
     */
    public static function create_checkout_browser_token($button_id) {
        $token = wp_generate_password(32, false);

        $tokens = self::get_checkout_browser_tokens();
        $tokens[] = $token;
        $tokens = array_slice($tokens, -self::CHECKOUT_BROWSER_TOKEN_MAX);
        $cookie_value = implode('.', $tokens);

        $secure = is_ssl();
        setcookie(self::CHECKOUT_BROWSER_TOKEN_COOKIE, $cookie_value, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure, true);
        $_COOKIE[self::CHECKOUT_BROWSER_TOKEN_COOKIE] = $cookie_value;

        return self::get_checkout_browser_token_hash($token, $button_id);
    }

    public static function is_checkout_from_this_browser($client_reference_id, $button_id) {
        //Get the hash from the client_reference_id (swpm_<hash>|<button_id>).
        $ref_parts = explode('|', (string) $client_reference_id);
        $ref_hash = (strpos($ref_parts[0], 'swpm_') === 0) ? substr($ref_parts[0], 5) : '';
        if (empty($ref_hash)) {
            return false;
        }

        foreach (self::get_checkout_browser_tokens() as $token) {
            if (hash_equals($ref_hash, self::get_checkout_browser_token_hash($token, $button_id))) {
                return true;
            }
        }
        return false;
    }

    public static function get_stripe_plan_info($api_key, $plan_id) {
        SwpmMiscUtils::load_stripe_lib();
        
        $stripe_err = '';

        try {
            \Stripe\Stripe::setApiKey($api_key);

            $plan = \Stripe\Plan::retrieve($plan_id);
        } catch (\Stripe\Error\Authentication $e) {
            // Invalid secret key
            $stripe_err = $e->getMessage();
        } catch (Exception $e) {
            //that's probably invalid plan ID or some other error
            $stripe_err = $e->getMessage();
        }
        if (empty($stripe_err)) {
            //we proceed with getting plan details only if no errors occurred
            $plan_data['name'] = isset($plan->nickname) ? $plan->nickname : '';
            $plan_data['amount'] = $plan->amount;
            $plan_data['currency'] = $plan->currency;
            $plan_data['interval'] = $plan->interval;
            $plan_data['interval_count'] = $plan->interval_count;
            $plan_data['trial_period_days'] = $plan->trial_period_days;
            return array('success' => true, 'plan_data' => $plan_data);
        } else {
            return array('success' => false, 'error_msg' => $stripe_err);
        }
    }

}
