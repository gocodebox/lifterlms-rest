<?php
/**
 * Tests for Lessons API.
 *
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group REST
 * @group rest_lessons
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Lessons extends LLMS_REST_Unit_Test_Case_Posts {

	/**
	 * Route.
	 *
	 * @var string
	 */
	private $route = '/llms/v1/lessons';

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

		$this->endpoint = new LLMS_REST_Lessons_Controller();

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
	 * Test the item schema.
	 *
	 * @since [version]
	 */
	public function test_get_item_schema() {

		$schema = $this->endpoint->get_item_schema();

		$this->assertEquals( 'lesson', $schema['title'] );

		$props = array(
			'id',
			'audio_embed',
			'assignment',
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

		$schema_keys = array_keys( $schema['properties'] );
		sort( $schema_keys );
		sort( $props );

		$this->assertEquals( $props, $schema_keys );

		// check nested items.
		$assignment_quiz_nested = array(
			'enabled',
			'id',
			'progression'
		);
		$this->assertEquals( $assignment_quiz_nested, array_keys( $schema['properties']['assignment']['properties'] ) );
		$this->assertEquals( $assignment_quiz_nested, array_keys( $schema['properties']['quiz']['properties'] ) );

	}

	/**
	 * Test getting items.
	 *
	 * @since [version]
	 */
	public function test_get_items_success() {
		wp_set_current_user( $this->user_allowed );

		// create 3 courses with 3 lessons per course.
		$courses = $this->factory->course->create_many( 3, array( 'sections' => 1 , 'lessons' => 3 ) );

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
	 * @since [version]
	 */
	public function test_get_items_filter_by_parent() {
		wp_set_current_user( $this->user_allowed );

		// create a course with 3 sections and two lessons per section.
		$course = $this->factory->course->create( array( 'sections' => 3, 'lessons' => 2 ) );

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
	 * Test getting lessons filtered by section's parent.
	 *
	 * @since [version]
	 */
	public function test_get_items_orderby_order() {

		wp_set_current_user( $this->user_allowed );

		// create a course with 1 section and three lessons per section.
		$course = $this->factory->course->create( array( 'sections' => 1, 'lessons' => 3 ) );

		$course_obj = new LLMS_Course( $course );
		$lessons    = $course_obj->get_lessons('ids');

		// By default lessons are ordered by id
		$response = $this->perform_mock_request( 'GET', $this->route );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( $lessons, wp_list_pluck( $res_data, 'id' ) );

		// Set first lesson order to 8 and second to 10 so that, when ordered by 'order' ASC the collection will be [last, first, second]
		$first_lesson  = llms_get_post($lessons[0]);
		$second_lesson = llms_get_post($lessons[1]);
		$last_lesson   = llms_get_post($lessons[2]);
		$first_lesson->set( 'order', 8 );
		$second_lesson->set( 'order', 10 );

		$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'orderby' => 'order' ) );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( array( $lessons[2], $lessons[0], $lessons[1] ), wp_list_pluck( $res_data, 'id' ) );

		// Check DESC order works as well, we expect [second, first, last]
		$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'orderby' => 'order', 'order' => 'desc' ) );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( array( $lessons[1], $lessons[0], $lessons[2] ), wp_list_pluck( $res_data, 'id' ) );
	}

	// public function test_get_items_orderby_date_created() {}
	// public function test_get_items_orderby_date_updated() {}
	// public function test_get_items_pagination() {}

	// public function test_create_item_success() {}
	// public function test_create_item_missing_required() {}
	// public function test_create_item_auth_errors() {}

	// public function test_get_item_success() {}
	// public function test_get_item_auth_errors() {}
	// public function test_get_item_not_found() {}

	// public function test_update_item_success() {}
	// public function test_update_item_auth_errors() {}
	// public function test_update_item_errors() {}


	// public function test_delete_item_success() {}
	// public function test_get_item_auth_errors() {}


	/**
	 * Test links.
	 *
	 * @since [version]
	 */
    public function test_links() {

		// create course with 1 section and 3 lessons per section.
		$course = $this->factory->course->create_and_get( array( 'sections' => 1, 'lessons' => 3 ) );

		$course_obj  = new LLMS_Course( $course );
		$lessons     = $course_obj->get_lessons();

		wp_set_current_user( $this->user_allowed );

		$i = 0;
		foreach ( $lessons as $lesson ) {

			/**
			 * Latest leson: remove the quiz.
			 */
			if ( 3 === $i ) {
				$lesson->set( 'quiz_enabled', 'no' );
			}

			$response = $this->perform_mock_request( 'GET',  $this->route . '/' . $lesson->get( 'id' )  );

			switch ( $i++ ):
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
	 * @since [version]
	 */
	protected function filter_expected_fields( $expected, $llms_post ) {
		// Parent section.
		$expected['parent_id'] = $llms_post->get_parent_section();

		// Parent course.
		$expected['course_id'] = $llms_post->get_parent_course();

		// Order.
		$expected['order'] = $llms_post->get( 'order' );

		// Public.
		$expected['public'] = $llms_post->is_free();

		// Points.
		$expected['points'] = $llms_post->get( 'points' );

		return $expected;
	}

}
