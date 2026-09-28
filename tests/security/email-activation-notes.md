# Email activation security regression checks

Run the standalone checks without WordPress or outbound email:

```sh
php tests/security/email-activation.php
php tests/security/paid-registration-completion.php
php tests/security/auto-login-after-registration.php
php tests/security/recurring-payment-email.php
```

The activation check executes the actual registration email method, public activation
and resend handlers, administrator resend helper, and activation storage helper.
WordPress services and the database are test doubles. The integration tests in
`tests/test-cases/classes/test-class.swpm-email-activation.php` additionally verify
the real options API, conditional SQL deletion and cache invalidation. Run them
with the repository's WordPress PHPUnit environment before release.

## Compatibility and intentional changes

- Existing unexpired activation links retain their token and original 24-hour
  lifetime. Expiry is now enforced on activation, even if WP-Cron has not run.
- Resends reuse a valid token. Expired or missing tokens are regenerated using
  WordPress's random password generator; activation URLs keep the same parameters.
  Conditional database writes prevent overlapping requests from overwriting a
  newer token or recreating a consumed record. A request that loses this race
  does not send an email with an obsolete token.
- The `swpm_email_activation_data` filter runs when creating a token. Addon
  metadata, including `fb_form_id`, survives resends. Token/timestamp fields are
  enforced after this filter, and password fields are removed.
- Public resend links remain usable without login or a WordPress nonce. Email
  always goes to the saved member address. Missing, active and throttled accounts
  receive the same response.
- Public resend limits default to one per member per minute and ten requests per
  source address per ten minutes. Administrator resends bypass these public
  limits. The filters `swpm_activation_resend_member_interval` (seconds) and
  `swpm_activation_resend_source_limit` (requests per ten minutes) allow tuning.
  Sites behind a trusted proxy can supply a verified client address through
  `swpm_activation_resend_source`; core does not trust forwarding headers.
- Registration and activation emails, including administrator notifications, no
  longer include passwords. Existing `{password}` / member `{plain_password}`
  placeholders display password-reset guidance. Custom email templates are not
  overwritten. Passwords remain available in memory for account creation and
  existing auto-login callbacks.
- A migration removes legacy activation password fields in batches of 100 per
  plugin initialization. It preserves tokens, timestamps and addon metadata, and
  avoids overwriting concurrently changed activation records. Sites with more
  than 100 records finish cleanup over subsequent requests. No handler decrypts
  these legacy passwords while cleanup is pending.
- Existing activation status, email-content, activation-link and redirect hooks
  remain in place. Custom integrations that deliberately overrode recipients via
  POST, changed token fields through the data filter, or read stored activation
  passwords must be updated.

The standalone tests do not establish full third-party addon compatibility or
exercise a live SMTP server. Test those integrations in staging before release.

## Form Builder and failure recovery

The updated Form Builder addon uses the core token helper, the persisted member
email, and password-reset guidance in registration email templates. Its custom
templates, custom field substitutions, and form ID metadata are preserved. Deploy
the matching core update before or alongside the addon: new Form Builder
registrations stop with an update message on older core versions, while profile
editing remains available.

Account creation and completion hooks now finish even if email preparation fails.
Core and Form Builder display an account-created message with recovery links;
automatic login and configured redirects do not hide that message. This detects
preparation failures, not downstream SMTP delivery failures.

The second cleanup pass also handles records saved after the original migration.
Its progress marker is autoloaded, and database errors defer another attempt for
five minutes. Invalid or expired activation links offer the existing rate-limited
resend endpoint.

To include the actual Form Builder implementation in the WordPress integration
suite, mount the addon into the test container and set
`SWPM_FORM_BUILDER_TEST_PATH` to that directory. For example, from the core repo:

```sh
docker compose run --rm --no-deps \
  -v "$(pwd)/../simple-membership-addons/swpm-form-builder:/form-builder:ro" \
  -e SWPM_FORM_BUILDER_TEST_PATH=/form-builder \
  -e TERM=xterm --workdir /app/tests --entrypoint composer swpm test
```

The optional addon tests verify the real email implementation, templates, token
storage, failure recovery, custom-field persistence, and completion hooks. Run
`php tests/security/form-builder-core-requirement.php /path/to/swpm-form-builder`
separately to verify the older-core compatibility guard.
