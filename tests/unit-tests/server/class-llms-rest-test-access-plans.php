<?php
/**
 * Tests for Access Plans API
 *
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group REST
 * @group rest_access_plans
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Access_Plans extends LLMS_REST_Unit_Test_Case_Posts {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_access_plan';

	/**
	 * Route.
	 *
	 * @var string
	 */
	protected $route = '/llms/v1/access-plans';

	/**
	 * Arguments to access plan API calls.
	 *
	 * @var array
	 */
	protected $sample_access_plan_args = array(
		'price' => '1.99',
		'title' => 'What a title',
	);

	/**
	 * This is an internal flag we use to determine whether or not
	 * we need to use a step of 2 ids when testing the pagination.
	 *
	 * @var array
	 */
	protected $generates_revision_on_creation = false;

	/**
	 * @since [version]
	 * @var array
	 */
	private $schema_properties = array(
		'access_expiration',
		'access_expires',
		'access_length',
		'access_period',
		'availability_restrictions',
		'content',
		'date_created',
		'date_created_gmt',
		'date_updated',
		'date_updated_gmt',
		'enroll_text',
		'frequency',
		'id',
		'length',
		'menu_order',
		'period',
		'price',
		'post_id',
		'redirect_forced',
		'redirect_page',
		'redirect_type',
		'redirect_url',
		'sale_date_end',
		'sale_date_start',
		'sale_enabled',
		'sale_price',
		'sku',
		'title',
		'trial_enabled',
		'trial_length',
		'trial_period',
		'trial_price',
		'visibility',
	);

	/**
	 * Array of link $rels expected for each item.
	 *
	 * @var string[]
	 */
	private $expected_link_rels = array(
		'self',
		'collection',
		'post',
		'restrictions',
	);

	/**
	 *
	 * Setup our test server, endpoints, and user info.
	 *
	 * @since 1.0.0-beta.9
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint       = new LLMS_REST_Access_Plans_Controller();
		$this->user_allowed   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->user_forbidden = $this->factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Test the item schema
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_item_schema() {
		$schema = $this->endpoint->get_item_schema();

		$this->assertEquals( 'llms_access_plan', $schema['title'] );

		$props = $this->schema_properties;
		$schema_keys = array_keys( $schema['properties'] );

		$this->assertEqualSets( $props, $schema_keys );
	}

	/**
	 * Test list access plans pagination success
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_access_plans_with_pagination() {
		wp_set_current_user( $this->user_allowed );

		$access_plan_ids      = $this->factory->post->create_many( 25, array( 'post_type' => $this->post_type ) );
		$course               = $this->factory->course->create();
		foreach ( $access_plan_ids as $id ) {
			update_post_meta( $id, '_llms_product_id', $course );
		}
		$start_access_plan_id = $access_plan_ids[0];
		$this->pagination_test( $this->route, $start_access_plan_id );
	}

	/**
	 * Test getting single access plan that doesn't exist.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_nonexistent_access_plan() {

		wp_set_current_user( 0 );

		// Setup access plan
		$access_plan_id = $this->factory->post->create( array( 'post_type' => $this->post_type ) );

		$response = $this->perform_mock_request(
			'GET',
			$this->route . '/' . $access_plan_id . '2'
		);

		// The access plan doesn't exist.
		$this->assertResponseStatusEquals( 404, $response );

	}

	/**
	 * Test creating a single access plan
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_access_plan() {

		wp_set_current_user( $this->user_allowed );

		$course      = $this->factory->course->create_and_get();
		$sample_args = array_merge(
			$this->sample_access_plan_args,
			array(
				'post_id' => $course->get( 'id' ),
			)
		);

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		// Success.
		$this->assertResponseStatusEquals( 201, $response );

	}

	/**
	 * Test producing bad request error when creating a single access-plans
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_access_plan_bad_request() {
		wp_set_current_user( $this->user_allowed );

		$course      = $this->factory->course->create_and_get();
		$sample_args = array_merge(
			$this->sample_access_plan_args,
			array(
				'post_id' => $course->get( 'id' ),
			)
		);

		// Creating an access plan passing an id produces a bad request.
		$sample_args['id'] = '123';

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		// Bad request.
		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseMessageEquals( 'Cannot create existing Access Plan.', $response );

		unset( $sample_args['id'] );

		// Create an access plan without title.
		unset( $sample_args['title'] );

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		// Bad request.
		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseMessageEquals( 'Missing parameter(s): title', $response );

		$sample_args['title'] = $this->sample_access_plan_args['title'];

		// Create an access plan without price.
		unset( $sample_args['price'] );

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		// Bad request.
		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseMessageEquals( 'Missing parameter(s): price', $response );


		// Create an access plan without post_id.
		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$this->sample_access_plan_args
		);

		// Bad request.
		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseMessageEquals( 'Missing parameter(s): post_id', $response );

	}

	/**
	 * Test access plan alteration is allowed to who can edit parent post
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_altering_access_plan_allowed_to_who_can_edit_parent_post() {

		$instructor = $this->factory->user->create(
			array( 'role' => 'instructor' )
		);
		$assistant  = $this->factory->user->create(
			array( 'role' => 'instructors_assistant' )
		);
		$course     = $this->factory->course->create_and_get();

		// Assign the instructors to the course.
		$course->set_instructors(
			array(
				array(
					'id' => $instructor
				),
				array(
					'id' => $assistant
				),
			)
		);

		$sample_args = array_merge(
			$this->sample_access_plan_args,
			array(
				'post_id' => $course->get( 'id' ),
			)
		);

		// Instructors of the Course with post_id can manipulate.
		wp_set_current_user( $instructor );

		// Creation is allowed.
		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		$this->assertResponseStatusEquals( 201, $response );
		$new_plan_id = $response->get_data()['id'];

		// Update is allowed.
		$response = $this->perform_mock_request(
			'POST',
			$this->route . '/' . $new_plan_id,
			array(
				'title' => 'Title can change',
			)
		);

		// Update is allowed.
		$this->assertResponseStatusEquals( 200, $response );

		// Deletion is allowed.
		$response = $this->perform_mock_request(
			'DELETE',
			$this->route . '/' . $new_plan_id
		);
		$this->assertResponseStatusEquals( 204, $response );

		// Check the same happens with intructors assistants

		// Instructor's Assistant of the Course with post_id can manipulate.
		wp_set_current_user( $assistant );

		$sample_args = array_merge(
			$this->sample_access_plan_args,
			array(
				'post_id' => $course->get( 'id' ),
			)
		);

		// Creation is allowed.
		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		$this->assertResponseStatusEquals( 201, $response );
		$new_plan_id = $response->get_data()['id'];

		// Update is allowed.
		$response = $this->perform_mock_request(
			'POST',
			$this->route . '/' . $new_plan_id,
			array(
				'title' => 'Title can change',
			)
		);

		// Update is allowed.
		$this->assertResponseStatusEquals( 200, $response );

		// Deletion is allowed.
		$response = $this->perform_mock_request(
			'DELETE',
			$this->route . '/' . $new_plan_id
		);
		$this->assertResponseStatusEquals( 204, $response );

	}

	/**
	 * Test forbidden single access plan creation
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_access_plan_forbidden() {
		wp_set_current_user( $this->user_forbidden );

		$sample_args = array_merge(
			$this->sample_access_plan_args,
			array(
				'post_id' => $this->factory->course->create(),
			)
		);

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		// Forbidden.
		$this->assertResponseStatusEquals( 403, $response );

		// Check that a generic instructor can't create an access plan.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'instructor' ) ) );

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

	}


	/**
	 * Test creating single access plan without permissions
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_access_plan_without_permissions() {
		wp_set_current_user( 0 );

		$sample_args = array_merge(
			$this->sample_access_plan_args,
			array(
				'post_id' => $this->factory->course->create(),
			)
		);

		$response = $this->perform_mock_request(
			'POST',
			$this->route,
			$sample_args
		);

		// Unauthorized.
		$this->assertResponseStatusEquals( 401, $response );

	}

	/**
	 * Test links
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_links() {

		wp_set_current_user( $this->user_allowed );

		$access_plan_id = $this->factory->post->create( array( 'post_type' => $this->post_type ) );
		$expected_link_rels = $this->expected_link_rels;

		// Link the plan to a course.
		$course = $this->factory->course->create();
		update_post_meta( $access_plan_id, '_llms_product_id', $course );

		// Limit it by membership.
		$membership = $this->factory->membership->create();
		update_post_meta( $access_plan_id, '_llms_availability_restrictions', array( $membership ) );
		update_post_meta( $access_plan_id, '_llms_availability', 'members' );

		$response = $this->perform_mock_request( 'GET', $this->route . '/' . $access_plan_id );

		$this->assertEquals( $expected_link_rels, array_keys( $response->get_links() ) );

		// Remove availability restrictions.
		update_post_meta( $access_plan_id, '_llms_availability', '' );
		$response = $this->perform_mock_request( 'GET', $this->route . '/' . $access_plan_id );
		unset( $expected_link_rels[ array_search( 'restrictions', $expected_link_rels, true ) ] );

		$this->assertEquals( $expected_link_rels, array_keys( $response->get_links() ) );

	}

}
