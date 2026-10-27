#!/usr/bin/env bash
# Rebuild the local development site from nothing.
#
#   ./scripts/setup-local.sh
#
# Needs: php 8.1+, wp-cli, a running MySQL, composer. No Docker.
# Creates a throwaway local site. It never touches staging or production.
set -euo pipefail

cd "$(dirname "$0")/.."

DB_NAME="${DB_NAME:-vehicle_report_dev}"
DB_USER="${DB_USER:-root}"
DB_HOST="${DB_HOST:-127.0.0.1}"
SITE_URL="${WP_URL:-http://localhost:8080}"

echo "① Database"
mysql -u"$DB_USER" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "② WordPress core"
[ -f wp-settings.php ] || wp core download --skip-content

if [ ! -f wp-config.php ]; then
    wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="${DB_PASSWORD:-}" --dbhost="$DB_HOST" --skip-check --extra-php <<'PHP'
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
PHP
fi

echo "③ Site"
if ! wp core is-installed 2>/dev/null; then
    PASSWORD="$(openssl rand -base64 18)"
    wp core install --url="$SITE_URL" --title="Vehicle Report UK (dev)" \
        --admin_user=devadmin --admin_email=dev@example.test \
        --admin_password="$PASSWORD" --skip-email
    printf '# Local development only — gitignored. Not production credentials.\nWP_URL=%s\nWP_ADMIN_USER=devadmin\nWP_ADMIN_PASSWORD=%s\nDB_NAME=%s\nDB_USER=%s\nDB_PASSWORD=%s\nDB_HOST=%s\n' \
        "$SITE_URL" "$PASSWORD" "$DB_NAME" "$DB_USER" "${DB_PASSWORD:-}" "$DB_HOST" > .env
    chmod 600 .env
    echo "   admin credentials written to .env"
fi

echo "④ WooCommerce and store defaults"
wp plugin is-installed woocommerce 2>/dev/null || wp plugin install woocommerce
wp plugin activate woocommerce >/dev/null

# High-Performance Order Storage: the plugin is built and tested against it.
wp option update woocommerce_feature_custom_order_tables_enabled yes >/dev/null
wp option update woocommerce_custom_orders_table_enabled yes >/dev/null
wp option update woocommerce_custom_orders_table_data_sync_enabled no >/dev/null
wp option update woocommerce_currency GBP >/dev/null
wp option update woocommerce_default_country GB >/dev/null
wp option update woocommerce_enable_guest_checkout yes >/dev/null
wp option update timezone_string Europe/London >/dev/null

# WooCommerce ships new stores in coming-soon mode, which hides the shop, cart
# and checkout from everyone except signed-in administrators. A dev site that
# only works when you are logged in hides real problems, so it is opened here.
wp option update woocommerce_coming_soon no >/dev/null

echo "⑤ Our plugin"
wp plugin activate vehicle-report-orders >/dev/null

# Page URLs in the seeded content are written as /slug/, and the report
# download route is matched before the query is parsed, so both need the
# pretty permalink structure.
wp rewrite structure '/%postname%/' --hard >/dev/null

echo "⑥ Storefront pages"
wp theme activate vehicle-report-theme >/dev/null
wp eval-file scripts/seed-content.php

echo "⑦ Development tooling"
composer install --no-interaction --quiet

echo
echo "Ready. Start the site with:  ./scripts/serve.sh"
echo "Sign in at $SITE_URL/wp-admin  (credentials in .env)"
