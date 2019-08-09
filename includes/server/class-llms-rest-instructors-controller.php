<?php
/**
 * REST Resource Controller for Instructors.
 *
 * @package  LifterLMS_REST/Classes/Controllers
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Instructors_Controller class..
 *
 * @since [version]
 */
class LLMS_REST_Instructors_Controller extends LLMS_REST_Users_Controller {

	/**
	 * Resource ID or Name.
	 *
	 * @var string
	 */
	protected $resource_name = 'instructor';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'instructors';

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['post_in'] = array(
			'description' => __( 'Retrieve only instructors for the specified course(s) and/or membership(s). Accepts a single WP Post ID or a comma separated list of IDs.', 'lifterlms' ),
			'type'        => 'string',
		);

		$params['post_not_in'] = array(
			'description' => __( 'Exclude instructors who do not have permissions for the specified course(s) and/or membership(s). Accepts a single WP Post ID or a comma separated list of IDs.', 'lifterlms' ),
			'type'        => 'string',
		);

		return $params;

	}

	/**
	 * Get the item schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();
		$schema['properties']['roles']['default'] = array( 'instructor' );

		return $schema;

	}

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Instructor|WP_Error
	 */
	protected function get_object( $id ) {

		$instructor = llms_get_instructor( $id );
		return $instructor ? $instructor : llms_rest_not_found_error();

	}

}
