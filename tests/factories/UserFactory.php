<?php
/**
 * Factory for creating test users.
 *
 * @package APD\Tests\Factories
 */

declare(strict_types=1);

namespace APD\Tests\Factories;

/**
 * Factory class for generating test users.
 */
class UserFactory
{
    /**
     * Default user attributes.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'user_login'    => '',
        'user_pass'     => 'password123',
        'user_email'    => '',
        'user_nicename' => '',
        'display_name'  => '',
        'role'          => 'subscriber',
    ];

    /**
     * Default meta values.
     *
     * @var array<string, mixed>
     */
    protected array $defaultMeta = [
        'first_name'  => '',
        'last_name'   => '',
        'description' => '',
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
     * Set user role.
     *
     * @param string $role WordPress role.
     */
    public function withRole(string $role): self
    {
        $this->attributes['role'] = $role;
        return $this;
    }

    /**
     * Create as subscriber.
     */
    public function asSubscriber(): self
    {
        return $this->withRole('subscriber');
    }

    /**
     * Create as contributor.
     */
    public function asContributor(): self
    {
        return $this->withRole('contributor');
    }

    /**
     * Create as author.
     */
    public function asAuthor(): self
    {
        return $this->withRole('author');
    }

    /**
     * Create as editor.
     */
    public function asEditor(): self
    {
        return $this->withRole('editor');
    }

    /**
     * Create as administrator.
     */
    public function asAdmin(): self
    {
        return $this->withRole('administrator');
    }

    /**
     * Set display name and email.
     *
     * @param string $firstName First name.
     * @param string $lastName  Last name.
     */
    public function withName(string $firstName, string $lastName): self
    {
        $this->meta['first_name'] = $firstName;
        $this->meta['last_name'] = $lastName;
        $this->attributes['display_name'] = $firstName . ' ' . $lastName;

        return $this;
    }

    /**
     * Generate user data without inserting.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<string, mixed> User data array.
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
        $uniqueId = wp_rand(1000, 99999);

        if (empty($attributes['user_login'])) {
            $attributes['user_login'] = 'testuser' . $uniqueId;
        }

        if (empty($attributes['user_email'])) {
            $attributes['user_email'] = 'testuser' . $uniqueId . '@example.com';
        }

        if (empty($attributes['display_name'])) {
            $firstName = $meta['first_name'] ?: $this->generateFirstName();
            $lastName = $meta['last_name'] ?: $this->generateLastName();
            $attributes['display_name'] = $firstName . ' ' . $lastName;
            $meta['first_name'] = $firstName;
            $meta['last_name'] = $lastName;
        }

        if (empty($attributes['user_nicename'])) {
            $attributes['user_nicename'] = sanitize_title($attributes['display_name']);
        }

        return [
            'attributes' => $attributes,
            'meta'       => $meta,
        ];
    }

    /**
     * Create and insert a user.
     *
     * @param array<string, mixed> $overrides Additional overrides.
     * @return int The user ID.
     */
    public function create(array $overrides = []): int
    {
        $data = $this->make($overrides);

        // Insert the user.
        $userId = wp_insert_user($data['attributes']);

        if (is_wp_error($userId)) {
            throw new \RuntimeException('Failed to create user: ' . $userId->get_error_message());
        }

        // Set meta values.
        foreach ($data['meta'] as $key => $value) {
            if (! empty($value)) {
                update_user_meta($userId, $key, $value);
            }
        }

        // Reset for next use.
        $this->reset();

        return $userId;
    }

    /**
     * Create multiple users with a specific role.
     *
     * @param string               $role      User role.
     * @param int                  $count     Number of users to create.
     * @param array<string, mixed> $overrides Additional overrides.
     * @return array<int> Array of user IDs.
     */
    public function createManyWithRole(string $role, int $count, array $overrides = []): array
    {
        $userIds = [];

        for ($i = 0; $i < $count; $i++) {
            $userIds[] = $this->withRole($role)->create($overrides);
        }

        return $userIds;
    }

    /**
     * Get or create a test admin user.
     *
     * @return int The admin user ID.
     */
    public function getOrCreateAdmin(): int
    {
        $user = get_user_by('login', 'test_admin');

        if ($user) {
            return $user->ID;
        }

        return $this->asAdmin()
            ->withAttributes(['user_login' => 'test_admin'])
            ->create();
    }

    /**
     * Generate a fake first name.
     */
    protected function generateFirstName(): string
    {
        $names = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Emily', 'Chris', 'Lisa'];

        return $names[array_rand($names)];
    }

    /**
     * Generate a fake last name.
     */
    protected function generateLastName(): string
    {
        $names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];

        return $names[array_rand($names)];
    }
}
