<?php
/**
 * PHPUnit bootstrap file for integration tests.
 *
 * This bootstrap loads the WordPress test suite and activates the plugin.
 * Integration tests require a running WordPress installation (via Docker).
 *
 * @package APD\Tests
 */

declare(strict_types=1);

// Define test constants.
define('APD_TESTING', true);

// Get the tests directory.
$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Check if WordPress test suite is installed.
if (! file_exists("{$_tests_dir}/includes/functions.php")) {
    echo "Could not find {$_tests_dir}/includes/functions.php\n";
    echo "Please run: bin/install-wp-tests.sh wordpress_test root root mysql latest\n";
    exit(1);
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin(): void {
    // Load Composer autoloader.
    require dirname(__DIR__) . '/vendor/autoload.php';

    // Load the main plugin file.
    $plugin_file = dirname(__DIR__) . '/all-purpose-directory.php';

    if (file_exists($plugin_file)) {
        require $plugin_file;
    }
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Load test case base classes and utilities.
require_once __DIR__ . '/TestCase.php';
