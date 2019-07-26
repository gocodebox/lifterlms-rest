<?php
/**
 * LifterLMS REST API Unit Test Case Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 * @since [version]
 * @version [version]
 */

class LLMS_REST_Unit_Test_Case extends LLMS_Unit_Test_Case {

	/**
	 * Generate a mock api key.
	 *
	 * @since [version]
	 *
	 * @see {Reference}
	 * @link {URL}
	 *
	 * @param string $permissions Key permissions.
	 * @param int $user_id WP_User ID. If not supplied generates one via the user factory.
	 * @param bool $authorize If true, automatically adds creds to auth headers.
	 * @return LLMS_REST_API_Key
	 */
	protected function get_mock_api_key( $permissions = 'read_write', $user_id = null, $authorize = true ) {

		$key = LLMS_REST_API()->keys()->create( array(
			'description' => 'Test Key',
			'user_id' => $user_id ? $user_id : $this->factory->user->create(),
			'permissions' => $permissions,
		) );

		if ( $authorize ) {
			$this->mock_authorization( $key->get( 'consumer_key_one_time' ), $key->get( 'consumer_secret' ) );
		}

		return $key;

	}

	/**
	 * Mock authorization headers.
	 *
	 * @since [version]
	 *
	 * @param string $key Consumer key.
	 * @param string $secret Consumer secret.
	 * @return void
	 */
	protected function mock_authorization( $key = null, $secret = null ) {

		$_SERVER['HTTP_X_LLMS_CONSUMER_KEY']    = $key;
		$_SERVER['HTTP_X_LLMS_CONSUMER_SECRET'] = $secret;
	}

	/**
	 * test teardown.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function tearDown() {

		parent::tearDown();

		// Remove possibly mocked headers.
		unset( $_SERVER['HTTP_X_LLMS_CONSUMER_KEY'], $_SERVER['HTTP_X_LLMS_CONSUMER_SECRET'] );

	}

}
