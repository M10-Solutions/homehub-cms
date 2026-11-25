#!/bin/sh
# UAT-002f: prepare a separate, empty installation without touching existing data.
set -eu
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
DEST=${1:-}
if [ -z "$DEST" ]; then
  echo "Usage: sh bin/prepare-fresh-install-test.sh /absolute/path/to/new-test-directory" >&2
  exit 2
fi
case "$DEST" in /*) ;; *) echo 'Destination must be an absolute path.' >&2; exit 2;; esac
if [ -e "$DEST" ]; then
  echo "Refusing to use an existing destination: $DEST" >&2
  exit 1
fi
mkdir -p "$DEST"
# Copy application files, but NEVER copy live data, secrets, or local VCS metadata.
(cd "$ROOT" && tar --exclude='./data' --exclude='./.git' --exclude='./vendor' --exclude='./.env' --exclude='./.env.*' -cf - .) | (cd "$DEST" && tar -xf -)
mkdir -p "$DEST/data/config" "$DEST/data/content" "$DEST/data/upload"
cat > "$DEST/compose.uat-002f.yaml" <<'YAML'
services:
  homehub:
    container_name: homehub-uat-002f
    image: homehub-uat-002f:local
    ports: !override
      - "127.0.0.1:18011:80"
YAML
cat > "$DEST/UAT-002F-START.txt" <<'TEXT'
This is an ISOLATED TEST INSTALLATION. No production data was copied.
From this directory:
  docker compose -p homehub-uat-002f -f compose.yaml -f compose.uat-002f.yaml up -d --build
  docker compose -p homehub-uat-002f -f compose.yaml -f compose.uat-002f.yaml exec -u www-data homehub php bin/create-admin.php
  docker compose -p homehub-uat-002f -f compose.yaml -f compose.uat-002f.yaml ps
Access http://127.0.0.1:18011/ from the Docker host (or use SSH port forwarding).
Check that a SECOND create-admin attempt refuses to overwrite users.
Stop test instance (does NOT remove data):
  docker compose -p homehub-uat-002f -f compose.yaml -f compose.uat-002f.yaml down
IMPORTANT: Never run 'down -v' on your production stack. Keep test data for inspection.
Requires Docker Compose supporting !override (v2.24.4+).
TEXT
printf 'Prepared isolated UAT-002f test directory: %s\n' "$DEST"
cat "$DEST/UAT-002F-START.txt"
