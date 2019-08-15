<?php
/**
 * Test REST Functions.
 *
 * @package  LifterLMS_REST/Tests
 *
 * @group functions
 *
 * @since 1.0.0-beta.1
 * @version 1.0.0-beta.1
 */
class LLMS_REST_Test_Functions extends LLMS_REST_Unit_Test_Case_Base {

	/**
	 * Test the llms_rest_api_hash() function
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @return void
	 */
	public function test_llms_rest_api_hash() {

		$hash = llms_rest_api_hash( 1 );
		$this->assertTrue( is_string( $hash ) );
		$this->assertEquals( 64, strlen( $hash ) );

		$hash = llms_rest_api_hash( 'abc' );
		$this->assertTrue( is_string( $hash ) );
		$this->assertEquals( 64, strlen( $hash ) );

		$hash = llms_rest_api_hash( llms_rest_random_hash() );
		$this->assertTrue( is_string( $hash ) );
		$this->assertEquals( 64, strlen( $hash ) );

	}

	/**
	 * Test the llms_rest_get_api_endpoint_data() method.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @return void
	 */
	public function test_llms_rest_get_api_endpoint_data() {

		$res = llms_rest_get_api_endpoint_data( '/llms/v1' );
		$this->assertEquals( 'llms/v1', $res['namespace'] );

	}

	/**
	 * Test the llms_rest_random_hash() function
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @return void
	 */
	public function test_llms_rest_random_hash() {

		$hash = llms_rest_random_hash();
		$this->assertTrue( is_string( $hash ) );
		$this->assertEquals( 40, strlen( $hash ) );

	}

}
