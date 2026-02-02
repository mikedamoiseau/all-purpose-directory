<?php
/**
 * Factory for creating test listings.
 *
 * @package APD\Tests\Factories
 */

declare(strict_types=1);

namespace APD\Tests\Factories;

/**
 * Factory class for generating test listings.
 */
class ListingFactory
{
    /**
     * Default listing attributes.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'post_type'    => 'apd_listing',
        'post_status'  => 'publish',
        'post_title'   => '',
        'post_content' => '',
        'post_excerpt' => '',
        'post_author'  => 0,
    ];

    /**
     * Default meta values.
     *
     * @var array<string, mixed>
     */
    protected array $defaultMeta = [];

    /**
     * Attribute overrides.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Meta overrides.
     *
     * @var array<string, mixed>
     */
    protected array $meta = [];

    /**
     * Categories to assign.
     *
     * @var array<int>
     */
    protected array $categories = [];

    /**
     * Tags to assign.
     *
     * @var array<int>
     */
    protected array $tags = [];

    /**
     * Create a new factory instance.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset factory to defaults.
     */
    public function reset(): self
    {
        $this->attributes = [];
        $this->meta = [];
        $this->categories = [];
        $this->tags = [];

        return $this;
    }

    /**
     * Set attribute overrides.
     *
     * @param array<string, mixed> $attributes Attributes to override.
     */
    public function withAttributes(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    /**
     * Set meta overrides.
     *
     * @param array<string, mixed> $meta Meta values to set.
     */
    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    /**
     * Set post status.
     *
     * @param string $status Post status.
     */
    public function withStatus(string $status): self
    {
        $this->attributes['post_status'] = $status;
        return $this;
    }

    /**
     * Set post author.
     *
     * @param int $authorId Author user ID.
     */
    public function withAuthor(int $authorId): self
    {
        $this->attributes['post_author'] = $authorId;
        return $this;
    }

    /**
     * Set categories to assign.
     *
     * @param array<int> $categoryIds Category term IDs.
     */
    public function withCategories(array $categoryIds): self
    {
        $this->categories = $categoryIds;
        return $this;
    }

    /**
     * Set tags to assign.
     *
     * @param array<int> $tagIds Tag term IDs.
     */
    public function withTags(array $tagIds): self
    {
        $this->tags = $tagIds;
        return $this;
    }

    /**
     * Generate listing data without inserting.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<string, mixed> Listing data array.
     */
    public function make(array $overrides = []): array
    {
        $attributes = array_merge(
            $this->defaults,
            $this->attributes,
            $overrides
        );

        // Generate fake data for empty fields.
        if (empty($attributes['post_title'])) {
            $attributes['post_title'] = $this->generateTitle();
        }

        if (empty($attributes['post_content'])) {
            $attributes['post_content'] = $this->generateContent();
        }

        if (empty($attributes['post_excerpt'])) {
            $attributes['post_excerpt'] = $this->generateExcerpt();
        }

        return [
            'attributes' => $attributes,
            'meta'       => array_merge($this->defaultMeta, $this->meta),
            'categories' => $this->categories,
            'tags'       => $this->tags,
        ];
    }

    /**
     * Create and insert a listing.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return int The post ID.
     */
    public function create(array $overrides = []): int
    {
        $data = $this->make($overrides);

        // Insert the post.
        $postId = wp_insert_post($data['attributes']);

        if (is_wp_error($postId)) {
            throw new \RuntimeException('Failed to create listing: ' . $postId->get_error_message());
        }

        // Set meta values.
        foreach ($data['meta'] as $key => $value) {
            update_post_meta($postId, $key, $value);
        }

        // Assign categories.
        if (! empty($data['categories'])) {
            wp_set_object_terms($postId, $data['categories'], 'apd_category');
        }

        // Assign tags.
        if (! empty($data['tags'])) {
            wp_set_object_terms($postId, $data['tags'], 'apd_tag');
        }

        // Reset for next use.
        $this->reset();

        return $postId;
    }

    /**
     * Create multiple listings.
     *
     * @param int                  $count     Number of listings to create.
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<int> Array of post IDs.
     */
    public function createMany(int $count, array $overrides = []): array
    {
        $postIds = [];

        for ($i = 0; $i < $count; $i++) {
            $postIds[] = $this->create($overrides);
        }

        return $postIds;
    }

    /**
     * Generate a fake listing title.
     */
    protected function generateTitle(): string
    {
        $adjectives = ['Premium', 'Excellent', 'Professional', 'Quality', 'Best', 'Top-Rated'];
        $nouns = ['Service', 'Business', 'Company', 'Store', 'Shop', 'Agency'];
        $locations = ['Downtown', 'Westside', 'Central', 'North', 'East', 'South'];

        return sprintf(
            '%s %s %s',
            $adjectives[array_rand($adjectives)],
            $nouns[array_rand($nouns)],
            $locations[array_rand($locations)]
        );
    }

    /**
     * Generate fake listing content.
     */
    protected function generateContent(): string
    {
        return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.';
    }

    /**
     * Generate fake listing excerpt.
     */
    protected function generateExcerpt(): string
    {
        return 'A brief description of this listing for display in archive views.';
    }
}
