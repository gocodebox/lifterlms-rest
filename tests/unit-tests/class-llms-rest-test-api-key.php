<?php
/**
 * Test the API Key model.
 *
 * @package  LifterLMS_REST/Tests
 *
 * @group api_key
 * @group api_keys
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_API_Key extends LLMS_REST_Unit_Test_Case {

	/**
	 * Test the get_edit_link() method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_edit_link() {

		$key = LLMS_REST_API()->keys()->create( array(
			'description' => 'Test Key',
			'user_id' => $this->factory->user->create(),
		) );

		$this->assertEquals(
			'http://example.org/wp-admin/admin.php?page=llms-settings&tab=rest-api&section=keys&edit-key=' . $key->get( 'id' ),
			$key->get_edit_link()
		);

	}

	/**
	 * Test the get_delete_link() method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_delete_link() {

		$key = LLMS_REST_API()->keys()->create( array(
			'description' => 'Test Key',
			'user_id' => $this->factory->user->create(),
		) );

		$link = $key->get_delete_link();

		$this->assertEquals( 0, strpos( 'http://example.org/wp-admin/admin.php?page=llms-settings&tab=rest-api&section=keys&revoke-key=' . $key->get( 'id' ), $key->get_delete_link() ) );
		parse_str( wp_parse_url( $link, PHP_URL_QUERY ), $parts );
		$this->assertTrue( array_key_exists( 'key-revoke-nonce', $parts ) );

	}

}
