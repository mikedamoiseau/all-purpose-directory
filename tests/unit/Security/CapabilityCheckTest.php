<?php
/**
 * Tests for capability and permission checks.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Brain\Monkey\Functions;
use APD\Api\RestController;

/**
 * CapabilityCheckTest verifies authorization checks on all protected actions.
 */
class CapabilityCheckTest extends SecurityTestCase {

    /**
     * Create a mock WP_REST_Request.
     *
     * @param array $params Request parameters.
     * @return \WP_REST_Request
     */
    private function create_mock_request(array $params = []): \WP_REST_Request {
        $request = $this->createMock(\WP_REST_Request::class);
        $request->method('get_param')->willReturnCallback(function ($key) use ($params) {
            return $params[$key] ?? null;
        });
        return $request;
    }

    /**
     * Test REST API public permission callback always returns true.
     */
    public function test_rest_api_public_permission(): void {
        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_public($request);

        $this->assertTrue($result, 'Public permission should always return true');
    }

    /**
     * Test REST API authenticated permission requires login.
     */
    public function test_rest_api_authenticated_permission_requires_login(): void {
        Functions\when('get_current_user_id')->justReturn(0);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_authenticated($request);

        $this->assertInstanceOf(\WP_Error::class, $result, 'Should return WP_Error for guests');
        $this->assertEquals('rest_not_logged_in', $result->get_error_code());
    }

    /**
     * Test REST API authenticated permission passes for logged-in users.
     */
    public function test_rest_api_authenticated_permission_passes_for_logged_in(): void {
        Functions\when('get_current_user_id')->justReturn(1);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_authenticated($request);

        $this->assertTrue($result, 'Authenticated permission should pass for logged-in users');
    }

    /**
     * Test REST API admin permission requires manage_options capability.
     */
    public function test_rest_api_admin_permission_requires_manage_options(): void {
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('current_user_can')->justReturn(false);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_admin($request);

        $this->assertInstanceOf(\WP_Error::class, $result, 'Admin permission should fail without manage_options');
        $this->assertEquals('rest_forbidden', $result->get_error_code());
    }

    /**
     * Test REST API create listing permission fails for guests.
     */
    public function test_rest_api_create_listing_permission_fails_for_guest(): void {
        Functions\when('get_current_user_id')->justReturn(0);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_create_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_not_logged_in', $result->get_error_code());
    }

    /**
     * Test REST API create listing permission fails without capability.
     */
    public function test_rest_api_create_listing_permission_fails_without_capability(): void {
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('current_user_can')->justReturn(false);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_create_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result, 'Create listing should fail without capability');
    }

    /**
     * Test REST API manage listings permission fails without capability.
     */
    public function test_rest_api_manage_listings_permission_fails_without_capability(): void {
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('current_user_can')->justReturn(false);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();
        $result = $controller->permission_manage_listings($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * Test listing capabilities are properly defined.
     */
    public function test_listing_capabilities_are_defined(): void {
        $reflection = new \ReflectionClass(\APD\Core\Capabilities::class);

        $this->assertTrue(
            $reflection->hasMethod('get_listing_caps'),
            'Capabilities class should define get_listing_caps method'
        );
    }

    /**
     * Test permission denied for non-owner editing listing.
     */
    public function test_non_owner_cannot_edit_listing(): void {
        $listing_id = 123;
        $owner_id = 100;
        $other_user_id = 200;

        Functions\when('get_current_user_id')->justReturn($other_user_id);

        $post = new \stdClass();
        $post->post_author = $owner_id;
        $post->post_type = 'apd_listing';
        Functions\when('get_post')->justReturn($post);
        Functions\when('current_user_can')->justReturn(false);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => $listing_id]);
        $result = $controller->permission_edit_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result, 'Non-owner should not be able to edit');
        $this->assertEquals('rest_forbidden', $result->get_error_code());
    }

    /**
     * Test guest cannot access authenticated endpoints.
     */
    public function test_guest_cannot_access_authenticated_endpoints(): void {
        Functions\when('get_current_user_id')->justReturn(0);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();

        $result = $controller->permission_authenticated($request);
        $this->assertInstanceOf(\WP_Error::class, $result, 'Authenticated should return error for guests');

        $result = $controller->permission_create_listing($request);
        $this->assertInstanceOf(\WP_Error::class, $result, 'Create listing should return error for guests');
    }

    /**
     * Test REST API permission methods exist.
     */
    public function test_rest_api_permission_methods_exist(): void {
        $controller = RestController::get_instance();
        $reflection = new \ReflectionClass($controller);

        $required_methods = [
            'permission_public',
            'permission_authenticated',
            'permission_create_listing',
            'permission_edit_listing',
            'permission_delete_listing',
            'permission_admin',
            'permission_manage_listings',
        ];

        foreach ($required_methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "RestController should have {$method} method"
            );
        }
    }

    /**
     * Test edit listing permission fails for invalid listing ID.
     */
    public function test_edit_listing_permission_fails_for_invalid_id(): void {
        Functions\when('get_current_user_id')->justReturn(1);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => 0]);
        $result = $controller->permission_edit_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_invalid_param', $result->get_error_code());
    }

    /**
     * Test edit listing permission fails for non-existent listing.
     */
    public function test_edit_listing_permission_fails_for_non_existent_listing(): void {
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_post')->justReturn(null);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => 999]);
        $result = $controller->permission_edit_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_listing_not_found', $result->get_error_code());
    }

    /**
     * Test edit listing permission fails for wrong post type.
     */
    public function test_edit_listing_permission_fails_for_wrong_post_type(): void {
        Functions\when('get_current_user_id')->justReturn(1);

        $post = new \stdClass();
        $post->post_type = 'post';
        Functions\when('get_post')->justReturn($post);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => 123]);
        $result = $controller->permission_edit_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_listing_not_found', $result->get_error_code());
    }

    /**
     * Test delete listing permission fails for non-owner.
     */
    public function test_delete_listing_permission_fails_for_non_owner(): void {
        $listing_id = 123;
        $owner_id = 100;
        $other_user_id = 200;

        Functions\when('get_current_user_id')->justReturn($other_user_id);

        $post = new \stdClass();
        $post->post_author = $owner_id;
        $post->post_type = 'apd_listing';
        Functions\when('get_post')->justReturn($post);
        Functions\when('current_user_can')->justReturn(false);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => $listing_id]);
        $result = $controller->permission_delete_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
    }

    /**
     * Test delete listing permission fails for guest.
     */
    public function test_delete_listing_permission_fails_for_guest(): void {
        Functions\when('get_current_user_id')->justReturn(0);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => 123]);
        $result = $controller->permission_delete_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_not_logged_in', $result->get_error_code());
    }

    /**
     * Test edit listing permission fails for guest.
     */
    public function test_edit_listing_permission_fails_for_guest(): void {
        Functions\when('get_current_user_id')->justReturn(0);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => 123]);
        $result = $controller->permission_edit_listing($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('rest_not_logged_in', $result->get_error_code());
    }

    /**
     * Test capability checks verify correct error codes.
     */
    public function test_permission_error_codes_are_correct(): void {
        Functions\when('get_current_user_id')->justReturn(0);

        $controller = RestController::get_instance();
        $request = $this->create_mock_request();

        // Guest errors should have 401 status
        $result = $controller->permission_authenticated($request);
        $this->assertInstanceOf(\WP_Error::class, $result);
        $error_data = $result->get_error_data('rest_not_logged_in');
        $this->assertEquals(401, $error_data['status'], 'Guest error should have 401 status');
    }

    /**
     * Test that user_can capability check is used for post-specific checks.
     */
    public function test_edit_permission_checks_edit_post_capability(): void {
        $listing_id = 123;

        Functions\when('get_current_user_id')->justReturn(1);

        $post = new \stdClass();
        $post->post_author = 1;
        $post->post_type = 'apd_listing';
        Functions\when('get_post')->justReturn($post);

        // Track if current_user_can was called with edit_post
        $cap_checked = false;
        Functions\when('current_user_can')->alias(function ($cap, ...$args) use (&$cap_checked, $listing_id) {
            if ($cap === 'edit_post' && isset($args[0]) && $args[0] === $listing_id) {
                $cap_checked = true;
            }
            return false;
        });

        $controller = RestController::get_instance();
        $request = $this->create_mock_request(['id' => $listing_id]);
        $controller->permission_edit_listing($request);

        $this->assertTrue($cap_checked, 'Should check edit_post capability with listing ID');
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        parent::tearDown();
    }
}
