<?php
/**
 * Tests for Courses API.
 *
 * @package LifterLMS_Rest/Tests
 *
 * @group REST
 * @group rest_api_keys
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_API_Keys_Controller extends LLMS_REST_Unit_Test_Case_Server {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/api-keys';

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {

		parent::setUp();
		$this->user_allowed = $this->factory->user->create( array( 'role' => 'administrator', ) );
		$this->user_forbidden = $this->factory->user->create( array( 'role' => 'subscriber', ) );
		$this->endpoint = new LLMS_REST_API_Keys_Controller();

	}

	/**
	 * Test route registration.
	 *
	 * @since [version]
	 */
	public function test_register_routes() {

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->route, $routes );
		$this->assertArrayHasKey( $this->route . '/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( $this->route . '/self', $routes );

	}

	public function test_get_item_schema() {

		$schema = $this->endpoint->get_item_schema();
		$this->assertTrue( array_key_exists( '$schema', $schema ) );
		$this->assertTrue( array_key_exists( 'title', $schema ) );
		$this->assertTrue( array_key_exists( 'type', $schema ) );
		$this->assertTrue( array_key_exists( 'properties', $schema ) );
		$this->assertTrue( array_key_exists( 'description', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'permissions', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'user_id', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'truncated_key', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'last_access', $schema['properties'] ) );

	}

	/**
	 * Test error responses for creating a key
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_create_item_errors() {

		// Empty body.
		$request = new WP_REST_Request( 'POST', $this->route );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseCodeEquals( 'rest_missing_callback_param', $response );

		// Unauthorized.
		$args = array(
			'description' => 'Mock Description',
			'user_id' => $this->factory->user->create(),
			'permissions' => 'read',
		);
		$request->set_body_params( $args );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatusEquals( 401, $response );
		$this->assertResponseCodeEquals( 'llms_rest_unauthorized_request', $response );

		// Forbidden.
		wp_set_current_user( $this->user_forbidden );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatusEquals( 403, $response );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $response );

		// Invalid submitted user_id
		wp_set_current_user( $this->user_allowed );
		$args['user_id'] = 9032423402934;
		$request->set_body_params( $args );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseCodeEquals( 'rest_invalid_param', $response );

	}

	/**
	 * Test creation of a new key success.
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_create_item_success() {

		wp_set_current_user( $this->user_allowed );
		$request = new WP_REST_Request( 'POST', $this->route );
		$args = array(
			'description' => 'Mock Description',
			'user_id' => $this->factory->user->create(),
			'permissions' => 'read',
		);
		$request->set_body_params( $args );
		$response = $this->server->dispatch( $request );

		$this->assertResponseStatusEquals( 201, $response );

		$res_data = $response->get_data();

		$this->assertEquals( $args['description'], $res_data['description'] );
		$this->assertEquals( $args['user_id'], $res_data['user_id'] );
		$this->assertEquals( $args['permissions'], $res_data['permissions'] );
		$this->assertTrue( array_key_exists( 'consumer_secret', $res_data ) );
		$this->assertTrue( array_key_exists( 'last_access', $res_data ) );
		$this->assertEquals( $res_data['truncated_key'], substr( $res_data['consumer_key'], -7 ) );

	}

	/**
	 * test the delete_item() method.
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_delete_item() {

	}

	/**
	 * test the get_item() method.
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_get_item() {

	}

	/**
	 * test the get_items() method.
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_get_items() {

	}

	/**
	 * test the update_item() method.
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_update_item() {

	}

}
