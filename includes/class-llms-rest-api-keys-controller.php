<?php
/**
 * REST Controller for API Keys.
 *
 * @package  LifterLMS_REST/Classes
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_API_Keys_Controller class.
 *
 * @since [version]
 */
class LLMS_REST_API_Keys_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'llms/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'api-keys';

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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), // see class-wp-rest-controller.php.
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
		// register_rest_route(
		// $this->namespace,
		// '/' . $this->rest_base . '/(?P<id>[\d]+)',
		// array(
		// 'args'   => array(
		// 'id' => array(
		// 'description' => __( 'Unique identifier for the object.', 'lifterlms' ),
		// 'type'        => 'integer',
		// ),
		// ),
		// array(
		// 'methods'             => WP_REST_Server::READABLE,
		// 'callback'            => array( $this, 'get_item' ),
		// 'permission_callback' => array( $this, 'get_item_permissions_check' ),
		// 'args'                => $get_item_args,
		// ),
		// array(
		// 'methods'             => WP_REST_Server::EDITABLE,
		// 'callback'            => array( $this, 'update_item' ),
		// 'permission_callback' => array( $this, 'update_item_permissions_check' ),
		// 'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ), // see class-wp-rest-controller.php.
		// ),
		// array(
		// 'methods'             => WP_REST_Server::DELETABLE,
		// 'callback'            => array( $this, 'delete_item' ),
		// 'permission_callback' => array( $this, 'delete_item_permissions_check' ),
		// ),
		// 'schema' => array( $this, 'get_public_item_schema' ),
		// )
		// );
		// phpcs:enable Squiz.Commenting.InlineComment.InvalidEndChar
	}

	/**
	 * Get the Course's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'api_key',
			'type'       => 'object',
			'properties' => array(
				'description'   => array(
					'description' => __( 'Friendly, human-readable name or description.', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permissions'   => array(
					'description' => __( 'Determines the capabilities and permissions of the key.', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( LLMS_REST_API()->keys()->get_permissions() ),
				),
				'user_id'       => array(
					'description' => __( 'The WordPress User ID of the key owner.', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'absint',
					),
				),
				'truncated_key' => array(
					'description' => __( 'The last 7 characters of the Consumer Key.', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_access'   => array(
					'description' => __( 'The date the key was last used. Format: Y-m-d H:i:s.', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

	}

}

return new LLMS_REST_API_Keys_Controller();
