<?php
/**
 * LifterLMS REST API Unit Test Case Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 * @since [version]
 * @version [version]
 */

class LLMS_REST_Unit_Test_Case_Base extends LLMS_Unit_Test_Case {

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
	 * Create multiple API Keys
	 *
	 * @since [version]
	 *
	 * @param int $count Number of keys to create.
	 * @param string $permissions Define permissions for the keys. If not specified assigns a random permission to each key.
	 * @param int $user WP_User Id to assign the key to. If not supplied uses the user factory to create a new admin user for each created key.
	 * @return void
	 */
	protected function create_many_api_keys( $count, $permissions = 'rand', $user = null ) {

		$pems_available = array_keys( LLMS_REST_API()->keys()->get_permissions() );
		$num_pems = count( $pems_available ) - 1;

		$i = 1;
		while ( $i <= $count ) {

			$pem = 'rand' === $permissions ? $pems_available[ rand( 0, $num_pems ) ] : $permissions;
			$uid = $user ? $user : $this->factory->user->create( array( 'role' => 'administrator' ) );

			$this->get_mock_api_key( $pem, $uid, false );
			$i++;
		}

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
