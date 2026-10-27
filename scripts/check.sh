#!/usr/bin/env bash
# Everything that must pass before a commit.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "① PHP syntax"
find wp-content/plugins/vehicle-report-orders scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
echo "   ok"

echo "② WordPress coding standards"
vendor/bin/phpcs

echo "③ Unit tests"
vendor/bin/phpunit

# The integration cases need a real WordPress with WooCommerce behind them.
# On a machine without the local site they are skipped rather than failed,
# but they are not optional before a release: see docs/TEST_CASES.md.
echo "④ Integration tests"
if [ "${SKIP_INTEGRATION:-}" = "1" ]; then
    echo "   skipped (SKIP_INTEGRATION=1)"
elif ! command -v wp >/dev/null 2>&1; then
    echo "   skipped — WP-CLI is not installed"
elif ! wp core is-installed >/dev/null 2>&1; then
    echo "   skipped — no local WordPress installation here"
else
    wp eval-file tests/integration/run.php
fi
