<?php
/**
 * Tests for Sections API.
 *
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group REST
 * @group rest_sections
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Sections extends LLMS_REST_Unit_Test_Case_Server {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/sections';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private $post_type = 'section';

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {

		parent::setUp();
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


		$this->sample_section_args = array(
			'title'        => array(
				'rendered' => 'Introduction',
				'raw'      => 'Introduction',
			),
		);

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'posts', array( 'post_type' => $this->post_type ) );

		$this->endpoint = new LLMS_REST_Sections_Controller();
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

		// Child lessons.
		// $this->assertArrayHasKey( $this->route . '/(?P<id>[\d]+)/content', $routes );
	}

	/**
	 * Test list sections.
	 *
	 * @since [version]
	 */
	public function test_get_sections() {

		wp_set_current_user( $this->user_allowed );

		// create 2 courses.
		$courses = $this->factory->course->create_many( 2, array( 'sections' => 5 ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route ) );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$res_data = $response->get_data();
		$this->assertEquals( 10, count( $res_data ) ); // default per_page is 10.

		// Check retrieved sections are the same as the generated ones.
		/*
		$course_id =
		for ( $i = 0; $i < 10; $i++ ) {
			assertEquals( $res_data[$i]['title']);
		}*/

	}

	/**
	 * Test producing bad request error when creating a single section.
	 *
	 * @since [version]
	 */
	public function test_create_section_bad_request() {

		wp_set_current_user( $this->user_allowed );

		$request = new WP_REST_Request( 'POST', $this->route );
		// create a course.
		$course = $this->factory->course->create( array( 'sections' => 0 ) );
		$post   = $this->factory->post->create();

		// create a section without parent_id.
		$section_args = $this->sample_section_args;

		$request->set_body_params( $section_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// Creating a section passing a parent_id which is not a course id produces a bad request.
		$section_args = $this->sample_section_args;

		// This post doesn't exist.
		$section_args['parent_id'] = 1234;

		$request->set_body_params( $section_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertResponseMessageEquals( 'Invalid parent_id param. It must be a valid Course ID.', $response );

		// This post exists but is not a course.
		$section_args['parent_id'] = $post;

		$request->set_body_params( $section_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertResponseMessageEquals( 'Invalid parent_id param. It must be a valid Course ID.', $response );

		$this->sample_section_args['parent_id'] = $course;

		// Creating a section passing an order equal to 0 produces a bad request.
		$section_args = $this->sample_section_args;
		$section_args['order'] = 0;
		$request->set_body_params( $section_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertResponseMessageEquals( 'Invalid order param. It must be greater than 0.', $response );

		// create a section without title.
		$section_args = $this->sample_section_args;
		unset( $section_args['title'] );

		$request->set_body_params( $section_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

	}

	/**
	 * Test deleting a single section.
	 *
	 * @since [version]
	 */
	public function test_delete_section() {

		wp_set_current_user( $this->user_allowed );

		// create a section first.
		$section = llms_get_post( $this->factory->post->create( array( 'post_type' => 'section' ) ) );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $section->get( 'id' ) );

		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 204, $response->get_status() );

		// Cannot find just deleted post.
		$this->assertFalse( get_post_status( $section->get( 'id' ) ) );

	}

	/**
	 * Test trashing a single section.
	 *
	 * @since [version]
	 */
	public function test_trash_section() {

		wp_set_current_user( $this->user_allowed );

		// create a section first.
		$section = llms_get_post( $this->factory->post->create( array( 'post_type' => 'section' ) ) );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $section->get( 'id' ) );
		$request->set_param( 'force', false );
		$response = $this->server->dispatch( $request );

		// We still expect a 204 section are always deleted and not trashed.
		$this->assertEquals( 204, $response->get_status() );

		// Cannot find just deleted post.
		$this->assertFalse( get_post_status( $section->get( 'id' ) ) );

	}

}
