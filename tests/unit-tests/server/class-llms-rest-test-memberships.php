<?php
/**
 * Tests for Memberships API.
 *
 * @since   [version]
 * @package LifterLMS_Rest/Tests/Controllers
 *
 * @group   REST
 * @group   rest_memberships
 *
 * @version [version]
 *
 * @todo    update tests to check links.
 * @todo    do more tests on the membership update/delete.
 */
class LLMS_REST_Test_Memberships extends LLMS_REST_Unit_Test_Case_Posts {
	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'membership';

	/**
	 * Route.
	 *
	 * @var string
	 */
	protected $route = '/llms/v1/memberships';

	/**
	 * Arguments to membership API calls.
	 *
	 * @var array
	 */
	protected $sample_membership_args = array();

	/**
	 * @since [version]
	 * @var array
	 */
	private $schema_properties = array(
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
		'id',
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
	 * @since [version]
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint       = new LLMS_REST_Memberships_Controller();
		$this->user_allowed   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->user_forbidden = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$this->sample_membership_args = array(
			'title'        => array(
				'rendered' => 'Gold',
				'raw'      => 'Gold',
			),
			'content'      => array(
				'rendered' => "\\n<h2>Lorem ipsum dolor sit amet.</h2>\\n\\n\\n\\n<p>Expectoque quid ad id, quod quaerebam, respondeas. " .
				              "Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>\\n",
				'raw'      => "<!-- wp:heading -->\\n<h2>Lorem ipsum dolor sit amet.</h2>\\n<!-- /wp:heading -->\\n\\n<!-- wp:paragraph -->\\n<p>" .
				              "Expectoque quid ad id, quod quaerebam, respondeas. Nec enim, " .
				              "omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus.</p>\\n<!-- /wp:paragraph -->",
			),
			'date_created' => '2019-05-20 17:22:05',
			'status'       => 'publish',
		);
	}

	/**
	 * Test creating a single membership.
	 *
	 * @since [version]
	 */
	public function test_create_membership() {
		wp_set_current_user( $this->user_allowed );

		$membership_args = array_merge(
			$this->sample_membership_args,
			array(
				'auto_enroll'         => array(
					$this->factory->course->create()
				),
				// Categories are tested in test_create_membership_with_taxonomies().
				'catalog_visibility'  => 'search',
				'instructors'         => array(
					get_current_user_id(),
					$this->factory->user->create( array( 'role' => 'instructor', ) ),
				),
				'restriction_action'  => 'none',
				'restriction_message' => 'You must belong to the [lifterlms_membership_link id="{{membership_id}}"] ' .
				                         'membership to access this content.',
				'restriction_page_id' => 0,
				'restriction_url'     => '',
				'sales_page_page_id'  => 0,
				'sales_page_type'     => 'none',
				'sales_page_url'      => '',
				// Tags are tested in test_create_membership_with_taxonomies().
			)
		);

		$response = $this->perform_mock_request( 'POST', $this->route, $membership_args );

		// Success.
		$this->assertEquals( 201, $response->get_status() );

		$response_data = $response->get_data();

		/**
		 * The rtrim below is not ideal but at the moment we have templates printed after the membership summary (e.g. prerequisites) that,
		 * even when printing no data they still print "\n". Let's pretend we're not interested in testing the trailing "\n" presence.
		 */
		$this->assertEquals( rtrim( $membership_args['content']['rendered'], "\n" ), rtrim( $response_data['content']['rendered'], "\n" ) );

		$properties = array(
			'auto_enroll',
			'catalog_visibility',
			'instructors',
			'date_created',
			'status',
			'restriction_action',
			'restriction_page_id',
			'restriction_url',
			'sales_page_page_id',
			'sales_page_type',
			'sales_page_url',
			'title',
		);
		foreach ( $properties as $property ) {
			$this->assertEquals( $membership_args[ $property ], $response_data[ $property ] );
		}

		$restriction_message_raw = str_replace( '{{membership_id}}', $response_data['id'], $membership_args['restriction_message'] );
		$this->assertEquals( $restriction_message_raw, $response_data['restriction_message']['raw'] );
		$this->assertEquals( do_shortcode( $restriction_message_raw ), $response_data['restriction_message']['rendered'] );
	}

	/**
	 * Test create membership with raw properties.
	 * Check textual properties are still set when supplying them as 'raw'.
	 *
	 * @since [version]
	 */
	public function test_create_membership_and_raws() {
		wp_set_current_user( $this->user_allowed );

		$membership_raw_messages = array(
			'restriction_message' => array(
				'raw' => 'Restriction raw message',
			),
		);
		$membership_args         = array_merge(
			$this->sample_membership_args,
			$membership_raw_messages
		);

		$response      = $this->perform_mock_request( 'POST', $this->route, $membership_args );
		$response_data = $response->get_data();

		foreach ( $membership_raw_messages as $property => $content ) {
			$this->assertEquals( $content['raw'], $response_data[ $property ]['raw'] );
		}
	}

	/**
	 * Test producing bad request error when creating a single membership.
	 *
	 * @since [version]
	 */
	public function test_create_membership_bad_request() {
		wp_set_current_user( $this->user_allowed );

		$membership_args = $this->sample_membership_args;

		// Creating a membership passing an id produces a bad request.
		$membership_args['id'] = '123';

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// Create a membership without title.
		$membership_args = $this->sample_membership_args;
		unset( $membership_args['title'] );

		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// Create a membership without content.
		$membership_args = $this->sample_membership_args;
		unset( $membership_args['content'] );

		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// Status param must respect the item schema, hence one of "publish" "pending" "draft" "auto-draft" "future" "private" "trash".
		$membership_args           = $this->sample_membership_args;
		$status                    = array_merge( array_keys( get_post_statuses() ), array( 'future', 'trash', 'auto-draft' ) );
		$membership_args['status'] = $status[0] . rand() . 'not_in_enum';

		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );

		// catalog_visibility param must respect the item schema, hence one of array_keys( llms_get_product_visibility_options() ).
		$membership_args                       = $this->sample_membership_args;
		$catalog_visibility                    = array_keys( llms_get_product_visibility_options() );
		$membership_args['catalog_visibility'] = $catalog_visibility[0] . rand() . 'not_in_enum';

		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );
		// Bad request.
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating a single membership defaults are correctly set.
	 *
	 * @since [version]
	 */
	public function test_create_membership_check_defaults() {
		wp_set_current_user( $this->user_allowed );

		$membership_args = array(
			'title'   => 'Gold',
			'content' => 'Content',
		);

		$response      = $this->perform_mock_request( 'POST', $this->route, $membership_args );
		$response_data = $response->get_data();

		// Check defaults.
		// Auto enroll.
		$this->assertEquals( array(), $response_data['auto_enroll'] );

		// Catalog visibility.
		$this->assertEquals( 'catalog_search', $response_data['catalog_visibility'] );

		// Categories.
		$this->assertEquals( array(), $response_data['categories'] );

		// Comment status.
		$this->assertEquals( 'open', $response_data['comment_status'] );

		// Instructors. If empty, llms core responds with the current user id in an array.
		$this->assertEquals( array( get_current_user_id() ), $response_data['instructors'] );

		// Menu order.
		$this->assertEquals( 0, $response_data['menu_order'] );

		// Ping status.
		$this->assertEquals( 'open', $response_data['ping_status'] );

		// Restriction action.
		$this->assertEquals( 'none', $response_data['restriction_action'] );

		// Restriction message.
		$restriction_message_raw = 'You must belong to the [lifterlms_membership_link id="' . $response_data['id'] . '"] ' .
		                           'membership to access this content.';
		$this->assertEquals( $restriction_message_raw, $response_data['restriction_message']['raw'] );
		$this->assertEquals( do_shortcode( $restriction_message_raw ), $response_data['restriction_message']['rendered'] );

		// Sales page type.
		$this->assertEquals( 'none', $response_data['sales_page_type'] );

		// Status.
		$this->assertEquals( 'publish', $response_data['status'] );

		// Tags.
		$this->assertEquals( array(), $response_data['tags'] );
	}

	/**
	 * Test forbidden single membership creation.
	 *
	 * @since [version]
	 */
	public function test_create_membership_forbidden() {
		wp_set_current_user( $this->user_forbidden );

		$response = $this->perform_mock_request( 'POST', $this->route, $this->sample_membership_args );

		// Forbidden.
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test creating a single membership special props.
	 * These props, when set, alter the rendered content so we test them separately.
	 *
	 * @since [version]
	 */
	/*	public function test_create_membership_special() {
			wp_set_current_user( $this->user_allowed );

			$membership_args = array_merge(
				$this->sample_membership_args,
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
			$response = $this->perform_mock_request( 'POST', $this->route, $membership_args );

			// Success.
			$this->assertEquals( 201, $response->get_status() );

			$response_data = $response->get_data();

			$this->assertEquals( esc_url_raw( $sample_membership_args['audio_embed'] ), $response_data['audio_embed'] );
			$this->assertEquals( esc_url_raw( $sample_membership_args['video_embed'] ), $response_data['video_embed'] );
			$this->assertEquals( $sample_membership_args['capacity_enabled'], $response_data['capacity_enabled'] );
			$this->assertEquals( do_shortcode( $sample_membership_args['capacity_message'] ), $response_data['capacity_message']['rendered'] );
			$this->assertEquals( $sample_membership_args['capacity_limit'], $response_data['capacity_limit'] );

			// Dates.
			$this->assertEquals( $sample_membership_args['access_opens_date'], $response_data['access_opens_date'] );
			$this->assertEquals( $sample_membership_args['access_closes_date'], $response_data['access_closes_date'] );
			$this->assertEquals( $sample_membership_args['enrollment_opens_date'], $response_data['enrollment_opens_date'] );
			$this->assertEquals( $sample_membership_args['enrollment_closes_date'], $response_data['enrollment_closes_date'] );
		}*/

	/**
	 * Test creating a membership with an instructor that doesn't exist.
	 *
	 * @since [version]
	 */
	public function test_create_membership_with_bad_instructor() {
		wp_set_current_user( $this->user_allowed );

		// Create instructor.
		$good_instructor_id = $this->factory->user->create( array( 'role' => 'instructor' ) );
		$bad_instructor_id  = $good_instructor_id + 9;
		$membership_args    = $this->sample_membership_args;

		// Create membership with non-existing instructor.
		$membership_args['instructors'] = array( $bad_instructor_id );
		$response                       = $this->perform_mock_request( 'POST', $this->route, $membership_args );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualSets( array( $this->user_allowed ), $response->data['instructors'] );

		// Create membership with existing instructor and non-existing instructor.
		$membership_args['instructors'] = array( $good_instructor_id, $bad_instructor_id );
		$response                       = $this->perform_mock_request( 'POST', $this->route, $membership_args );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualSets( array( $good_instructor_id ), $response->data['instructors'] );

		// Create membership with non-existing instructor and existing instructor.
		$membership_args['instructors'] = array( $bad_instructor_id, $good_instructor_id );
		$response                       = $this->perform_mock_request( 'POST', $this->route, $membership_args );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualSets( array( $good_instructor_id ), $response->data['instructors'] );
	}

	/**
	 * Test creating a membership with an empty instructors array.
	 *
	 * @since [version]
	 */
	public function test_create_membership_with_empty_instructors() {
		wp_set_current_user( $this->user_allowed );

		// Create membership with empty instructors.
		$membership_args                = $this->sample_membership_args;
		$membership_args['instructors'] = array();
		$response = $this->perform_mock_request( 'POST', $this->route, $membership_args );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualSets( array( $this->user_allowed ), $response->data['instructors'] );
	}

	/**
	 * Test creating a single membership with taxonomies.
	 *
	 * @since [version]
	 */
	public function test_create_membership_with_taxonomies() {
		wp_set_current_user( $this->user_allowed );
		$taxonomies = array(
			'categories' => array(
				1,
				2,
				3,
			),
			'tags'       => array(
				6,
				4,
				8,
			),
		);

		$membership_args = array_merge(
			$this->sample_membership_args,
			$taxonomies
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );

		// Terms have not ben created. We expect the membership is created with empty taxonomies.
		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();
		foreach ( $taxonomies as $tax => $tid ) {
			$this->assertEquals( array(), $response_data[ $tax ] );
		}

		// let's create the terms.
		$taxonomies      = array(
			'categories' => $this->factory()->term->create_many(
				3,
				array(
					'taxonomy' => 'membership_cat',
				)
			),
			'tags'       => $this->factory()->term->create_many(
				3,
				array(
					'taxonomy' => 'membership_tag',
				)
			),
		);
		$membership_args = array_merge(
			$this->sample_membership_args,
			$taxonomies
		);
		$request->set_body_params( $membership_args );
		$response = $this->server->dispatch( $request );

		// Terms have been created. We expect the membership is created with taxonomies set.
		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();
		foreach ( $taxonomies as $tax => $tid ) {
			$this->assertEquals( $tid, $response_data[ $tax ] );
		}
	}

	/**
	 * Test creating single membership without permissions.
	 *
	 * @since [version]
	 */
	public function test_create_membership_without_permissions() {
		wp_set_current_user( 0 );

		$response = $this->perform_mock_request( 'POST', $this->route, $this->sample_membership_args );

		// Unauthorized.
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting bad request response when deleting a membership.
	 *
	 * @since [version]
	 */
	public function test_delete_bad_request_membership() {
		wp_set_current_user( $this->user_allowed );

		// create a membership first.
		$membership = $this->factory->membership->create_and_get();

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $membership->get( 'id' ) );
		$request->set_param( 'force', 'bad_parameter_value' );
		$response = $this->server->dispatch( $request );

		// Bad request because of a bad parameter.
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test single membership update without authorization.
	 *
	 * @since [version]
	 */
	public function test_delete_forbidden_membership() {
		// create a membership first.
		$membership = $this->factory->membership->create_and_get();

		wp_set_current_user( $this->user_forbidden );

		$response = $this->perform_mock_request( 'DELETE', $this->route . '/' . $membership->get( 'id' ) );

		// Forbidden.
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test deleting a single membership.
	 *
	 * @since [version]
	 */
	public function test_delete_membership() {
		wp_set_current_user( $this->user_allowed );

		// create a membership first.
		$membership = $this->factory->membership->create_and_get();

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $membership->get( 'id' ) );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 204, $response->get_status() );
		// empty body.
		$this->assertEquals( null, $response->get_data() );

		// Cannot find just deleted post.
		$this->assertFalse( get_post_status( $membership->get( 'id' ) ) );
	}

	/**
	 * Test single membership deletion without authorization.
	 *
	 * @since [version]
	 */
	public function test_delete_membership_without_authorization() {
		// Create a membership first.
		$membership = $this->factory->membership->create_and_get();

		wp_set_current_user( 0 );

		$response = $this->perform_mock_request( 'DELETE', $this->route . '/' . $membership->get( 'id' ) );

		// Unauthorized.
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a nonexistent single membership.
	 *
	 * @since [version]
	 */
	public function test_delete_nonexistent_membership() {
		wp_set_current_user( $this->user_allowed );

		// Setup membership.
		$membership_id = $this->factory->membership->create();

		$response = $this->perform_mock_request( 'DELETE', $this->route . '/' . $membership_id . '42' );

		// Post not found, so it's "deleted".
		$this->assertEquals( 204, $response->get_status() );
		$this->assertEquals( '', $response->get_data() );
	}

	/**
	 * Test the item schema.
	 *
	 * @since [version]
	 */
	public function test_get_item_schema() {
		$schema = $this->endpoint->get_item_schema();

		$this->assertEquals( 'llms_membership', $schema['title'] );

		$props = $this->schema_properties;

		$schema_keys = array_keys( $schema['properties'] );
		sort( $schema_keys );
		sort( $props );

		$this->assertEquals( $props, $schema_keys );
	}

	/**
	 * Test getting a single membership.
	 *
	 * @since [version]
	 */
	public function test_get_membership() {
		wp_set_current_user( $this->user_allowed );

		// Setup membership.
		$membership = $this->factory->membership->create_and_get();
		$response   = $this->perform_mock_request( 'GET', $this->route . '/' . $membership->get( 'id' ) );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		// Check retrieved membership matches the created ones.
		$response_data = $response->get_data();
		$this->llms_posts_fields_match( $membership, $response_data );
	}

	/**
	 * Test list membership content.
	 *
	 * @since [version]
	 *
	 * @todo  test order and orderby
	 */
	public function test_get_membership_enrollments() {
		wp_set_current_user( $this->user_allowed );

		// create 1 membership.
		$membership = $this->factory->membership->create();
		$response   = $this->perform_mock_request( 'GET', $this->route . '/' . $membership . '/enrollments' );

		// We have no students enrolled for this membership so we expect a 404.
		$this->assertEquals( 404, $response->get_status() );

		// create 5 students and enroll them.
		$student_ids = $this->factory->student->create_and_enroll_many( 5, $membership );
		$response    = $this->perform_mock_request( 'GET', $this->route . '/' . $membership . '/enrollments' );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertEquals( 5, count( $response_data ) );

		// Filter by student_id.
		$request = new WP_REST_Request( 'GET', $this->route . '/' . $membership . '/enrollments' );
		$request->set_param( 'student', "$student_ids[0]" );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertEquals( 1, count( $response_data ) );
		$this->assertEquals( $student_ids[0], $response_data[0]['student_id'] );
	}

	/**
	 * Test get single membership with forbidden context.
	 *
	 * @since [version]
	 */
	public function test_get_membership_forbidden() {
		wp_set_current_user( $this->user_forbidden );

		// Setup membership.
		$membership_id = $this->factory->membership->create();
		$response      = $this->perform_mock_request(
			'GET',
			$this->route . '/' . $membership_id,
			array(),
			array( 'context' => 'edit' ) // Role needs 'edit_post' capability.
		);

		// Check we're not allowed to get results.
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test getting single membership without permission.
	 *
	 * @since [version]
	 */
	/*	public function test_get_membership_without_permission() {
			wp_set_current_user( 0 );

			// Setup membership.
			$membership_id = $this->factory->membership->create();
			$response      = $this->perform_mock_request( 'GET', $this->route . '/' . $membership_id );

			// Check we don't have permissions to make this request.
			$this->assertEquals( 401, $response->get_status() );
		}*/

	/**
	 * Test list memberships.
	 *
	 * @since [version]
	 */
	public function test_get_memberships() {
		wp_set_current_user( $this->user_allowed );

		// Create 12 memberships. Do not create 12 monkeys.
		$memberships = $this->factory->membership->create_many( 12 );

		$response = $this->perform_mock_request( 'GET', $this->route );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertEquals( 10, count( $response_data ) ); // default per_page is 10.

		// Check retrieved memberships are the same as the generated ones.
		// Note: the check can be done in this simple way as by default the rest api memberships are ordered by id.
		for ( $i = 0; $i < 10; $i ++ ) {
			$this->llms_posts_fields_match( new LLMS_Membership( $memberships[ $i ] ), $response_data[ $i ] );
		}

		$headers = $response->get_headers();
		$this->assertEquals( 12, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );
	}

	/**
	 * Test getting memberships: bad request.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_bad_request() {
		wp_set_current_user( $this->user_allowed );

		// create 5 memberships.
		$this->factory->membership->create_many( 5, array() );
		$request = new WP_REST_Request( 'GET', $this->route );

		// Bad request, there's no page 2.
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Bad request, order param allowed are only "desc" and "asc" (enum).
		$request->set_param( 'order', 'not_desc' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test list memberships exclude arg.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_exclude() {
		wp_set_current_user( $this->user_allowed );

		// create 15 memberships.
		$memberships = $this->factory->membership->create_many( 5, array() );
		$request     = new WP_REST_Request( 'GET', $this->route );

		// get only the 2nd and 3rd membership.
		$request->set_param( 'exclude', "$memberships[0], $memberships[1]" );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response_data ) );

		// Check retrieved data do not contain first and second created memberships.
		$this->assertEquals( array_slice( $memberships, 2 ), wp_list_pluck( $response_data, 'id' ) );
	}

	/**
	 * Test get memberships with forbidden context.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_forbidden() {
		wp_set_current_user( $this->user_forbidden );

		// Setup membership.
		$this->factory->membership->create();

		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array( 'context' => 'edit' ) // Role needs 'edit_post' capability.
		);

		// Check we're not allowed to get results.
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test list memberships include arg.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_include() {
		wp_set_current_user( $this->user_allowed );

		// create 15 memberships.
		$memberships = $this->factory->membership->create_many( 5, array() );
		$request     = new WP_REST_Request( 'GET', $this->route );

		// get only the 2nd and 3rd membership.
		$request->set_param( 'include', "$memberships[1], $memberships[2]" );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $response_data ) );

		// Check retrieved memberships are the same as the second and third generated memberships.
		for ( $i = 0; $i < 2; $i ++ ) {
			$this->llms_posts_fields_match( new LLMS_Membership( $memberships[ $i + 1 ] ), $response_data[ $i ] );
		}
	}

	/**
	 * Test list memberships ordered by id ascending.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_ordered_by_id_ascending() {
		wp_set_current_user( $this->user_allowed );

		// create 5 memberships.
		$memberships = $this->factory->membership->create_many( 5, array() );
		$request     = new WP_REST_Request( 'GET', $this->route );

		// default is 'asc'.
		$request->set_param( 'order', 'asc' );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		// Check retrieved memberships are the same as the generated ones and in the same order.
		// Note: the check can be done in this simple way as by default the rest api memberships are ordered by id.
		for ( $i = 0; $i < 5; $i ++ ) {
			$this->llms_posts_fields_match( new LLMS_Membership( $memberships[ $i ] ), $response_data[ $i ] );
		}
	}

	/**
	 * Test list memberships ordered by id descending.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_ordered_by_id_descending() {
		wp_set_current_user( $this->user_allowed );

		// create 5 memberships.
		$memberships = $this->factory->membership->create_many( 5, array() );
		$request     = new WP_REST_Request( 'GET', $this->route );

		// default is 'asc'.
		$request->set_param( 'order', 'desc' );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		// Check retrieved memberships are the same as the generated ones but in the reversed order.
		// Note: the check can be done in this simple way as by default the rest api memberships are ordered by id.
		$reversed_data = array_reverse( $response_data );
		for ( $i = 0; $i < 5; $i ++ ) {
			$this->llms_posts_fields_match( new LLMS_Membership( $memberships[ $i ] ), $reversed_data[ $i ] );
		}
	}

	/**
	 * Test getting memberships orderby `menu_order`.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_ordered_by_menu_order() {
		wp_set_current_user( $this->user_allowed );

		// Create 3 memberships.
		$membership_ids = $this->factory->membership->create_many( 3, array() );

		// By default lessons are ordered by id.
		$response = $this->perform_mock_request( 'GET', $this->route );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$response_data = $response->get_data();
		$this->assertEquals( $membership_ids, wp_list_pluck( $response_data, 'id' ) );

		// Set first membership order to 8 and second to 10 so that, when ordered by 'menu_order' ASC the collection will be [last, first, second].
		$first_membership  = llms_get_post( $membership_ids[0] );
		$second_membership = llms_get_post( $membership_ids[1] );
		$last_membership   = llms_get_post( $membership_ids[2] );
		$first_membership->set( 'menu_order', 8 );
		$second_membership->set( 'menu_order', 10 );

		$response = $this->perform_mock_request( 'GET', $this->route, array(), array( 'orderby' => 'menu_order' ) );
		// Success.
		$this->assertResponseStatusEquals( 200, $response );
		$response_data = $response->get_data();
		$this->assertEquals( array( $membership_ids[2], $membership_ids[0], $membership_ids[1] ), wp_list_pluck( $response_data, 'id' ) );

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
		$response_data = $response->get_data();
		$this->assertEquals( array( $membership_ids[1], $membership_ids[0], $membership_ids[2] ), wp_list_pluck( $response_data, 'id' ) );
	}

	/**
	 * Test list memberships ordered by title.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_ordered_by_title() {
		wp_set_current_user( $this->user_allowed );

		// create 3 memberships.
		$memberships = $this->factory->membership->create_many( 3, array() );

		$membership_first = new LLMS_Membership( $memberships[0] );
		$membership_first->set( 'title', 'Membership B' );
		$membership_second = new LLMS_Membership( $memberships[1] );
		$membership_second->set( 'title', 'Membership A' );
		$membership_second = new LLMS_Membership( $memberships[2] );
		$membership_second->set( 'title', 'Membership C' );

		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'orderby', 'title' ); // default is id.

		$response = $this->server->dispatch( $request );

		$response_data = $response->get_data();

		// Check retrieved memberships are ordered by title asc.
		$this->assertEquals( 'Membership A', $response_data[0]['title']['rendered'] );
		$this->assertEquals( 'Membership B', $response_data[1]['title']['rendered'] );
		$this->assertEquals( 'Membership C', $response_data[2]['title']['rendered'] );
	}

	/**
	 * Test list memberships ordered by title descending.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_ordered_by_title_descending() {
		wp_set_current_user( $this->user_allowed );

		// create 3 memberships.
		$memberships = $this->factory->membership->create_many( 3, array() );

		$membership_first = new LLMS_Membership( $memberships[0] );
		$membership_first->set( 'title', 'Membership B' );
		$membership_second = new LLMS_Membership( $memberships[1] );
		$membership_second->set( 'title', 'Membership A' );
		$membership_second = new LLMS_Membership( $memberships[2] );
		$membership_second->set( 'title', 'Membership C' );

		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'orderby', 'title' ); // default is id.
		$request->set_param( 'order', 'desc' ); // default is 'asc'.

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		// Check retrieved memberships are ordered by title desc.
		$this->assertEquals( 'Membership C', $response_data[0]['title']['rendered'] );
		$this->assertEquals( 'Membership B', $response_data[1]['title']['rendered'] );
		$this->assertEquals( 'Membership A', $response_data[2]['title']['rendered'] );
	}

	/**
	 * Test list memberships pagination success.
	 *
	 * @since [version]
	 */
	public function test_get_memberships_with_pagination() {
		wp_set_current_user( $this->user_allowed );

		$membership_ids      = $this->factory->membership->create_many( 25, array() );
		$start_membership_id = $membership_ids[0];
		$this->pagination_test( $this->route, $start_membership_id );
	}

	/**
	 * Test getting memberships without permission.
	 *
	 * @since [version]
	 */
	/*	public function test_get_memberships_without_permission() {
			wp_set_current_user( 0 );

			// Setup membership.
			$this->factory->membership->create();

			$response = $this->perform_mock_request( 'GET', $this->route );

			// Check we don't have permissions to make this request.
			$this->assertEquals( 401, $response->get_status() );
		}*/

	/**
	 * Test getting single membership that doesn't exist.
	 *
	 * @since [version]
	 */
	public function test_get_nonexistent_membership() {
		wp_set_current_user( 0 );

		// Setup membership.
		$membership_id = $this->factory->membership->create();

		$response = $this->perform_mock_request( 'GET', $this->route . '/' . $membership_id . '42' );

		// Not found.
		$this->assertEquals( 404, $response->get_status() );
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
	 * Test trashing a single membership.
	 *
	 * @since [version]
	 */
	public function test_trash_membership() {
		wp_set_current_user( $this->user_allowed );

		// create a membership first.
		$membership = $this->factory->membership->create_and_get();

		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $membership->get( 'id' ) );
		$request->set_param( 'force', false );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		// Non empty body.
		$this->assertTrue( ! empty( $response_data ) );
		// Deleted post status should be 'trash'.
		$this->assertEquals( 'trash', get_post_status( $membership->get( 'id' ) ) );
		// check the trashed post returned into the response is the correct one.
		$this->assertEquals( $membership->get( 'id' ), $response_data['id'] );
		// check the trashed post returned into the response has the correct status 'trash'.
		$this->assertEquals( 'trash', $response_data['status'] );

		// Trash again I expect the same as above.
		$request = new WP_REST_Request( 'DELETE', $this->route . '/' . $membership->get( 'id' ) );
		$request->set_param( 'force', false );
		$response = $this->server->dispatch( $request );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		// Non empty body.
		$this->assertTrue( ! empty( $response_data ) );
		// Deleted post status should be 'trash'.
		$this->assertEquals( 'trash', get_post_status( $membership->get( 'id' ) ) );
		// check the trashed post returned into the response is the correct one.
		$this->assertEquals( $membership->get( 'id' ), $response_data['id'] );
		// check the trashed post returned into the response has the correct status 'trash'.
		$this->assertEquals( 'trash', $response_data['status'] );
	}

	/**
	 * Test forbidden single membership update.
	 *
	 * @since [version]
	 */
	public function test_update_forbidden_membership() {
		// create a membership first.
		$membership = $this->factory->membership->create_and_get();

		wp_set_current_user( $this->user_forbidden );

		$request = new WP_REST_Request( 'POST', $this->route . '/' . $membership->get( 'id' ) );
		$request->set_body_params( $this->sample_membership_args );
		$response = $this->server->dispatch( $request );

		// Bad request.
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test updating a membership.
	 *
	 * @since [version]
	 */
	public function test_update_membership() {
		wp_set_current_user( $this->user_allowed );

		// Create membership and instructor.
		$membership_id = $this->factory->membership->create();
		$instructor_id = $this->factory->user->create( array( 'role' => 'instructor' ) );

		// Update.
		$update_data = array(
			'title'               => 'A TITLE UPDATED',
			'content'             => '<p>CONTENT UPDATED</p>',
			'date_created'        => '2019-10-31 15:32:15',
			'restriction_message' => "You must belong to the UPDATED [lifterlms_membership_link id=\"$membership_id\"] " .
			                         'membership to access this content.',
			'status'              => 'draft',
			'instructors'  => array( $instructor_id ),
		);
		$response = $this->perform_mock_request( 'POST', $this->route . '/' . $membership_id, $update_data );

		// Success.
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();

		$this->assertEquals( $update_data['title'], $response_data['title']['raw'] );
		$this->assertEquals( $update_data['title'], $response_data['title']['rendered'] );
		$this->assertEquals(
			rtrim( apply_filters( 'the_content', $update_data['content'] ), "\n" ),
			rtrim( $response_data['content']['rendered'], "\n" )
		);
		$this->assertEquals( $update_data['date_created'], $response_data['date_created'] );
		$this->assertEquals( $update_data['status'], $response_data['status'] );
		$this->assertEqualSets( array( $instructor_id ), $response_data['instructors'] );

		$restriction_message_raw = $update_data['restriction_message'];
		$this->assertEquals( $restriction_message_raw, $response_data['restriction_message']['raw'] );
		$this->assertEquals( do_shortcode( $restriction_message_raw ), $response_data['restriction_message']['rendered'] );
	}

	/**
	 * Test updating a membership with an instructor that does not exist.
	 *
	 * @since [version]
	 */
	public function test_update_membership_with_bad_instructor() {
		wp_set_current_user( $this->user_allowed );

		// Create membership, before update instructor, after update instructor and non-existing instructor.
		$membership_id          = $this->factory->membership->create();
		$membership             = new LLMS_Membership( $membership_id );
		$original_instructor_id = $this->factory->user->create( array( 'role' => 'instructor' ) );
		$good_instructor_id     = $this->factory->user->create( array( 'role' => 'instructor' ) );
		$bad_instructor_id      = $good_instructor_id + 9;

		// Update membership with non-existing instructor.
		$membership->set_instructors( array( array( 'id' => $original_instructor_id ) ) );
		$update_data = array( 'instructors' => array( $bad_instructor_id ) );
		$response    = $this->perform_mock_request( 'POST', $this->route . '/' . $membership_id, $update_data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEqualSets( array( $original_instructor_id ), $response->data['instructors'] );

		// Update membership with existing instructor and non-existing instructor.
		$membership->set_instructors( array( array( 'id' => $original_instructor_id ) ) );
		$update_data = array( 'instructors' => array( $good_instructor_id, $bad_instructor_id ) );
		$response    = $this->perform_mock_request( 'POST', $this->route . '/' . $membership_id, $update_data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEqualSets( array( $good_instructor_id ), $response->data['instructors'] );

		// Update membership with non-existing instructor and existing instructor.
		$membership->set_instructors( array( array( 'id' => $original_instructor_id ) ) );
		$update_data = array( 'instructors' => array( $bad_instructor_id, $good_instructor_id ) );
		$response    = $this->perform_mock_request( 'POST', $this->route . '/' . $membership_id, $update_data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEqualSets( array( $good_instructor_id ), $response->data['instructors'] );
	}

	/**
	 * Test updating a membership with an empty instructors array.
	 *
	 * @since [version]
	 */
	public function test_update_membership_with_empty_instructors() {
		wp_set_current_user( $this->user_allowed );

		// Create membership with instructor.
		$instructor_id = $this->factory->user->create( array( 'role' => 'instructor' ) );
		$membership_id = $this->factory->membership->create();
		$membership = new LLMS_Membership( $membership_id );
		$membership->set_instructors( array( array( 'id' => $instructor_id ) ) );

		// Update membership with empty instructors.
		$update_data = array( 'instructors' => array() );
		$response = $this->perform_mock_request( 'POST', $this->route . '/' . $membership_id, $update_data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEqualSets( array( $instructor_id ), $response->data['instructors'] );
	}

	/**
	 * Test single membership update without authorization.
	 *
	 * @since [version]
	 */
	public function test_update_membership_without_authorization() {
		// create a membership first.
		$membership = $this->factory->membership->create_and_get();

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', $this->route . '/' . $membership->get( 'id' ) );
		$request->set_body_params( $this->sample_membership_args );
		$response = $this->server->dispatch( $request );

		// Unauthorized.
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a nonexistent membership.
	 *
	 * @since [version]
	 */
	public function test_update_nonexistent_membership() {
		wp_set_current_user( $this->user_allowed );

		// Setup membership.
		$membership_id = $this->factory->membership->create();

		$response = $this->perform_mock_request(
			'POST',
			$this->route . '/' . $membership_id . '42',
			$this->sample_membership_args
		);

		// Not found.
		$this->assertEquals( 404, $response->get_status() );
	}
}
