# Start Here — UK Vehicle Report Website / Claude Handoff

This pack contains:

1. **PROJECT_MASTER_SPEC.md** — 7,000+ word complete product/engineering specification, evidence classification, user journeys, payments, order database, staff dashboard, manual PDF fulfillment, explicit exclusion of CRM/buyer matching/outbound SMS, security, launch checklist, testing, and research sources.
2. **CLAUDE.md** — short operating instructions specifically for Claude Code.
3. **DECISIONS.md** — explicit unknowns and launch blockers.
4. **SCOPE_CHANGELOG.md** — precisely what changed in v1.1 and what Claude must not build.

**How to use:**

- Upload these three files to Claude, or put them in a new coding repository root.
- Tell Claude: "Read `CLAUDE.md` and `PROJECT_MASTER_SPEC.md`. Begin with Milestone 0/1. Do not enable real payments until merchant and report-source approvals are complete."
- If possible, provide screenshots of https://starwoodmotor.co.uk/ because the site could not be reliably accessed directly on 27 Oct 2026.

**Latest owner clarification (scope lock):** Build **only** the paid UK vehicle-report website through payment and manual report email delivery. The separately discussed internal CRM for sellers and real buyers is **not part of this website**. Do not implement a hidden connection, buyer-introduction promises, SMS marketing, seller-lead harvesting, or CRM sync. These require a separate contract and specification if ever commissioned.

**Important:** This product delivers genuinely sourced, value-added manual vehicle reports. It is not a scraping/spam or buyer-impersonation tool. UK targeting by itself does not establish marketing legality.
