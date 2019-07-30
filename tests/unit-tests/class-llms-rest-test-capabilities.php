<?php
/**
 * Test capabilities class.
 *
 * @package LifterLMS_REST/Tests
 *
 * @group caps
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Capabilities extends LLMS_REST_Unit_Test_Case {

	/**
	 * Test the add() method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_add() {

		$this->assertEquals( array( 'manage_lifterlms_api_keys' => true ), LLMS_REST_Capabilities::add( array() ) );
		$this->assertEquals( array(
			'some_other_cap' => true,
			'manage_lifterlms_api_keys' => true,
		), LLMS_REST_Capabilities::add( array( 'some_other_cap' => true ) ) );

	}

	/**
	 * Test various user types to ensure they have the proper capabilities.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_user_capabilites_integration() {

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$manager = $this->factory->user->create( array( 'role' => 'lms_manager' ) );
		$student = $this->factory->student->create();
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$this->assertTrue( user_can( $admin, 'manage_lifterlms_api_keys' ) );
		$this->assertTrue( user_can( $manager, 'manage_lifterlms_api_keys' ) );
		$this->assertFalse( user_can( $student, 'manage_lifterlms_api_keys' ) );
		$this->assertFalse( user_can( $subscriber, 'manage_lifterlms_api_keys' ) );

	}

}
