<?php
/**
 * Test the REST controller for the instructors resource
 *
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group REST
 * @group rest_instructors
 * @group rest_users
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Instructors_Controllers extends LLMS_REST_Unit_Test_Case_Server {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/instructors';

	/**
	 * Setup the test case.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function setUp() {

		parent::setUp();
		$this->user_allowed = $this->factory->user->create( array( 'role' => 'administrator', ) );
		$this->user_forbidden = $this->factory->user->create( array( 'role' => 'subscriber', ) );
		$this->endpoint = new LLMS_REST_Instructors_Controller();

	}

	/**
	 * Test route registration
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_register_routes() {

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->route, $routes );
		$this->assertArrayHasKey( $this->route . '/(?P<id>[\d]+)', $routes );

	}

	/**
	 * Ensure all collection parameters have been registered.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_collection_params() {

		$params = $this->endpoint->get_collection_params();
		$this->assertArrayHasKey( 'context', $params );
		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertArrayHasKey( 'order', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'include', $params );
		$this->assertArrayHasKey( 'roles', $params );
		$this->assertArrayHasKey( 'post_in', $params );
		$this->assertArrayHasKey( 'post_not_in', $params );

	}

	/**
	 * Test the item schema.
	 *
	 * @since [version]
	 *
	 * @return [type]
	 */
	public function test_get_item_schema() {

		$schema = $this->endpoint->get_item_schema();

		$this->assertEquals( 'instructor', $schema['title'] );

		$props = array(
			'id',
			'username',
			'name',
			'first_name',
			'last_name',
			'email',
			'url',
			'description',
			'nickname',
			'registered_date',
			'roles',
			'password',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_country',
			'avatar_urls',
		);

		$this->assertEquals( $props, array_keys( $schema['properties'] ) );

		$this->assertEquals( array( 'instructor' ), $schema['properties']['roles']['default'] );

		$schema = $this->endpoint->get_item_schema();
		update_option( 'show_avatars', '' );
		$this->assertFalse( array_key_exists( 'avatar_urls', array_keys( $schema['properties'] ) ) );

		update_option( 'show_avatars', 1 );

	}

	/**
	 * Test the get_object method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_object() {

		$id = $this->factory->user->create( array( 'role' => 'instructor' ) );

		// Good.
		$instructor = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'get_object', array( $id ) );
		$this->assertTrue( is_a( $instructor, 'LLMS_Instructor' ) );

		// 404.
		$error_404 = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'get_object', array( $id + 1 ) );
		$this->assertIsWPError( $error_404 );
		$this->assertWPErrorCodeEquals( 'llms_rest_not_found', $error_404 );

	}

}
