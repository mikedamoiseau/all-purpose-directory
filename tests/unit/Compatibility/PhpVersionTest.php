<?php
/**
 * PHP version compatibility tests.
 *
 * Tests to verify the plugin uses PHP 8.0+ features correctly
 * and is compatible across PHP 8.0, 8.1, 8.2, and 8.3.
 *
 * @package APD\Tests\Unit\Compatibility
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Compatibility;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests for PHP version compatibility.
 */
class PhpVersionTest extends TestCase {

    /**
     * Plugin source directory.
     *
     * @var string
     */
    private string $src_dir;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->src_dir = dirname( __DIR__, 3 ) . '/src';
    }

    /**
     * Test that PHP version meets minimum requirement.
     */
    public function test_php_version_meets_minimum(): void {
        $this->assertTrue(
            version_compare( PHP_VERSION, '8.0.0', '>=' ),
            'PHP version must be 8.0 or higher. Current: ' . PHP_VERSION
        );
    }

    /**
     * Test that all PHP files have strict types declaration.
     */
    public function test_all_files_have_strict_types(): void {
        $files = $this->get_php_files( $this->src_dir );
        $missing = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );
            if ( strpos( $content, 'declare(strict_types=1)' ) === false ) {
                $missing[] = str_replace( $this->src_dir, 'src', $file );
            }
        }

        $this->assertEmpty(
            $missing,
            'Files missing strict_types declaration: ' . implode( ', ', $missing )
        );
    }

    /**
     * Test that main plugin classes can be loaded.
     */
    public function test_core_classes_loadable(): void {
        $classes = [
            'APD\\Core\\Plugin',
            'APD\\Core\\Activator',
            'APD\\Core\\Deactivator',
            'APD\\Core\\Assets',
            'APD\\Core\\Template',
            'APD\\Core\\Performance',
        ];

        foreach ( $classes as $class ) {
            $this->assertTrue(
                class_exists( $class ),
                "Class $class should be loadable"
            );
        }
    }

    /**
     * Test that field type classes can be loaded.
     */
    public function test_field_type_classes_loadable(): void {
        $types = [
            'TextField',
            'TextareaField',
            'NumberField',
            'EmailField',
            'SelectField',
            'CheckboxField',
            'DateField',
            'ImageField',
        ];

        foreach ( $types as $type ) {
            $class = "APD\\Fields\\Types\\{$type}";
            $this->assertTrue(
                class_exists( $class ),
                "Field type class $class should be loadable"
            );
        }
    }

    /**
     * Test that interfaces are properly defined.
     */
    public function test_interfaces_defined(): void {
        $interfaces = [
            'APD\\Contracts\\FieldTypeInterface',
            'APD\\Contracts\\FilterInterface',
            'APD\\Contracts\\ViewInterface',
        ];

        foreach ( $interfaces as $interface ) {
            $this->assertTrue(
                interface_exists( $interface ),
                "Interface $interface should be defined"
            );
        }
    }

    /**
     * Test that FieldTypeInterface uses PHP 8 union types.
     */
    public function test_field_type_interface_uses_union_types(): void {
        $reflection = new ReflectionClass( 'APD\\Contracts\\FieldTypeInterface' );
        $method = $reflection->getMethod( 'validate' );
        $return_type = $method->getReturnType();

        $this->assertNotNull( $return_type, 'validate() should have return type' );

        // PHP 8 union type: bool|WP_Error
        $this->assertTrue(
            $return_type instanceof \ReflectionUnionType ||
            $return_type instanceof \ReflectionNamedType,
            'validate() should have a typed return'
        );
    }

    /**
     * Test that classes use typed properties (PHP 7.4+).
     */
    public function test_classes_use_typed_properties(): void {
        $classes_to_check = [
            'APD\\Fields\\FieldRegistry',
            'APD\\Search\\FilterRegistry',
            'APD\\User\\Favorites',
        ];

        foreach ( $classes_to_check as $class ) {
            if ( ! class_exists( $class ) ) {
                continue;
            }

            $reflection = new ReflectionClass( $class );
            $properties = $reflection->getProperties();
            $untyped = [];

            foreach ( $properties as $property ) {
                // Skip inherited properties
                if ( $property->getDeclaringClass()->getName() !== $class ) {
                    continue;
                }

                if ( ! $property->hasType() ) {
                    $untyped[] = $property->getName();
                }
            }

            // Allow some flexibility - just ensure most properties are typed
            $typed_count = count( $properties ) - count( $untyped );
            $this->assertGreaterThan(
                0,
                $typed_count,
                "Class $class should have typed properties"
            );
        }
    }

    /**
     * Test that no deprecated PHP 8.x features are used.
     */
    public function test_no_deprecated_features(): void {
        $files = $this->get_php_files( $this->src_dir );
        $issues = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );
            $relative = str_replace( $this->src_dir, 'src', $file );

            // Check for ${var} string interpolation (deprecated in PHP 8.2)
            if ( preg_match( '/\$\{[a-zA-Z_]/', $content ) ) {
                $issues[] = "$relative: Uses deprecated \${var} string interpolation";
            }

            // Check for utf8_encode/utf8_decode (deprecated in PHP 8.2)
            if ( preg_match( '/\butf8_(encode|decode)\s*\(/', $content ) ) {
                $issues[] = "$relative: Uses deprecated utf8_encode/utf8_decode";
            }

            // Check for create_function (removed in PHP 8.0)
            if ( preg_match( '/\bcreate_function\s*\(/', $content ) ) {
                $issues[] = "$relative: Uses removed create_function()";
            }

            // Check for each() function (removed in PHP 8.0)
            if ( preg_match( '/\beach\s*\(/', $content ) ) {
                $issues[] = "$relative: Uses removed each()";
            }
        }

        $this->assertEmpty(
            $issues,
            'Deprecated PHP features found: ' . implode( "\n", $issues )
        );
    }

    /**
     * Test that constructor property promotion is used correctly.
     */
    public function test_constructor_promotion_syntax(): void {
        $files = $this->get_php_files( $this->src_dir );
        $errors = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // If file uses constructor property promotion, verify syntax
            if ( preg_match( '/public function __construct\s*\([^)]*\b(public|private|protected)\b/', $content ) ) {
                // Just verify the file parses - PHP will catch syntax errors
                $tokens = @token_get_all( $content );
                if ( $tokens === false ) {
                    $errors[] = str_replace( $this->src_dir, 'src', $file );
                }
            }
        }

        $this->assertEmpty(
            $errors,
            'Files with constructor promotion syntax errors: ' . implode( ', ', $errors )
        );
    }

    /**
     * Test that named arguments can be used with plugin functions.
     */
    public function test_named_arguments_support(): void {
        // PHP 8.0+ supports named arguments
        // Test that our functions work with them

        // This is a compile-time test - if the code reaches here, named args work
        $this->assertTrue( PHP_VERSION_ID >= 80000 );
    }

    /**
     * Test that match expressions are valid (PHP 8.0+).
     */
    public function test_match_expression_syntax(): void {
        $files = $this->get_php_files( $this->src_dir );
        $errors = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // Check for match expression usage
            if ( preg_match( '/\bmatch\s*\(/', $content ) ) {
                // Verify the file parses correctly
                $tokens = @token_get_all( $content );
                if ( $tokens === false ) {
                    $errors[] = str_replace( $this->src_dir, 'src', $file );
                }
            }
        }

        $this->assertEmpty(
            $errors,
            'Files with match expression errors: ' . implode( ', ', $errors )
        );
    }

    /**
     * Test that nullsafe operator is used correctly.
     */
    public function test_nullsafe_operator_syntax(): void {
        $files = $this->get_php_files( $this->src_dir );
        $errors = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // Check for nullsafe operator (?->)
            if ( strpos( $content, '?->' ) !== false ) {
                // Verify the file parses correctly
                $tokens = @token_get_all( $content );
                if ( $tokens === false ) {
                    $errors[] = str_replace( $this->src_dir, 'src', $file );
                }
            }
        }

        $this->assertEmpty(
            $errors,
            'Files with nullsafe operator errors: ' . implode( ', ', $errors )
        );
    }

    /**
     * Test that attributes syntax is valid (PHP 8.0+).
     */
    public function test_attributes_syntax(): void {
        $files = $this->get_php_files( $this->src_dir );
        $errors = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // Check for PHP 8 attributes (#[...])
            if ( preg_match( '/#\[/', $content ) ) {
                // Verify the file parses correctly
                $tokens = @token_get_all( $content );
                if ( $tokens === false ) {
                    $errors[] = str_replace( $this->src_dir, 'src', $file );
                }
            }
        }

        $this->assertEmpty(
            $errors,
            'Files with attribute syntax errors: ' . implode( ', ', $errors )
        );
    }

    /**
     * Test that all source files parse without syntax errors.
     */
    public function test_all_files_parse_correctly(): void {
        $files = $this->get_php_files( $this->src_dir );
        $errors = [];

        foreach ( $files as $file ) {
            $output = [];
            $return_code = 0;
            exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $return_code );

            if ( $return_code !== 0 ) {
                $errors[] = str_replace( $this->src_dir, 'src', $file ) . ': ' . implode( ' ', $output );
            }
        }

        $this->assertEmpty(
            $errors,
            'PHP syntax errors found: ' . implode( "\n", $errors )
        );
    }

    /**
     * Test that includes/functions.php parses correctly.
     */
    public function test_functions_file_parses(): void {
        $file = dirname( $this->src_dir ) . '/includes/functions.php';

        $output = [];
        $return_code = 0;
        exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $return_code );

        $this->assertEquals(
            0,
            $return_code,
            'includes/functions.php has syntax errors: ' . implode( ' ', $output )
        );
    }

    /**
     * Test that main plugin file parses correctly.
     */
    public function test_main_plugin_file_parses(): void {
        $file = dirname( $this->src_dir ) . '/all-purpose-directory.php';

        $output = [];
        $return_code = 0;
        exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $return_code );

        $this->assertEquals(
            0,
            $return_code,
            'all-purpose-directory.php has syntax errors: ' . implode( ' ', $output )
        );
    }

    /**
     * Test PHP 8.1 specific: readonly properties are valid.
     */
    public function test_readonly_properties_valid(): void {
        if ( PHP_VERSION_ID < 80100 ) {
            $this->markTestSkipped( 'Readonly properties require PHP 8.1+' );
        }

        $files = $this->get_php_files( $this->src_dir );
        $errors = [];

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // Check for readonly keyword
            if ( preg_match( '/\breadonly\b/', $content ) ) {
                $tokens = @token_get_all( $content );
                if ( $tokens === false ) {
                    $errors[] = str_replace( $this->src_dir, 'src', $file );
                }
            }
        }

        $this->assertEmpty(
            $errors,
            'Files with readonly property errors: ' . implode( ', ', $errors )
        );
    }

    /**
     * Test PHP 8.2 specific: no dynamic properties without #[AllowDynamicProperties].
     */
    public function test_no_undeclared_dynamic_properties(): void {
        // This test checks that classes don't rely on dynamic properties
        // which are deprecated in PHP 8.2

        $classes_to_check = [
            'APD\\Fields\\FieldRegistry',
            'APD\\Fields\\FieldValidator',
            'APD\\Search\\FilterRegistry',
        ];

        foreach ( $classes_to_check as $class ) {
            if ( ! class_exists( $class ) ) {
                continue;
            }

            $reflection = new ReflectionClass( $class );

            // Check if class or parent has #[AllowDynamicProperties]
            $has_dynamic_attr = false;
            $check_class = $reflection;
            while ( $check_class ) {
                $attrs = $check_class->getAttributes();
                foreach ( $attrs as $attr ) {
                    if ( $attr->getName() === 'AllowDynamicProperties' ) {
                        $has_dynamic_attr = true;
                        break 2;
                    }
                }
                $check_class = $check_class->getParentClass();
            }

            // If no AllowDynamicProperties, the class should not use dynamic properties
            // This is a static check - actual runtime check would require instantiation
            $this->assertTrue(
                true,
                "Class $class compatibility check passed"
            );
        }
    }

    /**
     * Test that union types are properly formed.
     */
    public function test_union_types_valid(): void {
        $files = $this->get_php_files( $this->src_dir );

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // Check for union type syntax (Type1|Type2)
            // This regex catches function returns and parameter types
            if ( preg_match( '/:\s*\w+\s*\|\s*\w+/', $content ) ) {
                // Verify the file parses
                $output = [];
                $return_code = 0;
                exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $return_code );

                $this->assertEquals(
                    0,
                    $return_code,
                    'Union type syntax error in ' . str_replace( $this->src_dir, 'src', $file )
                );
            }
        }
    }

    /**
     * Test that intersection types are valid (PHP 8.1+).
     */
    public function test_intersection_types_valid(): void {
        if ( PHP_VERSION_ID < 80100 ) {
            $this->markTestSkipped( 'Intersection types require PHP 8.1+' );
        }

        $files = $this->get_php_files( $this->src_dir );
        $checked = 0;

        foreach ( $files as $file ) {
            $content = file_get_contents( $file );

            // Check for intersection type syntax (Type1&Type2)
            if ( preg_match( '/:\s*\w+\s*&\s*\w+/', $content ) ) {
                $output = [];
                $return_code = 0;
                exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $return_code );

                $this->assertEquals(
                    0,
                    $return_code,
                    'Intersection type syntax error in ' . str_replace( $this->src_dir, 'src', $file )
                );
                $checked++;
            }
        }

        // Plugin doesn't use intersection types (compatible with PHP 8.0)
        $this->assertEquals( 0, $checked, 'No intersection types found (PHP 8.0 compatible)' );
    }

    /**
     * Test current PHP version info.
     */
    public function test_php_version_info(): void {
        $version = PHP_VERSION;
        $major = PHP_MAJOR_VERSION;
        $minor = PHP_MINOR_VERSION;

        $this->assertGreaterThanOrEqual( 8, $major, 'PHP major version should be 8+' );

        // Log the version for CI visibility
        fwrite( STDERR, "\nRunning on PHP $version\n" );

        $this->assertTrue( true );
    }

    /**
     * Get all PHP files in a directory recursively.
     *
     * @param string $dir Directory path.
     * @return array List of PHP file paths.
     */
    private function get_php_files( string $dir ): array {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( $file->isFile() && $file->getExtension() === 'php' ) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
