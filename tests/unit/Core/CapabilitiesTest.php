<?php
/**
 * Tests for the Capabilities class.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Capabilities;
use APD\Tests\Unit\UnitTestCase;

/**
 * Tests for the Capabilities class.
 */
class CapabilitiesTest extends UnitTestCase
{
    /**
     * Test that all capabilities have the correct prefix.
     */
    public function test_capabilities_have_apd_prefix(): void
    {
        $capabilities = Capabilities::get_all();

        foreach ($capabilities as $cap) {
            $this->assertStringContainsString(
                '_apd_',
                $cap,
                "Capability '{$cap}' should contain '_apd_' prefix"
            );
        }
    }

    /**
     * Test that get_all returns all capability constants.
     */
    public function test_get_all_returns_all_capabilities(): void
    {
        $capabilities = Capabilities::get_all();

        $expectedCapabilities = [
            Capabilities::EDIT_LISTING,
            Capabilities::READ_LISTING,
            Capabilities::DELETE_LISTING,
            Capabilities::EDIT_LISTINGS,
            Capabilities::EDIT_OTHERS_LISTINGS,
            Capabilities::PUBLISH_LISTINGS,
            Capabilities::READ_PRIVATE_LISTINGS,
            Capabilities::DELETE_LISTINGS,
            Capabilities::DELETE_PRIVATE_LISTINGS,
            Capabilities::DELETE_PUBLISHED_LISTINGS,
            Capabilities::DELETE_OTHERS_LISTINGS,
            Capabilities::EDIT_PRIVATE_LISTINGS,
            Capabilities::EDIT_PUBLISHED_LISTINGS,
            Capabilities::MANAGE_CATEGORIES,
            Capabilities::MANAGE_TAGS,
        ];

        $this->assertCount(count($expectedCapabilities), $capabilities);

        foreach ($expectedCapabilities as $expected) {
            $this->assertContains($expected, $capabilities);
        }
    }

    /**
     * Test that editor capabilities are a subset of all capabilities.
     */
    public function test_editor_capabilities_are_subset_of_all(): void
    {
        $all = Capabilities::get_all();
        $editor = Capabilities::get_editor_capabilities();

        foreach ($editor as $cap) {
            $this->assertContains(
                $cap,
                $all,
                "Editor capability '{$cap}' should be in all capabilities"
            );
        }
    }

    /**
     * Test that author capabilities are a subset of all capabilities.
     */
    public function test_author_capabilities_are_subset_of_all(): void
    {
        $all = Capabilities::get_all();
        $author = Capabilities::get_author_capabilities();

        foreach ($author as $cap) {
            $this->assertContains(
                $cap,
                $all,
                "Author capability '{$cap}' should be in all capabilities"
            );
        }
    }

    /**
     * Test that author capabilities are a subset of editor capabilities.
     */
    public function test_author_capabilities_are_subset_of_editor(): void
    {
        $editor = Capabilities::get_editor_capabilities();
        $author = Capabilities::get_author_capabilities();

        foreach ($author as $cap) {
            $this->assertContains(
                $cap,
                $editor,
                "Author capability '{$cap}' should be in editor capabilities"
            );
        }
    }

    /**
     * Test that capability constants are defined.
     */
    public function test_capability_constants_are_defined(): void
    {
        $expectedConstants = [
            'EDIT_LISTING',
            'READ_LISTING',
            'DELETE_LISTING',
            'EDIT_LISTINGS',
            'EDIT_OTHERS_LISTINGS',
            'PUBLISH_LISTINGS',
            'READ_PRIVATE_LISTINGS',
            'DELETE_LISTINGS',
            'DELETE_PRIVATE_LISTINGS',
            'DELETE_PUBLISHED_LISTINGS',
            'DELETE_OTHERS_LISTINGS',
            'EDIT_PRIVATE_LISTINGS',
            'EDIT_PUBLISHED_LISTINGS',
            'MANAGE_CATEGORIES',
            'MANAGE_TAGS',
        ];

        foreach ($expectedConstants as $constant) {
            $this->assertTrue(
                defined(Capabilities::class . '::' . $constant),
                "Capabilities::{$constant} constant should be defined"
            );
        }
    }

    /**
     * Test specific capability values.
     */
    public function test_specific_capability_values(): void
    {
        $this->assertSame('edit_apd_listing', Capabilities::EDIT_LISTING);
        $this->assertSame('read_apd_listing', Capabilities::READ_LISTING);
        $this->assertSame('delete_apd_listing', Capabilities::DELETE_LISTING);
        $this->assertSame('edit_apd_listings', Capabilities::EDIT_LISTINGS);
        $this->assertSame('publish_apd_listings', Capabilities::PUBLISH_LISTINGS);
        $this->assertSame('manage_apd_categories', Capabilities::MANAGE_CATEGORIES);
        $this->assertSame('manage_apd_tags', Capabilities::MANAGE_TAGS);
    }

    /**
     * Test that capabilities are unique.
     */
    public function test_capabilities_are_unique(): void
    {
        $capabilities = Capabilities::get_all();
        $uniqueCapabilities = array_unique($capabilities);

        $this->assertCount(
            count($capabilities),
            $uniqueCapabilities,
            'All capabilities should be unique'
        );
    }

    /**
     * Test editor capabilities contain expected values.
     */
    public function test_editor_capabilities_content(): void
    {
        $editor = Capabilities::get_editor_capabilities();

        $this->assertContains(Capabilities::EDIT_LISTINGS, $editor);
        $this->assertContains(Capabilities::EDIT_OTHERS_LISTINGS, $editor);
        $this->assertContains(Capabilities::PUBLISH_LISTINGS, $editor);
        $this->assertContains(Capabilities::READ_PRIVATE_LISTINGS, $editor);
        $this->assertContains(Capabilities::DELETE_LISTINGS, $editor);
    }

    /**
     * Test author capabilities contain expected values.
     */
    public function test_author_capabilities_content(): void
    {
        $author = Capabilities::get_author_capabilities();

        $this->assertContains(Capabilities::EDIT_LISTINGS, $author);
        $this->assertContains(Capabilities::PUBLISH_LISTINGS, $author);
        $this->assertContains(Capabilities::DELETE_LISTINGS, $author);

        // Authors should NOT have these
        $this->assertNotContains(Capabilities::EDIT_OTHERS_LISTINGS, $author);
        $this->assertNotContains(Capabilities::READ_PRIVATE_LISTINGS, $author);
    }
}
