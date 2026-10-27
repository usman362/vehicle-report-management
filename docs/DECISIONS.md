# Decisions / open questions

**Updated:** 2026-10-27 | Version 1.2 (Milestones 0–1 recorded)

| Key | Current decision | Type | Owner / next action |
|---|---|---|---|
| STACK-01 | WordPress + WooCommerce + small custom plugin | Proposed | Confirm merchant prefers manageable WP administration |
| SCOPE-01 | Paid manual report fulfillment, no automatic API in MVP | Client-provided | Confirm precise report scope and provider rights |
| PAY-01 | Stripe via official WooCommerce extension if eligible | Proposed | Merchant verifies eligibility and supplies authorized connection |
| SCOPE-02 | Customer-facing website ends after paid report ordering/delivery | **Owner-confirmed** | Do not add buyer/seller introductions to storefront |
| CRM-01 | Any internal buyer/seller CRM is a **separate future project** | **Owner-confirmed** | No website CRM table, feature, menu, API or integration |
| SMS-01 | Seller outreach / UK SIM / SMS marketing not included in this website | **Owner-confirmed scope** | Separate requirements and compliance review if ever commissioned |
| REF-01 | Original reference live pages not inspected: 502 | Verified limitation | Provide screenshots/authorized staging for visual matching |
| PRICE-01 | GBP price unknown | Blocker | Merchant decides exact amount incl/excl VAT |
| SLA-01 | Email delivery window unknown | Blocker | Merchant approves realistic business hours and SLA |
| REPORT-01 | Provider/data licence and sample unknown | Launch blocker | Obtain written usage/redistribution rights |
| LEGAL-01 | Policies and registered merchant identity unconfirmed | Launch blocker | UK legal/policy review |
| BRAND-01 | Final company name, logo/images unknown | Blocker to visual signoff | Merchant supplies licensed assets |
| TEST-01 | Checkout style (Block vs Classic) TBD | Technical | Decide in Milestone 3 by testing the registration field against the installed WooCommerce 11.2.1 block checkout; classic is the fallback |
| STACK-02 | Pinned development versions: WordPress 7.1.3, WooCommerce 11.2.1, PHP 8.4.19 (plugin requires 8.1+), MySQL 9.6 | **Agent decision, M1** | Re-verify against the merchant's hosting before staging; upgrades tested on staging first |
| STACK-03 | High-Performance Order Storage enabled in development; plugin declares `custom_order_tables` and `cart_checkout_blocks` compatibility | **Agent decision, M1** | All order reads/writes go through the WooCommerce CRUD API; re-test on each WooCommerce major upgrade |
| ENV-01 | Local environment is PHP + MySQL + WP-CLI with no Docker; rebuilt by `scripts/setup-local.sh` | **Agent decision, M1** | Hosting choice for staging/production still open (see §24) |
| QA-01 | Quality gate is `scripts/check.sh`: `php -l`, WordPress Coding Standards (PHPCS/WPCS) and PHPUnit | **Agent decision, M1** | WordPress integration tests added with Milestone 3 |
| SEC-01 | Admin password and database settings are generated into `.env`, which is gitignored; no credential is committed | **Agent decision, M1** | Production secrets managed by the merchant's hosting, never in the repository |

Do not convert placeholders into facts. Record client signoff/date for decisions as they are confirmed.

## Launch blockers, in the order they are needed

1. **PRICE-01** — the GBP amount and whether VAT is included. Needed before any paid checkout is enabled.
2. **SLA-01** — the delivery window in business hours, and the hours themselves. It is printed before payment and drives the overdue indicator.
3. **REPORT-01** — which data sources staff may actually use, and written permission to redistribute what they produce. This decides what the site may promise.
4. **LEGAL-01** — registered trader identity, and UK-reviewed terms, privacy, cookie and refund policies.
5. **BRAND-01** — business name, logo and licensed imagery, for the visual sign-off.

Until 1–4 are resolved the site can be built and demonstrated on staging with
placeholders that are clearly marked and with the gateway in test mode, but it
cannot take a real payment.
