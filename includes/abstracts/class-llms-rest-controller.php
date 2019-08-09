<?php
/**
 * Base REST Controller Class.
 *
 * @package  LifterLMS_REST/Abstracts
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Controller class..
 *
 * @since [version]
 */
abstract class LLMS_REST_Controller extends WP_REST_Controller {

	/**
	 * Base Resource
	 *
	 * For example: "courses" or "students".
	 *
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'llms/v1';

	/**
	 * Schema properties available for ordering the collection.
	 *
	 * @var string[]
	 */
	protected $orderby_properties = array(
		'id',
	);

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return object|WP_Error
	 */
	abstract protected function get_object( $id );

	/**
	 * Create an item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return llms_rest_bad_request_error( __( 'Cannot create an existing resource.', 'lifterlms' ) );
		}

		$item   = $this->prepare_item_for_database( $request );
		$object = $this->create_object( $item, $request );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$fields_update = $this->update_additional_fields_for_object( $item, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $this->get_object_id( $object ) ) ) );

		return $response;

	}

	/**
	 * Insert the prepared data into the database.
	 *
	 * @since [version]
	 *
	 * @param array           $prepared Prepared item data.
	 * @param WP_REST_Request $request Request object.
	 * @return obj Object Instance of object from $this->get_object().
	 */
	protected function create_object( $prepared, $request ) {

		// @todo: add version to message.

		// Translators: %s = method name.
		_doing_it_wrong( 'LLMS_REST_Controller::create_object', sprintf( __( "Method '%s' must be overridden.", 'lifterlms' ), __METHOD__ ), '[version]' );

		// For example.
		return $this->get_object( $this->get_object_id( $prepared ) );

	}

	/**
	 * Delete the item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {

		$object = $this->get_object( $request['id'], false );

		// We don't return 404s for items that are not found.
		if ( ! is_wp_error( $object ) ) {

			// If there was an error deleting the object return the error. If the error is that the object doesn't exist return 204 below!
			$del = $this->delete_object( $object, $request );
			if ( is_wp_error( $del ) ) {
				return $del;
			}
		}

		$response = rest_ensure_response( null );
		$response->set_status( 204 );

		return $response;

	}

	/**
	 * Delete the object.
	 *
	 * Note: we do not return 404s when the resource to delete cannot be found. We assume it's already been deleted and respond with 204.
	 * Errors returned by this method should be any error other than a 404!
	 *
	 * @since [version]
	 *
	 * @param obj             $object Instance of the object from $this->get_object().
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error true when the object is removed, WP_Error on failure.
	 */
	protected function delete_object( $object, $request ) {

		// @todo: add version to message.

		// Translators: %s = method name.
		_doing_it_wrong( 'LLMS_REST_Controller::delete_object', sprintf( __( "Method '%s' must be overridden.", 'lifterlms' ), __METHOD__ ), '[version]' );

		// For example.
		return true;

	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		// We're not currently implementing searching.
		unset( $query_params['search'] );

		// page and per_page params are already specified in WP_Rest_Controller->get_collection_params().

		$query_params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'lifterlms' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'lifterlms' ),
			'type'              => 'string',
			'default'           => $this->orderby_properties[0],
			'enum'              => $this->orderby_properties,
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_params['include'] = array(
			'description'       => __( 'Limit results to a list of ids. Accepts a single id or a comma separated list of ids.', 'lifterlms' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_params['exclude'] = array(
			'description'       => __( 'Exclude a list of ids from results. Accepts a single id or a comma separated list of ids.', 'lifterlms' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

	/**
	 * Get a single item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$object = $this->get_object( (int) $request['id'] );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$response = $this->prepare_item_for_response( $object, $request );

		return rest_ensure_response( $response );

	}

	/**
	 * Retrieves the query params for retrieving a single resource.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_get_item_params() {

		return array(
			'context' => $this->get_context_param(
				array(
					'default' => 'view',
				)
			),
		);

	}

	/**
	 * Retrieve arguments for deleting a resource.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_delete_item_args() {
		return array();
	}

	/**
	 * Retrieve an ID from the object
	 *
	 * @since [version]
	 *
	 * @param obj $object Item object.
	 * @return int
	 */
	protected function get_object_id( $object ) {
		if ( is_object( $object ) && ! empty( $object->id ) ) {
			return $object->id;
		} elseif ( is_array( $object ) && ! empty( $object['id'] ) ) {
			return $object['id'];
		} elseif ( method_exists( $object, 'get_id' ) ) {
			return $object->get_id();
		} elseif ( method_exists( $object, 'get' ) ) {
			return $object->get( 'id' );
		}

		// @todo: add version to message.

		// Translators: %s = method name.
		_doing_it_wrong( 'LLMS_REST_Controller::get_object_id', sprintf( __( "Method '%s' must be overridden.", 'lifterlms' ), __METHOD__ ), '[version]' );

		// For example.
		return 0;

	}

	/**
	 * Map request keys to database keys for insertion.
	 *
	 * Array keys are the request fields (as defined in the schema) and
	 * array values are the database fields.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	protected function map_schema_to_database() {

		$schema = $this->get_item_schema();
		$keys   = array_keys( $schema['properties'] );
		return array_combine( $keys, $keys );

	}

	/**
	 * Prepare request arguments for a database insert/update.
	 *
	 * @since [version]
	 *
	 * @param WP_Rest_Request $request Request object.
	 * @return array
	 */
	protected function prepare_item_for_database( $request ) {

		$prepared = array();
		$map      = $this->map_schema_to_database();
		$schema   = $this->get_item_schema();

		foreach ( $map as $req_key => $db_key ) {

			if ( ! empty( $request[ $req_key ] ) ) {
				$prepared[ $db_key ] = $request[ $req_key ];
			}
		}

		return $prepared;

	}

	/**
	 * Prepares a single object for response.
	 *
	 * @since [version]
	 *
	 * @param obj             $object Raw object from database.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $object, $request ) {

		$data = $this->prepare_object_for_response( $object, $request );

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		// Add links.
		$response->add_links( $this->prepare_links( $object ) );

		return $response;

	}

	/**
	 * Prepare links for the request.
	 *
	 * @since [version]
	 *
	 * @param obj $object Item object.
	 * @return array
	 */
	protected function prepare_links( $object ) {

		$base = rest_url( sprintf( '/%1$s/%2$s', $this->namespace, $this->rest_base ) );

		$links = array(
			'self'       => array(
				'href' => sprintf( '%1$s/%2$d', $base, $this->get_object_id( $object ) ),
			),
			'collection' => array(
				'href' => $base,
			),
		);

		return $links;

	}


	/**
	 * Register routes.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'lifterlms' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_get_item_params(),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ), // see class-wp-rest-controller.php.
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_delete_item_args(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Update item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function update_item( $request ) {

		$object = $this->get_object( $request['id'] );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$item   = $this->prepare_item_for_database( $request );
		$object = $this->update_object( $item, $request );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$fields_update = $this->update_additional_fields_for_object( $item, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $response );

		return $response;

	}

	/**
	 * Update the object in the database with prepared data.
	 *
	 * @since [version]
	 *
	 * @param array           $prepared Prepared item data.
	 * @param WP_REST_Request $request Request object.
	 * @return obj Object Instance of object from $this->get_object().
	 */
	protected function update_object( $prepared, $request ) {

		// @todo: add version to message.

		// Translators: %s = method name.
		_doing_it_wrong( 'LLMS_REST_Controller::update_object', sprintf( __( "Method '%s' must be overridden.", 'lifterlms' ), __METHOD__ ), '[version]' );

		// For example.
		return $this->get_object( $prepared['id'] );

	}

}
