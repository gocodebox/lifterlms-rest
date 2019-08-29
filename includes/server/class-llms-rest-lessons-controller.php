<?php
/**
 * REST Lessons Controller Class
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since 1.0.0-beta.1
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;


/**
 * LLMS_REST_Lessons_Controller
 *
 * @since 1.0.0-beta.1
 * @since [version] `prepare_objects_query()` renamed to `prepare_collection_query_args()`.
 *                  Added the following properties: assignment, drip_date, drip_days, drip_method, public, quiz.
 *                  Added the following links: prerequisite, quiz, assignment.
 *                  Fixed `siblings` link that was using the parent course's id instead of the parent section's id.
 *                  Fixed `parent` link href, replacing 'section' with 'sections'.
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
	 * Parent section id.
	 *
	 * @var int
	 */
	protected $parent_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function __construct() {

		$this->collection_params = $this->build_collection_params();

	}

	/**
	 * Set parent id.
	 *
	 * @since 1.0.0-beta.1
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
	 * @since 1.0.0-beta.1
	 *
	 * @return int|null Course parent id. Null if not set.
	 */
	public function get_parent_id() {
		return isset( $this->parent_id ) ? $this->parent_id : null;
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0-beta.1
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
	 * @since 1.0.0-beta.1
	 *
	 * @param array $lesson_args Lesson args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	protected function create_llms_post( $lesson_args ) {
		$lesson = new LLMS_Lesson( 'new', $lesson_args );
		return $lesson && is_a( $lesson, 'LLMS_Lesson' ) ? $lesson : llms_rest_not_found_error();
	}

	/**
	 * Get the Lesson's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0-beta.1
	 * @since [version] Added the following properties: assignment, drip_date, drip_days, drip_method, public, quiz.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		$lesson_properties = array(
			'parent_id'    => array(
				'description' => __( 'WordPress post ID of the parent item. Must be a Section ID. 0 indicates an "orphaned" lesson which can be edited and viewed by instructors and admins but cannot be read by students.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'course_id'    => array(
				'description' => __( 'WordPress post ID of the lesson\'s parent course.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'order'        => array(
				'description' => __( 'Order of the lesson within its immediate parent.', 'lifterlms' ),
				'type'        => 'integer',
				'default'     => 1,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
				'required'    => true,
			),
			'prerequisite' => array(
				'description' => __( 'Lesson ID of the prerequisite lesson.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'points'       => array(
				'description' => __( 'Determines the weight of the lesson when grading the course.', 'literlms' ),
				'type'        => 'integer',
				'default'     => 1,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'audio_embed'  => array(
				'description' => __( 'URL to an oEmbed enable audio URL.', 'lifterlms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'format'      => 'uri',
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'video_embed'  => array(
				'description' => __( 'URL to an oEmbed enable video URL.', 'lifterlms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'format'      => 'uri',
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'assignment'   => array(
				'description' => __( 'Associate an assignment with this lesson. While assignment functionality is included with the LifterLMS Core REST API, the assignments themselves are implemented by the LifterLMS Assignments add-on.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
				),
				'properties'  => array(
					'enabled'     => array(
						'description' => __( 'Determines if an assignment is enabled for the lesson.', 'lifterlms' ),
						'type'        => 'boolean',
						'default'     => false,
						'context'     => array( 'view', 'edit' ),
					),
					'id'          => array(
						'description' => __( 'The post ID of the associated assingment.', 'lifterlms' ),
						'type'        => 'integer',
						'default'     => 0,
						'context'     => array( 'view', 'edit' ),
						'arg_options' => array(
							'sanitize_callback' => 'absint',
						),
					),
					'progression' => array(
						'description' => __(
							'Determines lesson progression requirements related to the assignment.
							<ul>
								<li>complete: The assignment must be completed (with any grade) to progress the lesson.</li>
								<li>pass: A passing grade must be earned to progress the lesson.</li>
							</ul>',
							'lifterlms'
						),
						'type'        => 'string',
						'default'     => 'complete',
						'enum'        => array( 'complete', 'pass' ),
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
			'drip_date'    => array(
				'description' => __(
					'The date and time when the lesson becomes available. Applicable only when drip_method is date. Format: Y-m-d H:i:s.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'drip_days'    => array(
				'description' => __( 'Number of days to wait before allowing access to the lesson. Applicable only when drip_method is enrollment, start, or prerequisite.', 'lifterlms' ),
				'type'        => 'integer',
				'default'     => 1,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'drip_method'  => array(
				'description' => __(
					'Determine the method with which to make the lesson content available.
					<ul>
						<li>none: Drip is disabled; the lesson is immediately available.</li>
						<li>date: Lesson is made available at a specific date and time.</li>
						<li>enrollment: Lesson is made available a specific number of days after enrollment into the course.</li>
						<li>start: Lesson is made available a specific number of days after the course\'s start date. Only available on courses with a access_opens_date.</li>
						<li>prerequisite: Lesson is made available a specific number of days after the prerequisite lesson is completed.</li>
					</ul>',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'none',
				'enum'        => array( 'none', 'date', 'enrollment', 'start', 'prerequisite' ),
				'context'     => array( 'view', 'edit' ),
			),
			'public'       => array(
				'description' => __( 'Denotes a lesson that\'s publicly accessible regardless of course enrollment.', 'lifterlms' ),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
			'quiz'         => array(
				'description' => __( 'Associate an assignment with this lesson. While assignment functionality is included with the LifterLMS Core REST API, the assignments themselves are implemented by the LifterLMS Assignments add-on.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
				),
				'properties'  => array(
					'enabled'     => array(
						'description' => __( 'Determines if a quiz is enabled for the lesson.', 'lifterlms' ),
						'type'        => 'boolean',
						'default'     => false,
						'context'     => array( 'view', 'edit' ),
					),
					'id'          => array(
						'description' => __( 'The post ID of the associated quiz.', 'lifterlms' ),
						'type'        => 'integer',
						'default'     => 0,
						'context'     => array( 'view', 'edit' ),
						'arg_options' => array(
							'sanitize_callback' => 'absint',
						),
					),
					'progression' => array(
						'description' => __(
							'Determines lesson progression requirements related to the quiz.
							<ul>
								<li>complete: The quiz must be completed (with any grade) to progress the lesson.</li>
								<li>pass: A passing grade must be earned to progress the lesson.</li>
							</ul>',
							'lifterlms'
						),
						'type'        => 'string',
						'default'     => 'complete',
						'enum'        => array( 'complete', 'pass' ),
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
		);

		$schema['properties'] = array_merge( (array) $schema['properties'], $lesson_properties );

		return $schema;

	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @return array The Enrollments collection parameters.
	 */
	public function get_collection_params() {
		return $this->collection_params;
	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since 1.0.0-beta.1
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
	 * @since 1.0.0-beta.1
	 *
	 * @return array Collection parameters.
	 */
	public function build_collection_params() {

		$query_params = parent::get_collection_params();

		$query_params['parent'] = array(
			'description'       => __( 'Filter lessons by the parent post (section) ID.', 'lifterlms' ),
			'type'              => 'integer',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;

	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since 1.0.0-beta.1
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
	 * Format query arguments to retrieve a collection of objects.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_collection_query_args( $request ) {

		$query_args = parent::prepare_collection_query_args( $request );

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
							'key'     => '_llms_parent_section',
							'value'   => $parent_id,
							'compare' => '=',
						),
					),
				)
			);
		}

		return $query_args;
	}

	/**
	 * Get action/filters to be removed before preparing the item for response.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param LLMS_Section $lesson Lesson object.
	 * @return array Array of action/filters to be removed for response.
	 */
	protected function get_filters_to_be_removed_for_response( $lesson ) {

		if ( ! llms_blocks_is_post_migrated( $lesson->get( 'id' ) ) ) {
			return array();
		}

		return array(
			// hook => [callback, priority].
			'lifterlms_single_lesson_after_summary' => array(
				// Lesson Navigation.
				array(
					'callback' => 'lifterlms_template_lesson_navigation',
					'priority' => 20,
				),
				// Lesson Progression.
				array(
					'callback' => 'lifterlms_single_lesson_after_summary',
					'priority' => 10,
				),
			),
		);

	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 1.0.0-beta.1
	 * @since [version] Fixed `siblings` link that was using the parent course's id instead of the parent section's id.
	 *                  Fixed `parent` link href, replacing 'section' with 'sections'.
	 *                  Following links added: prerequisite, quiz, assignment.
	 *
	 * @param LLMS_Lesson $lesson LLMS Section.
	 * @return array Links for the given object..
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

		// Parent section.
		if ( $parent_section_id ) {
			$lesson_links['parent'] = array(
				'type' => 'section',
				'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'sections', $parent_section_id ) ),
			);
		}

		// Siblings.
		$lesson_links['siblings'] = array(
			'href' => add_query_arg(
				'parent',
				$parent_section_id,
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

		// Prerequisite.
		$prerequisite = $lesson->get_prerequisite();

		if ( ! empty( $prerequisite ) ) {
			$lesson_links['prerequisite'] = array(
				'type' => $this->post_type,
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $prerequisite ) ),
			);
		}

		// Quiz.
		$quiz = $lesson->get_quiz();
		if ( ! empty( $quiz ) ) {
			$lesson_links['quiz'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'quizzes', $quiz->get( 'id' ) ) ),
			);
		}

		// Assignment.
		if ( function_exists( 'llms_lesson_get_assignment' ) ) {

			$assignment = llms_lesson_get_assignment( $lesson );
			if ( $assignment ) {
				$lesson_links['assignment'] = array(
					'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'assignments', $assignment->get( 'id' ) ) ),
				);
			}
		}

		return array_merge( $links, $lesson_links );
	}

	/**
	 * Checks if a Lesson can be read
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param LLMS_Lesson $lesson The Lesson oject.
	 * @return bool Whether the post can be read.
	 *
	 * @todo Implement read permission based on the section's id:
	 * 0 indicates an "orphaned" lesson which can be edited and viewed by instructors and admins but cannot be read by students.
	 */
	protected function check_read_permission( $lesson ) {

		/**
		 * As of now, lessons of password protected courses cannot be read
		 */
		if ( post_password_required( $lesson->get_parent_course() ) ) {
			return false;
		}

		/**
		 * At the moment we grant lessons read permission only to who can edit lessons.
		 */
		return parent::check_update_permission( $lesson );

	}

}
