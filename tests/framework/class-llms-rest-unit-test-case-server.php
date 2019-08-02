<?php
/**
 * LifterLMS REST API Server Unit Test Case Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 *
 * @since [version]
 * @version [version]
 */

class LLMS_REST_Unit_Test_Case_Server extends LLMS_REST_Unit_Test_Case_Base {

	/**
	 * Server object
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Setup our test server.
	 *
	 * @since [version]
	 */
	public function setUp() {

		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

	}

	/**
	 * Assert a WP_REST_Response code equals an expected code.
	 *
	 * @since [version]
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
	 * Assert a WP_REST_Response status code equals an expected status code.
	 *
	 * @since [version]
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
	 * @since [version]
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
	 * Preform a mock WP_REST_Request
	 *
	 * @since [version]
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
	 * @since [version]
	 */
	public function tearDown() {

		parent::tearDown();

		global $wp_rest_server;
		unset( $this->server );

		$wp_rest_server = null;

	}

}
