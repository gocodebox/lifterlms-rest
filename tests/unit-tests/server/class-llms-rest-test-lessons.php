<?php
/**
 * Tests for Lessons API.
 *
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group REST
 * @group rest_lessons
 *
 * @since 1.0.0-beta.7
 * @version 1.0.0-beta.7
 */
class LLMS_REST_Test_Lessons extends LLMS_REST_Unit_Test_Case_Posts {

	/**
	 * Route.
	 *
	 * @var string
	 */
	protected $route = '/llms/v1/lessons';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'lesson';


	/**
	 * Array of link $rels expected for each item.
	 *
	 * @var array
	 */
	private $expected_link_rels = array( 'self', 'collection', 'course', 'parent', 'siblings', 'next', 'previous' );

	/**
	 * Array of schema properties.
	 *
	 * @var array
	 */
	private $schema_props = array(
		'id',
		'audio_embed',
		'comment_status',
		'content',
		'course_id',
		'date_created',
		'date_created_gmt',
		'date_updated',
		'date_updated_gmt',
		'drip_date',
		'drip_days',
		'drip_method',
		'excerpt',
		'featured_media',
		'menu_order',
		'order',
		'parent_id',
		'password',
		'permalink',
		'ping_status',
		'points',
		'post_type',
		'prerequisite',
		'public',
		'quiz',
		'slug',
		'status',
		'title',
		'video_embed',
	);

	/**
	 * Setup our test server, endpoints, and user info.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
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

		$this->sample_lesson = array(
			'title'        => array(
				'rendered' => 'Getting Started with LifterLMS',
				'raw'      => 'Getting Started with LifterLMS',
			),
			'content'      => array(
				'rendered' => "\\n<h2>Lorem ipsum dolor sit amet.</h2>\\n\\n\\n\\n<p>Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>\\n",
				'raw'      => "<!-- wp:heading -->\\n<h2>Lorem ipsum dolor sit amet.</h2>\\n<!-- /wp:heading -->\\n\\n<!-- wp:paragraph -->\\n<p>Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>\\n<!-- /wp:paragraph -->",
			),
			'excerpt'      => array(
				'rendered' => '<p>Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>',
				'raw'      => 'Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.',
			),
			'date_created' => '2019-05-20 17:22:05',
			'status'       => 'publish',

		);

		$this->endpoint = new LLMS_REST_Lessons_Controller();

	}


	/**
	 * Test route registration.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_register_routes() {

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->route, $routes );
		$this->assertArrayHasKey( $this->route . '/(?P<id>[\d]+)', $routes );
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

		$this->assertEquals( 'lesson', $schema['title'] );

		$props = $this->schema_props;

		$schema_keys = array_keys( $schema['properties'] );
		sort( $schema_keys );
		sort( $props );

		$this->assertEquals( $props, $schema_keys );

		// check nested items.
		$quiz_nested = array(
			'enabled',
			'id',
			'progression',
		);

		$this->assertEquals( $quiz_nested, array_keys( $schema['properties']['quiz']['properties'] ) );

	}

	/**
	 * Test getting items.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_items_success() {

		wp_set_current_user( $this->user_allowed );

		// create 3 courses with 3 lessons per course.
		$courses = $this->factory->course->create_many(
			3,
			array(
				'sections' => 1,
				'lessons'  => 3,
			)
		);

		$response = $this->perform_mock_request( 'GET', $this->route );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );

		$res_data = $response->get_data();
		$this->assertEquals( 9, count( $res_data ) );

		// Check parent course and parent section match.
		$i = 0;

		// Check retrieved lessons are the same as the generated ones.
		foreach ( $courses as $course ) {
			$course_obj = new LLMS_Course( $course );
			$lessons    = $course_obj->get_lessons();

			// Easy sequential check as lessons are by default ordered by id.
			$j = 0;
			foreach ( $lessons as $lesson ) {
				$res_lesson = $res_data[ ( $i * count( $lessons ) ) + $j ];
				$this->llms_posts_fields_match( $lesson, $res_lesson );
				$j++;
			}

			$i++;
		}

	}

	/**
	 * Test getting lessons filtered by section's parent.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_items_filter_by_parent() {
		wp_set_current_user( $this->user_allowed );

		// create a course with 3 sections and two lessons per section.
		$course = $this->factory->course->create(
			array(
				'sections' => 3,
				'lessons'  => 2,
			)
		);

		$course_obj = new LLMS_Course( $course );
		$sections   = $course_obj->get_sections();

		$i = 0;

		foreach ( $sections as $section ) {
			if ( 2 === $i++ ) {
				continue;
			}

			// filter by parent section.
			$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'parent' => $section->get( 'id' ) ) );

			// Success.
			$this->assertResponseStatusEquals( 200, $response );
			$res_data = $response->get_data();
			$this->assertEquals( 2, count( $res_data ) );

			$lessons = $section->get_lessons();

			// Easy sequential check as lesson are by default ordered by id.
			$j = 0;
			foreach ( $lessons as $lesson ) {

				$res_lesson = $res_data[ $j ];
				$this->llms_posts_fields_match( $lesson, $res_lesson );
				$j++;

			}
		}

		// Check filtering by a section id which doesn't exist.
		$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'parent' => $section->get( 'id' ) + 999 ) );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );

		// Expect an empty collection.
		$res_data = $response->get_data();
		$this->assertEquals( 0, count( $res_data ) );

	}

	// public function test_get_items_exclude() {}
	// public function test_get_items_include() {}
	// public function test_get_items_orderby_id() {}
	// public function test_get_items_orderby_title() {}

	/**
	 * Test getting lessons ordered by `order`.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_items_orderby_order() {

		wp_set_current_user( $this->user_allowed );

		// create a course with 1 section and three lessons per section.
		$course = $this->factory->course->create(
			array(
				'sections' => 1,
				'lessons'  => 3,
			)
		);

		$course_obj = new LLMS_Course( $course );
		$lessons    = $course_obj->get_lessons( 'ids' );

		// By default lessons are ordered by id.
		$response = $this->perform_mock_request( 'GET', $this->route );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( $lessons, wp_list_pluck( $res_data, 'id' ) );

		// Set first lesson order to 8 and second to 10 so that, when ordered by 'order' ASC the collection will be [last, first, second]
		$first_lesson  = llms_get_post( $lessons[0] );
		$second_lesson = llms_get_post( $lessons[1] );
		$last_lesson   = llms_get_post( $lessons[2] );
		$first_lesson->set( 'order', 8 );
		$second_lesson->set( 'order', 10 );

		$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'orderby' => 'order' ) );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( array( $lessons[2], $lessons[0], $lessons[1] ), wp_list_pluck( $res_data, 'id' ) );

		// Check DESC order works as well, we expect [second, first, last].
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'orderby' => 'order',
				'order'   => 'desc',
			)
		);
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( array( $lessons[1], $lessons[0], $lessons[2] ), wp_list_pluck( $res_data, 'id' ) );

	}

	// public function test_get_items_orderby_date_created() {}
	// public function test_get_items_orderby_date_updated() {}

	/**
	 * Test list lessons pagination.
	 *
	 * @since 1.0.0-beta.7
	 */
	public function test_get_items_pagination() {

		wp_set_current_user( $this->user_allowed );

		// create sections
		$course = $this->factory->course->create_and_get( array( 'sections' => 1, 'lessons' => 25 ) );
		$start_lesson_id = $course->get_lessons( 'ids' )[0];

		$this->pagination_test( $this->route, $start_lesson_id );

	}

	/**
	 * Test creating lesson.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_create_item_success() {

		wp_set_current_user( $this->user_allowed );

		// create a parent course, a prerequisite and a quiz.
		$course_id           = $this->factory->course->create(
			array(
				'sections' => 1,
				'lessons'  => 1,
				'quiz'     => 1,
			)
		);
		$course              = llms_get_post( $course_id );
		$prerequisite_lesson = $course->get_lessons()[0];
		$prerequisite        = $prerequisite_lesson->get( 'id' );
		$quiz                = $prerequisite_lesson->get( 'quiz' );

		$sample_lesson_additional = array(
			'parent_id'    => 0, // checks also orphaned lessons are available.
			'course_id'    => $course_id,
			'drip_date'    => '', // checks also drip date can be null.
			'order'        => 2,
			'public'       => true,
			'points'       => 3,
			'drip_days'    => 20,
			'drip_method'  => 'enrollment',
			'prerequisite' => $prerequisite,
			'quiz'         => array(
				'enabled'     => true,
				'id'          => $quiz,
				'progression' => 'pass',
			),
		);

		$sample_lesson = array_merge(
			$this->sample_lesson,
			$sample_lesson_additional
		);

		$res = $this->perform_mock_request( 'POST', $this->route, $sample_lesson );

		// Success.
		$this->assertResponseStatusEquals( 201, $res );
		$res_data = $res->get_data();
		$lesson   = new LLMS_Lesson( $res_data['id'] );

		// Check fields.
		$this->llms_posts_fields_match( $lesson, $res_data );

		foreach ( $sample_lesson_additional as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					$this->assertEquals( $v, $res_data[ $key ][ $k ] );
				}
			} else {
				$this->assertEquals( $value, $res_data[ $key ] );
			}
		}

		// Check that if we create a course with no prerequisite the $lessons->has_prerequisite() returns false
		unset( $sample_lesson['prerequisite'] );
		$res = $this->perform_mock_request( 'POST', $this->route, $sample_lesson );

		// Success.
		$this->assertResponseStatusEquals( 201, $res );
		$res_data = $res->get_data();
		$lesson   = new LLMS_Lesson( $res_data['id'] );

		$this->assertFalse( $lesson->has_prerequisite() );
	}

	/**
	 * Test creating lesson with wrong params.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	//public function test_create_item_wrong_params() {

	/**
	 * Test creating lesson missing required parameters.
	 *
	 * @since 1.0.0-beta.7
	 * @todo abstract and move in posts case.
	 * @return void
	 */
	public function test_create_item_missing_required() {

		$res = $this->perform_mock_request( 'POST', $this->route );
		$this->assertResponseStatusEquals( 400, $res );
		$this->assertResponseCodeEquals( 'rest_missing_callback_param', $res );
		$this->assertResponseMessageEquals( 'Missing parameter(s): title, content', $res );

	}

	/**
	 * Test creating lesson auth errors.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_create_item_auth_errors() {

		$res = $this->perform_mock_request( 'POST', $this->route, $this->sample_lesson );
		$this->assertResponseStatusEquals( 401, $res );
		$this->assertResponseCodeEquals( 'llms_rest_unauthorized_request', $res );

		wp_set_current_user( $this->user_forbidden );
		$res = $this->perform_mock_request( 'POST', $this->route, $this->sample_lesson );
		$this->assertResponseStatusEquals( 403, $res );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $res );

	}


	/**
	 * Retrieve success.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_item_success() {

		wp_set_current_user( $this->user_allowed );

		// create a course with 1 section and 1 lesson with no quizzes
		$course = $this->factory->course->create_and_get(
			array(
				'sections' => 1,
				'lessons'  => 1,
				'quiz'     => 0,
			)
		);
		$lesson = $course->get_lessons()[0];
		$res    = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $lesson->get( 'id' ) ) );

		$this->assertResponseStatusEquals( 200, $res );
		$res_data = $res->get_data();

		// check that created and the retrieved lessons match.
		$this->llms_posts_fields_match( $lesson, $res_data );

		// check that the retrieved lesson has exactly the fields we expect.
		$props = $this->schema_props;

		// we're not in edit context so 'password' property won't be returned
		$props = array_diff( $props, array( 'password' ) );

		$res_data_keys = array_keys( $res_data );
		sort( $res_data_keys );
		sort( $props );

		$this->assertEquals( $props, $res_data_keys );

		// check nested items.
		$quiz_nested = array(
			'enabled',
			'id',
			'progression',
		);

		$this->assertEquals( $quiz_nested, array_keys( $res_data['quiz'] ) );

	}

	/**
	 * Test getting an item with no auth.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_item_auth_errors() {

		// create a course with 1 section and 1 lesson with no quizzes
		$course = $this->factory->course->create_and_get(
			array(
				'sections' => 1,
				'lessons'  => 1,
				'quiz'     => 0,
			)
		);
		$res    = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $course->get_lessons( 'ids' )[0] ) );
		$this->assertResponseStatusEquals( 401, $res );
		$this->assertResponseCodeEquals( 'llms_rest_unauthorized_request', $res );

		wp_set_current_user( $this->user_forbidden );

		$res = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, $course->get_lessons( 'ids' )[0] ) );
		$this->assertResponseStatusEquals( 403, $res );
		$this->assertResponseCodeEquals( 'llms_rest_forbidden_request', $res );

	}

	/**
	 * Test not found lesson.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_item_not_found() {

		wp_set_current_user( $this->user_allowed );

		$res = $this->perform_mock_request( 'GET', sprintf( '%1$s/%2$d', $this->route, 1234 ) );
		$this->assertResponseStatusEquals( 404, $res );
		$this->assertResponseCodeEquals( 'llms_rest_not_found', $res );

	}

	// public function test_update_item_success() {}
	// public function test_update_item_auth_errors() {}
	// public function test_update_item_errors() {}


	// public function test_delete_item_success() {}
	// public function test_get_item_auth_errors() {}


	/**
	 * Test links.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_links() {

		// create course with 1 section and 3 lessons per section.
		$course = $this->factory->course->create_and_get(
			array(
				'sections' => 1,
				'lessons'  => 3,
			)
		);

		$course_obj = new LLMS_Course( $course );
		$lessons    = $course_obj->get_lessons();

		wp_set_current_user( $this->user_allowed );

		$i = 0;
		foreach ( $lessons as $lesson ) {

			/**
			 * Latest lesson: remove the quiz.
			 */
			if ( 3 === $i ) {
				$lesson->set( 'quiz_enabled', 'no' );
			}

			$response = $this->perform_mock_request( 'GET', $this->route . '/' . $lesson->get( 'id' ) );

			switch ( $i++ ) :
				case 0:
					$expected_link_rels = array_values( array_diff( $this->expected_link_rels, array( 'previous' ) ) );
					break;
				case 2:
					$expected_link_rels = array_values( array_diff( $this->expected_link_rels, array( 'next' ) ) );
					break;
				default:
					$expected_link_rels = $this->expected_link_rels;
			endswitch;

			if ( $lesson->is_quiz_enabled() ) {
				$expected_link_rels[] = 'quiz';
			}

			$this->assertEquals( $expected_link_rels, array_keys( $response->get_links() ) );

		}

	}

	/**
	 * Override.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @param $expected array Array of expected properties.
	 * @param $lesson LLMS_Post Instance of LLMS_Post.
	 * @return array
	 */
	protected function filter_expected_fields( $expected, $lesson ) {

		// Audio Embed.
		$expected['audio_embed'] = $lesson->get( 'audio_embed' );

		// Video Embed.
		$expected['video_embed'] = $lesson->get( 'video_embed' );

		// Parent section.
		$expected['parent_id'] = $lesson->get_parent_section();

		// Parent course.
		$expected['course_id'] = $lesson->get_parent_course();

		// Order.
		$expected['order'] = $lesson->get( 'order' );

		// Public.
		$expected['public'] = $lesson->is_free();

		// Points.
		$expected['points'] = $lesson->get( 'points' );

		// Quiz.
		$expected['quiz']['enabled']     = llms_parse_bool( $lesson->get( 'quiz_enabled' ) );
		$expected['quiz']['id']          = absint( $lesson->get( 'quiz' ) );
		$expected['quiz']['progression'] = llms_parse_bool( $lesson->get( 'require_passing_grade' ) ) ? 'pass' : 'completed';

		// Drip method.
		$expected['drip_method'] = $lesson->get( 'drip_method' );
		$expected['drip_method'] = $expected['drip_method'] ? $expected['drip_method'] : 'none';

		// Drip days.
		$expected['drip_days'] = absint( $lesson->get( 'days_before_available' ) );

		// Drip date.
		$date = $lesson->get( 'date_available' );
		if ( $date ) {
			$time = $lesson->get( 'time_available' );

			if ( ! $time ) {
				$time = '12:00 AM';
			}

			$drip_date = date_i18n( 'Y-m-d H:i:s', strtotime( $date . ' ' . $time ) );
		} else {
			$drip_date = '';
		}

		$expected['drip_date'] = $drip_date;

		// Prerequisite.
		$expected['prerequisite'] = absint( $lesson->get_prerequisite() );

		return $expected;
	}

}
