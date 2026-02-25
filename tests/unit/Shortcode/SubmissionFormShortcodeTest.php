<?php
/**
 * SubmissionFormShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\SubmissionFormShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for SubmissionFormShortcode.
 */
final class SubmissionFormShortcodeTest extends UnitTestCase {

	/**
	 * SubmissionFormShortcode instance.
	 *
	 * @var SubmissionFormShortcode
	 */
	private SubmissionFormShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock admin settings used in permission checks.
		Functions\when( 'apd_get_option' )->alias( function ( $key, $default = null ) {
			$settings = [
				'who_can_submit'   => 'anyone',
				'guest_submission' => false,
				'submission_roles' => [],
				'terms_page'       => 0,
			];
			return $settings[ $key ] ?? $default;
		} );

		$this->shortcode = new SubmissionFormShortcode();
	}

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_submission_form(): void {
		$this->assertSame( 'apd_submission_form', $this->shortcode->get_tag() );
	}

	/**
	 * Test description is set.
	 */
	public function test_description_is_set(): void {
		$this->assertNotEmpty( $this->shortcode->get_description() );
		$this->assertStringContainsString( 'submission', strtolower( $this->shortcode->get_description() ) );
	}

	/**
	 * Test has correct default attributes.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( 'true', $defaults['require_login'] );
		$this->assertSame( '', $defaults['redirect'] );
		$this->assertSame( 'true', $defaults['show_title'] );
		$this->assertSame( 'true', $defaults['show_content'] );
		$this->assertSame( 'false', $defaults['show_excerpt'] );
		$this->assertSame( 'true', $defaults['show_categories'] );
		$this->assertSame( 'true', $defaults['show_tags'] );
		$this->assertSame( 'true', $defaults['show_featured_image'] );
		$this->assertSame( 'false', $defaults['show_terms'] );
		$this->assertSame( '', $defaults['terms_text'] );
		$this->assertSame( '', $defaults['terms_link'] );
		$this->assertSame( 'true', $defaults['terms_required'] );
		$this->assertSame( '', $defaults['submit_text'] );
		$this->assertSame( '', $defaults['class'] );
	}

	/**
	 * Test attribute documentation exists for all attributes.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'require_login', $docs );
		$this->assertArrayHasKey( 'redirect', $docs );
		$this->assertArrayHasKey( 'show_title', $docs );
		$this->assertArrayHasKey( 'show_content', $docs );
		$this->assertArrayHasKey( 'show_excerpt', $docs );
		$this->assertArrayHasKey( 'show_categories', $docs );
		$this->assertArrayHasKey( 'show_tags', $docs );
		$this->assertArrayHasKey( 'show_featured_image', $docs );
		$this->assertArrayHasKey( 'show_terms', $docs );
		$this->assertArrayHasKey( 'terms_text', $docs );
		$this->assertArrayHasKey( 'terms_link', $docs );
		$this->assertArrayHasKey( 'terms_required', $docs );
		$this->assertArrayHasKey( 'submit_text', $docs );
		$this->assertArrayHasKey( 'class', $docs );
	}

	/**
	 * Test example usage is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_submission_form', $example );
		$this->assertStringContainsString( ']', $example );
	}

	/**
	 * Test require_login attribute is boolean type.
	 */
	public function test_require_login_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['require_login']['type'] );
	}

	/**
	 * Test redirect attribute is string type.
	 */
	public function test_redirect_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['redirect']['type'] );
	}

	/**
	 * Test show_title attribute is boolean type.
	 */
	public function test_show_title_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_title']['type'] );
	}

	/**
	 * Test show_content attribute is boolean type.
	 */
	public function test_show_content_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_content']['type'] );
	}

	/**
	 * Test show_excerpt attribute is boolean type.
	 */
	public function test_show_excerpt_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_excerpt']['type'] );
	}

	/**
	 * Test show_categories attribute is boolean type.
	 */
	public function test_show_categories_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_categories']['type'] );
	}

	/**
	 * Test show_tags attribute is boolean type.
	 */
	public function test_show_tags_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_tags']['type'] );
	}

	/**
	 * Test show_featured_image attribute is boolean type.
	 */
	public function test_show_featured_image_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_featured_image']['type'] );
	}

	/**
	 * Test show_terms attribute is boolean type.
	 */
	public function test_show_terms_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_terms']['type'] );
	}

	/**
	 * Test terms_text attribute is string type.
	 */
	public function test_terms_text_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['terms_text']['type'] );
	}

	/**
	 * Test terms_link attribute is string type.
	 */
	public function test_terms_link_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['terms_link']['type'] );
	}

	/**
	 * Test terms_required attribute is boolean type.
	 */
	public function test_terms_required_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['terms_required']['type'] );
	}

	/**
	 * Test submit_text attribute is string type.
	 */
	public function test_submit_text_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['submit_text']['type'] );
	}

	/**
	 * Test class attribute is string type.
	 */
	public function test_class_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['class']['type'] );
	}

	/**
	 * Test render shows login message when required and not logged in.
	 */
	public function test_render_shows_login_message_when_not_logged_in(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_login_url' )->justReturn( 'https://example.com/wp-login.php' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/submit/' );
		Functions\when( 'shortcode_atts' )->alias( function ( $defaults, $atts, $shortcode ) {
			return array_merge( $defaults, $atts );
		} );

		// Mock the submission success check function.
		Functions\when( 'apd_is_submission_success' )->justReturn( false );

		// Note: filter_var is a PHP internal function and can't be mocked.
		// The shortcode uses filter_var( $value, FILTER_VALIDATE_BOOLEAN ).
		// Since 'true' string evaluates to true for FILTER_VALIDATE_BOOLEAN,
		// the test passes the actual attributes through shortcode_atts.
		$output = $this->shortcode->render( [ 'require_login' => 'true' ] );

		$this->assertStringContainsString( 'apd-login-required', $output );
		$this->assertStringContainsString( 'Log In', $output );
	}

	/**
	 * Test all attribute docs have type defined.
	 */
	public function test_all_attribute_docs_have_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		foreach ( $docs as $attr => $doc ) {
			$this->assertArrayHasKey( 'type', $doc, "Attribute '$attr' is missing 'type' in documentation." );
		}
	}

	/**
	 * Test all attribute docs have description defined.
	 */
	public function test_all_attribute_docs_have_description(): void {
		$docs = $this->shortcode->get_attribute_docs();

		foreach ( $docs as $attr => $doc ) {
			$this->assertArrayHasKey( 'description', $doc, "Attribute '$attr' is missing 'description' in documentation." );
			$this->assertNotEmpty( $doc['description'], "Attribute '$attr' has empty description." );
		}
	}

	/**
	 * Test all attribute docs have default defined.
	 */
	public function test_all_attribute_docs_have_default(): void {
		$docs = $this->shortcode->get_attribute_docs();

		foreach ( $docs as $attr => $doc ) {
			$this->assertArrayHasKey( 'default', $doc, "Attribute '$attr' is missing 'default' in documentation." );
		}
	}

	/**
	 * Test defaults match documented defaults.
	 */
	public function test_defaults_match_documented_defaults(): void {
		$defaults = $this->shortcode->get_defaults();
		$docs     = $this->shortcode->get_attribute_docs();

		foreach ( $defaults as $attr => $default ) {
			if ( isset( $docs[ $attr ] ) ) {
				$this->assertSame(
					$docs[ $attr ]['default'],
					$default,
					"Default for '$attr' doesn't match documented default."
				);
			}
		}
	}

	// =========================================================================
	// Edit Mode Tests
	// =========================================================================

	/**
	 * Test listing_id attribute exists in defaults.
	 */
	public function test_listing_id_attribute_exists(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertArrayHasKey( 'listing_id', $defaults );
		$this->assertSame( '0', $defaults['listing_id'] );
	}

	/**
	 * Test listing_id attribute documentation exists.
	 */
	public function test_listing_id_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'listing_id', $docs );
		$this->assertArrayHasKey( 'type', $docs['listing_id'] );
		$this->assertArrayHasKey( 'description', $docs['listing_id'] );
	}

	/**
	 * Test listing_id attribute type is integer.
	 */
	public function test_listing_id_is_integer_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['listing_id']['type'] );
	}

	/**
	 * Test listing_id description mentions URL parameter.
	 */
	public function test_listing_id_description_mentions_url_parameter(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertStringContainsString( 'edit_listing', $docs['listing_id']['description'] );
	}
}
