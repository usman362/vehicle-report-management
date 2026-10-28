#!/usr/bin/env bash
# Deploy this site to a cPanel account over SSH.
#
#   ./scripts/deploy.sh starvehicles
#
# The argument is an SSH host alias from ~/.ssh/config. Everything the merchant
# has to decide — price, trading name, data sources — stays unset, so the
# storefront comes up with ordering switched off. That is deliberate: the site
# refuses to take money it cannot honour.
set -euo pipefail

HOST="${1:-starvehicles}"
DOCROOT="${DOCROOT:-public_html}"
SITE_URL="${SITE_URL:-https://starvehicles.co.uk}"
SITE_TITLE="${SITE_TITLE:-Vehicle Report UK}"
ADMIN_USER="${ADMIN_USER:-vradmin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-}"

here() { cd "$(dirname "$0")/.."; pwd; }
LOCAL="$(here)"

say() { printf '\n\033[1m%s\033[0m\n' "$*"; }

if [ -z "$ADMIN_EMAIL" ]; then
	echo "Set ADMIN_EMAIL=you@example.com — WordPress needs a real address for the administrator." >&2
	exit 1
fi

say "① Checking the connection"
ssh -o BatchMode=yes "$HOST" 'echo "  connected as $(whoami) on $(hostname)"'

say "② WP-CLI on the server"
ssh "$HOST" 'bash -s' <<'REMOTE'
set -euo pipefail
mkdir -p ~/bin
if [ ! -x ~/bin/wp ]; then
	curl -sS -o ~/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x ~/bin/wp
fi
echo "  $(~/bin/wp --version --allow-root 2>/dev/null || ~/bin/wp --version)"
REMOTE

say "③ Database"
# Created through cPanel's own API so it is owned by the account, and the
# password is generated on the server: it is never printed or sent anywhere.
ssh "$HOST" 'bash -s' <<REMOTE
set -euo pipefail
USER_PREFIX="\$(whoami)"
DB="\${USER_PREFIX}_vr"
DBUSER="\${USER_PREFIX}_vr"

if uapi --output=json Mysql list_databases 2>/dev/null | grep -q "\"\$DB\""; then
	echo "  database exists: \$DB"
else
	uapi Mysql create_database name="\$DB" >/dev/null
	echo "  database created: \$DB"
fi

if [ ! -f ~/.vr-db-pass ]; then
	LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32 > ~/.vr-db-pass
	chmod 600 ~/.vr-db-pass
fi
PASS="\$(cat ~/.vr-db-pass)"

uapi Mysql create_user name="\$DBUSER" password="\$PASS" >/dev/null 2>&1 || \
	uapi Mysql set_password user="\$DBUSER" password="\$PASS" >/dev/null
uapi Mysql set_privileges_on_database user="\$DBUSER" database="\$DB" privileges='ALL PRIVILEGES' >/dev/null
echo "  user ready: \$DBUSER"
REMOTE

say "④ WordPress core"
ssh "$HOST" "bash -s" <<REMOTE
set -euo pipefail
export PATH="\$HOME/bin:\$PATH"
cd ~/$DOCROOT

if [ ! -f wp-load.php ]; then
	wp core download --version=6.8.2 --force
	echo "  core downloaded"
else
	echo "  core present: \$(wp core version)"
fi

if [ ! -f wp-config.php ]; then
	wp config create \
		--dbname="\$(whoami)_vr" \
		--dbuser="\$(whoami)_vr" \
		--dbpass="\$(cat ~/.vr-db-pass)" \
		--dbhost=localhost \
		--skip-check
	wp config set WP_DEBUG false --raw
	wp config set DISALLOW_FILE_EDIT true --raw
	wp config set VR_PRIVATE_STORAGE_DIR "\$HOME/vehicle-reports-private" --type=constant
	echo "  wp-config written"
fi

mkdir -p ~/vehicle-reports-private
chmod 700 ~/vehicle-reports-private

if ! wp core is-installed 2>/dev/null; then
	wp core install \
		--url="$SITE_URL" \
		--title="$SITE_TITLE" \
		--admin_user="$ADMIN_USER" \
		--admin_email="$ADMIN_EMAIL" \
		--skip-email
	echo "  WordPress installed — set the administrator password with a reset link, it was not printed"
else
	echo "  WordPress already installed"
fi

wp rewrite structure '/%postname%/' --hard >/dev/null
REMOTE

say "⑤ WooCommerce"
ssh "$HOST" "bash -s" <<REMOTE
set -euo pipefail
export PATH="\$HOME/bin:\$PATH"
cd ~/$DOCROOT
wp plugin is-installed woocommerce 2>/dev/null || wp plugin install woocommerce --version=9.4.2
wp plugin activate woocommerce
wp option update woocommerce_currency GBP
wp option update woocommerce_default_country GB
wp option update woocommerce_coming_soon no
echo "  WooCommerce ready"
REMOTE

say "⑥ Our plugin, theme and content"
rsync -az --delete \
	--exclude='.DS_Store' \
	"$LOCAL/wp-content/plugins/vehicle-report-orders/" \
	"$HOST:$DOCROOT/wp-content/plugins/vehicle-report-orders/"

rsync -az --delete \
	--exclude='.DS_Store' \
	"$LOCAL/wp-content/themes/vehicle-report-theme/" \
	"$HOST:$DOCROOT/wp-content/themes/vehicle-report-theme/"

# The seeder reads these; the development mu-plugins are deliberately not sent.
rsync -az --delete "$LOCAL/content/" "$HOST:$DOCROOT/content/"
rsync -az "$LOCAL/scripts/seed-content.php" "$HOST:$DOCROOT/scripts/seed-content.php"
echo "  files synced"

say "⑦ Activate and seed"
ssh "$HOST" "bash -s" <<REMOTE
set -euo pipefail
export PATH="\$HOME/bin:\$PATH"
cd ~/$DOCROOT
wp theme activate vehicle-report-theme
wp plugin activate vehicle-report-orders
wp eval-file scripts/seed-content.php
REMOTE

say "⑧ What the merchant still has to supply"
ssh "$HOST" "bash -s" <<REMOTE
set -euo pipefail
export PATH="\$HOME/bin:\$PATH"
cd ~/$DOCROOT
wp eval 'foreach ( VehicleReportOrders\Domain\Settings::missing_for_live() as \$k => \$v ) { echo "  - \$v\n"; }'
echo
echo "  Storefront: $SITE_URL"
echo "  Ordering stays switched off until the list above is empty."
REMOTE
