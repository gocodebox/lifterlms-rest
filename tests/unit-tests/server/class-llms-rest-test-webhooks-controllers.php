<?php
/**
 * Tests for Student Progress controller.
 *
 * @package LifterLMS_Rest/Tests
 *
 * @group REST
 * @group webhooks
 * @group rest_webhooks
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Webhooks_Controller extends LLMS_REST_Unit_Test_Case_Server {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/webhooks';

	private function assertIsAWebhook( $data ) {

		$keys = array( 'id', 'name', 'status', 'topic', 'delivery_url', 'secret', 'created', 'updated', 'resource', 'event', 'hooks' );

		// Don't worry about links from a list request.
		unset( $data['_links'] );

		// All keys.
		$this->assertEquals( $keys, array_keys( $data ) );

		// Correct formats.
		$this->assertTrue( is_int( $data['id'] ) );
		$this->assertTrue( is_string( $data['name'] ) );
		$this->assertTrue( is_string( $data['status'] ) );
		$this->assertTrue( is_string( $data['delivery_url'] ) );
		$this->assertTrue( is_string( $data['secret'] ) );
		$this->assertTrue( is_int( rest_parse_date( $data['created'] ) ) );
		$this->assertTrue( is_int( rest_parse_date( $data['updated'] ) ) );
		$this->assertEquals( $data['resource'] . '.' . $data['event'], $data['topic'] );
		$this->assertTrue( is_array( $data['hooks'] ) );

	}

	/**
	 * Setup our test server, endpoints, and user info.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function setUp() {

		parent::setUp();

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}lifterlms_webhooks" );

		$this->endpoint = new LLMS_REST_Webhooks_Controller();

		$this->user_allowed = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->user_forbidden = $this->factory->user->create( array( 'role' => 'instructor' ) );

	}

	/**
	 * Test route registration.
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
	 * Error if webhook creation missing required parameters.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_missing_required() {

		$res = $this->perform_mock_request( 'POST', $this->route );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_missing_callback_param', $res );
		$this->assertResponseMessageEquals( 'Missing parameter(s): topic, delivery_url', $res );

	}

	/**
	 * Error creating webhook with invalid topic
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_invalid_topic() {

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'POST', $this->route, $this->get_hook_args( array(
			'topic' => 'course.fake',
		) ) );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_invalid_param', $res );

	}

	/**
	 * Error creating webhook with invalid status
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_invalid_status() {

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'POST', $this->route, $this->get_hook_args( array(
			'status' => 'fake',
		) ) );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_invalid_param', $res );

	}

	/**
	 * Error for invalid status
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_bad_url() {

		remove_filter( 'llms_rest_webhook_pre_ping', '__return_true' );

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'POST', $this->route, $this->get_hook_args() );
		$this->assertResponseStatusEquals( 500, $res );
		$this->assertResponseCodeEquals( 'llms_rest_webhook_ping_unreachable', $res );

		add_filter( 'llms_rest_webhook_pre_ping', '__return_true' );

	}

	/**
	 * Error creating webhook with an id
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_no_id_allowed() {

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'POST', $this->route, $this->get_hook_args( array(
			'id' => 123,
		) ) );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseMessageEquals( 'Cannot create an existing resource.', $res );
		$this->assertResponseCodeEquals( 'llms_rest_bad_request', $res );

	}

	/**
	 * Works.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_okay() {

		$args = $this->get_hook_args();

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'POST', $this->route, $args );

		$this->assertResponseStatusEquals( 201, $res );
		$this->assertIsAWebhook( $res->get_data() );
		$this->assertArrayHasKey( 'Location', $res->get_headers() );
		$links = $res->get_links();
		$this->assertArrayHasKey( 'self', $links );
		$this->assertArrayHasKey( 'collection', $links );

	}

	/**
	 * Public function test delete item.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_delete_item() {

		$hook = $this->get_hook();
		$id = $hook->get( 'id' );

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'DELETE', sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) ) );
		$this->assertResponseStatusEquals( 204, $res );

		$this->assertFalse( LLMS_REST_API()->webhooks()->get( $id ) );

		// Deleting agin still results in 204.
		$res = $this->perform_mock_request( 'DELETE', sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) ) );
		$this->assertResponseStatusEquals( 204, $res );

	}


	/**
	 * Can't get if unauthorized
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_item_error_unauthorized() {

		$hook = $this->get_hook();
		$res = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) ) );
		$this->assertResponseStatusEquals( 401, $res );
		$this->assertResponseCodeEquals( 'llms_rest_unauthorized_request', $res );

	}

	/**
	 * Authorized but missing capabilities
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_item_error_forbidden() {

		wp_set_current_user( $this->user_forbidden );
		$hook = $this->get_hook();
		$res = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) ) );
		$this->assertResponseStatusEquals( 403, $res );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $res );

	}

	/**
	 * 404
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_item_error_404() {

		wp_set_current_user( $this->user_allowed );
		$hook = $this->get_hook();
		$res = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) + 1 ) );
		$this->assertResponseStatusEquals( 404, $res );
		$this->assertResponseCodeEquals( 'llms_rest_not_found', $res );

	}

	/**
	 * Retrieve success.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_item_success() {

		wp_set_current_user( $this->user_allowed );
		$hook = $this->get_hook();
		$res = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) ) );
		$this->assertResponseStatusEquals( 200, $res );
		$data = $res->get_data();

		$this->assertIsAWebhook( $data );

	}

	/**
	 * Cant list unuathorized.
	 *
	 * @since [version]
	 *
	 * @see {Reference}
	 * @link {URL}
	 *
	 * @return [type]
	 */
	public function test_get_items_unauthorized() {

		$res = $this->perform_mock_request( 'GET', $this->route );
		$this->assertResponseStatusEquals( 401, $res );
		$this->assertResponseCodeEquals( 'llms_rest_unauthorized_request', $res );

	}

	/**
	 * Cant list without permissions.
	 *
	 * @since [version]
	 *
	 * @see {Reference}
	 * @link {URL}
	 *
	 * @return [type]
	 */
	public function test_get_items_forbidden() {

		wp_set_current_user( $this->user_forbidden );
		$res = $this->perform_mock_request( 'GET', $this->route );
		$this->assertResponseStatusEquals( 403, $res );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $res );

	}

	/**
	 * None found.
	 *
	 * @since [version]
	 *
	 * @see {Reference}
	 * @link {URL}
	 *
	 * @return [type]
	 */
	public function test_get_items_okay_none() {

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'GET', $this->route );
		$this->assertResponseStatusEquals( 200, $res );
		$this->assertEquals( array(), $res->get_data() );
		$headers = $res->get_headers();
		$this->assertEquals( 0, $headers['X-WP-Total'] );
		$this->assertEquals( 0, $headers['X-WP-TotalPages'] );
		$this->assertTrue( ! array_key_exists( 'Link', $headers ) );

	}

	/**
	 * Test pagination.
	 *
	 * @since [version]
	 *
	 * @see {Reference}
	 * @link {URL}
	 *
	 * @return [type]
	 */
	public function test_get_items_okay_pagination() {

		$this->create_many_webhooks( 6 );

		wp_set_current_user( $this->user_allowed );
		$res = $this->perform_mock_request( 'GET', $this->route, array(), array( 'per_page' => 2 ) );
		$this->assertResponseStatusEquals( 200, $res );

		$data = $res->get_data();
		foreach ( $data as $item ) {
			$this->assertIsAWebhook( $item );
		}

		$this->assertEquals( 2, count( $data ) );

		$headers = $res->get_headers();
		$this->assertEquals( 6, $headers['X-WP-Total'] );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'] );

		$links = $this->parse_link_headers( $res );
		$this->assertEquals( array( 'next', 'last' ), array_keys( $links ) );


		// Page 2
		$res = $this->perform_mock_request( 'GET', $this->route, array(), array( 'per_page' => 2, 'page' => 2 ) );
		$this->assertResponseStatusEquals( 200, $res );
		$data = $res->get_data();
		$this->assertEquals( 2, count( $data ) );

		$links = $this->parse_link_headers( $res );
		$this->assertEquals( array( 'first', 'prev', 'next', 'last' ), array_keys( $links ) );

		// Page 3
		$res = $this->perform_mock_request( 'GET', $this->route, array(), array( 'per_page' => 2, 'page' => 3 ) );
		$this->assertResponseStatusEquals( 200, $res );
		$data = $res->get_data();
		$this->assertEquals( 2, count( $data ) );

		$links = $this->parse_link_headers( $res );
		$this->assertEquals( array( 'first', 'prev' ), array_keys( $links ) );

	}

	/**
	 * Error for invalid status
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_update_item_invalid_status() {

		$hook = $this->get_hook();

		wp_set_current_user( $this->user_allowed );
		$route = sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) );
		$res = $this->perform_mock_request( 'POST', $route, array(
			'status' => 'invalid',
		) );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_invalid_param', $res );

	}

	/**
	 * Error for invalid status
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_update_item_bad_url() {

		$hook = $this->get_hook();

		remove_filter( 'llms_rest_webhook_pre_ping', '__return_true' );

		wp_set_current_user( $this->user_allowed );
		$route = sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) );
		$res = $this->perform_mock_request( 'POST', $route, array(
			'delivery_url' => 'https://fake.tld',
		) );
		$this->assertResponseStatusEquals( 500, $res );
		$this->assertResponseCodeEquals( 'llms_rest_webhook_ping_unreachable', $res );

		add_filter( 'llms_rest_webhook_pre_ping', '__return_true' );

	}

	/**
	 * Error for invalid topic
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_update_item_invalid_topic() {

		$hook = $this->get_hook();

		wp_set_current_user( $this->user_allowed );
		$route = sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) );
		$res = $this->perform_mock_request( 'POST', $route, array(
			'topic' => 'invalid',
		) );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_invalid_param', $res );

	}

	/**
	 * Success.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_update_item_okay() {

		$hook = $this->get_hook( array(
			'status' => 'disabled',
		) );

		$args = array(
			'name' => 'new name',
			'status' => 'active',
			'topic' => 'action.new_action',
			'secret' => 'newsecret',
			'delivery_url' => 'https://new.tld',
		);

		wp_set_current_user( $this->user_allowed );
		$route = sprintf( '%1$s/%2$d', $this->route, $hook->get( 'id' ) );
		$res = $this->perform_mock_request( 'POST', $route, $args );
		$data = $res->get_data();

		$this->assertResponseStatusEquals( 200, $res );
		$this->assertIsAWebhook( $data );
		$links = $res->get_links();
		$this->assertArrayHasKey( 'self', $links );
		$this->assertArrayHasKey( 'collection', $links );

		foreach ( $args as $key => $val ) {
			$this->assertEquals( $val, $data[ $key ] );
		}

	}

}
