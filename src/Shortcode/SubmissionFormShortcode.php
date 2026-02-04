<?php
/**
 * Submission Form Shortcode Class.
 *
 * Displays the listing submission form for frontend submission.
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
 * Class SubmissionFormShortcode
 *
 * @since 1.0.0
 */
final class SubmissionFormShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_submission_form';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the listing submission form.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'require_login' => 'true',
		'redirect'      => '',
		'class'         => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'require_login' => [
			'type'        => 'boolean',
			'description' => 'Require user to be logged in.',
			'default'     => 'true',
		],
		'redirect'      => [
			'type'        => 'string',
			'description' => 'URL to redirect to after submission.',
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
		return '[apd_submission_form require_login="true"]';
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
		// Check login requirement.
		if ( $atts['require_login'] && ! is_user_logged_in() ) {
			return $this->require_login( __( 'Please log in to submit a listing.', 'all-purpose-directory' ) );
		}

		/**
		 * Filter to enable the submission form.
		 *
		 * Return true to enable custom submission form implementation.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $enabled Whether the form is enabled.
		 * @param array $atts    The shortcode attributes.
		 */
		$enabled = apply_filters( 'apd_submission_form_enabled', false, $atts );

		if ( $enabled ) {
			/**
			 * Filter the submission form output.
			 *
			 * @since 1.0.0
			 *
			 * @param string $output The form output.
			 * @param array  $atts   The shortcode attributes.
			 */
			return apply_filters( 'apd_submission_form_output', '', $atts );
		}

		// Default: show coming soon message.
		return $this->coming_soon( __( 'Listing submission', 'all-purpose-directory' ) );
	}
}
