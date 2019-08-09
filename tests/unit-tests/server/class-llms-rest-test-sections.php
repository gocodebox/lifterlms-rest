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
