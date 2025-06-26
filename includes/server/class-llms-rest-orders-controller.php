<?php
/**
 * REST Orders Controller.
 *
 * @package LLMS_REST
 *
 * @since [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LifterLMS REST API Orders Controller
 */
class LLMS_REST_Orders_Controller extends LLMS_REST_Posts_Controller {
	protected $post_type = 'llms_order';
	protected $rest_base = 'orders';

	function prepare_collection_query_args( $request ) {
		$query_args = parent::prepare_collection_query_args( $request );
		if ( is_wp_error( $query_args ) ) {
			return $query_args;
		}

		$query_args['post_status'] = llms_get_order_statuses();

		return $query_args;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.2 Filter taxonomies by `public` property instead of `show_in_rest`.
	 * @since 1.0.0-beta.3 Filter taxonomies by `show_in_llms_rest` property instead of `public`.
	 * @since 1.0.0-beta.7 `self` and `collection` links prepared in the parent class.
	 *                     Fix wp:featured_media link, we don't expose any embeddable field.
	 * @since 1.0.0-beta.8 Return links to those taxonomies which have an accessible rest route.
	 * @since 1.0.0-beta.14 Added $request parameter.
	 *
	 * @param LLMS_Post_Model $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $object, $request ) {

		$links = parent::prepare_links( $object, $request );

		$links['student'] = array(
			'href'       => rest_url(
				sprintf( '/%s/%s/%d', 'llms/v1', 'students', $object->get( 'user_id' ) )
			),
			'embeddable' => true,
		);

		return $links;
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Order      $order  Lesson object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $order, $request ) {

		$data = parent::prepare_object_for_response( $order, $request );

		$data['status'] = str_replace( 'llms-', '', $data['status'] );

		return $data;
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
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	function check_read_permission( $object ) {
		return current_user_can( 'manage_lifterlms' );
	}
}
