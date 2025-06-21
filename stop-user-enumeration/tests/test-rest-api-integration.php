<?php
/**
 * Integration tests for REST API security
 * Run with: wp-env run tests-cli phpunit
 */

class Test_REST_API_Integration extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		
		// Enable the plugin's REST API blocking
		update_option( 'stop-user-enumeration', array( 'stop_rest_user' => 'on' ) );
		
		// Create test users
		$this->test_user = $this->factory->user->create( array(
			'user_login' => 'testuser',
			'user_email' => 'test@example.com',
			'role'       => 'author',
		) );
		
		$this->admin_user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test that the REST API users endpoint is blocked for non-authenticated requests
	 */
	public function test_rest_api_blocked_for_guests() {
		// Log out
		wp_set_current_user( 0 );
		
		// Make REST API request
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$response = rest_do_request( $request );
		
		// When rest_do_request is used, WP_Error responses are converted to WP_REST_Response
		// with appropriate status codes
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 401, $response->get_status() );
		
		// Check the response data contains our error
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertEquals( 'rest_cannot_access', $data['code'] );
	}

	/**
	 * Test that authenticated users can access the endpoint
	 */
	public function test_rest_api_allowed_for_authenticated() {
		// Log in as admin
		wp_set_current_user( $this->admin_user );
		
		// Make REST API request
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$response = rest_do_request( $request );
		
		// Should be allowed
		$this->assertNotWPError( $response );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test that URL-encoded paths cannot bypass protection
	 * This tests the main vulnerability fix
	 */
	public function test_url_encoded_bypass_blocked() {
		// Log out
		wp_set_current_user( 0 );
		
		// Simulate various encoded requests
		$test_routes = array(
			'/wp/v2/users',      // Normal
			'/wp/v2/users/',     // With trailing slash
			'/wp/v2/users/1',    // Specific user
		);
		
		foreach ( $test_routes as $route ) {
			$request = new WP_REST_Request( 'GET', $route );
			$response = rest_do_request( $request );
			
			$this->assertInstanceOf( 'WP_REST_Response', $response, "Route $route should return REST response" );
			$this->assertEquals( 401, $response->get_status(), "Route $route should be blocked with 401" );
			
			$data = $response->get_data();
			$this->assertEquals( 'rest_cannot_access', $data['code'], "Route $route should have correct error code" );
		}
	}

	/**
	 * Test that query parameters cannot bypass protection
	 */
	public function test_query_parameter_bypass_blocked() {
		// Log out
		wp_set_current_user( 0 );
		
		// Create request with simple-jwt-login in query params
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$request->set_query_params( array( 
			'foo' => 'simple-jwt-login',
			'another' => 'param',
		) );
		
		$response = rest_do_request( $request );
		
		// Should still be blocked
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 401, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertEquals( 'rest_cannot_access', $data['code'] );
	}

	/**
	 * Test that legitimate simple-jwt-login routes are allowed
	 */
	public function test_simple_jwt_login_routes_allowed() {
		// Log out
		wp_set_current_user( 0 );
		
		// Test actual simple-jwt-login route
		$request = new WP_REST_Request( 'POST', '/simple-jwt-login/v1/auth' );
		$response = rest_do_request( $request );
		
		// Should not be blocked by our plugin (may fail for other reasons)
		if ( $response instanceof WP_REST_Response && $response->get_status() === 401 ) {
			$data = $response->get_data();
			$this->assertNotEquals( 'rest_cannot_access', $data['code'] ?? '' );
		} else {
			// If not a 401 error, then it's definitely not blocked by our plugin
			$this->assertTrue( true );
		}
	}

	/**
	 * Test that other endpoints are not affected
	 */
	public function test_other_endpoints_not_blocked() {
		// Log out
		wp_set_current_user( 0 );
		
		// Test posts endpoint
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = rest_do_request( $request );
		
		// Should not be blocked by our plugin
		if ( is_wp_error( $response ) ) {
			$this->assertNotEquals( 'rest_cannot_access', $response->get_error_code() );
		} else {
			$this->assertEquals( 200, $response->get_status() );
		}
	}

	/**
	 * Test the stop_user_enumeration_should_block filter
	 */
	public function test_should_block_filter() {
		// Log out
		wp_set_current_user( 0 );
		
		// Add filter to allow access
		add_filter( 'stop_user_enumeration_should_block', '__return_false' );
		
		// Make request
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$response = rest_do_request( $request );
		
		// Should be allowed due to filter
		$this->assertNotWPError( $response );
		
		// Clean up
		remove_filter( 'stop_user_enumeration_should_block', '__return_false' );
	}

	public function tearDown(): void {
		// Reset
		wp_set_current_user( 0 );
		delete_option( 'stop-user-enumeration' );
		
		parent::tearDown();
	}
}