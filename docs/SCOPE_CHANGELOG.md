# Scope Change Log — Version 1.1 (27 October 2026)

## Reason for update

The product owner clarified the boundary between the **paid vehicle-history report website** and a **different possible future internal CRM** for managing seller contacts and real buyers. Previously the spec mentioned CRM and SMS as potential website extensions; that could cause the coding agent to build unwanted features.

## Binding changes

1. Website workflow ends at registration entry → clearly described paid report → checkout/payment → acknowledgement → admin fulfillment → manual authentic report email and secure access; order support/refunds remain part of normal e-commerce obligations.
2. Buyer–seller introductions, buyer-finding promises, vehicle listings, contact pipeline, buyer status/identity tracking, seller contact collection, CRM dashboards, CRM APIs, website-to-CRM sync and SMS sales campaigns are **excluded from this website**.
3. A separate CRM can be assessed later as a different project, not as a hidden Phase 2 feature of this site. Any future coordination of buyer/seller contact details must follow applicable transparency, data-rights and communications rules.
4. Report offering must be genuine value-added research/analysis with verifiable sources, licensed use where required, and accurately disclosed limitations. Manual fulfillment is intentional; automatic vehicle API is not part of launch.
5. No assumption that the reference website was visually audited; previous attempt returned 502. Do not claim exact layout/features, pricing or source technology.
6. Updated QA cases explicitly verify absence of CRM and SMS features.

## Files revised

- `PROJECT_MASTER_SPEC.md` (v1.1): scope lock, updated feature and non-feature lists, forms, schema, admin and tests.
- `CLAUDE.md`: concise binding scope direction.
- `DECISIONS.md`: owner-confirmed decisions.
- `START_HERE.md`: handoff instructions for v1.1.

## Implications for Claude

- Never silently implement a buyer–seller network because voice notes mentioned genuine buyer connections.
- Never put an SMS/leads feature into this project because UK messaging was discussed separately.
- Do not represent the storefront as a buyer finding/matching service.
- The absence of a CRM on the website must not be used to misrepresent the true commercial nature of sales communications.
- If requested to implement a future CRM, create a **new repo and new specification** after explicit approval.
