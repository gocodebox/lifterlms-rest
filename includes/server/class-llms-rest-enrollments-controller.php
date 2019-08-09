<?php
/**
 * REST Enrollments Controller Class
 *
 * @package LLMS_REST
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Enrollments_Controller
 *
 * @since [version]
 */
class LLMS_REST_Enrollments_Controller extends LLMS_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'students/(?P<id>[\d]+)/enrollments';

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
		'date_created',
		'date_updated',
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
			'/' . $this->rest_base . '/(?P<post_id>[\d]+)',
			array(
				'args'   => array(
					'post_id' => array(
						'description' => __( 'Unique course, lesson, or section Identifer. The WordPress Post ID.', 'lifterlms' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_get_item_params(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( 'PATCH' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		// Everybody can list enrollments (in read mode)?
		if ( 'edit' === $request['context'] && ! $this->check_update_permission() ) {
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
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args, $request );

		if ( is_wp_error( $query_results ) ) {
			return $query_results;
		}

		$page        = (int) $query_args['page'];
		$page        = $page ? $page : 1;
		$max_pages   = $query_results['pages'];
		$total_posts = $query_results['total'];
		$objects     = $query_results['objects'];

		if ( $page > $max_pages && $total_posts > 0 ) {
			return llms_rest_bad_request_error( __( 'The page number requested is larger than the number of pages available.', 'lifterlms' ) );
		}

		// Specs require 404 when no course enrollments are found.
		if ( empty( $query_results['objects'] ) ) {
			return llms_rest_not_found_error();
		}

		$response = rest_ensure_response( $objects );

		$response->header( 'X-WP-Total', $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();

		$base = add_query_arg(
			urlencode_deep( $request_params ),
			rest_url( $request->get_route() )
		);

		// Add first page.
		$first_link = add_query_arg( 'page', 1, $base );
		$response->link_header( 'first', $first_link );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}
		// Add last page.
		$last_link = add_query_arg( 'page', $max_pages, $base );
		$response->link_header( 'last', $last_link );

		return $response;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		$enrollment_exists = $this->enrollment_exists( (int) $request['id'], (int) $request['post_id'] );
		if ( is_wp_error( $enrollment_exists ) ) {
			return $enrollment_exists;
		}

		if ( 'edit' === $request['context'] && ! $this->check_update_permission() ) {
			return llms_rest_authorization_required_error();
		}

		$object = new stdClass();

		$object->id = (int) $request['id'];
		$object->id = (int) $request['post_id'];

		if ( ! $this->check_read_permission( $object ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Get a single item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$object = $this->get_object( (int) $request['id'], (int) $request['post_id'] );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$response = $this->prepare_item_for_response( $object, $request );

		return $response;

	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {

		$enrollment_exists = $this->enrollment_exists( (int) $request['id'], (int) $request['post_id'], false );

		if ( $enrollment_exists ) {
			return llms_rest_bad_request_error( __( 'Cannot create existing enrollment.', 'lifterlms' ) );
		}

		if ( ! $this->check_create_permission() ) {
			return llms_rest_authorization_required_error( __( 'Sorry, you are not allowed to create an enrollment as this user.', 'lifterlms' ) );
		}

		return true;
	}


	/**
	 * Creates a single enrollment.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		$user_id = (int) $request['id'];
		$post_id = (int) $request['post_id'];

		// check both students and product exist.
		$student = new LLMS_Student( $user_id );

		if ( ! $student->exists() ) {
			return llms_rest_not_found_error();
		}

		// can only be enrolled in the following post types.
		$product_type = get_post_type( $post_id );
		if ( ! $product_type ) {
			return llms_rest_not_found_error();
		}
		if ( ! in_array( $product_type, array( 'course', 'llms_membership' ), true ) ) {
			return llms_rest_bad_request_error();
		}

		// Enroll.
		$enroll = $student->enroll( $post_id, 'admin_' . get_current_user_id() );

		// Something went wrong internally.
		if ( ! $enroll ) {
			return llms_rest_server_error( __( 'The enrollment could not be created', 'lifterlms' ) );
		}

		$request->set_param( 'context', 'edit' );
		$enrollment = $this->get_object( $user_id, $post_id );

		$response = $this->prepare_item_for_response( $enrollment, $request );

		$response->set_status( 201 );

		$response->header(
			'Location',
			rest_url( sprintf( '/%s/%s/%d/%s/%d', 'llms/v1', 'students', $enrollment->student_id, 'enrollments', $enrollment->post_id ) )
		);

		return $response;

	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		$enrollment_exists = $this->enrollment_exists( (int) $request['id'], (int) $request['post_id'] );
		if ( is_wp_error( $enrollment_exists ) ) {
			return $enrollment_exists;
		}

		if ( ! $this->check_update_permission() ) {
			return llms_rest_authorization_required_error( __( 'Sorry, you are not allowed to update an enrollment as this user.', 'lifterlms' ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {

		$enrollment_exists = $this->enrollment_exists( (int) $request['id'], (int) $request['post_id'] );
		if ( is_wp_error( $enrollment_exists ) ) {
			// Enrollment not found, we don't return a 404.
			if ( in_array( 'llms_rest_not_found', $enrollment_exists->get_error_codes(), true ) ) {
				return true;
			}

			return $enrollment_exists;
		}

		if ( ! $this->check_delete_permission() ) {
			return llms_rest_authorization_required_error();
		}

		return true;

	}

	/**
	 * Deletes a single llms post.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {

		$enrollment_exists = $this->enrollment_exists( (int) $request['id'], (int) $request['post_id'] );
		$response          = new WP_REST_Response();
		$response->set_status( 204 );

		if ( is_wp_error( $enrollment_exists ) ) {
			// Enrollment not found, we don't return a 404.
			if ( in_array( 'llms_rest_not_found', $enrollment_exists->get_error_codes(), true ) ) {
				return true;
			}

			return $enrollment_exists;
		}

		$result = llms_delete_student_enrollment( (int) $request['id'], (int) $request['post_id'] );

		if ( ! $result ) {
			return llms_rest_server_error( __( 'The enrollment cannot be deleted.', 'lifterlms' ) );
		}

		return rest_ensure_response( $response );

	}

	/**
	 * Check enrollment existence.
	 *
	 * @since [version]
	 *
	 * @param int     $student_id Student ID.
	 * @param int     $post_id The course/membership ID.
	 * @param boolean $wp_error Optional. Whether return a WP_Error instance or a boolean. Default true (returns WP_Error).
	 * @return WP_Error|boolean
	 */
	protected function enrollment_exists( $student_id, $post_id, $wp_error = true ) {

		$student = llms_get_student( $student_id );

		if ( empty( $student ) ) {
			return $wp_error ? llms_rest_bad_request_error() : false;
		}

		$current_status = $student->get_enrollment_status( $post_id );

		if ( empty( $current_status ) ) {
			return $wp_error ? llms_rest_not_found_error() : false;
		}

		return true;

	}

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $student_id Student ID.
	 * @param int $post_id The course/membership ID.
	 * @return object|WP_Error
	 */
	protected function get_object( $student_id, $post_id = null ) {

		if ( empty( $post_id ) ) {
			return llms_rest_bad_request();
		}

		$query_args = $this->prepare_object_query( $student_id, $post_id );
		$result     = $this->query_enrollments( $query_args );

		if ( $result->items ) {
			return $result->items[0];
		}

		return llms_rest_not_found_error();
	}

	/**
	 * Prepare enrollments objects query.
	 *
	 * @since [version]
	 *
	 * @param int $student_id Student ID.
	 * @param int $post_id The course/membership ID.
	 * @return array
	 */
	protected function prepare_object_query( $student_id, $post_id ) {

		$args = array();

		$args['id']   = $student_id;
		$args['post'] = $post_id;

		$args = $this->prepare_items_query( $args );

		return $args;

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
			'enum'              => array_keys( llms_get_enrollment_statuses() ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_params['post'] = array(
			'description'       => __( 'Limit results to a specific course or membership or a list of courses and/or memberships. Accepts a single post id or a comma separated list of post ids.', 'lifterlms' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

	/**
	 * Get the Enrollments's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'students-enrollments',
			'type'       => 'object',
			'properties' => array(
				'post_id'      => array(
					'description' => __( 'The ID of the course/membership.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'student_id'   => array(
					'description' => __( 'The ID of the student.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( 'Creation date. Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_updated' => array(
					'description' => __( 'Date last modified. Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'       => array(
					'description' => __( 'The status of the enrollment.', 'lifterlms' ),
					'enum'        => array_keys( llms_get_enrollment_statuses() ),
					'context'     => array( 'view', 'edit' ),
					'type'        => 'string',
				),
			),
		);

		return $schema;
	}

	/**
	 * Prepare enrollments objects query.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {

		// Retrieve the list of registered collection query parameters.
		$registered_params = $this->get_collection_params();
		$args              = array();

		/*
		* For each known parameter which is both registered and present in the request,
		* set the parameter's value on the query $args.
		*/
		foreach ( array_keys( $registered_params ) as $param ) {
			if ( isset( $request[ $param ] ) ) {
				$args[ $param ] = $request[ $param ];
			}
		}

		$args['id']   = $request['id'];
		$args['page'] = ! isset( $args['page'] ) ? 1 : 0;

		$args = $this->prepare_items_query( $args, $request );

		return $args;

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

		if ( isset( $query_args['orderby'] ) ) {
			switch ( $query_args['orderby'] ) {
				case 'date_updated':
					$query_args['orderby'] = 'upm2.updated_date';
					break;
				case 'date_created':
					$query_args['orderby'] = 'upm.updated_date';
					break;
				default:
					unset( $query_args['orderby'] );
					break;
			}
		}

		$query_args['is_students_route'] = $request ? false !== stristr( $request->get_route(), '/students/' ) : true;

		return $query_args;

	}

	/**
	 * Get enrollments objects.
	 *
	 * @since [version]
	 *
	 * @param array           $query_args Query args.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	public function get_objects( $query_args, $request ) {

		$objects = array();
		$result  = $this->query_enrollments( $query_args );

		foreach ( $result->items as $enrollment ) {

			if ( ! $this->check_read_permission( $enrollment ) ) {
				continue;
			}

			$response_object = $this->prepare_item_for_response( $enrollment, $request );

			if ( ! is_wp_error( $response_object ) ) {
				$objects[] = $this->prepare_response_for_collection( $response_object );
			}
		}

		$total_items = $result->found_items;

		if ( $total_items < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = $this->query_enrollments( $query_args );
			$total_posts = $count_query->found_items;
		}

		return array(
			'objects' => $objects,
			'total'   => (int) $total_items,
			'pages'   => (int) ceil( $total_items / (int) $query_args['per_page'] ),
		);
	}

	/**
	 * Get enrollments query
	 *
	 * @since [version]
	 *
	 * @param array $query_args Query args.
	 * @return object An object with two fields: items an array of OBJECT result of the query; found_items the total found items
	 */
	protected function query_enrollments( $query_args ) {
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
			$limit = '';
		}

		/**
		 * List enrollments of the current student_id or post_id.
		 * Depends on the endpoint route.
		 */
		if ( $query_args['is_students_route'] ) {
			$id_column = 'user_id';
		} else {
			$id_column = 'post_id';
		}

		/**
		 * Filter the enrollments by user_id or post_id param
		 */
		if ( isset( $query_args['student'] ) ) {
			$filter = sprintf( ' AND upm.user_id IN ( %s )', implode( ', ', $query_args['student'] ) );
		} elseif ( isset( $query_args['post'] ) ) {
			$filter = sprintf( ' AND upm.post_id IN ( %s )', implode( ', ', $query_args['post'] ) );
		} else {
			$filter = '';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$updated_date_status = $wpdb->prepare(
			"(
				SELECT user_id, post_id, updated_date, meta_value
				FROM {$wpdb->prefix}lifterlms_user_postmeta as upm
				WHERE upm.{$id_column} = %d
				$filter AND upm.meta_key = '_status'
				AND upm.updated_date = (
					SELECT MAX( upm2.updated_date )
					FROM {$wpdb->prefix}lifterlms_user_postmeta AS upm2
					WHERE upm2.meta_key = '_status'
					AND upm2.post_id = upm.post_id
					AND upm2.user_id = upm.user_id
				)
				$limit
			)",
			array(
				$query_args['id'],
			)
		);

		if ( isset( $query_args['status'] ) ) {
			$filter .= $wpdb->prepare( ' AND upm2.meta_value = %s', $query_args['status'] );
		}

		if ( isset( $query_args['orderby'], $query_args['order'] ) ) {
			$order = sprintf( 'ORDER BY %1$s %2$s', esc_sql( $query_args['orderby'] ), esc_sql( $query_args['order'] ) );
		} else {
			$order = '';
		}

		$query = new stdClass();

		// the query.
		$query->items = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT SQL_CALC_FOUND_ROWS DISTINCT upm.post_id AS post_id, upm.user_id as student_id, upm.updated_date as date_created, upm2.updated_date as date_updated, upm2.meta_value as status
				FROM {$wpdb->prefix}lifterlms_user_postmeta AS upm
				JOIN {$updated_date_status} as upm2 ON upm.post_id = upm2.post_id AND upm.user_id = upm2.user_id
				JOIN {$wpdb->posts} AS p ON p.ID = upm.post_id
				WHERE p.post_status = 'publish'
				  AND upm.meta_key = '_start_date'
				  AND upm.{$id_column} = %d
				  {$filter}
				{$order}
				{$limit};
				",
				array(
					$query_args['id'],
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query->found_items = absint( $wpdb->get_var( 'SELECT FOUND_ROWS()' ) );

		return $query;
	}

	/**
	 * Prepare a single item for the REST response
	 *
	 * @since [version]
	 *
	 * @param object          $object  Enrollment data object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $object, $request ) {

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->prepare_object_for_response( $object, $request );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		// Filter data by context. E.g. in "view" mode the password property won't be allowed.
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $object );
		$response->add_links( $links );

		return $response;
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param stdClass        $enrollment Enrollment object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	public function prepare_object_for_response( $enrollment, $request ) {

		$prepared_enrollment = get_object_vars( $enrollment );

		// Apply filters.
		$prepared_enrollment['status'] = apply_filters(
			'llms_get_enrollment_status',
			$prepared_enrollment['status'],
			$prepared_enrollment['student_id'],
			$prepared_enrollment['post_id']
		);

		return $prepared_enrollment;

	}

	/**
	 * Prepare enrollments links for the request.
	 *
	 * @since [version]
	 *
	 * @param object $enrollment Enrollment object data.
	 * @return array Links for the given object.
	 */
	public function prepare_links( $enrollment ) {

		$links = array(
			'self'       => array(
				'href' => rest_url(
					sprintf( '/%s/%s/%d/%s/%d', 'llms/v1', 'students', $enrollment->student_id, 'enrollments', $enrollment->post_id )
				),
			),
			'collection' => array(
				'href' => rest_url(
					sprintf( '/%s/%s/%d/%s', 'llms/v1', 'students', $enrollment->student_id, 'enrollments' )
				),
			),
			'student'    => array(
				'href' => rest_url(
					sprintf( '/%s/%s/%d', 'llms/v1', 'students', $enrollment->student_id )
				),
			),
		);

		switch ( get_post_type( $enrollment->post_id ) ) :
			case 'course':
				$links['post'] = array(
					'type' => 'course',
					'href' => rest_url(
						sprintf( '/%s/%s/%d', 'llms/v1', 'courses', $enrollment->post_id )
					),
				);
				break;

			case 'llms_membership':
				$links['post'] = array(
					'type' => 'llms_membership',
					'href' => rest_url(
						sprintf( '/%s/%s/%d', 'llms/v1', 'memberships', $enrollment->post_id )
					),
				);
				break;
		endswitch;

		return $links;
	}

	/**
	 * Checks if an enrollment can be edited.
	 *
	 * @since [version]
	 *
	 * @return bool Whether the enrollment can be created
	 */
	protected function check_create_permission() {
		return current_user_can( 'enroll' );
	}

	/**
	 * Checks if an enrollment can be updated
	 *
	 * @since [version]
	 *
	 * @return bool Whether the enrollment can be edited.
	 */
	protected function check_update_permission() {
		return current_user_can( 'enroll' ) && current_user_can( 'unenroll' );

	}

	/**
	 * Checks if an enrollment can be deleted
	 *
	 * @since [version]
	 *
	 * @return bool Whether the enrollment can be deleted.
	 */
	protected function check_delete_permission() {
		return current_user_can( 'unenroll' );
	}

	/**
	 * Checks if an llms post can be read.
	 *
	 * @since [version]
	 *
	 * @param mixed $enrollment The enrollment object.
	 * @return bool Whether the enrollment can be read.
	 */
	protected function check_read_permission( $enrollment ) {

		/**
		 * As of now, enrollments of password protected courses cannot be read
		 */
		if ( post_password_required( $enrollment->post_id ) ) {
			return false;
		}

		// @TODO: who can read this enrollment?
		return true; // current_user_can('view_others_lifterlms_reports');
	}

}
