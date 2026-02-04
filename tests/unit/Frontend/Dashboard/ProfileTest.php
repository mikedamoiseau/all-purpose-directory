<?php
/**
 * Profile Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Dashboard
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Dashboard;

use APD\Frontend\Dashboard\Profile;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for Profile.
 */
final class ProfileTest extends UnitTestCase {

	/**
	 * Store original $_POST to restore later.
	 *
	 * @var array
	 */
	private array $original_post = [];

	/**
	 * Store original $_FILES to restore later.
	 *
	 * @var array
	 */
	private array $original_files = [];

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Store and clear superglobals.
		$this->original_post  = $_POST;
		$this->original_files = $_FILES;
		$_POST                = [];
		$_FILES               = [];

		// Mock common WordPress functions.
		Functions\stubs( [
			'get_current_user_id'  => 1,
			'is_user_logged_in'    => true,
			'sanitize_text_field'  => static fn( $str ) => is_string( $str ) ? trim( $str ) : '',
			'sanitize_textarea_field' => static fn( $str ) => is_string( $str ) ? trim( $str ) : '',
			'sanitize_email'       => static fn( $email ) => filter_var( $email, FILTER_SANITIZE_EMAIL ),
			'is_email'             => static fn( $email ) => (bool) filter_var( $email, FILTER_VALIDATE_EMAIL ),
			'esc_url_raw'          => static fn( $url ) => filter_var( $url, FILTER_SANITIZE_URL ),
			'wp_parse_args'        => static fn( $args, $defaults ) => array_merge( $defaults, $args ),
			'wp_create_nonce'      => static fn( $action ) => 'test_nonce_' . $action,
			'wp_verify_nonce'      => static fn( $nonce, $action ) => strpos( $nonce, $action ) !== false,
			'wp_unslash'           => static fn( $value ) => $value,
			'absint'               => static fn( $val ) => abs( (int) $val ),
			'size_format'          => static fn( $bytes ) => round( $bytes / 1024 / 1024, 2 ) . ' MB',
			'get_avatar_url'       => static fn( $id, $args = [] ) => 'https://gravatar.com/avatar/' . $id,
		] );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Restore superglobals.
		$_POST  = $this->original_post;
		$_FILES = $this->original_files;

		// Reset singleton instance.
		$reflection = new \ReflectionClass( Profile::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * Test constructor sets default configuration.
	 */
	public function test_constructor_sets_default_config(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$profile = new Profile();

		$this->assertSame( 0, $profile->get_user_id() );
	}

	/**
	 * Test constructor with current user.
	 */
	public function test_constructor_sets_current_user(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 42 );

		$profile = new Profile();

		$this->assertSame( 42, $profile->get_user_id() );
	}

	/**
	 * Test set_user_id and get_user_id.
	 */
	public function test_set_and_get_user_id(): void {
		$profile = new Profile();

		$profile->set_user_id( 99 );
		$this->assertSame( 99, $profile->get_user_id() );

		$profile->set_user_id( 0 );
		$this->assertSame( 0, $profile->get_user_id() );
	}

	/**
	 * Test singleton returns instance.
	 */
	public function test_get_instance_returns_instance(): void {
		$instance = Profile::get_instance();

		$this->assertInstanceOf( Profile::class, $instance );
	}

	/**
	 * Test singleton with config updates configuration.
	 */
	public function test_get_instance_with_config_updates(): void {
		$instance = Profile::get_instance( [ 'show_avatar' => false ] );

		$this->assertInstanceOf( Profile::class, $instance );
	}

	/**
	 * Test render returns empty string for no user.
	 */
	public function test_render_returns_empty_for_no_user(): void {
		$profile = new Profile();
		$profile->set_user_id( 0 );

		$result = $profile->render();

		$this->assertSame( '', $result );
	}

	/**
	 * Test get_user_data returns empty structure for invalid user.
	 */
	public function test_get_user_data_returns_empty_for_invalid_user(): void {
		Functions\when( 'get_userdata' )->justReturn( false );

		$profile = new Profile();
		$data    = $profile->get_user_data( 0 );

		$this->assertIsArray( $data );
		$this->assertSame( '', $data['display_name'] );
		$this->assertSame( '', $data['user_email'] );
		$this->assertIsArray( $data['social'] );
	}

	/**
	 * Test get_user_data returns correct structure for valid user.
	 */
	public function test_get_user_data_returns_correct_structure(): void {
		$user_obj                = new \stdClass();
		$user_obj->display_name  = 'Test User';
		$user_obj->first_name    = 'Test';
		$user_obj->last_name     = 'User';
		$user_obj->user_email    = 'test@example.com';
		$user_obj->description   = 'Test bio';
		$user_obj->user_url      = 'https://example.com';

		Functions\when( 'get_userdata' )->justReturn( $user_obj );
		Functions\when( 'get_user_meta' )->justReturn( '' );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );
		$data = $profile->get_user_data();

		$this->assertSame( 'Test User', $data['display_name'] );
		$this->assertSame( 'Test', $data['first_name'] );
		$this->assertSame( 'User', $data['last_name'] );
		$this->assertSame( 'test@example.com', $data['user_email'] );
		$this->assertSame( 'Test bio', $data['description'] );
		$this->assertSame( 'https://example.com', $data['user_url'] );
		$this->assertArrayHasKey( 'social', $data );
	}

	/**
	 * Test validate_profile returns error for empty display name.
	 */
	public function test_validate_profile_requires_display_name(): void {
		Functions\when( 'email_exists' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => '',
			'user_email'   => 'test@example.com',
			'social'       => [],
		];

		$result = $profile->validate_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate_profile returns error for empty email.
	 */
	public function test_validate_profile_requires_email(): void {
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => '',
			'social'       => [],
		];

		$result = $profile->validate_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate_profile returns error for invalid email.
	 */
	public function test_validate_profile_validates_email_format(): void {
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => 'invalid-email',
			'social'       => [],
		];

		$result = $profile->validate_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate_profile returns error for email already in use.
	 */
	public function test_validate_profile_checks_email_exists(): void {
		Functions\when( 'email_exists' )->justReturn( 999 ); // Different user ID
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => 'taken@example.com',
			'social'       => [],
		];

		$result = $profile->validate_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate_profile allows same user's email.
	 */
	public function test_validate_profile_allows_own_email(): void {
		Functions\when( 'email_exists' )->justReturn( 1 ); // Same user ID
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => 'test@example.com',
			'user_url'     => '',
			'social'       => [],
		];

		$result = $profile->validate_profile( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate_profile validates website URL.
	 */
	public function test_validate_profile_validates_website_url(): void {
		Functions\when( 'email_exists' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => 'test@example.com',
			'user_url'     => 'not-a-url',
			'social'       => [],
		];

		$result = $profile->validate_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate_profile validates social URLs.
	 */
	public function test_validate_profile_validates_social_urls(): void {
		Functions\when( 'email_exists' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => 'test@example.com',
			'user_url'     => '',
			'social'       => [
				'facebook'  => 'not-a-url',
				'twitter'   => '',
				'linkedin'  => '',
				'instagram' => '',
			],
		];

		$result = $profile->validate_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate_profile passes with valid data.
	 */
	public function test_validate_profile_passes_with_valid_data(): void {
		Functions\when( 'email_exists' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'user_email'   => 'test@example.com',
			'user_url'     => 'https://example.com',
			'social'       => [
				'facebook'  => 'https://facebook.com/test',
				'twitter'   => 'https://x.com/test',
				'linkedin'  => '',
				'instagram' => '',
			],
		];

		$result = $profile->validate_profile( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test save_profile calls wp_update_user.
	 */
	public function test_save_profile_calls_wp_update_user(): void {
		$captured_user_data = null;

		Functions\when( 'wp_update_user' )->alias( function( $data ) use ( &$captured_user_data ) {
			$captured_user_data = $data;
			return $data['ID'];
		} );

		Functions\when( 'update_user_meta' )->justReturn( true );
		Functions\when( 'delete_user_meta' )->justReturn( true );
		Functions\when( 'do_action' )->justReturn( null );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'first_name'   => 'Test',
			'last_name'    => 'User',
			'user_email'   => 'test@example.com',
			'description'  => 'Test bio',
			'user_url'     => 'https://example.com',
			'phone'        => '555-1234',
			'social'       => [
				'facebook'  => 'https://facebook.com/test',
				'twitter'   => '',
				'linkedin'  => '',
				'instagram' => '',
			],
		];

		$result = $profile->save_profile( $data );

		$this->assertTrue( $result );
		$this->assertSame( 1, $captured_user_data['ID'] );
		$this->assertSame( 'Test User', $captured_user_data['display_name'] );
		$this->assertSame( 'test@example.com', $captured_user_data['user_email'] );
	}

	/**
	 * Test save_profile returns WP_Error on failure.
	 */
	public function test_save_profile_returns_error_on_failure(): void {
		Functions\when( 'wp_update_user' )->justReturn( new \WP_Error( 'update_failed', 'Update failed' ) );
		Functions\when( 'do_action' )->justReturn( null );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$data = [
			'display_name' => 'Test User',
			'first_name'   => '',
			'last_name'    => '',
			'user_email'   => 'test@example.com',
			'description'  => '',
			'user_url'     => '',
			'phone'        => '',
			'social'       => [],
		];

		$result = $profile->save_profile( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test get_avatar_url returns custom avatar if set.
	 */
	public function test_get_avatar_url_returns_custom_avatar(): void {
		Functions\when( 'get_user_meta' )->justReturn( 123 );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://example.com/avatar.jpg' );

		$profile = new Profile();

		$url = $profile->get_avatar_url( 1, 150 );

		$this->assertSame( 'https://example.com/avatar.jpg', $url );
	}

	/**
	 * Test get_avatar_url falls back to Gravatar.
	 */
	public function test_get_avatar_url_falls_back_to_gravatar(): void {
		Functions\when( 'get_user_meta' )->justReturn( 0 );

		$profile = new Profile();

		$url = $profile->get_avatar_url( 1, 150 );

		$this->assertStringContainsString( 'gravatar.com', $url );
	}

	/**
	 * Test get_social_links returns array of links.
	 */
	public function test_get_social_links_returns_array(): void {
		Functions\when( 'get_user_meta' )->alias( function( $user_id, $key, $single ) {
			if ( $key === '_apd_social_facebook' ) {
				return 'https://facebook.com/test';
			}
			return '';
		} );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$profile = new Profile();

		$links = $profile->get_social_links( 1 );

		$this->assertIsArray( $links );
		$this->assertArrayHasKey( 'facebook', $links );
		$this->assertArrayHasKey( 'twitter', $links );
		$this->assertArrayHasKey( 'linkedin', $links );
		$this->assertArrayHasKey( 'instagram', $links );
		$this->assertSame( 'https://facebook.com/test', $links['facebook'] );
	}

	/**
	 * Test has_custom_avatar returns true when avatar is set.
	 */
	public function test_has_custom_avatar_returns_true_when_set(): void {
		Functions\when( 'get_user_meta' )->justReturn( 123 );

		$profile = new Profile();

		$this->assertTrue( $profile->has_custom_avatar( 1 ) );
	}

	/**
	 * Test has_custom_avatar returns false when not set.
	 */
	public function test_has_custom_avatar_returns_false_when_not_set(): void {
		Functions\when( 'get_user_meta' )->justReturn( 0 );

		$profile = new Profile();

		$this->assertFalse( $profile->has_custom_avatar( 1 ) );
	}

	/**
	 * Test get_message returns null when no message.
	 */
	public function test_get_message_returns_null_when_no_message(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$message = $profile->get_message();

		$this->assertNull( $message );
	}

	/**
	 * Test get_message returns and clears message.
	 */
	public function test_get_message_returns_and_clears_message(): void {
		$stored_message = [
			'type'    => 'success',
			'message' => 'Profile updated',
		];

		Functions\when( 'get_transient' )->justReturn( $stored_message );
		Functions\when( 'delete_transient' )->justReturn( true );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$message = $profile->get_message();

		$this->assertIsArray( $message );
		$this->assertSame( 'success', $message['type'] );
		$this->assertSame( 'Profile updated', $message['message'] );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd_profile_save', Profile::NONCE_ACTION );
		$this->assertSame( '_apd_profile_nonce', Profile::NONCE_NAME );
		$this->assertSame( 2097152, Profile::MAX_AVATAR_SIZE );
		$this->assertContains( 'image/jpeg', Profile::ALLOWED_AVATAR_TYPES );
		$this->assertContains( 'facebook', Profile::SOCIAL_PLATFORMS );
	}

	/**
	 * Test get_social_labels returns array.
	 */
	public function test_get_social_labels_returns_array(): void {
		$profile = new Profile();
		$labels  = $profile->get_social_labels();

		$this->assertIsArray( $labels );
		$this->assertArrayHasKey( 'facebook', $labels );
		$this->assertArrayHasKey( 'twitter', $labels );
		$this->assertArrayHasKey( 'linkedin', $labels );
		$this->assertArrayHasKey( 'instagram', $labels );
	}

	/**
	 * Test get_social_icons returns array.
	 */
	public function test_get_social_icons_returns_array(): void {
		$profile = new Profile();
		$icons   = $profile->get_social_icons();

		$this->assertIsArray( $icons );
		$this->assertSame( 'dashicons-facebook', $icons['facebook'] );
		$this->assertSame( 'dashicons-twitter', $icons['twitter'] );
		$this->assertSame( 'dashicons-linkedin', $icons['linkedin'] );
		$this->assertSame( 'dashicons-instagram', $icons['instagram'] );
	}

	/**
	 * Test handle_avatar_upload returns 0 when no file.
	 */
	public function test_handle_avatar_upload_returns_0_when_no_file(): void {
		$profile = new Profile();
		$profile->set_user_id( 1 );

		$result = $profile->handle_avatar_upload();

		$this->assertSame( 0, $result );
	}

	/**
	 * Test handle_avatar_upload returns error for upload errors.
	 */
	public function test_handle_avatar_upload_returns_error_for_upload_error(): void {
		$_FILES['apd_avatar'] = [
			'name'     => 'test.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/test.jpg',
			'error'    => UPLOAD_ERR_NO_FILE,
			'size'     => 1000,
		];

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$result = $profile->handle_avatar_upload();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test handle_avatar_upload returns error for too large file.
	 */
	public function test_handle_avatar_upload_returns_error_for_large_file(): void {
		$_FILES['apd_avatar'] = [
			'name'     => 'test.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/test.jpg',
			'error'    => UPLOAD_ERR_OK,
			'size'     => Profile::MAX_AVATAR_SIZE + 1,
		];

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$result = $profile->handle_avatar_upload();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test handle_avatar_upload returns error for invalid type.
	 */
	public function test_handle_avatar_upload_returns_error_for_invalid_type(): void {
		$_FILES['apd_avatar'] = [
			'name'     => 'test.pdf',
			'type'     => 'application/pdf',
			'tmp_name' => '/tmp/test.pdf',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 1000,
		];

		Functions\when( 'wp_check_filetype' )->justReturn( [
			'ext'  => 'pdf',
			'type' => 'application/pdf',
		] );

		$profile = new Profile();
		$profile->set_user_id( 1 );

		$result = $profile->handle_avatar_upload();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
