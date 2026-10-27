# Test cases

Each case from the specification's acceptance matrix (§20), how it is covered,
and its result. `automated` cases run in `./scripts/check.sh`; `verified` cases
were exercised against the running local site and the evidence is noted.

## Ordering and payment

| ID | Case | Coverage | Result |
|---|---|---|---|
| T01 | Valid UK registration, guest customer | verified — browser order 29 | Order created with `LT09 KZV` and `LT09KZV` |
| T02 | Empty or invalid registration | automated — `RegistrationNumberTest`, plus storefront validation | Rejected with an explanation; no order |
| T03 | Email invalid, XSS or SQL payloads | automated — `RegistrationNumberTest::test_it_rejects_input_that_cannot_be_a_plate` | `<script>` rejected; output escaped throughout |
| T04 | Price changed in the browser | verified | WooCommerce holds the price server-side; the form never submits one |
| T05 | Verified gateway callback | verified — order 29 | Paid once, queued once, deadline set |
| T06 | Fake thank-you URL | verified — a visit to the confirmation page without payment leaves the order pending | No fulfilment state change |
| T07 | Duplicate webhook | verified — order 12 | Second notification logged as `payment.duplicate_notification`, nothing re-queued |
| T08 | Card declined or cancelled | not yet run — needs a real gateway sandbox | Open: do it with the merchant's Stripe test keys |

## Report handling

| ID | Case | Coverage | Result |
|---|---|---|---|
| T09 | Staff upload a safe PDF | verified — order 12 version 1 | Stored above the web root, hash recorded |
| T10 | Staff upload a PHP file or an oversized file | automated — `UploadValidatorTest` | Rejected on magic bytes, extension and size; nothing stored |
| T11 | Unprivileged user calls the upload endpoint | code — capability check in `FulfillmentActions`; refusal written to the audit log | Needs an integration test to prove end to end |
| T12 | Recipient opens their signed link | verified | 200 with the PDF |
| T13 | Another order's link, or an expired one | verified + automated — `DownloadTokenTest` | Tampered 403, expired 410, revoked 403, undelivered 403 |
| T14 | Mail outage after upload | code — attempt recorded, retry scheduled with back-off, staff told after the maximum | Needs an integration test |
| T15 | Manual resend | verified — resend issues a fresh link and logs it | Same order, same report |
| T16 | Refund | code — recorded in the audit log, fulfilment history untouched | Needs a gateway sandbox |
| T17 | Staff cannot fulfil | verified — requires a written reason before the state changes | No fabricated report possible |

## Privacy, scope and the storefront

| ID | Case | Coverage | Result |
|---|---|---|---|
| T18 | Mobile checkout | partly verified — storefront at 375 px; full checkout pass outstanding | Open |
| T19 | Keyboard and screen reader | partly verified — labels, focus states and semantics in place; a full pass is outstanding | Open |
| T20 | Search engine visits a private URL | verified — `noindex` and `no-store` sent on report routes | No report content served |
| T21 | Backup restore | not yet run | Open: must include the private report directory |
| T22 | Search for CRM, seller lists, messaging or lead scraping | automated — `grep` over the plugin finds none; no such dependency, table, route or screen exists | Nothing found |
| T23 | Checkout and fulfilment without matchmaking | verified — orders 29 and 31 | No buyer introduction, CRM or promotional message anywhere |
| T24 | Order placed before a price or policy change | verified — price, delivery window and terms version are snapshotted on the order | Snapshot unchanged |
| T25 | Customer data export or deletion request | verified — export returned the registration, status, deadline and report hash for order 29; erasure on a test order deleted the stored file, cleared the registration and contact snapshot, marked the report row erased, left the order and its audit history in place, and turned a link already issued into a 403 | Export complete, erasure complete, financial record retained |
| T26 | Two customers order the same registration | code — orders are separate records with separate tokens and secrets | Needs an integration test |
| T27 | Report file replaced | verified — a second upload supersedes the first, both rows kept | History retained |
| T28 | Gateway unavailable | not yet run | Open |
| T29 | A claim with no data source behind it | verified — the storefront prints a visible placeholder instead of an invented figure, and ordering is blocked until sources are configured | No unsupported promise rendered |
| T30 | Staff role exports a customer list | code — export capability is not granted to fulfilment or support roles | Needs an integration test |

## Still to do

- Gateway sandbox cases (T08, T16, T28) once the merchant supplies Stripe test keys.
- WordPress integration tests for the capability and delivery paths (T11, T14, T26, T30).
- Full accessibility and mobile checkout pass (T18, T19).
- A restore rehearsal that includes the private report directory (T21).
