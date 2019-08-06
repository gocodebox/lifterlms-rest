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

		return $response;

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

}
