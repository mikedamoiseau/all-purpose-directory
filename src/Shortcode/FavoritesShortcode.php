<?php
/**
 * Favorites Shortcode Class.
 *
 * Displays the user's favorite listings.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FavoritesShortcode
 *
 * @since 1.0.0
 */
final class FavoritesShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_favorites';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the user\'s favorite listings.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'view'          => 'grid',
		'columns'       => 3,
		'count'         => 12,
		'show_empty'    => 'true',
		'empty_message' => '',
		'class'         => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'view'          => [
			'type'        => 'slug',
			'description' => 'Display view: grid or list.',
			'default'     => 'grid',
		],
		'columns'       => [
			'type'        => 'integer',
			'description' => 'Number of columns for grid view.',
			'default'     => 3,
		],
		'count'         => [
			'type'        => 'integer',
			'description' => 'Number of favorites to show per page.',
			'default'     => 12,
		],
		'show_empty'    => [
			'type'        => 'boolean',
			'description' => 'Show message when no favorites.',
			'default'     => 'true',
		],
		'empty_message' => [
			'type'        => 'string',
			'description' => 'Message when no favorites found.',
			'default'     => '',
		],
		'class'         => [
			'type'        => 'string',
			'description' => 'Additional CSS classes.',
			'default'     => '',
		],
	];

	/**
	 * Get example usage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_example(): string {
		return '[apd_favorites view="grid" columns="3"]';
	}

	/**
	 * Generate the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $atts    Parsed shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string Shortcode output.
	 */
	protected function output( array $atts, ?string $content ): string {
		// Require login.
		if ( ! is_user_logged_in() ) {
			return $this->require_login( __( 'Please log in to view your favorites.', 'all-purpose-directory' ) );
		}

		/**
		 * Filter to enable the favorites display.
		 *
		 * Return true to enable custom favorites implementation.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $enabled Whether favorites is enabled.
		 * @param array $atts    The shortcode attributes.
		 */
		$enabled = apply_filters( 'apd_favorites_enabled', false, $atts );

		if ( $enabled ) {
			/**
			 * Filter the favorites output.
			 *
			 * @since 1.0.0
			 *
			 * @param string $output The favorites output.
			 * @param array  $atts   The shortcode attributes.
			 */
			return apply_filters( 'apd_favorites_output', '', $atts );
		}

		// Default: show coming soon message.
		return $this->coming_soon( __( 'Favorites', 'all-purpose-directory' ) );
	}
}
