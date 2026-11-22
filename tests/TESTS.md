# Plugin Tests: what is covered

This file lists what the **Plugin Tests** GitHub Actions workflow checks, and what isn't covered yet.
Update it in the same commit when you add or change a test.

## How to run

GitHub > Actions > **Plugin Tests** > Run workflow. Choose the branch and tick the test groups to run (all are ticked by default).
The workflow file is [.github/workflows/tests.yml](../.github/workflows/tests.yml).

| Group | What it is | PHP versions | Files |
|---|---|---|---|
| Lint | PHP syntax check of the plugin and the test files (the Stripe and Braintree libraries are skipped) | 7.4, 8.5 | (in the workflow) |
| Security scripts | Standalone scripts that load plugin files with stand-ins for the WordPress functions (no database) | 7.4, 8.4 | [security/](security/), run by [bin/run-security-tests.php](bin/run-security-tests.php) |
| PHPUnit | Tests with the WordPress test library and a real database | 7.4, 8.4 | [phpunit/](phpunit/) |
| Endpoint tests | A real WordPress site with the plugin: sends PayPal IPN, Stripe webhook and email activation requests over HTTP and checks the database | 7.4, 8.4 | [e2e/](e2e/) |

Which group a new test belongs in:
- The code runs from an incoming request (IPN, webhook, a link the member clicks) and ends with `exit` or `wp_die()`: **Endpoint tests**.
- Plugin functions and classes that need WordPress and the database: **PHPUnit**.
- A small check of one function, with no database: **Security scripts** (PHPUnit is usually better for new tests).

The older Docker setup in this folder ([readme.md](readme.md), [composer.json](composer.json), [phpunit.xml.dist](phpunit.xml.dist)) is not run by the workflow.

---

## Endpoint tests ([e2e/run-endpoint-tests.php](e2e/run-endpoint-tests.php))

The site is set up with WP-CLI. [e2e/setup-site.php](e2e/setup-site.php) creates the level, the button and the members.
[e2e/mu-plugin-fake-paypal-verification.php](e2e/mu-plugin-fake-paypal-verification.php) answers PayPal's IPN verification request (VERIFIED, or INVALID for the rejection test).
It also saves the member ID of the last `swpm_payment_refunded` action, for the refund checks.
The Stripe events are signed with a test signing secret. IPNs are made from the anonymized fixtures in [fixtures/paypal-ipn/](fixtures/paypal-ipn/).

**PayPal Standard IPN (Buy Now)**
- A verified IPN creates the transaction record.
- A new buyer gets a member who needs to complete the registration, with the checkout token hash saved (browser proof for the registration link).
- A duplicate IPN (same txn_id) is ignored.
- A payment matched to an existing member by email is recorded, but doesn't get the browser proof.
- Rejected: another PayPal account (receiver email), an amount lower than the price, an IPN PayPal doesn't verify.

**Stripe webhooks**
- A renewal (`invoice.payment_succeeded`) of a subscription created on this site is recorded, linked to the member and updates the member's access start date (HTTP 200).
- A renewal of another site's subscription (same Stripe account) is ignored.
- A webhook with a wrong signature is rejected (HTTP 400) and nothing is saved.
- A cancellation (`customer.subscription.deleted`) deactivates the member of a no-expiry level and updates the agreement record's status.
- A full refund (`charge.refunded`) of a one-time payment marks it Refunded and deactivates the member.
- Stripe Legacy subscriptions (the member's subscr_id and the checkout record's txn_id are the customer ID), found by the customer ID:
  a renewal is recorded, linked to the member and updates the access start date; a cancellation deactivates the member and updates the checkout record's status;
  a refund of a renewal payment marks it Refunded and deactivates the member.

**Email activation**
- A wrong activation code doesn't activate the account and shows the "invalid or expired" message.
- No member enumeration: a wrong code, a member ID that doesn't exist, an active member, no code and a used link all get the same response.
- The correct code activates the account, and the code is deleted once it's used.
- The resend activation email request shows the same message for a missing member and an active member.

**Registration form** (level-specific forms, `[swpm_registration_form level=N]`, submitted the way a browser does)
- The form has the level fields; a normal registration creates a member of the form's level, and the WP user gets the level's role.
- A changed level (the Editor level, with the free level's hash) is rejected: no member, no WP user.
- With two different level values (`membership_level` and `swpm_membership_level`), the member doesn't get the Editor level or role.
- A level with the Administrator role never creates an administrator.
- Completing a paid registration (the link sent after the payment) saves the username, keeps the paid level and gives the level's role.
- A level hash made with the old method (MD5 of swpm_private_key_one and the level, from the 4.8.9 security report) is rejected.

**PayPal Standard: member ID (swpm_id) in the custom field** (Buy Now and subscription fixtures; the bad value is from the Patchstack stored XSS report)
- A logged-in member's ID: Buy Now renews that member; a subscription's first payment and signup are recorded with that member ID and renew the member; no other member is created for the PayPal email.
- A subscription refund marks the payment Refunded, deactivates the member and passes the member ID to `swpm_payment_refunded`; a cancellation updates the agreement record's status.
- An invalid member ID is ignored, never saved: Buy Now and a subscription signup give the buyer a member account and the record has its ID; a subscription payment is recorded with the subscription's member; a refund and a cancellation still find and deactivate the subscription's member.
- *Fails until the XSS fix:* the invalid member ID checks (the value is saved on the record, and no member is found or created).

## PHPUnit ([phpunit/tests/](phpunit/tests/))

**Plugin smoke test** (test-plugin-smoke.php)
- The plugin loads, activation creates the tables and saves the DB version and default settings.
- The main shortcodes are registered, the login form renders for a visitor, the registration form shortcode runs.
- The thank you page doesn't show a paid registration link based on the IP address.

**Refunds** (test-refunds.php)
- A full refund marks the transaction Refunded; partial refunds add up to a full refund.
- A repeated notification isn't counted twice; a late partial notification doesn't undo a full refund.
- A refund without a transaction record saves nothing.
- The Stripe first payment refund passes the checkout txn ID; the PPCP first sale refund passes the order ID (not after a free trial, not when saved more than a day after the checkout).

**Subscription records** (test-subscription-records.php)
- An agreement record with a translated status is found by its txn_type; an older one without a txn_type is found by its English status; renewal records aren't agreement records.
- PayPal Standard: the first payment isn't a recurring payment when the agreement status is translated; a later payment is.
- The payments list shows a translated agreement status with its color, and the other statuses.
- Deleting a payment record also deletes its payments table row.

**Payment member ID** (test-payment-member-id.php)
- The payments list's member profile link: a bad member ID is never printed; a member's link has their ID, also for a member found by the subscription ID.
- The payments list's status is escaped (also on an agreement record).
- `save_txn_record()`: a bad member ID from the custom field isn't saved (the record gets the subscription's member instead); a valid member ID is kept.
- *Fails until the XSS fix:* the bad member ID link, the status escaping and the two bad member ID `save_txn_record()` tests.

**Paid registration link** (test-paid-registration-link.php)
- A member created by the checkout is flagged; an existing member found by email or given by the swpm_id isn't.
- The registration code cookie finds its pending member; no cookie with a spoofed IP header finds nothing.
- The PayPal Standard token finds its pending member; a completed checkout doesn't hide a pending one from the same browser.

**Profile sync** (test-profile-sync.php)
- Names with apostrophes are saved without backslashes; email and password changes are synced.
- The sync doesn't run while the plugin updates the WP user; other member fields aren't changed.

**Registration level key** (test-registration-level-key.php)
- Activation creates a strong random key; an old uniqid() key is replaced when a registration form is shown.
- The form's level hash isn't an MD5 of the key and the level; a strong key is kept (forms already shown to visitors keep working).

**Role After Expiry** (test-role-after-expiry.php)
- The expiry role is applied and the level role restored; nothing changes for a level without the option.
- An administrator is never changed; admin account status changes apply and restore the roles.
- Activating a member that wasn't expired keeps its role.

## Security scripts ([security/](security/))

| Script | Checks |
|---|---|
| auto-login-after-registration.php | Auto-login after registration authenticates on the server; no password in the redirect URL; email recovery instructions stay visible |
| email-activation.php | The activation and resend emails: token storage, no password emailed, the recipient, no early admin notification |
| forced-wp-login-sync.php | The login/logout sync between the member and the WP user session |
| paid-registration-completion.php | Completing a paid registration keeps the validated role and level and uses up the registration code; an old MD5 level hash is rejected before anything is saved |
| paid-registration-link-browser.php | The paid registration link shows only in the browser that paid (cookie or PayPal token), never by IP address |
| plugin-check-database-queries.php | Database queries use placeholders (member filters, meta keys, protection lookups, events, the expiry batch update) |
| recurring-payment-email.php | The recurring payment email: headers, the custom message filter, the merge tags |
| stripe-checkout-browser-token.php | The Stripe checkout browser token cookie: several checkouts, the token limit, invalid values |

---

## Not covered yet (ideas for new tests)

Payments
- PayPal Standard subscriptions: a new buyer's signup and the paid registration link, renewal payments, a paid trial (also with a translated agreement status), partial refunds, eot. The signup, first payment, refund and cancel fixtures are in [fixtures/paypal-ipn/](fixtures/paypal-ipn/); the others need captured IPNs.
- PayPal PPCP: the checkout (onApprove), the webhooks, refunds (with faked PayPal API answers).
- Stripe: the checkout session and the return to the site after the first payment; partial refunds over the webhook.
- Braintree.
- The checkout's swpm_id custom value comes from the browser (step 4d).

Members and access
- Registration with the default registration page and the "Enable Free Membership" setting; registration with email activation, and the emails it sends.
- Form Builder add-on registration forms (the add-on is in another repository, without these tests).
- Login and logout, the login lockout, the IP address detection (step 4c).
- Password reset (link and generated password, the {password} merge tag).
- Content protection: posts, pages, categories, comments, the more tag, partial protection.
- Membership expiry (the daily cron) and account renewal.

Admin
- Adding, editing and deleting members; the bulk actions; the CSV export.
- Membership level settings.
