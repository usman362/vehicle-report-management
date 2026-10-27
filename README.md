# Vehicle Report Management

UK vehicle history **report ordering and manual fulfilment** website.

A customer enters a registration mark, pays in GBP, and staff prepare and send a
genuine, source-attributed PDF by email. The site does **not** query DVLA, DVSA
or HPI, and it is **not** a CRM, lead system or messaging platform — see
[`CLAUDE.md`](CLAUDE.md) and [`docs/SCOPE_CHANGELOG.md`](docs/SCOPE_CHANGELOG.md).

## Documentation

| File | What it is |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | Operating instructions and the binding scope lock |
| [`docs/PROJECT_MASTER_SPEC.md`](docs/PROJECT_MASTER_SPEC.md) | Full product and engineering specification (source of truth) |
| [`docs/DECISIONS.md`](docs/DECISIONS.md) | Confirmed decisions, open questions and launch blockers |
| [`docs/SCOPE_CHANGELOG.md`](docs/SCOPE_CHANGELOG.md) | What changed in v1.1 and what must not be built |
| [`docs/REQUIREMENTS_TRACEABILITY.md`](docs/REQUIREMENTS_TRACEABILITY.md) | Each requirement mapped to its milestone, code and test |
| [`docs/TEST_CASES.md`](docs/TEST_CASES.md) | The acceptance matrix with how each case was covered and its result |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Putting the site on a server, including where report files must live |
| [`docs/OPERATIONS_MANUAL.md`](docs/OPERATIONS_MANUAL.md) | How staff prepare, deliver and correct a report |

## Repository layout

```
.                                   WordPress root (core files are gitignored)
├── CLAUDE.md                       agent instructions and scope lock
├── docs/                           specification and decisions
├── scripts/                        setup-local.sh, serve.sh, check.sh
├── tests/                          PHPUnit unit tests (no WordPress needed)
├── phpcs.xml  phpunit.xml  composer.json
└── wp-content/
    ├── plugins/vehicle-report-orders/   all domain logic lives here
    └── themes/                          presentation only
```

WordPress core, WooCommerce, `wp-config.php`, `.env` and `wp-content/uploads/`
are deliberately not tracked.

## Local development

Requirements: PHP 8.1+, MySQL (or MariaDB), [WP-CLI](https://wp-cli.org/) and
Composer. No Docker.

```bash
./scripts/setup-local.sh     # database, WordPress, WooCommerce, plugin, tooling
./scripts/serve.sh           # http://localhost:8080
```

`setup-local.sh` generates an admin password and writes it, with the database
settings, to `.env` (gitignored). Nothing in this repository contains real
credentials.

## Checks

```bash
./scripts/check.sh           # php -l, WordPress coding standards, unit tests
```

Individually: `vendor/bin/phpcs`, `vendor/bin/phpcbf`, `vendor/bin/phpunit`.

## What works today

A customer enters a registration, pays, and staff deliver a report:

- **Order** — registration entry on the storefront, carried into the block or
  classic checkout, captured onto the order with the price, delivery window and
  terms version that applied at the time.
- **Payment** — only a verified WooCommerce payment queues the work. A repeated
  webhook, a refresh or a manual status change is recognised and logged rather
  than queueing twice.
- **Promise** — the deadline is counted in the merchant's own working hours, so
  a Friday evening order is never promised for Saturday.
- **Fulfilment** — a queue with filters and overdue flags, an order panel for
  claiming, uploading, delivering, resending and revoking, and a reason required
  before an order can be marked unfulfillable.
- **Report files** — validated by their bytes, hashed, stored above the web root
  with unguessable names, and versioned so a replacement keeps its predecessor.
- **Delivery** — signed, expiring, per-order links. Rotating an order's secret
  revokes every link already sent.
- **Record** — an append-only audit trail with redaction, covering uploads,
  deliveries, resends, refusals and failed permission checks.
- **Data requests** — WordPress's own export and erasure tools answer for this
  plugin. Erasure deletes the stored report, the registration and the contact
  snapshot and kills links already sent; the order, its totals and the audit
  trail stay, because financial records must survive and an audit trail that
  can be deleted on request is not an audit trail.

Honesty is enforced, not just documented: the price, delivery window, opening
hours and data sources are read from settings at render time and shown as
visible placeholders until the merchant supplies them, and ordering stays
switched off until they are.

## What is not here, by instruction

No CRM, buyer–seller matchmaking, seller leads, phone-number harvesting, SMS
campaigns or outbound marketing — see
[`docs/SCOPE_CHANGELOG.md`](docs/SCOPE_CHANGELOG.md). A test
(`tests/Unit/ScopeLockTest.php`) fails the build if such code ever appears.

## Before it can take real money

Price, delivery window, report data licence, legal merchant identity and brand
assets are outstanding — see the blockers in
[`docs/DECISIONS.md`](docs/DECISIONS.md). Until they are supplied the storefront
will not start an order, and the go-live confirmation cannot be ticked.
