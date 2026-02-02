<?php
/**
 * PHPUnit bootstrap file for unit tests.
 *
 * This bootstrap uses Brain Monkey to mock WordPress functions,
 * allowing fast unit tests without a WordPress installation.
 *
 * @package APD\Tests\Unit
 */

declare(strict_types=1);

// Define test constants.
define('APD_TESTING', true);
define('ABSPATH', '/tmp/wordpress/');

// Load Composer autoloader.
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Load test case base class.
require_once __DIR__ . '/UnitTestCase.php';
