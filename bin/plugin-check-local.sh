#!/usr/bin/env bash
set -euo pipefail

# Deterministic local WordPress Plugin Check runner (outside GitHub Actions).
#
# Requirements:
# - Docker + docker-compose
# - Local WP test env at /home/mike/Documents/www/test/wordpress
#
# Behavior:
# 1) Starts the WP test stack
# 2) Syncs current plugin working tree into test plugins dir
# 3) Installs wp-cli PHAR in container (if missing)
# 4) Runs `wp plugin check` with fixed, stable flags and a hard timeout
# 5) Stops the test stack (unless KEEP_STACK=1)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
WP_ENV_ROOT="${WP_ENV_ROOT:-/home/mike/Documents/www/test/wordpress}"
WP_PLUGIN_DIR="$WP_ENV_ROOT/html/web/app/plugins/all-purpose-directory"
BUILD_DIR="${BUILD_DIR:-/tmp/apd-plugin-check-build}"
DOCKER_COMPOSE="docker-compose"
PHP_SERVICE="php"
TIMEOUT_SECS="${PLUGIN_CHECK_TIMEOUT:-300}"
KEEP_STACK="${KEEP_STACK:-0}"

if ! command -v "$DOCKER_COMPOSE" >/dev/null 2>&1; then
  echo "Error: docker-compose is required." >&2
  exit 1
fi

if ! command -v timeout >/dev/null 2>&1; then
  echo "Error: timeout command is required (coreutils)." >&2
  exit 1
fi

if [[ ! -d "$WP_ENV_ROOT" ]]; then
  echo "Error: WP env not found at $WP_ENV_ROOT" >&2
  exit 1
fi

cleanup() {
  if [[ "$KEEP_STACK" != "1" ]]; then
    "$DOCKER_COMPOSE" -f "$WP_ENV_ROOT/docker-compose.yml" down >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

echo "> Starting local WP test stack..."
"$DOCKER_COMPOSE" -f "$WP_ENV_ROOT/docker-compose.yml" up -d >/dev/null

echo "> Building distribution-like plugin payload..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"
rsync -a --delete \
  --exclude '.git' \
  --exclude-from "$PLUGIN_ROOT/.distignore" \
  "$PLUGIN_ROOT/" "$BUILD_DIR/"

echo "> Syncing built payload into local WP env..."
mkdir -p "$WP_PLUGIN_DIR"
rsync -a --delete "$BUILD_DIR/" "$WP_PLUGIN_DIR/"

echo "> Ensuring wp-cli is available in container..."
"$DOCKER_COMPOSE" -f "$WP_ENV_ROOT/docker-compose.yml" exec -T "$PHP_SERVICE" bash -lc '
  if [ ! -f /tmp/wp-cli.phar ]; then
    curl -sSLo /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  fi
'

echo "> Running WordPress Plugin Check (timeout: ${TIMEOUT_SECS}s)..."
set +e
timeout "$TIMEOUT_SECS" "$DOCKER_COMPOSE" -f "$WP_ENV_ROOT/docker-compose.yml" exec -T "$PHP_SERVICE" bash -lc '
  cd /var/www/html &&
  php /tmp/wp-cli.phar plugin check all-purpose-directory \
    --path=/var/www/html/web/wp \
    --allow-root \
    --skip-themes \
    --format=table
'
RC=$?
set -e

if [[ $RC -eq 124 ]]; then
  echo "\nPlugin check timed out after ${TIMEOUT_SECS}s" >&2
  exit 124
fi

exit $RC
