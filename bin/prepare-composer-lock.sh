#!/bin/sh
# Copyright (C) 2026 M10-Solutions
# SPDX-License-Identifier: GPL-3.0-or-later
# Run only in a clean development checkout, never on production data.
set -eu
cd "$(dirname "$0")/.."
# Build with the actual PHP runtime and extensions; no application container is started.
docker compose build homehub
image=$(docker compose config --images | head -n 1)
# Use a temporary directory containing only the manifest and existing lock.
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT HUP INT TERM
cp composer.json "$work/"
if [ -f composer.lock ]; then cp composer.lock "$work/"; fi
run_composer() {
  docker run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
    -v "$work:/workspace" -w /workspace --entrypoint composer "$image" "$@"
}
if [ ! -f "$work/composer.lock" ]; then
  run_composer update --no-install --no-interaction --prefer-dist
fi
run_composer validate --strict
run_composer audit --locked --no-dev
run_composer licenses --locked --no-dev --format=json > "$work/composer-licenses.json"
cp "$work/composer.lock" composer.lock
cp "$work/composer-licenses.json" docs/composer-licenses.json
printf '%s\n' 'Lockfile and license inventory created. Review licenses, rebuild and run strict QA.'
