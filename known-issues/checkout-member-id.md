# The member ID in the checkout's custom field is not tied to the logged-in member

Decided: 2026-11-23. Area: payments, all gateways (PayPal Standard, PayPal PPCP, Stripe, Braintree).

## Behavior

When a logged-in member pays, the checkout page puts their member ID (`swpm_id`) in the payment's custom field. The payment handlers use that ID to find the member to renew or upgrade. The ID comes from the buyer's browser and is not checked against the logged-in member on the server.

- A buyer who changes it to another member's valid ID applies their own payment to that member's account (renewal or level change).
- If the ID is valid but that member account was deleted, the payment is recorded but no member account is updated (there is no fallback to the email lookup).

An invalid (non-numeric) ID is rejected and treated as no ID: the member is found by the buyer's email or the subscription ID, like a buyer who is not logged in. That was the fix for a stored XSS report and is covered by tests.

## Why we keep it

- The buyer gains nothing: no access, no role, no money. It costs them a real payment.
- The payment record shows which member the payment was applied to, and an admin can undo the change.
- A fix (signing or binding the ID to the checkout on the server) means changing every gateway's checkout, for a case where someone pays to do something pointless.

## Where

- `SwpmMemberUtils::get_valid_member_id()` in `simple-membership/classes/class.swpm-utils-member.php`: rejects invalid values.
- `swpm_handle_subsc_signup_stand_alone()` in `simple-membership/ipn/swpm_handle_subsc_ipn.php`: uses the ID (with a comment about this behavior).
- Tests: the "PayPal Standard: member ID (swpm_id) in the custom field" endpoint tests and `tests/phpunit/tests/test-payment-member-id.php` (see `tests/TESTS.md`).

## Raise it again if

- A change lets the buyer gain something from it (access, a role, a free or cheaper membership).
- It is reported to us as a real vulnerability.
- Genuine buyers report payments that were not applied because their member account was deleted. The small fix then is the email lookup fallback (test-first, it is payment code).
