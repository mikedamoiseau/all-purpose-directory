<?php
/**
 * Global helper functions for All Purpose Directory.
 *
 * @package APD
 */

declare(strict_types=1);

// Prevent direct web access (but allow CLI/testing).
if (! defined('ABSPATH') && ! defined('APD_TESTING') && PHP_SAPI !== 'cli') {
    exit;
}

// Helper functions will be added here as the plugin is developed.
