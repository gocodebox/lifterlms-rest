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
	protected function assertResponseCodeEquals( $expected, $response ) {

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
	protected function assertResponseStatusEquals( $expected, $response ) {

		$this->assertEquals( $expected, $response->get_status() );

	}

	/**
	 * Preform a mock WP_REST_Request
	 *
	 * @since [version]
	 *
	 * @param string $method Request method.
	 * @param string $route Request route, eg: '/llms/v1/courses'.
	 * @param array $body Optional request body.
	 * @return WP_REST_Response.
	 */
	protected function perform_mock_request( $method, $route, $body = array() ) {

		$request = new WP_REST_Request( $method, $route );
		if ( $body ) {
			$request->set_body_params( $body );
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
