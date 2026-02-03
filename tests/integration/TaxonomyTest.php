<?php
/**
 * Integration tests for Taxonomies.
 *
 * Tests category and tag taxonomy registration with WordPress.
 *
 * @package APD\Tests\Integration
 */

declare(strict_types=1);

namespace APD\Tests\Integration;

use APD\Tests\TestCase;
use APD\Taxonomy\CategoryTaxonomy;
use APD\Taxonomy\TagTaxonomy;
use APD\Listing\PostType;

/**
 * Test case for Taxonomies.
 *
 * @covers \APD\Taxonomy\CategoryTaxonomy
 * @covers \APD\Taxonomy\TagTaxonomy
 */
class TaxonomyTest extends TestCase
{
    /**
     * Test category taxonomy is registered.
     */
    public function testCategoryTaxonomyIsRegistered(): void
    {
        $this->assertTrue(
            taxonomy_exists(CategoryTaxonomy::TAXONOMY),
            'Category taxonomy should be registered.'
        );
    }

    /**
     * Test category taxonomy is hierarchical.
     */
    public function testCategoryTaxonomyIsHierarchical(): void
    {
        $taxonomy = get_taxonomy(CategoryTaxonomy::TAXONOMY);

        $this->assertTrue(
            $taxonomy->hierarchical,
            'Category taxonomy should be hierarchical.'
        );
    }

    /**
     * Test category taxonomy labels.
     */
    public function testCategoryTaxonomyLabels(): void
    {
        $taxonomy = get_taxonomy(CategoryTaxonomy::TAXONOMY);

        $this->assertEquals('Categories', $taxonomy->labels->name);
        $this->assertEquals('Category', $taxonomy->labels->singular_name);
        $this->assertEquals('Add New Category', $taxonomy->labels->add_new_item);
        $this->assertEquals('Edit Category', $taxonomy->labels->edit_item);
    }

    /**
     * Test category taxonomy shows in REST.
     */
    public function testCategoryTaxonomyShowsInRest(): void
    {
        $taxonomy = get_taxonomy(CategoryTaxonomy::TAXONOMY);

        $this->assertTrue(
            $taxonomy->show_in_rest,
            'Category taxonomy should be available in REST API.'
        );
        $this->assertEquals('apd_category', $taxonomy->rest_base);
    }

    /**
     * Test category taxonomy rewrite rules.
     */
    public function testCategoryTaxonomyRewriteRules(): void
    {
        $taxonomy = get_taxonomy(CategoryTaxonomy::TAXONOMY);

        $this->assertIsArray($taxonomy->rewrite);
        $this->assertEquals('listing-category', $taxonomy->rewrite['slug']);
        $this->assertTrue($taxonomy->rewrite['hierarchical']);
    }

    /**
     * Test category taxonomy is attached to listing post type.
     */
    public function testCategoryTaxonomyAttachedToListings(): void
    {
        $taxonomy = get_taxonomy(CategoryTaxonomy::TAXONOMY);

        $this->assertContains(
            PostType::POST_TYPE,
            $taxonomy->object_type,
            'Category taxonomy should be attached to listing post type.'
        );
    }

    /**
     * Test tag taxonomy is registered.
     */
    public function testTagTaxonomyIsRegistered(): void
    {
        $this->assertTrue(
            taxonomy_exists(TagTaxonomy::TAXONOMY),
            'Tag taxonomy should be registered.'
        );
    }

    /**
     * Test tag taxonomy is not hierarchical.
     */
    public function testTagTaxonomyIsNotHierarchical(): void
    {
        $taxonomy = get_taxonomy(TagTaxonomy::TAXONOMY);

        $this->assertFalse(
            $taxonomy->hierarchical,
            'Tag taxonomy should not be hierarchical.'
        );
    }

    /**
     * Test tag taxonomy labels.
     */
    public function testTagTaxonomyLabels(): void
    {
        $taxonomy = get_taxonomy(TagTaxonomy::TAXONOMY);

        $this->assertEquals('Tags', $taxonomy->labels->name);
        $this->assertEquals('Tag', $taxonomy->labels->singular_name);
        $this->assertEquals('Add New Tag', $taxonomy->labels->add_new_item);
        $this->assertEquals('Edit Tag', $taxonomy->labels->edit_item);
    }

    /**
     * Test tag taxonomy shows in REST.
     */
    public function testTagTaxonomyShowsInRest(): void
    {
        $taxonomy = get_taxonomy(TagTaxonomy::TAXONOMY);

        $this->assertTrue(
            $taxonomy->show_in_rest,
            'Tag taxonomy should be available in REST API.'
        );
        $this->assertEquals('apd_tag', $taxonomy->rest_base);
    }

    /**
     * Test tag taxonomy rewrite rules.
     */
    public function testTagTaxonomyRewriteRules(): void
    {
        $taxonomy = get_taxonomy(TagTaxonomy::TAXONOMY);

        $this->assertIsArray($taxonomy->rewrite);
        $this->assertEquals('listing-tag', $taxonomy->rewrite['slug']);
    }

    /**
     * Test tag taxonomy is attached to listing post type.
     */
    public function testTagTaxonomyAttachedToListings(): void
    {
        $taxonomy = get_taxonomy(TagTaxonomy::TAXONOMY);

        $this->assertContains(
            PostType::POST_TYPE,
            $taxonomy->object_type,
            'Tag taxonomy should be attached to listing post type.'
        );
    }

    /**
     * Test creating a category.
     */
    public function testCreateCategory(): void
    {
        $result = wp_insert_term('Test Category', CategoryTaxonomy::TAXONOMY);

        $this->assertIsArray($result, 'Should return array with term_id.');
        $this->assertArrayHasKey('term_id', $result);

        $term = get_term($result['term_id'], CategoryTaxonomy::TAXONOMY);
        $this->assertEquals('Test Category', $term->name);
    }

    /**
     * Test creating hierarchical categories.
     */
    public function testCreateHierarchicalCategories(): void
    {
        // Create parent category.
        $parent = wp_insert_term('Parent Category', CategoryTaxonomy::TAXONOMY);
        $this->assertIsArray($parent);

        // Create child category.
        $child = wp_insert_term(
            'Child Category',
            CategoryTaxonomy::TAXONOMY,
            ['parent' => $parent['term_id']]
        );
        $this->assertIsArray($child);

        // Verify parent-child relationship.
        $child_term = get_term($child['term_id'], CategoryTaxonomy::TAXONOMY);
        $this->assertEquals($parent['term_id'], $child_term->parent);
    }

    /**
     * Test category meta fields - icon.
     */
    public function testCategoryMetaIcon(): void
    {
        $result = wp_insert_term('Icon Category', CategoryTaxonomy::TAXONOMY);
        $term_id = $result['term_id'];

        // Set icon meta.
        update_term_meta($term_id, CategoryTaxonomy::META_ICON, 'dashicons-store');

        // Verify icon is saved and retrieved.
        $icon = CategoryTaxonomy::get_icon($term_id);
        $this->assertEquals('dashicons-store', $icon);

        // Also verify using the helper function.
        $term = get_term($term_id, CategoryTaxonomy::TAXONOMY);
        $helper_icon = apd_get_category_icon($term);
        $this->assertEquals('dashicons-store', $helper_icon);
    }

    /**
     * Test category meta fields - color.
     */
    public function testCategoryMetaColor(): void
    {
        $result = wp_insert_term('Color Category', CategoryTaxonomy::TAXONOMY);
        $term_id = $result['term_id'];

        // Set color meta.
        update_term_meta($term_id, CategoryTaxonomy::META_COLOR, '#ff5733');

        // Verify color is saved and retrieved.
        $color = CategoryTaxonomy::get_color($term_id);
        $this->assertEquals('#ff5733', $color);

        // Also verify using the helper function.
        $helper_color = apd_get_category_color($term_id);
        $this->assertEquals('#ff5733', $helper_color);
    }

    /**
     * Test assigning category to listing.
     */
    public function testAssignCategoryToListing(): void
    {
        // Create a listing.
        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Test Listing',
            'post_status' => 'publish',
        ]);

        // Create a category.
        $category = wp_insert_term('Assigned Category', CategoryTaxonomy::TAXONOMY);

        // Assign category to listing.
        wp_set_object_terms($listing_id, $category['term_id'], CategoryTaxonomy::TAXONOMY);

        // Verify assignment.
        $terms = get_the_terms($listing_id, CategoryTaxonomy::TAXONOMY);
        $this->assertCount(1, $terms);
        $this->assertEquals('Assigned Category', $terms[0]->name);
    }

    /**
     * Test assigning tag to listing.
     */
    public function testAssignTagToListing(): void
    {
        // Create a listing.
        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Test Listing with Tag',
            'post_status' => 'publish',
        ]);

        // Create a tag.
        $tag = wp_insert_term('Test Tag', TagTaxonomy::TAXONOMY);

        // Assign tag to listing.
        wp_set_object_terms($listing_id, $tag['term_id'], TagTaxonomy::TAXONOMY);

        // Verify assignment.
        $terms = get_the_terms($listing_id, TagTaxonomy::TAXONOMY);
        $this->assertCount(1, $terms);
        $this->assertEquals('Test Tag', $terms[0]->name);
    }

    /**
     * Test helper function apd_get_listing_categories.
     */
    public function testGetListingCategories(): void
    {
        // Create a listing with categories.
        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing with Categories',
            'post_status' => 'publish',
        ]);

        // Create and assign categories.
        $cat1 = wp_insert_term('Category One', CategoryTaxonomy::TAXONOMY);
        $cat2 = wp_insert_term('Category Two', CategoryTaxonomy::TAXONOMY);
        wp_set_object_terms($listing_id, [$cat1['term_id'], $cat2['term_id']], CategoryTaxonomy::TAXONOMY);

        // Test helper function.
        $categories = apd_get_listing_categories($listing_id);
        $this->assertCount(2, $categories);

        $names = wp_list_pluck($categories, 'name');
        $this->assertContains('Category One', $names);
        $this->assertContains('Category Two', $names);
    }

    /**
     * Test helper function apd_get_listing_categories with no categories.
     */
    public function testGetListingCategoriesEmpty(): void
    {
        // Create a listing without categories.
        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing without Categories',
            'post_status' => 'publish',
        ]);

        $categories = apd_get_listing_categories($listing_id);
        $this->assertIsArray($categories);
        $this->assertEmpty($categories);
    }

    /**
     * Test helper function apd_get_listing_tags.
     */
    public function testGetListingTags(): void
    {
        // Create a listing with tags.
        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing with Tags',
            'post_status' => 'publish',
        ]);

        // Create and assign tags.
        $tag1 = wp_insert_term('Tag One', TagTaxonomy::TAXONOMY);
        $tag2 = wp_insert_term('Tag Two', TagTaxonomy::TAXONOMY);
        $tag3 = wp_insert_term('Tag Three', TagTaxonomy::TAXONOMY);
        wp_set_object_terms($listing_id, [$tag1['term_id'], $tag2['term_id'], $tag3['term_id']], TagTaxonomy::TAXONOMY);

        // Test helper function.
        $tags = apd_get_listing_tags($listing_id);
        $this->assertCount(3, $tags);

        $names = wp_list_pluck($tags, 'name');
        $this->assertContains('Tag One', $names);
        $this->assertContains('Tag Two', $names);
        $this->assertContains('Tag Three', $names);
    }

    /**
     * Test helper function apd_get_listing_tags with no tags.
     */
    public function testGetListingTagsEmpty(): void
    {
        // Create a listing without tags.
        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing without Tags',
            'post_status' => 'publish',
        ]);

        $tags = apd_get_listing_tags($listing_id);
        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }

    /**
     * Test helper function apd_get_category_listings.
     */
    public function testGetCategoryListings(): void
    {
        // Create a category.
        $category = wp_insert_term('Listings Category', CategoryTaxonomy::TAXONOMY);

        // Create listings and assign to category.
        $listing1 = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing A',
            'post_status' => 'publish',
        ]);
        $listing2 = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing B',
            'post_status' => 'publish',
        ]);
        $listing3 = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing C (not in category)',
            'post_status' => 'publish',
        ]);

        wp_set_object_terms($listing1, $category['term_id'], CategoryTaxonomy::TAXONOMY);
        wp_set_object_terms($listing2, $category['term_id'], CategoryTaxonomy::TAXONOMY);

        // Test helper function.
        $listings = apd_get_category_listings($category['term_id']);
        $this->assertCount(2, $listings);

        $ids = wp_list_pluck($listings, 'ID');
        $this->assertContains($listing1, $ids);
        $this->assertContains($listing2, $ids);
        $this->assertNotContains($listing3, $ids);
    }

    /**
     * Test helper function apd_get_category_listings with custom args.
     */
    public function testGetCategoryListingsWithArgs(): void
    {
        // Create a category.
        $category = wp_insert_term('Paginated Category', CategoryTaxonomy::TAXONOMY);

        // Create multiple listings.
        for ($i = 1; $i <= 5; $i++) {
            $listing_id = $this->factory()->post->create([
                'post_type'   => PostType::POST_TYPE,
                'post_title'  => "Listing $i",
                'post_status' => 'publish',
            ]);
            wp_set_object_terms($listing_id, $category['term_id'], CategoryTaxonomy::TAXONOMY);
        }

        // Test with limit.
        $listings = apd_get_category_listings($category['term_id'], ['posts_per_page' => 2]);
        $this->assertCount(2, $listings);
    }

    /**
     * Test helper function apd_get_categories_with_count.
     */
    public function testGetCategoriesWithCount(): void
    {
        // Create categories.
        $cat1 = wp_insert_term('Count Category A', CategoryTaxonomy::TAXONOMY);
        $cat2 = wp_insert_term('Count Category B', CategoryTaxonomy::TAXONOMY);
        $cat3 = wp_insert_term('Count Category C (empty)', CategoryTaxonomy::TAXONOMY);

        // Create listings and assign to categories.
        for ($i = 1; $i <= 3; $i++) {
            $listing_id = $this->factory()->post->create([
                'post_type'   => PostType::POST_TYPE,
                'post_title'  => "Listing for Cat A - $i",
                'post_status' => 'publish',
            ]);
            wp_set_object_terms($listing_id, $cat1['term_id'], CategoryTaxonomy::TAXONOMY);
        }

        $listing_id = $this->factory()->post->create([
            'post_type'   => PostType::POST_TYPE,
            'post_title'  => 'Listing for Cat B',
            'post_status' => 'publish',
        ]);
        wp_set_object_terms($listing_id, $cat2['term_id'], CategoryTaxonomy::TAXONOMY);

        // Test helper function.
        $categories = apd_get_categories_with_count();
        $this->assertNotEmpty($categories);

        // Find our categories and check counts.
        $counts = [];
        foreach ($categories as $category) {
            if (strpos($category->name, 'Count Category') === 0) {
                $counts[$category->name] = $category->count;
            }
        }

        $this->assertEquals(3, $counts['Count Category A']);
        $this->assertEquals(1, $counts['Count Category B']);
        $this->assertEquals(0, $counts['Count Category C (empty)']);
    }

    /**
     * Test helper function apd_get_categories_with_count with hide_empty.
     */
    public function testGetCategoriesWithCountHideEmpty(): void
    {
        // Create an empty category.
        wp_insert_term('Empty Category', CategoryTaxonomy::TAXONOMY);

        // Get categories hiding empty ones.
        $categories = apd_get_categories_with_count(['hide_empty' => true]);

        $names = wp_list_pluck($categories, 'name');
        $this->assertNotContains('Empty Category', $names);
    }
}
