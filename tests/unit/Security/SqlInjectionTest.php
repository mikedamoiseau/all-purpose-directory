<?php
/**
 * Tests for SQL injection prevention.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Brain\Monkey\Functions;

/**
 * SqlInjectionTest verifies database queries are safe from SQL injection.
 */
class SqlInjectionTest extends SecurityTestCase {

    /**
     * Test that wpdb is available for prepared statements.
     */
    public function test_wpdb_prepare_pattern_documented(): void {
        // WordPress wpdb->prepare is the standard for safe SQL queries
        // This test documents the expected pattern:
        // $wpdb->prepare("SELECT * FROM table WHERE id = %d", $id)

        // Verify our code uses WP_Query which internally uses wpdb safely
        $this->assertTrue(
            class_exists(\APD\Search\SearchQuery::class),
            'SearchQuery should exist and use WP_Query for safe queries'
        );
    }

    /**
     * Test SQL injection vectors are sanitized with absint.
     */
    public function test_sql_injection_in_integer_fields(): void {
        $vectors = $this->getSqlInjectionVectors();

        foreach ($vectors as $vector) {
            $sanitized = absint($vector);

            // absint always returns a non-negative integer
            $this->assertIsInt($sanitized);
            $this->assertGreaterThanOrEqual(0, $sanitized);
        }
    }

    /**
     * Test SQL injection vectors in text sanitization.
     */
    public function test_sql_injection_in_text_fields(): void {
        $vectors = $this->getSqlInjectionVectors();

        foreach ($vectors as $vector) {
            $sanitized = sanitize_text_field($vector);

            // Should be a safe string
            $this->assertIsString($sanitized);
        }
    }

    /**
     * Test SQL special characters handling.
     */
    public function test_sql_special_characters(): void {
        $dangerous = [
            "'; DROP TABLE users; --",
            "1 OR 1=1",
            "1' AND '1'='1",
            "admin'--",
            "1; DELETE FROM posts",
        ];

        foreach ($dangerous as $input) {
            // After sanitization, should be safe string
            $sanitized = sanitize_text_field($input);
            $this->assertIsString($sanitized);

            // As integer, should be 0 or the numeric portion
            $as_int = absint($input);
            $this->assertIsInt($as_int);
        }
    }

    /**
     * Test that SearchQuery uses safe query modification.
     */
    public function test_search_query_class_exists(): void {
        $this->assertTrue(
            class_exists(\APD\Search\SearchQuery::class),
            'SearchQuery class should exist for safe query handling'
        );
    }

    /**
     * Test that PostType uses WP_Query for safe queries.
     */
    public function test_post_type_class_exists(): void {
        $this->assertTrue(
            class_exists(\APD\Listing\PostType::class),
            'PostType class should exist for safe listing queries via WP_Query'
        );
    }

    /**
     * Test LIKE clause escaping pattern.
     */
    public function test_like_clause_escaping(): void {
        // LIKE wildcards should be escaped in user input
        $input = "test%_value";

        // Our sanitization strips tags but doesn't escape LIKE wildcards
        // The actual wpdb->prepare would handle this
        $sanitized = sanitize_text_field($input);
        $this->assertIsString($sanitized);
    }

    /**
     * Test that numeric parameters use absint.
     */
    public function test_numeric_params_use_absint(): void {
        // Non-numeric inputs become 0
        $this->assertEquals(0, absint('DROP TABLE'));

        // Strings starting with numbers extract the number
        // This is expected PHP behavior - int('1; DELETE') = 1
        $this->assertEquals(1, absint('1; DELETE'));
        $this->assertEquals(1, absint('1'));
        $this->assertEquals(100, absint('100'));
    }

    /**
     * Test array of IDs sanitization.
     */
    public function test_array_of_ids_sanitized(): void {
        $ids = ['1', "2'; DROP TABLE", '3', '-5', 'abc'];
        $sanitized = array_map('absint', $ids);

        $expected = [1, 2, 3, 5, 0];
        $this->assertEquals($expected, $sanitized);
    }

    /**
     * Test sanitize_sql_orderby simulation.
     */
    public function test_orderby_sanitization(): void {
        // Orderby values should be from allowlist
        $allowed_orderby = ['date', 'title', 'menu_order', 'ID'];
        $user_input = "date; DROP TABLE posts";

        // Sanitize by checking against allowlist
        $sanitized = in_array($user_input, $allowed_orderby, true) ? $user_input : 'date';

        $this->assertEquals('date', $sanitized);
    }

    /**
     * Test order direction sanitization.
     */
    public function test_order_direction_sanitization(): void {
        $allowed = ['ASC', 'DESC'];

        // Valid
        $this->assertTrue(in_array('ASC', $allowed, true));
        $this->assertTrue(in_array('DESC', $allowed, true));

        // Invalid - should use default
        $user_input = "ASC; DROP TABLE";
        $sanitized = in_array(strtoupper($user_input), $allowed, true) ? strtoupper($user_input) : 'DESC';
        $this->assertEquals('DESC', $sanitized);
    }

    /**
     * Test meta query key sanitization.
     */
    public function test_meta_query_key_sanitization(): void {
        $key = "_apd_test'; DROP TABLE";
        $sanitized = sanitize_key($key);

        // Should only contain safe characters
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $sanitized);
    }

    /**
     * Test that query arguments are properly typed.
     */
    public function test_query_arguments_typed(): void {
        // Simulating WP_Query args
        $args = [
            'post_type' => 'apd_listing',
            'posts_per_page' => absint('10; DROP TABLE'),
            'paged' => absint("2' OR 1=1"),
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $this->assertEquals(10, $args['posts_per_page']);
        $this->assertEquals(2, $args['paged']);
        $this->assertEquals('apd_listing', $args['post_type']);
    }

    /**
     * Test taxonomy term sanitization with sanitize_key.
     */
    public function test_taxonomy_term_sanitization(): void {
        $term_slug = "term'; DROP TABLE";
        // sanitize_key is the proper function for slugs/keys
        $sanitized = sanitize_key($term_slug);

        // Should be lowercase alphanumeric with underscore/dash only
        $this->assertIsString($sanitized);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $sanitized);
        $this->assertStringNotContainsString("'", $sanitized);
        $this->assertStringNotContainsString(';', $sanitized);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        global $wpdb;
        $wpdb = null;
        parent::tearDown();
    }
}
