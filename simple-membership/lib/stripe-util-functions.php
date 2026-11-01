<?php

/* Misc Utility Functions for the Stripe Gateway */

class StripeUtilFunctions {

    /*
     * Stripe checkout (SCA) browser token.
     * A random token is created for each checkout session. It is saved in a cookie in the browser that started the checkout,
     * and in the session's client_reference_id (format: swpm_<token>|<button_id>). When the buyer returns from Stripe, the
     * token in the cookie must match, so the payment is only applied to the account (and paid registration link) of the browser that started the checkout.
     */
    public static function get_checkout_browser_token_cookie_name($button_id) {
        return 'swpm_stripe_checkout_' . absint($button_id);
    }

    public static function create_checkout_browser_token($button_id) {
        $token = wp_generate_password(32, false);
        $secure = is_ssl();
        setcookie(self::get_checkout_browser_token_cookie_name($button_id), $token, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure, true);
        return $token;
    }

    public static function is_checkout_from_this_browser($client_reference_id, $button_id) {
        //Get the token from the client_reference_id (swpm_<token>|<button_id>).
        $ref_parts = explode('|', (string) $client_reference_id);
        $token = (strpos($ref_parts[0], 'swpm_') === 0) ? substr($ref_parts[0], 5) : '';

        $cookie_name = self::get_checkout_browser_token_cookie_name($button_id);
        $cookie_token = isset($_COOKIE[$cookie_name]) ? sanitize_text_field(wp_unslash($_COOKIE[$cookie_name])) : '';

        if (empty($token) || empty($cookie_token)) {
            return false;
        }
        return hash_equals($token, $cookie_token);
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
