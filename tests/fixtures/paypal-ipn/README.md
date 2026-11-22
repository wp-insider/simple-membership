# PayPal Standard IPN fixtures

Anonymized copies of real PayPal Standard IPN messages (the "Post string" line of the plugin's debug log). The tests fill in the placeholders and POST the result to the IPN handler (`/?swpm_process_ipn=1`). PayPal's verification request is faked with the `pre_http_request` filter (answer `VERIFIED`, or `INVALID` to test the failure path).

| File | Source | Type |
|---|---|---|
| `web-accept-live.txt` | Live PayPal, 2026-11-18 | Buy Now (`txn_type=web_accept`) |
| `web-accept-sandbox.txt` | PayPal sandbox, 2026-11-17 | Buy Now (`txn_type=web_accept`, `test_ipn=1`) |
| `subscr-signup-live.txt` | Live PayPal, 2026-11-22 | Subscription signup (`txn_type=subscr_signup`), 1.00 a month, 2 payments, no trial. Bought while logged in, so `custom` has `swpm_id`. |
| `subscr-payment-live.txt` | Live PayPal, 2026-11-22 | First payment of that subscription (`txn_type=subscr_payment`). PayPal sent it about 8 seconds before the signup. |
| `subscr-refund-live.txt` | Live PayPal, 2026-11-22 | Full refund of that first payment (`payment_status=Refunded`, `reason_code=refund`, no `txn_type`). The handler marked the payment as refunded and set the member to inactive. |
| `subscr-cancel-live.txt` | Live PayPal, 2026-11-22 | Cancellation of that subscription (`txn_type=subscr_cancel`), sent after the refund. The member was kept (duration level), and the agreement record's `subscr_status` was set to `canceled`. |

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
| `{SWPM_ID}` | Subscription fixtures only. The logged-in member's ID (example: `35`). It sits inside the twice-encoded `custom` value, so URL-encode a test value twice before filling it in. |
| `{PARENT_TXN_ID}` | Refund fixture only. The `txn_id` of the payment being refunded. `{TXN_ID}` is the refund's own new transaction ID. |
| `{REFUND_AMOUNT}` | Refund fixture only. The refunded amount, negative (example: `-1.00`). Use the full payment amount for a full refund. |
| `{SUBSCR_ID}` | Subscription fixtures only. The PayPal subscription ID (example: `I-TEST0000000001`). Use the same value in the signup and its payments. The amount in the signup (`amount3`, `mc_amount3`) is `{AMOUNT}` too, and the test button needs a 1-month billing cycle with 2 payments to match `period3` and `recur_times`. |

Notes:
- The `custom` value is URL-encoded twice, exactly as PayPal sends it. After PHP decodes the request, the handler decodes it once more.
- Never add real emails, names, payer IDs, IP addresses or `verify_sign` values here. This repository is public.
