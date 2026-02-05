<?php
/**
 * Demo Data Tracker Class.
 *
 * Tracks and manages demo data for cleanup purposes.
 *
 * @package APD\Admin\DemoData
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoDataTracker
 *
 * Tracks demo data items and provides cleanup functionality.
 *
 * @since 1.0.0
 */
final class DemoDataTracker {

	/**
	 * Meta key used to mark items as demo data.
	 */
	public const META_KEY = '_apd_demo_data';

	/**
	 * Meta value used to mark items as demo data.
	 */
	public const META_VALUE = '1';

	/**
	 * Singleton instance.
	 *
	 * @var DemoDataTracker|null
	 */
	private static ?DemoDataTracker $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DemoDataTracker
	 */
	public static function get_instance(): DemoDataTracker {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Mark a post as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_post_as_demo( int $post_id ): bool {
		return (bool) update_post_meta( $post_id, self::META_KEY, self::META_VALUE );
	}

	/**
	 * Mark a term as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term ID.
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_term_as_demo( int $term_id ): bool {
		return (bool) update_term_meta( $term_id, self::META_KEY, self::META_VALUE );
	}

	/**
	 * Mark a user as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_user_as_demo( int $user_id ): bool {
		return (bool) update_user_meta( $user_id, self::META_KEY, self::META_VALUE );
	}

	/**
	 * Mark a comment (review) as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_id Comment ID.
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_comment_as_demo( int $comment_id ): bool {
		return (bool) update_comment_meta( $comment_id, self::META_KEY, self::META_VALUE );
	}

	/**
	 * Check if a post is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_demo_post( int $post_id ): bool {
		return get_post_meta( $post_id, self::META_KEY, true ) === self::META_VALUE;
	}

	/**
	 * Check if a term is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	public function is_demo_term( int $term_id ): bool {
		return get_term_meta( $term_id, self::META_KEY, true ) === self::META_VALUE;
	}

	/**
	 * Check if a user is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function is_demo_user( int $user_id ): bool {
		return get_user_meta( $user_id, self::META_KEY, true ) === self::META_VALUE;
	}

	/**
	 * Check if a comment is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_id Comment ID.
	 * @return bool
	 */
	public function is_demo_comment( int $comment_id ): bool {
		return get_comment_meta( $comment_id, self::META_KEY, true ) === self::META_VALUE;
	}

	/**
	 * Count all demo data items by type.
	 *
	 * @since 1.0.0
	 *
	 * @return array{users: int, categories: int, tags: int, listings: int, reviews: int, inquiries: int}
	 */
	public function count_demo_data(): array {
		global $wpdb;

		// Count demo users.
		$users = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
				self::META_KEY,
				self::META_VALUE
			)
		);

		// Count demo categories.
		$categories = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
				INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
				WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s",
				self::META_KEY,
				self::META_VALUE,
				'apd_category'
			)
		);

		// Count demo tags.
		$tags = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
				INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
				WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s",
				self::META_KEY,
				self::META_VALUE,
				'apd_tag'
			)
		);

		// Count demo listings.
		$listings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s",
				self::META_KEY,
				self::META_VALUE,
				'apd_listing'
			)
		);

		// Count demo reviews.
		$reviews = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm
				INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
				WHERE cm.meta_key = %s AND cm.meta_value = %s AND c.comment_type = %s",
				self::META_KEY,
				self::META_VALUE,
				'apd_review'
			)
		);

		// Count demo inquiries.
		$inquiries = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s",
				self::META_KEY,
				self::META_VALUE,
				'apd_inquiry'
			)
		);

		return [
			'users'      => $users,
			'categories' => $categories,
			'tags'       => $tags,
			'listings'   => $listings,
			'reviews'    => $reviews,
			'inquiries'  => $inquiries,
		];
	}

	/**
	 * Get IDs of demo posts by post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type.
	 * @return int[]
	 */
	public function get_demo_post_ids( string $post_type ): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s",
				self::META_KEY,
				self::META_VALUE,
				$post_type
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Get IDs of demo terms by taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return int[]
	 */
	public function get_demo_term_ids( string $taxonomy ): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT t.term_id FROM {$wpdb->terms} t
				INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
				WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s",
				self::META_KEY,
				self::META_VALUE,
				$taxonomy
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Get IDs of demo users.
	 *
	 * @since 1.0.0
	 *
	 * @return int[]
	 */
	public function get_demo_user_ids(): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
				self::META_KEY,
				self::META_VALUE
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Get IDs of demo comments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $comment_type Comment type (e.g., 'apd_review').
	 * @return int[]
	 */
	public function get_demo_comment_ids( string $comment_type = '' ): array {
		global $wpdb;

		if ( ! empty( $comment_type ) ) {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT c.comment_ID FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
					WHERE cm.meta_key = %s AND cm.meta_value = %s AND c.comment_type = %s",
					self::META_KEY,
					self::META_VALUE,
					$comment_type
				)
			);
		} else {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key = %s AND meta_value = %s",
					self::META_KEY,
					self::META_VALUE
				)
			);
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Delete all demo users.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of users deleted.
	 */
	public function delete_demo_users(): int {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$user_ids = $this->get_demo_user_ids();
		$deleted  = 0;

		foreach ( $user_ids as $user_id ) {
			// Reassign content to admin (user ID 1) or null if deleting content.
			if ( wp_delete_user( $user_id, 1 ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all demo categories.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of categories deleted.
	 */
	public function delete_demo_categories(): int {
		$term_ids = $this->get_demo_term_ids( 'apd_category' );
		$deleted  = 0;

		foreach ( $term_ids as $term_id ) {
			$result = wp_delete_term( $term_id, 'apd_category' );
			if ( $result && ! is_wp_error( $result ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all demo tags.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of tags deleted.
	 */
	public function delete_demo_tags(): int {
		$term_ids = $this->get_demo_term_ids( 'apd_tag' );
		$deleted  = 0;

		foreach ( $term_ids as $term_id ) {
			$result = wp_delete_term( $term_id, 'apd_tag' );
			if ( $result && ! is_wp_error( $result ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all demo listings.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of listings deleted.
	 */
	public function delete_demo_listings(): int {
		$post_ids = $this->get_demo_post_ids( 'apd_listing' );
		$deleted  = 0;

		foreach ( $post_ids as $post_id ) {
			$result = wp_delete_post( $post_id, true ); // Force delete (bypass trash).
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all demo reviews.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of reviews deleted.
	 */
	public function delete_demo_reviews(): int {
		$comment_ids = $this->get_demo_comment_ids( 'apd_review' );
		$deleted     = 0;

		foreach ( $comment_ids as $comment_id ) {
			$result = wp_delete_comment( $comment_id, true ); // Force delete.
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all demo inquiries.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of inquiries deleted.
	 */
	public function delete_demo_inquiries(): int {
		$post_ids = $this->get_demo_post_ids( 'apd_inquiry' );
		$deleted  = 0;

		foreach ( $post_ids as $post_id ) {
			$result = wp_delete_post( $post_id, true ); // Force delete.
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Clear all user favorites that reference demo listings.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of favorites cleared.
	 */
	public function clear_demo_favorites(): int {
		global $wpdb;

		$demo_listing_ids = $this->get_demo_post_ids( 'apd_listing' );
		if ( empty( $demo_listing_ids ) ) {
			return 0;
		}

		$cleared = 0;

		// Get all users with favorites.
		$users_with_favorites = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_apd_favorites'"
		);

		foreach ( $users_with_favorites as $user_id ) {
			$favorites = get_user_meta( (int) $user_id, '_apd_favorites', true );
			if ( ! is_array( $favorites ) || empty( $favorites ) ) {
				continue;
			}

			$original_count = count( $favorites );
			$favorites      = array_diff( $favorites, $demo_listing_ids );
			$new_count      = count( $favorites );

			if ( $new_count !== $original_count ) {
				update_user_meta( (int) $user_id, '_apd_favorites', array_values( $favorites ) );
				$cleared += ( $original_count - $new_count );
			}
		}

		return $cleared;
	}

	/**
	 * Delete all demo data.
	 *
	 * Deletes in dependency order: reviews → inquiries → favorites → listings → tags → categories → users.
	 *
	 * @since 1.0.0
	 *
	 * @return array{users: int, categories: int, tags: int, listings: int, reviews: int, inquiries: int, favorites: int}
	 */
	public function delete_all(): array {
		/**
		 * Fires before demo data deletion begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_delete_demo_data' );

		$counts = [
			'reviews'    => $this->delete_demo_reviews(),
			'inquiries'  => $this->delete_demo_inquiries(),
			'favorites'  => $this->clear_demo_favorites(),
			'listings'   => $this->delete_demo_listings(),
			'tags'       => $this->delete_demo_tags(),
			'categories' => $this->delete_demo_categories(),
			'users'      => $this->delete_demo_users(),
		];

		/**
		 * Fires after demo data deletion completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $counts Number of items deleted by type.
		 */
		do_action( 'apd_after_delete_demo_data', $counts );

		return $counts;
	}

	/**
	 * Reset singleton instance for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}
}
