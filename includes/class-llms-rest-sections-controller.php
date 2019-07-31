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
	 * Parent id.
	 *
	 * @var int
	 */
	protected $parent_id;

	/**
	 * Constructor.
	 *
	 * @since [version]
	 */
	public function __construct() {

		$this->collection_params = $this->build_collection_params();

	}

	/**
	 * Set parent id.
	 *
	 * @since [version]
	 *
	 * @param int $parent_id Course parent id.
	 * @return void
	 */
	public function set_parent_id( $parent_id ) {
		$this->parent_id = $parent_id;
	}

	/**
	 * Get parent id.
	 *
	 * @since [version]
	 *
	 * @return int|null Course parent id. Null if not set.
	 */
	public function get_parent_id() {
		return isset( $this->parent_id ) ? $this->parent_id : null;
	}


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
	 * @param array $section_args Section args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	protected function create_llms_post( $section_args ) {
		$section = new LLMS_Section( 'new', $section_args );
		return $section && is_a( $section, 'LLMS_Section' ) ? $section : llms_rest_not_found_error();
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
			'required'    => true,
		);

		// Setion order.
		$schema['properties']['order'] = array(
			'description' => __( 'Order of the section within the course.', 'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'absint',
			),
			'required'    => true,
		);

		// remove unnecessary properties.
		$unnecessary_properties = array(
			'permalink',
			'slug',
			'content',
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
	 * @return array The Enrollments collection parameters.
	 */
	public function get_collection_params() {
		return $this->collection_params;
	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @param array $collection_params The Enrollments collection parameters to be set.
	 * @return void
	 */
	public function set_collection_params( $collection_params ) {
		$this->collection_params = $collection_params;
	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function build_collection_params() {

		$query_params = parent::get_collection_params();

		$query_params['parent'] = array(
			'description'       => __( 'Filter sections by the parent post (course) ID.', 'lifterlms' ),
			'type'              => 'integer',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_params['orderby']['enum'] = array(
			'id',
			'title',
			'date_created',
			'date_updated',
			'order',
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

		// Orderby 'order' requires a meta query.
		if ( isset( $query_args['orderby'] ) && 'order' === $query_args['orderby'] ) {
			$query_args = array_merge(
				$query_args,
				array(
					'meta_key' => '_llms_order',
					'orderby'  => 'meta_value_num',
				)
			);
		}

		$parent_id = 0;
		if ( isset( $this->parent_id ) ) {
			$parent_id = $this->parent_id;
		} elseif ( ! empty( $request['parent'] ) && $request['parent'] > 1 ) {
			$parent_id = $request['parent'];
		}

		// Filter by parent.
		if ( ! empty( $parent_id ) ) {
			$query_args = array_merge(
				$query_args,
				array(
					'meta_query' => array(
						array(
							'key'     => '_llms_parent_course',
							'value'   => absint( $parent_id ),
							'compare' => '=',
						),
					),
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

		$links         = parent::prepare_links( $section );
		$parent_course = $section->get_course();

		/**
		 * If the section has no course parent return earlier
		 */
		if ( ! is_a( $parent_course, 'LLMS_Course' ) ) {
			return $links;
		}

		$section_id       = $section->get( 'id' );
		$parent_course_id = $parent_course->get( 'id' );

		$section_links = array();

		// Parent.
		$section_links['parent'] = array(
			'type' => 'course',
			'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'courses', $parent_course_id ) ),
		);

		// Siblings.
		$section_links['siblings'] = array(
			'href' => add_query_arg(
				'parent',
				$parent_course_id,
				$links['collection']['href']
			),
		);

		// Next.
		$next_section = $section->get_next();
		if ( $next_section ) {
			$section_links['next'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $next_section->get( 'id' ) ) ),
			);
		}

		// Previous.
		$previous_section = $section->get_previous();
		if ( $previous_section ) {
			$section_links['previous'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $previous_section->get( 'id' ) ) ),
			);
		}

		return array_merge( $links, $section_links );
	}

	/**
	 * Checks if a Section can be read
	 *
	 * @since [version]
	 *
	 * @param LLMS_Section $section The Section oject.
	 * @return bool Whether the post can be read.
	 */
	protected function check_read_permission( $section ) {

		/**
		 * As of now, sections of password protected courses cannot be read
		 */
		if ( post_password_required( $section->get( 'parent_course' ) ) ) {
			return false;
		}

		return parent::check_read_permission( $section );

	}

}
