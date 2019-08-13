<?php
/**
 * REST Lessons Controller Class
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;


/**
 * LLMS_REST_Lessons_Controller
 *
 * @since [version]
 */
class LLMS_REST_Lessons_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'lessons';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'lesson';

	/**
	 * Schema properties available for ordering the collection.
	 *
	 * @var string[]
	 */
	protected $orderby_properties = array(
		'id',
		'title',
		'date_created',
		'date_updated',
		'order',
	);

	/**
	 * Constructor.
	 *
	 * @since [version]
	 */
	public function __construct() {

		$this->collection_params = $this->build_collection_params();

	}

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Lesson|WP_Error
	 */
	protected function get_object( $id ) {
		$lesson = llms_get_post( $id );
		return $lesson && is_a( $lesson, 'LLMS_Lesson' ) ? $lesson : llms_rest_not_found_error();
	}

	/**
	 * Get an LLMS_Lesson
	 *
	 * @since [version]
	 *
	 * @param array $lesson_args Lesson args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	protected function create_llms_post( $lesson_args ) {
		$lesson = new LLMS_Lesson( 'new', $lesson_args );
		return $lesson && is_a( $lesson, 'LLMS_Lesson' ) ? $lesson : llms_rest_not_found_error();
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

		return $query_params;

	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Lesson     $lesson Lesson object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $lesson, $request ) {

		$data = parent::prepare_object_for_response( $lesson, $request );

		// Parent section.
		$data['parent_id'] = $lesson->get_parent_section();
		// Parent course.
		$data['course_id'] = $lesson->get_parent_course();

		// Order.
		$data['order'] = $lesson->get( 'order' );

		return $data;

	}

	/**
	 * Prepare links for the request.
	 *
	 * @param LLMS_Lesson $lesson  LLMS Section.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $lesson ) {

		$links = parent::prepare_links( $lesson );

		unset( $links['content'] );

		$lesson_id         = $lesson->get( 'id' );
		$parent_course_id  = $lesson->get_parent_course();
		$parent_section_id = $lesson->get_parent_section();

		$lesson_links = array();

		// Parent course.
		if ( $parent_course_id ) {
			$lesson_links['course'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'courses', $parent_course_id ) ),
			);
		}

		// Parent (section).
		if ( $parent_section_id ) {
			$lesson_links['parent'] = array(
				'type' => 'section',
				'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'section', $parent_section_id ) ),
			);
		}

		// Siblings.
		$lesson_links['siblings'] = array(
			'href' => add_query_arg(
				'parent',
				$parent_course_id,
				$links['collection']['href']
			),
		);

		// Next.
		$next_lesson = $lesson->get_next_lesson();
		if ( $next_lesson ) {
			$lesson_links['next'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $next_lesson ) ),
			);
		}

		// Previous.
		$previous_lesson = $lesson->get_previous_lesson();
		if ( $previous_lesson ) {
			$lesson_links['previous'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $previous_lesson ) ),
			);
		}

		return array_merge( $links, $lesson_links );
	}

	/**
	 * Checks if a Lesson can be read
	 *
	 * @since [version]
	 *
	 * @param LLMS_Lesson $lesson The Lesson oject.
	 * @return bool Whether the post can be read.
	 */
	protected function check_read_permission( $lesson ) {

		/**
		 * As of now, lessons of password protected courses cannot be read
		 */
		if ( post_password_required( $lesson->get( 'parent_course' ) ) ) {
			return false;
		}

		return parent::check_read_permission( $lesson );

	}

	/**
	 * Get a collection of content items (lessons).
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_content_items( $request ) {

		$this->content_controller->set_parent_id( $request['id'] );
		$result = $this->content_controller->get_items( $request );

		// Specs require 404 when no course's lessons are found.
		if ( ! is_wp_error( $result ) && empty( $result->data ) ) {
			return llms_rest_not_found_error();
		}

		return $result;

	}

}
