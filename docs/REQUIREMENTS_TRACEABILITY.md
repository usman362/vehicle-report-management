# Requirements traceability

**Updated:** 2026-10-27 · Milestones 0–4 complete, Milestone 5 in progress

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
| REG-02 | Registration field on the order form and checkout | §6.2 | `Storefront/OrderForm.php`, `Checkout/RegistrationField.php` | T01, T02 | done |
| PAGE-01 | Public pages with truthful pricing, sources and delivery disclosure | §5, §6 | `Storefront/Disclosures.php`, pages 15–21, `vehicle-report-theme` | T29, manual review | done — values come from settings; placeholders shown until the merchant supplies them |
| ORD-01 | Order metadata persisted through WooCommerce CRUD | §8 | `Checkout/OrderMetaSaver.php` | T01, T24 | done |
| PAY-01 | Verified gateway outcome only; no paid state from a thank-you visit | §7, §10 | `Domain/OrderLifecycle.php` | T05, T06 | done |
| PAY-02 | Duplicate webhook or refresh is idempotent | §7 | `Domain/OrderLifecycle.php` | T07 | done |
| SLA-02 | Delivery deadline counted in the merchant's own working hours | §12 | `Domain/BusinessHours.php` | `BusinessHoursTest` (10 cases) | done |
| FUL-01 | Staff fulfilment queue with filters and SLA age | §12 | `Admin/FulfillmentQueuePage.php`, `Domain/OrderRepository.php` | verified on the running site | done |
| FUL-02 | Private report storage, no public PDF URL | §11, §17 | `Reports/PrivateStorage.php`, `Reports/UploadValidator.php` | T09, T10 | done — files live above the web root |
| FUL-03 | Scoped, expiring download link | §7, §11 | `Reports/DownloadToken.php`, `Reports/DownloadController.php` | T12, T13, `DownloadTokenTest` | done |
| FUL-04 | Delivery email with retries and attempt log | §11 | `Notifications/EmailNotifier.php` | T14, T15 | done — retry path needs an integration test |
| FUL-05 | Replacing a report keeps the previous version | §11 | `Reports/ReportVersionRepository.php` | T27 | done |
| AUD-01 | Audit trail for every upload, delivery, resend, status change and refund | §12 | `Audit/AuditLogger.php`, `Admin/AuditLogPage.php` | `AuditRedactionTest`, verified | done |
| CAP-01 | Capability checks server-side for every admin action | §4, §9 | `Admin/CapabilityManager.php`, `Admin/FulfillmentActions.php` | T11, T30 | done — integration test outstanding |
| MAIL-01 | One confirmation email, carrying the registration and the promise | §11 | `Notifications/OrderEmailAdditions.php` | verified — captured message | done |
| PRIV-01 | Personal-data export and erasure, and suggested privacy-policy wording | §8, §17 | `Privacy/PersonalData.php` | T25 | done — erasure removes the report file, registration and contact snapshot; the order, its totals and the audit trail are retained |

## Scope exclusions (verified, not built)

| ID | Requirement | Spec | Test | Status |
|---|---|---|---|---|
| EXC-01 | No CRM, buyer–seller matching, seller leads or listings | §13, scope lock | T22, T23 | done (nothing exists) |
| EXC-02 | No phone-number harvesting or outbound SMS/marketing | §13 | T22 | done (nothing exists) |
| EXC-03 | No automatic DVLA/DVSA/HPI lookup in MVP | §14 | code review | done (nothing exists) |
| EXC-04 | No invented vehicle facts, reviews, prices or certifications | §17 | content review | ongoing |

## Outstanding

| ID | Requirement | Spec | Status |
|---|---|---|---|
| PAGE-02 | Sample report, about and legal pages | §5, §6.6, §6.7 | not started — copy needs the merchant and a UK legal review |
| QA-02 | WordPress integration tests for capability, delivery and tenancy paths | §20 | not started |
| PAY-03 | Gateway sandbox cases: decline, refund, outage | §10 | blocked on merchant Stripe test keys (PAY-01 decision) |

`T…` identifiers refer to the acceptance matrix in `PROJECT_MASTER_SPEC.md` §20
and are listed with their results in `TEST_CASES.md`.
