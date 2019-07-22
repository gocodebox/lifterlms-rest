<?php
/**
 * Test Admin form submissions.
 *
 * @package  LifterLMS_REST/Tests
 *
 * @group admin
 * @group admin_form_contoller
 *
 * @since [version]
 * @version [version]
 */
class LLMS_REST_Test_Admin_Form_Controller extends LLMS_REST_Unit_Test_Case {

	/**
	 * Set up the tests.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function setUp() {

		// Ensure required classes are loaded.
		set_current_screen( 'index.php' );
		LLMS_REST_API()->includes();
		include_once LLMS_PLUGIN_DIR . 'includes/admin/class.llms.admin.notices.php';

		$this->obj = new LLMS_REST_Admin_Form_Controller();

	}

	/**
	 * Clean up admin notices between tests
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function tearDown() {

		parent::tearDown();
		foreach( LLMS_Admin_Notices::get_notices() as $id ) {
			LLMS_Admin_Notices::delete_notice( $id );
		}

	}

	/**
	 * Test no events are run on regular admin screens.
	 *
	 * @since [version]
	 *
	 * @see {Reference}
	 * @link {URL}
	 *
	 * @return [type]
	 */
	public function test_handle_events_no_submit() {

		$this->assertFalse( $this->obj->handle_events() );

	}

	/**
	 * Test the "Revoke" nonce URL for deleting api keys.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_revoke_key() {

		// Key id but no nonce.
		$this->mockGetRequest( array(
			'revoke-key' => 9324234,
		) );
		$this->assertFalse( $this->obj->handle_events() );

		// Nonce present but no key.
		$this->mockGetRequest( array(
			'key-revoke-nonce' => wp_create_nonce( 'revoke' ),
		) );
		$this->assertFalse( $this->obj->handle_events() );

		// Nonce & key but key is fake.
		$this->mockGetRequest( array(
			'revoke-key' => 9324234,
			'key-revoke-nonce' => wp_create_nonce( 'revoke' ),
		) );
		$this->assertFalse( $this->obj->handle_events() );

		// Nonce is fake.
		$this->mockGetRequest( array(
			'revoke-key' => 9324234,
			'key-revoke-nonce' => 'arstarstarst',
		) );
		$this->assertFalse( $this->obj->handle_events() );

		// Real key and real nonce.
		$key = LLMS_REST_API()->keys()->create( array(
			'description' => 'Test Key',
			'user_id' => $this->factory->user->create(),
		) );
		$this->mockGetRequest( array(
			'revoke-key' => $key->get( 'id' ),
			'key-revoke-nonce' => wp_create_nonce( 'revoke' ),
		) );

		// redirect and exit back to the keys list.
		$this->expectException( LLMS_Unit_Test_Exception_Redirect::class );
		$this->expectExceptionMessage( 'http://example.org/wp-admin/admin.php?page=llms-settings&tab=rest-api&section=keys [302] YES' );

		try {

			$this->obj->handle_events();

		} catch ( LLMS_Unit_Test_Exception_Redirect $exception ) {

			// Key will no longer exist.
			$this->assertFalse( LLMS_REST_API()->keys()->get( $key->get( 'id' ) ) );

			// Should have an admin notice.
			$notices = LLMS_Admin_Notices::get_notices();
			$this->assertEquals( 1, count( $notices ) );
			$this->assertEquals( 'The API Key has been successfully deleted.', LLMS_Admin_Notices::get_notice( $notices[0] )['html'] );

			throw $exception;
		}

	}

}
