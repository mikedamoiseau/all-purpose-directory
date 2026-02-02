<?php
/**
 * Factory for creating test reviews.
 *
 * @package APD\Tests\Factories
 */

declare(strict_types=1);

namespace APD\Tests\Factories;

/**
 * Factory class for generating test reviews.
 *
 * Reviews are stored as comments with meta data.
 */
class ReviewFactory
{
    /**
     * Default review attributes.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'comment_post_ID'      => 0,
        'comment_author'       => '',
        'comment_author_email' => '',
        'comment_content'      => '',
        'comment_type'         => 'apd_review',
        'comment_approved'     => 1,
        'user_id'              => 0,
    ];

    /**
     * Default meta values.
     *
     * @var array<string, mixed>
     */
    protected array $defaultMeta = [
        'rating' => 5,
        'title'  => '',
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
     * Set the listing to review.
     *
     * @param int $listingId Listing post ID.
     */
    public function forListing(int $listingId): self
    {
        $this->attributes['comment_post_ID'] = $listingId;
        return $this;
    }

    /**
     * Set the review author.
     *
     * @param int    $userId User ID.
     * @param string $name   Author name.
     * @param string $email  Author email.
     */
    public function byUser(int $userId, string $name = '', string $email = ''): self
    {
        $this->attributes['user_id'] = $userId;

        if (! empty($name)) {
            $this->attributes['comment_author'] = $name;
        }

        if (! empty($email)) {
            $this->attributes['comment_author_email'] = $email;
        }

        return $this;
    }

    /**
     * Set the rating.
     *
     * @param int $rating Rating value (1-5).
     */
    public function withRating(int $rating): self
    {
        $this->meta['rating'] = max(1, min(5, $rating));
        return $this;
    }

    /**
     * Set the review title.
     *
     * @param string $title Review title.
     */
    public function withTitle(string $title): self
    {
        $this->meta['title'] = $title;
        return $this;
    }

    /**
     * Set as pending (not approved).
     */
    public function asPending(): self
    {
        $this->attributes['comment_approved'] = 0;
        return $this;
    }

    /**
     * Generate review data without inserting.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<string, mixed> Review data array.
     */
    public function make(array $overrides = []): array
    {
        $attributes = array_merge(
            $this->defaults,
            $this->attributes,
            $overrides
        );

        $meta = array_merge($this->defaultMeta, $this->meta);

        // Generate fake data for empty fields.
        if (empty($attributes['comment_author'])) {
            $attributes['comment_author'] = $this->generateAuthorName();
        }

        if (empty($attributes['comment_author_email'])) {
            $attributes['comment_author_email'] = $this->generateEmail();
        }

        if (empty($attributes['comment_content'])) {
            $attributes['comment_content'] = $this->generateContent();
        }

        if (empty($meta['title'])) {
            $meta['title'] = $this->generateTitle($meta['rating']);
        }

        return [
            'attributes' => $attributes,
            'meta'       => $meta,
        ];
    }

    /**
     * Create and insert a review.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return int The comment ID.
     */
    public function create(array $overrides = []): int
    {
        $data = $this->make($overrides);

        if (empty($data['attributes']['comment_post_ID'])) {
            throw new \RuntimeException('Review must be associated with a listing. Use forListing() method.');
        }

        // Insert the comment.
        $commentId = wp_insert_comment($data['attributes']);

        if (! $commentId) {
            throw new \RuntimeException('Failed to create review.');
        }

        // Set meta values.
        foreach ($data['meta'] as $key => $value) {
            update_comment_meta($commentId, '_apd_' . $key, $value);
        }

        // Reset for next use.
        $this->reset();

        return $commentId;
    }

    /**
     * Create multiple reviews for a listing.
     *
     * @param int                  $listingId Listing post ID.
     * @param int                  $count     Number of reviews to create.
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<int> Array of comment IDs.
     */
    public function createManyForListing(int $listingId, int $count, array $overrides = []): array
    {
        $commentIds = [];

        for ($i = 0; $i < $count; $i++) {
            $commentIds[] = $this->forListing($listingId)
                ->withRating(wp_rand(1, 5))
                ->create($overrides);
        }

        return $commentIds;
    }

    /**
     * Generate a fake author name.
     */
    protected function generateAuthorName(): string
    {
        $firstNames = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Emily'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Generate a fake email.
     */
    protected function generateEmail(): string
    {
        return 'user' . wp_rand(1000, 9999) . '@example.com';
    }

    /**
     * Generate fake review content.
     */
    protected function generateContent(): string
    {
        $reviews = [
            'Great experience! Highly recommend this place to everyone.',
            'Good service but could be better. Will visit again.',
            'Excellent quality and friendly staff. Very satisfied.',
            'Average experience. Nothing special but not bad either.',
            'Outstanding! Exceeded all my expectations.',
        ];

        return $reviews[array_rand($reviews)];
    }

    /**
     * Generate a review title based on rating.
     *
     * @param int $rating The rating value.
     */
    protected function generateTitle(int $rating): string
    {
        $titles = [
            1 => ['Disappointing', 'Not recommended', 'Poor experience'],
            2 => ['Below average', 'Could be better', 'Needs improvement'],
            3 => ['Average', 'Okay', 'Fair enough'],
            4 => ['Good', 'Recommended', 'Nice experience'],
            5 => ['Excellent', 'Highly recommended', 'Outstanding'],
        ];

        $options = $titles[$rating] ?? $titles[3];

        return $options[array_rand($options)];
    }
}
