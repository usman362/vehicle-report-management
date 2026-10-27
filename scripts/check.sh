#!/usr/bin/env bash
# Everything that must pass before a commit.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "① PHP syntax"
find wp-content/plugins/vehicle-report-orders -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
echo "   ok"

echo "② WordPress coding standards"
vendor/bin/phpcs

echo "③ Unit tests"
vendor/bin/phpunit
