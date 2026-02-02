<?php
/**
 * PHPUnit bootstrap file for unit tests.
 *
 * This bootstrap uses Brain Monkey to mock WordPress functions,
 * allowing fast unit tests without a WordPress installation.
 *
 * IMPORTANT: APD_TESTING must be defined before the autoloader loads
 * includes/functions.php, which checks for this constant.
 *
 * @package APD\Tests\Unit
 */

declare(strict_types=1);

// Define APD_TESTING FIRST - the autoloader will load functions.php which checks this.
if ( ! defined( 'APD_TESTING' ) ) {
    define( 'APD_TESTING', true );
}

// Define ABSPATH for WordPress compatibility.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

// Define plugin constants needed by source files.
if ( ! defined( 'APD_VERSION' ) ) {
    define( 'APD_VERSION', '1.0.0' );
}
if ( ! defined( 'APD_MIN_PHP_VERSION' ) ) {
    define( 'APD_MIN_PHP_VERSION', '8.0' );
}
if ( ! defined( 'APD_MIN_WP_VERSION' ) ) {
    define( 'APD_MIN_WP_VERSION', '6.0' );
}
if ( ! defined( 'APD_PLUGIN_FILE' ) ) {
    define( 'APD_PLUGIN_FILE', dirname( __DIR__, 2 ) . '/all-purpose-directory.php' );
}
if ( ! defined( 'APD_PLUGIN_DIR' ) ) {
    define( 'APD_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'APD_PLUGIN_URL' ) ) {
    define( 'APD_PLUGIN_URL', 'https://example.com/wp-content/plugins/all-purpose-directory/' );
}
if ( ! defined( 'APD_PLUGIN_BASENAME' ) ) {
    define( 'APD_PLUGIN_BASENAME', 'all-purpose-directory/all-purpose-directory.php' );
}

// Load Composer autoloader.
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Load test case base class.
require_once __DIR__ . '/UnitTestCase.php';
