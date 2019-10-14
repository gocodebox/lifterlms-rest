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

		$this->sample_membership_args = array(
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
	 * Test getting items.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @return void
	 */
	public function test_get_items_success() {

		wp_set_current_user( $this->user_allowed );

		// create 9 memberships
		$memberships = $this->factory->post->create_many( 9, array( 'post_type' => 'llms_membership' ) );
		$response    = $this->perform_mock_request( 'GET', $this->route );

		// Success.
		$this->assertResponseStatusEquals( 200, $response );

		$res_data = $response->get_data();
		$this->assertEquals( 9, count( $res_data ) );

		// Check retrieved lessons are the same as the generated ones.
		foreach ( $memberships as $membership ) {
			$membership_obj = new LLMS_Membership( $membership );
			$this->llms_posts_fields_match( $membership_obj, $res_data );
		}

	}


	// public function test_get_items_exclude() {}
	// public function test_get_items_include() {}
	// public function test_get_items_orderby_id() {}
	// public function test_get_items_orderby_title() {}

	/**
	 * Test getting memberships orderby `menu_order`.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_items_orderby_menu_order() {

		wp_set_current_user( $this->user_allowed );

		// create 3 memberships.
		$memberships = $this->factory->post->create_many( 3, array( 'post_type' => 'llms_membership' ) );

		// By default lessons are ordered by id.
		$response = $this->perform_mock_request( 'GET', $this->route );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( $memberships, wp_list_pluck( $res_data, 'id' ) );

		// Set first membership order to 8 and second to 10 so that, when ordered by 'menu_order' ASC the collection will be [last, first, second]
		$first_membership  = llms_get_post( $memberships[0] );
		$second_membership = llms_get_post( $memberships[1] );
		$last_membership   = llms_get_post( $memberships[2] );
		$first_membership->set( 'menu_order', 8 );
		$second_membership->set( 'menu_order', 10 );

		$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'orderby' => 'menu_order' ) );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( array( $memberships[2], $memberships[0], $memberships[1] ), wp_list_pluck( $res_data, 'id' ) );

		// Check DESC order works as well, we expect [second, first, last].
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'orderby' => 'menu_order',
				'order'   => 'desc',
			)
		);
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$res_data = $response->get_data();
		$this->assertEquals( array( $memberships[1], $memberships[0], $memberships[2] ), wp_list_pluck( $res_data, 'id' ) );

	}

	/**
	 * Test get memberships with pagination.
	 *
	 * @since [version]
	 */
	public function test_get_items_pagination() {

		wp_set_current_user( $this->user_allowed );

		$membership_ids      = $this->factory->post->create_many( 25, array( 'post_type' => 'llms_membership' ) );
		$start_membership_id = $membership_ids[0];
		$this->pagination_test( $this->route, $start_membership_id );

	}

	/**
	 * Test creating membership
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_create_item_success() {

		wp_set_current_user( $this->user_allowed );

		// create two courses to autoenroll.
		$course_ids = $this->factory->course->create_many( 2, array( 0, 0, 0, 0 ) );

		// no instruct
		$sample_membership_additional = array(
			'restriction_action' => 'custom',
			'restriction_url'    => 'https://example.tld/my-custom-url',
			'auto_enroll'        => $course_ids,
		);


		$sample_membership_args = array_merge(
			$this->sample_membership_args,
			$sample_membership_additional
		);

		$res = $this->perform_mock_request( 'POST', $this->route, $sample_membership_args );

		// Success.
		$this->assertResponseStatusEquals( 201, $res );
		$res_data   = $res->get_data();
		$membership = new LLMS_Membership( $res_data['id'] );

		// Check fields.
		$this->llms_posts_fields_match( $membership, $res_data );

		foreach ( $sample_membership_additional as $key => $value ) {
			$this->assertEquals( $value, $res_data[ $key ] );
		}

		// Check some defaults.
		// check instructor == author (default).
		$this->assertEquals( $membership->get( 'author' ), wp_list_pluck( $membership->get_instructors(), 'id' )[0] );
		$this->assertEquals( $membership->get( 'author' ), $res_data['instructors'][0] );

		// check that even if the membership has been created with no restriction message,
		// the `restriction_add_notice` property post property is 'yes'.
		$this->assertEquals( 'yes', $membership->get( 'restriction_add_notice' ) );

		// Check anyways the default `restriction_message` has been correctly created.
		$this->assertEquals( 'You must belong to the [lifterlms_membership_link id="' . $res_data['id'] . '" membership to access this content.', $res_data['restriction_message']['raw'] );
		$this->assertEquals( do_shortcode( 'You must belong to the [lifterlms_membership_link id="' . $res_data['id'] . '" membership to access this content.' ), $res_data['restriction_message']['rendered'] );
		$this->assertEquals( $membership->get( 'restriction_notice', true ), $res_data['restriction_message']['raw'] );

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
		$membership =  $this->factory->post->create( array( 'post_type' => 'llms_membership' ) );
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
