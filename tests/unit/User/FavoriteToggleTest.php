<?php
/**
 * FavoriteToggle Unit Tests.
 *
 * @package APD\Tests\Unit\User
 */

declare(strict_types=1);

namespace APD\Tests\Unit\User;

use APD\User\FavoriteToggle;
use APD\User\Favorites;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for FavoriteToggle.
 */
final class FavoriteToggleTest extends UnitTestCase {

	/**
	 * FavoriteToggle instance.
	 *
	 * @var FavoriteToggle
	 */
	private FavoriteToggle $toggle;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton for clean tests.
		$reflection = new \ReflectionClass( FavoriteToggle::class );
		$instance   = $reflection->getProperty( 'instance' );
		@$instance->setAccessible( true );
		$instance->setValue( null, null );

		// Also reset Favorites singleton.
		$favReflection = new \ReflectionClass( Favorites::class );
		$favInstance   = $favReflection->getProperty( 'instance' );
		@$favInstance->setAccessible( true );
		$favInstance->setValue( null, null );

		// Mock WordPress constants.
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}
		if ( ! defined( 'COOKIEPATH' ) ) {
			define( 'COOKIEPATH', '/' );
		}
		if ( ! defined( 'COOKIE_DOMAIN' ) ) {
			define( 'COOKIE_DOMAIN', '' );
		}

		// Common mock setup.
		Functions\stubs( [
			'get_current_user_id' => 1,
			'is_user_logged_in'   => true,
			'get_post'            => function ( $id ) {
				if ( $id <= 0 ) {
					return null;
				}
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_status = 'publish';
				$post->post_author = 1;
				return $post;
			},
			'current_user_can'    => false,
			'is_ssl'              => false,
			'wp_json_encode'      => 'json_encode',
			'wp_parse_args'       => function ( $args, $defaults ) {
				return array_merge( $defaults, $args );
			},
			'wp_create_nonce'     => function ( $action ) {
				return 'test_nonce_' . $action;
			},
			'has_post_thumbnail'  => false,
			'esc_attr'            => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html'            => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'__'                  => function ( $text, $domain = 'default' ) {
				return $text;
			},
			'_n'                  => function ( $single, $plural, $number, $domain = 'default' ) {
				return $number === 1 ? $single : $plural;
			},
			'number_format_i18n'  => function ( $number ) {
				return number_format( (float) $number );
			},
		] );

		$this->toggle = FavoriteToggle::get_instance();
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = FavoriteToggle::get_instance();
		$instance2 = FavoriteToggle::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd_toggle_favorite', FavoriteToggle::AJAX_ACTION );
		$this->assertSame( 'apd_favorite_nonce', FavoriteToggle::NONCE_ACTION );
	}

	/**
	 * Test init registers AJAX handlers.
	 */
	public function test_init_registers_ajax_handlers(): void {
		$ajax_hooks = [];

		Functions\when( 'add_action' )->alias(
			function ( $tag, $callback ) use ( &$ajax_hooks ) {
				if ( str_starts_with( $tag, 'wp_ajax' ) ) {
					$ajax_hooks[] = $tag;
				}
			}
		);
		Functions\when( 'add_filter' )->justReturn( true );

		$this->toggle->init();

		$this->assertContains( 'wp_ajax_apd_toggle_favorite', $ajax_hooks );
		$this->assertContains( 'wp_ajax_nopriv_apd_toggle_favorite', $ajax_hooks );
	}

	/**
	 * Test init registers card button hooks.
	 */
	public function test_init_registers_card_button_hooks(): void {
		$card_hooks = [];

		Functions\when( 'add_action' )->alias(
			function ( $tag, $callback, $priority = 10 ) use ( &$card_hooks ) {
				if ( str_starts_with( $tag, 'apd_listing_card' ) || str_starts_with( $tag, 'apd_single_listing' ) ) {
					$card_hooks[] = $tag;
				}
			}
		);
		Functions\when( 'add_filter' )->justReturn( true );

		$this->toggle->init();

		$this->assertContains( 'apd_listing_card_image', $card_hooks );
		$this->assertContains( 'apd_listing_card_footer', $card_hooks );
		$this->assertContains( 'apd_single_listing_meta', $card_hooks );
	}

	/**
	 * Test get_button returns valid HTML structure.
	 */
	public function test_get_button_returns_valid_html(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 5 );

		$html = $this->toggle->get_button( $listing_id );

		// Check for essential attributes.
		$this->assertStringContainsString( 'class="apd-favorite-button', $html );
		$this->assertStringContainsString( 'data-listing-id="123"', $html );
		$this->assertStringContainsString( 'data-nonce="test_nonce_apd_favorite_nonce"', $html );
		$this->assertStringContainsString( 'aria-label="', $html );
		$this->assertStringContainsString( 'aria-pressed="', $html );
		$this->assertStringContainsString( '<button', $html );
		$this->assertStringContainsString( '</button>', $html );
	}

	/**
	 * Test button shows active state when favorited.
	 */
	public function test_button_shows_active_when_favorited(): void {
		$listing_id = 123;

		// Mock that this listing is favorited.
		Functions\when( 'get_user_meta' )->justReturn( [ 123 ] );
		Functions\when( 'get_post_meta' )->justReturn( 5 );

		$html = $this->toggle->get_button( $listing_id );

		$this->assertStringContainsString( 'apd-favorite-button--active', $html );
		$this->assertStringContainsString( 'aria-pressed="true"', $html );
		$this->assertStringContainsString( 'Remove from favorites', $html );
		$this->assertStringContainsString( 'apd-heart-icon--filled', $html );
	}

	/**
	 * Test button shows inactive state when not favorited.
	 */
	public function test_button_shows_inactive_when_not_favorited(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 5 );

		$html = $this->toggle->get_button( $listing_id );

		$this->assertStringNotContainsString( 'apd-favorite-button--active', $html );
		$this->assertStringContainsString( 'aria-pressed="false"', $html );
		$this->assertStringContainsString( 'Add to favorites', $html );
		$this->assertStringContainsString( 'apd-heart-icon--outline', $html );
	}

	/**
	 * Test button includes count when show_count is true.
	 */
	public function test_button_includes_count_when_enabled(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 42 );

		$html = $this->toggle->get_button( $listing_id, [ 'show_count' => true ] );

		$this->assertStringContainsString( 'apd-favorite-count', $html );
		$this->assertStringContainsString( 'data-count="42"', $html );
		$this->assertStringContainsString( '42', $html );
	}

	/**
	 * Test button excludes count when show_count is false.
	 */
	public function test_button_excludes_count_when_disabled(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 42 );

		$html = $this->toggle->get_button( $listing_id, [ 'show_count' => false ] );

		$this->assertStringNotContainsString( 'apd-favorite-count', $html );
	}

	/**
	 * Test button size variants.
	 */
	public function test_button_size_variants(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		$small_html = $this->toggle->get_button( $listing_id, [ 'size' => 'small' ] );
		$this->assertStringContainsString( 'apd-favorite-button--small', $small_html );

		$medium_html = $this->toggle->get_button( $listing_id, [ 'size' => 'medium' ] );
		$this->assertStringContainsString( 'apd-favorite-button--medium', $medium_html );

		$large_html = $this->toggle->get_button( $listing_id, [ 'size' => 'large' ] );
		$this->assertStringContainsString( 'apd-favorite-button--large', $large_html );
	}

	/**
	 * Test invalid size defaults to medium.
	 */
	public function test_invalid_size_defaults_to_medium(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		$html = $this->toggle->get_button( $listing_id, [ 'size' => 'invalid' ] );

		$this->assertStringContainsString( 'apd-favorite-button--medium', $html );
	}

	/**
	 * Test custom class is added.
	 */
	public function test_custom_class_is_added(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		$html = $this->toggle->get_button( $listing_id, [ 'class' => 'my-custom-class' ] );

		$this->assertStringContainsString( 'my-custom-class', $html );
	}

	/**
	 * Test render_button outputs the button HTML.
	 */
	public function test_render_button_outputs_html(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		ob_start();
		$this->toggle->render_button( $listing_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<button', $output );
		$this->assertStringContainsString( 'apd-favorite-button', $output );
	}

	/**
	 * Test render_card_button only renders when thumbnail exists.
	 */
	public function test_render_card_button_only_with_thumbnail(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		// Without thumbnail.
		Functions\when( 'has_post_thumbnail' )->justReturn( false );

		ob_start();
		$this->toggle->render_card_button( $listing_id );
		$output_no_thumb = ob_get_clean();

		$this->assertEmpty( $output_no_thumb );

		// With thumbnail.
		Functions\when( 'has_post_thumbnail' )->justReturn( true );

		ob_start();
		$this->toggle->render_card_button( $listing_id );
		$output_with_thumb = ob_get_clean();

		$this->assertStringContainsString( 'apd-favorite-button--overlay', $output_with_thumb );
	}

	/**
	 * Test render_card_button_fallback only renders without thumbnail.
	 */
	public function test_render_card_button_fallback_only_without_thumbnail(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		// With thumbnail (fallback should not render).
		Functions\when( 'has_post_thumbnail' )->justReturn( true );

		ob_start();
		$this->toggle->render_card_button_fallback( $listing_id );
		$output_with_thumb = ob_get_clean();

		$this->assertEmpty( $output_with_thumb );

		// Without thumbnail (fallback should render).
		Functions\when( 'has_post_thumbnail' )->justReturn( false );

		ob_start();
		$this->toggle->render_card_button_fallback( $listing_id );
		$output_no_thumb = ob_get_clean();

		$this->assertStringContainsString( 'apd-favorite-button', $output_no_thumb );
	}

	/**
	 * Test add_script_data adds favorite-related data.
	 */
	public function test_add_script_data_adds_favorite_data(): void {
		$initial_data = [
			'ajaxUrl' => '/admin-ajax.php',
			'i18n'    => [],
		];

		$result = $this->toggle->add_script_data( $initial_data );

		// Check favorite-specific data.
		$this->assertArrayHasKey( 'favoriteNonce', $result );
		$this->assertArrayHasKey( 'favoriteAction', $result );
		$this->assertArrayHasKey( 'requiresLogin', $result );

		$this->assertSame( 'apd_toggle_favorite', $result['favoriteAction'] );

		// Check i18n strings.
		$this->assertArrayHasKey( 'addedToFavorites', $result['i18n'] );
		$this->assertArrayHasKey( 'removedFromFavorites', $result['i18n'] );
		$this->assertArrayHasKey( 'addToFavorites', $result['i18n'] );
		$this->assertArrayHasKey( 'removeFromFavorites', $result['i18n'] );
		$this->assertArrayHasKey( 'loginRequired', $result['i18n'] );
		$this->assertArrayHasKey( 'favoriteError', $result['i18n'] );
	}

	/**
	 * Test add_script_data preserves existing data.
	 */
	public function test_add_script_data_preserves_existing_data(): void {
		$initial_data = [
			'ajaxUrl'     => '/admin-ajax.php',
			'existingKey' => 'existingValue',
			'i18n'        => [
				'existingString' => 'Hello',
			],
		];

		$result = $this->toggle->add_script_data( $initial_data );

		$this->assertSame( '/admin-ajax.php', $result['ajaxUrl'] );
		$this->assertSame( 'existingValue', $result['existingKey'] );
		$this->assertSame( 'Hello', $result['i18n']['existingString'] );
	}

	/**
	 * Test add_script_data creates i18n array if not exists.
	 */
	public function test_add_script_data_creates_i18n_array(): void {
		$initial_data = [
			'ajaxUrl' => '/admin-ajax.php',
		];

		$result = $this->toggle->add_script_data( $initial_data );

		$this->assertIsArray( $result['i18n'] );
		$this->assertArrayHasKey( 'addToFavorites', $result['i18n'] );
	}

	/**
	 * Test button HTML is filterable.
	 */
	public function test_button_html_is_filterable(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );
		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $value, ...$args ) {
				if ( $tag === 'apd_favorite_button_html' ) {
					return '<custom-button>Custom</custom-button>';
				}
				if ( $tag === 'apd_favorite_button_classes' ) {
					return $value;
				}
				return $value;
			}
		);

		$html = $this->toggle->get_button( $listing_id );

		$this->assertSame( '<custom-button>Custom</custom-button>', $html );
	}

	/**
	 * Test button classes are filterable.
	 */
	public function test_button_classes_are_filterable(): void {
		$listing_id    = 123;
		$filtered_classes = [];

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );
		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $value, ...$args ) use ( &$filtered_classes ) {
				if ( $tag === 'apd_favorite_button_classes' ) {
					$filtered_classes = $value;
					$value[]          = 'filtered-class';
					return $value;
				}
				return $value;
			}
		);

		$html = $this->toggle->get_button( $listing_id );

		$this->assertContains( 'apd-favorite-button', $filtered_classes );
		$this->assertContains( 'apd-favorite-button--medium', $filtered_classes );
		$this->assertStringContainsString( 'filtered-class', $html );
	}

	/**
	 * Test zero count shows empty count display.
	 */
	public function test_zero_count_shows_empty_display(): void {
		$listing_id = 123;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( 0 );

		$html = $this->toggle->get_button( $listing_id, [ 'show_count' => true ] );

		$this->assertStringContainsString( 'data-count="0"', $html );
		// The count span should be present but empty (not display "0").
		$this->assertMatchesRegularExpression( '/<span class="apd-favorite-count" data-count="0"><\/span>/', $html );
	}
}
