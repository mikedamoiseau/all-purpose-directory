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

// Define WP_Error class for unit tests if not already defined.
if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error implementation for unit tests.
	 *
	 * This is a simplified version of WordPress's WP_Error class
	 * that provides the essential functionality needed for testing.
	 */
	class WP_Error {
		/**
		 * Stores the error messages.
		 *
		 * @var array<string, array<string>>
		 */
		private array $errors = [];

		/**
		 * Stores error data.
		 *
		 * @var array<string, mixed>
		 */
		private array $error_data = [];

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function __construct( string $code = '', string $message = '', mixed $data = '' ) {
			if ( ! empty( $code ) ) {
				$this->add( $code, $message, $data );
			}
		}

		/**
		 * Add an error.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function add( string $code, string $message, mixed $data = '' ): void {
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		/**
		 * Check if there are errors.
		 *
		 * @return bool True if there are errors.
		 */
		public function has_errors(): bool {
			return ! empty( $this->errors );
		}

		/**
		 * Get error codes.
		 *
		 * @return array<string> Error codes.
		 */
		public function get_error_codes(): array {
			return array_keys( $this->errors );
		}

		/**
		 * Get error message for a code.
		 *
		 * @param string $code Error code.
		 * @return string Error message.
		 */
		public function get_error_message( string $code = '' ): string {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return $this->errors[ $code ][0] ?? '';
		}

		/**
		 * Get all error messages.
		 *
		 * @param string $code Optional. Error code.
		 * @return array<string> Error messages.
		 */
		public function get_error_messages( string $code = '' ): array {
			if ( empty( $code ) ) {
				$messages = [];
				foreach ( $this->errors as $error_messages ) {
					$messages = array_merge( $messages, $error_messages );
				}
				return $messages;
			}
			return $this->errors[ $code ] ?? [];
		}

		/**
		 * Get the first error code.
		 *
		 * @return string Error code.
		 */
		public function get_error_code(): string {
			$codes = $this->get_error_codes();
			return $codes[0] ?? '';
		}

		/**
		 * Get error data.
		 *
		 * @param string $code Error code.
		 * @return mixed Error data.
		 */
		public function get_error_data( string $code = '' ): mixed {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return $this->error_data[ $code ] ?? null;
		}
	}
}

// Define is_wp_error function for unit tests if not already defined.
if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Check if a value is a WP_Error.
	 *
	 * @param mixed $thing Value to check.
	 * @return bool True if WP_Error.
	 */
	function is_wp_error( mixed $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

// Define _doing_it_wrong function for unit tests if not already defined.
if ( ! function_exists( '_doing_it_wrong' ) ) {
	/**
	 * Mark something as being incorrectly called.
	 *
	 * @param string $function_name The function that was called.
	 * @param string $message       A message explaining what was done incorrectly.
	 * @param string $version       The version since the message was added.
	 */
	function _doing_it_wrong( string $function_name, string $message, string $version ): void {
		// In tests, we silently ignore this or could log it.
		// Could trigger a notice in debug mode if needed.
	}
}

// Load test case base class.
require_once __DIR__ . '/UnitTestCase.php';
