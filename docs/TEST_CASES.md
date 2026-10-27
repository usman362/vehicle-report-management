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
| T11 | Unprivileged user calls the action endpoint | automated — `tests/integration/cases/01-capabilities.php`: a support agent calling claim and deliver is refused with `vr_forbidden`, and both refusals appear in the audit log | Refused and recorded |
| T12 | Recipient opens their signed link | verified | 200 with the PDF |
| T13 | Another order's link, or an expired one | verified + automated — `DownloadTokenTest` | Tampered 403, expired 410, revoked 403, undelivered 403 |
| T14 | Mail outage after upload | automated — `tests/integration/cases/02-delivery-retry.php`: the mailer is made to reject everything; each attempt is logged, a retry is really queued, the service gives up exactly once at the maximum, flags the order, ignores a late retry, and sends when the mailer recovers | Retried, escalated, recovered |
| T15 | Manual resend | verified — resend issues a fresh link and logs it | Same order, same report |
| T16 | Refund | code — recorded in the audit log, fulfilment history untouched | Needs a gateway sandbox |
| T17 | Staff cannot fulfil | verified — requires a written reason before the state changes | No fabricated report possible |

## Privacy, scope and the storefront

| ID | Case | Coverage | Result |
|---|---|---|---|
| T18 | Mobile checkout | verified — a complete order placed at 375 px: registration entered, block checkout filled, order 71 paid, with the registration, deadline and terms version captured. No horizontal overflow on any page, and the registration field no longer escapes its card | Orderable on a phone |
| T19 | Keyboard and screen reader | verified — axe-core 4.10 run against all 13 pages plus cart, checkout and the confirmation page: no violations in this site's own markup. Three landmark faults and a missing checkout `h1` were found and fixed | Clean |
| T20 | Search engine visits a private URL | verified — `noindex` and `no-store` sent on report routes | No report content served |
| T21 | Backup restore | verified — `scripts/backup.sh`, then every report file deleted, all three plugin tables dropped and an order removed; `scripts/restore.sh` brought back 7 orders, 3 report files, 193 rows across the plugin tables, and the download link a customer already held returned the PDF again. The rehearsal also found that the dump carried a `GTID_PURGED` statement the same server refuses on import, so the backup was unrestorable until that was fixed | Restored and proven |
| T22 | Search for CRM, seller lists, messaging or lead scraping | automated — `grep` over the plugin finds none; no such dependency, table, route or screen exists | Nothing found |
| T23 | Checkout and fulfilment without matchmaking | verified — orders 29 and 31 | No buyer introduction, CRM or promotional message anywhere |
| T24 | Order placed before a price or policy change | verified — price, delivery window and terms version are snapshotted on the order | Snapshot unchanged |
| T25 | Customer data export or deletion request | verified — export returned the registration, status, deadline and report hash for order 29; erasure on a test order deleted the stored file, cleared the registration and contact snapshot, marked the report row erased, left the order and its audit history in place, and turned a link already issued into a 403 | Export complete, erasure complete, financial record retained |
| T26 | Two customers order the same registration | automated — `tests/integration/cases/03-order-isolation.php`: separate secrets, links and files; neither token verifies against the other order, and revoking one leaves the other working | Fully isolated |
| T27 | Report file replaced | verified — a second upload supersedes the first, both rows kept | History retained |
| T28 | Gateway unavailable | not yet run | Open |
| T29 | A claim with no data source behind it | verified — the storefront prints a visible placeholder instead of an invented figure, and ordering is blocked until sources are configured | No unsupported promise rendered |
| T30 | Staff role exports a customer list | automated — `tests/integration/cases/01-capabilities.php`: none of the three staff roles holds `export`, `list_users`, `edit_users`, `manage_woocommerce`, `manage_options` or either personal-data capability | No route to a customer list |

## Pages and store visibility

| Case | How it was checked | Result |
|---|---|---|
| Legal pages carry a draft notice until signed off | verified — the terms, privacy, cookie and refund pages each render the notice; ticking **Legal pages → Copy reviewed** on the settings screen removed it from all four at once, and clearing the box brought it back | Notice is driven by the setting, not by page copy |
| Sample report page invents nothing | verified — with no example configured the page shows a visible placeholder; supplying a URL replaced it with a link | No fabricated report is shipped |
| Pages are reproducible from the repository | verified — `wp eval-file scripts/seed-content.php` created or refreshed all 13 pages and set the front, privacy and terms page options | A fresh install comes up with the same site |
| Customers can actually reach the store | verified — WooCommerce was found in coming-soon mode, which serves the shop, cart and checkout only to signed-in administrators; the plugin's screens now warn while it is on, and `setup-local.sh` opens the store | The trap is visible instead of silent |

## Accessibility

Audited with axe-core 4.10.2 in the browser, on every storefront page and on
cart, checkout and the order confirmation. What it found, and what was done:

| Finding | Where it came from | Fixed by |
|---|---|---|
| Duplicate `banner` and `contentinfo` landmarks | the theme's header and footer parts each wrapped their content in a second `<header>` / `<footer>` inside the one the template part already renders | plain `<div>` wrappers in `parts/header.html` and `parts/footer.html` |
| `aria-hidden` element containing focusable controls | WooCommerce hooks a mini-cart drawer into block-theme headers | the mini cart is unhooked in `functions.php`: this store sells one report from an order form, so a basket drawer adds a step rather than removing one |
| Checkout had no `h1`, a nested `<header>` and no footer | WooCommerce's own `page-checkout` template and `checkout-header` part | theme overrides: `templates/page-checkout.html` and `parts/checkout-header.html` |
| Content touching both screen edges below 420 px | the theme set no root padding | `useRootPaddingAwareAlignments` with a `clamp(1rem, 4vw, 2rem)` gutter in `theme.json` |
| The registration field escaping its card on a phone | `width: 100%` on a `content-box` input with padding and a 2px border | `box-sizing: border-box` on `.vr-registration-plate input` |

What remains are two findings in WordPress's own admin bar and skip link, which
only signed-in administrators ever see, and one on a WooCommerce loading
skeleton that is replaced as soon as payment methods load.

## How these were run

- `./scripts/check.sh` runs the lot: PHP syntax, WordPress coding standards,
  the unit suite (no WordPress needed) and the integration suite.
- The integration suite is `wp eval-file tests/integration/run.php`. It runs
  inside the real WordPress and WooCommerce, creates its own orders, staff
  users and report files, and deletes them again whether or not it passes.
  It is skipped, with a reason printed, where there is no local site.

## Still to do

- Gateway sandbox cases (T08, T16, T28) once the merchant supplies Stripe test keys.
