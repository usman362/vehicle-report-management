# Requirements traceability

**Updated:** 2026-10-28 · Milestones 0–5 complete, plus the feature parity pass against the reference site (`REFERENCE_COMPARISON.md`)

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
| PAGE-01 | Public pages with truthful pricing, sources and delivery disclosure | §5, §6 | `Storefront/Disclosures.php`, `content/pages/`, `vehicle-report-theme` | T29, manual review | done — values come from settings; placeholders shown until the merchant supplies them |
| PAGE-02 | Sample report, about and legal pages | §5, §6.6, §6.7 | `content/pages/{sample-report,about,terms,privacy-policy,cookies,refunds}.html`, `scripts/seed-content.php` | manual review | pages exist and are reproducible from the repository; the legal four carry an enforced draft notice until the merchant's adviser signs the wording off (LEGAL-01) |
| OPS-01 | Backups that include the report files, and a proven restore | §17, §21 | `scripts/backup.sh`, `scripts/restore.sh` | T21 | done — rehearsed by destroying the files and tables and restoring |
| A11Y-01 | Accessible, usable on a phone | §5, §20 | `vehicle-report-theme` (`theme.json` root padding, header/footer/checkout parts, `templates/page-checkout.html`) | T18, T19 | done — axe-core clean on every page; a full order placed at 375 px |
| PAGE-03 | Store actually reachable by customers | §5 | `Admin/StoreVisibility.php` | verified — notice shown while WooCommerce coming-soon mode is on | done |
| ORD-01 | Order metadata persisted through WooCommerce CRUD | §8 | `Checkout/OrderMetaSaver.php` | T01, T24 | done |
| PAY-01 | Verified gateway outcome only; no paid state from a thank-you visit | §7, §10 | `Domain/OrderLifecycle.php` | T05, T06 | done |
| PAY-02 | Duplicate webhook or refresh is idempotent | §7 | `Domain/OrderLifecycle.php` | T07 | done |
| PAY-04 | Decline, outage and refund handled correctly | §7, §10 | `Domain/OrderLifecycle.php`, `Domain/OrderRepository::is_fully_refunded()`, `Reports/DownloadController.php`, `wp-content/mu-plugins/vr-dev-gateway.php` | T08, T16, T28 | done — a full refund revokes the customer's links and a resend is refused; a partial refund does not |
| SLA-02 | Delivery deadline counted in the merchant's own working hours | §12 | `Domain/BusinessHours.php` | `BusinessHoursTest` (10 cases) | done |
| FUL-01 | Staff fulfilment queue with filters and SLA age | §12 | `Admin/FulfillmentQueuePage.php`, `Domain/OrderRepository.php` | verified on the running site | done |
| FUL-02 | Private report storage, no public PDF URL | §11, §17 | `Reports/PrivateStorage.php`, `Reports/UploadValidator.php` | T09, T10 | done — files live above the web root |
| FUL-03 | Scoped, expiring download link | §7, §11 | `Reports/DownloadToken.php`, `Reports/DownloadController.php` | T12, T13, `DownloadTokenTest` | done |
| FUL-04 | Delivery email with retries and attempt log | §11 | `Notifications/EmailNotifier.php` | T14, T15 | done |
| FUL-05 | Replacing a report keeps the previous version | §11 | `Reports/ReportVersionRepository.php` | T27 | done |
| REPORT-02 | A report states, source by source, what was checked and what was not | §6, §17 | `Domain/CoverageOutcome.php`, `Reports/Coverage.php`, `Admin/FulfillmentActions.php`, `templates/emails/report-ready.php` | `CoverageTest`, `tests/integration/cases/05-coverage.php` | done — staff cannot store a report with a source unanswered, and "could not be checked" must say why |
| I18N-01 | Every string translatable, with a template shipped | §9 | `languages/vehicle-report-orders.pot` (256 strings), `languages/vehicle-report-theme.pot` | `wp i18n make-pot` reports no wrong-domain strings | done |
| LIFE-01 | Removing the plugin leaves the business records | §17 | `uninstall.php` | verified on a throwaway install, both paths | done — full removal is opt-in via `VR_REMOVE_ALL_DATA` |
| AUD-01 | Audit trail for every upload, delivery, resend, status change and refund | §12 | `Audit/AuditLogger.php`, `Admin/AuditLogPage.php` | `AuditRedactionTest`, verified | done |
| CAP-01 | Capability checks server-side for every admin action | §4, §9 | `Admin/CapabilityManager.php`, `Admin/FulfillmentActions.php` | T11, T30 | done |
| QA-02 | Integration tests against a real WordPress and WooCommerce | §20, §26 | `tests/integration/` | T11, T14, T26, T30 | done — 126 assertions, run by `scripts/check.sh` |
| MAIL-01 | One confirmation email, carrying the registration and the promise | §11 | `Notifications/OrderEmailAdditions.php` | verified — captured message | done |
| PRIV-01 | Personal-data export and erasure, and suggested privacy-policy wording | §8, §17 | `Privacy/PersonalData.php` | T25 | done — erasure removes the report file, registration and contact snapshot; the order, its totals and the audit trail are retained |

## Reference parity (Milestone 6)

Built after comparing the reference site named by the client, page by page. The
numbered sections are the gaps recorded in `REFERENCE_COMPARISON.md`.

| ID | Requirement | Gap | Implementation | Test | Status |
|---|---|---|---|---|---|
| PKG-01 | Tiered packages, each promising a named set of checks | 3.1 | `Domain/Package.php`, `Domain/PackageRepository.php`, `Domain/VehicleCheck.php` | `PackageTest`, `VehicleCheckTest`, integration case 07 | done — three tiers, all unpriced until the merchant sets a price |
| PKG-02 | A page per package, with its own order form | 3.1 | `Storefront/Sections.php`, product base `/packages/` | verified at `/packages/{basic,silver,classic}/` | done |
| PKG-03 | The tier bought is recorded on the order and cannot be rewritten later | 3.1 | `Checkout/OrderMetaSaver.php`, `OrderMeta::TIER_SNAPSHOT` | integration case 07 | done |
| ORD-10 | Vehicle type on the order form and at checkout | 3.2 | `Domain/VehicleType.php`, `Checkout/RegistrationField.php` | integration case 07 | done — the merchant chooses which types are offered |
| ORD-11 | A VIN accepted where there is no current UK plate | 3.2 | `Domain/Vin.php`, `Checkout/RegistrationField.php` | `VinTest`, integration case 07 | done — either identifier satisfies checkout, neither does not |
| SUP-01 | Contact form that does not publish a mailbox to scrapers | 3.3 | `Storefront/ContactForm.php` | verified — captured message with Reply-To | done — honeypot and a per-visitor cooldown |
| SUP-02 | Phone, postal address and company details published | 3.3 | `Domain/Settings.php`, `Storefront/Sections.php` | settings screen, `/contact/` | done — a missing address now blocks go-live |
| SUP-03 | WhatsApp button and live chat, each off until configured | 3.3 | `Storefront/SupportWidgets.php` | integration case 07 | done — chat is named on the cookie page when it is switched on |
| CNT-01 | Every check published with what it cannot tell you | 3.4 | `Domain/VehicleCheck.php`, `templates/storefront/checks.php` | `VehicleCheckTest`, integration case 07 | done |
| CNT-02 | Delivery record published from this site's own orders | 3.6 | `Storefront/Sections.php` | manual — hidden below ten deliveries | done — never a marketing figure |
| CNT-03 | Customer reviews, merchant-entered, real only | 3.6 | `Domain/Settings::reviews()`, `templates/storefront/reviews.php` | integration case 07 | done — no reviews, no section |
| SEO-01 | Description, Open Graph and Twitter tags on every page | 3.5 | `Seo/MetaTags.php` | verified in page source | done — stands aside for a dedicated SEO plugin |
| SEO-02 | Organization and FAQ structured data | 3.5 | `Seo/StructuredData.php` | verified in page source | done — no ratings or awards are emitted |
| SEO-03 | Site icon, so a tab and a shared link are not blank | 3.5 | `content/brand/site-icon.png`, `scripts/seed-content.php` | verified | done — a plain placeholder for the merchant to replace |
| SEC-05 | Private report storage resolved before it is trusted | — | `Reports/PrivateStorage.php` | integration case 07 | done — found during this pass: an unresolved `ABSPATH` put the directory inside the web root |

## Scope exclusions (verified, not built)

| ID | Requirement | Spec | Test | Status |
|---|---|---|---|---|
| EXC-01 | No CRM, buyer–seller matching, seller leads or listings | §13, scope lock | T22, T23 | done (nothing exists) |
| EXC-02 | No phone-number harvesting or outbound SMS/marketing | §13 | T22 | done (nothing exists) |
| EXC-03 | No automatic DVLA/DVSA/HPI lookup in MVP | §14 | code review | done (nothing exists) |
| EXC-04 | No invented vehicle facts, reviews, prices or certifications | §17 | content review | ongoing |
| EXC-05 | No fabricated press endorsements, usage counts or competitor comparisons | §17 | `REFERENCE_COMPARISON.md` §6 | done (deliberately not built) |
| EXC-06 | No US vehicle-data claims (NMVTIS, DMV, title brands, auction records) on a UK service | §14, §17 | `REFERENCE_COMPARISON.md` §6 | done (deliberately not built) |

## Outstanding

| ID | Requirement | Spec | Status |
|---|---|---|---|
| PAY-03 | The same three cases against the merchant's real Stripe sandbox | §10 | blocked on merchant Stripe test keys (PAY-01 decision). This site's own behaviour on a decline, an outage and a refund is done and tested — see PAY-04 |

`T…` identifiers refer to the acceptance matrix in `PROJECT_MASTER_SPEC.md` §20
and are listed with their results in `TEST_CASES.md`.
