<?php
/**
 * REST Quiz Attempts Controller.
 *
 * @package LLMS_REST
 *
 * @since [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Quiz_Attempts_Controller class.
 */
class LLMS_REST_Quiz_Attempts_Controller extends LLMS_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'quiz-attempts';


	/**
	 * Collection params.
	 *
	 * @var array()
	 */
	protected $collection_params;


	/**
	 * Schema properties available for ordering the collection.
	 *
	 * @var string[]
	 */
	protected $orderby_properties = array(
		'start_date',
		'update_date',
		'end_date',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0-beta.1
	 */
	public function __construct() {
		$this->collection_params = $this->build_collection_params();
	}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.7 Fixed description of the `post_id` path parameter.
	 * @since 1.0.0-beta.10 Add `trigger` param for create/update/delete endpoints.
	 *                      Use backticks in args descriptions.
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
					'post_id' => array(
						'description' => __( 'Unique quiz attempt ID.', 'lifterlms' ),
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

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.4 Everybody who can view the enrollment's student can list the enrollments although
	 *                     the single enrollment permission will be checked in
	 *                     `LLMS_REST_Enrollments_Controller::get_objects()`.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! $this->check_read_permission( $request ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Get a collection of enrollments.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$response = parent::get_items( $request );
		// Specs require 404 when no quiz attempts are found.
		if ( ! is_wp_error( $response ) && empty( $response->data ) ) {
			return llms_rest_not_found_error();
		}

		return $response;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! $this->check_read_permission( $request ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Get a single item.
	 *
	 * @since 1.0.0-beta.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$object = $this->get_object( (int) $request['id'] );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$response = $this->prepare_item_for_response( $object, $request );

		return $response;
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0-beta.1
	 * @since 1.0.0-beta.4 Fix call to undefined function llms_rest_bad_request(),
	 *                     must be llms_rest_bad_request_error().
	 *
	 * @param int $student_id Student ID.
	 * @param int $post_id The course/membership ID.
	 * @return object|WP_Error
	 */
	protected function get_object( $attempt_id ) {

		if ( empty( $attempt_id ) ) {
			return llms_rest_bad_request_error();
		}

		$query_args = $this->prepare_object_query_args( $attempt_id );
		$query      = $this->get_objects_query( $query_args );
		$items      = $this->get_objects_from_query( $query );

		if ( $items ) {
			return $items[0];
		}

		return llms_rest_not_found_error();
	}

	/**
	 * Prepare enrollments objects query.
	 *
	 * @since 1.0.0-beta.7
	 * @since 1.0.0-beta.10 Set query limit to 1.
	 *
	 * @param int $attempt_id Student ID.
	 * @return array
	 */
	protected function prepare_object_query_args( $attempt_id ) {

		$args = array();

		$args['id']            = $attempt_id;
		$args['no_found_rows'] = true;
		$args['per_page']      = 1;

		$args = $this->prepare_items_query( $args );

		return $args;
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
	 * Build the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	protected function build_collection_params() {

		$query_params = parent::get_collection_params();

		unset( $query_params['include'], $query_params['exclude'] );

		$query_params['status'] = array(
			'description'       => __( 'Filter results to records matching the specified status.', 'lifterlms' ),
			'enum'              => array_keys( llms_get_quiz_attempt_statuses() ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_params['post'] = array(
			'description'       => __( 'Limit results to a specific lesson or a list of lessons. Accepts a single post id or a comma separated list of post ids.', 'lifterlms' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

	/**
	 * Get the Quiz Attempt's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	protected function get_item_schema_base() {

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'students-enrollments',
			'type'       => 'object',
			'properties' => array(
				'student_id'     => array(
					'description' => __( 'The ID of the student.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'quiz_id'        => array(
					'description' => __( 'The ID of the quiz.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'lesson_id'      => array(
					'description' => __( 'The ID of the lesson.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'start_date'     => array(
					'description' => __( 'Start date. Format: `Y-m-d H:i:s`', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'update_date'    => array(
					'description' => __( 'Date last modified. Format: `Y-m-d H:i:s`', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'end_date'       => array(
					'description' => __( 'End date. Format: `Y-m-d H:i:s`', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'status'         => array(
					'description' => __( 'The status of the quiz attempt.', 'lifterlms' ),
					'enum'        => array_keys( llms_get_quiz_attempt_statuses() ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'type'        => 'string',
				),
				'attempt'        => array(
					'description' => __( 'The attempt number of the quiz.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'grade'          => array(
					'description' => __( 'The grade of the quiz attempt.', 'lifterlms' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'can_be_resumed' => array(
					'description' => __( 'Whether the quiz attempt can be resumed.', 'lifterlms' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Retrieve an array of objects from the result of $this->get_objects_query().
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @param WP_Query $query Query result.
	 * @return obj[]
	 */
	protected function get_objects_from_query( $query ) {

		return $query->items;
	}

	/**
	 * Prepare collection items for response.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @param array           $objects Array of objects to be prepared for response.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_collection_items_for_response( $objects, $request ) {

		$items = array();

		foreach ( $objects as $object ) {

			if ( ! $this->check_read_permission( $object ) ) {
				continue;
			}

			$item = $this->prepare_item_for_response( $object, $request );
			if ( ! is_wp_error( $item ) ) {
				$items[] = $this->prepare_response_for_collection( $item );
			}
		}

		return $items;
	}

	/**
	 * Retrieve pagination information from an objects query.
	 *
	 * @since 1.0.0-beta.7
	 *
	 * @param stdClass        $query    Objects query result returned by {@see LLMS_REST_Enrollments_Controller::get_objects_query()}.
	 * @param array           $prepared Array of collection arguments.
	 * @param WP_REST_Request $request  Request object.
	 * @return array {
	 *     Array of pagination information.
	 *
	 *     @type int $current_page  Current page number.
	 *     @type int $total_results Total number of results.
	 *     @type int $total_pages   Total number of results pages.
	 * }
	 */
	protected function get_pagination_data_from_query( $query, $prepared, $request ) {

		$total_results = (int) $query->found_results;
		$current_page  = isset( $prepared['page'] ) ? (int) $prepared['page'] : 1;
		$total_pages   = (int) ceil( $total_results / (int) $prepared['per_page'] );

		return compact( 'current_page', 'total_results', 'total_pages' );
	}

	/**
	 * Prepare enrollments objects query
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error
	 */
	protected function prepare_collection_query_args( $request ) {

		$prepared = parent::prepare_collection_query_args( $request );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		$prepared['id']   = $request['id'];
		$prepared['page'] = ! isset( $prepared['page'] ) ? 1 : $prepared['page'];

		return $this->prepare_items_query( $prepared, $request );
	}

	/**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @since [version]
	 *
	 * @param array           $prepared_args Optional. Prepared WP_Query arguments. Default empty array.
	 * @param WP_REST_Request $request       Optional. Full details about the request.
	 * @return array Items query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {

		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			$query_args[ $key ] = $value;
		}

		// Filters.
		if ( isset( $query_args['student'] ) && ! is_array( $query_args['student'] ) ) {
			$query_args['student'] = array_map( 'absint', explode( ',', $query_args['student'] ) );
		}
		if ( isset( $query_args['post'] ) && ! is_array( $query_args['post'] ) ) {
			$query_args['post'] = array_map( 'absint', explode( ',', $query_args['post'] ) );
		}

		// $query_args['is_students_route'] = $request ? false !== stristr( $request->get_route(), '/students/' ) : true;

		return $query_args;
	}

	/**
	 * Get enrollments query.
	 *
	 * @since [version]
	 *
	 * @param  array           $query_args Array of collection arguments.
	 * @param  WP_REST_Request $request    Optional. Full details about the request. Default null.
	 * @return stdClass An object with two fields: 'items' an array of OBJECT result of the query; 'found_results' the total found items.
	 */
	protected function get_objects_query( $query_args, $request = null ) {

		global $wpdb;

		// Maybe limit the query results depending on the page param.
		if ( isset( $query_args['page'] ) ) {
			$skip  = $query_args['page'] > 1 ? ( $query_args['page'] - 1 ) * $query_args['per_page'] : 0;
			$limit = $wpdb->prepare(
				'LIMIT %d, %d',
				array(
					$skip,
					$query_args['per_page'],
				)
			);
		} else {
			$limit = $wpdb->prepare(
				'LIMIT %d',
				$query_args['per_page']
			);
		}

		$filter = '';

		if ( isset( $query_args['id'] ) && ! empty( $query_args['id'] ) ) {
			$filter .= $wpdb->prepare( ' AND qa.student_id = %d', $query_args['id'] );
		}

		if ( isset( $query_args['status'] ) ) {
			$filter .= $wpdb->prepare( ' AND qa.status = %s', $query_args['status'] );
		}

		if ( isset( $query_args['orderby'], $query_args['order'] ) ) {
			$order = sprintf( 'ORDER BY %1$s %2$s', esc_sql( $query_args['orderby'] ), esc_sql( $query_args['order'] ) );
		} else {
			$order = '';
		}

		$query = new stdClass();

		$select_found_rows = empty( $query_args['no_found_rows'] ) ? esc_sql( 'SQL_CALC_FOUND_ROWS' ) : '';

		// the query.
		$query->items = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT {$select_found_rows} DISTINCT id, student_id, quiz_id, lesson_id, start_date, update_date, end_date, status, attempt, grade, can_be_resumed
				FROM {$wpdb->prefix}lifterlms_quiz_attempts AS qa
				WHERE 1=1
				{$filter}
				{$order}
				{$limit};
				",
				array(
					$query_args['id'],
				)
			)
		);// no-cache ok.
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$count = count( $query->items );

		if ( $count ) {
			foreach ( $query->items as $key => $item ) {
				$query->items[ $key ]->lesson_id  = (int) $item->lesson_id;
				$query->items[ $key ]->student_id = (int) $item->student_id;
				$query->items[ $key ]->quiz_id    = (int) $item->quiz_id;
			}
		}

		$query->found_results = empty( $query_args['no_found_rows'] ) ? absint( $wpdb->get_var( 'SELECT FOUND_ROWS()' ) ) : $count; // no-cache ok.

		return $query;
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param stdClass        $attempt Attempt object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	public function prepare_object_for_response( $attempt, $request ) {

		$prepared_quiz_attempt = get_object_vars( $attempt );

		// Apply filters.
		$prepared_quiz_attempt['status'] = apply_filters(
			'llms_get_quiz_attempt_status',
			$prepared_quiz_attempt['status'],
			$prepared_quiz_attempt['student_id'],
			$prepared_quiz_attempt['quiz_id'],
			$prepared_quiz_attempt['lesson_id']
		);

		// Filter data including only schema props.
		$data = array_intersect_key( $prepared_quiz_attempt, array_flip( $this->get_fields_for_response( $request ) ) );

		/**
		 * Filters the enrollment data for a response.
		 *
		 * @since 1.0.0-beta.10
		 *
		 * @param array           $data       Array of enrollment properties prepared for response.
		 * @param stdClass        $enrollment Enrollment object.
		 * @param WP_REST_Request $request    Full details about the request.
		 */
		return apply_filters( 'llms_rest_prepare_quiz_attempt_object_response', $data, $attempt, $request );
	}

	/**
	 * Prepare enrollments links for the request.
	 *
	 * @since [version]
	 *
	 * @param object          $attempt Attempt object data.
	 * @param WP_REST_Request $request    Request object.
	 * @return array Links for the given object.
	 */
	public function prepare_links( $attempt, $request ) {

		$links = array(
			'self'       => array(
				'href' => rest_url(
					sprintf( '/%s/%s/%d', 'llms/v1', 'quiz-attempts', $attempt->id )
				),
			),
			'collection' => array(
				'href' => rest_url(
					sprintf( '/%s/%s', 'llms/v1', 'quiz-attempts' )
				),
			),
			'student'    => array(
				'href'       => rest_url(
					sprintf( '/%s/%s/%d', 'llms/v1', 'students', $attempt->student_id )
				),
				'embeddable' => true,
			),
			'quiz'       => array(
				'href'       => rest_url(
					sprintf( '/%s/%s/%d', 'llms/v1', 'quizzes', $attempt->quiz_id )
				),
				'embeddable' => true,
			),
			'lesson'     => array(
				'href'       => rest_url(
					sprintf( '/%s/%s/%d', 'llms/v1', 'lessons', $attempt->lesson_id )
				),
				'embeddable' => true,
			),
		);

		/**
		 * Filters the enrollment's links.
		 *
		 * @since [version]
		 *
		 * @param array    $links      Links for the given enrollment.
		 * @param stdClass $attempt Attempt object.
		 */
		return apply_filters( 'llms_rest_quiz_attempt_links', $links, $attempt );
	}

	/**
	 * Checks if a quiz attempt can be read.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request The request array.
	 * @return bool Whether the enrollment can be read.
	 */
	protected function check_read_permission( $request ) {

		return current_user_can( 'manage_lifterlms' );
	}
}
