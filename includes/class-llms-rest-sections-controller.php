<?php
/**
 * REST Sections Controller Class
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;


/**
 * LLMS_REST_Sections_Controller
 *
 * @since [version]
 */
class LLMS_REST_Sections_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'sections';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'section';

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Section|WP_Error
	 */
	protected function get_object( $id ) {
		$section = llms_get_post( $id );
		return $section && is_a( $section, 'LLMS_Section' ) ? $section : llms_rest_not_found_error();
	}

	/**
	 * Get an LLMS_Section
	 *
	 * @since [version]
	 *
	 * @param array $object_args Object args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	protected function create_llms_post( $object_args ) {
		$object = new LLMS_Section( 'new', $object_args );
		return $object && is_a( $object, 'LLMS_Section' ) ? $object : llms_rest_not_found_error();
	}

	/**
	 * Get the Section's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		// Section's parent id.
		$schema['properties']['parent_id'] = array(
			'description' => __( 'WordPress post ID of the parent item. Must be a Course ID.', 'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'absint',
			),
		);

		// Setion order.
		$schema['properties']['order'] = array(
			'description' => __( 'Order of the section within the course.', 'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'absint',
			),
		);

		// remove unnecessary properties.
		$unnecessary_properties = array(
			'permalink',
			'slug',
			'menu_order',
			'excerpt',
			'featured_media',
			'status',
			'password',
			'featured_media',
			'comment_status',
			'ping_status',
		);

		foreach ( $unnecessary_properties as $unnecessary_property ) {
			unset( $schema['properties'][ $unnecessary_property ] );
		}

		return $schema;

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

		$query_params['parent'] = array(
			'description' => __( 'Filter sections by the parent post (course) ID.', 'lifterlms' ),
			'type'        => 'integer',
		);

		return $query_params;
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Section    $section Section object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $section, $request ) {

		$data = parent::prepare_object_for_response( $section, $request );

		// Parent course.
		$data['parent_id'] = $section->get( 'parent_course' );

		// Order.
		$data['order'] = $section->get( 'order' );

		return $data;

	}

	/**
	 * Prepare objects query.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$query_args = parent::prepare_objects_query( $request );

		// Maybe filter by parent.
		if ( ! empty( $request['parent'] ) && $request['parent'] > 1 ) {
			/**
			 * Note: we can improve the core Section class in order to ask it the meta query we need here.
			 */
			$query_args = array_merge(
				$query_args,
				array(
					'meta_key'   => '_llms_parent_course',
					'meta_value' => absint( $request['parent'] ),
				)
			);
		}

		return $query_args;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param LLMS_Section $section  LLMS Section.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $section ) {
		$links      = parent::prepare_links( $section );
		$section_id = $section->get( 'id' );

		$section_links = array();

		// Content.
		$section_links['content'] = array(
			'href' => add_query_arg(
				'parent',
				$section_id,
				$links['content']['href']
			),
		);

		// Parent.
		$section_links['parent'] = array(
			'type' => 'course',
			'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'courses', $section->get( 'parent_course' ) ) ),
		);

		// Siblings.
		$section_links['siblings'] = array(
			'href' => add_query_arg(
				'parent',
				$section_id,
				$links['self']['href']
			),
		);

		return array_merge( $links, $section_links );
	}

}
