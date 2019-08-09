<?php
/**
 * Tests for Enrollments API.
 *
 * @package LifterLMS_Rest/Tests
 *
 * @group REST
 * @group rest_enrollments
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Enrollments extends LLMS_REST_Unit_Test_Case_Server {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/students/(?P<id>[\d]+)/enrollments';

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}lifterlms_user_postmeta" );

		$this->endpoint = new LLMS_REST_Enrollments_Controller();

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
	 * Test route registration.
	 *
	 * @since [version]
	 */
	public function test_register_routes() {

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->route, $routes );
		$this->assertArrayHasKey( $this->route . '/(?P<post_id>[\d]+)', $routes );

	}

	/**
	 * Test list student enrollments.
	 *
	 * @since [version]
	 */
	public function test_get_enrollments() {

		// create enrollments.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Create new courses.
		$course_ids = $this->factory->post->create_many( 5, array( 'post_type' => 'course' ) );

		foreach ( $course_ids as $course_id ) {
			// Enroll Student in newly created course.
			llms_enroll_student( $user_id, $course_id, 'test_get_enrollments' );
		}

		$request = new WP_REST_Request( 'GET', $this->parse_route($user_id) );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$res_data = $response->get_data();

		// Expect 5 enrollments.
		$this->assertEquals( 5, count( $res_data ) );

		// Check enrollments post_id.
		$i = 0;
		foreach ( $res_data as $enrollment ) {
			$this->assertEquals( $course_ids[$i], $res_data[$i++]['post_id'] );
		}
	}

	/**
	 * Test list student enrollments filter by post_id.
	 *
	 * @since [version]
	 */
    public function test_get_enrollment_filter_post() {

		// create enrollments.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Create new courses.
		$course_ids = $this->factory->post->create_many( 10, array( 'post_type' => 'course' ) );

		$j = 0;
		$courses = array();
		foreach ( $course_ids as $course_id ) {
			if ( 0 === ( $j++ % 2 ) ) {
				// Enroll Student in newly created course.
				llms_enroll_student( $user_id, $course_id, 'test_filter_enrollments' );
				$courses[] = $course_id;
			}
		}

		$request = new WP_REST_Request( 'GET', $this->parse_route($user_id) );
		$request->set_param( 'post', "$courses[1],$courses[2]" );
	    $response = $this->server->dispatch( $request );

	    // Success.
	    $this->assertEquals( 200, $response->get_status() );
	    $res_data = $response->get_data();

	    // Expect 2 enrollments.
	    $this->assertEquals( 2, count( $res_data ) );

	    // Check enrollments post_id.
	    $i = 0;
	    foreach ( $res_data as $enrollment ) {
			$this->assertEquals( $courses[$i+1], $res_data[$i++]['post_id'] );
		}

	}

	/**
	 * Test create enrollment.
	 *
	 * @since [version]
	 */
	public function test_create_enrollment() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$request  = new WP_REST_Request( 'POST', $this->parse_route( $user_id ) . '/' . $course_id );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 201, $response->get_status() );
		$res_data = $response->get_data();

		// Check:
		$this->assertEquals( $user_id, $res_data['student_id'] );
		$this->assertEquals( $course_id, $res_data['post_id'] );
		$this->assertEquals( 'enrolled', $res_data['status'] );

	}

	/**
	 * Test producing bad request error when creating a single enrollment.
	 *
	 * @since [version]
	 */
	public function test_create_enrollment_bad_request() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Bad request: user id doesn't exist.
		$request  = new WP_REST_Request( 'POST', $this->parse_route( $user_id . '1234' ) . '/' . $course_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Bad request: course id doesn't exist.
		$request  = new WP_REST_Request( 'POST', $this->parse_route( $user_id ) . '/' . $course_id . '1245' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Bad request: post is not a enrollable.
		$lesson_id = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		$request  = new WP_REST_Request( 'POST', $this->parse_route( $user_id ) . '/' . $lesson_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

	}

	/**
	 * Test deleting a single enrollment.
	 *
	 * @since [version]
	 */
	public function test_delete_enrollment() {

		wp_set_current_user( $this->user_allowed );

		// create an enrollment, we need a student and a course/membership.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		// Create new course
		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );

		// Enroll Student in newly created course/membership
		llms_enroll_student( $user_id, $course_id, 'test_delete' );

		// Delete user's enrollment
		$request = new WP_REST_Request( 'DELETE', $this->parse_route($user_id) . '/' . $course_id );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 204, $response->get_status() );
		// Student should not be enrolled in course
		$this->assertFalse( llms_is_user_enrolled( $user_id, $course_id ) );

	}

	/**
	 * Test protected enrollment_exists method.
	 *
	 * @since [version]
	 */
	public function test_enrollment_exists() {
		$error_code = 'llms_rest_not_found';

		$result = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'enrollment_exists', array( 789, 879 ) );
		// enrollment doesn't exist because both student and course/membership do not exist.
		$this->assertWPError( $result );

		$student_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$result = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'enrollment_exists', array( $student_id, 879 ) );
		// enrollment doesn't exist because course/membership do not exist.
		$this->assertWPError( $result );

		// Create new course.
		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$result = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'enrollment_exists', array( $student_id, $course_id ) );
		// enrollment doesn't exist because the $student has not been enrolled yet.
		$this->assertWPError( $result );
		$this->assertWPErrorCodeEquals( $error_code, $result );

		// Enroll Student.
		llms_enroll_student( $student_id, $course_id, 'test_exists' );
		$result = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'enrollment_exists', array( $student_id, $course_id ) );
		// enrollment exists because the $student has been enrolled yet.
		$this->assertTrue( $result );

		// Unenroll Student.
		llms_unenroll_student( $student_id, $course_id );
		$result = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'enrollment_exists', array( $student_id, $course_id ) );
		// enrollment still exists because the $student has been unenrolled but not deleted
		$this->assertTrue( $result );

		// Delete student's enrollment
		llms_delete_student_enrollment( $student_id, $course_id );
		$result = LLMS_Unit_Test_Util::call_method( $this->endpoint, 'enrollment_exists', array( $student_id, $course_id ) );
		// enrollment still exists because the $student has been unenrolled but not deleted
		$this->assertWPError( $result );
		$this->assertWPErrorCodeEquals( $error_code, $result );
	}

	private function parse_route( $student_id ) {
		return str_replace( '(?P<id>[\d]+)', $student_id, $this->route );
	}

}
