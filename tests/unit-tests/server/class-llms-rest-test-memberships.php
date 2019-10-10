<?php
/**
 * Tests for Memberships API.
 *
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group REST
 * @group rest_memberships
 *
 * @since [version]
 */
class LLMS_REST_Test_Memberships extends LLMS_REST_Unit_Test_Case_Posts {

	/**
	 * Route.
	 *
	 * @var string
	 */
	protected $route = '/llms/v1/memberships';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_membership';

	/**
	 * Array of schema properties.
	 *
	 * @var array
	 */
	private $schema_props = array(
		'id',
		'auto_enroll',
		'catalog_visibility',
		'categories',
		'comment_status',
		'content',
		'date_created',
		'date_created_gmt',
		'date_updated',
		'date_updated_gmt',
		'excerpt',
		'featured_media',
		'instructors',
		'menu_order',
		'password',
		'permalink',
		'ping_status',
		'post_type',
		'restriction_action',
		'restriction_message',
		'restriction_page_id',
		'restriction_url',
		'sales_page_page_id',
		'sales_page_type',
		'sales_page_url',
		'slug',
		'status',
		'tags',
		'title',
	);

	/**
	 *
	 * Setup our test server, endpoints, and user info.
	 *
	 */
	public function setUp() {

		parent::setUp();
		$this->endpoint     = new LLMS_REST_Memberships_Controller();
		$this->user_allowed = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->user_forbidden = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

	}

	/**
	 * Test the item schema.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_item_schema() {

		$schema = $this->endpoint->get_item_schema();

		$this->assertEquals( 'llms_membership', $schema['title'] );

		$props = $this->schema_props;

		$schema_keys = array_keys( $schema['properties'] );
		sort( $schema_keys );
		sort( $props );

		$this->assertEquals( $props, $schema_keys );

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

		// Enrollments.
		$this->assertArrayHasKey( $this->route . '/(?P<id>[\d]+)/enrollments', $routes );

	}

	/**
	 * Test list membership's enrollments.
	 *
	 * @since [version]
	 *
	 * @todo test order and orderby
	 */
	public function test_get_membership_enrollments() {

		wp_set_current_user( $this->user_allowed );

		// create 1 membership.
		$membership = $this->factory->membership->create();
		$response   = $this->perform_mock_request( 'GET', $this->route . '/' . $membership . '/enrollments' );

		// We have no students enrolled for this membership so we expect a 404.
		$this->assertResponseStatusEquals( 404, $response );

		// create 5 students and enroll them.
		$student_ids = $this->factory->student->create_and_enroll_many( 5, $membership );
		$response    = $this->perform_mock_request( 'GET', $this->route . '/' . $membership . '/enrollments' );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );

		$res_data = $response->get_data();
		$this->assertEquals( 5, count( $res_data ) );

		// Filter by student_id.
		$response = $this->perform_mock_request( 'GET', $this->route . '/' . $membership . '/enrollments', array(), array( 'student' => "$student_ids[0]" ) );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );

		$res_data = $response->get_data();
		$this->assertEquals( 1, count( $res_data ) );
		$this->assertEquals( $student_ids[0], $res_data[0]['student_id'] );

	}

}
