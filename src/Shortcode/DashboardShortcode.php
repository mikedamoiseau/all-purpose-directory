<?php
/**
 * Dashboard Shortcode Class.
 *
 * Displays the user dashboard for managing listings.
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
 * Class DashboardShortcode
 *
 * @since 1.0.0
 */
final class DashboardShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_dashboard';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the user dashboard for managing listings.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'show_stats'    => 'true',
		'show_listings' => 'true',
		'show_favorites' => 'true',
		'class'         => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'show_stats'    => [
			'type'        => 'boolean',
			'description' => 'Show statistics overview.',
			'default'     => 'true',
		],
		'show_listings' => [
			'type'        => 'boolean',
			'description' => 'Show user listings.',
			'default'     => 'true',
		],
		'show_favorites' => [
			'type'        => 'boolean',
			'description' => 'Show favorite listings.',
			'default'     => 'true',
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
		return '[apd_dashboard show_stats="true" show_listings="true"]';
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
			return $this->require_login( __( 'Please log in to access your dashboard.', 'all-purpose-directory' ) );
		}

		/**
		 * Filter to enable the dashboard.
		 *
		 * Return true to enable custom dashboard implementation.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $enabled Whether the dashboard is enabled.
		 * @param array $atts    The shortcode attributes.
		 */
		$enabled = apply_filters( 'apd_dashboard_enabled', false, $atts );

		if ( $enabled ) {
			/**
			 * Filter the dashboard output.
			 *
			 * @since 1.0.0
			 *
			 * @param string $output The dashboard output.
			 * @param array  $atts   The shortcode attributes.
			 */
			return apply_filters( 'apd_dashboard_output', '', $atts );
		}

		// Default: show coming soon message.
		return $this->coming_soon( __( 'User dashboard', 'all-purpose-directory' ) );
	}
}
