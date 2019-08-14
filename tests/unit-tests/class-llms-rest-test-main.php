<?php
/**
 * Test the main class / loader.
 *
 * @package  LifterLMS_REST/Tests
 *
 * @group main
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Main extends LLMS_REST_Unit_Test_Case_Base {

	/**
	 * Setup the test case.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function setUp() {

		parent::setUp();
		$this->main = LLMS_REST_API();

	}

	/**
	 * Test keys() method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_keys() {

		$this->assertTrue( is_a( $this->main->keys(), 'LLMS_REST_API_Keys' ) );

	}


	/**
	 * Test webhooks() method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_webhooks() {

		$this->assertTrue( is_a( $this->main->webhooks(), 'LLMS_REST_Webhooks' ) );

	}

}
