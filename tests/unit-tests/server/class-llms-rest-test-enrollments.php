<?php
/**
 * Tests for Enrollments API.
 *
 * @package LifterLMS_Rest/Tests
 *
 * @group REST
 * @group rest_enrollments
 *
 * @since 1.0.0-beta.1
 * @version 1.0.0-beta.3
 */
class LLMS_REST_Test_Enrollments extends LLMS_REST_Unit_Test_Case_Server {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/students/(?P<id>[\d]+)/enrollments';

	/**
	 * Consider dates equal for +/- 2 mins
	 *
	 * @var integer
	 */
	private $date_delta = 120;

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
	 * @since 1.0.0-beta.1
	 */
	public function test_register_routes() {

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->route, $routes );
		$this->assertArrayHasKey( $this->route . '/(?P<post_id>[\d]+)', $routes );

	}

	/**
	 * Test list student enrollments.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_get_enrollments() {

		wp_set_current_user( $this->user_allowed );

		// create user.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Create new courses.
		$course_ids = $this->factory->post->create_many( 5, array( 'post_type' => 'course' ) );

		foreach ( $course_ids as $course_id ) {
			// Enroll Student in newly created course.
			llms_enroll_student( $user_id, $course_id, 'test_get_enrollments' );
		}

		$response = $this->perform_mock_request( 'GET',  $this->parse_route( $user_id ) );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();

		// Expect 5 enrollments.
		$this->assertEquals( 5, count( $res_data ) );

		// Check enrollments post_id.
		$i = 0;
		foreach ( $res_data as $enrollment ) {
			$this->assertEquals( $course_ids[$i], $res_data[$i]['post_id'] );
			// make sure post_id and student_id are inegers.
			$this->assertInternalType( "int", $res_data[$i]['post_id'] );
			$this->assertInternalType( "int", $res_data[$i]['student_id'] );
			$i++;
		}

	}

	/**
	 * Test list student enrollments.
	 *
	 * @since 1.0.0-beta.3
	 */
	public function test_get_enrollments_pagination() {

		wp_set_current_user( $this->user_allowed );

		// create enrollments.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Create new courses.
		$course_ids      = $this->factory->post->create_many( 25, array( 'post_type' => 'course' ) );
		$start_course_id = $course_ids[0];

		foreach ( $course_ids as $course_id ) {
			// Enroll Student in newly created course.
			llms_enroll_student( $user_id, $course_id, 'test_get_enrollments_pagination' );
		}

		$route = $this->parse_route( $user_id );

		// Page 1.
		$response = $this->perform_mock_request( 'GET', $route );

		$body = $response->get_data();
		$headers = $response->get_headers();

		$links = $this->parse_link_headers( $response );

		$this->assertResponseStatusEquals( 200, $response );
		$this->assertEquals( 25, $headers['X-WP-Total'] );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'] );
		$this->assertEquals( array( 'next', 'last' ), array_keys( $links ) );

		$this->assertEquals( range( $start_course_id, $start_course_id + 9 ), wp_list_pluck( $body, 'post_id' ) );

		$start_course_id += 10;

		// Page 2.
		$response = $this->perform_mock_request( 'GET', $route, array(), array( 'page' => 2 ) );

		$body = $response->get_data();
		$headers = $response->get_headers();

		$links = $this->parse_link_headers( $response );

		$this->assertResponseStatusEquals( 200, $response );

		$this->assertEquals( 25, $headers['X-WP-Total'] );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'] );
		$this->assertEquals( array( 'first', 'prev', 'next', 'last' ), array_keys( $links ) );

		$this->assertEquals( range( $start_course_id, $start_course_id + 9 ), wp_list_pluck( $body, 'post_id' ) );

		$start_course_id += 10;

		// Page 3.
		$response = $this->perform_mock_request( 'GET', $route, array(), array( 'page' => 3 ) );

		$body = $response->get_data();
		$headers = $response->get_headers();

		$links = $this->parse_link_headers( $response );

		$this->assertResponseStatusEquals( 200, $response );
		$this->assertEquals( 25, $headers['X-WP-Total'] );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'] );
		$this->assertEquals( array( 'first', 'prev' ), array_keys( $links ) );

		$this->assertEquals( range( $start_course_id, $start_course_id + 4 ), wp_list_pluck( $body, 'post_id' ) );

		// Out of bounds.
		$response = $this->perform_mock_request( 'GET', $route, array(), array( 'page' => 4 ) );

		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseCodeEquals( 'llms_rest_bad_request', $response );

	}

	/**
	 * Test list student enrollments filter by post_id.
	 *
	 * @since 1.0.0-beta.1
	 */
    public function test_get_enrollments_filter_post() {

		wp_set_current_user( $this->user_allowed );

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

		$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id ), array(), array( 'post' =>  "$courses[1],$courses[2]" ) );

	    // Success.
	    $this->assertResponseStatusEquals( 200, $response );
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
	 * Test getting current user enrollments permissions.
	 *
	 * @since [version]
	 */
	public function test_get_current_user_enrollments_permissions() {

		// create an user.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		// Setup course.
		$course_id = $this->factory->course->create();
		wp_set_current_user( $this->user_allowed );
		llms_enroll_student( $user_id, $course_id, 'test_get_current_user_enrollments' );

		wp_set_current_user( $user_id );

		// check we can list our own enrollments
		$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id ) );

		// Check we have permissions to make this request.
		$this->assertNotEquals( 403, $response->get_status() );
		// And that the list of enrollments contains the enrolled course.
		$enrollments = $response->get_data();
		$this->assertEquals( $course_id, $enrollments[0]['post_id'] );

		// Check we can get our own single enrollment.
		$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id ) . '/' . $course_id );

		// Check we have permissions to make this request.
		$this->assertNotEquals( 403, $response->get_status() );
		// And that the list of enrollments contains the enrolled course.
		$enrollment = $response->get_data();
		$this->assertEquals( $course_id, $enrollment['post_id'] );

	}

	/**
	 * Test getting enrollments without permission.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_get_enrollments_without_permission() {

		wp_set_current_user( 0 );

		// Setup course.
		$this->factory->course->create();

		$response = $this->perform_mock_request( 'GET',  $this->parse_route( 1 ) );
		// Check we don't have permissions to make this request.
		$this->assertResponseStatusEquals( 401, $response );

	}

	/**
	 * Test getting enrollments: forbidden request.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_get_enrollments_forbidden() {

		wp_set_current_user( $this->user_forbidden );

		// Setup course.
		$this->factory->course->create();

		$response = $this->perform_mock_request( 'GET',  $this->parse_route( 1 ) );

		// Check we're not allowed to get results.
		$this->assertResponseStatusEquals( 403, $response );

	}

	/**
	 * Test get single student enrollment
	 *
	 * @since 1.0.0-beta.1
	 */
    public function test_get_enrollment() {

		wp_set_current_user( $this->user_allowed );

		// create enrollment.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Create new courses.
		$course_id = $this->factory->post->create_many( 2, array( 'post_type' => 'course' ) );
		$date_now  = date( 'Y-m-d H:i:s' );
		llms_enroll_student( $user_id, $course_id[0], 'test_get_enrollment' );

		$response = $this->perform_mock_request( 'GET',  $this->parse_route( $user_id )  . '/' . $course_id[0] );

	    // Success.
	    $this->assertResponseStatusEquals( 200, $response );
	    $res_data = $response->get_data();

		// Check:
		$this->assertEquals( $user_id, $res_data['student_id'] );
		$this->assertEquals( $course_id[0], $res_data['post_id'] );
		$this->assertEquals( 'enrolled', $res_data['status'] );
		$this->assertEquals( $date_now, $res_data['date_created'], '', $this->date_delta );
		$this->assertEquals( $res_data['date_created'], $res_data['date_updated'] );

		$student = new LLMS_Student($user_id);
		$this->assertEquals( $res_data['status'], $student->get_enrollment_status( $course_id[0] ) );
		$this->assertEquals( $res_data['date_created'], $student->get_enrollment_date( $course_id[0], 'enrolled', 'Y-m-d H:i:s' ) );
		$this->assertEquals( $res_data['date_updated'], $student->get_enrollment_date( $course_id[0], 'updated', 'Y-m-d H:i:s' ) );

	}

	/**
	 * Test getting enrollment without permission.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_get_enrollment_without_permission() {

		wp_set_current_user( $this->user_allowed );

		// create enrollment.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		// Create new courses.
		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		llms_enroll_student( $user_id, $course_id, 'test_get_enrollment_noperm' );

		wp_set_current_user( 0 );

		// Setup course.
		$this->factory->course->create();

		$response = $this->perform_mock_request( 'GET',  $this->parse_route( $user_id )  . '/' . $course_id );

		// Check we don't have permissions to make this request.
		$this->assertResponseStatusEquals( 401, $response );

	}

	/**
	 * Test getting enrollment: forbidden request.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_get_enrollment_forbidden() {

		wp_set_current_user( $this->user_forbidden );

		// create enrollment.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		// Create new courses.
		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		llms_enroll_student( $user_id, $course_id, 'test_get_enrollment_forbidden' );

		// Setup course.
		$this->factory->course->create();

		$response = $this->perform_mock_request( 'GET',  $this->parse_route( $user_id )  . '/' . $course_id );

		// Check we're not allowed to get results.
		$this->assertResponseStatusEquals( 403, $response );

	}

	/**
	 * Test create enrollment.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_create_enrollment() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->perform_mock_request( 'POST',  $this->parse_route( $user_id )  . '/' . $course_id );
		$date_now  = date( 'Y-m-d H:i:s' );

		// Success.
		$this->assertResponseStatusEquals( 201, $response );
		$res_data = $response->get_data();

		// Check:
		$this->assertEquals( $user_id, $res_data['student_id'] );
		$this->assertEquals( $course_id, $res_data['post_id'] );
		$this->assertEquals( 'enrolled', $res_data['status'] );
		$this->assertEquals( $date_now, $res_data['date_created'], '', $this->date_delta );
		$this->assertEquals( $res_data['date_created'], $res_data['date_updated'] );

	}

	/**
	 * Test producing bad request error when creating a single enrollment.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_create_enrollment_bad_request() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Bad request: post is not enrollable.
		$lesson_id = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		$response = $this->perform_mock_request( 'POST',  $this->parse_route( $user_id )  . '/' . $lesson_id );

		$this->assertResponseStatusEquals( 400, $response );

		// invalid date.
		llms_enroll_student( $user_id, $course_id );
		$response = $this->perform_mock_request( 'PATCH',  $this->parse_route( $user_id ) . '/' . $course_id, array( 'date_created' => 'some_invalid_date' ) );
		$this->assertResponseStatusEquals( 400, $response );

	}

	/**
	 * Test update enrollment status.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_update_enrollment_status() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Enroll Student in newly created course/membership
		llms_enroll_student( $user_id, $course_id, 'test_update_status' );

		sleep(1); //<- to be sure the new status is subsequent the one set on creation.

		$response = $this->perform_mock_request( 'PATCH',  $this->parse_route( $user_id ) . '/' . $course_id, array( 'status' => 'expired' ) );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();

		// Check:
		$this->assertEquals( $user_id, $res_data['student_id'] );
		$this->assertEquals( $course_id, $res_data['post_id'] );
		$this->assertEquals( 'expired', $res_data['status'] );
		$student = new LLMS_Student( $user_id );
		$this->assertEquals( $res_data['status'], $student->get_enrollment_status( $course_id, false ) );

		// enroll and check the trigger is admin_{$this->user_allowed}.
		// clean:
		$student->delete_enrollment( $course_id );
		// insert an enrollment with a "different" trigger.
		$student->enroll( $course_id, 'whatever_trigger' );
		// unenroll.
		$student->unenroll( $course_id );
		$this->assertEquals( 'expired', $student->get_enrollment_status( $course_id ) );
		$this->assertEquals( 'whatever_trigger', $student->get_enrollment_trigger( $course_id, false ) );

		// enroll via api.
		sleep(1); //<- to be sure the new status is subsequent the one previously set.
		$response = $this->perform_mock_request( 'PATCH',  $this->parse_route( $user_id ) . '/' . $course_id, array( 'status' => 'enrolled' ) );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( 'enrolled', $res_data['status'] );
		$this->assertEquals( 'enrolled', $student->get_enrollment_status( $course_id, true ) );
		$this->assertEquals( "admin_{$this->user_allowed}", $student->get_enrollment_trigger( $course_id ) );

	}

	/**
	 * Test update enrollment creation date.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_update_enrollment_creation_date() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		// Enroll Student in newly created course/membership
		llms_enroll_student( $user_id, $course_id, 'test_update_creation' );

		$new_date = date( 'Y-m-d H:i:s', strtotime('+1 year') );
		$response = $this->perform_mock_request( 'PATCH',  $this->parse_route( $user_id ) . '/' . $course_id, array( 'date_created' => $new_date ) );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();

		// Check:
		$this->assertEquals( $user_id, $res_data['student_id'] );
		$this->assertEquals( $course_id, $res_data['post_id'] );
		$this->assertEquals( $new_date, $res_data['date_created'] );

		$student = new LLMS_Student( $user_id );
		$this->assertEquals( $res_data['date_created'], $student->get_enrollment_date( $course_id, 'enrolled', 'Y-m-d H:i:s' ) );

	}

	/**
	 * Test producing 404 request errort.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function test_enrollments_not_found() {

		wp_set_current_user( $this->user_allowed );

		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		/* create */
		// user not enrolled
		$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id ) );
		$this->assertResponseStatusEquals( 404, $response );

		// User id doesn't exist.
		$response = $this->perform_mock_request( 'POST', $this->parse_route( $user_id . '1234' ) . '/' . $course_id );
		$this->assertResponseStatusEquals( 404, $response );

		// Course id doesn't exist.
		$response = $this->perform_mock_request( 'POST', $this->parse_route( $user_id ) . '/' . $course_id . '1245' );
		$this->assertResponseStatusEquals( 404, $response );

		/* Update and Retrieve single */
		foreach ( array( 'PATCH', 'GET' ) as $method ) {
			// User id doesn't exist.
			$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id . '1234' ) . '/' . $course_id );
			$this->assertResponseStatusEquals( 404, $response );

			// Course id doesn't exist.
			$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id ) . '/' . $course_id . '1245' );
			$this->assertResponseStatusEquals( 404, $response );

			// User id and course id exist but the enrollment is not found
			$response = $this->perform_mock_request( 'GET', $this->parse_route( $user_id ) . '/' . $course_id  );
			$this->assertResponseStatusEquals( 404, $response );
		}

	}

	/**
	 * Test deleting a single enrollment.
	 *
	 * @since 1.0.0-beta.1
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
		$response = $this->perform_mock_request( 'DELETE',  $this->parse_route( $user_id ) . '/' . $course_id );

		// Success.
		$this->assertResponseStatusEquals( 204, $response );
		// Student should not be enrolled in course
		$this->assertFalse( llms_is_user_enrolled( $user_id, $course_id ) );

	}

	/**
	 * Test protected enrollment_exists method.
	 *
	 * @since 1.0.0-beta.1
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
