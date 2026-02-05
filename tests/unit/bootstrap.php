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

// Define WP_REST_Response class for unit tests if not already defined.
if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Minimal WP_REST_Response implementation for unit tests.
	 *
	 * This is a simplified version of WordPress's WP_REST_Response class
	 * that provides the essential functionality needed for testing.
	 */
	class WP_REST_Response {
		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		private mixed $data;

		/**
		 * HTTP status code.
		 *
		 * @var int
		 */
		private int $status;

		/**
		 * Response headers.
		 *
		 * @var array<string, string>
		 */
		private array $headers = [];

		/**
		 * Constructor.
		 *
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status code.
		 */
		public function __construct( mixed $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Get response data.
		 *
		 * @return mixed
		 */
		public function get_data(): mixed {
			return $this->data;
		}

		/**
		 * Set response data.
		 *
		 * @param mixed $data Response data.
		 */
		public function set_data( mixed $data ): void {
			$this->data = $data;
		}

		/**
		 * Get HTTP status code.
		 *
		 * @return int
		 */
		public function get_status(): int {
			return $this->status;
		}

		/**
		 * Set HTTP status code.
		 *
		 * @param int $status HTTP status code.
		 */
		public function set_status( int $status ): void {
			$this->status = $status;
		}

		/**
		 * Set a header.
		 *
		 * @param string $key   Header name.
		 * @param string $value Header value.
		 */
		public function header( string $key, string $value ): void {
			$this->headers[ $key ] = $value;
		}

		/**
		 * Get all headers.
		 *
		 * @return array<string, string>
		 */
		public function get_headers(): array {
			return $this->headers;
		}
	}
}

// Define WP_REST_Request class for unit tests if not already defined.
if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Minimal WP_REST_Request implementation for unit tests.
	 *
	 * This is a simplified version of WordPress's WP_REST_Request class
	 * that provides the essential functionality needed for testing.
	 */
	class WP_REST_Request {
		/**
		 * Request parameters.
		 *
		 * @var array<string, mixed>
		 */
		private array $params = [];

		/**
		 * Request headers.
		 *
		 * @var array<string, string>
		 */
		private array $headers = [];

		/**
		 * HTTP method.
		 *
		 * @var string
		 */
		private string $method = 'GET';

		/**
		 * Request route.
		 *
		 * @var string
		 */
		private string $route = '';

		/**
		 * Constructor.
		 *
		 * @param string $method HTTP method.
		 * @param string $route  Request route.
		 */
		public function __construct( string $method = 'GET', string $route = '' ) {
			$this->method = $method;
			$this->route  = $route;
		}

		/**
		 * Get a parameter value.
		 *
		 * @param string $key Parameter key.
		 * @return mixed
		 */
		public function get_param( string $key ): mixed {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * Set a parameter value.
		 *
		 * @param string $key   Parameter key.
		 * @param mixed  $value Parameter value.
		 */
		public function set_param( string $key, mixed $value ): void {
			$this->params[ $key ] = $value;
		}

		/**
		 * Get all parameters.
		 *
		 * @return array<string, mixed>
		 */
		public function get_params(): array {
			return $this->params;
		}

		/**
		 * Get a header value.
		 *
		 * @param string $key Header key.
		 * @return string|null
		 */
		public function get_header( string $key ): ?string {
			return $this->headers[ $key ] ?? null;
		}

		/**
		 * Set a header value.
		 *
		 * @param string $key   Header key.
		 * @param string $value Header value.
		 */
		public function set_header( string $key, string $value ): void {
			$this->headers[ $key ] = $value;
		}

		/**
		 * Get HTTP method.
		 *
		 * @return string
		 */
		public function get_method(): string {
			return $this->method;
		}

		/**
		 * Get route.
		 *
		 * @return string
		 */
		public function get_route(): string {
			return $this->route;
		}
	}
}

// Define WP_Post class for unit tests if not already defined.
if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Minimal WP_Post implementation for unit tests.
	 *
	 * Uses mixed types for flexibility in testing - WordPress's actual
	 * WP_Post doesn't strictly type properties.
	 */
	class WP_Post {
		/** @var mixed */
		public $ID = 0;
		/** @var mixed */
		public $post_author = '0';
		/** @var mixed */
		public $post_date = '';
		/** @var mixed */
		public $post_date_gmt = '';
		/** @var mixed */
		public $post_content = '';
		/** @var mixed */
		public $post_title = '';
		/** @var mixed */
		public $post_excerpt = '';
		/** @var mixed */
		public $post_status = 'publish';
		/** @var mixed */
		public $comment_status = 'open';
		/** @var mixed */
		public $ping_status = 'open';
		/** @var mixed */
		public $post_password = '';
		/** @var mixed */
		public $post_name = '';
		/** @var mixed */
		public $to_ping = '';
		/** @var mixed */
		public $pinged = '';
		/** @var mixed */
		public $post_modified = '';
		/** @var mixed */
		public $post_modified_gmt = '';
		/** @var mixed */
		public $post_content_filtered = '';
		/** @var mixed */
		public $post_parent = 0;
		/** @var mixed */
		public $guid = '';
		/** @var mixed */
		public $menu_order = 0;
		/** @var mixed */
		public $post_type = 'post';
		/** @var mixed */
		public $post_mime_type = '';
		/** @var mixed */
		public $comment_count = '0';
		/** @var mixed */
		public $filter = 'raw';

		/**
		 * Constructor.
		 *
		 * @param object|array|null $post Post data.
		 */
		public function __construct( object|array|null $post = null ) {
			if ( is_object( $post ) ) {
				foreach ( get_object_vars( $post ) as $key => $value ) {
					$this->$key = $value;
				}
			} elseif ( is_array( $post ) ) {
				foreach ( $post as $key => $value ) {
					$this->$key = $value;
				}
			}
		}
	}
}

// Define WP_REST_Server class for unit tests if not already defined.
if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Minimal WP_REST_Server implementation for unit tests.
	 */
	class WP_REST_Server {
		/**
		 * HTTP method constants.
		 */
		public const READABLE   = 'GET';
		public const CREATABLE  = 'POST';
		public const EDITABLE   = 'POST, PUT, PATCH';
		public const DELETABLE  = 'DELETE';
		public const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
	}
}

// Define WP_Term class for unit tests if not already defined.
if ( ! class_exists( 'WP_Term' ) ) {
	/**
	 * Minimal WP_Term implementation for unit tests.
	 */
	class WP_Term {
		/** @var mixed */
		public $term_id = 0;
		/** @var mixed */
		public $name = '';
		/** @var mixed */
		public $slug = '';
		/** @var mixed */
		public $term_group = 0;
		/** @var mixed */
		public $term_taxonomy_id = 0;
		/** @var mixed */
		public $taxonomy = '';
		/** @var mixed */
		public $description = '';
		/** @var mixed */
		public $parent = 0;
		/** @var mixed */
		public $count = 0;
		/** @var mixed */
		public $filter = 'raw';

		/**
		 * Constructor.
		 *
		 * @param object|array|null $term Term data.
		 */
		public function __construct( object|array|null $term = null ) {
			if ( is_object( $term ) ) {
				foreach ( get_object_vars( $term ) as $key => $value ) {
					$this->$key = $value;
				}
			} elseif ( is_array( $term ) ) {
				foreach ( $term as $key => $value ) {
					$this->$key = $value;
				}
			}
		}
	}
}

// Load helper functions for testing.
require_once dirname(__DIR__, 2) . '/includes/module-functions.php';

// Load test case base class.
require_once __DIR__ . '/UnitTestCase.php';
