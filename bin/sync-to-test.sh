#!/bin/bash
#
# Sync plugin to test environment
#
# Usage: ./bin/sync-to-test.sh
#
# This script syncs the plugin files to the Docker test environment
# for local testing. It excludes development files that aren't needed
# in the test environment.

set -e

PLUGIN_DIR="/Users/mike/Documents/www/private/all-purpose-directory"
TEST_PLUGIN_DIR="/Users/mike/Documents/www/test/wp-all-purpose-directory/html/wp-content/plugins/all-purpose-directory"

echo "Syncing plugin to test environment..."

rsync -av --delete \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='composer.lock' \
  --exclude='.phpunit.result.cache' \
  --exclude='.idea' \
  --exclude='.claude' \
  --exclude='.claude-bw' \
  --exclude='research' \
  --exclude='PLAN.md' \
  --exclude='TASKS.md' \
  "$PLUGIN_DIR/" \
  "$TEST_PLUGIN_DIR/"

echo "Done! Plugin synced to: $TEST_PLUGIN_DIR"
echo ""
echo "To activate the plugin in Docker:"
echo "  docker-compose exec web wp plugin activate all-purpose-directory"
