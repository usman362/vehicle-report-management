# UK Vehicle History Report Ordering Website — Master Product & Engineering Specification

**Prepared for:** Claude / Claude Code implementation handoff  
**Version:** 1.1 | **Research date:** 27 October 2026 | **Scope updated:** 27 October 2026  
**Document status:** IMPLEMENTATION SPECIFICATION — contains verified reference facts, client-provided requirements, implementation proposals, and unresolved decisions.  
**Reference concept:** https://starwoodmotor.co.uk/  
**Working name:** Vehicle Report UK (placeholder only; NOT the real business name)

> **READ THIS FIRST: REFERENCE VERIFICATION LIMITATION**
>
> We attempted to access the reference website directly on 27 October 2026, but the home page returned **502 Bad Gateway** and the publicly mentioned `/refund_returns/` page was also unavailable. A live end-to-end audit (all screenshots, precise page layout, forms, actual prices, checkout, email behavior, and source code) **was not possible**. Public third-party search listings describe the domain as **“Starwood Motor - Inspection Done Right!”**, with a vehicle-history report business concept, but do **not** verify all features. Do not claim or implement the exact same design based on conjecture. Obtain owner-supplied screenshots or a working test URL before a pixel-perfect visual build. This specification instead creates a complete, professional product matching the *client's stated legitimate functionality*.
>
> Site trust-rater services flag the domain as new/low-trust. Those are third-party caution signals, not legal findings. Do not imitate brand, copy assets, or falsely claim affiliation.

---

## Latest scope lock (27 October 2026) — binding implementation instruction

**BUILD:** Public UK vehicle-history report sales website, registration form, accurate product/pricing details, GBP checkout, verified payment, customer confirmation, staff order list and manual authentic PDF delivery by email. Include transactional support/refund and essential privacy/security features.

**DO NOT BUILD:** Buyer/seller accounts, buyer matching, seller leads, seller-contact scraping, listing aggregator, phone-number outreach, SMS campaign gateway, sales CRM or any background CRM sync. The owner specifically stated these do not belong in the public report-selling platform. The separately discussed future CRM is **not commissioned by this specification**.

**Operational distinction:** Generating real value through manual research, licensed data, accurate source citations, analysis and report design is permitted. Charging for improved presentation alone must not be represented as exclusive proprietary checks; every paid promise must be deliverable.

---

## 0. Source classification and precedence

All statements in this document belong to one of four categories:

- **[REF-INDEXED]**: supported only by public search/third-party indexed descriptions of starwoodmotor.co.uk, not by direct live-page inspection.
- **[CLIENT]**: requirements as stated in three Urdu-language voice-note transcripts provided in the conversation (plus later clarification: use the best technology, not necessarily Laravel).
- **[OFFICIAL]**: supported by cited WooCommerce, UK government, ICO, Stripe, or messaging provider documentation.
- **[PROPOSED]**: product/architecture choice recommended here; client has not yet signed off.

Priority for code decisions: **lawful, truthful behavior and data rights > verified client requirements > authorized design assets > reasonable engineering defaults.** If two sources conflict, document it in `docs/DECISIONS.md` and obtain product-owner approval before irreversible changes. Never transform [PROPOSED] into [CLIENT] or [REF-INDEXED] into direct observation.

### Verified vs unknown reference-site evidence

| Item | Evidence | Confidence |
|---|---|---|
| Domain: `starwoodmotor.co.uk` | URL supplied by project owner | High |
| Title/tagline “Starwood Motor - Inspection Done Right!” | Search-engine / third-party listing | Moderate, indexed only |
| Vehicle-history report concept | Search-engine/third-party descriptions | Moderate, indexed only |
| Lookup by vehicle details | Third-party description | Moderate, indexed only |
| Paid report packages | Third-party description | Moderate, indexed only |
| Exact pricing, color codes, typography, hero image, pages, wording | Direct site inaccessible | **Unknown** |
| Actual vehicle data provider and report contents | Not verified | **Unknown** |
| Real payment gateway/flow | Not verified | **Unknown** |
| Source technologies WordPress/Elementor/WooCommerce, etc. | Previously asserted based on secondary scanners; no current first-party verification | **Unconfirmed** |
| Real order dashboard, SMS or email implementation | Not inspected | **Unknown** |
| Ownership, authorization, licence, customer endorsements | Not validated | **Unknown** |

**Do not build an “exact clone” until design screenshots/assets have been lawfully supplied.** A similar visual structure with original branding/content is the target for initial development.

---

## 1. What the client actually requested [CLIENT]

Read this scope carefully; the client does **not** want a complex real-time vehicle history service at launch.

1. A website offering a **paid vehicle history report order** for UK vehicle registration numbers.
2. A strong professional public landing page styled in the general category of the reference site.
3. Vehicle registration input with basic validation.
4. Customer email capture and checkout in GBP.
5. An eligible payment gateway; after verified payment, show a confirmation that the report will arrive at their email address.
6. A simple back-office order list showing vehicle registration, customer email, amount, payment status, timestamp, and delivery status.
7. **Manual report fulfillment**, not automatic generation, in the initial version.
8. Staff can use **authentic, permissioned sources** to prepare each order's report and upload/send it through the store.
9. Website goes live once core ordering/payment/delivery behavior is tested.
10. The owner explicitly confirmed that **this website ends at paid vehicle-report ordering and delivery**. There are **no buyer matches, introductions, listings, seller profiles, lead lists, outreach sequences, CRM features, or transfer/sync features** on this platform.
11. Separately, the owner described a possible **internal, future CRM** for independently managing contacts and legitimate buyer–seller introductions. That is a **different project, repository, budget, requirements process and compliance review**, not Phase 2 of this report-selling website. There is **no hidden or public CRM integration in this MVP**.
12. UK business messaging, listing research and outreach discussed in voice notes are **off-platform operational topics**; do not implement their collection, automation, or messaging in this website. Refer to section 13 for boundaries.
13. Owner confirmed Laravel is **not mandatory**: select a technology appropriate to the report-ordering MVP.

**Clarification:** “No backend” in the voice note means “do not build a sophisticated automatic reporting engine.” A payment/order/admin/delivery website necessarily has a backend, which is supplied by WooCommerce in this proposal.

### Explicit two-system boundary — latest user clarification [CLIENT]

**System A: THIS paid vehicle-report website (build now).** A visitor enters a vehicle registration, reviews the real product scope and delivery expectations, pays, receives an accurate payment receipt, and later receives an authentic value-added report after manual staff review. The admin works with order/payment/fulfillment/support only. Customer-facing screens do **not** represent buyer/seller introductions or claim a buyer has been found.

**System B: SEPARATE internal relationship CRM (not part of this build).** If separately commissioned, a team may manage real contacts, lead provenance, actual buyer interest, permitted communication, consent and introductions. No CRM screens, database tables, APIs, tracking, lead imports, message-sending software, sales scraping, role-change displays, or CRM branding may be added to System A. Future CRM access, if ever wanted, requires a separate written contract, transparent data disclosures and permission review. Architectural isolation cannot be used to conceal misleading business representations.

**The two processes have different operational purposes.** Do not silently design a single ecosystem; the report website must be fully functional by itself. Do not add a 'Find a buyer' or 'Get connected' CTA, promise of buyer introductions, or post-payment redirect to a CRM.

### Behavior explicitly excluded from this product

The voice notes also described a plan to impersonate a prospective buyer, obtain listing seller phone numbers, pressure sellers to buy a particular report, then disengage. That sequence is **not a supported product workflow**. Also excluded: scraping private seller numbers for unsolicited marketing, automated spam, misleading report-source claims, and passing free/public data off as exclusive licensed checks without appropriate explanation or rights. This is a **product requirement and acceptance constraint**, not an optional disclaimer. We can implement legitimate customer acquisition, genuine sale inquiries, consent-based marketing and authorized data/report delivery.

---

## 2. Goals and success criteria

### Business goals

- Launch a credible standalone UK vehicle report **ordering and manual fulfillment** service.
- Allow any visitor to place an order with minimal friction; **guest checkout is the default**.
- Capture accurate vehicle identifiers and customer contact information.
- Take secure **one-time GBP payments** through an eligible business merchant account.
- Give buyer realistic and prominently disclosed delivery timing and report limitations **before payment**.
- Make fulfillment, refunds, failed-payment handling and customer service visible to staff.
- Allow paid ad traffic and opted-in outreach to convert through a truthful site experience.

### Measurable MVP success / acceptance

- One buyer can successfully pay in test mode and receive a confirmation/order reference.
- Only verified paid orders enter the “ready to fulfill” queue.
- Staff can find the order by reference, vehicle registration, email and date.
- Staff can upload one authentic PDF to an order (or add an authorized document link), publish delivery and trigger the correct email.
- A recipient can access only **their own** report using a controlled, expiring link; no public file URL.
- Manual fulfillment status has a timestamp and actor record.
- User can request help/refund and staff can process it without editing historical payment facts.
- Duplicate payment callbacks or refreshes cannot generate duplicate orders or double-deliver.
- There is **no seller outreach, CRM, phone-number scraping, or outbound marketing/SMS system** in the MVP codebase; report delivery/transactional emails still work.
- Responsive pages, accessible controls, security scans and standard launch checklist pass.

### Non-goals for version 1

- Live DVLA/DVSA/HPI/carVertical integrated checks.
- AI-generated factual vehicle report content.
- Automatic report procurement, VIN decoding or real-time vehicle specs.
- Vehicle valuation, finance, theft, accident or write-off checks *unless actually supported by an approved data source.*
- Multi-country / multi-currency commerce, subscriptions, B2B reseller platform.
- Any CRM, buyer–seller matchmaking, seller onboarding, referral/matching dashboards, contact importing, SMS campaigns, or scraped seller phone numbers.
- Impersonation, deceptive seller outreach, fake testimonials or invented partner logos.

---

## 3. Technology decision [PROPOSED]

### Preferred MVP: WordPress + WooCommerce + an isolated small custom plugin

**Why:** proven e-commerce, checkout, guest orders, email events, payment integrations, back-office orders, and easy business-owner content administration. This directly matches manual-fulfillment scope and avoids unnecessary bespoke framework complexity. The custom plugin must contain all domain-specific logic; avoid putting business logic in theme snippets.

| Concern | Proposal | Notes |
|---|---|---|
| CMS | Supported, up-to-date WordPress version | Check current stable + plugin compatibility at implementation |
| Commerce | Supported WooCommerce version, HPOS compatible | Use WooCommerce CRUD methods, avoid direct order tables |
| Theme | Lightweight custom child theme or custom block theme | Avoid heavy page builders unless required for client content editing |
| Design | Responsive semantic HTML/CSS, Gutenberg blocks where appropriate | Original copy and licensed photography |
| Domain feature | `vehicle-report-orders` custom WordPress plugin | Registration field, per-order fulfillment meta, queue UI, secure delivery |
| Payments | Official WooCommerce Stripe plugin **if merchant eligible**; else approved alternative | Merchant owns gateway account; never fake identity |
| Email | Authenticated SMTP / transactional provider | DKIM/SPF/DMARC; log results, retry failures |
| Report storage | Protected WooCommerce-compatible storage or private object storage | Never public attachment URL |
| External CRM / outbound SMS | **Not included in this product** | Separate project if ever authorized; no plugin code or dependencies |
| Server | Managed WP hosting with staging, backups, TLS, cron | No reliance on random cheap shared hosting for money flows |
| Monitoring | Uptime, error logs, payment and email alerts | Avoid PII in logs |

**Alternative A:** Shopify + custom checkout extension/order metafields if faster to launch and merchant accepts platform fees/limitations.  
**Alternative B:** Laravel + Filament when manual fulfillment becomes a large custom workflow or multi-source report platform.  
**Alternative C:** Next.js with hosted commerce/backend only for strong headless needs.  
No stack pivot without documented feature/cost/time reason.

### WooCommerce product model

- Offer a **virtual service product** named e.g. “Vehicle History Report — Manual Review”.
- Do **not** label it instantly downloadable until file exists; virtual **not automatically downloadable** during payment stage so staff can fulfill later.
- Customer knows report is delivered manually within a configured delivery window (e.g., “within X business hours,” with X approved by client and achievable).
- Start with **one product / one price**; packages only if client defines actual differing authorized data coverage.
- Do not display “instant report” or guarantees of specific checks without delivery capability.
- Tax/VAT amount and inclusion must be set based on merchant tax advice; never assume.
- Orders must remain Processing or a dedicated fulfillment state until manual delivery; test whether the current WooCommerce version auto-completes virtual products in the configured setup.

---

## 4. User roles and permissions

| Role | Allowed |
|---|---|
| Visitor | View content, enter registration, pricing, request report, contact support |
| Guest purchaser | Order, pay, receive notices, view report through securely scoped link |
| Registered customer (optional) | View own order history and authorized downloads |
| Fulfillment staff | View minimal assigned orders, add source notes, upload report, mark delivered, resend delivery |
| Support agent | Find customer order, update support/refund request, view limited transaction metadata |
| Store manager | Manage orders, prices, refunds through gateway, reporting configuration |
| Administrator | Full settings and permissions, but never view raw card numbers |

**Least privilege:** staff must not need full WordPress administrator to upload a PDF. Audit who delivered or reissued a report. Enforce capability checks server side for every admin action.

---

## 5. Sitemap: public, transactional, admin

### Public pages (MVP)

1. `/` — Home
2. `/vehicle-report/` — What the report offers, eligibility, input form and limitations
3. `/pricing/` — Full GBP total, tax and delivery window, included/unsupported checks
4. `/how-it-works/` — Purchase and manual delivery process
5. `/sample-report/` — Sample **clearly marked illustrative** PDF/screenshots, never presented as authentic vehicle record
6. `/about-us/` — Accurate legal trader name, brand, team details if provided
7. `/contact/` — Real support details and form
8. `/faq/` — Honest coverage, timings, refunds, how to locate registration, privacy
9. `/terms-and-conditions/`
10. `/privacy-policy/`
11. `/cookie-policy/` and consent controls if applicable
12. `/refund-and-cancellation-policy/`
13. `/report-data-sources/` — Authorized sources, scope and missing-record limitations

### Transactional

14. `/order-report/` — Registration entry, product selection, pricing summary
15. `/checkout/` — Secure WooCommerce checkout
16. `/checkout/order-received/` — Paid or pending state depending on verified gateway result
17. `/order-status/` — Order lookup via signed token or verified email-based link
18. `/my-account/` — Optional, only if explicitly enabled
19. `/support/order/` — Submit request tied to order

### WP Admin

- WooCommerce > Orders
- Vehicle Reports > Fulfillment Queue
- Vehicle Reports > Report Settings
- Vehicle Reports > Templates & Email Settings
- Vehicle Reports > Data Source Attribution
- Vehicle Reports > Audit Log
- Vehicle Reports > Support/Refund Queue (can reuse helpdesk integration)
- **No Marketing CRM / Seller Leads / Messaging module** in this website

**SEO technical:** sitemap, robots directives, index public educational pages, `noindex` all checkout/status/personal/report pages, canonical tags, alt text, structured data only for factually supported data.

---

## 6. Page-by-page user-interface requirements

### 6.1 Home

**Above the fold:** logo; clear UK-focused headline; concise truthful service subtitle; UK registration number input; primary CTA “Order a vehicle history report”; real delivery-time disclosure; clear GBP price link. If there is no live lookup, CTA wording must NOT imply instant vehicle result. Prefer “Continue to Report Order” instead of “Generate Instant Report”.

**Sections, in order:** hero / value proposition; process 1-2-3; exactly what is included; example report preview; pricing and order CTA; limitations/data source disclosure; help/FAQ; contact trust details; legal footer. Testimonials only if documented and permissioned. No invented guarantees, certifications or “thousands of happy customers.”

### 6.2 Order/report form

Fields:

- `registration_number` (required for UK plates; min/max length/format checks allow legitimate special cases; normalize spaces/case, preserve original input for audit).
- `VIN` (optional and **not required** for launch; show only when actual capability/use case supports it).
- `customer_email` (required).
- `customer_name` (optional or required if payment merchant requests it).
- `phone` (omit by default; collect only if the payment provider requires it or client approves a legitimate order-support need; never for lead harvesting).
- `package/product` (server-controlled ID and price).
- `terms_acceptance` (required with explicit acceptance version and timestamp).
- `digital_content_early_delivery_consent` (conditional if applicable, per counsel-approved flow).
- **No seller lead acquisition or SMS/email marketing subscription fields in this MVP.** Transactional order updates and report delivery use the order email.

UX: trim, normalize, preserve raw value; inline errors; no silent overwrites; retain flow on recoverable payment failure; privacy explanation. Avoid over-restrictive UK plate regex excluding cherished/private/old formats. Reg/VIN form does **not** query a genuine history service in MVP.

### 6.3 Pricing

Price must be real, approved, all mandatory fees identified, GBP currency, whether VAT included made clear. Show exact promised included outputs and **manual delivery SLA**. Do not invent tier distinctions or time-limited discounts. Price and package details snapshot on order creation.

### 6.4 Checkout

Guest purchase enabled; payment field hosted/tokenized by supported gateway; final charge and currency visible; order summary contains input registration; consent separate; failed/canceled session has non-alarming recovery. No default subscription or hidden recurring billing.

### 6.5 Order thank-you

Only on verified payment success show: “Payment received. Your report request has been received and we will email the report to [masked email] within [stated timeframe].” Provide order number, support link, expected handling time, receipt link. If pending, show “Payment awaiting confirmation,” never falsely claim paid. Do not leak unmasked email if link is shared.

### 6.6 Sample report

Only fictional/sample registration and data; conspicuous demo watermark; show report sections that actually can be fulfilled with provider agreements. Explain “not all checks are available for every vehicle” with source/data coverage footnote.

### 6.7 About, Contact, policies

Actual registered trader details and accessible support. No placeholder fake office address, “licensed data provider” badges without contract, copied UK company details, copyright-infringing images or unattributed report PDFs.

### 6.8 Responsive/a11y

Mobile: vehicle input no horizontal overflow; body 16px+; accessible labels and clear focus indicators; keyboard checkout; contrast; meaningful button text; responsive at 320, 375, 768, 1024, 1440 px; avoid blocking login popups. Target WCAG 2.2 AA as a design/testing goal.

---

## 7. Core order journey and state machine

```text
VISITOR
  -> view report details and price
  -> enter UK registration + email
  -> server validates and creates pending WooCommerce order
  -> redirect to eligible hosted/gateway checkout
  -> gateway outcome:
       cancelled/failed -> payment_failed or pending, show retry
       succeeded -> VERIFIED webhook or WooCommerce gateway confirmation
  -> mark PAYMENT_PAID, queue report fulfillment
  -> email payment/order confirmation
  -> staff opens fulfillment queue and checks ordered package and registration
  -> staff obtains valid/licensed information using permitted workflow
  -> staff uploads final honest PDF + selects source/coverage metadata
  -> system checks file/security + authorizes delivery action
  -> mark REPORT_DELIVERED, send secure link by email
  -> customer opens report -> optional re-download until expiry
  -> support/refund/reissue process as needed
```

### Payment vs fulfillment states (keep distinct)

**Payment status:** `pending`, `authorized` (if applicable), `paid`, `failed`, `cancelled`, `partially_refunded`, `refunded`, `disputed`.

**Report fulfillment status:** `not_ready`, `awaiting_staff`, `in_progress`, `needs_customer_info`, `report_uploaded`, `delivered`, `delivery_failed`, `cannot_fulfill`, `cancelled`.

Do not conflate `paid` with `delivered`. Use WooCommerce built-in order lifecycle for payments and a namespaced custom fulfillment meta/state with auditable transitions.

### Required transitions/invariants

- `awaiting_staff` only after verified paid order.
- If paid order has missing registration/email, `needs_customer_info` and staff sees a warning.
- `delivered` requires valid private file/reference, staff action or scheduled approved delivery, plus delivery attempt record.
- Failed email is `delivery_failed` (may still have valid secure customer portal link), retryable.
- Refund does not erase order/source/audit history; access revocation policy configurable.
- Repeated gateway notification results in idempotent update, not repeated charge/email.
- Download token is cryptographically random, unguessable, expires, and scoped to specific order/report.

---

## 8. Domain data model / storage

Use **WooCommerce standard customer/order tables and CRUD API** (HPOS-compatible). Do not create redundant `orders`/`payments` tables in WP unless necessary. Add a domain extension with protected metadata or dedicated small tables for fulfillment / source attribution / audit events, chosen according to query needs.

### Per-order fields (namespace `vr_`)

- `vr_original_registration` (entered string)
- `vr_normalized_registration` (uppercase alphanumeric; preserve display format separately)
- `vr_vin` (only if collected)
- `vr_customer_email_snapshot`
- `vr_package_snapshot_json`
- `vr_delivery_sla_snapshot`
- `vr_terms_version_accepted`, `vr_terms_accepted_at`
- `vr_digital_content_consent_version`, `vr_digital_content_consent_at` (if applicable)
- `vr_payment_verified_at` (from trusted gateway)
- `vr_report_status`, `vr_assigned_staff_id`, `vr_report_uploaded_at`, `vr_delivered_at`
- `vr_report_private_storage_key` (not public URL)
- `vr_report_sha256` (integrity)
- `vr_report_source_summary` (genuine source list + as-of timestamp)
- `vr_report_coverage_notes` (limitations)
- `vr_delivery_attempt_count`, `vr_last_delivery_error_code`
- `vr_refund_reason`, `vr_support_case_id` (if used)

### Recommended dedicated tables

- `wp_vr_audit_events`: id, order_id, actor_id(nullable), action, from_state, to_state, metadata_redacted_json, created_at
- `wp_vr_delivery_attempts`: id, order_id, report_version, channel(email), status, provider_message_ref, retry_count, failure_code, created_at
- `wp_vr_report_versions`: id, order_id, file_storage_key, sha256, prepared_by, source_summary, coverage_summary, status, created_at

### Retention / deletion

Create merchant-owned data retention schedule approved by privacy adviser; provide DSAR export/erasure workflows where required and preserve mandatory financial records when legally required. Do not treat report lookup identifiers as harmless: UK vehicle registrations can be personal data in context. Encrypt secrets, avoid raw payment details.

---

## 9. Custom WordPress plugin design

Folder structure recommendation:

```text
wp-content/plugins/vehicle-report-orders/
├── vehicle-report-orders.php
├── readme.txt
├── composer.json                   # optional, only if needed
├── src/
│   ├── Plugin.php
│   ├── Activation.php
│   ├── Domain/
│   │   ├── RegistrationNumber.php
│   │   ├── FulfillmentStatus.php
│   │   └── ReportService.php
│   ├── Checkout/
│   │   ├── RegistrationField.php
│   │   ├── OrderMetaSaver.php
│   │   └── TermsConsent.php
│   ├── Admin/
│   │   ├── FulfillmentQueuePage.php
│   │   ├── FulfillmentActions.php
│   │   ├── SettingsPage.php
│   │   └── CapabilityManager.php
│   ├── Reports/
│   │   ├── UploadHandler.php
│   │   ├── PrivateStorage.php
│   │   ├── DownloadController.php
│   │   └── ReportVersionRepository.php
│   ├── Notifications/
│   │   ├── EmailNotifier.php
│   │   └── DeliveryRetryJob.php
│   ├── Audit/
│   │   └── AuditLogger.php
│   └── Privacy/
│       ├── Eraser.php
│       └── Exporter.php
├── templates/
├── assets/
└── tests/
```

**Developer implementation rules:**

- Namespaced PHP classes, modern WP practices, no deprecated APIs.
- Use WC CRUD/get_meta/update_meta rather than SQL on `wp_posts` for orders.
- State HPOS compatibility where actually tested; don't declare blindly.
- Support selected **Checkout Block** or **classic checkout** end-to-end; do not mix incompatible field hooks. Official current Checkout Block extension supports `woocommerce_register_additional_checkout_field()` (see source).
- Plugin settings for staff roles, email templates, report SLA, policy version, source labels.
- Nonces and capability checks for admin actions; `sanitize_*`, output escaping, prepared statements.
- WordPress REST endpoints only if necessary; add auth, permission callbacks and rate limits.
- Keep translatable strings in text domain and run static analysis/tests.
- No template-only “fake backend”; staff actions must persist and send working notifications.

---

## 10. Payments, refunds and finance operations

### Merchant prerequisites

- Genuine legal entity/trader details, payment account owner, UK trading eligibility, bank account, tax/VAT configuration and approved business category. Gateway approval is not automatic simply because site is UK-facing.
- Confirm with acquirer that **manually fulfilled vehicle report services** are an acceptable merchant category.
- Configure GBP, invoice/receipt legal details, chargeback evidence handling and support email.

### Stripe + WooCommerce [PROPOSED]

- Install official supported WooCommerce Stripe extension.
- Merchant connects their real gateway account (developer does not use their own identity to disguise merchant).
- Enable sandbox/test keys first, TLS, webhook status check in Woo settings.
- Never store PAN/CVV; follow hosted/tokenized integration.
- Only mark paid through verified gateway processing, not through a manually supplied query parameter or `/thank-you` visit.
- Document real production webhook URL and use gateway's prescribed signature validation/extension behavior.
- Test payment authorization, 3DS, declines, abandoned checkout, duplicate notifications, partial/full refunds, disputes and webhook outages.

### Refund and consumer rights behavior

- Legal team approves actual policy for manual service vs digital content; don't automatically claim “no refunds after purchase.”
- Show delivery deadline, what happens if report cannot be supplied, transparent cancellations/refunds and support process.
- If digital-content immediate supply rule applies, collect **express agreement + acknowledgement** per legal advice and record it. Do not invent a checkbox to abolish statutory rights.
- If supplier data unavailable, offer disclosed substitution only with customer's approval or refund as appropriate.
- Record refund through payment processor and reflect in WooCommerce. No editing financial records to disguise refunds or chargebacks.

---

## 11. Manual report generation and delivery

### Fulfillment checklist per paid order

1. Staff confirms exact vehicle registration and whether customer-supplied VIN matches any lawful source information.
2. Staff checks approved data sources (official free data where permitted, contracted commercial data where subscribed; **redistribution/download and white-label rights must be checked**).
3. Staff records *which* checks are actually supported by the source; no false “clear” conclusion when data unavailable.
4. Staff creates or uploads original truthful report with provider attribution and report-generation date.
5. Staff marks unavailable checks “Not checked / Data unavailable”, never silently omits promised checks without remedy.
6. Admin uploads PDF, sees original file name, file hash, MIME validation, malware scan (where available), preview metadata and source summary.
7. System saves private file, generates scoped download token, schedules delivery email, logs action and attempts.
8. Customer receives accurate status and secure report link; resend allowed with audit trail.
9. Refund/escalation path if staff cannot deliver within promised time.

### Report template (if report created in-house)

Cover + report ID/date + vehicle identifier + exact source list + identity details (if actually checked) + MOT/tax history (if actually checked) + mileage history (if actually checked) + commercial risk checks (only if licensed/verified) + record limitations + disclosures + customer support. Include a “not checked” state. No invented accident/finance/theft/keeper data. Do not remove third-party attribution or modify an existing provider PDF's brand/watermark where licence forbids it.

### Delivery options

**Preferred:** secure order-linked portal with expiring PDF URL, notification email containing link rather than large attachment.  
**Alternative:** attach PDF to email only if mail provider/security/size make it appropriate and recipient consent/information duties are satisfied.  
Potential resend action should NOT create duplicate paid order or change invoice amount.

### Email notifications required

1. Payment received/order confirmed (customer + internal staff)
2. Payment failed/awaiting (customer, if permitted and necessary)
3. Need additional vehicle details
4. Report ready/delivered (customer)
5. Report delivery failed (internal)
6. Support ticket acknowledgment
7. Refund confirmed

All templates: real brand identity, order reference, item price, masked registration where needed, expected delivery time, support contact, no invented “instant report” claims. Transactional messages must not silently include marketing content.

---

## 12. Fulfillment back-office specifications

### Queue list fields

- Order number; placed date/time (UTC storage with Europe/London display preference)
- Customer name/email
- Registration / VIN if supplied
- Product / price / VAT snapshot
- Gateway transaction reference and **verified** payment state
- Fulfillment state; age vs SLA; assigned staff
- Report attachment/version and delivery attempts
- Action buttons: View, Assign, Mark in Progress, Request Info, Upload, Preview, Deliver, Resend, Refund (permission controlled)

### Filtering and alerts

Filters: paid/unfulfilled; assigned-to-me; awaiting customer; delivered; overdue; delivery failure; date range; product; registration exact match; email search. Never display full lists of customer email in public; admin exports permissioned and logged.

### Admin audit requirements

Audit every upload, replacement, report-source edit, delivery, resend, status change, refund initiation, export and failed admin permission check where appropriate. Never store raw API secrets/PII in debug logs.

### SLA operations

Default delivery SLA **TBD, client must approve**. Display countdown/overdue indicators, notify manager for approaching breach; don't falsely promise instant delivery.

---

## 13. External sales operations, CRM and UK messaging — explicitly OUT OF SCOPE

**Current product owner decision:** The website is only for legitimate paid vehicle report ordering and manual fulfillment. The discussion about sourcing UK vehicle sellers, sending messages from a laptop/UK number, and later connecting real buyers concerns external business operations and a possible separate CRM — **not** any component to implement inside this website. There must be no CRM API, leads database, phone-number scraping, seller-contact export, buyer–seller matching, messaging gateway, SMS campaign interface, lead assignment, buyer role switching, or cross-project tracking built into this repository.

### If a future separate CRM is commissioned

That future project requires its **own specification, authorization, account separation, data retention assessment, lawful lead sources and outreach permissions**. Do not assume the report website's customers opted into CRM contact sharing or buyer introductions. Any data transfer would require a clear purpose and appropriate privacy notices/permissions. Contact roles should reflect actual relationships; commercial marketing must be accurately disclosed.

Potential legitimate channels to assess **in that separate project** (not in the site code): opt-in inquiries, permissioned mailing lists, approved dealer partnerships, paid advertising, and genuinely transparent marketplace interactions subject to service terms. A publicly listed seller phone number is not automatic consent for promotional SMS.

### Prohibited implementation requests

Do not create tools to harvest private seller phone numbers, mass-send unsolicited SMS, spoof UK numbers, impersonate genuine car buyers to persuade a seller to pay, hide a commercial purpose, or generate fabricated report information. If requested, mark the request as out-of-scope and a compliance blocker while continuing unrelated legitimate report-commerce development.

### Transactional messages which ARE in scope

This website does require **customer-initiated transactional email**: payment receipt/order acknowledgement, report delivery, delivery failure/support notifications, and refund information. These emails must go to the customer's provided address for the relevant paid order, respect privacy, and not be repurposed into unwanted promotions. UK SMS delivery of transaction updates is **not planned for the MVP**.

---

## 14. Vehicle-data sourcing (manual MVP; APIs optional later)

**MVP:** website itself does NOT query DVLA/DVSA/HPI. Human staff fulfills using approved sources under permitted usage. Never promise checks not available. Where free official data are used, be transparent about their origin, licence and actual report value (curation, interpretation, combined analysis) without pretending proprietary exclusive access.

**Phase 2 optional (requires signed integration decision):**

- DVLA Vehicle Enquiry Service uses VRM and an API key; at research date official portal said **new VES registrations closed**. Do not assume access.
- DVSA MOT History API can provide official MOT information subject to approved onboarding, OAuth/API keys, terms and limits.
- HPI, Experian or other approved providers can provide commercial finance/write-off/stolen checks, with separate licence and **commercial resale/white-label rights**.
- Third-party sample reports are not proof of data-sharing permission.

**Vehicle identity:** UK registration alone does not magically supply VIN/engine/chassis number from every source. Treat VIN as separate input/output only where lawful data actually provides it. Never show “verified VIN” based solely on assumed reconstruction.

---

## 15. CMS, content and content ownership

Allow merchant to change: logo, menus, page copy, legal pages (versioned), product price and active status, report delivery SLA, business contact, FAQ, sample report, SEO. Staff cannot change material customer commitments retroactively for orders already placed; snapshot price, package promises and SLA with order.

Content ownership: design and wording may be inspired by broad industry conventions, but **do not scrape/copy protected assets, other brands' customer reviews, report PDFs, or misleading logos**. Placeholder content clearly marked in staging. All final claims require verifiable approval.

---

## 16. UI design brief [PROPOSED, NOT LIVE-REFERENCE-VERIFIED]

**Visual direction:** trustworthy British automotive service, clean light surface with dark navy sections, strong contrast, prominent search form, muted accent blue; professional rather than fake institutional/government styling.

**Tokens (provisional, adjustable after screenshots):**

```css
:root {
  --color-ink: #142638;
  --color-navy: #0f2944;
  --color-accent: #1769aa;
  --color-bg: #f7f9fb;
  --color-surface: #ffffff;
  --color-border: #dce3ea;
  --color-success: #13764b;
  --color-danger: #b93838;
  --radius: 12px;
  --container: 1160px;
}
```

**Layout:** 1-column mobile, 2-column hero at desktop, simple comparison cards; max width 1160; hero CTA visible; avoid confusing “free lookup” claims if no lookup occurs. Use licensed real car photography or clear custom illustration. Create a real design system, not random copied screenshots.

### Screens to build/test

1. Home desktop/mobile
2. Pricing desktop/mobile
3. Vehicle registration form with validation
4. Checkout (empty/filled/validation/error)
5. Paid pending/manual delivery state
6. Payment not confirmed state
7. Report delivered page/download
8. Contact/FAQ/policies
9. Admin list/filter/order detail/upload/delivery
10. Report email desktop/mobile
11. Empty/loading/error states and accessibility focus states

**Visual parity condition:** if screenshots are later supplied by owner and licensed for use, produce a screenshot-by-screenshot comparison, component inventory, color/font measurements and change log. Without this, do not tell client it is a pixel-perfect clone.

---

## 17. Security, privacy and compliance requirements

- HTTPS everywhere, HTTP -> HTTPS, secure cookies, admin 2FA, strong role separation.
- Update WordPress/WooCommerce/plugins on tested staging; disable unused plugins/themes.
- Full/server-side validation; prepared queries; WordPress nonce AND capability checks (nonce alone not authorization).
- Payment callback signature/integrity handled by official gateway integration.
- CSP, secure headers where feasible, rate limit report form, login and contact endpoints.
- Keep files in protected storage; only authenticated/signed order owner access, expiring download.
- Malware/type/size checks for staff PDF uploads; PDF-only where reasonable; no executable downloads.
- Backup and restoration test, independent offsite copies, secret management, incident-response contacts.
- GDPR privacy notices, retention, DSAR, data breach procedure, processors/data-transfer documentation; ICO obligations assessed by responsible business owner.
- PECR consent and suppression for any marketing SMS; contact sourcing reviewed.
- UK **DMCC unfair commercial practices** rules: no misleading statements, pressure tactics, fabricated sources, prices or reviews.
- UK distance-selling/digital-content rules: accurate pre-contract information, terms and cancellation/refund flow vetted by UK legal adviser.
- Company name and contact legally correct; VAT registration details if applicable and required.
- Data licensing verified for every data source; include source/provenance and limitations.
- Never claim DVLA/DVSA/HPI partnership or government endorsement without proof.
- Trademark and copyrights: unique branding or permissioned original assets.

This is technical/product guidance, not a substitute for UK legal advice.

---

## 18. Analytics & operations

Measure with privacy-aware analytics and consent as required:

- Homepage views / report page views
- Form start / submit / validation failures
- Checkout start / payment success / payment decline / abandoned checkout
- Paid orders / gross revenue / refunds / disputes
- Median manual fulfillment time and overdue orders
- Report delivery attempts and failures
- Support contact volume and average resolution time

Do not send full vehicle registrations, names, emails, card details or report file contents to general analytics events. Name events e.g. `vehicle_order_started`, `checkout_completed`, `report_delivered` without PII.

---

## 19. Non-functional requirements / launch targets

- Mobile-first and keyboard accessible; responsive at 320px and above.
- Performance: target Core Web Vitals passing on real hosting; optimize images, caching **excluding checkout/order/account/report/private pages**.
- Reliability: automated offsite backup, tested restore; payment status eventual consistency and retry-friendly.
- Email reliability: authenticated sending, domain reputation and observable delivery errors.
- Accessibility: target WCAG 2.2 AA; run axe and manual checks.
- Security: baseline OWASP ASVS/Top 10 review; routine updates, least privilege.
- No public exposure of report PDFs or order info through caching, indexing or predictable URLs.
- All times stored UTC; display Europe/London business hours including daylight saving.
- Do not hard-code prices, SLA, merchant identity or contact details; configure them.

---

## 20. Testing / acceptance matrix

| ID | Case | Expected |
|---|---|---|
| T01 | Valid UK registration, guest customer | Checkout order created with normalized and original registration |
| T02 | Empty/invalid registration | Clear error, no checkout |
| T03 | Email invalid / XSS / SQL payloads | Rejected or escaped; never executed |
| T04 | Change checkout price client-side | Server-side amount remains fixed |
| T05 | Gateway success callback verified | Payment paid exactly once, fulfillment starts |
| T06 | Fake thank-you URL | Order remains unpaid |
| T07 | Duplicate webhook | No duplicate status transition/email/delivery |
| T08 | Card declined/cancelled | No fulfillment, useful retry UI |
| T09 | Staff upload safe PDF | Private storage, report metadata recorded |
| T10 | Staff uploads PHP/oversized file | Rejected without public file leak |
| T11 | Unprivileged user calls upload endpoint | 403, audit-worthy alert |
| T12 | Paid report recipient opens signed link | Gets only their report |
| T13 | Different order/expired link | 403/410, no report disclosure |
| T14 | SMTP outage after upload | Delivery failed & retried, no report lost |
| T15 | Manual resends | Same report/order, every attempt audited |
| T16 | Refund | Correct gateway/Woo state; audit preserved |
| T17 | Staff cannot fulfill | Escalation/refund flow, no fabricated report |
| T18 | Mobile iPhone/Android | No horizontal scroll; checkout usable |
| T19 | Keyboard/screen reader | Input and errors labelled; focus visible |
| T20 | Search engine visits private URL | `noindex`, authorization, no cache |
| T21 | Backup restore staging | Orders and protected report references recover |
| T22 | Search for CRM, seller lists, messaging gateway or lead-scraping workflows | None exists in website UI, API, admin, database or dependencies |
| T23 | Customer checkout and report fulfillment | Flow completes without buyer matching, CRM account, buyer introduction or promotional SMS |
| T24 | Order placed before policy/price update | Stored snapshot remains unchanged |
| T25 | Customer data export/delete request | Privacy workflow follows documented retention obligations |
| T26 | Duplicate report order for same plate | Separate transactions and owner authorizations; no cross-access |
| T27 | Report file replacement | Version history retained, proper disclosure/resend |
| T28 | Unexpected gateway down | Fail safely with no paid claim |
| T29 | Unsupported data claim | Admin/content checks prevent promise without provider approval |
| T30 | Staff role export customer list | Blocked unless explicitly authorized and logged |

Acceptance: all critical payment/authorization/privacy tests pass, and all unresolved compliance go-live blockers are closed.

---

## 21. Release plan / implementation tasks for Claude

### Milestone 0 — Requirements / permissions (MUST be done before accepting real payments)

- Collect legal merchant name/address/contact, branding rights, approved data suppliers/licences, report specimen and actual fulfillment SLA.
- Decide prices/tax and refund policy with client/legal adviser.
- Verify payment merchant onboarding/eligibility.
- Get working reference website access or screenshots for visual matching; if unavailable, proceed with unique brand design and document difference.
- Record decisions in `docs/DECISIONS.md`; configure defaults as placeholders **not publishable**.

### Milestone 1 — Development setup

- Git repository, `.gitignore`, environment examples, local WP/Woo sandbox, WP-CLI, code formatter/linter, staging URL.
- Pin tested compatible versions with documented upgrade plan.
- Install essential commerce, payment sandbox and email sandbox plugins.

### Milestone 2 — Public pages and design

- Home, pricing, how-it-works, sample, contact, FAQ and policies.
- Validate copy and obvious product limitations.
- Build accessible mobile navigation and vehicle form.

### Milestone 3 — Domain extension

- Registration input, checkout propagation and WooCommerce order metadata.
- Custom states, fulfillment queue, audit log, admin capabilities.
- Email templates and staff notification.

### Milestone 4 — Payments + secured fulfillment

- Gateway sandbox, webhooks, order confirmation, source logging, PDF upload and private download.
- Delivery retries, secure report notification and refund path.

### Milestone 5 — Quality assurance

- Payment sandbox cases, attack/permission tests, email deliverability, mobile/a11y, backups, cookie/privacy.
- Write client handover and user SOP for manual delivery.

### Milestone 6 — Launch

- Domain, DNS, TLS, SPF/DKIM/DMARC, live merchant verification, gateway live keys, test transaction and refund, logging, backups and monitoring.
- Legal go-live signoff, data licences reviewed.
- Turn on production traffic **only when** real report fulfillment capacity exists.

### Milestone 7 — Optional future roadmap

- **Possible extensions to this report-selling product only (subject to a new decision):** licensed real-time vehicle data API, supplier coverage comparison, automatic PDF, customer report dashboard, multi-plan pricing. **CRM, buyer–seller matchmaking and outreach remain a separate product, never a module of this website.**

---

## 22. Suggested project repository structure

```text
vehicle-report-uk/
├── README.md
├── CLAUDE.md
├── docs/
│   ├── PROJECT_MASTER_SPEC.md
│   ├── DECISIONS.md
│   ├── REQUIREMENTS_TRACEABILITY.md
│   ├── DATA_PROCESSING_AND_RETENTION.md
│   ├── DEPLOYMENT.md
│   ├── OPERATIONS_MANUAL.md
│   ├── API_AND_WEBHOOK_NOTES.md
│   ├── TEST_CASES.md
│   └── REFERENCE_VISUAL_AUDIT.md
├── wp-content/
│   ├── plugins/vehicle-report-orders/
│   └── themes/vehicle-report-theme/
├── tests/
├── scripts/
└── .env.example
```

`CLAUDE.md` should instruct the coding agent to implement one phase at a time, keep existing behavior, run tests and report assumptions, not fill unknown reference site features with invented claims.

---

## 23. Detailed process edge cases

- **Unknown plate format:** preserve original and normalized; do not infer vehicle exists from syntax alone.
- **Same plate multiple people:** segregate orders by buyer. Do not reveal previous purchaser data.
- **Charge succeeded but user closed browser:** webhook independently completes order and sends email.
- **Payment processed but staff unavailable:** visible fulfillment queue, alerts and customer SLA message; refund if required.
- **Email typo:** customer support change requires secure verification; log change and resend.
- **Provider cannot identify car:** contact customer, do not fabricate report, offer refund/remedy according to commitments.
- **Cancelled order gets report uploaded accidentally:** prevent delivery until manager resolves state.
- **Source changes result:** new version with timestamp, old history preserved and client notified when material.
- **Multiple browser submits:** idempotency/session checkout handling to avoid unintended duplicate charges.
- **Reused report file across unrelated buyers:** only if license, recipient authorization and order-specific entitlement permit; avoid leakage.
- **Download link indexed or forwarded:** expiring, high-entropy signed URL + optional recipient verification; private storage remains protected.
- **Caching:** exclude WooCommerce cart/checkout/my-account and all report/order status endpoints.
- **WordPress updates:** staging regression tests for block checkout and HPOS; never untested auto-updates of incompatible major payment extensions.
- **Customer disputes:** preserve purchase disclosure snapshots, consent receipt, report-source provenance, fulfillment timestamps and email delivery evidence.

---

## 24. Dependencies and client-provided inputs

| Input from merchant | Required when | Blocker if absent? |
|---|---|---|
| Legal trading identity/contact | Before launch | Yes |
| Merchant payment account and bank eligibility | Before payment live | Yes |
| Owned domain/hosting | Before launch | Yes |
| Approved brand name/logo/images or permission | Before design final | Yes for visual signoff |
| Report scope, actual data sources, resale permissions | Before accepting payment | **Yes** |
| Realistic manual delivery SLA/business hours | Before paid checkout | **Yes** |
| Price/VAT setup and invoicing rules | Before paid checkout | **Yes** |
| UK law-reviewed policies | Before launch | **Yes** |
| Customer support inbox | Before launch | Yes |
| Reference screenshots | For pixel-perfect similarity only | Not needed for independent design |
| Separate CRM / sales messaging inputs | **Not applicable to this website** | **Outside project scope** |
| Vehicle data API credentials | Phase 2 automation only | No for manual MVP |
| Any existing report template | For exact report format | No if original authorized template built |

---

## 25. Project estimates (not fixed promises)

Assuming one experienced developer, readily available WordPress hosting and a cooperative UK merchant:

| Workstream | Tentative effort |
|---|---|
| Requirements, design assets, legal/gateway discovery | 2–5 working days plus external approvals |
| WP/Woo setup & frontend pages | 3–6 working days |
| Registration/order custom plugin & admin queue | 4–8 working days |
| Payment sandbox, email, report upload/security | 3–6 working days |
| QA, staging, launch | 3–6 working days |
| **Technical MVP total** | **15–31 developer working days**, excluding provider/merchant/legal delays |

These are planning placeholders, not client quotations. Website running costs include hosting, transactional email, payment fees, staff fulfillment labor, and licensed data/report cost. Separate CRM, paid acquisition and outbound sales/messaging expenses are excluded from this website budget. Do not project income based on unverifiable anecdotes.

---

## 26. Claude execution rules and definition of done

Claude / Claude Code should:

1. Read `CLAUDE.md` and this complete spec before coding.
2. Build **MVP only** until roadmap features are approved.
3. List key unknowns, select safe config defaults, mark launch blockers; do not hide them.
4. Write a short plan per milestone, implement, lint/test, and report changed files.
5. Preserve functioning existing code; never randomly replace plugins/theme.
6. Avoid copying reference site's copyrighted wording/branding/assets.
7. Never imply realtime DVLA lookup, instant report, verified accidents/finance or exclusive data without working authorized implementation.
8. Never add scraped private sellers / impersonation / spam features. **Do not add CRM or contact/consent integration to this repository**; any separate future product requires separate requirements and approvals.
9. Handle security/payment semantics using official WooCommerce/WP conventions.
10. Keep the custom logic plugin self-contained; themes only presentation.
11. Keep credentials out of git and generated sample configuration.
12. Do not put any payment gateway in live mode until product owner supplies real authorized account and signs off.
13. Keep test data/fixtures unmistakably fictional.
14. Never declare completion if critical acceptance tests are unrun or failing.
15. Produce at each milestone: summary, files changed, tests, open decisions, security caveats, staging demo steps.

**Definition of done:** working legitimate paid manual-report website with truthful claims, verified paid checkout, order queue, secure PDF delivery, customer emails, staff workflow, policies, deploy/runbook and passing launch checklist — **without any CRM, matchmaking, lead harvesting or outbound sales messaging functionality**.

---

## 27. Requirements traceability

| Source statement | Product requirement | Location |
|---|---|---|
| Client: website where registration entered | Registration order form | §§1, 6, 7 |
| Client: payment gateway | WooCommerce + approved gateway | §§3, 10 |
| Client: payment success message and email | Confirmation and email templates | §§6, 11 |
| Client: collect registration in backend | Order metadata/admin queue | §§8, 12 |
| Client: do not build automatic report system | Manual fulfillment V1 | §§1, 11, 14 |
| Client: upload/send final PDF | Private report upload + delivery | §§7, 11 |
| Client: UK vehicle-report customers | UK-oriented paid report product, truthful communications | §§1, 2, 6 |
| Client: separate future CRM for contacts/real buyer referrals | **Explicitly exclude CRM and buyer–seller matching from this website; separate system/spec** | §§1, 13 |
| Client: UK numbers and laptop messaging in voice notes | **External operations, not a website feature; no outreach automation implemented** | §13 |
| Client: not necessarily Laravel | Stack-neutral decision, WP preferred | §3 |
| Reference indexed: vehicle history reports | Service positioning, not verified implementation | §§0, 1 |
| Reference indexed: paid report packages | Pricing architecture without invented prices | §§0, 6 |
| Legal: truthful trading, consent, refunds | Requirements + go-live gates | §§10, 13, 17, 24 |

---

## 28. Live website verification plan (once reference site works)

If site returns successfully and inspection permission exists, perform and append `docs/REFERENCE_VISUAL_AUDIT.md`:

1. Capture full-page desktop 1440px and mobile 390px screenshots of Home, Pricing, About, Contact, FAQ, report form and policies.
2. Record exact navigation, footer, colors, typography, banners, images, icons, CTA copy, empty states.
3. Inspect only non-destructive publicly accessible forms; **do not** place real test purchase without business owner's permission. Use payment sandbox/test account if offered.
4. Record prices, currency, tax, legal copy and claimed data checks **as observed on that date**, never reuse as promises without permission.
5. Inspect customer email/report format only through authorized test flows.
6. Map reference components to proposed components; highlight differences and copyright/licensing limitations.
7. Get client approval of desired **visual similarity** rather than assuming clone rights.

Until this audit exists, aesthetic details are proposals, not facts about the original.

---

## 29. Verified external sources / current references

**Reference status**

- Original reference URL: https://starwoodmotor.co.uk/ — attempted 27 Oct 2026; web fetch returned HTTP 502.
- Third-party listing/title/registration-date/context (not a live-site audit): https://www.scamadviser.com/check-website/starwoodmotor.co.uk
- Another indexed business description/trust evaluation (independent opinion, not proof of wrongdoing): https://www.scam-detector.com/validator/starwoodmotor-co-uk-review/

**WordPress / checkout / delivery**

- WooCommerce virtual and downloadable product behavior: https://woocommerce.com/document/managing-products/virtual-downloadable/
- WooCommerce digital file delivery: https://woocommerce.com/document/digital-downloadable-product-handling/
- WooCommerce checkout-block additional field API: https://developer.woocommerce.com/docs/block-development/tutorials/how-to-additional-checkout-fields-guide/
- WooCommerce official Stripe gateway: https://woocommerce.com/document/stripe/
- Stripe webhook setup for WooCommerce: https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/

**UK compliance / marketing**

- UK CMA DMCC unfair commercial practices guidance: https://www.gov.uk/government/publications/unfair-commercial-practices-cma207/unfair-commercial-practices
- GOV.UK online/distance selling: https://www.gov.uk/online-and-distance-selling-for-businesses
- GOV.UK online digital services: https://www.gov.uk/online-and-distance-selling-for-businesses/online-selling
- Consumer Contracts Regulation 37 (digital content): https://www.legislation.gov.uk/uksi/2013/3134/regulation/37/2026-01-01
- ICO guide to electronic marketing: https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/guide-to-pecr/electronic-and-telephone-marketing/
- ICO B2B marketing (public contacts do not remove all obligations): https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/business-to-business-marketing/
- ICO detailed electronic mail marketing guidance (updated Apr 2026): https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/guidance-on-direct-marketing-using-electronic-mail/
- Twilio UK SMS guidelines: https://www.twilio.com/en-us/guidelines/gb/sms
- Twilio UK sender ID rules: https://help.twilio.com/articles/23737742623387-Documents-Required-and-Instructions-to-Register-Your-Alphanumeric-Sender-ID-in-United-Kingdom

**Optional future vehicle API**

- DVLA VES official docs (registrations marked closed at research): https://developer-portal.driver-vehicle-licensing.api.gov.uk/apis/vehicle-enquiry-service/vehicle-enquiry-service-description.html
- DVSA MOT History API: https://documentation.history.mot.api.gov.uk/

---

## 30. Open decisions: do NOT silently invent these

- [ ] Exact business/brand name, registered business identity, genuine UK operational status
- [ ] Reference site screenshots / precise look-and-feel parity target
- [ ] Actual product scope and **what real checks** manual staff can deliver
- [ ] Data licence/commercial redistribution permission for intended report sources
- [ ] Actual report specimen and final PDF branding/source attribution
- [ ] One report price and VAT treatment
- [ ] Manual delivery SLA and business hours/time zone
- [ ] Refund/cancellation policy and customer support details
- [ ] Payment provider and merchant authorization
- [ ] Authentication vs guest-only
- [ ] Phone capture only if a real checkout/support need is approved; omit by default
- [x] CRM, buyer introductions and outbound SMS are **excluded** from the vehicle-report website; any separate future CRM needs its own requirements and approvals
- [ ] UK company legal review and data-protection responsibilities
- [ ] Domain and hosting provider
- [ ] Final logo/photo rights
- [ ] Staff count and roles

**If any of these are absent, build a working staging system with clearly marked placeholders and disabled production checkout where applicable; do not substitute fake facts.**
