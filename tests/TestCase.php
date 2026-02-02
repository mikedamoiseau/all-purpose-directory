<?php
/**
 * Base test case for integration tests.
 *
 * Extends WP_UnitTestCase to provide WordPress testing capabilities.
 *
 * @package APD\Tests
 */

declare(strict_types=1);

namespace APD\Tests;

use WP_UnitTestCase;

/**
 * Base test case for integration tests.
 */
abstract class TestCase extends WP_UnitTestCase
{
    /**
     * Set up before each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        // Reset any plugin state between tests.
        $this->resetPluginState();
    }

    /**
     * Tear down after each test.
     */
    public function tearDown(): void
    {
        $this->resetPluginState();

        parent::tearDown();
    }

    /**
     * Reset plugin state between tests.
     */
    protected function resetPluginState(): void
    {
        // Clear any cached data, transients, etc.
        // This will be expanded as the plugin develops.
    }

    /**
     * Create a test listing.
     *
     * @param array $args Optional. Arguments to override defaults.
     * @return int The post ID.
     */
    protected function createListing(array $args = []): int
    {
        $defaults = [
            'post_type'   => 'apd_listing',
            'post_status' => 'publish',
            'post_title'  => 'Test Listing ' . wp_generate_uuid4(),
            'post_content' => 'Test listing content.',
        ];

        $args = wp_parse_args($args, $defaults);

        return $this->factory()->post->create($args);
    }

    /**
     * Create a test category.
     *
     * @param array $args Optional. Arguments to override defaults.
     * @return int The term ID.
     */
    protected function createCategory(array $args = []): int
    {
        $defaults = [
            'taxonomy' => 'apd_category',
            'name'     => 'Test Category ' . wp_generate_uuid4(),
        ];

        $args = wp_parse_args($args, $defaults);

        return $this->factory()->term->create($args);
    }

    /**
     * Assert that a post meta value equals expected value.
     *
     * @param int    $post_id  The post ID.
     * @param string $meta_key The meta key.
     * @param mixed  $expected The expected value.
     * @param string $message  Optional. Assertion message.
     */
    protected function assertPostMeta(int $post_id, string $meta_key, mixed $expected, string $message = ''): void
    {
        $actual = get_post_meta($post_id, $meta_key, true);
        $this->assertEquals($expected, $actual, $message);
    }
}
