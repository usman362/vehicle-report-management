# CLAUDE.md — Coding Agent Instructions

You are implementing a **legitimate UK vehicle report ordering and manual-fulfillment website**. This repository is not an automatic vehicle data service at launch.

## Read first, in order

1. `PROJECT_MASTER_SPEC.md` — entire source-of-truth product / engineering specification.
2. `DECISIONS.md` — signed-off facts, assumptions and pending decisions.
3. Codebase and dependency manifests, if code already exists; preserve working features.

## Key reference constraint

Reference: https://starwoodmotor.co.uk/ . On 27 October 2026, this site did not load in direct inspection (502). Indexed descriptions support only the broad report-ordering concept. **You do not have an audited page inventory, screenshots, source code or evidence of its exact pricing/features.** Produce an original visual design unless rights-cleared screenshots/assets are supplied. Never claim a pixel-perfect reference clone.

## Scope lock — updated 27 October 2026

**Current website = paid report ordering + manual fulfillment only.** Do not build buyer–seller matchmaking, buyer introductions, seller lead databases, vehicle listings, a CRM, phone-number scraping, bulk SMS/sales outreach, role-switching screens, or any external CRM synchronization. The owner mentioned a **future standalone internal CRM**, not an expansion of this website; defer it to a separate project and specification. Nothing on the storefront should promise connecting customers to buyers.

## Approved MVP

Use **WordPress + WooCommerce + official gateway if the merchant is eligible**, with a small namespaced, HPOS-compatible custom plugin `vehicle-report-orders`. The application must support:

- Responsive public content pages and truthful pricing/data-source/delivery disclosures.
- UK registration input, order metadata persistence, email capture, guest checkout.
- GBP one-time payment, verified gateway outcome, no double processing.
- Clear post-payment message saying manual report delivery is by email within an approved SLA.
- Order dashboard and staff fulfillment queue with registration, email, purchase and status.
- Authorized staff upload of a genuine source-attributed PDF to **private** storage.
- Secure, scoped report download and email delivery plus retries and audit trail.
- Order receipts, support/refund request path, documented legal/privacy pages.
- Mobile/accessibility/security/test coverage and safe deploy/runbook.

No automatic DVLA/HPI history engine in MVP. No assumption that registration number alone reveals VIN, chassis or engine IDs.

## Non-negotiable constraints

- Do not implement scraped private car-seller lead lists, deceptive buyer impersonation, unsolicited bulk marketing, spoofing or fake-report manufacture.
- UK sales SMS and contact workflows are **not part of this website**. If ever planned, handle under a separate independently scoped, permissioned CRM/messaging project.
- Do not generate made-up vehicle facts, customer reviews, company addresses, licenses, government endorsements, prices or reports.
- Do not white-label a third-party PDF without data and trademark permission.
- Do not process live payments until real merchant, fulfillment source rights, verified pricing and legal copy exist.
- A fake thank-you redirect is NOT proof of payment. Payment and report fulfillment status are distinct.
- Use private report files and signed/authorized download links, never publicly predictable PDF URLs.

## Code conventions

- PHP under a namespaced custom plugin; WooCommerce CRUD/HPOS patterns, capability checks and nonces.
- Use the current supported WooCommerce checkout implementation and the appropriate custom field API (Block or Classic).
- Translatable UI strings; sanitize inputs, escape outputs; secure uploads and REST permissions.
- Environment secrets outside git. No raw cards, access tokens or unnecessary PII in logs.
- Test payments and idempotency, every admin permission, file access, refunds and email retries.
- Keep theme focused on view code; all core workflows in plugin.

## Working style

For every phase:

1. Read relevant master spec sections and current code; summarize requirements in 5-10 lines.
2. Identify client decision blockers and record defaults in `DECISIONS.md`.
3. Propose a brief implementation plan with files to create/change.
4. Implement a small end-to-end vertical slice; don't generate empty placeholders masquerading as done functionality.
5. Add or update tests, run them, and report output accurately.
6. Summarize changed files, screenshots/screens tested, caveats, and next steps.

Do not destroy existing working functionality to refactor unrelated code. Do not silently invent the reference site's appearance. Do not declare a milestone complete without testing.

## Suggested first instruction from project owner

> Read `PROJECT_MASTER_SPEC.md` and `CLAUDE.md` in full. Start with Milestone 0 and Milestone 1 only. Produce a requirements matrix, `DECISIONS.md`, a stepwise repo/bootstrap plan, selected WordPress/WooCommerce/plugin versions, and a working local development environment. Do not implement any CRM, matchmaking, seller contact collection or outbound SMS; do not enable live payments before merchant and data-source approval. Once the setup builds successfully, implement the Home and report order form as Phase 2 with tests. Do not skip unresolved blockers.
