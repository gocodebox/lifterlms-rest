<?php
/**
 * REST Courses Controller Class
 *
 * @package LLMS_REST
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;


/**
 * LLMS_REST_Courses_Controller
 *
 * @since [version]
 */
class LLMS_REST_Courses_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'courses';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'course';


	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Course|WP_Error
	 */
	protected function get_object( $id ) {
		$course = llms_get_post( $id );
		return $course && is_a( $course, 'LLMS_Course' ) ? $course : llms_rest_not_found_error();
	}


	/**
	 * Get an LLMS_Course
	 *
	 * @since [version]
	 *
	 * @param array $object_args Object args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	protected function create_llms_post( $object_args ) {
		$object = new LLMS_Course( 'new', $object_args );
		return $object && is_a( $object, 'LLMS_Course' ) ? $object : llms_rest_not_found_error();
	}

	/**
	 * Get the Course's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		$course_properties = array(
			'catalog_visibility'        => array(
				'description' => __( 'Visibility of the course in catalogs and search results.', 'lifterlms' ),
				'type'        => 'string',
				'enum'        => array_keys( llms_get_product_visibility_options() ),
				'default'     => 'catalog_search',
				'context'     => array( 'view', 'edit' ),
			),
			// consider to move tags and cats in the posts controller abstract.
			'categories'                => array(
				'description' => __( 'List of course categories.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'tags'                      => array(
				'description' => __( 'List of course tags.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'difficulties'              => array(
				'description' => __( 'List of course difficulties.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'tracks'                    => array(
				'description' => __( 'List of course tracks.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'instructors'               => array(
				'description' => __( 'List of course instructors.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'audio_embed'               => array(
				'description' => __( 'URL to an oEmbed enable audio URL.', 'lifterlms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'video_embed'               => array(
				'description' => __( 'URL to an oEmbed enable video URL.', 'lifterlms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'capacity_enabled'          => array(
				'description' => __( 'Determines if an enrollment capacity limit is enabled.', 'lifterlms' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'capacity_limit'            => array(
				'description' => __( 'Number of students who can be enrolled in the course before enrollment closes.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'capacity_message'          => array(
				'description' => __( 'Message displayed when enrollment capacity has been reached.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'prerequisite'              => array(
				'description' => __( 'Course ID of the prerequisite course.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'prerequisite_track'        => array(
				'description' => __( 'Term ID of a prerequisite track.', 'lifterlms' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'length'                    => array(
				'description' => __( 'User defined course length.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw length description.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered length description.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'restricted_message'        => array(
				'description' => __( 'Message displayed when non-enrolled visitors try to access restricted course content (lessons, quizzes, etc..) directly.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'default'     => __( 'You must enroll in this course to access course content.', 'lifterlms' ),
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'default'     => __( 'You must enroll in this course to access course content.', 'lifterlms' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'access_closes_date'        => array(
				'description' => __(
					'Date when the course closes. After this date enrolled students may no longer view and interact with the restricted course content.
					If blank the course is open indefinitely after the the access_opens_date has passed.
					Does not affect course enrollment, see enrollment_opens_date to control the course enrollment close date.
					Format: Y-m-d H:i:s.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'access_closes_message'     => array(
				'description' => __( 'Message displayed to enrolled students when the course is accessed after the access_closes_date has passed.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'default'     => __( 'This course closed on [lifterlms_course_info key="end_date"].', 'lifterlms' ),
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'access_opens_date'         => array(
				'description' => __(
					'Date when the course opens, allowing enrolled students to begin to view and interact with the restricted course content.
					If blank the course is open until after the access_closes_date has passed.
					Does not affect course enrollment, see enrollment_opens_date to control the course enrollment start date.
					Format: Y-m-d H:i:s.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'access_opens_message'      => array(
				'description' => __( 'Message displayed to enrolled students when the course is accessed before the access_opens_date has passed.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'default'     => __( 'This course opens on [lifterlms_course_info key="start_date"].', 'lifterlms' ),
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'enrollment_closes_date'    => array(
				'description' => __(
					'Date when the course enrollment closes.
					If blank course enrollment is open indefinitely after the the enrollment_opens_date has passed.
					Does not affect course content access, see access_opens_date to control course access close date.
					Format: Y-m-d H:i:s.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'enrollment_closes_message' => array(
				'description' => __( 'Message displayed to visitors when attempting to enroll into a course after the enrollment_closes_date has passed.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'default'     => __( 'Enrollment in this course closed on [lifterlms_course_info key="enrollment_end_date"].', 'lifterlms' ),
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'enrollment_opens_date'     => array(
				'description' => __(
					'Date when the course enrollment opens.
					If blank course enrollment is open until after the enrollment_closes_date has passed.
					Does not affect course content access, see access_opens_date to control course access start date.
					Format: Y-m-d H:i:s.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'enrollment_opens_message'  => array(
				'description' => __( 'Message displayed to visitors when attempting to enroll into a course before the enrollment_opens_date has passed.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'default'     => __( 'Enrollment in this course opens on [lifterlms_course_info key="enrollment_start_date"].', 'lifterlms' ),
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'sales_page_page_id'        => array(
				'description' => __(
					'The WordPress page ID of the sales page. Required when sales_page_type equals page. Only returned when the sales_page_type equals page.',
					'lifterlms'
				),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'sales_page_page_type'      => array(
				'description' => __(
					'Determines the type of sales page content to display.<br> - <code>none</code> displays the course content.<br> - <code>content</code> displays alternate content from the <code>excerpt</code> property.<br> - <code>page</code> redirects to the WordPress page defined in <code>content_page_id</code>.<br> - <code>url</code> redirects to the URL defined in <code>content_page_url</code>',
					'lifterlms'
				),
				'type'        => 'string',
				'enum'        => array_keys( llms_get_sales_page_types() ),
				'context'     => array( 'view', 'edit' ),
			),
			'sales_page_page_url'       => array(
				'description' => __(
					'The URL of the sales page content. Required when <code>content_type</code> equals <code>url</code>. Only returned when the <code>content_type</code> equals <code>url</code>.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'video_tile'                => array(
				'description' => __( 'When true the video_embed will be used on the course tiles (on the catalog, for example) instead of the featured image.', 'lifterlms' ),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
		);

		$schema['properties'] = array_merge( (array) $schema['properties'], $course_properties );
		return $schema;

	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Course     $course  Course object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $course, $request ) {

		$data = parent::prepare_object_for_response( $course, $request );

		// Catalog visibility.
		$data['catalog_visibility'] = $course->get_product()->get_catalog_visibility();

		// Categories.
		$data['categories'] = $course->get_categories(
			array(
				'fields' => 'ids',
			)
		);

		// Tags.
		$data['tags'] = $course->get_tags(
			array(
				'fields' => 'ids',
			)
		);

		// Difficulties.
		$difficulties         = get_the_terms( $course->get( 'id' ), 'course_difficulty' );
		$difficulties         = empty( $difficulties ) ? array() : $difficulties;
		$data['difficulties'] = $difficulties;

		// Tracks.
		$data['tracks'] = $course->get_tracks(
			array(
				'fields' => 'ids',
			)
		);

		// Instructors.
		$instructors         = $course->get_instructors();
		$instructors         = empty( $instructors ) ? array() : wp_list_pluck( $instructors, 'id' );
		$data['instructors'] = $instructors;

		// Audio Embed.
		$data['audio_embed'] = $course->get( 'audio_embed' );

		// Video Embed.
		$data['video_embed'] = $course->get( 'video_embed' );

		// Video tile.
		$data['video_tile'] = 'yes' === $course->get( 'tile_featured_video' );

		// Capacity.
		$data['capacity_enabled'] = 'yes' === $course->get( 'enable_capacity' );

		$data['capacity_limit']   = $course->get( 'capacity' );
		$data['capacity_message'] = array(
			'raw'      => $course->get( 'capacity_message', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'capacity_message' ) ),
		);

		// Prerequisite.
		$data['prerequisite'] = (int) $course->get_prerequisite_id();

		// Prerequisite track.
		$data['prerequisite_track'] = (int) $course->get_prerequisite_id( 'course_track' );

		// Length.
		$data['length'] = array(
			'raw'      => $course->get( 'length', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'length' ) ),
		);

		// Restricted message.
		$data['restricted_message'] = array(
			'raw'      => $course->get( 'content_restricted_message', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'content_restricted_message' ) ),
		);

		// Enrollment open/closed.
		$data['access_opens_date']  = $course->get_date( 'start_date', 'Y-m-d H:i:s' );
		$data['access_closes_date'] = $course->get_date( 'end_date', 'Y-m-d H:i:s' );

		$data['access_opens_message'] = array(
			'raw'      => $course->get( 'course_opens_message', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'course_opens_message' ) ),
		);

		$data['access_closes_message'] = array(
			'raw'      => $course->get( 'course_closed_message', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'course_closed_message' ) ),
		);

		// Enrollment open/closed.
		$data['enrollment_opens_date']  = $course->get_date( 'enrollment_start_date', 'Y-m-d H:i:s' );
		$data['enrollment_closes_date'] = $course->get_date( 'enrollment_end_date', 'Y-m-d H:i:s' );

		$data['enrollment_opens_message'] = array(
			'raw'      => $course->get( 'enrollment_opens_message', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'enrollment_opens_message' ) ),
		);

		$data['enrollment_closes_message'] = array(
			'raw'      => $course->get( 'enrollment_closed_message', $raw = true ),
			'rendered' => do_shortcode( $course->get( 'enrollment_closed_message' ) ),
		);

		// Sales page page type.
		$data['sales_page_page_type'] = $course->get( 'sales_page_content_type' );

		// Sales page id/url.
		if ( 'page' === $data['sales_page_page_type'] ) {
			$data['sales_page_page_id'] = $course->get( 'sales_page_content_page_id' );
		} elseif ( 'url' === $data['sales_page_page_type'] ) {
			$data['sales_page_page_url'] = $course->get( 'sales_page_content_url' );
		}

		return $data;

	}

	/**
	 * Get action/filters to be removed before preparing the item for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Course $course Course object.
	 * @return array Array of action/filters to be removed for response.
	 */
	protected function get_filters_to_be_removed_for_response( $course ) {

		if ( ! llms_blocks_is_post_migrated( $course->get( 'id' ) ) ) {
			return array();
		}

		return array(
			// hook => [callback, priority].
			'lifterlms_single_course_after_summary' => array(
				// Course Information.
				array(
					'callback' => 'lifterlms_template_single_meta_wrapper_start',
					'priority' => 5,
				),
				array(
					'callback' => 'lifterlms_template_single_length',
					'priority' => 10,
				),
				array(
					'callback' => 'lifterlms_template_single_difficulty',
					'priority' => 20,
				),
				array(
					'callback' => 'lifterlms_template_single_course_tracks',
					'priority' => 25,
				),
				array(
					'callback' => 'lifterlms_template_single_course_categories',
					'priority' => 30,
				),
				array(
					'callback' => 'lifterlms_template_single_course_tags',
					'priority' => 35,
				),
				array(
					'callback' => 'lifterlms_template_single_meta_wrapper_end',
					'priority' => 50,
				),
				// Course Progress.
				array(
					'callback' => 'lifterlms_template_single_course_progress',
					'priority' => 60,
				),
				// Course Syllabus.
				array(
					'callback' => 'lifterlms_template_single_syllabus',
					'priority' => 90,
				),
				// Instructors.
				array(
					'callback' => 'lifterlms_template_course_author',
					'priority' => 40,
				),
				// Pricing Table.
				array(
					'callback' => 'lifterlms_template_pricing_table',
					'priority' => 60,
				),
			),
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param LLMS_Course $course  LLMS Course.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $course ) {
		$links     = parent::prepare_links( $course );
		$course_id = $course->get( 'id' );

		$course_links = array();

		// Access plans.
		$course_links['access_plans'] = array(
			'href' => add_query_arg(
				'post',
				$course_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'access-plans' ) )
			),
		);

		// Enrollments.
		$course_links['enrollments'] = array(
			'href' => add_query_arg(
				'post',
				$course_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'enrollments' ) )
			),
		);

		// Insturctors.
		$course_links['instructors'] = array(
			'href' => add_query_arg(
				'post',
				$course_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'instructors' ) )
			),
		);

		// Prerequisite.
		$prerequisite = $course->get_prerequisite_id();
		if ( ! empty( $prerequisite ) ) {
			$course_links['prerequisites'][] = array(
				'type' => $this->post_type,
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $prerequisite ) ),
			);
		}

		// Prerequisite track.
		$prerequisite_track = $course->get_prerequisite_id( 'course_track' );
		if ( ! empty( $prerequisite_track ) ) {
			$course_links['prerequisites'][] = array(
				'type' => 'track',
				'href' => rest_url( sprintf( 'wp/v2/%s/%d', 'course_track', $prerequisite_track ) ),
			);
		}

		// Students.
		$course_links['students'] = array(
			'href' => add_query_arg(
				'enrolled_in',
				$course_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'students' ) )
			),
		);

		return array_merge( $links, $course_links );
	}

}
