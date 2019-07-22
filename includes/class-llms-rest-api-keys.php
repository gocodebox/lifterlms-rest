<?php
/**
 * CRUD API Keys.
 *
 * @package  LifterLMS_REST/Classes
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_API_Keys class.
 *
 * @since [version]
 */
class LLMS_REST_API_Keys {

	/**
	 * Singleton instance
	 *
	 * @var  null
	 */
	protected static $_instance = null;

	/**
	 * Get Main Singleton Instance.
	 *
	 * @since [version]
	 *
	 * @return LLMS_REST
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Create a new API Key
	 *
	 * @since [version]
	 *
	 * @param array $data {
	 *     Associative array of data to set to a key's properties.
	 *
	 *     @type string $description (Required) A friendly name for the key.
	 *     @type int $user_id WP_User (Required) ID of the key's owner.
	 *     @type string $permissions (Required) Permission string for the key. Accepts `read`, `write`, or `read_write`.
	 * }
	 * @return [type]
	 */
	public function create( $data ) {

		if ( ! empty( $data['id'] ) ) {
			return new WP_Error( 'llms_rest_key_exists', __( 'Cannot create a new API key with a pre-defined ID.', 'lifterlms' ) );
		}

		/**
		 * Allow customization of default API Key properties.
		 *
		 * @since [version]
		 *
		 * @param array $properties An associative array of key properties.
		 */
		$defaults = apply_filters(
			'llms_rest_api_key_default_properties',
			array(
				'permissions' => 'read',
			)
		);
		$data     = wp_parse_args( $data, $defaults );

		// Required Fields.
		if ( empty( $data['description'] ) ) {
			return new WP_Error( 'llms_rest_key_missing_description', __( 'An API Key description is required.', 'lifterlms' ) );
		} elseif ( empty( $data['user_id'] ) ) {
			return new WP_Error( 'llms_rest_key_missing_user', __( 'An API Key must be assigned to a user.', 'lifterlms' ) );
		}

		$err = $this->is_data_valid( $data );
		if ( is_wp_error( $err ) ) {
			return $err;
		}

		$api_key = new LLMS_REST_API_Key();

		$key    = 'ck_' . llms_rest_random_hash();
		$secret = 'cs_' . llms_rest_random_hash();

		$data['consumer_key']    = llms_rest_api_hash( $key );
		$data['consumer_secret'] = $secret;
		$data['truncated_key']   = substr( $key, -7 );

		// Set and save.
		$api_key->setup( $data )->save();

		// Return the unhashed key on creation to be displayed once and never stored.
		$api_key->set( 'consumer_key_one_time', $key );

		return $api_key;

	}

	/**
	 * Delete an API key.
	 *
	 * @since [version]
	 *
	 * @param int $id API Key ID.
	 * @return bool  `true` on success, `false` if the key couldn't be found or an error was encountered during deletion.
	 */
	public function delete( $id ) {
		$key = $this->get( $id, false );
		if ( $key ) {
			return $key->delete();
		}
		return false;
	}

	/**
	 * Retrieve an API Key object instance.
	 *
	 * @since [version]
	 *
	 * @param int  $id API Key ID.
	 * @param bool $hydrate If true, pulls all key data from the database on instantiation.
	 * @return LLMS_REST_API_Key|false
	 */
	public function get( $id, $hydrate = true ) {
		$key = new LLMS_REST_API_Key( $id, $hydrate );
		if ( $key && $key->exists() ) {
			return $key;
		}
		return false;
	}

	/**
	 * Retrieve the base admin url for managing API keys.
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function get_admin_url() {
		return add_query_arg(
			array(
				'page'    => 'llms-settings',
				'tab'     => 'rest-api',
				'section' => 'keys',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Retrieve an array of options for API Key Permissions.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_permissions() {
		return array(
			'read'       => __( 'Read', 'lifterlms' ),
			'write'      => __( 'Write', 'lifterlms' ),
			'read_write' => __( 'Read / Write', 'lifterlms' ),
		);
	}

	/**
	 * Validate data supplied for creating/updating a key.
	 *
	 * @since [version]
	 *
	 * @param array $data {
	 *     Associative array of data to set to a key's properties.
	 *
	 *     @type string $description A friendly name for the key.
	 *     @type int $user_id WP_User ID of the key's owner.
	 *     @type string $permissions Permission string for the key. Accepts `read`, `write`, or `read_write`.
	 * }
	 * @return WP_Error|true When data is invalid will return a WP_Error with information about the invalid properties,
	 *                            otherwise `true` denoting data is valid.
	 */
	protected function is_data_valid( $data ) {

		// First conditions prevents '', '0', 0, etc... & second prevents invalid / non existant user ids.
		if ( ( isset( $data['user_id'] ) && empty( $data['user_id'] ) ) || ( ! empty( $data['user_id'] ) && ! get_user_by( 'id', $data['user_id'] ) ) ) {
			// Translators: %s = Invalid user id.
			return new WP_Error( 'llms_rest_key_invalid_user', sprintf( __( '"%s" is not a valid user ID.', 'lifterlms' ), $data['user_id'] ) );
		}

		// Prevent blank/empty descriptions.
		if ( isset( $data['description'] ) && empty( $data['description'] ) ) {
			return new WP_Error( 'llms_rest_key_invalid_description', __( 'An API Description is required.', 'lifterlms' ) );
		}

		// Validate Permissions.
		if ( ! empty( $data['permissions'] ) && ! in_array( $data['permissions'], array_keys( $this->get_permissions() ), true ) ) {
			// Translators: %s = Invalid permission string.
			return new WP_Error( 'llms_rest_key_invalid_permissions', sprintf( __( '"%s" is not a valid permission.', 'lifterlms' ), $data['permissions'] ) );
		}

		return true;

	}

	/**
	 * Update an API Key
	 *
	 * @since [version]
	 *
	 * @param array $data {
	 *     Associative array of data to set to a key's properties.
	 *
	 *     @type string $description A friendly name for the key.
	 *     @type int $user_id WP_User ID of the key's owner.
	 *     @type string $permissions Permission string for the key. Accepts `read`, `write`, or `read_write`.
	 *     @type string $last_access MySQL Datetime string representing the last time the key was used to access the API.
	 * }
	 * @return LLMS_REST_API_Key|WP_Error
	 */
	public function update( $data ) {

		if ( empty( $data['id'] ) ) {
			return new WP_Error( 'llms_rest_key_missing_id', __( 'No API Key ID was supplied.', 'lifterlms' ) );
		}

		$api_key = $this->get( $data['id'] );
		if ( ! $api_key || ! $api_key->exists() ) {
			return new WP_Error( 'llms_rest_key_invalid_key', __( 'The requested API Key could not be located.', 'lifterlms' ) );
		}

		// Filter out write-protected keys.
		$data = array_diff_key(
			$data,
			array(
				'id'              => false,
				'consumer_key'    => false,
				'consumer_secret' => false,
				'truncated_key'   => false,
			)
		);

		$err = $this->is_data_valid( $data );
		if ( is_wp_error( $err ) ) {
			return $err;
		}

		// Set and save.
		$api_key->setup( $data )->save();

		return $api_key;

	}

}
