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
	 * @since [version]
	 *
	 * @param LLMS_Order      $object  Object data.
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

		$product = $object->get_product();
		if ( ! $product ) {
			return $links;
		}

		switch ( $product->get( 'type' ) ) {
			case 'course':
				$links['product'] = array(
					'href'       => rest_url(
						sprintf( '/%s/%s/%d', 'llms/v1', 'courses', $object->get( 'product_id' ) )
					),
					'embeddable' => true,
				);
				break;
			case 'llms_membership':
				$links['product'] = array(
					'href'       => rest_url(
						sprintf( '/%s/%s/%d', 'llms/v1', 'memberships', $object->get( 'product_id' ) )
					),
					'embeddable' => true,
				);
				break;
		}

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

		$data['status']          = str_replace( 'llms-', '', $data['status'] );
		$data['billing_email']   = $order->get( 'billing_email' );
		$data['payment_gateway'] = $order->get( 'payment_gateway' );
		$data['coupon_amount']   = $order->get( 'coupon_amount' );
		$data['original_total']  = $order->get( 'original_total' );
		$data['product_id']      = $order->get( 'product_id' );
		$data['sale_value']      = $order->get( 'sale_value' );

		return $data;
	}

	/**
	 * Get the order's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array Item schema data.
	 */
	protected function get_item_schema_base() {

		$schema = (array) parent::get_item_schema_base();

		$order_properties = array(
			'billing_email'   => array(
				'description' => __( 'Billing email address for the order.', 'lifterlms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
			'payment_gateway' => array(
				'description' => __( 'Payment gateway used for the order.', 'lifterlms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
			'coupon_amount'   => array(
				'description' => __( 'Total amount of any coupons applied to the order.', 'lifterlms' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
			'original_total'  => array(
				'description' => __( 'Original total amount of the order before any discounts or coupons.', 'lifterlms' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
			'product_id'      => array(
				'description' => __( 'ID of the product associated with the order.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
			'sale_value'      => array(
				'description' => __( 'Sale value of the order.', 'lifterlms' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
		);

		$schema['properties'] = array_merge( (array) $schema['properties'], $order_properties );

		return $schema;
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
