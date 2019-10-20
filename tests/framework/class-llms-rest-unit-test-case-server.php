<?php
/**
 * LifterLMS REST API Server Unit Test Case Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 *
 * @since 1.0.0-beta.1
 * @version 1.0.0-beta.7
 */

class LLMS_REST_Unit_Test_Case_Server extends LLMS_REST_Unit_Test_Case_Base {
	/**
	 * @var LLMS_REST_Controller
	 */
	protected $endpoint;

	/**
	 * Server object
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Route.
	 *
	 * @var string
	 */
	protected $route;

	/**
	 * ID of user that is allowed to perform an action in the test.
	 *
	 * @var int
	 */
	protected $user_allowed;

	/**
	 * ID of user that is forbidden to an perform action in the test.
	 *
	 * @var int
	 */
	protected $user_forbidden;

	/**
	 * Setup our test server.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function setUp() {

		parent::setUp();
		$this->server = rest_get_server();

	}

	/**
	 * Assert a WP_REST_Response code equals an expected code.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param string $expected Expected response code.
	 * @param WP_REST_Response $response Response object.
	 * @return void
	 */
	protected function assertResponseCodeEquals( $expected, WP_REST_Response $response ) {

		$data = $response->get_data();
		$this->assertEquals( $expected, $data['code'] );

	}

	/**
	 * Assert a WP_REST_Response message equals an expected message.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param int $expected Expected response message.
	 * @param WP_REST_Response $response Response object.
	 * @return void
	 */
	protected function assertResponseMessageEquals( $expected, WP_REST_Response $response ) {

		$data = $response->get_data();
		$this->assertEquals( $expected, $data['message'] );

	}

	/**
	 * Assert a WP_REST_Response status code equals an expected status code.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param int $expected Expected response http status code.
	 * @param WP_REST_Response $response Response object.
	 * @return void
	 */
	protected function assertResponseStatusEquals( $expected, WP_REST_Response $response ) {

		$this->assertEquals( $expected, $response->get_status() );

	}

	/**
	 * Parse the `Link` header to pull all links into an associative array of rel => uri
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param WP_REST_Response $response Response object.
	 * @return array
	 */
	protected function parse_link_headers( WP_REST_Response $response ) {

		$headers = $response->get_headers();
		$links = isset( $headers['Link'] ) ? $headers['Link'] : '';

		$parsed = array();
		if ( $links ) {

			foreach ( explode( ',', $links ) as $link ) {
				preg_match( '/<(.*)>; rel="(.*)"/i', trim( $link, ',' ), $match );
				if ( 3 === count( $match ) ) {
					$parsed[ $match[2] ] = $match[1];
				}
			}

		}

		return $parsed;

	}

	/**
	 * Utility to perform pagination test.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @param string $route    Optional. Request route, eg: '/llms/v1/courses'. Default empty string, will fall back on this->route.
	 * @param int    $start_id Optional. The id of the first item. Default 1.
	 * @param int    $per_page The number of items per page. Default 10.
	 * @param string $id_field The field name for the id item that should be present in the response. Set to empty to not perform any check.
	 * @param int    $total    Total expected items.
	 * @return void
	 */
	protected function pagination_test( $route = '', $start_id = 1, $per_page = 10, $id_field = 'id', $total = 25 ) {

		$route       = empty( $route ) ? $this->route : $route;
		$total_pages = (int) ceil( $total / $per_page );
		$initial_id  = $start_id;

		for ( $i = 1; $i <= $total_pages; $i++ ) {

			$args = array( 'per_page' => $per_page );
			if ( $i > 1 ) {
				$args[ 'page' ] = $i;
			}

			// Page $i.
			$response = $this->perform_mock_request( 'GET', $route, array(), $args );

			$body = $response->get_data();
			$headers = $response->get_headers();

			$links = $this->parse_link_headers( $response );

			$this->assertResponseStatusEquals( 200, $response );
			$this->assertEquals( $total, $headers['X-WP-Total'] );
			$this->assertEquals( $total_pages, $headers['X-WP-TotalPages'] );

			// Pagination links check.
			if ( 1 !== $total_pages ) {
				switch ( $i ) :
					// First page we expect only 'next' and 'last' links.
					case 1 : $links_array = array( 'next', 'last' ); break;
					// Last page we expect only 'first' and 'prev' links.
					case $total_pages: $links_array = array( 'first', 'prev' ); break;
					default : $links_array = array( 'first', 'prev', 'next', 'last' );
				endswitch;

				$this->assertEquals( $links_array, array_keys( $links ) );
			}

			if ( $id_field) {
				$stop_id = ( $i !== $total_pages ) ? ( $start_id + $per_page - 1 ) : ( $total + $initial_id - 1 );
				$this->assertEquals( range( $start_id, $stop_id ), wp_list_pluck( $body, $id_field ) );
				$start_id += $per_page;
			}

		}

		// Big per page.
		$response = $this->perform_mock_request( 'GET', $route, array(), array( 'per_page' => $total + 10 ) );

		// Check Pagination headers.
		$headers = $response->get_headers();
		$this->assertEquals( $total, $headers['X-WP-Total'] );
		$this->assertEquals( 1, $headers['X-WP-TotalPages'] );

		// No links because this is the only page.
		$links = $this->parse_link_headers( $response );
		$this->assertEquals( array(), array_keys( $links ) );

		// Out of bounds.
		$response = $this->perform_mock_request( 'GET', $route, array(), array( 'per_page' => $per_page, 'page' => $total_pages + 1 ) );

		$this->assertResponseStatusEquals( 400, $response );
		$this->assertResponseCodeEquals( 'llms_rest_bad_request', $response );
	}

	/**
	 * Preform a mock WP_REST_Request
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param string $method Request method.
	 * @param string $route Request route, eg: '/llms/v1/courses'.
	 * @param array $body Optional request body.
	 * @param array $query Optional query arguments.
	 * @return WP_REST_Response.
	 */
	protected function perform_mock_request( $method, $route, $body = array(), $query = array() ) {

		$request = new WP_REST_Request( $method, $route );
		if ( $body ) {
			$request->set_body_params( $body );
		}
		if( $query ) {
			$request->set_query_params( $query );
		}
		return $this->server->dispatch( $request );

	}

	/**
	 * Unset the server.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function tearDown() {

		parent::tearDown();

		global $wp_rest_server;
		unset( $this->server );

		$wp_rest_server = null;

	}

}
