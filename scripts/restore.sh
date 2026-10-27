#!/usr/bin/env bash
# Restore a backup taken by backup.sh. This replaces the database, the uploads
# and the private report directory, so it asks before it does anything.
#
#   ./scripts/restore.sh ../vehicle-report-backups/20261027T120000Z
#   CONFIRM=yes ./scripts/restore.sh <dir>     (non-interactive)
set -euo pipefail

cd "$(dirname "$0")/.."

BACKUP="${1:-}"

if [ -z "$BACKUP" ] || [ ! -f "$BACKUP/manifest.txt" ]; then
    echo "Usage: ./scripts/restore.sh <backup directory>" >&2
    exit 1
fi

command -v wp >/dev/null 2>&1 || { echo "WP-CLI is required." >&2; exit 1; }

echo "About to restore:"
cat "$BACKUP/manifest.txt"
echo
echo "This overwrites the current database, uploads and private reports."

if [ "${CONFIRM:-}" != "yes" ]; then
    printf 'Type the word restore to continue: '
    read -r answer
    [ "$answer" = "restore" ] || { echo "Nothing was changed."; exit 1; }
fi

echo "① Verifying the archive"
( cd "$BACKUP" && shasum -a 256 -c checksums.sha256 >/dev/null )

PRIVATE_DIR="$(grep '^private_dir: ' "$BACKUP/manifest.txt" | cut -d' ' -f2-)"

if [ -z "$PRIVATE_DIR" ]; then
    echo "The manifest does not say where the reports live. Stopping." >&2
    exit 1
fi

echo "② Database"
# Older backups, and any taken on a server where --set-gtid-purged was not
# available, carry a GTID_PURGED statement that the same server will not accept
# back. It spans two lines, so it is dropped as a block rather than by grep.
gunzip -c "$BACKUP/database.sql.gz" \
    | awk '/^SET @@GLOBAL.GTID_PURGED/ { skip = 1 } skip { if ( /;[[:space:]]*$/ ) { skip = 0 }; next } { print }' \
    | wp db import - --quiet

echo "③ Uploads"
rm -rf wp-content/uploads
tar -xzf "$BACKUP/uploads.tar.gz" -C wp-content

echo "④ Private reports  ($PRIVATE_DIR)"
rm -rf "${PRIVATE_DIR:?}"
tar -xzf "$BACKUP/private-reports.tar.gz" -C "$(dirname "$PRIVATE_DIR")"
chmod 700 "$PRIVATE_DIR"

echo "⑤ Flushing caches"
wp cache flush >/dev/null 2>&1 || true
wp rewrite flush --hard >/dev/null 2>&1 || true

echo
echo "Restored. Check a delivered order's download link before telling anyone it worked."
