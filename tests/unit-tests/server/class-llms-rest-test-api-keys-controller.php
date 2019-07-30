<?php
/**
 * Tests for Courses API.
 *
 * @package LifterLMS_Rest/Tests
 *
 * @group REST
 * @group rest_api_keys
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_API_Keys_Controller extends LLMS_REST_Server_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {

		parent::setUp();
		$this->endpoint = new LLMS_REST_API_Keys_Controller();

	}

	// public function test_register_routes() {

	// 	$this->endpoint->register_routes();

	// }

	public function test_get_item_schema() {

		$schema = $this->endpoint->get_item_schema();
		$this->assertTrue( array_key_exists( '$schema', $schema ) );
		$this->assertTrue( array_key_exists( 'title', $schema ) );
		$this->assertTrue( array_key_exists( 'type', $schema ) );
		$this->assertTrue( array_key_exists( 'properties', $schema ) );
		$this->assertTrue( array_key_exists( 'description', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'permissions', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'user_id', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'truncated_key', $schema['properties'] ) );
		$this->assertTrue( array_key_exists( 'last_access', $schema['properties'] ) );


	}

}
