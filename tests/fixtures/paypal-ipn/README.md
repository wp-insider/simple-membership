# PayPal Standard IPN fixtures

Anonymized copies of real PayPal Standard IPN messages (the "Post string" line of the plugin's debug log). The tests fill in the placeholders and POST the result to the IPN handler (`/?swpm_process_ipn=1`). PayPal's verification request is faked with the `pre_http_request` filter (answer `VERIFIED`, or `INVALID` to test the failure path).

| File | Source | Type |
|---|---|---|
| `web-accept-live.txt` | Live PayPal, 2026-11-18 | Buy Now (`txn_type=web_accept`) |
| `web-accept-sandbox.txt` | PayPal sandbox, 2026-11-17 | Buy Now (`txn_type=web_accept`, `test_ipn=1`) |

## Placeholders

| Placeholder | Value |
|---|---|
| `{PAYER_EMAIL}` | The buyer's email, URL-encoded (example: `buyer%40example.com`). An existing member's email tests the "existing member" path. |
| `{RECEIVER_EMAIL}` | The PayPal email set on the test button, URL-encoded. A different value tests the receiver email check. |
| `{BUTTON_ID}` | The ID of the test PayPal Standard button. |
| `{LEVEL_ID}` | The membership level ID of that button. |
| `{TXN_ID}` | A unique PayPal transaction ID per test. Reuse one to test the duplicate check. |
| `{AMOUNT}` | The amount paid (example: `19.95`). Lower than the button price to test the price check. |
| `{CURRENCY}` | The currency code (example: `USD`). |
| `{TOKEN}` | The checkout token: 24 lowercase hex characters, the same value as in the test browser's `swpm_pp_checkout_tokens` cookie. |

Notes:
- The `custom` value is URL-encoded twice, exactly as PayPal sends it. After PHP decodes the request, the handler decodes it once more.
- Never add real emails, names, payer IDs, IP addresses or `verify_sign` values here. This repository is public.
