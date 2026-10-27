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

## Current state

Milestones 0 and 1 are in place: specification in the repository, decisions
recorded, a working local WordPress + WooCommerce environment with HPOS enabled,
the plugin skeleton activating cleanly, and the coding-standards and test
harness running.

**The site cannot go live yet.** Price, delivery SLA, report data licence, legal
merchant identity and brand assets are still outstanding — they are listed as
blockers in [`docs/DECISIONS.md`](docs/DECISIONS.md). Payments stay in test mode
until the merchant account and those approvals exist.
