<?php
/**
 * LifterLMS REST API Unit Test Case Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 * @since [version]
 * @version [version]
 */

class LLMS_REST_Unit_Test_Case extends LLMS_Unit_Test_Case {

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
