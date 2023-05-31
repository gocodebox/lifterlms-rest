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
 * @since 1.0.0-beta.1
 */
class LLMS_REST_Test_Instructors_Controllers extends LLMS_REST_Unit_Test_Case_Users {

	/**
	 * Route.
	 *
	 * @var string
	 */
	protected $route = '/llms/v1/instructors';

	/**
	 * Setup the test case.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.27 Define `$this->object_type` as 'instructor'.
	 *
	 * @return void
	 */
	public function set_up() {

		parent::set_up();
		$this->user_admin = $this->factory->user->create( array( 'role' => 'administrator', ) );
		$this->user_subscriber = $this->factory->user->create( array( 'role' => 'subscriber', ) );
		$this->user_instructor = $this->factory->user->create( array( 'role' => 'instructor', ) );
		$this->user_assistant = $this->factory->user->create( array( 'role' => 'instructors_assistant', ) );
		$asst = llms_get_instructor( $this->user_assistant )->add_parent( $this->user_instructor );
		$this->endpoint = new LLMS_REST_Instructors_Controller();
		$this->object_type = 'instructor';

	}

	/**
	 * Test route registration
	 *
	 * @since 1.0.0-beta.1
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
	 * @since 1.0.0-beta.1
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
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.27 Added schema `meta` property.
	 *
	 * @return void
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
			'meta',
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
	 * @since 1.0.0-beta.1
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

	// public function test_get_items_auth() {}
	// public function test_get_items_exclude() {}
	// public function test_get_items_include() {}
	// public function test_get_items_orderby_id() {}
	// public function test_get_items_orderby_email() {}
	// public function test_get_items_orderby_name() {}
	// public function test_get_items_orderby_registered_date() {}
	// public function test_get_items_pagination() {}

	/**
	 * Test list instructors pagination.
	 *
	 * @since 1.0.0-beta.7
	 */
	public function test_get_items_pagination() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->users}" );

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $admin_id );
		// other 24 users except the admin who's an instructor too.
		$ids = $this->factory->user->create_many( 24, array( 'role' => 'instructor' ) );
		$this->pagination_test( $this->route, $admin_id );

	}

	// public function test_get_items_filter_by_posts() {}
	// public function test_get_items_filter_by_roles() {}

	public function test_create_item_missing_required() {

		$res = $this->perform_mock_request( 'POST', $this->route );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_missing_callback_param', $res );
		$this->assertResponseMessageEquals( 'Missing parameter(s): email', $res );

	}

	public function test_create_item_auth_errors() {

		// Unauthorized user.
		wp_set_current_user( null );
		$res = $this->perform_mock_request( 'POST', $this->route, array( 'email' => 'mock@mock.mock' ) );
		$this->assertResponseStatusEquals( 401, $res );
		$this->assertResponseCodeEquals( 'llms_rest_unauthorized_request', $res );

		// Forbidden user.
		wp_set_current_user( $this->user_subscriber );
		$res = $this->perform_mock_request( 'POST', $this->route, array( 'email' => 'mock@mock.mock' ) );
		$this->assertResponseStatusEquals( 403, $res );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $res );

	}

	public function test_create_item_as_instructor() {

		// Instructor can only create assistants.
		wp_set_current_user( $this->user_instructor );
		$res = $this->perform_mock_request( 'POST', $this->route, array( 'email' => 'mock@mock.mock', ) );
		$this->assertResponseStatusEquals( 403, $res );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $res );
		$this->assertResponseMessageEquals( 'You are not allowed to give users this role.', $res );

		// Okay.
		$res = $this->perform_mock_request( 'POST', $this->route, array(
			'email' => 'mock@mock.mock',
			'roles' => 'instructors_assistant',
		) );
		$this->assertResponseStatusEquals( 201, $res );

	}

	public function test_create_item_success() {

		wp_set_current_user( $this->user_admin );
		$args = array(
			'email' => 'jamief@mockinstructor.tld',
			'first_name' => 'Jamie',
			'last_name' => 'Fitzgerald',
			'name' => 'Jamie Fitzgerald',
			'nickname' => 'JamieF1932',
			'username' => 'jamief',
			'url' => 'http://jamief.geocities.com',
			'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
			'billing_address_1' => '123 Some Street',
			'billing_address_2' => 'Suite A',
			'billing_city' => 'Some City',
			'billing_state' => 'NH',
			'billing_postcode' => '32319',
			'billing_country' => 'USA',
		);
		$res = $this->perform_mock_request( 'POST', $this->route, $args );

		$this->assertResponseStatusEquals( 201, $res );

		$data = $res->get_data();
		foreach ( $args as $key => $expected ) {
			$this->assertEquals( $expected, $data[ $key ] );
		}

		$this->assertEquals( array( 'instructor' ), $data['roles'] );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'avatar_urls', $data );
		$this->assertArrayHasKey( 'registered_date', $data );

		$this->assertArrayHasKey( 'Location', $res->get_headers() );

	}

	// public function test_get_item_auth() {}
	// public function test_get_item_not_found() {}
	// public function test_get_item_success() {}

	// public function test_update_item_auth() {}
	// public function test_update_item_errors() {}
	// public function test_update_item_success() {}

	// public function test_delete_item_auth() {}
	// public function test_delete_item_success() {}

	/**
	 * Teardown test
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function tear_down() {

		parent::tear_down();

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->users}" );

	}

	/**
	 * Create user, returns the user ID.
	 *
	 * @since 1.0.0-beta.27
	 *
	 * @return int
	 */
	protected function create_user() {
		return $this->factory->user->create( array( 'role' => 'instructor' ) );
	}

}
