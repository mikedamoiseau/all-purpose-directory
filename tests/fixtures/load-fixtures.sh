#!/bin/bash
#
# Load SQL fixtures into the test database.
#
# Usage:
#   ./load-fixtures.sh [fixture_name]
#
# Examples:
#   ./load-fixtures.sh              # Load all fixtures
#   ./load-fixtures.sh listings     # Load only listings fixture
#   ./load-fixtures.sh categories   # Load only categories fixture
#   ./load-fixtures.sh reviews      # Load only reviews fixture
#
# Environment variables:
#   DB_NAME     - Database name (default: wordpress_test)
#   DB_USER     - Database user (default: root)
#   DB_PASS     - Database password (default: root)
#   DB_HOST     - Database host (default: mysql)
#   TABLE_PREFIX - WordPress table prefix (default: wp_)

set -e

# Default values (can be overridden by environment variables)
DB_NAME="${DB_NAME:-wordpress_test}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"
DB_HOST="${DB_HOST:-mysql}"
TABLE_PREFIX="${TABLE_PREFIX:-wp_}"

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# MySQL command with credentials
mysql_cmd() {
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
}

# Load a fixture file
load_fixture() {
    local fixture_name="$1"
    local fixture_file="${SCRIPT_DIR}/sample-${fixture_name}.sql"

    if [[ -f "$fixture_file" ]]; then
        echo "Loading fixture: $fixture_name"
        mysql_cmd < "$fixture_file"
        echo "  ✓ Loaded $fixture_name"
    else
        echo "  ✗ Fixture not found: $fixture_file"
        return 1
    fi
}

# Main execution
main() {
    echo "Loading APD test fixtures..."
    echo "Database: $DB_NAME @ $DB_HOST"
    echo ""

    if [[ -n "$1" ]]; then
        # Load specific fixture
        load_fixture "$1"
    else
        # Load all fixtures in order
        load_fixture "categories"
        load_fixture "listings"
        load_fixture "reviews"
    fi

    echo ""
    echo "Fixtures loaded successfully!"
}

main "$@"
