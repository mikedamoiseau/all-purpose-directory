<?php
/**
 * Factory for creating test categories.
 *
 * @package APD\Tests\Factories
 */

declare(strict_types=1);

namespace APD\Tests\Factories;

/**
 * Factory class for generating test categories.
 */
class CategoryFactory
{
    /**
     * Default category attributes.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'taxonomy'    => 'apd_category',
        'name'        => '',
        'description' => '',
        'parent'      => 0,
        'slug'        => '',
    ];

    /**
     * Default meta values.
     *
     * @var array<string, mixed>
     */
    protected array $defaultMeta = [
        'icon'  => '',
        'color' => '',
    ];

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
     * Set parent category.
     *
     * @param int $parentId Parent term ID.
     */
    public function withParent(int $parentId): self
    {
        $this->attributes['parent'] = $parentId;
        return $this;
    }

    /**
     * Set category icon.
     *
     * @param string $icon Icon class or URL.
     */
    public function withIcon(string $icon): self
    {
        $this->meta['icon'] = $icon;
        return $this;
    }

    /**
     * Set category color.
     *
     * @param string $color Hex color code.
     */
    public function withColor(string $color): self
    {
        $this->meta['color'] = $color;
        return $this;
    }

    /**
     * Generate category data without inserting.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<string, mixed> Category data array.
     */
    public function make(array $overrides = []): array
    {
        $attributes = array_merge(
            $this->defaults,
            $this->attributes,
            $overrides
        );

        // Generate fake data for empty fields.
        if (empty($attributes['name'])) {
            $attributes['name'] = $this->generateName();
        }

        if (empty($attributes['slug'])) {
            $attributes['slug'] = sanitize_title($attributes['name']);
        }

        if (empty($attributes['description'])) {
            $attributes['description'] = $this->generateDescription();
        }

        return [
            'attributes' => $attributes,
            'meta'       => array_merge($this->defaultMeta, $this->meta),
        ];
    }

    /**
     * Create and insert a category.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return int The term ID.
     */
    public function create(array $overrides = []): int
    {
        $data = $this->make($overrides);

        // Insert the term.
        $result = wp_insert_term(
            $data['attributes']['name'],
            $data['attributes']['taxonomy'],
            [
                'description' => $data['attributes']['description'],
                'parent'      => $data['attributes']['parent'],
                'slug'        => $data['attributes']['slug'],
            ]
        );

        if (is_wp_error($result)) {
            throw new \RuntimeException('Failed to create category: ' . $result->get_error_message());
        }

        $termId = $result['term_id'];

        // Set meta values.
        foreach ($data['meta'] as $key => $value) {
            if (! empty($value)) {
                update_term_meta($termId, '_apd_' . $key, $value);
            }
        }

        // Reset for next use.
        $this->reset();

        return $termId;
    }

    /**
     * Create a hierarchical category tree.
     *
     * @param array<string, mixed> $tree Tree structure with children.
     * @return array<int> Array of term IDs.
     */
    public function createTree(array $tree): array
    {
        $termIds = [];

        foreach ($tree as $item) {
            $attributes = $item['attributes'] ?? [];
            $children = $item['children'] ?? [];

            $parentId = $this->create($attributes);
            $termIds[] = $parentId;

            if (! empty($children)) {
                foreach ($children as $child) {
                    $child['attributes'] = $child['attributes'] ?? [];
                    $child['attributes']['parent'] = $parentId;

                    $childId = $this->create($child['attributes']);
                    $termIds[] = $childId;
                }
            }
        }

        return $termIds;
    }

    /**
     * Generate a fake category name.
     */
    protected function generateName(): string
    {
        $categories = [
            'Restaurants',
            'Hotels',
            'Shopping',
            'Services',
            'Entertainment',
            'Healthcare',
            'Education',
            'Automotive',
            'Real Estate',
            'Professional Services',
        ];

        return $categories[array_rand($categories)] . ' ' . wp_rand(1, 100);
    }

    /**
     * Generate fake category description.
     */
    protected function generateDescription(): string
    {
        return 'A category for organizing related listings.';
    }
}
