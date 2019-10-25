<?php
/**
 * REST Lessons Controller Class
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since 1.0.0-beta.1
 * @version 1.0.0-beta.8
 */

defined( 'ABSPATH' ) || exit;


/**
 * LLMS_REST_Lessons_Controller
 *
 * @since 1.0.0-beta.1
 * @since 1.0.0-beta.7 `prepare_objects_query()` renamed to `prepare_collection_query_args()`.
 *                     Added the following properties to the item schema: `drip_date`, `drip_days`, `drip_method`, `public`, `quiz`.
 *                     Added the following links: `prerequisite`, `quiz`.
 *                     Fixed `siblings` link that was using the parent course's id instead of the parent section's id.
 *                     Fixed `parent` link href, replacing 'section' with 'sections'.
 *                     Added following properties to the response object: `public`, `points`, `quiz`, `drip_method`, `drip_days`, `drip_date`, `prerequisite`.
 *                     Fixed lesson progression callback name when defining the filters to be removed while preparing the item for response.
 *                     Added `llms_rest_lesson_item_schema`, `llms_rest_pre_insert_lesson`, `llms_rest_prepare_lesson_object_response`, `llms_rest_lesson_links` filter hooks.
 *                     Added `prepare_item_for_database()`, `update_additional_object_fields()` method.
 * @since 1.0.0-beta.8 Call `set_bulk()` llms post method passing `true` as second parameter,
 *                     so to instruct it to return a WP_Error on failure.
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
	 * Prepares a single lesson for create or update.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Array of lesson args or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {

		$prepared_item = parent::prepare_item_for_database( $request );
		$schema        = $this->get_item_schema();

		// Lesson's audio embed URL.
		if ( ! empty( $schema['properties']['audio_embed'] ) && isset( $request['audio_embed'] ) ) {
			$prepared_item['audio_embed'] = $request['audio_embed'];
		}

		// Lesson's video embed URL.
		if ( ! empty( $schema['properties']['video_embed'] ) && isset( $request['video_embed'] ) ) {
			$prepared_item['video_embed'] = $request['video_embed'];
		}

		// Parent (section) id.
		if ( ! empty( $schema['properties']['parent_id'] ) && isset( $request['parent_id'] ) ) {
			$prepared_item['parent_section'] = $request['parent_id'];
		}

		// Course id.
		if ( ! empty( $schema['properties']['course_id'] ) && isset( $request['course_id'] ) ) {

			$parent_course = llms_get_post( $request['course_id'] );

			if ( ! $parent_course || ! is_a( $parent_course, 'LLMS_Course' ) ) {
				return llms_rest_bad_request_error( __( 'Invalid course_id param. It must be a valid Course ID.', 'lifterlms' ) );
			}

			$prepared_item['parent_course'] = $request['course_id'];
		}

		// Order.
		if ( ! empty( $schema['properties']['order'] ) && isset( $request['order'] ) ) {

			// order must be > 0. It's sanitized as absint so it cannot come as negative value.
			if ( 0 === $request['order'] ) {
				return llms_rest_bad_request_error( __( 'Invalid order param. It must be greater than 0.', 'lifterlms' ) );
			}

			$prepared_item['order'] = $request['order'];
		}

		// Public (free lesson).
		if ( ! empty( $schema['properties']['public'] ) && isset( $request['public'] ) ) {
			$prepared_item['free_lesson'] = empty( $request['public'] ) ? 'no' : 'yes';
		}

		// Points.
		if ( ! empty( $schema['properties']['points'] ) && isset( $request['points'] ) ) {
			$prepared_item['points'] = $request['points'];
		}

		// Drip days.
		if ( ! empty( $schema['properties']['drip_days'] ) && isset( $request['drip_days'] ) ) {

			// drip_days must be > 0. It's sanitized as absint so it cannot come as negative value.
			if ( 0 === $request['drip_days'] ) {
				return llms_rest_bad_request_error( __( 'Invalid drip_days param. It must be greater than 0.', 'lifterlms' ) );
			}

			$prepared_item['days_before_available'] = $request['drip_days'];
		}

		// Drip date.
		if ( ! empty( $schema['properties']['drip_date'] ) && isset( $request['drip_date'] ) ) {
			$drip_date = rest_parse_date( $request['drip_date'] );

			// Drip date is nullable.
			if ( empty( $drip_date ) ) {
				$prepared_item['date_available'] = '';
				$prepared_item['time_available'] = '';
			} else {
				$prepared_item['date_available'] = date_i18n( 'Y-m-d', $drip_date );
				$prepared_item['time_available'] = date_i18n( 'H:i:s', $drip_date );
			}
		}

		// Drip method.
		if ( ! empty( $schema['properties']['drip_method'] ) && isset( $request['drip_method'] ) ) {
			$prepared_item['drip_method'] = 'none' === $request['drip_method'] ? '' : $request['drip_method'];
		}

		// Quiz enabled.
		if ( ! empty( $schema['properties']['quiz']['properties']['enabled'] ) && isset( $request['quiz']['enabled'] ) ) {
			$prepared_item['quiz_enabled'] = empty( $request['quiz']['enabled'] ) ? 'no' : 'yes';
		}

		// Quiz id.
		if ( ! empty( $schema['properties']['quiz']['properties']['id'] ) && isset( $request['quiz']['id'] ) ) {

			// check if quiz exists.
			$quiz = llms_get_post( $request['quiz']['id'] );

			if ( is_a( $quiz, 'LLMS_Quiz' ) ) {
				$prepared_item['quiz'] = $request['quiz']['id'];
			}
		}

		// Quiz progression.
		if ( ! empty( $schema['properties']['quiz']['properties']['progression'] ) && isset( $request['quiz']['progression'] ) ) {
			$prepared_item['require_passing_grade'] = 'complete' === $request['quiz']['progression'] ? 'no' : 'yes';
		}

		/**
		 * Filters the lesson data for a response.
		 *
		 * @since 1.0.0-beta.7
		 *
		 * @param array           $prepared_item Array of lesson item properties prepared for database.
		 * @param WP_REST_Request $request       Full details about the request.
		 * @param array           $schema        The item schema.
		 */
		return apply_filters( 'llms_rest_pre_insert_lesson', $prepared_item, $request, $schema );

	}

	/**
	 * Updates a single llms lesson.
	 *
	 * @since 1.0.0-beta.7
	 * @since 1.0.0-beta.8 Call `set_bulk()` llms post method passing `true` as second parameter,
	 *                     so to instruct it to return a WP_Error on failure.
	 *
	 * @param LLMS_Lesson     $lesson        LLMS_Lesson instance.
	 * @param WP_REST_Request $request       Full details about the request.
	 * @param array           $schema        The item schema.
	 * @param array           $prepared_item Array.
	 * @param bool            $creating      Optional. Whether we're in creation or update phase. Default true (create).
	 * @return bool|WP_Error True on success or false if nothing to update, WP_Error object if something went wrong during the update.
	 */
	protected function update_additional_object_fields( $lesson, $request, $schema, $prepared_item, $creating = true ) {

		$error = new WP_Error();

		$to_set = array();

		// Prerequisite.
		if ( ! empty( $schema['properties']['prerequisite'] ) && isset( $request['prerequisite'] ) ) {

			// check if lesson exists.
			$prerequisite = llms_get_post( $request['prerequisite'] );

			if ( is_a( $prerequisite, 'LLMS_Lesson' ) ) {
				$to_set['prerequisite'] = $request['prerequisite'];
			} else {
				$to_set['prerequisite'] = 0;
			}
		}

		// Needed until the following will be implemented: https://github.com/gocodebox/lifterlms/issues/908.
		$to_set['has_prerequisite'] = empty( $to_set['prerequisite'] ) ? 'no' : 'yes';

		if ( ! $creating ) {
			if ( $to_set['has_prerequisite'] === $lesson->get( 'has_prerequisite' ) ) {
				unset( $to_set['has_prerequisite'] );
			}
		}

		// Set bulk.
		if ( ! empty( $to_set ) ) {
			$update = $lesson->set_bulk( $to_set, true );
			if ( is_wp_error( $update ) ) {
				$error = $update;
			}
		}

		if ( ! empty( $error->errors ) ) {
			return $error;
		}

		return ! empty( $to_set );

	}

	/**
	 * Get the Lesson's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.7 Added the following properties: drip_date, drip_days, drip_method, public, quiz.
	 *                  Added `llms_rest_lesson_item_schema` filter hook.
	 *
	 * @return array Item schema data.
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
				'description' => __( 'Determines the weight of the lesson when grading the course.', 'lifterlms' ),
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
				'description' => __( 'Associate a quiz with this lesson.', 'lifterlms' ),
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

		/**
		 * Filter item schema for the lessons controller.
		 *
		 * @since 1.0.0-beta.7
		 *
		 * @param array $schema Item schema data.
		 */
		return apply_filters( 'llms_rest_lesson_item_schema', $schema );

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
	 * @since 1.0.0-beta.7 Added following properties to the response object:
	 *                  public, points, quiz, drip_method, drip_days, drip_date, prerequisite, audio_embed, video_embed.
	 *                  Added `llms_rest_prepare_lesson_object_response` filter hook.
	 *
	 * @param LLMS_Lesson     $lesson Lesson object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $lesson, $request ) {

		$data = parent::prepare_object_for_response( $lesson, $request );

		// Audio Embed.
		$data['audio_embed'] = $lesson->get( 'audio_embed' );

		// Video Embed.
		$data['video_embed'] = $lesson->get( 'video_embed' );

		// Parent section.
		$data['parent_id'] = $lesson->get_parent_section();

		// Parent course.
		$data['course_id'] = $lesson->get_parent_course();

		// Order.
		$data['order'] = $lesson->get( 'order' );

		// Public.
		$data['public'] = $lesson->is_free();

		// Points.
		$data['points'] = $lesson->get( 'points' );

		// Quiz.
		$data['quiz']['enabled']     = llms_parse_bool( $lesson->get( 'quiz_enabled' ) );
		$data['quiz']['id']          = absint( $lesson->get( 'quiz' ) );
		$data['quiz']['progression'] = llms_parse_bool( $lesson->get( 'require_passing_grade' ) ) ? 'pass' : 'completed';

		// Drip method.
		$data['drip_method'] = $lesson->get( 'drip_method' );
		$data['drip_method'] = $data['drip_method'] ? $data['drip_method'] : 'none';

		// Drip days.
		$data['drip_days'] = absint( $lesson->get( 'days_before_available' ) );

		// Drip date.
		$date = $lesson->get( 'date_available' );
		if ( $date ) {
			$time = $lesson->get( 'time_available' );

			if ( ! $time ) {
				$time = '12:00 AM';
			}

			$drip_date = date_i18n( 'Y-m-d H:i:s', strtotime( $date . ' ' . $time ) );
		} else {
			$drip_date = '';
		}

		$data['drip_date'] = $drip_date;

		// Prerequisite.
		$data['prerequisite'] = absint( $lesson->get_prerequisite() );

		/**
		 * Filters the lesson data for a response.
		 *
		 * @since 1.0.0-beta.7
		 *
		 * @param array           $data    Array of lesson properties prepared for response.
		 * @param LLMS_Lesson     $lesson  Lesson object.
		 * @param WP_REST_Request $request Full details about the request.
		 */
		return apply_filters( 'llms_rest_prepare_lesson_object_response', $data, $lesson, $request );

	}

	/**
	 * Format query arguments to retrieve a collection of objects.
	 *
	 * @since 1.0.0-beta.7
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
	 * @since 1.0.0-beta.7 Fixed lesson progression callback name.
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
					'callback' => 'lifterlms_template_complete_lesson_link',
					'priority' => 10,
				),
			),
		);

	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.7 Fixed `siblings` link that was using the parent course's id instead of the parent section's id.
	 *                  Fixed `parent` link href, replacing 'section' with 'sections'.
	 *                  Following links added: `prerequisite`, `quiz`.
	 *                  Added `llms_rest_lesson_links` filter hook.
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
		if ( $lesson->is_quiz_enabled() ) {
			$quiz                 = $lesson->get_quiz();
			$lesson_links['quiz'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%d', 'llms/v1', 'quizzes', $quiz->get( 'id' ) ) ),
			);
		}

		$links = array_merge( $links, $lesson_links );

		/**
		 * Filters the lesson's links.
		 *
		 * @since 1.0.0-beta.7
		 *
		 * @param array       links   Links for the given lesson.
		 * @param LLMS_Lesson $lesson Lesson object.
		 */
		return apply_filters( 'llms_rest_lesson_links', $links, $lesson );

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
