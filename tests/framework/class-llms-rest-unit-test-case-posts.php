<?php
/**
 * LifterLMS REST API witServer Unit Test Case Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 *
 * @since 1.0.0-beta.1
 * @since 1.0.0-beta.7 Fixed some expected properties not tested at all, and wrong excerpts.
 * @since 1.0.0-beta.8 Added tests on getting links to terms based on the current user caps.
 * @since 1.0.0-beta.19 Added tests on filtering the collection by post status.
 * @since 1.0.0-beta.21 Test search.
 * @since 1.0.0-beta.25 Added tests on updating post meta with the same value as the stored one.
 */

require_once 'class-llms-rest-unit-test-case-server.php';

class LLMS_REST_Unit_Test_Case_Posts extends LLMS_REST_Unit_Test_Case_Server {

    /**
	 * db post type of the model being tested
	 * @var  string
	 */
    protected $post_type = '';

	/**
	 *
	 * Setup.
	 *
	 * @since 1.0.0-beta.7
	 */
	public function set_up() {
		parent::set_up();

		// assume all posts have been migrated to the block editor to avoid adding parts to the content.
		add_filter( 'llms_blocks_is_post_migrated', '__return_true' );
		$blocks_migrate = new LLMS_Blocks_Migrate();
		$blocks_migrate->remove_template_hooks();

		// clean the db from this post type
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'posts', array( 'post_type' => $this->post_type ) );
	}


	/**
	 * Test getting links to terms based on the current user caps.
	 *
	 * @since 1.0.0-beta.8
	 */
	public function test_get_links_terms() {

		if ( empty( $this->rest_taxonomies ) ) {
			$this->markTestSkipped( 'No taxonomies to test' );
			return;
		}

		// create a post first.
		$llms_post = $this->factory->post->create( array( 'post_type' => $this->post_type ) );
		$llms_post = llms_get_post($llms_post);

		$response = $this->perform_mock_request( 'GET', $this->route . '/' . $llms_post->get( 'id' ) );
		$links    = $response->get_links();

		// I expect no wp terms, as who made the request has no right caps to show the posts's taxonomies in rest.
		$this->assertArrayNotHasKey( 'https://api.w.org/term', $links );

		// same request with right caps.
		$instructor = $this->factory->instructor->create();
		wp_set_current_user( $instructor );

		// clean and register the taxonomies again so that the show_in_rest property is set to true.
		foreach ( get_object_taxonomies( $this->post_type ) as $taxonomy ) {
			unregister_taxonomy( $taxonomy );
		}
		LLMS_Post_Types::register_taxonomies();

		$response = $this->perform_mock_request( 'GET', $this->route . '/' . $llms_post->get( 'id' ) );
		$links    = $response->get_links();

		// I expect wp terms, as who made the request has the right caps to show the llms_post's taxonomies in rest.
		$this->assertArrayHasKey( 'https://api.w.org/term', $links );
		$this->assertEquals(
			wp_list_pluck( wp_list_pluck($links['https://api.w.org/term'], 'attributes' ), 'taxonomy' ),
			$this->rest_taxonomies
		);

	}

	/**
	 * Utility to compare an LLMS_Post with an array of data, tipically coming from a rest response.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.7 Fixed some expected properties not tested at all, and wrong excerpts.
	 *
	 * @param LLMS_Post_Model $llms_post       An LLMS_Post_Model.
	 * @param array           $llms_post_data  An array of llms post data.
	 * @param string          $context         Optional. Default 'view'.
	 * @return void
	 */
	protected function llms_posts_fields_match( $llms_post, $llms_post_data, $context = 'view' ) {

		$password_required = post_password_required( $llms_post->get( 'id' ) );
		global $post;
		$temp = $post;
		$post = $llms_post->get( 'post' );

		$expected = array(
			'id'               => $llms_post->get( 'id' ),
			'title'            => array(
				'raw'      => $llms_post->get( 'title', true ),
				'rendered' => $llms_post->get( 'title' ),
			),
			'status'           => $llms_post->get( 'status' ),
			'content'          => array(
				'raw'      => $llms_post->get( 'content', true ),
				'rendered' => $password_required ? '' : apply_filters( 'the_content', $llms_post->get( 'content', true ) ),
			),
			'excerpt'          => array(
				'raw'      => $llms_post->get( 'excerpt', true ),
				'rendered' => $password_required ? '' : apply_filters( 'the_excerpt', $llms_post->get( 'excerpt' ) ),
			),
			'date_created'     => $llms_post->get( 'date', 'Y-m-d H:i:s' ),
			'date_created_gmt' => $llms_post->get( 'date_gmt', 'Y-m-d H:i:s' ),
			'date_updated'     => $llms_post->get( 'modified', 'Y-m-d H:i:s' ),
			'date_updated_gmt' => $llms_post->get( 'modified_gmt', 'Y-m-d H:i:s' ),
		);

		if ( 'edit' !== $context ) {
			unset(
				$expected['content']['raw'],
				$expected['excerpt']['raw'],
				$expected['title']['raw']
			);
		}

		$expected = $this->filter_expected_fields( $expected, $llms_post );

		/**
		 * The rtrim below is not ideal but at the moment we have templates printed after the llms_post summary (e.g. prerequisites) that,
		 * even when printing no data they still print "\n". Let's pretend we're not interested in testing the trailing "\n" presence.
		 */
		foreach ( $expected as $key => $value ) {
			if ( ! isset( $llms_post_data[ $key ] ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					if ( 'content' === $key ) {
						if ( ! isset( $llms_post_data[ $key ][ $k ] ) ) {
							continue;
						}
						$this->assertEquals( rtrim( $v, "\n" ), rtrim( $llms_post_data[ $key ][ $k ], "\n" ) );
					} else {
						$this->assertEquals( $v, $llms_post_data[ $key ][ $k ] );
					}
				}
			} else {
				if ( 'content' === $key ) {
					$this->assertEquals( rtrim( $value, "\n" ), rtrim( $llms_post_data[ $key ], "\n" ) );
				} else {
					$this->assertEquals( $value, $llms_post_data[ $key ] );
				}
			}
		}

		$post = $temp;
	}

	/**
	 * Test collection params contain 'status'
	 *
	 * @since 1.0.0-beta.19
	 *
	 * @return void
	 */
	public function test_collection_params_contain_status() {
		if ( isset( $this->endpoint->get_item_schema()['properties']['status'] ) ) {
			$this->assertContains(
				'status',
				array_keys( $this->endpoint->get_collection_params() )
			);
		}else {
			$this->assertNotContains(
				'status',
				array_keys( $this->endpoint->get_collection_params() )
			);
		}
	}

	/**
	 * Test collection filter by post status
	 *
	 * @since 1.0.0-beta.19
	 *
	 * @return void
	 */
	public function test_filter_by_post_status() {

		// Skip those post types which have no status property.
		if ( ! isset( $this->endpoint->get_item_schema()['properties']['status'] ) ){
			$this->markTestSkipped( sprintf( 'post status not available for %1$s', $this->post_type ) );
		}

		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator',
				)
			)
		);

		// Create two posts.
		$posts = $this->factory->post->create_many(
			2,
			array(
				'post_type' => $this->post_type,
			)
		);

		$response = $this->perform_mock_request(
			'GET',
			$this->route
		);
		$res_data = $response->get_data();
		$this->assertEquals( 2, count( $res_data ), $this->post_type );

		// Make one of them 'draft'.
		wp_update_post(
			array(
				'ID'          => $posts[0],
				'post_status' => 'draft',
			)
		);

		$response = $this->perform_mock_request(
			'GET',
			$this->route
		);
		// By default only published posts are returned.
		$res_data = $response->get_data();
		$this->assertEquals( 1, count( $res_data ), $this->post_type );
		$this->assertEquals( $posts[1], $res_data[0]['id'], $this->post_type );

		// Get only draft courses.
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'status' => 'draft',
			)
		);

		$res_data = $response->get_data();
		$this->assertEquals( 1, count( $res_data ), $this->post_type );
		$this->assertEquals( $posts[0], $res_data[0]['id'], $this->post_type );

		// Get both published and draft posts.
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'status' => 'publish,draft',
			)
		);

		$res_data = $response->get_data();
		$this->assertEquals( 2, count( $res_data ), $this->post_type );

		// Change user to someone who cannot edit drafts.
		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'subscriber',
				)
			)
		);

		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'status' => 'draft',
			)
		);
		// We expect an authorization error.
		$this->assertResponseStatusEquals( 400, $response, $this->post_type );

		// Change user to someone who cannot edit drafts generated by the admin.
		$author = $this->factory->user->create(
			array(
				'role' => 'instructor',
			)
		);

		wp_set_current_user(
			$author
		);

		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'status' => 'draft',
			)
		);
		// We expect an empty list of posts.
		$res_data = $response->get_data();
		$this->assertResponseStatusEquals( 200, $response, $this->post_type );
		$this->assertEquals( 0, count( $res_data ), $this->post_type );

		// Check instructor will get their own drafts.
		$post = $this->factory->post->create(
			array(
				'post_type'   => $this->post_type,
				'post_author' => $author,
				'post_status' => 'draft',
			)
		);
		$llms_post = llms_get_post( $post );
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'status' => 'draft',
			)
		);
		$res_data = $response->get_data();

		$this->assertEquals( 1, count( $res_data ), $this->post_type );
		$this->assertEquals( $post, $res_data[0]['id'], $this->post_type );

	}


	/**
	 * Test collection search.
	 *
	 * @since 1.0.0-beta.21
	 *
	 * @return void
	 */
	public function test_search() {

		if ( ! LLMS_Unit_Test_Util::get_private_property_value( $this->endpoint, 'is_searchable' ) ) {
			$this->markTestSkipped(
				sprintf(
					'The %1$s endpoint is not searchable',
					LLMS_Unit_Test_Util::get_private_property_value( $this->endpoint, 'rest_base' )
				)
			);
		}

		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator',
				)
			)
		);

		// Create two posts.
		$post_match = $this->factory->post->create(
			array(
				'post_type'  => $this->post_type,
				'post_title' => 'Match when searching with the term "whatever"',
			)
		);

		$post_unmatch = $this->factory->post->create(
			array(
				'post_type'  => $this->post_type,
				'post_title' => 'This doesn\'t match the search',
			)
		);
		// Access plans need a product id.
		if ( 'llms_access_plan' === $this->post_type ) {
			$course =  $this->factory->post->create( array( 'post_type' => 'course' ) );
			llms_get_post( $post_match )->set( 'product_id', $course );
			llms_get_post( $post_unmatch )->set( 'product_id', $course );
		}

		$response = $this->perform_mock_request(
			'GET',
			$this->route
		);
		$res_data = $response->get_data();
		$this->assertEquals( 2, count( $res_data ), $this->post_type );

		// Search.
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'search' => 'whatever',
			)
		);
		$res_data = $response->get_data();
		$this->assertEquals( 1, count( $res_data ), $this->post_type );
		$this->assertEquals( $post_match, $res_data[0]['id'], $this->post_type );

		// Search no matches.
		$response = $this->perform_mock_request(
			'GET',
			$this->route,
			array(),
			array(
				'search' => 'foreveryoung',
			)
		);
		$res_data = $response->get_data();
		$this->assertEquals( 0, count( $res_data ), $this->post_type );

	}

	/**
	 * Test updating post meta using the same value as the stored one.
	 *
	 * @since 1.0.0-beta.25
	 *
	 * @return void
	 */
	public function test_update_meta_same_stored_value() {

		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator',
				)
			)
		);

		// Create a post type and get the resource.
		$pt = $this->create_post_resource();

		$response = $this->perform_mock_request(
			'GET',
			$this->route . '/' . $pt->ID,
		);

		// Update the resource with exactly the same data.
		$response = $this->perform_mock_request(
			'POST',
			$this->route . '/' . $pt->ID,
			$response->get_data()
		);

		// Success.
		$this->assertResponseStatusEquals( 200, $response, $this->post_type );

	}

	/**
	 * Create a resource for this post type.
	 *
	 * @since 1.0.0-beta.25
	 *
	 * @param array $params Array of request params.
	 * @return WP_Post
	 */
	protected function create_post_resource( $params = array() ) {

		$resource = $this->perform_mock_request(
			'POST',
			$this->route,
			array_merge(
				array(
					'title'   => sprintf( "A %s", $this->post_type ),
					'content' => sprintf( "Some content for %s", $this->post_type ),
				),
				$params
			)
		);

		return get_post( $resource->get_data()['id'] );

	}

	/**
	 * Utility to compare an LLMS_Post with an array of data, tipically coming from a rest response.
	 *
	 * Stub.
	 *
	 * @since 1.0.0-beta.1
	 */
	protected function filter_expected_fields( $expected, $llms_post ) {
		return $expected;
	}

}
