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

	function check_read_permission( $object ) {
		return current_user_can( 'manage_lifterlms' );
	}
}
