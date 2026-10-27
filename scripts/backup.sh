#!/usr/bin/env bash
# Back up everything that is not in git: the database, uploads, and — the one
# people forget — the private report directory. A backup without the reports
# restores a site that has every order and no deliverable.
#
#   ./scripts/backup.sh                 → ../vehicle-report-backups/<timestamp>
#   BACKUP_DIR=/mnt/backups ./scripts/backup.sh
set -euo pipefail

cd "$(dirname "$0")/.."

SITE_DIR="$(pwd)"
BACKUP_ROOT="${BACKUP_DIR:-$(dirname "$SITE_DIR")/vehicle-report-backups}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
TARGET="$BACKUP_ROOT/$STAMP"

command -v wp >/dev/null 2>&1 || { echo "WP-CLI is required." >&2; exit 1; }

PRIVATE_DIR="$(wp eval 'echo VehicleReportOrders\Reports\PrivateStorage::base_path();' 2>/dev/null)"

if [ -z "$PRIVATE_DIR" ] || [ ! -d "$PRIVATE_DIR" ]; then
    echo "Could not locate the private report directory. Refusing to take a partial backup." >&2
    exit 1
fi

mkdir -p "$TARGET"
chmod 700 "$TARGET"

echo "① Database"
# --single-transaction keeps the dump consistent while the site keeps serving.
#
# --set-gtid-purged=OFF matters more than it looks: on a MySQL server with
# GTIDs the default dump carries a SET @@GLOBAL.GTID_PURGED line that makes the
# file refuse to import back into the same server. A backup that cannot be
# restored is not a backup. MariaDB has no such flag, hence the fallback.
if ! wp db export "$TARGET/database.sql" --quiet --single-transaction --set-gtid-purged=OFF 2>/dev/null; then
    wp db export "$TARGET/database.sql" --quiet --single-transaction
fi
gzip -f "$TARGET/database.sql"

echo "② Uploads"
tar -czf "$TARGET/uploads.tar.gz" -C wp-content uploads

echo "③ Private reports  ($PRIVATE_DIR)"
tar -czf "$TARGET/private-reports.tar.gz" -C "$(dirname "$PRIVATE_DIR")" "$(basename "$PRIVATE_DIR")"

echo "④ Manifest"
{
    echo "taken_at: $STAMP"
    echo "site_url: $(wp option get siteurl)"
    echo "wordpress: $(wp core version)"
    echo "woocommerce: $(wp plugin get woocommerce --field=version)"
    echo "plugin_commit: $(git rev-parse HEAD 2>/dev/null || echo unknown)"
    echo "private_dir: $PRIVATE_DIR"
    echo "orders: $(wp eval 'echo count( wc_get_orders( array( "limit" => -1, "return" => "ids" ) ) );')"
    echo "report_files: $(find "$PRIVATE_DIR" -type f ! -name '.*' | wc -l | tr -d ' ')"
} > "$TARGET/manifest.txt"

( cd "$TARGET" && shasum -a 256 ./*.gz > checksums.sha256 )

echo
echo "Backup written to $TARGET"
cat "$TARGET/manifest.txt"
