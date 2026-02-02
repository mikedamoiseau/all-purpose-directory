#!/usr/bin/env bash

# =============================================================================
# WordPress Test Suite Installation Script
# =============================================================================
#
# This script installs the WordPress test suite for running integration tests.
# Run this script inside the Docker container before running integration tests.
#
# Usage:
#   ./bin/install-wp-tests.sh [db-name] [db-user] [db-pass] [db-host] [wp-version]
#
# Example:
#   ./bin/install-wp-tests.sh wordpress_test root root mysql latest
#
# =============================================================================

set -e

if [ $# -lt 3 ]; then
    echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
    exit 1
fi

DB_NAME=${1:-wordpress_test}
DB_USER=${2:-root}
DB_PASS=${3:-root}
DB_HOST=${4:-mysql}
WP_VERSION=${5:-latest}
SKIP_DB_CREATE=${6:-false}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
    if [ $(which curl) ]; then
        curl -s "$1" > "$2";
    elif [ $(which wget) ]; then
        wget -nv -O "$2" "$1"
    fi
}

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
    WP_BRANCH=${WP_VERSION%\-*}
    WP_TESTS_TAG="branches/$WP_BRANCH"
elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
    WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
    if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
        WP_TESTS_TAG="tags/${WP_VERSION%??}"
    else
        WP_TESTS_TAG="tags/$WP_VERSION"
    fi
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
    WP_TESTS_TAG="trunk"
else
    # http serves a hierarchical file listing, so grep can parse for the latest stable version
    download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
    grep -o '"version":"[^"]*' /tmp/wp-latest.json | head -1 | sed -E 's/"version":"([^"]+)"/\1/' > /tmp/wp-latest-version.txt
    WP_VERSION=$(cat /tmp/wp-latest-version.txt)
    if [[ -z "$WP_VERSION" ]]; then
        echo "Could not determine latest WordPress version"
        exit 1
    fi
    WP_TESTS_TAG="tags/$WP_VERSION"
fi
export WP_TESTS_TAG

install_wp() {
    if [ -d $WP_CORE_DIR ]; then
        echo "WordPress core already installed at $WP_CORE_DIR"
        return
    fi

    mkdir -p $WP_CORE_DIR

    if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        mkdir -p $TMPDIR/wordpress-trunk
        rm -rf $TMPDIR/wordpress-trunk/*
        svn export --quiet https://core.svn.wordpress.org/trunk $TMPDIR/wordpress-trunk/wordpress
        mv $TMPDIR/wordpress-trunk/wordpress/* $WP_CORE_DIR
    else
        if [ $WP_VERSION == 'latest' ]; then
            local ARCHIVE_NAME='latest'
        elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
            local ARCHIVE_NAME="wordpress-$WP_VERSION"
        else
            local ARCHIVE_NAME="wordpress-$WP_VERSION"
        fi
        download https://wordpress.org/${ARCHIVE_NAME}.tar.gz $TMPDIR/wordpress.tar.gz
        tar --strip-components=1 -zxf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR
    fi

    download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

install_test_suite() {
    # portable in-place argument for sed -i
    local ioession=$(echo "tmp")

    if [ -d $WP_TESTS_DIR ]; then
        echo "WordPress test suite already installed at $WP_TESTS_DIR"
        return
    fi

    # set up testing suite
    mkdir -p $WP_TESTS_DIR
    rm -rf $WP_TESTS_DIR/{includes,data}

    svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
    svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
}

install_db() {
    if [ ${SKIP_DB_CREATE} = "true" ]; then
        echo "Skipping database creation"
        return 0
    fi

    # parse DB_HOST for port or socket references
    local PARTS=(${DB_HOST//\:/ })
    local DB_HOSTNAME=${PARTS[0]};
    local DB_SOCK_OR_PORT=${PARTS[1]};
    local EXTRA=""

    if ! [ -z $DB_HOSTNAME ]; then
        if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
            EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
        elif ! [ -z $DB_SOCK_OR_PORT ]; then
            EXTRA=" --socket=$DB_SOCK_OR_PORT"
        elif ! [ -z $DB_HOSTNAME ]; then
            EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
        fi
    fi

    # Drop and recreate the test database
    mysqladmin drop $DB_NAME --force --silent --password="$DB_PASS" --user="$DB_USER"$EXTRA 2>/dev/null || true
    mysqladmin create $DB_NAME --password="$DB_PASS" --user="$DB_USER"$EXTRA --skip-ssl || \
    mysqladmin create $DB_NAME --password="$DB_PASS" --user="$DB_USER"$EXTRA

    echo "Database '$DB_NAME' created"
}

generate_wp_tests_config() {
    local WP_TESTS_CONFIG="$WP_TESTS_DIR/wp-tests-config.php"

    if [ -f $WP_TESTS_CONFIG ]; then
        echo "wp-tests-config.php already exists"
        return
    fi

    download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php $WP_TESTS_CONFIG

    # Use sed to replace placeholders - portable across GNU and BSD sed
    sed -i.bak "s|dirname( __FILE__ ) . '/src/'|'$WP_CORE_DIR/'|" $WP_TESTS_CONFIG
    sed -i.bak "s/youremptytestdbnamehere/$DB_NAME/" $WP_TESTS_CONFIG
    sed -i.bak "s/yourusernamehere/$DB_USER/" $WP_TESTS_CONFIG
    sed -i.bak "s/yourpasswordhere/$DB_PASS/" $WP_TESTS_CONFIG
    sed -i.bak "s|localhost|$DB_HOST|" $WP_TESTS_CONFIG
    rm -f $WP_TESTS_CONFIG.bak

    echo "Generated wp-tests-config.php"
}

echo "=========================================="
echo "Installing WordPress Test Suite"
echo "=========================================="
echo "DB_NAME:     $DB_NAME"
echo "DB_USER:     $DB_USER"
echo "DB_HOST:     $DB_HOST"
echo "WP_VERSION:  $WP_VERSION"
echo "WP_TESTS_DIR: $WP_TESTS_DIR"
echo "WP_CORE_DIR:  $WP_CORE_DIR"
echo "=========================================="

install_wp
install_test_suite
install_db
generate_wp_tests_config

echo "=========================================="
echo "WordPress Test Suite installed successfully!"
echo "=========================================="
