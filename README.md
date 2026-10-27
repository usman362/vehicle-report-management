# Vehicle Report Management

UK vehicle history **report ordering and manual fulfilment** website.

A customer picks a package and enters a registration or a VIN, pays in GBP, and
staff prepare and send a genuine, source-attributed PDF by email. The site does **not** query DVLA, DVSA
or HPI, and it is **not** a CRM, lead system or messaging platform — see
[`CLAUDE.md`](CLAUDE.md) and [`docs/SCOPE_CHANGELOG.md`](docs/SCOPE_CHANGELOG.md).

The feature set was compared page by page against the reference site the client
named; the gaps, what was built from them and what was deliberately left out are
in [`docs/REFERENCE_COMPARISON.md`](docs/REFERENCE_COMPARISON.md).

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
├── .github/workflows/              checks that run on every push
├── content/pages/                  storefront and legal page copy, as files
├── scripts/                        setup, serve, check, seed-content, backup, restore
├── tests/Unit/                     PHPUnit unit tests (no WordPress needed)
├── tests/integration/              cases that run inside real WordPress
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
./scripts/check.sh           # php -l, coding standards, unit tests, integration tests
```

Individually: `vendor/bin/phpcs`, `vendor/bin/phpcbf`, `vendor/bin/phpunit`, and
`wp eval-file tests/integration/run.php`.

GitHub Actions runs the same things on every push and pull request
(`.github/workflows/ci.yml`): standards and unit tests on PHP 8.1 and 8.3, and
the integration suite against a WordPress built from scratch by
`scripts/setup-local.sh` — which also keeps that script honest, since a setup
script nobody runs from nothing stops working quietly.

The unit suite needs no WordPress at all: it covers the domain logic — plate
parsing, the state machine, business-hours arithmetic, upload validation, token
signing — and the scope lock. The integration suite runs inside the real
WordPress and WooCommerce, because capability enforcement, delivery retries and
per-order isolation cannot be proved anywhere else. It creates its own orders,
staff users and report files and removes them again, pass or fail; without a
local site it is skipped with a reason rather than silently passing.

## What works today

A customer picks a package, names a vehicle, pays, and staff deliver a report:

- **Order** — a registration or a VIN, the kind of vehicle, and the package, all
  entered on the storefront and carried into the block or classic checkout.
  Either identifier names the vehicle; a checkout giving neither is refused. The
  order keeps the price, delivery window, terms version and package that applied
  at the time, so a later change cannot rewrite what was promised.
- **Packages** — tiers the merchant defines, each promising a named set of
  checks, each with its own page at `/packages/{slug}/`. A tier with no price
  cannot be bought and says so; go-live is blocked until every tier has one.
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
- **Payment failures** — a decline or an unreachable provider tells the customer
  plainly, charges nothing and queues no work, and the same order can still be
  paid for afterwards. A full refund revokes the customer's links; a partial one
  does not.
- **Coverage** — a report cannot be stored until staff have said, for every
  published source, whether a record was found, nothing was recorded, or the
  check was not possible — and the last has to say why. The customer's email
  repeats it source by source, so a gap cannot be read as a pass.
- **Record** — an append-only audit trail with redaction, covering uploads,
  deliveries, resends, refusals and failed permission checks.
- **Data requests** — WordPress's own export and erasure tools answer for this
  plugin. Erasure deletes the stored report, the registration and the contact
  snapshot and kills links already sent; the order, its totals and the audit
  trail stay, because financial records must survive and an audit trail that
  can be deleted on request is not an audit trail.

- **What a report can tell you** — every check is published with what it cannot
  tell you as well as what it can, because a check described without its limits
  reads as a guarantee. The same catalogue drives the package lists, so the
  marketing and the product cannot drift apart.
- **Trust signals** — the delivery record is counted from this site's own orders
  and stays hidden below ten deliveries; reviews are the merchant's real ones,
  entered by hand, and no reviews means no reviews section. No press logo, usage
  count or competitor comparison is generated anywhere.
- **Contact and support** — a contact form that emails the support address and
  stores nothing, with a honeypot and a per-visitor cooldown; phone, postal
  address and company details published once supplied; and a WhatsApp button and
  live chat that stay hidden until the merchant gives their own account. Turning
  chat on names the provider on the cookie page.
- **Search and sharing** — description, Open Graph and Twitter tags taken from
  each page's own words, `Organization` and `FAQPage` structured data, and a
  placeholder site icon. No rating or award is emitted, and a dedicated SEO
  plugin takes over if one is ever installed.

- **Pages** — storefront, about, sample-report and legal pages live in
  `content/pages/` and are rebuilt by `scripts/seed-content.php`, so the wording
  is reviewable in a diff and a fresh install comes up identical. The terms,
  privacy, cookie and refund pages carry a draft notice, in code rather than in
  prose, until the operator confirms a legal adviser has settled them.

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

Package prices, delivery window, report data licence, legal merchant identity
(including a published postal address) and brand assets are outstanding — see the blockers in
[`docs/DECISIONS.md`](docs/DECISIONS.md). Until they are supplied the storefront
will not start an order, and the go-live confirmation cannot be ticked.

Backups are `scripts/backup.sh` (database, uploads and the private report
directory together) and `scripts/restore.sh`. The restore has been rehearsed by
deleting every report file and dropping the plugin's tables: a download link
issued before the backup returns the report afterwards.

Two traps worth naming, because both look like a working site from the inside:
WooCommerce installs a store in coming-soon mode, where only signed-in
administrators can reach the checkout — the plugin's screens warn while that is
on — and the legal pages are drafting aids until someone qualified has read
them.
