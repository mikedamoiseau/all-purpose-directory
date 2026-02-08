<?php
/**
 * SubmissionForm Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Submission
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Submission;

use APD\Frontend\Submission\SubmissionForm;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for SubmissionForm.
 *
 * Note: Since FieldRegistry and FieldRenderer are final/singleton classes,
 * we test the SubmissionForm with its default dependencies and mock only
 * WordPress functions.
 */
final class SubmissionFormTest extends UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock common WordPress functions used by SubmissionForm.
		Functions\stubs( [
			'get_post'              => null,
			'get_the_terms'         => [],
			'get_post_thumbnail_id' => 0,
			'get_terms'             => [],
		] );
	}

	/**
	 * Test constructor sets default configuration.
	 */
	public function test_constructor_sets_default_config(): void {
		$form = new SubmissionForm();

		$config = $form->get_config();

		$this->assertTrue( $config['show_title'] );
		$this->assertTrue( $config['show_content'] );
		$this->assertFalse( $config['show_excerpt'] );
		$this->assertTrue( $config['show_categories'] );
		$this->assertTrue( $config['show_tags'] );
		$this->assertTrue( $config['show_featured_image'] );
		$this->assertFalse( $config['show_terms'] );
		$this->assertTrue( $config['terms_required'] );
		$this->assertSame( 0, $config['listing_id'] );
	}

	/**
	 * Test constructor merges custom configuration.
	 */
	public function test_constructor_merges_custom_config(): void {
		$form = new SubmissionForm( [
			'show_title'     => false,
			'show_terms'     => true,
			'redirect'       => '/thank-you/',
			'custom_option'  => 'test',
		] );

		$config = $form->get_config();

		$this->assertFalse( $config['show_title'] );
		$this->assertTrue( $config['show_terms'] );
		$this->assertSame( '/thank-you/', $config['redirect'] );
		$this->assertSame( 'test', $config['custom_option'] );
	}

	/**
	 * Test get_config_value returns correct value.
	 */
	public function test_get_config_value_returns_value(): void {
		$form = new SubmissionForm( [ 'redirect' => '/custom/' ] );

		$this->assertSame( '/custom/', $form->get_config_value( 'redirect' ) );
	}

	/**
	 * Test get_config_value returns default for missing key.
	 */
	public function test_get_config_value_returns_default(): void {
		$form = new SubmissionForm();

		$this->assertSame( 'default', $form->get_config_value( 'nonexistent', 'default' ) );
	}

	/**
	 * Test default terms text is set.
	 */
	public function test_default_terms_text_is_set(): void {
		$form = new SubmissionForm();

		$config = $form->get_config();

		$this->assertNotEmpty( $config['terms_text'] );
		$this->assertStringContainsString( 'terms', strtolower( $config['terms_text'] ) );
	}

	/**
	 * Test default submit text for new listing.
	 */
	public function test_default_submit_text_for_new_listing(): void {
		$form = new SubmissionForm();

		$config = $form->get_config();

		$this->assertStringContainsString( 'Submit', $config['submit_text'] );
	}

	/**
	 * Test default submit text for edit mode.
	 */
	public function test_default_submit_text_for_edit_mode(): void {
		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$config = $form->get_config();

		$this->assertStringContainsString( 'Update', $config['submit_text'] );
	}

	/**
	 * Test custom submit text overrides default.
	 */
	public function test_custom_submit_text_overrides_default(): void {
		$form = new SubmissionForm( [ 'submit_text' => 'Create Listing' ] );

		$config = $form->get_config();

		$this->assertSame( 'Create Listing', $config['submit_text'] );
	}

	/**
	 * Test is_edit_mode returns false for new listing.
	 */
	public function test_is_edit_mode_false_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertFalse( $form->is_edit_mode() );
	}

	/**
	 * Test is_edit_mode returns true for existing listing.
	 */
	public function test_is_edit_mode_true_for_existing_listing(): void {
		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertTrue( $form->is_edit_mode() );
	}

	/**
	 * Test get_redirect_url returns configured URL.
	 */
	public function test_get_redirect_url_returns_configured_url(): void {
		$form = new SubmissionForm( [ 'redirect' => '/thank-you/' ] );

		$this->assertSame( '/thank-you/', $form->get_redirect_url() );
	}

	/**
	 * Test get_redirect_url returns empty for no redirect.
	 */
	public function test_get_redirect_url_returns_empty_for_no_redirect(): void {
		$form = new SubmissionForm();

		$this->assertSame( '', $form->get_redirect_url() );
	}

	/**
	 * Test set_errors with array.
	 */
	public function test_set_errors_with_array(): void {
		$form = new SubmissionForm();

		$errors = [
			'listing_title' => [ 'Title is required.' ],
		];

		$result = $form->set_errors( $errors );

		$this->assertSame( $form, $result );
		$this->assertSame( $errors, $form->get_errors() );
	}

	/**
	 * Test has_errors returns false when no errors.
	 */
	public function test_has_errors_returns_false_when_no_errors(): void {
		$form = new SubmissionForm();

		$this->assertFalse( $form->has_errors() );
	}

	/**
	 * Test has_errors returns true when has errors.
	 */
	public function test_has_errors_returns_true_when_has_errors(): void {
		$form = new SubmissionForm();

		$form->set_errors( [ 'field' => [ 'Error message' ] ] );

		$this->assertTrue( $form->has_errors() );
	}

	/**
	 * Test get_field_values returns empty for new listing.
	 */
	public function test_get_field_values_returns_empty_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( [], $form->get_field_values() );
	}

	/**
	 * Test get_field_values returns submitted values when available.
	 */
	public function test_get_field_values_returns_submitted_values(): void {
		$submitted = [
			'phone'   => '123-456-7890',
			'website' => 'https://example.com',
		];

		$form = new SubmissionForm( [ 'submitted_values' => $submitted ] );

		$this->assertSame( $submitted, $form->get_field_values() );
	}

	/**
	 * Test get_title_value returns empty for new listing.
	 */
	public function test_get_title_value_returns_empty_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( '', $form->get_title_value() );
	}

	/**
	 * Test get_title_value returns submitted value.
	 */
	public function test_get_title_value_returns_submitted_value(): void {
		$form = new SubmissionForm( [ 'submitted_values' => [ 'listing_title' => 'Test Title' ] ] );

		$this->assertSame( 'Test Title', $form->get_title_value() );
	}

	/**
	 * Test get_content_value returns empty for new listing.
	 */
	public function test_get_content_value_returns_empty_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( '', $form->get_content_value() );
	}

	/**
	 * Test get_content_value returns submitted value.
	 */
	public function test_get_content_value_returns_submitted_value(): void {
		$form = new SubmissionForm( [ 'submitted_values' => [ 'listing_content' => 'Test content here.' ] ] );

		$this->assertSame( 'Test content here.', $form->get_content_value() );
	}

	/**
	 * Test get_excerpt_value returns empty for new listing.
	 */
	public function test_get_excerpt_value_returns_empty_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( '', $form->get_excerpt_value() );
	}

	/**
	 * Test get_excerpt_value returns submitted value.
	 */
	public function test_get_excerpt_value_returns_submitted_value(): void {
		$form = new SubmissionForm( [ 'submitted_values' => [ 'listing_excerpt' => 'Short description' ] ] );

		$this->assertSame( 'Short description', $form->get_excerpt_value() );
	}

	/**
	 * Test get_selected_categories returns empty for new listing.
	 */
	public function test_get_selected_categories_returns_empty_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( [], $form->get_selected_categories() );
	}

	/**
	 * Test get_selected_categories returns submitted values.
	 */
	public function test_get_selected_categories_returns_submitted_values(): void {
		$form = new SubmissionForm( [ 'submitted_values' => [ 'listing_categories' => [ '1', '5', '10' ] ] ] );

		$this->assertSame( [ 1, 5, 10 ], $form->get_selected_categories() );
	}

	/**
	 * Test get_selected_tags returns empty for new listing.
	 */
	public function test_get_selected_tags_returns_empty_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( [], $form->get_selected_tags() );
	}

	/**
	 * Test get_selected_tags returns submitted values.
	 */
	public function test_get_selected_tags_returns_submitted_values(): void {
		$form = new SubmissionForm( [ 'submitted_values' => [ 'listing_tags' => [ '2', '8' ] ] ] );

		$this->assertSame( [ 2, 8 ], $form->get_selected_tags() );
	}

	/**
	 * Test get_featured_image_id returns zero for new listing.
	 */
	public function test_get_featured_image_id_returns_zero_for_new_listing(): void {
		$form = new SubmissionForm();

		$this->assertSame( 0, $form->get_featured_image_id() );
	}

	/**
	 * Test get_featured_image_id returns submitted value.
	 */
	public function test_get_featured_image_id_returns_submitted_value(): void {
		$form = new SubmissionForm( [ 'submitted_values' => [ 'featured_image' => '456' ] ] );

		$this->assertSame( 456, $form->get_featured_image_id() );
	}

	/**
	 * Test get_categories returns empty when get_terms returns empty.
	 */
	public function test_get_categories_returns_empty_array(): void {
		Functions\when( 'get_terms' )->justReturn( [] );

		$form = new SubmissionForm();

		$categories = $form->get_categories();

		$this->assertSame( [], $categories );
	}

	/**
	 * Test get_tags returns empty when get_terms returns empty.
	 */
	public function test_get_tags_returns_empty_array(): void {
		Functions\when( 'get_terms' )->justReturn( [] );

		$form = new SubmissionForm();

		$tags = $form->get_tags();

		$this->assertSame( [], $tags );
	}

	/**
	 * Test get_field_groups returns empty by default.
	 */
	public function test_get_field_groups_returns_empty_by_default(): void {
		$form = new SubmissionForm();

		$groups = $form->get_field_groups();

		$this->assertSame( [], $groups );
	}

	/**
	 * Test get_category_options returns empty when no categories.
	 */
	public function test_get_category_options_returns_empty_when_no_categories(): void {
		Functions\when( 'get_terms' )->justReturn( [] );

		$form = new SubmissionForm();

		$options = $form->get_category_options();

		$this->assertSame( [], $options );
	}

	/**
	 * Test nonce action is set correctly.
	 */
	public function test_nonce_action_is_set(): void {
		$form = new SubmissionForm();

		$config = $form->get_config();

		$this->assertSame( 'apd_submit_listing', $config['nonce_action'] );
	}

	/**
	 * Test nonce name is set correctly.
	 */
	public function test_nonce_name_is_set(): void {
		$form = new SubmissionForm();

		$config = $form->get_config();

		$this->assertSame( 'apd_submission_nonce', $config['nonce_name'] );
	}

	/**
	 * Test custom nonce action can be set.
	 */
	public function test_custom_nonce_action_can_be_set(): void {
		$form = new SubmissionForm( [ 'nonce_action' => 'custom_action' ] );

		$config = $form->get_config();

		$this->assertSame( 'custom_action', $config['nonce_action'] );
	}

	/**
	 * Test custom nonce name can be set.
	 */
	public function test_custom_nonce_name_can_be_set(): void {
		$form = new SubmissionForm( [ 'nonce_name' => 'custom_nonce' ] );

		$config = $form->get_config();

		$this->assertSame( 'custom_nonce', $config['nonce_name'] );
	}

	/**
	 * Test get_submission_fields applies filter.
	 */
	public function test_get_submission_fields_applies_filter(): void {
		$form = new SubmissionForm();

		// The filter is applied via apply_filters which returns its first arg by default
		$fields = $form->get_submission_fields();

		$this->assertIsArray( $fields );
	}

	// =========================================================================
	// Edit Mode Tests
	// =========================================================================

	/**
	 * Test get_title_value returns post title in edit mode.
	 */
	public function test_get_title_value_returns_post_title_in_edit_mode(): void {
		$mock_post = (object) [
			'post_title'   => 'Existing Title',
			'post_content' => 'Existing content',
			'post_excerpt' => 'Existing excerpt',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertSame( 'Existing Title', $form->get_title_value() );
	}

	/**
	 * Test get_content_value returns post content in edit mode.
	 */
	public function test_get_content_value_returns_post_content_in_edit_mode(): void {
		$mock_post = (object) [
			'post_title'   => 'Existing Title',
			'post_content' => 'Existing content here',
			'post_excerpt' => 'Existing excerpt',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertSame( 'Existing content here', $form->get_content_value() );
	}

	/**
	 * Test get_excerpt_value returns post excerpt in edit mode.
	 */
	public function test_get_excerpt_value_returns_post_excerpt_in_edit_mode(): void {
		$mock_post = (object) [
			'post_title'   => 'Existing Title',
			'post_content' => 'Existing content',
			'post_excerpt' => 'Short excerpt text',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertSame( 'Short excerpt text', $form->get_excerpt_value() );
	}

	/**
	 * Test get_selected_categories returns terms in edit mode.
	 */
	public function test_get_selected_categories_returns_terms_in_edit_mode(): void {
		$mock_terms = [
			(object) [ 'term_id' => 1 ],
			(object) [ 'term_id' => 5 ],
		];
		Functions\when( 'get_the_terms' )->justReturn( $mock_terms );

		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertSame( [ 1, 5 ], $form->get_selected_categories() );
	}

	/**
	 * Test get_selected_tags returns terms in edit mode.
	 */
	public function test_get_selected_tags_returns_terms_in_edit_mode(): void {
		$mock_terms = [
			(object) [ 'term_id' => 10 ],
			(object) [ 'term_id' => 20 ],
		];
		Functions\when( 'get_the_terms' )->justReturn( $mock_terms );

		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertSame( [ 10, 20 ], $form->get_selected_tags() );
	}

	/**
	 * Test get_featured_image_id returns thumbnail in edit mode.
	 */
	public function test_get_featured_image_id_returns_thumbnail_in_edit_mode(): void {
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 456 );

		$form = new SubmissionForm( [ 'listing_id' => 123 ] );

		$this->assertSame( 456, $form->get_featured_image_id() );
	}

	/**
	 * Test submitted values override existing values in edit mode.
	 */
	public function test_submitted_values_override_existing_in_edit_mode(): void {
		$mock_post = (object) [
			'post_title'   => 'Existing Title',
			'post_content' => 'Existing content',
			'post_excerpt' => 'Existing excerpt',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$submitted = [
			'listing_title'   => 'Updated Title',
			'listing_content' => 'Updated content',
		];

		$form = new SubmissionForm( [
			'listing_id'       => 123,
			'submitted_values' => $submitted,
		] );

		// Submitted values should take precedence
		$this->assertSame( 'Updated Title', $form->get_title_value() );
		$this->assertSame( 'Updated content', $form->get_content_value() );
	}

	// =========================================================================
	// Template Accessibility Tests
	// =========================================================================

	/**
	 * Test image upload template has aria-labelledby for accessibility.
	 */
	public function test_image_upload_template_has_aria_labelledby(): void {
		$template_path = dirname( __DIR__, 4 ) . '/templates/submission/image-upload.php';

		$this->assertFileExists( $template_path );

		$content = file_get_contents( $template_path );

		$this->assertStringContainsString( 'aria-labelledby', $content, 'Image upload template should have aria-labelledby for accessibility' );
	}

	/**
	 * Test image upload template has aria-describedby for accessibility.
	 */
	public function test_image_upload_template_has_aria_describedby(): void {
		$template_path = dirname( __DIR__, 4 ) . '/templates/submission/image-upload.php';

		$content = file_get_contents( $template_path );

		$this->assertStringContainsString( 'aria-describedby', $content, 'Image upload template should have aria-describedby for accessibility' );
	}

	/**
	 * Test image upload template error container has role="alert".
	 */
	public function test_image_upload_template_errors_have_role_alert(): void {
		$template_path = dirname( __DIR__, 4 ) . '/templates/submission/image-upload.php';

		$content = file_get_contents( $template_path );

		$this->assertStringContainsString( 'role="alert"', $content, 'Image upload template errors should have role="alert" for screen readers' );
	}
}
