#!/usr/bin/env bash
# Serve the local development site (PHP built-in server through WP-CLI).
#
#   ./scripts/serve.sh            → http://localhost:8080
#   PORT=8090 ./scripts/serve.sh
set -euo pipefail

cd "$(dirname "$0")/.."
PORT="${PORT:-8080}"

echo "http://localhost:$PORT  (Ctrl+C to stop)"
wp server --host=localhost --port="$PORT"
