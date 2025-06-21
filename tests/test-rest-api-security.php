<?php
/**
 * Test REST API Security
 *
 * @package Stop_User_Enumeration
 */

/**
 * REST API Security Test Case
 */
class Test_REST_API_Security extends WP_UnitTestCase {

	/**
	 * Frontend instance
	 *
	 * @var \Stop_User_Enumeration\FrontEnd\FrontEnd
	 */
	private $frontend;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Enable REST API blocking
		update_option( 'stop-user-enumeration', array( 'stop_rest_user' => 'on' ) );
		
		// Create frontend instance
		$this->frontend = new \Stop_User_Enumeration\FrontEnd\FrontEnd( 'stop-user-enumeration', '1.7.3' );
		
		// Create a test user
		$this->factory->user->create( array(
			'user_login' => 'testuser',
			'user_email' => 'test@example.com',
			'role'       => 'subscriber',
		) );
	}

	/**
	 * Test that normal REST API users endpoint is blocked for non-authenticated users
	 */
	public function test_rest_api_users_endpoint_blocked() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Create REST request for users endpoint
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$server = new WP_REST_Server();
		
		// Test the filter
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		
		// Assert that access is denied
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'rest_cannot_access', $result->get_error_code() );
		$this->assertEquals( 'Only authenticated users can access the User endpoint REST API.', $result->get_error_message() );
	}

	/**
	 * Test that URL-encoded REST API path cannot bypass protection
	 */
	public function test_url_encoded_path_cannot_bypass() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Test various encoded paths that should still be blocked
		$encoded_paths = array(
			'/wp/v2/users',  // Normal path
			'/wp/v2/users/', // With trailing slash
			'/wp/v2/users/1', // Specific user
		);
		
		foreach ( $encoded_paths as $path ) {
			$request = new WP_REST_Request( 'GET', $path );
			$server = new WP_REST_Server();
			
			$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
			
			// All these should be blocked
			$this->assertInstanceOf( 'WP_Error', $result, "Path $path should be blocked" );
			$this->assertEquals( 'rest_cannot_access', $result->get_error_code() );
		}
	}

	/**
	 * Test that simple-jwt-login in query parameters cannot bypass protection
	 */
	public function test_simple_jwt_login_query_parameter_cannot_bypass() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Create REST request with simple-jwt-login in query params (the vulnerability)
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$request->set_query_params( array( 'foo' => 'simple-jwt-login' ) );
		$server = new WP_REST_Server();
		
		// Test the filter
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		
		// Assert that access is STILL denied (query param should not bypass)
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'rest_cannot_access', $result->get_error_code() );
	}

	/**
	 * Test that legitimate simple-jwt-login routes are allowed
	 */
	public function test_legitimate_simple_jwt_login_routes_allowed() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Create REST request for actual simple-jwt-login route
		$request = new WP_REST_Request( 'POST', '/simple-jwt-login/v1/auth' );
		$server = new WP_REST_Server();
		
		// Test the filter
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		
		// Assert that access is allowed (returns null, not WP_Error)
		$this->assertNull( $result );
	}

	/**
	 * Test that authenticated users can access the users endpoint
	 */
	public function test_authenticated_users_can_access() {
		// Log in as admin
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		
		// Create REST request
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$server = new WP_REST_Server();
		
		// Test the filter
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		
		// Assert that access is allowed (returns null, not WP_Error)
		$this->assertNull( $result );
	}

	/**
	 * Test that non-user endpoints are not affected
	 */
	public function test_non_user_endpoints_not_affected() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Test various non-user endpoints
		$allowed_endpoints = array(
			'/wp/v2/posts',
			'/wp/v2/pages',
			'/wp/v2/categories',
			'/wp/v2/tags',
			'/custom/v1/endpoint',
		);
		
		foreach ( $allowed_endpoints as $endpoint ) {
			$request = new WP_REST_Request( 'GET', $endpoint );
			$server = new WP_REST_Server();
			
			$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
			
			// These should not be blocked (returns null)
			$this->assertNull( $result, "Endpoint $endpoint should not be blocked" );
		}
	}

	/**
	 * Test the REST stop match filter
	 */
	public function test_rest_stop_match_filter() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Change the pattern via filter
		add_filter( 'stop_user_enumeration_rest_stop_match', function() {
			return '#^/wp/v[0-9]+/custom-users#i';
		});
		
		// Test that original users endpoint is now allowed
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$server = new WP_REST_Server();
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		$this->assertNull( $result );
		
		// Test that custom pattern is blocked
		$request = new WP_REST_Request( 'GET', '/wp/v2/custom-users' );
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		$this->assertInstanceOf( 'WP_Error', $result );
		
		// Remove filter
		remove_all_filters( 'stop_user_enumeration_rest_stop_match' );
	}

	/**
	 * Test the should_block filter
	 */
	public function test_should_block_filter() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Add filter to allow specific IPs
		add_filter( 'stop_user_enumeration_should_block', function( $should_block, $ip ) {
			// Allow localhost
			if ( $ip === '127.0.0.1' ) {
				return false;
			}
			return $should_block;
		}, 10, 2 );
		
		// Mock the IP to be localhost
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		
		// Create REST request
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$server = new WP_REST_Server();
		
		// Test the filter
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		
		// Should be allowed due to filter
		$this->assertNull( $result );
		
		// Clean up
		unset( $_SERVER['REMOTE_ADDR'] );
		remove_all_filters( 'stop_user_enumeration_should_block' );
	}

	/**
	 * Test edge cases with empty or malformed routes
	 */
	public function test_edge_cases() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		$server = new WP_REST_Server();
		
		// Test with empty route
		$request = new WP_REST_Request( 'GET', '' );
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		$this->assertNull( $result );
		
		// Test with null route
		$request = new WP_REST_Request( 'GET', null );
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		$this->assertNull( $result );
		
		// Test with route containing users but not matching pattern
		$request = new WP_REST_Request( 'GET', '/custom/users/endpoint' );
		$result = $this->frontend->only_allow_logged_in_rest_access_to_users( null, $server, $request );
		$this->assertNull( $result );
	}

	/**
	 * Integration test simulating the actual WordPress REST API flow
	 */
	public function test_integration_with_rest_api() {
		// Ensure user is logged out
		wp_set_current_user( 0 );
		
		// Hook our method to the REST API
		add_filter( 'rest_pre_dispatch', array( $this->frontend, 'only_allow_logged_in_rest_access_to_users' ), 10, 3 );
		
		// Create a REST server
		$server = new WP_REST_Server();
		do_action( 'rest_api_init', $server );
		
		// Test blocked request
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$response = $server->dispatch( $request );
		
		// Should get an error response
		$this->assertEquals( 401, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_cannot_access', $data['code'] );
		
		// Clean up
		remove_filter( 'rest_pre_dispatch', array( $this->frontend, 'only_allow_logged_in_rest_access_to_users' ), 10 );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Reset options
		delete_option( 'stop-user-enumeration' );
		
		// Log out
		wp_set_current_user( 0 );
		
		parent::tearDown();
	}
}