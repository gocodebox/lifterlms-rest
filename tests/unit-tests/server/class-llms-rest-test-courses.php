<?php
/**
 * Tests for Courses API.
 *
 * @package LifterLMS_Rest/Tests
 *
 * @group REST
 * @group rest_courses
 *
 * @since [version]
 * @version [version]
 *
 * @todo update tests with the new params, e.g. rendered/raw content
 */
class LLMS_REST_Test_Courses extends LLMS_REST_Server_Unit_Test_Case {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/courses';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private $post_type = 'course';

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {

		parent::setUp();
		$this->endpoint     = new LLMS_REST_Courses_Controller();
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

		$this->sample_course_args = array(
			'title'        => array(
				'rendered' => 'Getting Started with LifterLMS',
				'raw'      => 'Getting Started with LifterLMS',
			),
			'content'      => array(
				'rendered' => "\\n<h2>Lorem ipsum dolor sit amet.</h2>\\n\\n\\n\\n<p>Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>\\n",
				'raw'      => "<!-- wp:heading -->\\n<h2>Lorem ipsum dolor sit amet.</h2>\\n<!-- /wp:heading -->\\n\\n<!-- wp:paragraph -->\\n<p>Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>\\n<!-- /wp:paragraph -->",
			),
			'date_created' => '2019-05-20 17:22:05',
			'status'       => 'publish',
		);

		// To avoid adding parts to the content.
		add_filter( 'llms_blocks_is_post_migrated', '__return_true' );

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'posts', array( 'post_type' => $this->post_type ) );

		// assume all courses have been migrated to the block editor.
		add_filter( 'llms_blocks_is_post_migrated', '__return_true' );
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

	}


	/**
	 * Test list courses.
	 *
	 * @since [version]
	 */
	public function test_get_courses() {

		wp_set_current_user( $this->user_allowed );

		// create 12 courses.
		$courses = $this->factory->course->create_many( 12 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route ) );

		$res_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $res_data ) ); // default per_page is 10.

		// Check retrieved courses are the same as the generated ones.
		// Note: the check can be done in this simple way as by default the rest api courses are ordered by id.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->courses_fields_match( new LLMS_Course( $courses[ $i ] ), $res_data[ $i ] );
		}

	}

	/**
	 * Test list courses pagination success.
	 *
	 * @since [version]
	 */
	public function test_get_courses_with_pagination() {

		wp_set_current_user( $this->user_allowed );

		// create 15 courses.
		$courses = $this->factory->course->create_many( 15 );
		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'page', 2 );

		$response = $this->server->dispatch( $request );

		$res_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 5, count( $res_data ) );

		// Check retrieved courses are the same as the generated ones with an offset of 10 (first page).
		// Note: the check can be done in this simple way as by default the rest api courses are ordered by id.
		for ( $i = 0; $i < 5; $i++ ) {
			$this->courses_fields_match( new LLMS_Course( $courses[ $i + 10 ] ), $res_data[ $i ] );
		}

	}

	/**
	 * Test list courses include arg
	 *
	 * @since [version]
	 */
	public function test_get_courses_include() {

		wp_set_current_user( $this->user_allowed );

		// create 15 courses.
		$courses = $this->factory->course->create_many( 5 );
		$request = new WP_REST_Request( 'GET', $this->route );

		// get only the 2nd and 3rd course.
		$request->set_param( 'include', "$courses[1], $courses[2]" );

		$response = $this->server->dispatch( $request );

		$res_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $res_data ) );

		// Check retrieved courses are the same as the second and third generated courses.
		for ( $i = 0; $i < 2; $i++ ) {
			$this->courses_fields_match( new LLMS_Course( $courses[ $i + 1 ] ), $res_data[ $i ] );
		}

	}

	/**
	 * Test list courses exclude arg
	 *
	 * @since [version]
	 */
	public function test_get_courses_exclude() {

		wp_set_current_user( $this->user_allowed );

		// create 15 courses.
		$courses = $this->factory->course->create_many( 5 );
		$request = new WP_REST_Request( 'GET', $this->route );

		// get only the 2nd and 3rd course.
		$request->set_param( 'exclude', "$courses[0], $courses[1]" );

		$response = $this->server->dispatch( $request );

		$res_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $res_data ) );
		// Check retrieved data do not contain first and second created courses.
		$this->assertEquals( array_slice( $courses, 2 ), wp_list_pluck( $res_data, 'id' ) );
	}

	/**
	 * Test list courses ordered by id desc.
	 *
	 * @since [version]
	 */
	public function test_get_courses_ordered_by_id_desc() {

		wp_set_current_user( $this->user_allowed );

		// create 5 courses.
		$courses = $this->factory->course->create_many( 5 );
		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'order', 'desc' ); // default is 'asc'.

		$response = $this->server->dispatch( $request );

		$res_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		// Check retrieved courses are the same as the generated ones but in the reversed order.
		// Note: the check can be done in this simple way as by default the rest api courses are ordered by id.
		$reversed_data = array_reverse( $res_data );
		for ( $i = 0; $i < 5; $i++ ) {
			$this->courses_fields_match( new LLMS_Course( $courses[ $i ] ), $reversed_data[ $i ] );
		}

	}

	/**
	 * Test list courses ordered by title.
	 *
	 * @since [version]
	 */
	public function test_get_courses_ordered_by_title() {

		wp_set_current_user( $this->user_allowed );

		// create 3 courses.
		$courses = $this->factory->course->create_many( 3 );

		$course_first = new LLMS_Course( $courses[0] );
		$course_first->set( 'title', 'Course B' );
		$course_second = new LLMS_Course( $courses[1] );
		$course_second->set( 'title', 'Course A' );
		$course_second = new LLMS_Course( $courses[2] );
		$course_second->set( 'title', 'Course C' );

		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'orderby', 'title' ); // default is id.

		$response = $this->server->dispatch( $request );

		$res_data = $response->get_data();

		// Check retrieved courses are ordered by title asc.
		$this->assertEquals( 'Course A', $res_data[0]['title']['rendered'] );
		$this->assertEquals( 'Course B', $res_data[1]['title']['rendered'] );
		$this->assertEquals( 'Course C', $res_data[2]['title']['rendered'] );
	}

	/**
	 * Test list courses ordered by title
	 *
	 * @since [version]
	 */
	public function test_get_courses_ordered_by_title_desc() {

		wp_set_current_user( $this->user_allowed );

		// create 3 courses.
		$courses = $this->factory->course->create_many( 3 );

		$course_first = new LLMS_Course( $courses[0] );
		$course_first->set( 'title', 'Course B' );
		$course_second = new LLMS_Course( $courses[1] );
		$course_second->set( 'title', 'Course A' );
		$course_second = new LLMS_Course( $courses[2] );
		$course_second->set( 'title', 'Course C' );

		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'orderby', 'title' ); // default is id.
		$request->set_param( 'order', 'desc' ); // default is 'asc'.

		$response = $this->server->dispatch( $request );
		$res_data = $response->get_data();

		// Check retrieved courses are ordered by title desc.
		$this->assertEquals( 'Course C', $res_data[0]['title']['rendered'] );
		$this->assertEquals( 'Course B', $res_data[1]['title']['rendered'] );
		$this->assertEquals( 'Course A', $res_data[2]['title']['rendered'] );
	}

	/**
	 * Test getting courses without permission.
	 *
	 * @since [version]
	 *//*
	public function test_get_courses_without_permission() {

		wp_set_current_user( 0 );

		// Setup course.
		$this->factory->course->create();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route ) );

		// Check we don't have permissions to make this request.
		$this->assertEquals( 401, $response->get_status() );

	}
	*/

	/**
	 * Test getting courses: forbidden request.
	 *
	 * @since [version]
	 *//*
	public function test_get_courses_forbidden() {

		wp_set_current_user( $this->user_forbidden );

		// Setup course.
		$this->factory->course->create();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route ) );

		// Check we're not allowed to get results.
		$this->assertEquals( 403, $response->get_status() );

	}*/

	/**
	 * Test getting courses: bad request.
	 *
	 * @since [version]
	 */
	public function test_get_courses_bad_request() {

		wp_set_current_user( $this->user_allowed );

		// create 5 courses.
		$courses = $this->factory->course->create_many( 5 );
		$request = new WP_REST_Request( 'GET', $this->route );

		// Bad request, there's no page 2.
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Bad request, order param allowed are only "desc" and "asc" (emum).
		$request->set_param( 'order', 'not_desc' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

	}

	/**
	 * Test getting a single course.
	 *
	 * @since [version]
	 */
	public function test_get_course() {

		wp_set_current_user( $this->user_allowed );

		// Setup course.
		$course   = $this->factory->course->create_and_get();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route . '/' . $course->get( 'id' ) ) );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		// Check retrieved course matches the created ones.
		$this->courses_fields_match( $course, $response->get_data() );

	}


	/**
	 * Test getting single course without permission.
	 *
	 * @since [version]
	 */
	/*
	public function test_get_course_without_permission() {

		wp_set_current_user( 0 );

		// Setup course.
		$course_id = $this->factory->course->create();
		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route . '/' . $course_id ) );

		// Check we don't have permissions to make this request.
		$this->assertEquals( 401, $response->get_status() );

	}
	*/

	/**
	 * Test getting forbidden single course.
	 *
	 * @since [version]
	 */
	/*
	public function test_get_course_forbidden() {

		wp_set_current_user( $this->user_forbidden );

		// Setup course.
		$course_id = $this->factory->course->create();
		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route . '/' . $course_id ) );

		// Check we're not allowed to get results.
		$this->assertEquals( 403, $response->get_status() );

	}
	*/

	/**
	 * Test getting single course that doesn't exist.
	 *
	 * @since [version]
	 */
	public function test_get_nonexistent_course() {

		wp_set_current_user( 0 );

		// Setup course.
		$course_id = $this->factory->course->create();
		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route . '/' . $course_id . '4' ) );

		// the course doesn't exists.
		$this->assertEquals( 404, $response->get_status() );

	}

	/**
	 * Test creating a single course.
	 *
	 * @since [version]
	 */
	public function test_create_course() {

		wp_set_current_user( $this->user_allowed );

		$request = new WP_REST_Request( 'POST', $this->route );

		$catalog_visibility = array_keys( llms_get_product_visibility_options() )[2];
		$sample_course_args = array_merge(
			$this->sample_course_args,
			array(
				'catalog_visibility' => $catalog_visibility,
				'instructors'        => array(
					get_current_user_id(),
					$this->factory->user->create(
						array(
							'role' => 'instructor',
						)
					),
				),
				'video_tile'         => true,
			)
		);

		$request->set_body_params( $sample_course_args );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 201, $response->get_status() );

		$res_data = $response->get_data();

		$this->assertEquals( $sample_course_args['title']['rendered'], $res_data['title']['rendered'] );
		/**
		 * The rtrim below is not ideal but at the moment we have templates printed after the course summary (e.g. prerequisites) that,
		 * even when printing no data they still print "\n". Let's pretend we're not interested in testing the trailing "\n" presence.
		 */
		$this->assertEquals( rtrim( $sample_course_args['content']['rendered'], "\n" ), rtrim( $res_data['content']['rendered'], "\n" ) );

		$this->assertEquals( $sample_course_args['date_created'], $res_data['date_created'] );
		$this->assertEquals( $sample_course_args['status'], $res_data['status'] );
		$this->assertEquals( $sample_course_args['catalog_visibility'], $res_data['catalog_visibility'] );
		$this->assertEquals( $sample_course_args['instructors'], $res_data['instructors'] );
		$this->assertEquals( $sample_course_args['video_tile'], $res_data['video_tile'] );

	}

	/**
	 * Test creating a single course special props.
	 * These props, when set, alter the rendered content so we test them separetaly.
	 *
	 * @since [version]
	 */
	public function test_create_course_special() {

		wp_set_current_user( $this->user_allowed );

		$request = new WP_REST_Request( 'POST', $this->route );

		$sample_course_args = array_merge(
			$this->sample_course_args,
			array(
				'audio_embed'            => 'https://www.youtube.com/abc',
				'video_embed'            => 'www.youtube.com/efg',
				'capacity_limit'         => 22,
				'capacity_enabled'       => true,
				'capacity_message'       => 'Enrollment has closed because the maximum number of allowed students has been reached.',
				'access_opens_date'      => '2019-05-22 17:20:05',
				'access_closes_date'     => '2019-05-22 17:23:08',
				'enrollment_opens_date'  => '2019-05-22 17:22:05',
				'enrollment_closes_date' => '2019-05-22 17:22:08',
			)
		);

		$request->set_body_params( $sample_course_args );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 201, $response->get_status() );

		$res_data = $response->get_data();

		$this->assertEquals( esc_url_raw( $sample_course_args['audio_embed'] ), $res_data['audio_embed'] );
		$this->assertEquals( esc_url_raw( $sample_course_args['video_embed'] ), $res_data['video_embed'] );
		$this->assertEquals( $sample_course_args['capacity_enabled'], $res_data['capacity_enabled'] );
		$this->assertEquals( do_shortcode( $sample_course_args['capacity_message'] ), $res_data['capacity_message']['rendered'] );
		$this->assertEquals( $sample_course_args['capacity_limit'], $res_data['capacity_limit'] );

		// No enrollments message set, hence we should see the default message.
		$this->assertEquals( 'Enrollment in this course opens on [lifterlms_course_info id=' . $res_data['id'] . ' key="enrollment_start_date"].', $res_data['enrollment_opens_message']['raw'] );
		$this->assertEquals( do_shortcode( 'Enrollment in this course opens on [lifterlms_course_info id=' . $res_data['id'] . ' key="enrollment_start_date"].' ), $res_data['enrollment_opens_message']['rendered'] );

		// Dates
		$this->assertEquals( $sample_course_args['access_opens_date'], $res_data['access_opens_date'] );
		$this->assertEquals( $sample_course_args['access_closes_date'], $res_data['access_closes_date'] );
		$this->assertEquals( $sample_course_args['enrollment_opens_date'], $res_data['enrollment_opens_date'] );
		$this->assertEquals( $sample_course_args['enrollment_closes_date'], $res_data['enrollment_closes_date'] );
	}

	/**
	 * Test creating a single course with taxonomies
	 *
	 * @since [version]
	 */
	public function test_create_course_with_taxonomies() {

		wp_set_current_user( $this->user_allowed );
		$taxonomies = array(
			'categories'   => array(
				1,
				2,
				3,
			),
			'tags'         => array(
				6,
				4,
				8,
			),
			'difficulties' => array(
				9,
			),
			'tracks'       => array(
				7,
				5,
				6,
			),
		);

		$course_args = array_merge(
			$this->sample_course_args,
			$taxonomies
		);

		$request = new WP_REST_Request( 'POST', $this->route );

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );

		// Terms have not ben created, I expect the course is created with empty taxonomies.
		$this->assertEquals( 201, $response->get_status() );

		$res_data = $response->get_data();

		foreach ( $taxonomies as $tax => $tid ) {
			$this->assertEquals( array(), $res_data[ $tax ] );
		}

		// let's create the terms.
		$taxonomies = array(
			'categories'   => $this->factory()->term->create_many(
				3,
				array(
					'taxonomy' => 'course_cat',
				)
			),
			'tags'         => $this->factory()->term->create_many(
				3,
				array(
					'taxonomy' => 'course_tag',
				)
			),
			'difficulties' => $this->factory()->term->create_many(
				1,
				array(
					'taxonomy' => 'course_difficulty',
				)
			),
			'tracks'       => $this->factory()->term->create_many(
				3,
				array(
					'taxonomy' => 'course_track',
				)
			),
		);

		$course_args = array_merge(
			$this->sample_course_args,
			$taxonomies
		);

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );

		// Terms have been created, I expect the course is created with taxonomies set.
		$this->assertEquals( 201, $response->get_status() );

		$res_data = $response->get_data();

		foreach ( $taxonomies as $tax => $tid ) {
			$this->assertEquals( $tid, $res_data[ $tax ] );
		}
	}

	/**
	 * Test producing bad request error when creating a single course.
	 *
	 * @since [version]
	 */
	public function test_create_course_bad_request() {

		wp_set_current_user( $this->user_allowed );

		$request = new WP_REST_Request( 'POST', $this->route );

		$course_args = $this->sample_course_args;

		// Creating a course passing an id produces a bad request.
		$course_args['id'] = '123';

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// create a course without title.
		$course_args = $this->sample_course_args;
		unset( $course_args['title'] );

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// create a course without content.
		$course_args = $this->sample_course_args;
		unset( $course_args['content'] );

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// status param must respect the item scehma, hence one of "publish" "pending" "draft" "auto-draft" "future" "private" "trash".
		$course_args           = $this->sample_course_args;
		$status                = array_merge( array_keys( get_post_statuses() ), array( 'future', 'trash', 'auto-draft' ) );
		$course_args['status'] = $status[0] . rand() . 'not_in_enum';

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// catalog_visibility param must respect the item schema, hence one of array_keys( llms_get_product_visibility_options() ).
		$course_args                       = $this->sample_course_args;
		$catalog_visibility                = array_keys( llms_get_product_visibility_options() );
		$course_args['catalog_visibility'] = $catalog_visibility[0] . rand() . 'not_in_enum';

		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

	}

	/**
	 * Test creating single course without permissions.
	 *
	 * @since [version]
	 */
	public function test_create_course_without_permissions() {

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', $this->route );

		$request->set_body_params( $this->sample_course_args );
		$response = $this->server->dispatch( $request );

		// Unhauthorized.
		$this->assertEquals( 401, $response->get_status() );

	}

	/**
	 * Test forbidden single course creation.
	 *
	 * @since [version]
	 */
	public function test_create_course_forbidden() {

		wp_set_current_user( $this->user_forbidden );

		$request = new WP_REST_Request( 'POST', $this->route );

		$request->set_body_params( $this->sample_course_args );
		$response = $this->server->dispatch( $request );

		// Forbidden.
		$this->assertEquals( 403, $response->get_status() );

	}


	/**
	 * Test updating a course.
	 *
	 * @since [version]
	 */
	public function test_update_course() {

		// create a course first.
		$course = $this->factory->course->create_and_get();

		wp_set_current_user( $this->user_allowed );

		// update.
		$update_data = array(
			'title'        => 'A TITLE UPDTAED',
			'content'      => '<p>CONTENT UPDATED</p>',
			'date_created' => '2019-05-22 17:22:05',
			'status'       => 'draft',
		);

		$request = new WP_REST_Request( 'POST', $this->route . '/' . $course->get( 'id' ) );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$res_data = $response->get_data();

		$this->assertEquals( $update_data['title'], $res_data['title']['rendered'] );
		$this->assertEquals( rtrim( apply_filters( 'the_content', $update_data['content'] ), "\n" ), rtrim( $res_data['content']['rendered'], "\n" ) );
		$this->assertEquals( $update_data['date_created'], $res_data['date_created'] );
		$this->assertEquals( $update_data['status'], $res_data['status'], $update_data['status'] );

	}

	/**
	 * Test updating a nonexistent course.
	 *
	 * @since [version]
	 */
	public function test_update_nonexistent_course() {

		wp_set_current_user( $this->user_allowed );

		$id = 48987456;

		$request     = new WP_REST_Request( 'POST', $this->route . '/' . $id );
		$course_args = $this->sample_course_args;
		$request->set_body_params( $course_args );
		$response = $this->server->dispatch( $request );
		$res_data = $response->get_data();

		// Not found.
		$this->assertEquals( 404, $response->get_status() );

	}

	/**
	 * Test forbidden single course update.
	 *
	 * @since [version]
	 */
	public function test_update_forbidden_course() {

		// create a course first.
		$course = $this->factory->course->create_and_get();

		wp_set_current_user( $this->user_forbidden );

		$request = new WP_REST_Request( 'POST', $this->route . '/' . $course->get( 'id' ) );

		$request->set_body_params( $this->sample_course_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 403, $response->get_status() );

	}

	/**
	 * Test single course update without authorization.
	 *
	 * @since [version]
	 */
	public function test_update_course_without_authorization() {

		// create a course first.
		$course = $this->factory->course->create_and_get();

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', $this->route . '/' . $course->get( 'id' ) );

		$request->set_body_params( $this->sample_course_args );
		$response = $this->server->dispatch( $request );

		// Unauthorized.
		$this->assertEquals( 401, $response->get_status() );

	}

	/**
	 * Test deleting a single course.
	 *
	 * @since [version]
	 */
	public function test_delete_course() {

		wp_set_current_user( $this->user_allowed );

		// create a course first.
		$course = $this->factory->course->create_and_get();

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $course->get( 'id' ) );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// check the deleted post is the correct one.
		$this->courses_fields_match( $course, $response->get_data()['previous'], $with_modified_date = false );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->route . '/' . $course->get( 'id' ) ) );

		// Cannot find just deleted post.
		$this->assertEquals( 404, $response->get_status() );

	}

	/**
	 * Test deleting a nonexistent single course.
	 *
	 * @since [version]
	 */
	public function test_delete_nonexistent_course() {

		wp_set_current_user( $this->user_allowed );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/747484940' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		// Post not found.
		$this->assertEquals( 404, $response->get_status() );

	}


	/**
	 * Test single course update without authorization.
	 *
	 * @since [version]
	 */
	public function test_delete_forbidden_course() {

		// create a course first.
		$course = $this->factory->course->create_and_get();

		wp_set_current_user( $this->user_forbidden );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $course->get( 'id' ) );

		$response = $this->server->dispatch( $request );

		// Forbidden.
		$this->assertEquals( 403, $response->get_status() );

	}

	/**
	 * Test single course deletion without authorization.
	 *
	 * @since [version]
	 */
	public function test_delete_course_without_authorization() {

		// create a course first.
		$course = $this->factory->course->create_and_get();

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $course->get( 'id' ) );

		$response = $this->server->dispatch( $request );

		// Unauthorized.
		$this->assertEquals( 401, $response->get_status() );

	}

	/**
	 * Utility to compare a Course with response course data.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Course $course       A LLMS_Course.
	 * @param array       $course_data  An array of course data.
	 * @param string      $context      Optional. Default 'view'.
	 * @return void
	 */
	private function courses_fields_match( $course, $course_data, $context = 'view' ) {

		$post = get_post( $course->get( 'post' ) );

		$expected = array(
			'id'               => $course->get( 'id' ),
			'title'            => array(
				'raw'      => $post->post_title,
				'rendered' => $course->get( 'title' ),
			),
			'status'           => $course->get( 'status' ),
			'content'          => array(
				'raw'      => $post->post_content,
				'rendered' => apply_filters( 'the_content', $course->get( 'content', 'raw' ) ),
			),
			'date_created'     => $course->get( 'date', 'Y-m-d H:i:s' ),
			'date_created_gmt' => $course->get( 'date_gmt', 'Y-m-d H:i:s' ),
			'date_updated'     => $course->get( 'modified', 'Y-m-d H:i:s' ),
			'date_updated_gmt' => $course->get( 'modified_gmt', 'Y-m-d H:i:s' ),
		);

		if ( 'edit' !== $context ) {
			unset(
				$expected['content']['raw'],
				$expected['excerpt']['raw'],
				$expected['title']['raw']
			);
		}

		/**
		 * The rtrim below is not ideal but at the moment we have templates printed after the course summary (e.g. prerequisites) that,
		 * even when printing no data they still print "\n". Let's pretend we're not interested in testing the trailing "\n" presence.
		 */
		foreach ( $expected as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					if ( 'content' === $key ) {
						$this->assertEquals( rtrim( $v, "\n" ), rtrim( $course_data[ $key ][ $k ], "\n" ) );
					}
				}
			} else {
				if ( 'content' === $key ) {
					$this->assertEquals( rtrim( $value, "\n" ), rtrim( $course_data[ $key ], "\n" ) );
				}
			}
		}

	}

}
