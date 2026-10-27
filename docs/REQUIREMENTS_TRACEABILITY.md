# Requirements traceability

**Updated:** 2026-10-27 · Milestones 0–1 complete

Each requirement from `PROJECT_MASTER_SPEC.md` is listed with the milestone that
delivers it, the code that implements it and the test that proves it. Status is
one of: `done`, `in progress`, `not started`, `blocked`.

## Environment and foundations (Milestone 1)

| ID | Requirement | Spec | Implementation | Test | Status |
|---|---|---|---|---|---|
| ENV-01 | Version-controlled repository with WordPress core excluded | §22 | `.gitignore` | `git status` clean after core download | done |
| ENV-02 | Local WordPress + WooCommerce sandbox, no Docker | §21 M1 | `scripts/setup-local.sh` | script run from clean state | done |
| ENV-03 | Pinned, documented versions | §21 M1 | `docs/DECISIONS.md` STACK-02 | `wp core version`, `wp plugin list` | done |
| ENV-04 | Secrets outside git | §17 | `.gitignore`, `.env` written by setup | `git ls-files` shows no `.env` | done |
| ENV-05 | Coding standards and static checks | §9 | `phpcs.xml`, `scripts/check.sh` | `vendor/bin/phpcs` | done |
| ENV-06 | Automated test harness | §26 | `phpunit.xml`, `tests/` | `vendor/bin/phpunit` | done |
| ENV-07 | Plugin activates without touching core | §9 | `wp-content/plugins/vehicle-report-orders/` | `wp plugin activate` | done |
| ENV-08 | HPOS compatibility declared and enabled in dev | §3, §8 | `vehicle-report-orders.php` (`FeaturesUtil::declare_compatibility`) | `wp option get woocommerce_custom_orders_table_enabled` | done |

## Ordering and fulfilment (Milestones 2–4)

| ID | Requirement | Spec | Implementation | Test | Status |
|---|---|---|---|---|---|
| REG-01 | Registration input accepts all legitimate UK formats; original and normalised forms both kept | §6.2, §8 | `Domain/RegistrationNumber.php` | `tests/Unit/RegistrationNumberTest.php` | done |
| REG-02 | Registration field on the order form and checkout | §6.2 | — | T01, T02 | not started |
| PAGE-01 | Public pages with truthful pricing, sources and delivery disclosure | §5, §6 | — | manual review | blocked (PRICE-01, SLA-01, REPORT-01) |
| ORD-01 | Order metadata persisted through WooCommerce CRUD | §8 | — | T01 | not started |
| PAY-01 | Verified gateway outcome only; no paid state from a thank-you visit | §7, §10 | — | T05, T06 | not started |
| PAY-02 | Duplicate webhook or refresh is idempotent | §7 | — | T07 | not started |
| FUL-01 | Staff fulfilment queue with filters and SLA age | §12 | — | manual + integration | not started |
| FUL-02 | Private report storage, no public PDF URL | §11, §17 | — | T09, T10, T12, T13 | not started |
| FUL-03 | Scoped, expiring download link | §7, §11 | — | T12, T13 | not started |
| FUL-04 | Delivery email with retries and attempt log | §11 | — | T14, T15 | not started |
| AUD-01 | Audit trail for every upload, delivery, resend, status change and refund | §12 | — | integration | not started |
| CAP-01 | Capability checks server-side for every admin action | §4, §9 | — | T11, T30 | not started |

## Scope exclusions (verified, not built)

| ID | Requirement | Spec | Test | Status |
|---|---|---|---|---|
| EXC-01 | No CRM, buyer–seller matching, seller leads or listings | §13, scope lock | T22, T23 | done (nothing exists) |
| EXC-02 | No phone-number harvesting or outbound SMS/marketing | §13 | T22 | done (nothing exists) |
| EXC-03 | No automatic DVLA/DVSA/HPI lookup in MVP | §14 | code review | done (nothing exists) |
| EXC-04 | No invented vehicle facts, reviews, prices or certifications | §17 | content review | ongoing |

`T…` identifiers refer to the acceptance matrix in `PROJECT_MASTER_SPEC.md` §20.
