<?php

/* Misc Utility Functions for the Stripe Gateway */

class StripeUtilFunctions {

    // The API version requested by the calls below. The response is rendered in this version whatever Stripe library is
    // loaded (another plugin may load its own), so there is only one shape to read.
    const API_VERSION = '2026-07-29.dahlia';

    public static function get_request_options($secret_key) {
        return array('api_key' => $secret_key, 'stripe_version' => self::API_VERSION);
    }

    // The ID of an expanded object or of an ID string.
    public static function get_object_id($value) {
        if (is_string($value)) {
            return $value;
        }
        return (is_object($value) && isset($value->id)) ? (string) $value->id : '';
    }

    /**
     * Finds the one charge that paid an invoice (for example the first invoice of a subscription).
     *
     * Returns an array: 'state', 'charge_id', 'amount' (in the smallest currency unit, as Stripe gives it), 'currency', 'reason'.
     * The state is 'paid' only when the invoice is paid by exactly one successful charge of the whole amount, for the
     * expected customer, mode and subscription. Otherwise: 'no_amount_due', 'pending', 'unsupported', 'ambiguous' or 'error' (see 'reason').
     */
    public static function get_invoice_payment_charge($invoice_id, $secret_key, $customer_id, $livemode, $subscription_id) {
        $result = array('state' => 'unsupported', 'charge_id' => '', 'amount' => null, 'currency' => '', 'reason' => '');
        SwpmMiscUtils::load_stripe_lib();
        if (empty($invoice_id) || empty($secret_key) || !self::has_methods(array('\Stripe\Invoice' => 'retrieve', '\Stripe\Charge' => 'retrieve', '\Stripe\PaymentIntent' => 'retrieve'))) {
            $result['reason'] = 'missing invoice ID, API key or Stripe library methods';
            return $result;
        }
        $opts = self::get_request_options($secret_key);

        try {
            $invoice = \Stripe\Invoice::retrieve(array('id' => $invoice_id, 'expand' => array('payments.data.payment.payment_intent')), $opts);

            $status = isset($invoice->status) ? $invoice->status : '';
            if ($status === 'open') {
                $result['state'] = 'pending';
                $result['reason'] = 'the invoice is not paid yet';
                return $result;
            }
            if ($status !== 'paid') {
                $result['reason'] = 'invoice status: ' . $status;
                return $result;
            }
            if (isset($invoice->amount_due) && is_numeric($invoice->amount_due) && (int) $invoice->amount_due === 0) {
                $result['state'] = 'no_amount_due';
                $result['reason'] = 'no amount due on the invoice';
                return $result;
            }
            if (!isset($invoice->payments->data) || !isset($invoice->payments->has_more) || !is_int($invoice->amount_paid ?? null) || !isset($invoice->currency)) {
                $result['reason'] = 'the invoice has no complete payments list, paid amount or currency';
                return $result;
            }
            $invoice_subscription = isset($invoice->parent->subscription_details->subscription) ? self::get_object_id($invoice->parent->subscription_details->subscription) : '';
            if (self::get_object_id($invoice->customer ?? '') !== (string) $customer_id || !isset($invoice->livemode) || (bool) $invoice->livemode !== (bool) $livemode || $invoice_subscription !== (string) $subscription_id) {
                $result['state'] = 'ambiguous';
                $result['reason'] = 'the invoice is not of the expected customer, mode or subscription';
                return $result;
            }
            if ($invoice->payments->has_more !== false) {
                $result['state'] = 'ambiguous';
                $result['reason'] = 'the invoice has more payments than one page';
                return $result;
            }
            $paid = array();
            foreach ($invoice->payments->data as $payment) {
                if (isset($payment->status) && $payment->status === 'paid') {
                    $paid[] = $payment;
                }
            }
            if (count($paid) !== 1) {
                $result['state'] = count($paid) > 1 ? 'ambiguous' : 'unsupported';
                $result['reason'] = count($paid) . ' paid payments on the invoice';
                return $result;
            }
            $payment = $paid[0];
            if (!is_int($payment->amount_paid ?? null) || $payment->amount_paid !== $invoice->amount_paid || strtolower((string) ($payment->currency ?? '')) !== strtolower($invoice->currency)) {
                $result['state'] = 'ambiguous';
                $result['reason'] = 'the payment does not cover the whole invoice in its currency';
                return $result;
            }

            // The charge: directly, or the latest charge of a succeeded payment intent.
            $type = isset($payment->payment->type) ? $payment->payment->type : '';
            $charge_id = '';
            if ($type === 'charge') {
                $charge_id = self::get_object_id(isset($payment->payment->charge) ? $payment->payment->charge : '');
            } else if ($type === 'payment_intent') {
                $intent = isset($payment->payment->payment_intent) ? $payment->payment->payment_intent : '';
                if (is_string($intent) && $intent !== '') {
                    $intent = \Stripe\PaymentIntent::retrieve($intent, $opts);
                }
                if (is_object($intent) && isset($intent->status) && $intent->status === 'succeeded') {
                    $charge_id = self::get_object_id(isset($intent->latest_charge) ? $intent->latest_charge : '');
                }
            }
            if ($charge_id === '') {
                $result['reason'] = 'no succeeded charge for the payment (type: ' . $type . ')';
                return $result;
            }

            $charge = \Stripe\Charge::retrieve($charge_id, $opts);
            $checks = array(
                'status'   => isset($charge->status) && $charge->status === 'succeeded' && !empty($charge->paid),
                'amount'   => is_int($charge->amount ?? null) && $charge->amount === $payment->amount_paid,
                'currency' => isset($charge->currency) && strtolower($charge->currency) === strtolower($invoice->currency),
                'customer' => isset($charge->customer) && self::get_object_id($charge->customer) === (string) $customer_id,
                'mode'     => isset($charge->livemode) && (bool) $charge->livemode === (bool) $livemode,
            );
            $failed = array_keys(array_filter($checks, function ($ok) { return !$ok; }));
            if (!empty($failed)) {
                $result['state'] = 'ambiguous';
                $result['reason'] = 'the charge does not match: ' . implode(', ', $failed);
                return $result;
            }

            return array('state' => 'paid', 'charge_id' => $charge_id, 'amount' => $charge->amount, 'currency' => strtoupper($charge->currency), 'reason' => '');
        } catch (\Throwable $e) {
            $result['state'] = 'error';
            $result['reason'] = $e->getMessage();
            return $result;
        }
    }

    // True when every class => static method exists in the loaded Stripe library (another plugin may load an older one).
    public static function has_methods($methods) {
        foreach ($methods as $class => $method) {
            if (!class_exists($class) || !method_exists($class, $method)) {
                return false;
            }
        }
        return true;
    }

    // The amount in the smallest currency unit (cents), as Stripe gives it: zero-decimal currencies are not multiplied.
    public static function get_amount_in_stripe_units($amount, $currency) {
        $zero_cents = unserialize(SIMPLE_WP_MEMBERSHIP_STRIPE_ZERO_CENTS);
        return in_array(strtoupper((string) $currency), $zero_cents, true) ? (int) round((float) $amount) : (int) round((float) $amount * 100);
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
